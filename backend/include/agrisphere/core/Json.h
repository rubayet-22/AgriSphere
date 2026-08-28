// AgriSphere C++ Backend - dependency free JSON value, parser and serialiser.
//
// The API surface is deliberately small: it only needs to cover what the HTTP
// layer exchanges with PHP (and, later, an Android client).
#pragma once

#include <string>
#include <utility>
#include <vector>

namespace agri {
namespace json {

class Value {
public:
    enum class Type { Null, Bool, Int, Double, String, Array, Object };

    Value();
    Value(std::nullptr_t);
    Value(bool value);
    Value(int value);
    Value(long long value);
    Value(double value);
    Value(const char* value);
    Value(std::string value);

    static Value object();
    static Value array();

    Type type() const { return type_; }
    bool isNull() const { return type_ == Type::Null; }
    bool isObject() const { return type_ == Type::Object; }
    bool isArray() const { return type_ == Type::Array; }

    // Object helpers. `set` keeps insertion order so responses stay readable.
    Value& set(const std::string& key, Value value);
    bool has(const std::string& key) const;
    const Value* find(const std::string& key) const;
    const std::vector<std::pair<std::string, Value>>& members() const { return object_; }

    // Array helpers.
    Value& push(Value value);
    const std::vector<Value>& elements() const { return array_; }
    std::size_t size() const;

    // Scalar accessors - forgiving, mirroring how PHP consumes the payloads.
    std::string asString(const std::string& fallback = std::string()) const;
    long long asInt(long long fallback = 0) const;
    double asDouble(double fallback = 0.0) const;
    bool asBool(bool fallback = false) const;

    // Convenience lookups on objects.
    std::string getString(const std::string& key, const std::string& fallback = std::string()) const;
    long long getInt(const std::string& key, long long fallback = 0) const;
    double getDouble(const std::string& key, double fallback = 0.0) const;
    bool getBool(const std::string& key, bool fallback = false) const;

    std::string dump() const;

private:
    Type type_;
    bool bool_;
    long long int_;
    double double_;
    std::string string_;
    std::vector<Value> array_;
    std::vector<std::pair<std::string, Value>> object_;
};

// Returns a Null value and fills `error` when the document is malformed.
Value parse(const std::string& text, std::string* error = nullptr);

std::string escapeString(const std::string& raw);

} // namespace json
} // namespace agri
