//  AgriSphere - STRATEGY PATTERN : concrete strategy (Premium member)
//  5%. Selected only when the customer has an ADMIN-APPROVED premium_membership
//  row that has not expired. A membership that is still 'Pending' or that was
//  'Rejected' never reaches this class.
#pragma once

#include "agrisphere/strategy/DiscountStrategy.h"

namespace agri {
namespace strategy {

class PremiumDiscountStrategy : public DiscountStrategy {
public:
    std::string name() const override;
    double discountPercent() const override;
    double calculateDiscount(double subtotal) const override;
    std::string description() const override;
};

} // namespace strategy
} // namespace agri
