#include "agrisphere/strategy/RegularDiscountStrategy.h"

namespace agri {
namespace strategy {

std::string RegularDiscountStrategy::name() const { return "Regular"; }

double RegularDiscountStrategy::discountPercent() const { return 0.0; }

double RegularDiscountStrategy::calculateDiscount(double) const { return 0.0; }

std::string RegularDiscountStrategy::description() const {
    return "Regular customer - no customer discount";
}

} // namespace strategy
} // namespace agri
