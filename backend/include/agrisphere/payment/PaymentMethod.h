
#pragma once

#include "agrisphere/payment/PaymentTypes.h"

#include <string>

namespace agri {
namespace payment {

class PaymentMethod {
public:
    virtual ~PaymentMethod() = default;

    // Canonical slug persisted/matched on, e.g. "bkash".
    virtual std::string methodKey() const = 0;

    // Human readable name used in validation messages, e.g. "bKash".
    virtual std::string displayName() const = 0;

    // Checks the customer-supplied details for this method and normalizes
    // the value that should be persisted (e.g. trims a wallet number, masks
    // a card number). Called first; OrderService stops here on failure.
    virtual ValidationOutcome validate(const PaymentInput& input) const = 0;

    // The fee this method charges on top of orderTotal. 0.0 for methods
    // with no fee.
    virtual double calculateFee(double orderTotal) const = 0;

    // A transaction reference / authorization code for this payment.
    // Empty string for methods that don't generate one (Cash on Delivery).
    virtual std::string generateReference() const = 0;

    // The payment_status this method resolves to once accepted
    // ("Pending" or "Paid").
    virtual std::string resolveStatus() const = 0;
};

} // namespace payment
} // namespace agri
