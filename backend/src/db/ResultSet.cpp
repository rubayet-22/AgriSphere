#include "agrisphere/db/ResultSet.h"

#include "agrisphere/core/Strings.h"

namespace agri {
namespace db {

int Row::indexOf(const std::string& column) const {
    if (!columns_) {
        return -1;
    }
    for (std::size_t i = 0; i < columns_->size(); ++i) {
        if ((*columns_)[i] == column) {
            return static_cast<int>(i);
        }
    }
    // Fall back to a case insensitive match; MySQL column labels keep the case
    // used in the query but callers should not have to care.
    for (std::size_t i = 0; i < columns_->size(); ++i) {
        if (strings::iequals((*columns_)[i], column)) {
            return static_cast<int>(i);
        }
    }
    return -1;
}

bool Row::has(const std::string& column) const { return indexOf(column) >= 0; }

bool Row::isNull(const std::string& column) const {
    const int index = indexOf(column);
    if (index < 0) {
        return true;
    }
    return !values_[static_cast<std::size_t>(index)].has_value();
}

std::string Row::get(const std::string& column, const std::string& fallback) const {
    const int index = indexOf(column);
    if (index < 0) {
        return fallback;
    }
    const auto& value = values_[static_cast<std::size_t>(index)];
    return value ? *value : fallback;
}

long long Row::getInt(const std::string& column, long long fallback) const {
    const int index = indexOf(column);
    if (index < 0) {
        return fallback;
    }
    const auto& value = values_[static_cast<std::size_t>(index)];
    return value ? strings::toInt(*value, fallback) : fallback;
}

double Row::getDouble(const std::string& column, double fallback) const {
    const int index = indexOf(column);
    if (index < 0) {
        return fallback;
    }
    const auto& value = values_[static_cast<std::size_t>(index)];
    return value ? strings::toDouble(*value, fallback) : fallback;
}

} // namespace db
} // namespace agri
