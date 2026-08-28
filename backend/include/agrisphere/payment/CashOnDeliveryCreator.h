// =============================================================================
//  AgriSphere - FACTORY METHOD PATTERN : Concrete Creator for Cash on Delivery
// =============================================================================
//  Its only responsibility is createPayment(): instantiate
//  CashOnDeliveryPayment.
// =============================================================================
#pragma once

#include "agrisphere/payment/PaymentCreator.h"

namespace agri {
namespace payment {

class CashOnDeliveryCreator : public PaymentCreator {
public:
    std::unique_ptr<PaymentMethod> createPayment() override;
};

} // namespace payment
} // namespace agri
