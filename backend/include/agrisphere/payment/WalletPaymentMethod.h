// =============================================================================
//  AgriSphere - FACTORY METHOD PATTERN : shared wallet behavior
// =============================================================================
//  bKash and Nagad are both mobile-wallet payments: same 11-digit number
//  format, same transaction-fee model, same "Paid" status, same reference
//  code shape. WalletPaymentMethod holds that shared behavior once so
//  BkashPayment and NagadPayment only need to say what makes them different
//  (their display name and reference prefix). It is still abstract - it is
//  not one of the factory's four products, only a base the two wallet
//  products share.
// =============================================================================
#pragma once

#include "agrisphere/payment/PaymentMethod.h"

namespace agri {
namespace payment {

class WalletPaymentMethod : public PaymentMethod {
public:
    ValidationOutcome validate(const PaymentInput& input) const override;
    double calculateFee(double orderTotal) const override;
    std::string resolveStatus() const override;
    std::string generateReference() const override;

protected:
    // "BKS" for bKash, "NGD" for Nagad.
    virtual std::string referencePrefix() const = 0;
};

} // namespace payment
} // namespace agri
