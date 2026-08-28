#include "agrisphere/http/HttpTypes.h"

#include "agrisphere/core/Strings.h"

namespace agri {
namespace http {

bool Request::hasParam(const std::string& name) const {
    if (json.isObject() && json.has(name)) {
        return true;
    }
    return query.find(name) != query.end();
}

std::string Request::param(const std::string& name, const std::string& fallback) const {
    if (json.isObject()) {
        if (const json::Value* value = json.find(name)) {
            if (!value->isNull()) {
                return value->asString(fallback);
            }
        }
    }
    const auto it = query.find(name);
    return it == query.end() ? fallback : it->second;
}

long long Request::intParam(const std::string& name, long long fallback) const {
    if (json.isObject()) {
        if (const json::Value* value = json.find(name)) {
            if (!value->isNull()) {
                return value->asInt(fallback);
            }
        }
    }
    const auto it = query.find(name);
    return it == query.end() ? fallback : strings::toInt(it->second, fallback);
}

double Request::doubleParam(const std::string& name, double fallback) const {
    if (json.isObject()) {
        if (const json::Value* value = json.find(name)) {
            if (!value->isNull()) {
                return value->asDouble(fallback);
            }
        }
    }
    const auto it = query.find(name);
    return it == query.end() ? fallback : strings::toDouble(it->second, fallback);
}

Response Response::ofJson(const json::Value& value, int status) {
    Response response;
    response.status = status;
    response.body = value.dump();
    return response;
}

Response Response::error(int status, const std::string& message) {
    json::Value payload = json::Value::object();
    payload.set("success", json::Value(false));
    payload.set("error", json::Value(message));
    return ofJson(payload, status);
}

} // namespace http
} // namespace agri
