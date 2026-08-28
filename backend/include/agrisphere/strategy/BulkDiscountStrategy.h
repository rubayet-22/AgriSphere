//  AgriSphere - STRATEGY PATTERN : concrete strategy (bulk order)
//  10%. Selected when a REGULAR customer's order contains
//  DiscountContext::kBulkOrderThreshold units or more. A Premium member placing
//  a bulk order is a different rule and gets PremiumBulkDiscountStrategy (15%)
//  instead - the context installs one or the other, never both.
#pragma once

#include "agrisphere/strategy/DiscountStrategy.h"

namespace agri {
namespace strategy {

class BulkDiscountStrategy : public DiscountStrategy {
public:
    std::string name() const override;
    double discountPercent() const override;
    double calculateDiscount(double subtotal) const override;
    std::string description() const override;
};

} // namespace strategy
} // namespace agri
