//  AgriSphere - STRATEGY PATTERN : the strategy interface
//  One customer discount rule, expressed as an object. The three concrete
//  strategies are:
//
//    RegularDiscountStrategy      ->  0%  (every customer)
//    PremiumDiscountStrategy      ->  5%  (admin-approved Premium member)
//    BulkDiscountStrategy         -> 10%  (10 or more units in one order)
//    PremiumBulkDiscountStrategy  -> 15%  (Premium member placing a bulk order)
//
//  DiscountContext holds exactly one of them at a time and OrderService asks
//  the context - never an if/else chain over customer types - what the
//  discount is. Adding a fourth rule later means adding one class here, not
//  editing the checkout.
//
//  SCOPE: this is the CUSTOMER discount only. The farmer's per-product
//  discount is a completely different feature; it is stored in
//  product_discount, published through the Observer pattern (observer/), and
//  is already baked into effective_price before any strategy runs. Nothing in
//  this folder reads or changes a farmer discount.
#pragma once

#include <string>

namespace agri {
namespace strategy {

class DiscountStrategy {
public:
    virtual ~DiscountStrategy() = default;

    // Short name stored/logged and returned to PHP, e.g. "Premium".
    virtual std::string name() const = 0;

    // The fixed AgriSphere business rate for this strategy: 0, 5 or 10.
    virtual double discountPercent() const = 0;

    // The money taken off this subtotal. `subtotal` is already net of any
    // farmer product discount, so the farmer discount is never applied twice.
    virtual double calculateDiscount(double subtotal) const = 0;

    // One line the checkout page can show the customer.
    virtual std::string description() const = 0;
};

} // namespace strategy
} // namespace agri
