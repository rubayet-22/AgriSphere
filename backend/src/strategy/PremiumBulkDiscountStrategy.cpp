#include "agrisphere/strategy/PremiumBulkDiscountStrategy.h"

#include <cmath>

namespace agri {
namespace strategy {

std::string PremiumBulkDiscountStrategy::name() const { return "Premium + Bulk"; }

double PremiumBulkDiscountStrategy::discountPercent() const { return 15.0; }

double PremiumBulkDiscountStrategy::calculateDiscount(double subtotal) const {
    const double discount = subtotal * (discountPercent() / 100.0);
    return std::round(discount * 100.0) / 100.0;
}

std::string PremiumBulkDiscountStrategy::description() const {
    return "Premium member with a bulk order - 15% customer discount";
}

} // namespace strategy
} // namespace agri
