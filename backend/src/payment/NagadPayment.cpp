#include "agrisphere/payment/NagadPayment.h"

namespace agri {
namespace payment {

std::string NagadPayment::methodKey() const { return "nagad"; }

std::string NagadPayment::displayName() const { return "Nagad"; }

std::string NagadPayment::referencePrefix() const { return "NGD"; }

} // namespace payment
} // namespace agri
