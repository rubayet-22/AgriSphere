#include "agrisphere/payment/BkashPayment.h"

namespace agri {
namespace payment {

std::string BkashPayment::methodKey() const { return "bkash"; }

std::string BkashPayment::displayName() const { return "bKash"; }

std::string BkashPayment::referencePrefix() const { return "BKS"; }

} // namespace payment
} // namespace agri
