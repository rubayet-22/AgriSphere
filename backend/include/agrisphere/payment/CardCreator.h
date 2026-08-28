// =============================================================================
//  AgriSphere - FACTORY METHOD PATTERN : Concrete Creator for Card
// =============================================================================
//  Its only responsibility is createPayment(): instantiate CardPayment.
// =============================================================================
#pragma once

#include "agrisphere/payment/PaymentCreator.h"

namespace agri {
namespace payment {

class CardCreator : public PaymentCreator {
public:
    std::unique_ptr<PaymentMethod> createPayment() override;
};

} // namespace payment
} // namespace agri
