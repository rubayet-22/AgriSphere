// AgriSphere C++ Backend - exact-path router.
#pragma once

#include "agrisphere/http/HttpTypes.h"

#include <functional>
#include <map>
#include <string>

namespace agri {
namespace http {

using Handler = std::function<Response(const Request&)>;

class Router {
public:
    void add(const std::string& method, const std::string& path, Handler handler);
    void get(const std::string& path, Handler handler) { add("GET", path, std::move(handler)); }
    void post(const std::string& path, Handler handler) { add("POST", path, std::move(handler)); }

    // Returns 404/405 responses itself when nothing matches.
    Response dispatch(const Request& request) const;

    bool hasPath(const std::string& path) const;

private:
    std::map<std::string, std::map<std::string, Handler>> routes_; // path -> method -> handler
};

} // namespace http
} // namespace agri
