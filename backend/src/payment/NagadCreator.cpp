#include "agrisphere/payment/NagadCreator.h"

#include "agrisphere/payment/NagadPayment.h"

namespace agri {
namespace payment {

std::unique_ptr<PaymentMethod> NagadCreator::createPayment() {
    return std::make_unique<NagadPayment>();
}

} // namespace payment
} // namespace agri
