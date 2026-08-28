// AgriSphere C++ Backend - HTTP request/response value types.
#pragma once

#include "agrisphere/core/Json.h"

#include <map>
#include <string>

namespace agri {
namespace http {

struct Request {
    std::string method;
    std::string path;
    std::string rawQuery;
    std::string body;
    std::map<std::string, std::string> headers;   // lower-cased keys
    std::map<std::string, std::string> query;     // decoded ?a=b pairs
    json::Value json;                             // parsed body (object or null)

    std::string header(const std::string& name) const {
        const auto it = headers.find(name);
        return it == headers.end() ? std::string() : it->second;
    }

    // Reads a parameter from the JSON body first, then the query string.
    std::string param(const std::string& name, const std::string& fallback = std::string()) const;
    long long intParam(const std::string& name, long long fallback = 0) const;
    double doubleParam(const std::string& name, double fallback = 0.0) const;
    bool hasParam(const std::string& name) const;
};

struct Response {
    int status = 200;
    std::string contentType = "application/json; charset=utf-8";
    std::string body;

    static Response ofJson(const json::Value& value, int status = 200);
    static Response error(int status, const std::string& message);
};

} // namespace http
} // namespace agri
