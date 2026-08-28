// AgriSphere C++ Backend - checkout and order placement.
//
// This is the clearest example of why the C++ layer earns its place: placing an
// order touches customer_order, order_items, farm_product, farm_inventory,
// payment, customer and customer_cart inside one transaction, on one connection
// leased from the Singleton pool.
#pragma once

#include "agrisphere/core/Json.h"
#include "agrisphere/repositories/CartRepository.h"
#include "agrisphere/repositories/MembershipRepository.h"
#include "agrisphere/repositories/OrderRepository.h"
#include "agrisphere/strategy/DiscountContext.h"

#include <string>

namespace agri {
namespace service {

struct CheckoutRequest {
    long long customerId = 0;
    std::string deliveryAddress;
    std::string deliveryPhone;
    std::string paymentMethod;
    std::string bkashNumber;  // also carries the Nagad number
    std::string cardNumber;   // Card only
    std::string cardExpiry;   // Card only, "MM/YY"
    std::string cardCvc;      // Card only
};

struct DirectOrderRequest {
    long long customerId = 0;
    std::string name;
    std::string phone;
    std::string address;
    std::string instructions;
    std::string paymentMethod;
    std::string bkashNumber;  // also carries the Nagad number
    std::string cardNumber;   // Card only
    std::string cardExpiry;   // Card only, "MM/YY"
    std::string cardCvc;      // Card only
};

class OrderService {
public:
    // Cart contents plus stock, as the checkout page needs them.
    json::Value checkoutItems(long long customerId);

    // customer/checkout.php flow: stock is locked with FOR UPDATE, the order is
    // created as 'Processing' and the delivery address is saved back.
    json::Value placeOrder(const CheckoutRequest& request);

    // customer/process_order.php flow: order created as 'Pending', inventory
    // decremented, payment timestamped.
    json::Value placeOrderDirect(const DirectOrderRequest& request);

private:
    repo::CartRepository cart_;
    repo::OrderRepository orders_;

    // Strategy pattern: reads whether the customer is an approved Premium
    // member, then builds the CustomerProfile the DiscountContext selects on.
    repo::MembershipRepository memberships_;
    strategy::DiscountContext buildDiscountContext(long long customerId,
                                                   long long totalItems);
};

} // namespace service
} // namespace agri
