// =============================================================================
//  AgriSphere - FACTORY METHOD PATTERN : Concrete Creator for Nagad
// =============================================================================
//  Its only responsibility is createPayment(): instantiate NagadPayment.
// =============================================================================
#pragma once

#include "agrisphere/payment/PaymentCreator.h"

namespace agri {
namespace payment {

class NagadCreator : public PaymentCreator {
public:
    std::unique_ptr<PaymentMethod> createPayment() override;
};

} // namespace payment
} // namespace agri
