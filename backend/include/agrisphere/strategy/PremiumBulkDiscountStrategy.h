//  AgriSphere - STRATEGY PATTERN : concrete strategy (Premium member, bulk order)
//  15%. Selected when an admin-approved Premium member places a bulk order,
//  which is the best rate AgriSphere offers.
//
//  Note how this is expressed: the 15% is ONE business rule with ONE strategy
//  class, not "run the Premium strategy and then the Bulk strategy". The
//  context still installs exactly one strategy per order, so the rate can
//  never be applied twice or drift out of step with the other three.
#pragma once

#include "agrisphere/strategy/DiscountStrategy.h"

namespace agri {
namespace strategy {

class PremiumBulkDiscountStrategy : public DiscountStrategy {
public:
    std::string name() const override;
    double discountPercent() const override;
    double calculateDiscount(double subtotal) const override;
    std::string description() const override;
};

} // namespace strategy
} // namespace agri
