//  AgriSphere - FACTORY METHOD PATTERN : the Creator registry
//  PaymentCreator::forMethod() used to be an if/else chain over the
//  payment_method string. Every new payment method meant editing that
//  function. This registry replaces the chain with a lookup table:
//
//      "bkash"            -> makes a BkashCreator
//      "nagad"            -> makes a NagadCreator
//      "cod"              -> makes a CashOnDeliveryCreator
//      "cash on delivery" -> makes a CashOnDeliveryCreator
//      "card"             -> makes a CardCreator
//      "credit card"      -> makes a CardCreator
//      "debit card"       -> makes a CardCreator
//
//  Adding a payment method is now one registerFactory() line in
//  registerPaymentCreators() - forMethod() itself never changes again.
//
//  WHY std::function AND NOT A STORED CREATOR OBJECT
//  Several keys map to the same Concrete Creator ("cod" and
//  "cash on delivery" both mean CashOnDeliveryCreator), and forMethod() must
//  keep handing every caller its own fresh unique_ptr. So the map stores a
//  small factory function per key rather than a long-lived creator object:
//  the registry owns no PaymentCreator at all, it only knows how to make one.
//
//  This registry is a lookup table for the Factory Method pattern. It does
//  not create products - each Concrete Creator's createPayment() still does
//  that, exactly as before.
#pragma once

#include "agrisphere/payment/PaymentCreator.h"

#include <functional>
#include <memory>
#include <string>
#include <unordered_map>
#include <vector>

namespace agri {
namespace payment {

class PaymentCreatorRegistry {
public:
    // Makes one Concrete Creator. Called on every lookup, so each caller gets
    // its own object.
    using CreatorFactory = std::function<std::unique_ptr<PaymentCreator>()>;

    // The single instance. A function-local static, so C++11 guarantees it is
    // created once and in a thread safe way.
    static PaymentCreatorRegistry& getInstance();

    PaymentCreatorRegistry(const PaymentCreatorRegistry&) = delete;
    PaymentCreatorRegistry& operator=(const PaymentCreatorRegistry&) = delete;
    PaymentCreatorRegistry(PaymentCreatorRegistry&&) = delete;
    PaymentCreatorRegistry& operator=(PaymentCreatorRegistry&&) = delete;

    // Adds (or replaces) one key. Registering the same key twice is harmless,
    // which is what makes registerPaymentCreators() safe to call more than once.
    void registerFactory(const std::string& name, CreatorFactory factory);

    // The factory function for `name`, or nullptr when the key is unknown.
    // The pointer stays valid as long as the key is not re-registered; all
    // registration happens at startup, before any request is served, so
    // lookups from the HTTP worker threads only ever read the map.
    const CreatorFactory* getFactory(const std::string& name) const;

    // Every registered key, sorted so the order is stable.
    std::vector<std::string> registeredNames() const;

    std::size_t size() const { return factories_.size(); }

private:
    PaymentCreatorRegistry() = default;

    std::unordered_map<std::string, CreatorFactory> factories_;
};

// Fills the registry with the payment methods AgriSphere accepts. Called once
// from main() before the HTTP server starts. Safe to call more than once.
void registerPaymentCreators();

} // namespace payment
} // namespace agri
