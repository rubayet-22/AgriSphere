// =============================================================================
//  AgriSphere - FACTORY METHOD PATTERN : Card product
// =============================================================================
//  One of the four Concrete Products; instantiated by CardCreator's
//  createPayment(). Validates a card number/expiry/CVC instead of a wallet
//  number, charges a percentage-based fee, and generates an
//  authorization-code-style reference. Only a masked account number is ever
//  normalized/persisted - the raw card number and CVC never leave
//  validate().
// =============================================================================
#pragma once

#include "agrisphere/payment/PaymentMethod.h"

namespace agri {
namespace payment {

class CardPayment : public PaymentMethod {
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
