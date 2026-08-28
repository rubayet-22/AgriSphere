#include "agrisphere/strategy/DiscountContext.h"

#include "agrisphere/strategy/BulkDiscountStrategy.h"
#include "agrisphere/strategy/PremiumBulkDiscountStrategy.h"
#include "agrisphere/strategy/PremiumDiscountStrategy.h"
#include "agrisphere/strategy/RegularDiscountStrategy.h"

namespace agri {
namespace strategy {

void DiscountContext::setStrategy(std::unique_ptr<DiscountStrategy> strategy) {
    strategy_ = std::move(strategy);
}

void DiscountContext::selectStrategy(const CustomerProfile& profile) {
    
    const bool isBulk = profile.totalItems >= kBulkOrderThreshold;

    if (profile.isPremium && isBulk) {
        setStrategy(std::make_unique<PremiumBulkDiscountStrategy>());
    } else if (isBulk) {
        setStrategy(std::make_unique<BulkDiscountStrategy>());
    } else if (profile.isPremium) {
        setStrategy(std::make_unique<PremiumDiscountStrategy>());
    } else {
        setStrategy(std::make_unique<RegularDiscountStrategy>());
    }
}

double DiscountContext::calculateDiscount(double subtotal) const {
    if (!strategy_ || subtotal <= 0.0) {
        return 0.0;
    }
    return strategy_->calculateDiscount(subtotal);
}

std::string DiscountContext::strategyName() const {
    return strategy_ ? strategy_->name() : std::string("None");
}

double DiscountContext::discountPercent() const {
    return strategy_ ? strategy_->discountPercent() : 0.0;
}

std::string DiscountContext::description() const {
    return strategy_ ? strategy_->description() : std::string();
}

} // namespace strategy
} // namespace agri
