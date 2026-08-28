//  AgriSphere - STRATEGY PATTERN : concrete strategy (regular customer)
//  0%. This is the default strategy, so a customer who is not Premium and is
//  not placing a bulk order still goes through the same code path as everyone
//  else - the checkout has no "if there is no discount" special case.
#pragma once

#include "agrisphere/strategy/DiscountStrategy.h"

namespace agri {
namespace strategy {

class RegularDiscountStrategy : public DiscountStrategy {
public:
    std::string name() const override;
    double discountPercent() const override;
    double calculateDiscount(double subtotal) const override;
    std::string description() const override;
};

} // namespace strategy
} // namespace agri
