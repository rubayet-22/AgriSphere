#include "agrisphere/payment/PaymentCreator.h"

#include "agrisphere/core/Strings.h"
#include "agrisphere/payment/PaymentCreatorRegistry.h"

namespace agri {
namespace payment {

// The payment_method string is normalized exactly as before (trimmed and
// lower-cased) and then looked up in the registry. There is no if/else chain
// any more: which strings are accepted, and which Concrete Creator each one
// means, is decided entirely by registerPaymentCreators().
std::unique_ptr<PaymentCreator> PaymentCreator::forMethod(const std::string& paymentMethod) {
    const std::string key = strings::toLower(strings::trim(paymentMethod));

    const PaymentCreatorRegistry::CreatorFactory* factory =
        PaymentCreatorRegistry::getInstance().getFactory(key);
    if (factory == nullptr) {
        return nullptr;
    }

    // Calling the stored factory builds a brand new Concrete Creator, so every
    // caller still gets its own object exactly as it did before.
    return (*factory)();
}

std::vector<std::string> PaymentCreator::supportedMethods() {
    return PaymentCreatorRegistry::getInstance().registeredNames();
}

} // namespace payment
} // namespace agri
