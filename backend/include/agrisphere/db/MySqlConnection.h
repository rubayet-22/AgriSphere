// AgriSphere C++ Backend - a single MySQL/MariaDB connection.
//
// XAMPP does not ship a MySQL client library (no libmysql.dll / libmariadb.dll,
// no headers, no import library - PHP uses the built-in mysqlnd and the server
// links its client statically). Rather than forcing an extra SDK download, this
// class speaks the MySQL client/server protocol directly over Winsock:
//
//   * protocol version 10 handshake with mysql_native_password
//   * COM_QUERY for statements, full text protocol result set decoding
//   * OK / ERR / EOF packet handling, including multi-packet payloads
//
// CLIENT_MULTI_STATEMENTS is deliberately NOT negotiated, so the server refuses
// stacked queries - defence in depth on top of parameter escaping.
//
// One MySqlConnection is owned by one thread at a time; DatabaseManager hands
// them out from its pool.
#pragma once

#include "agrisphere/core/Config.h"
#include "agrisphere/db/ResultSet.h"

#include <cstdint>
#include <string>
#include <vector>

namespace agri {
namespace db {

class MySqlConnection {
public:
    explicit MySqlConnection(DbConfig config);
    ~MySqlConnection();

    MySqlConnection(const MySqlConnection&) = delete;
    MySqlConnection& operator=(const MySqlConnection&) = delete;
    MySqlConnection(MySqlConnection&&) = delete;
    MySqlConnection& operator=(MySqlConnection&&) = delete;

    // Throws DbError on failure.
    void connect();
    void close();
    bool isOpen() const { return socket_ != kInvalidSocket; }

    // Cheap liveness probe (COM_PING). Returns false instead of throwing so the
    // pool can transparently reconnect.
    bool ping();

    ResultSet query(const std::string& sql);
    ExecResult execute(const std::string& sql);

    // Escapes a value for use inside single quotes. Safe because the session
    // charset is fixed to utf8mb4, in which no multi-byte sequence can contain
    // an ASCII quote or backslash byte.
    std::string escape(const std::string& raw) const;

    const std::string& serverVersion() const { return serverVersion_; }

private:
#if defined(_WIN32)
    using SocketHandle = std::uintptr_t;
    static const SocketHandle kInvalidSocket = static_cast<SocketHandle>(~0ull);
#else
    using SocketHandle = int;
    static const SocketHandle kInvalidSocket = -1;
#endif

    DbConfig config_;
    SocketHandle socket_ = kInvalidSocket;
    std::uint8_t sequenceId_ = 0;
    std::string serverVersion_;
    bool noBackslashEscapes_ = false;

    void openSocket();
    void performHandshake();
    void applySessionSettings();

    void sendPacket(const std::vector<std::uint8_t>& payload, std::uint8_t sequenceId);
    std::vector<std::uint8_t> readPacket();
    void readExactly(std::uint8_t* buffer, std::size_t length);
    void writeExactly(const std::uint8_t* buffer, std::size_t length);

    void sendCommand(std::uint8_t command, const std::string& argument);
    ResultSet readResultSet(ExecResult* execOut);

    [[noreturn]] void throwSocketError(const std::string& context) const;
};

} // namespace db
} // namespace agri
