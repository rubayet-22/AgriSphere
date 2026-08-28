#include "agrisphere/payment/PaymentCreatorRegistry.h"

#include "agrisphere/core/Logger.h"
#include "agrisphere/payment/BkashCreator.h"
#include "agrisphere/payment/CardCreator.h"
#include "agrisphere/payment/CashOnDeliveryCreator.h"
#include "agrisphere/payment/NagadCreator.h"

#include <algorithm>
#include <utility>

namespace agri {
namespace payment {

PaymentCreatorRegistry& PaymentCreatorRegistry::getInstance() {
    static PaymentCreatorRegistry instance;
    return instance;
}

void PaymentCreatorRegistry::registerFactory(const std::string& name, CreatorFactory factory) {
    if (name.empty() || !factory) {
        return;
    }
    factories_[name] = std::move(factory);
}

const PaymentCreatorRegistry::CreatorFactory* PaymentCreatorRegistry::getFactory(
    const std::string& name) const {
    const auto it = factories_.find(name);
    if (it == factories_.end()) {
        return nullptr;
    }
    return &it->second;
}

std::vector<std::string> PaymentCreatorRegistry::registeredNames() const {
    std::vector<std::string> names;
    names.reserve(factories_.size());
    for (const auto& entry : factories_) {
        names.push_back(entry.first);
    }
    // unordered_map has no defined order, so sort for a stable result.
    std::sort(names.begin(), names.end());
    return names;
}



void registerPaymentCreators() {
    PaymentCreatorRegistry& registry = PaymentCreatorRegistry::getInstance();

    registry.registerFactory("bkash", [] { return std::make_unique<BkashCreator>(); });
    registry.registerFactory("nagad", [] { return std::make_unique<NagadCreator>(); });

    registry.registerFactory("cod", [] { return std::make_unique<CashOnDeliveryCreator>(); });
    registry.registerFactory("cash on delivery",
                             [] { return std::make_unique<CashOnDeliveryCreator>(); });

    registry.registerFactory("card", [] { return std::make_unique<CardCreator>(); });
    registry.registerFactory("credit card", [] { return std::make_unique<CardCreator>(); });
    registry.registerFactory("debit card", [] { return std::make_unique<CardCreator>(); });

    Logger::info("PaymentCreatorRegistry: " + std::to_string(registry.size()) +
                 " payment method key(s) registered");
}

} // namespace payment
} // namespace agri
