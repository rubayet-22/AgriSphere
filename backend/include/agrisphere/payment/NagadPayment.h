// =============================================================================
//  AgriSphere - FACTORY METHOD PATTERN : Nagad product
// =============================================================================
//  One of the four Concrete Products; instantiated by NagadCreator's
//  createPayment(). Everything about how a Nagad payment behaves (11-digit
//  wallet number, 1.5% fee, "Paid" status) lives in WalletPaymentMethod -
//  this class only supplies Nagad's identity.
// =============================================================================
#pragma once

#include "agrisphere/payment/WalletPaymentMethod.h"

namespace agri {
namespace payment {

class NagadPayment : public WalletPaymentMethod {
public:
    std::string methodKey() const override;
    std::string displayName() const override;

protected:
    std::string referencePrefix() const override;
};

} // namespace payment
} // namespace agri
