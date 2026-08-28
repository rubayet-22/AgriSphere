#include "agrisphere/strategy/PremiumDiscountStrategy.h"

#include <cmath>

namespace agri {
namespace strategy {

std::string PremiumDiscountStrategy::name() const { return "Premium"; }

double PremiumDiscountStrategy::discountPercent() const { return 5.0; }

double PremiumDiscountStrategy::calculateDiscount(double subtotal) const {
    const double discount = subtotal * (discountPercent() / 100.0);
    return std::round(discount * 100.0) / 100.0;
}

std::string PremiumDiscountStrategy::description() const {
    return "Premium member - 5% customer discount";
}

} // namespace strategy
} // namespace agri
