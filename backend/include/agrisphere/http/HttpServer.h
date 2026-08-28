// AgriSphere C++ Backend - small blocking HTTP/1.1 server built on Winsock.
//
// It exists so the C++ layer can be a *persistent service*: the process starts
// once, DatabaseManager opens its pool once, and every PHP request (and, later,
// every Android request) is served by the same long-lived instance.
#pragma once

#include "agrisphere/core/Config.h"
#include "agrisphere/http/Router.h"

#include <atomic>
#include <condition_variable>
#include <cstdint>
#include <deque>
#include <mutex>
#include <string>
#include <thread>
#include <vector>

namespace agri {
namespace http {

class HttpServer {
public:
    HttpServer(ServerConfig config, const Router& router);
    ~HttpServer();

    HttpServer(const HttpServer&) = delete;
    HttpServer& operator=(const HttpServer&) = delete;

    // Binds, listens and blocks in the accept loop until stop() is called.
    void run();
    void stop();

private:
#if defined(_WIN32)
    using SocketHandle = std::uintptr_t;
#else
    using SocketHandle = int;
#endif
    static const SocketHandle kInvalidSocket;

    ServerConfig config_;
    const Router& router_;

    SocketHandle listener_ = kInvalidSocket;
    std::atomic<bool> running_{false};

    std::vector<std::thread> workers_;
    std::deque<SocketHandle> queue_;
    std::mutex mutex_;
    std::condition_variable queued_;

    void workerLoop();
    void handleConnection(SocketHandle client);
    bool readRequest(SocketHandle client, std::string& buffer, Request& request, bool& keepAlive);
    void sendResponse(SocketHandle client, const Response& response, bool keepAlive);
    bool isAuthorized(const Request& request) const;
    void closeSocket(SocketHandle handle);
};

} // namespace http
} // namespace agri
