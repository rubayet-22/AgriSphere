#include "agrisphere/http/Router.h"

namespace agri {
namespace http {

void Router::add(const std::string& method, const std::string& path, Handler handler) {
    routes_[path][method] = std::move(handler);
}

bool Router::hasPath(const std::string& path) const {
    return routes_.find(path) != routes_.end();
}

Response Router::dispatch(const Request& request) const {
    const auto pathIt = routes_.find(request.path);
    if (pathIt == routes_.end()) {
        return Response::error(404, "No such endpoint: " + request.path);
    }
    const auto methodIt = pathIt->second.find(request.method);
    if (methodIt == pathIt->second.end()) {
        return Response::error(405, "Method " + request.method + " not allowed for " + request.path);
    }
    return methodIt->second(request);
}

} // namespace http
} // namespace agri
