#include "agrisphere/payment/CashOnDeliveryCreator.h"

#include "agrisphere/payment/CashOnDeliveryPayment.h"

namespace agri {
namespace payment {

std::unique_ptr<PaymentMethod> CashOnDeliveryCreator::createPayment() {
    return std::make_unique<CashOnDeliveryPayment>();
}

} // namespace payment
} // namespace agri
