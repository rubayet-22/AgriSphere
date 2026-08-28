#include "agrisphere/payment/CardCreator.h"

#include "agrisphere/payment/CardPayment.h"

namespace agri {
namespace payment {

std::unique_ptr<PaymentMethod> CardCreator::createPayment() {
    return std::make_unique<CardPayment>();
}

} // namespace payment
} // namespace agri
