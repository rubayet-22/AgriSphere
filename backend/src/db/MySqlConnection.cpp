#include "agrisphere/db/MySqlConnection.h"

#include "agrisphere/core/Logger.h"
#include "agrisphere/core/Sha1.h"
#include "agrisphere/core/Strings.h"

#if defined(_WIN32)
#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#include <winsock2.h>
#include <ws2tcpip.h>
#else
#include <arpa/inet.h>
#include <netdb.h>
#include <sys/socket.h>
#include <unistd.h>
#endif

#include <cstring>
#include <mutex>

namespace agri {
namespace db {
namespace {

// ---- capability flags -------------------------------------------------------
constexpr std::uint32_t CLIENT_LONG_PASSWORD = 0x00000001;
constexpr std::uint32_t CLIENT_LONG_FLAG = 0x00000004;
constexpr std::uint32_t CLIENT_CONNECT_WITH_DB = 0x00000008;
constexpr std::uint32_t CLIENT_PROTOCOL_41 = 0x00000200;
constexpr std::uint32_t CLIENT_TRANSACTIONS = 0x00002000;
constexpr std::uint32_t CLIENT_SECURE_CONNECTION = 0x00008000;
constexpr std::uint32_t CLIENT_PLUGIN_AUTH = 0x00080000;

// ---- commands ---------------------------------------------------------------
constexpr std::uint8_t COM_QUERY = 0x03;
constexpr std::uint8_t COM_PING = 0x0E;
constexpr std::uint8_t COM_QUIT = 0x01;

constexpr std::uint8_t PACKET_OK = 0x00;
constexpr std::uint8_t PACKET_EOF = 0xFE;
constexpr std::uint8_t PACKET_ERR = 0xFF;
constexpr std::uint8_t PACKET_LOCAL_INFILE = 0xFB;

// utf8mb4_general_ci - matches `SET NAMES utf8mb4` used by the PHP layer.
constexpr std::uint8_t CHARSET_UTF8MB4_GENERAL_CI = 45;

#if defined(_WIN32)
void ensureWinsock() {
    static std::once_flag flag;
    std::call_once(flag, []() {
        WSADATA data;
        if (WSAStartup(MAKEWORD(2, 2), &data) != 0) {
            throw DbError("WSAStartup failed");
        }
    });
}
int lastSocketError() { return WSAGetLastError(); }
#else
void ensureWinsock() {}
int lastSocketError() { return errno; }
#endif

class PacketReader {
public:
    explicit PacketReader(const std::vector<std::uint8_t>& payload) : data_(payload) {}

    bool eof() const { return pos_ >= data_.size(); }
    std::size_t remaining() const { return data_.size() - pos_; }
    std::size_t position() const { return pos_; }
    void skip(std::size_t count) { pos_ = std::min(data_.size(), pos_ + count); }

    std::uint8_t readByte() {
        require(1);
        return data_[pos_++];
    }

    std::uint32_t readFixed(std::size_t bytes) {
        require(bytes);
        std::uint32_t value = 0;
        for (std::size_t i = 0; i < bytes; ++i) {
            value |= static_cast<std::uint32_t>(data_[pos_ + i]) << (8 * i);
        }
        pos_ += bytes;
        return value;
    }

    std::uint64_t readLengthEncodedInt(bool* isNull = nullptr) {
        if (isNull) *isNull = false;
        const std::uint8_t first = readByte();
        if (first < 0xFB) {
            return first;
        }
        if (first == 0xFB) {
            if (isNull) *isNull = true;
            return 0;
        }
        if (first == 0xFC) return readFixed(2);
        if (first == 0xFD) return readFixed(3);
        // 0xFE
        require(8);
        std::uint64_t value = 0;
        for (std::size_t i = 0; i < 8; ++i) {
            value |= static_cast<std::uint64_t>(data_[pos_ + i]) << (8 * i);
        }
        pos_ += 8;
        return value;
    }

    std::string readLengthEncodedString(bool* isNull = nullptr) {
        bool nullFlag = false;
        const std::uint64_t length = readLengthEncodedInt(&nullFlag);
        if (isNull) *isNull = nullFlag;
        if (nullFlag) {
            return std::string();
        }
        require(static_cast<std::size_t>(length));
        std::string out(reinterpret_cast<const char*>(data_.data() + pos_),
                        static_cast<std::size_t>(length));
        pos_ += static_cast<std::size_t>(length);
        return out;
    }

    std::string readNullTerminatedString() {
        std::string out;
        while (pos_ < data_.size() && data_[pos_] != 0) {
            out.push_back(static_cast<char>(data_[pos_++]));
        }
        if (pos_ < data_.size()) {
            ++pos_; // consume the NUL
        }
        return out;
    }

    std::string readFixedString(std::size_t length) {
        require(length);
        std::string out(reinterpret_cast<const char*>(data_.data() + pos_), length);
        pos_ += length;
        return out;
    }

    std::string readRestOfPacket() {
        std::string out(reinterpret_cast<const char*>(data_.data() + pos_), data_.size() - pos_);
        pos_ = data_.size();
        return out;
    }

private:
    const std::vector<std::uint8_t>& data_;
    std::size_t pos_ = 0;

    void require(std::size_t count) const {
        if (pos_ + count > data_.size()) {
            throw DbError("malformed MySQL packet (truncated payload)");
        }
    }
};

void appendFixed(std::vector<std::uint8_t>& out, std::uint64_t value, std::size_t bytes) {
    for (std::size_t i = 0; i < bytes; ++i) {
        out.push_back(static_cast<std::uint8_t>((value >> (8 * i)) & 0xFF));
    }
}

void appendNullTerminated(std::vector<std::uint8_t>& out, const std::string& value) {
    out.insert(out.end(), value.begin(), value.end());
    out.push_back(0);
}

bool isEofPacket(const std::vector<std::uint8_t>& payload) {
    return !payload.empty() && payload[0] == PACKET_EOF && payload.size() < 9;
}

std::string decodeErrorPacket(const std::vector<std::uint8_t>& payload) {
    PacketReader reader(payload);
    reader.readByte(); // 0xFF
    const std::uint32_t code = reader.readFixed(2);
    std::string sqlState;
    if (reader.remaining() > 0 && payload[3] == '#') {
        reader.skip(1);
        sqlState = reader.readFixedString(5);
    }
    const std::string message = reader.readRestOfPacket();
    std::string out = "MySQL error " + std::to_string(code);
    if (!sqlState.empty()) {
        out += " (" + sqlState + ")";
    }
    out += ": " + message;
    return out;
}

// mysql_native_password:
//   token = SHA1(password) XOR SHA1(scramble || SHA1(SHA1(password)))
std::vector<std::uint8_t> nativePasswordToken(const std::string& password,
                                              const std::string& scramble) {
    if (password.empty()) {
        return {};
    }
    const crypto::Sha1Digest stage1 = crypto::sha1(password);
    const crypto::Sha1Digest stage2 = crypto::sha1(stage1.data(), stage1.size());

    std::vector<std::uint8_t> seed(scramble.begin(), scramble.end());
    seed.insert(seed.end(), stage2.begin(), stage2.end());
    const crypto::Sha1Digest stage3 = crypto::sha1(seed.data(), seed.size());

    std::vector<std::uint8_t> token(stage1.size());
    for (std::size_t i = 0; i < stage1.size(); ++i) {
        token[i] = static_cast<std::uint8_t>(stage1[i] ^ stage3[i]);
    }
    return token;
}

} // namespace

MySqlConnection::MySqlConnection(DbConfig config) : config_(std::move(config)) {}

MySqlConnection::~MySqlConnection() { close(); }

void MySqlConnection::throwSocketError(const std::string& context) const {
    throw DbError(context + " (socket error " + std::to_string(lastSocketError()) + ")");
}

void MySqlConnection::openSocket() {
    ensureWinsock();

    addrinfo hints{};
    hints.ai_family = AF_UNSPEC;
    hints.ai_socktype = SOCK_STREAM;
    hints.ai_protocol = IPPROTO_TCP;

    addrinfo* resolved = nullptr;
    const std::string portText = std::to_string(config_.port);
    if (getaddrinfo(config_.host.c_str(), portText.c_str(), &hints, &resolved) != 0 || !resolved) {
        throw DbError("cannot resolve MySQL host '" + config_.host + "'");
    }

    SocketHandle handle = kInvalidSocket;
    for (addrinfo* it = resolved; it != nullptr; it = it->ai_next) {
        handle = static_cast<SocketHandle>(::socket(it->ai_family, it->ai_socktype, it->ai_protocol));
        if (handle == kInvalidSocket) {
            continue;
        }
        if (::connect(static_cast<
#if defined(_WIN32)
                          SOCKET
#else
                          int
#endif
                      >(handle),
                      it->ai_addr, static_cast<int>(it->ai_addrlen)) == 0) {
            break;
        }
#if defined(_WIN32)
        ::closesocket(static_cast<SOCKET>(handle));
#else
        ::close(static_cast<int>(handle));
#endif
        handle = kInvalidSocket;
    }
    freeaddrinfo(resolved);

    if (handle == kInvalidSocket) {
        throwSocketError("cannot connect to MySQL at " + config_.host + ":" + portText);
    }

    socket_ = handle;

#if defined(_WIN32)
    const DWORD readTimeout = static_cast<DWORD>(config_.readTimeoutMs);
    const DWORD writeTimeout = static_cast<DWORD>(config_.connectTimeoutMs);
    ::setsockopt(static_cast<SOCKET>(socket_), SOL_SOCKET, SO_RCVTIMEO,
                 reinterpret_cast<const char*>(&readTimeout), sizeof(readTimeout));
    ::setsockopt(static_cast<SOCKET>(socket_), SOL_SOCKET, SO_SNDTIMEO,
                 reinterpret_cast<const char*>(&writeTimeout), sizeof(writeTimeout));
    const int noDelay = 1;
    ::setsockopt(static_cast<SOCKET>(socket_), IPPROTO_TCP, TCP_NODELAY,
                 reinterpret_cast<const char*>(&noDelay), sizeof(noDelay));
#endif
}

void MySqlConnection::close() {
    if (socket_ == kInvalidSocket) {
        return;
    }
    try {
        sendCommand(COM_QUIT, std::string());
    } catch (...) {
        // The server may already be gone; nothing useful to do here.
    }
#if defined(_WIN32)
    ::closesocket(static_cast<SOCKET>(socket_));
#else
    ::close(static_cast<int>(socket_));
#endif
    socket_ = kInvalidSocket;
}

void MySqlConnection::readExactly(std::uint8_t* buffer, std::size_t length) {
    std::size_t received = 0;
    while (received < length) {
        const int chunk = ::recv(static_cast<
#if defined(_WIN32)
                                     SOCKET
#else
                                     int
#endif
                                 >(socket_),
                                 reinterpret_cast<char*>(buffer + received),
                                 static_cast<int>(length - received), 0);
        if (chunk == 0) {
            throw DbError("MySQL connection closed by server");
        }
        if (chunk < 0) {
            throwSocketError("failed reading from MySQL");
        }
        received += static_cast<std::size_t>(chunk);
    }
}

void MySqlConnection::writeExactly(const std::uint8_t* buffer, std::size_t length) {
    std::size_t sent = 0;
    while (sent < length) {
        const int chunk = ::send(static_cast<
#if defined(_WIN32)
                                     SOCKET
#else
                                     int
#endif
                                 >(socket_),
                                 reinterpret_cast<const char*>(buffer + sent),
                                 static_cast<int>(length - sent), 0);
        if (chunk <= 0) {
            throwSocketError("failed writing to MySQL");
        }
        sent += static_cast<std::size_t>(chunk);
    }
}

std::vector<std::uint8_t> MySqlConnection::readPacket() {
    std::vector<std::uint8_t> payload;
    while (true) {
        std::uint8_t header[4];
        readExactly(header, 4);
        const std::size_t length = static_cast<std::size_t>(header[0]) |
                                   (static_cast<std::size_t>(header[1]) << 8) |
                                   (static_cast<std::size_t>(header[2]) << 16);
        sequenceId_ = static_cast<std::uint8_t>(header[3] + 1);

        const std::size_t offset = payload.size();
        payload.resize(offset + length);
        if (length > 0) {
            readExactly(payload.data() + offset, length);
        }
        // 0xFFFFFF means the payload continues in the next packet.
        if (length < 0xFFFFFF) {
            break;
        }
    }
    return payload;
}

void MySqlConnection::sendPacket(const std::vector<std::uint8_t>& payload, std::uint8_t sequenceId) {
    std::size_t offset = 0;
    std::uint8_t sequence = sequenceId;
    do {
        const std::size_t chunk = std::min<std::size_t>(payload.size() - offset, 0xFFFFFF);
        std::vector<std::uint8_t> frame;
        frame.reserve(chunk + 4);
        frame.push_back(static_cast<std::uint8_t>(chunk & 0xFF));
        frame.push_back(static_cast<std::uint8_t>((chunk >> 8) & 0xFF));
        frame.push_back(static_cast<std::uint8_t>((chunk >> 16) & 0xFF));
        frame.push_back(sequence++);
        frame.insert(frame.end(), payload.begin() + static_cast<std::ptrdiff_t>(offset),
                     payload.begin() + static_cast<std::ptrdiff_t>(offset + chunk));
        writeExactly(frame.data(), frame.size());
        offset += chunk;
    } while (offset < payload.size());
    sequenceId_ = sequence;
}

void MySqlConnection::performHandshake() {
    const std::vector<std::uint8_t> greeting = readPacket();
    if (greeting.empty()) {
        throw DbError("empty MySQL handshake packet");
    }
    if (greeting[0] == PACKET_ERR) {
        throw DbError(decodeErrorPacket(greeting));
    }

    PacketReader reader(greeting);
    const std::uint8_t protocolVersion = reader.readByte();
    if (protocolVersion != 10) {
        throw DbError("unsupported MySQL protocol version " + std::to_string(protocolVersion));
    }
    serverVersion_ = reader.readNullTerminatedString();
    reader.readFixed(4); // connection id
    std::string scramble = reader.readFixedString(8);
    reader.skip(1); // filler

    std::uint32_t serverCapabilities = reader.readFixed(2);
    std::string authPluginName = "mysql_native_password";
    if (reader.remaining() > 0) {
        reader.skip(1); // default charset
        reader.readFixed(2); // status flags
        serverCapabilities |= (reader.readFixed(2) << 16);
        const std::uint8_t authDataLength = reader.readByte();
        reader.skip(10); // reserved
        if (serverCapabilities & CLIENT_SECURE_CONNECTION) {
            const std::size_t part2 =
                std::max<std::size_t>(13, authDataLength > 8 ? authDataLength - 8u : 13u);
            std::string tail = reader.readFixedString(std::min(part2, reader.remaining()));
            if (!tail.empty() && tail.back() == '\0') {
                tail.pop_back();
            }
            scramble += tail;
        }
        if (serverCapabilities & CLIENT_PLUGIN_AUTH) {
            const std::string name = reader.readNullTerminatedString();
            if (!name.empty()) {
                authPluginName = name;
            }
        }
    }

    if (authPluginName != "mysql_native_password") {
        throw DbError("unsupported MySQL auth plugin '" + authPluginName +
                      "'. Set the account to mysql_native_password.");
    }

    // Note: CLIENT_MULTI_STATEMENTS is intentionally omitted.
    std::uint32_t clientCapabilities = CLIENT_LONG_PASSWORD | CLIENT_LONG_FLAG |
                                       CLIENT_PROTOCOL_41 | CLIENT_TRANSACTIONS;
    clientCapabilities |= (serverCapabilities & CLIENT_SECURE_CONNECTION);
    clientCapabilities |= (serverCapabilities & CLIENT_PLUGIN_AUTH);
    if (!config_.database.empty()) {
        clientCapabilities |= CLIENT_CONNECT_WITH_DB;
    }

    const std::vector<std::uint8_t> token = nativePasswordToken(config_.password, scramble);

    std::vector<std::uint8_t> response;
    appendFixed(response, clientCapabilities, 4);
    appendFixed(response, 0x01000000, 4); // 16 MB max packet
    response.push_back(CHARSET_UTF8MB4_GENERAL_CI);
    response.insert(response.end(), 23, 0);
    appendNullTerminated(response, config_.user);

    if (clientCapabilities & CLIENT_SECURE_CONNECTION) {
        response.push_back(static_cast<std::uint8_t>(token.size()));
        response.insert(response.end(), token.begin(), token.end());
    } else {
        response.insert(response.end(), token.begin(), token.end());
        response.push_back(0);
    }
    if (clientCapabilities & CLIENT_CONNECT_WITH_DB) {
        appendNullTerminated(response, config_.database);
    }
    if (clientCapabilities & CLIENT_PLUGIN_AUTH) {
        appendNullTerminated(response, authPluginName);
    }

    sendPacket(response, sequenceId_);

    std::vector<std::uint8_t> reply = readPacket();
    if (!reply.empty() && reply[0] == PACKET_EOF) {
        // Authentication switch request.
        PacketReader switchReader(reply);
        switchReader.readByte();
        const std::string plugin = switchReader.readNullTerminatedString();
        if (plugin != "mysql_native_password") {
            throw DbError("server requested unsupported auth plugin '" + plugin + "'");
        }
        std::string newScramble = switchReader.readRestOfPacket();
        if (!newScramble.empty() && newScramble.back() == '\0') {
            newScramble.pop_back();
        }
        const std::vector<std::uint8_t> newToken =
            nativePasswordToken(config_.password, newScramble);
        sendPacket(newToken, sequenceId_);
        reply = readPacket();
    }

    if (reply.empty()) {
        throw DbError("empty MySQL auth response");
    }
    if (reply[0] == PACKET_ERR) {
        throw DbError(decodeErrorPacket(reply));
    }
    if (reply[0] != PACKET_OK) {
        throw DbError("unexpected MySQL auth response byte " + std::to_string(reply[0]));
    }
}

void MySqlConnection::applySessionSettings() {
    // Fixing the charset is what makes byte-wise escaping safe.
    execute("SET NAMES " + config_.charset);

    // Escaping rules differ when the server runs with NO_BACKSLASH_ESCAPES.
    noBackslashEscapes_ = false;
    const ResultSet modes = query("SELECT @@sql_mode AS sql_mode");
    if (const auto row = modes.first()) {
        const std::string mode = strings::toLower(row->get("sql_mode"));
        noBackslashEscapes_ = mode.find("no_backslash_escapes") != std::string::npos;
    }
}

void MySqlConnection::connect() {
    close();
    openSocket();
    sequenceId_ = 0;
    performHandshake();
    applySessionSettings();
    Logger::debug("MySQL connection established (server " + serverVersion_ + ")");
}

void MySqlConnection::sendCommand(std::uint8_t command, const std::string& argument) {
    if (socket_ == kInvalidSocket) {
        throw DbError("MySQL connection is not open");
    }
    std::vector<std::uint8_t> payload;
    payload.reserve(argument.size() + 1);
    payload.push_back(command);
    payload.insert(payload.end(), argument.begin(), argument.end());
    sendPacket(payload, 0); // commands always restart the sequence at 0
}

bool MySqlConnection::ping() {
    if (socket_ == kInvalidSocket) {
        return false;
    }
    try {
        sendCommand(COM_PING, std::string());
        const std::vector<std::uint8_t> reply = readPacket();
        return !reply.empty() && reply[0] == PACKET_OK;
    } catch (...) {
        return false;
    }
}

ResultSet MySqlConnection::readResultSet(ExecResult* execOut) {
    std::vector<std::uint8_t> first = readPacket();
    if (first.empty()) {
        throw DbError("empty MySQL response packet");
    }
    if (first[0] == PACKET_ERR) {
        throw DbError(decodeErrorPacket(first));
    }
    if (first[0] == PACKET_OK) {
        PacketReader reader(first);
        reader.readByte();
        const std::uint64_t affected = reader.readLengthEncodedInt();
        const std::uint64_t insertId = reader.readLengthEncodedInt();
        if (execOut) {
            execOut->affectedRows = static_cast<long long>(affected);
            execOut->insertId = static_cast<long long>(insertId);
        }
        return ResultSet();
    }
    if (first[0] == PACKET_LOCAL_INFILE) {
        throw DbError("LOCAL INFILE responses are not supported");
    }

    PacketReader headerReader(first);
    const std::uint64_t columnCount = headerReader.readLengthEncodedInt();

    std::vector<std::string> columns;
    columns.reserve(static_cast<std::size_t>(columnCount));
    for (std::uint64_t i = 0; i < columnCount; ++i) {
        const std::vector<std::uint8_t> definition = readPacket();
        if (!definition.empty() && definition[0] == PACKET_ERR) {
            throw DbError(decodeErrorPacket(definition));
        }
        PacketReader defReader(definition);
        defReader.readLengthEncodedString(); // catalog
        defReader.readLengthEncodedString(); // schema
        defReader.readLengthEncodedString(); // table alias
        defReader.readLengthEncodedString(); // original table
        columns.push_back(defReader.readLengthEncodedString()); // column alias
        // The remainder (original name, charset, length, type, flags) is not
        // needed: the text protocol hands every value over as a string.
    }

    // Column definitions are terminated by an EOF packet because
    // CLIENT_DEPRECATE_EOF is not negotiated.
    const std::vector<std::uint8_t> separator = readPacket();
    if (!separator.empty() && separator[0] == PACKET_ERR) {
        throw DbError(decodeErrorPacket(separator));
    }

    ResultSet result;
    result.setColumns(columns);

    while (true) {
        const std::vector<std::uint8_t> packet = readPacket();
        if (packet.empty()) {
            throw DbError("unexpected empty row packet");
        }
        if (packet[0] == PACKET_ERR) {
            throw DbError(decodeErrorPacket(packet));
        }
        if (isEofPacket(packet)) {
            break;
        }
        PacketReader rowReader(packet);
        std::vector<std::optional<std::string>> values;
        values.reserve(columns.size());
        for (std::size_t i = 0; i < columns.size(); ++i) {
            bool isNull = false;
            std::string value = rowReader.readLengthEncodedString(&isNull);
            if (isNull) {
                values.emplace_back(std::nullopt);
            } else {
                values.emplace_back(std::move(value));
            }
        }
        result.addRow(std::move(values));
    }

    if (execOut) {
        execOut->affectedRows = static_cast<long long>(result.size());
        execOut->insertId = 0;
    }
    return result;
}

ResultSet MySqlConnection::query(const std::string& sql) {
    sendCommand(COM_QUERY, sql);
    return readResultSet(nullptr);
}

ExecResult MySqlConnection::execute(const std::string& sql) {
    sendCommand(COM_QUERY, sql);
    ExecResult outcome;
    readResultSet(&outcome);
    return outcome;
}

std::string MySqlConnection::escape(const std::string& raw) const {
    std::string out;
    out.reserve(raw.size() + 8);
    for (const char c : raw) {
        switch (c) {
            case '\'':
                // Valid in both default and NO_BACKSLASH_ESCAPES modes.
                out += "''";
                break;
            case '\\':
                if (noBackslashEscapes_) {
                    out.push_back('\\');
                } else {
                    out += "\\\\";
                }
                break;
            case '\0':
                if (!noBackslashEscapes_) {
                    out += "\\0";
                }
                // In NO_BACKSLASH_ESCAPES mode a NUL byte cannot be expressed
                // inside a quoted literal, so it is dropped.
                break;
            case '\n':
                out += noBackslashEscapes_ ? "\n" : "\\n";
                break;
            case '\r':
                out += noBackslashEscapes_ ? "\r" : "\\r";
                break;
            case '\x1a':
                out += noBackslashEscapes_ ? "\x1a" : "\\Z";
                break;
            default:
                out.push_back(c);
        }
    }
    return out;
}

} // namespace db
} // namespace agri
