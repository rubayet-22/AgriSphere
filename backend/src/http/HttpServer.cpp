#include "agrisphere/http/HttpServer.h"

#include "agrisphere/core/Logger.h"
#include "agrisphere/core/Strings.h"

#if defined(_WIN32)
#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#include <winsock2.h>
#include <ws2tcpip.h>
#else
#include <netinet/in.h>
#include <sys/socket.h>
#include <unistd.h>
#endif

#include <algorithm>
#include <cstring>
#include <sstream>

namespace agri {
namespace http {
namespace {

constexpr std::size_t kMaxBodyBytes = 8u * 1024u * 1024u;
constexpr std::size_t kMaxHeaderBytes = 64u * 1024u;

#if defined(_WIN32)
void ensureWinsock() {
    static std::once_flag flag;
    std::call_once(flag, []() {
        WSADATA data;
        WSAStartup(MAKEWORD(2, 2), &data);
    });
}
inline SOCKET native(std::uintptr_t handle) { return static_cast<SOCKET>(handle); }
#else
void ensureWinsock() {}
inline int native(int handle) { return handle; }
#endif

const char* statusText(int status) {
    switch (status) {
        case 200: return "OK";
        case 400: return "Bad Request";
        case 401: return "Unauthorized";
        case 404: return "Not Found";
        case 405: return "Method Not Allowed";
        case 413: return "Payload Too Large";
        case 500: return "Internal Server Error";
        case 503: return "Service Unavailable";
        default: return "OK";
    }
}

// Length independent comparison for the shared API key.
bool constantTimeEquals(const std::string& a, const std::string& b) {
    unsigned char diff = static_cast<unsigned char>(a.size() ^ b.size());
    const std::size_t count = std::max<std::size_t>(a.size(), b.size());
    for (std::size_t i = 0; i < count; ++i) {
        const unsigned char left = i < a.size() ? static_cast<unsigned char>(a[i]) : 0;
        const unsigned char right = i < b.size() ? static_cast<unsigned char>(b[i]) : 0;
        diff |= static_cast<unsigned char>(left ^ right);
    }
    return diff == 0;
}

void parseQueryString(const std::string& raw, std::map<std::string, std::string>& out) {
    for (const std::string& pair : strings::split(raw, '&')) {
        if (pair.empty()) {
            continue;
        }
        const std::size_t eq = pair.find('=');
        if (eq == std::string::npos) {
            out[strings::urlDecode(pair)] = std::string();
        } else {
            out[strings::urlDecode(pair.substr(0, eq))] = strings::urlDecode(pair.substr(eq + 1));
        }
    }
}

} // namespace

#if defined(_WIN32)
const HttpServer::SocketHandle HttpServer::kInvalidSocket = static_cast<SocketHandle>(~0ull);
#else
const HttpServer::SocketHandle HttpServer::kInvalidSocket = -1;
#endif

HttpServer::HttpServer(ServerConfig config, const Router& router)
    : config_(std::move(config)), router_(router) {}

HttpServer::~HttpServer() { stop(); }

void HttpServer::closeSocket(SocketHandle handle) {
    if (handle == kInvalidSocket) {
        return;
    }
#if defined(_WIN32)
    ::closesocket(native(handle));
#else
    ::close(native(handle));
#endif
}

void HttpServer::run() {
    ensureWinsock();

    listener_ = static_cast<SocketHandle>(::socket(AF_INET, SOCK_STREAM, IPPROTO_TCP));
    if (listener_ == kInvalidSocket) {
        throw std::runtime_error("cannot create listening socket");
    }

    const int reuse = 1;
    ::setsockopt(native(listener_), SOL_SOCKET, SO_REUSEADDR,
                 reinterpret_cast<const char*>(&reuse), sizeof(reuse));

    sockaddr_in address{};
    address.sin_family = AF_INET;
    address.sin_port = htons(config_.port);
    if (::inet_pton(AF_INET, config_.bindAddress.c_str(), &address.sin_addr) != 1) {
        address.sin_addr.s_addr = htonl(INADDR_LOOPBACK);
    }

    if (::bind(native(listener_), reinterpret_cast<sockaddr*>(&address), sizeof(address)) != 0) {
        closeSocket(listener_);
        listener_ = kInvalidSocket;
        throw std::runtime_error("cannot bind " + config_.bindAddress + ":" +
                                 std::to_string(config_.port) +
                                 " (is another AgriSphere backend already running?)");
    }
    if (::listen(native(listener_), SOMAXCONN) != 0) {
        closeSocket(listener_);
        listener_ = kInvalidSocket;
        throw std::runtime_error("cannot listen on the backend port");
    }

    running_ = true;
    workers_.reserve(config_.workerThreads);
    for (std::size_t i = 0; i < config_.workerThreads; ++i) {
        workers_.emplace_back([this]() { workerLoop(); });
    }

    Logger::info("HTTP API listening on http://" + config_.bindAddress + ":" +
                 std::to_string(config_.port) + " (" + std::to_string(config_.workerThreads) +
                 " worker threads)");

    while (running_) {
        sockaddr_in peer{};
#if defined(_WIN32)
        int peerLength = sizeof(peer);
#else
        socklen_t peerLength = sizeof(peer);
#endif
        const SocketHandle client = static_cast<SocketHandle>(
            ::accept(native(listener_), reinterpret_cast<sockaddr*>(&peer), &peerLength));
        if (client == kInvalidSocket) {
            if (!running_) {
                break;
            }
            continue;
        }
        {
            std::lock_guard<std::mutex> lock(mutex_);
            queue_.push_back(client);
        }
        queued_.notify_one();
    }

    queued_.notify_all();
    for (std::thread& worker : workers_) {
        if (worker.joinable()) {
            worker.join();
        }
    }
    workers_.clear();
}

void HttpServer::stop() {
    if (!running_.exchange(false)) {
        return;
    }
    closeSocket(listener_);
    listener_ = kInvalidSocket;
    queued_.notify_all();
}

void HttpServer::workerLoop() {
    while (true) {
        SocketHandle client = kInvalidSocket;
        {
            std::unique_lock<std::mutex> lock(mutex_);
            queued_.wait(lock, [this]() { return !queue_.empty() || !running_; });
            if (queue_.empty()) {
                if (!running_) {
                    return;
                }
                continue;
            }
            client = queue_.front();
            queue_.pop_front();
        }
        handleConnection(client);
    }
}

bool HttpServer::readRequest(SocketHandle client,
                             std::string& buffer,
                             Request& request,
                             bool& keepAlive) {
    // Accumulate until the end of the header block.
    std::size_t headerEnd = buffer.find("\r\n\r\n");
    char chunk[8192];
    while (headerEnd == std::string::npos) {
        const int received = ::recv(native(client), chunk, sizeof(chunk), 0);
        if (received <= 0) {
            return false;
        }
        buffer.append(chunk, static_cast<std::size_t>(received));
        if (buffer.size() > kMaxHeaderBytes) {
            return false;
        }
        headerEnd = buffer.find("\r\n\r\n");
    }

    const std::string headerBlock = buffer.substr(0, headerEnd);
    std::istringstream stream(headerBlock);
    std::string line;
    if (!std::getline(stream, line)) {
        return false;
    }
    if (!line.empty() && line.back() == '\r') {
        line.pop_back();
    }

    std::istringstream requestLine(line);
    std::string target;
    std::string version;
    requestLine >> request.method >> target >> version;
    if (request.method.empty() || target.empty()) {
        return false;
    }

    const std::size_t questionMark = target.find('?');
    if (questionMark == std::string::npos) {
        request.path = target;
    } else {
        request.path = target.substr(0, questionMark);
        request.rawQuery = target.substr(questionMark + 1);
        parseQueryString(request.rawQuery, request.query);
    }

    request.headers.clear();
    while (std::getline(stream, line)) {
        if (!line.empty() && line.back() == '\r') {
            line.pop_back();
        }
        if (line.empty()) {
            continue;
        }
        const std::size_t colon = line.find(':');
        if (colon == std::string::npos) {
            continue;
        }
        request.headers[strings::toLower(strings::trim(line.substr(0, colon)))] =
            strings::trim(line.substr(colon + 1));
    }

    const std::size_t bodyStart = headerEnd + 4;
    const std::size_t contentLength =
        static_cast<std::size_t>(strings::toInt(request.header("content-length"), 0));
    if (contentLength > kMaxBodyBytes) {
        return false;
    }
    while (buffer.size() < bodyStart + contentLength) {
        const int received = ::recv(native(client), chunk, sizeof(chunk), 0);
        if (received <= 0) {
            return false;
        }
        buffer.append(chunk, static_cast<std::size_t>(received));
    }
    request.body = buffer.substr(bodyStart, contentLength);
    buffer.erase(0, bodyStart + contentLength);

    const std::string contentType = strings::toLower(request.header("content-type"));
    if (!request.body.empty()) {
        if (contentType.find("application/json") != std::string::npos) {
            std::string error;
            request.json = json::parse(request.body, &error);
            if (!error.empty()) {
                request.json = json::Value();
            }
        } else if (contentType.find("application/x-www-form-urlencoded") != std::string::npos) {
            // Accepted as well, so the API is easy to call from any client.
            std::map<std::string, std::string> form;
            parseQueryString(request.body, form);
            json::Value object = json::Value::object();
            for (const auto& entry : form) {
                object.set(entry.first, json::Value(entry.second));
            }
            request.json = object;
        }
    }

    const std::string connection = strings::toLower(request.header("connection"));
    keepAlive = (version != "HTTP/1.0") ? (connection != "close") : (connection == "keep-alive");
    return true;
}

bool HttpServer::isAuthorized(const Request& request) const {
    if (config_.apiKey.empty()) {
        return true;
    }
    if (request.path == "/health") {
        return true; // unauthenticated liveness probe
    }
    return constantTimeEquals(request.header("x-agri-key"), config_.apiKey);
}

void HttpServer::sendResponse(SocketHandle client, const Response& response, bool keepAlive) {
    std::ostringstream out;
    out << "HTTP/1.1 " << response.status << " " << statusText(response.status) << "\r\n";
    out << "Content-Type: " << response.contentType << "\r\n";
    out << "Content-Length: " << response.body.size() << "\r\n";
    out << "Connection: " << (keepAlive ? "keep-alive" : "close") << "\r\n";
    out << "Cache-Control: no-store\r\n";
    out << "X-Content-Type-Options: nosniff\r\n";
    out << "\r\n";
    out << response.body;

    const std::string payload = out.str();
    std::size_t sent = 0;
    while (sent < payload.size()) {
        const int chunk = ::send(native(client), payload.data() + sent,
                                 static_cast<int>(payload.size() - sent), 0);
        if (chunk <= 0) {
            return;
        }
        sent += static_cast<std::size_t>(chunk);
    }
}

void HttpServer::handleConnection(SocketHandle client) {
#if defined(_WIN32)
    const DWORD timeout = 30000;
    ::setsockopt(native(client), SOL_SOCKET, SO_RCVTIMEO,
                 reinterpret_cast<const char*>(&timeout), sizeof(timeout));
    ::setsockopt(native(client), SOL_SOCKET, SO_SNDTIMEO,
                 reinterpret_cast<const char*>(&timeout), sizeof(timeout));
#endif

    std::string buffer;
    while (running_) {
        Request request;
        bool keepAlive = true;
        if (!readRequest(client, buffer, request, keepAlive)) {
            break;
        }

        Response response;
        try {
            if (!isAuthorized(request)) {
                response = Response::error(401, "Missing or invalid X-Agri-Key header");
            } else {
                response = router_.dispatch(request);
            }
        } catch (const std::exception& error) {
            Logger::error(request.method + " " + request.path + " -> " + error.what());
            response = Response::error(500, error.what());
        } catch (...) {
            Logger::error(request.method + " " + request.path + " -> unknown error");
            response = Response::error(500, "Unhandled backend error");
        }

        sendResponse(client, response, keepAlive && running_);
        if (!keepAlive) {
            break;
        }
    }
    closeSocket(client);
}

} // namespace http
} // namespace agri
