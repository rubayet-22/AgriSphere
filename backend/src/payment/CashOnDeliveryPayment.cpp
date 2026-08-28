#include "agrisphere/payment/CashOnDeliveryPayment.h"

namespace agri {
namespace payment {

std::string CashOnDeliveryPayment::methodKey() const { return "cod"; }

std::string CashOnDeliveryPayment::displayName() const { return "Cash on Delivery"; }

ValidationOutcome CashOnDeliveryPayment::validate(const PaymentInput&) const {
    ValidationOutcome outcome;
    outcome.ok = true;
    return outcome;
}

double CashOnDeliveryPayment::calculateFee(double) const { return 0.0; }

std::string CashOnDeliveryPayment::generateReference() const { return ""; }

std::string CashOnDeliveryPayment::resolveStatus() const { return "Pending"; }

} // namespace payment
} // namespace agri
