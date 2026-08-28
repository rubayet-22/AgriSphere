// =============================================================================
//  AgriSphere - FACTORY METHOD PATTERN : the abstract Creator
// =============================================================================
//  createPayment() is the Factory Method. Each Concrete Creator -
//  BkashCreator, NagadCreator, CardCreator, CashOnDeliveryCreator - overrides
//  it to instantiate its own Concrete Product (BkashPayment, NagadPayment,
//  CardPayment, CashOnDeliveryPayment). PaymentCreator itself never
//  constructs a product directly; that responsibility belongs entirely to
//  the subclasses.
//
//  forMethod() is the one place a payment_method string picks which
//  Concrete Creator to use - it only chooses a Creator. The actual product
//  construction happens inside that Creator's createPayment() override, not
//  here. This is also where the old "cod" vs "Cash on Delivery" string
//  inconsistency stays resolved: both spellings pick CashOnDeliveryCreator.
//
//  How that choice is made lives in PaymentCreatorRegistry: forMethod()
//  normalizes the string and looks it up in that registry, so adding a
//  payment method means adding one registerFactory() line rather than editing
//  this class. forMethod() still returns a fresh unique_ptr on every call.
// =============================================================================
#pragma once

#include "agrisphere/payment/PaymentMethod.h"

#include <memory>
#include <string>
#include <vector>

namespace agri {
namespace payment {

class PaymentCreator {
public:
    virtual ~PaymentCreator() = default;

    // The Factory Method. Overridden by each Concrete Creator to
    // instantiate its corresponding Concrete Product.
    virtual std::unique_ptr<PaymentMethod> createPayment() = 0;

    // Picks the Concrete Creator for a payment_method string. Returns
    // nullptr when paymentMethod doesn't match a known method.
    static std::unique_ptr<PaymentCreator> forMethod(const std::string& paymentMethod);

    // Canonical slugs this recognizes: {"bkash", "nagad", "cod", "card"}.
    static std::vector<std::string> supportedMethods();
};

} // namespace payment
} // namespace agri
