#include "agrisphere/core/Json.h"

#include "agrisphere/core/Strings.h"

#include <cmath>
#include <cstdio>
#include <sstream>

namespace agri {
namespace json {
namespace {

void appendUtf8(std::string& out, unsigned int codepoint) {
    if (codepoint <= 0x7F) {
        out.push_back(static_cast<char>(codepoint));
    } else if (codepoint <= 0x7FF) {
        out.push_back(static_cast<char>(0xC0 | (codepoint >> 6)));
        out.push_back(static_cast<char>(0x80 | (codepoint & 0x3F)));
    } else if (codepoint <= 0xFFFF) {
        out.push_back(static_cast<char>(0xE0 | (codepoint >> 12)));
        out.push_back(static_cast<char>(0x80 | ((codepoint >> 6) & 0x3F)));
        out.push_back(static_cast<char>(0x80 | (codepoint & 0x3F)));
    } else {
        out.push_back(static_cast<char>(0xF0 | (codepoint >> 18)));
        out.push_back(static_cast<char>(0x80 | ((codepoint >> 12) & 0x3F)));
        out.push_back(static_cast<char>(0x80 | ((codepoint >> 6) & 0x3F)));
        out.push_back(static_cast<char>(0x80 | (codepoint & 0x3F)));
    }
}

class Parser {
public:
    explicit Parser(const std::string& text) : text_(text) {}

    bool parse(Value& out) {
        skipWhitespace();
        if (!parseValue(out)) {
            return false;
        }
        skipWhitespace();
        return true;
    }

    const std::string& error() const { return error_; }

private:
    const std::string& text_;
    std::size_t pos_ = 0;
    std::string error_;

    bool fail(const std::string& message) {
        if (error_.empty()) {
            error_ = message + " at offset " + std::to_string(pos_);
        }
        return false;
    }

    void skipWhitespace() {
        while (pos_ < text_.size()) {
            const char c = text_[pos_];
            if (c == ' ' || c == '\t' || c == '\n' || c == '\r') {
                ++pos_;
            } else {
                break;
            }
        }
    }

    bool literal(const char* word) {
        const std::size_t len = std::char_traits<char>::length(word);
        if (text_.compare(pos_, len, word) != 0) {
            return false;
        }
        pos_ += len;
        return true;
    }

    bool parseValue(Value& out) {
        skipWhitespace();
        if (pos_ >= text_.size()) {
            return fail("unexpected end of input");
        }
        const char c = text_[pos_];
        switch (c) {
            case '{': return parseObject(out);
            case '[': return parseArray(out);
            case '"': {
                std::string s;
                if (!parseString(s)) {
                    return false;
                }
                out = Value(s);
                return true;
            }
            case 't':
                if (!literal("true")) return fail("invalid literal");
                out = Value(true);
                return true;
            case 'f':
                if (!literal("false")) return fail("invalid literal");
                out = Value(false);
                return true;
            case 'n':
                if (!literal("null")) return fail("invalid literal");
                out = Value(nullptr);
                return true;
            default:
                return parseNumber(out);
        }
    }

    bool parseObject(Value& out) {
        out = Value::object();
        ++pos_; // consume '{'
        skipWhitespace();
        if (pos_ < text_.size() && text_[pos_] == '}') {
            ++pos_;
            return true;
        }
        while (true) {
            skipWhitespace();
            if (pos_ >= text_.size() || text_[pos_] != '"') {
                return fail("expected object key");
            }
            std::string key;
            if (!parseString(key)) {
                return false;
            }
            skipWhitespace();
            if (pos_ >= text_.size() || text_[pos_] != ':') {
                return fail("expected ':'");
            }
            ++pos_;
            Value child;
            if (!parseValue(child)) {
                return false;
            }
            out.set(key, child);
            skipWhitespace();
            if (pos_ >= text_.size()) {
                return fail("unterminated object");
            }
            if (text_[pos_] == ',') {
                ++pos_;
                continue;
            }
            if (text_[pos_] == '}') {
                ++pos_;
                return true;
            }
            return fail("expected ',' or '}'");
        }
    }

    bool parseArray(Value& out) {
        out = Value::array();
        ++pos_; // consume '['
        skipWhitespace();
        if (pos_ < text_.size() && text_[pos_] == ']') {
            ++pos_;
            return true;
        }
        while (true) {
            Value child;
            if (!parseValue(child)) {
                return false;
            }
            out.push(child);
            skipWhitespace();
            if (pos_ >= text_.size()) {
                return fail("unterminated array");
            }
            if (text_[pos_] == ',') {
                ++pos_;
                continue;
            }
            if (text_[pos_] == ']') {
                ++pos_;
                return true;
            }
            return fail("expected ',' or ']'");
        }
    }

    bool parseString(std::string& out) {
        ++pos_; // consume opening quote
        out.clear();
        while (pos_ < text_.size()) {
            const char c = text_[pos_++];
            if (c == '"') {
                return true;
            }
            if (c != '\\') {
                out.push_back(c);
                continue;
            }
            if (pos_ >= text_.size()) {
                return fail("unterminated escape");
            }
            const char esc = text_[pos_++];
            switch (esc) {
                case '"': out.push_back('"'); break;
                case '\\': out.push_back('\\'); break;
                case '/': out.push_back('/'); break;
                case 'b': out.push_back('\b'); break;
                case 'f': out.push_back('\f'); break;
                case 'n': out.push_back('\n'); break;
                case 'r': out.push_back('\r'); break;
                case 't': out.push_back('\t'); break;
                case 'u': {
                    if (pos_ + 4 > text_.size()) {
                        return fail("truncated \\u escape");
                    }
                    unsigned int cp = static_cast<unsigned int>(
                        std::stoul(text_.substr(pos_, 4), nullptr, 16));
                    pos_ += 4;
                    if (cp >= 0xD800 && cp <= 0xDBFF && pos_ + 6 <= text_.size() &&
                        text_[pos_] == '\\' && text_[pos_ + 1] == 'u') {
                        const unsigned int low = static_cast<unsigned int>(
                            std::stoul(text_.substr(pos_ + 2, 4), nullptr, 16));
                        if (low >= 0xDC00 && low <= 0xDFFF) {
                            cp = 0x10000 + ((cp - 0xD800) << 10) + (low - 0xDC00);
                            pos_ += 6;
                        }
                    }
                    appendUtf8(out, cp);
                    break;
                }
                default:
                    return fail("unknown escape");
            }
        }
        return fail("unterminated string");
    }

    bool parseNumber(Value& out) {
        const std::size_t start = pos_;
        if (pos_ < text_.size() && (text_[pos_] == '-' || text_[pos_] == '+')) {
            ++pos_;
        }
        bool isDouble = false;
        while (pos_ < text_.size()) {
            const char c = text_[pos_];
            if (c >= '0' && c <= '9') {
                ++pos_;
            } else if (c == '.' || c == 'e' || c == 'E' || c == '+' || c == '-') {
                isDouble = true;
                ++pos_;
            } else {
                break;
            }
        }
        if (pos_ == start) {
            return fail("invalid number");
        }
        const std::string raw = text_.substr(start, pos_ - start);
        try {
            if (isDouble) {
                out = Value(std::stod(raw));
            } else {
                out = Value(static_cast<long long>(std::stoll(raw)));
            }
        } catch (...) {
            return fail("number out of range");
        }
        return true;
    }
};

} // namespace

Value::Value() : type_(Type::Null), bool_(false), int_(0), double_(0.0) {}
Value::Value(std::nullptr_t) : Value() {}
Value::Value(bool value) : type_(Type::Bool), bool_(value), int_(0), double_(0.0) {}
Value::Value(int value) : type_(Type::Int), bool_(false), int_(value), double_(0.0) {}
Value::Value(long long value) : type_(Type::Int), bool_(false), int_(value), double_(0.0) {}
Value::Value(double value) : type_(Type::Double), bool_(false), int_(0), double_(value) {}
Value::Value(const char* value)
    : type_(Type::String), bool_(false), int_(0), double_(0.0), string_(value ? value : "") {}
Value::Value(std::string value)
    : type_(Type::String), bool_(false), int_(0), double_(0.0), string_(std::move(value)) {}

Value Value::object() {
    Value v;
    v.type_ = Type::Object;
    return v;
}

Value Value::array() {
    Value v;
    v.type_ = Type::Array;
    return v;
}

Value& Value::set(const std::string& key, Value value) {
    if (type_ != Type::Object) {
        type_ = Type::Object;
    }
    for (auto& entry : object_) {
        if (entry.first == key) {
            entry.second = std::move(value);
            return *this;
        }
    }
    object_.emplace_back(key, std::move(value));
    return *this;
}

bool Value::has(const std::string& key) const { return find(key) != nullptr; }

const Value* Value::find(const std::string& key) const {
    for (const auto& entry : object_) {
        if (entry.first == key) {
            return &entry.second;
        }
    }
    return nullptr;
}

Value& Value::push(Value value) {
    if (type_ != Type::Array) {
        type_ = Type::Array;
    }
    array_.push_back(std::move(value));
    return *this;
}

std::size_t Value::size() const {
    if (type_ == Type::Array) return array_.size();
    if (type_ == Type::Object) return object_.size();
    return 0;
}

std::string Value::asString(const std::string& fallback) const {
    switch (type_) {
        case Type::String: return string_;
        case Type::Int: return std::to_string(int_);
        case Type::Double: {
            std::ostringstream out;
            out << double_;
            return out.str();
        }
        case Type::Bool: return bool_ ? "1" : "";
        default: return fallback;
    }
}

long long Value::asInt(long long fallback) const {
    switch (type_) {
        case Type::Int: return int_;
        case Type::Double: return static_cast<long long>(double_);
        case Type::Bool: return bool_ ? 1 : 0;
        case Type::String: return strings::toInt(string_, fallback);
        default: return fallback;
    }
}

double Value::asDouble(double fallback) const {
    switch (type_) {
        case Type::Int: return static_cast<double>(int_);
        case Type::Double: return double_;
        case Type::Bool: return bool_ ? 1.0 : 0.0;
        case Type::String: return strings::toDouble(string_, fallback);
        default: return fallback;
    }
}

bool Value::asBool(bool fallback) const {
    switch (type_) {
        case Type::Bool: return bool_;
        case Type::Int: return int_ != 0;
        case Type::Double: return double_ != 0.0;
        case Type::String: return !string_.empty() && string_ != "0" && string_ != "false";
        default: return fallback;
    }
}

std::string Value::getString(const std::string& key, const std::string& fallback) const {
    const Value* v = find(key);
    return v ? v->asString(fallback) : fallback;
}

long long Value::getInt(const std::string& key, long long fallback) const {
    const Value* v = find(key);
    return v ? v->asInt(fallback) : fallback;
}

double Value::getDouble(const std::string& key, double fallback) const {
    const Value* v = find(key);
    return v ? v->asDouble(fallback) : fallback;
}

bool Value::getBool(const std::string& key, bool fallback) const {
    const Value* v = find(key);
    return v ? v->asBool(fallback) : fallback;
}

std::string Value::dump() const {
    switch (type_) {
        case Type::Null: return "null";
        case Type::Bool: return bool_ ? "true" : "false";
        case Type::Int: return std::to_string(int_);
        case Type::Double: {
            if (std::isnan(double_) || std::isinf(double_)) {
                return "null";
            }
            char buffer[64];
            std::snprintf(buffer, sizeof(buffer), "%.10g", double_);
            return std::string(buffer);
        }
        case Type::String: return escapeString(string_);
        case Type::Array: {
            std::string out = "[";
            for (std::size_t i = 0; i < array_.size(); ++i) {
                if (i) out.push_back(',');
                out += array_[i].dump();
            }
            out.push_back(']');
            return out;
        }
        case Type::Object: {
            std::string out = "{";
            for (std::size_t i = 0; i < object_.size(); ++i) {
                if (i) out.push_back(',');
                out += escapeString(object_[i].first);
                out.push_back(':');
                out += object_[i].second.dump();
            }
            out.push_back('}');
            return out;
        }
    }
    return "null";
}

std::string escapeString(const std::string& raw) {
    std::string out;
    out.reserve(raw.size() + 2);
    out.push_back('"');
    for (const char c : raw) {
        switch (c) {
            case '"': out += "\\\""; break;
            case '\\': out += "\\\\"; break;
            case '\b': out += "\\b"; break;
            case '\f': out += "\\f"; break;
            case '\n': out += "\\n"; break;
            case '\r': out += "\\r"; break;
            case '\t': out += "\\t"; break;
            default:
                if (static_cast<unsigned char>(c) < 0x20) {
                    char buffer[8];
                    std::snprintf(buffer, sizeof(buffer), "\\u%04x",
                                  static_cast<unsigned int>(static_cast<unsigned char>(c)));
                    out += buffer;
                } else {
                    // UTF-8 bytes are emitted verbatim; the transport is UTF-8.
                    out.push_back(c);
                }
        }
    }
    out.push_back('"');
    return out;
}

Value parse(const std::string& text, std::string* error) {
    Parser parser(text);
    Value out;
    if (!parser.parse(out)) {
        if (error) {
            *error = parser.error();
        }
        return Value();
    }
    if (error) {
        error->clear();
    }
    return out;
}

} // namespace json
} // namespace agri
