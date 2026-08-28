// =============================================================================
//  AgriSphere - FACTORY METHOD PATTERN : Cash on Delivery product
// =============================================================================
//  One of the four Concrete Products; instantiated by
//  CashOnDeliveryCreator's createPayment(). No wallet number, no fee, no
//  reference code - the customer pays the courier, so the order simply
//  waits as "Pending" until that happens.
// =============================================================================
#pragma once

#include "agrisphere/payment/PaymentMethod.h"

namespace agri {
namespace payment {

class CashOnDeliveryPayment : public PaymentMethod {
public:
    std::string methodKey() const override;
    std::string displayName() const override;
    ValidationOutcome validate(const PaymentInput& input) const override;
    double calculateFee(double orderTotal) const override;
    std::string generateReference() const override;
    std::string resolveStatus() const override;
};

} // namespace payment
} // namespace agri
