#include "agrisphere/strategy/BulkDiscountStrategy.h"

#include <cmath>

namespace agri {
namespace strategy {

std::string BulkDiscountStrategy::name() const { return "Bulk"; }

double BulkDiscountStrategy::discountPercent() const { return 10.0; }

double BulkDiscountStrategy::calculateDiscount(double subtotal) const {
    const double discount = subtotal * (discountPercent() / 100.0);
    return std::round(discount * 100.0) / 100.0;
}

std::string BulkDiscountStrategy::description() const {
    return "Bulk order - 10% customer discount";
}

} // namespace strategy
} // namespace agri
