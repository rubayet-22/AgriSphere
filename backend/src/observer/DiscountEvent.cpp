#include "agrisphere/observer/DiscountEvent.h"

#include <cstdio>

namespace agri {
namespace observer {
namespace {


std::string formatPercent(double value) {
    char buffer[32];
    if (value == static_cast<long long>(value)) {
        std::snprintf(buffer, sizeof(buffer), "%lld", static_cast<long long>(value));
    } else {
        std::snprintf(buffer, sizeof(buffer), "%.2f", value);
    }
    return std::string(buffer);
}

std::string formatMoney(double value) {
    char buffer[32];
    std::snprintf(buffer, sizeof(buffer), "%.2f", value);
    return std::string(buffer);
}

} // namespace

std::string DiscountEvent::title() const {
    return formatPercent(discountPercent) + "% off " + productName;
}

std::string DiscountEvent::message() const {
    return "Farmer " + farmerName + " has discounted " + productName + " by " +
           formatPercent(discountPercent) + "%. The price is now BDT " +
           formatMoney(discountedPrice) + " instead of BDT " + formatMoney(originalPrice) + ".";
}

} // namespace observer
} // namespace agri
