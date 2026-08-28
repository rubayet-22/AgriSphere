// AgriSphere C++ Backend - typed SQL parameter.
//
// Every repository passes parameters as SqlValue objects and never
// concatenates user input into SQL text itself. DatabaseManager performs the
// binding (numeric formatting / charset aware escaping) in one place.
#pragma once

#include <optional>
#include <string>
#include <vector>

namespace agri {
namespace db {

class SqlValue {
public:
    enum class Kind { Null, Int, Double, String };

    SqlValue() : kind_(Kind::Null), int_(0), double_(0.0) {}
    SqlValue(int value) : kind_(Kind::Int), int_(value), double_(0.0) {}
    SqlValue(long value) : kind_(Kind::Int), int_(value), double_(0.0) {}
    SqlValue(long long value) : kind_(Kind::Int), int_(value), double_(0.0) {}
    SqlValue(unsigned int value)
        : kind_(Kind::Int), int_(static_cast<long long>(value)), double_(0.0) {}
    SqlValue(double value) : kind_(Kind::Double), int_(0), double_(value) {}
    SqlValue(const char* value)
        : kind_(value ? Kind::String : Kind::Null), int_(0), double_(0.0),
          string_(value ? value : "") {}
    SqlValue(std::string value)
        : kind_(Kind::String), int_(0), double_(0.0), string_(std::move(value)) {}
    SqlValue(bool value) : kind_(Kind::Int), int_(value ? 1 : 0), double_(0.0) {}

    // An absent optional binds as SQL NULL - used for nullable columns such as
    // farm_product.product_image.
    SqlValue(const std::optional<std::string>& value)
        : kind_(value ? Kind::String : Kind::Null), int_(0), double_(0.0),
          string_(value ? *value : "") {}

    static SqlValue null() { return SqlValue(); }

    Kind kind() const { return kind_; }
    long long intValue() const { return int_; }
    double doubleValue() const { return double_; }
    const std::string& stringValue() const { return string_; }

private:
    Kind kind_;
    long long int_;
    double double_;
    std::string string_;
};

using SqlParams = std::vector<SqlValue>;

} // namespace db
} // namespace agri
