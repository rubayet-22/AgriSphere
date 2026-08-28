// =============================================================================
//  AgriSphere - FACTORY METHOD PATTERN : Concrete Creator for bKash
// =============================================================================
//  Its only responsibility is createPayment(): instantiate BkashPayment.
// =============================================================================
#pragma once

#include "agrisphere/payment/PaymentCreator.h"

namespace agri {
namespace payment {

class BkashCreator : public PaymentCreator {
public:
    std::unique_ptr<PaymentMethod> createPayment() override;
};

} // namespace payment
} // namespace agri
