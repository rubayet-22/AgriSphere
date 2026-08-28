#include "agrisphere/payment/BkashCreator.h"

#include "agrisphere/payment/BkashPayment.h"

namespace agri {
namespace payment {

std::unique_ptr<PaymentMethod> BkashCreator::createPayment() {
    return std::make_unique<BkashPayment>();
}

} // namespace payment
} // namespace agri
