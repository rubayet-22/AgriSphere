//  AgriSphere - STRATEGY PATTERN : the Context
//  DiscountContext holds ONE DiscountStrategy and delegates the calculation to
//  it. OrderService talks only to this class, so the checkout contains no
//  "if premium ... else if bulk ..." arithmetic.
//
//  selectStrategy() is the single place where the AgriSphere business rule
//  lives:
//
//        premium AND bulk (>= 10 units) -> PremiumBulkDiscountStrategy (15%)
//        else bulk order  (>= 10 units) -> BulkDiscountStrategy        (10%)
//        else premium member            -> PremiumDiscountStrategy     ( 5%)
//        else                           -> RegularDiscountStrategy     ( 0%)
//
//  Because setStrategy() REPLACES the strategy rather than adding to a list,
//  exactly one rate is ever applied to an order. The combined 15% is its own
//  strategy, not the 5% and 10% strategies run one after the other.
#pragma once

#include "agrisphere/strategy/DiscountStrategy.h"

#include <memory>
#include <string>

namespace agri {
namespace strategy {

// What the checkout knows about the customer placing this order.
struct CustomerProfile {
    // True only for an admin-approved premium_membership row that has not
    // expired. A 'Pending' or 'Rejected' application leaves this false.
    bool isPremium = false;

    // Units in the order, used for the bulk rule.
    long long totalItems = 0;
};

class DiscountContext {
public:
    // The project had no bulk-order rule before this feature, so a simple
    // fixed threshold is used: 10 or more units in one order is "bulk".
    static constexpr long long kBulkOrderThreshold = 10;

    // Classic Strategy setter: installs (and replaces) the rule to use.
    void setStrategy(std::unique_ptr<DiscountStrategy> strategy);

    // Applies the AgriSphere rule above and installs the matching strategy.
    void selectStrategy(const CustomerProfile& profile);

    // Delegates to the installed strategy. `subtotal` is already net of any
    // farmer product discount.
    double calculateDiscount(double subtotal) const;

    std::string strategyName() const;
    double discountPercent() const;
    std::string description() const;

private:
    std::unique_ptr<DiscountStrategy> strategy_;
};

} // namespace strategy
} // namespace agri
