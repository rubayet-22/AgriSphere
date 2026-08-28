#include "agrisphere/services/DiscountService.h"

#include "agrisphere/core/Logger.h"
#include "agrisphere/core/Strings.h"
#include "agrisphere/db/JsonMapper.h"
#include "agrisphere/observer/BackendLogObserver.h"
#include "agrisphere/observer/MemberNotificationObserver.h"

#include <cmath>

namespace agri {
namespace service {
namespace {


constexpr double kMinDiscountPercent = 1.0;
constexpr double kMaxDiscountPercent = 90.0;

json::Value failure(const std::string& message) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(false));
    out.set("error", json::Value(message));
    return out;
}

double roundToTwoDecimals(double value) { return std::round(value * 100.0) / 100.0; }


decorator::DeliveryPreferences deliveryPreferencesFor(const repo::UserRecord& member) {
    decorator::DeliveryPreferences preferences;
    preferences.email = member.notifyEmail;
    preferences.sms = member.notifySms;
    preferences.push = member.notifyPush;
    preferences.emailAddress = member.email;
    preferences.phone = member.phone;
    preferences.memberName = member.fullName();
    return preferences;
}

} // namespace

DiscountService::DiscountService() {
   
    subject_.attach(std::make_shared<observer::BackendLogObserver>());
}


void DiscountService::refreshMemberObservers() {
    for (const std::shared_ptr<observer::DiscountObserver>& old : memberObservers_) {
        subject_.detach(old);
    }
    memberObservers_.clear();

    const std::vector<repo::UserRecord> members = users_.listActiveMembers();
    for (const repo::UserRecord& member : members) {
        auto memberObserver = std::make_shared<observer::MemberNotificationObserver>(
            member.userId, member.fullName(), deliveryPreferencesFor(member));
        subject_.attach(memberObserver);
        memberObservers_.push_back(memberObserver);
    }

    Logger::info("DiscountService: " + std::to_string(memberObservers_.size()) +
                 " member observer(s) subscribed to the discount subject");
}


json::Value DiscountService::activateDiscount(long long farmerId,
                                              long long productId,
                                              double discountPercent) {
   
    if (productId <= 0) {
        return failure("Please choose a product.");
    }

    discountPercent = roundToTwoDecimals(discountPercent);
    if (discountPercent < kMinDiscountPercent || discountPercent > kMaxDiscountPercent) {
        return failure("Discount must be between 1% and 90%.");
    }

    const auto product = discounts_.findProductForFarmer(productId);
    if (!product) {
        return failure("Product not found.");
    }
    if (product->farmerId != farmerId) {
        return failure("You can only discount your own products.");
    }
    if (product->status != "approved") {
        return failure("Only approved products can be discounted.");
    }

    
    std::lock_guard<std::mutex> lock(publishMutex_);
    discounts_.activate(productId, farmerId, discountPercent);

  
    const auto farmer = users_.findById(farmerId);

    observer::DiscountEvent event;
    event.productId = product->productId;
    event.productName = product->productName;
    event.farmerId = farmerId;
    event.farmerName = farmer ? farmer->fullName() : "A farmer";
    event.discountPercent = discountPercent;
    event.originalPrice = product->pricePerUnit;
    event.discountedPrice =
        roundToTwoDecimals(product->pricePerUnit * (1.0 - discountPercent / 100.0));
    event.createdAt = strings::nowDateTime();

    
    refreshMemberObservers();

    
    subject_.notifyObservers(event);

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("product_id", json::Value(event.productId));
    out.set("product_name", json::Value(event.productName));
    out.set("discount_percent", json::Value(event.discountPercent));
    out.set("original_price", json::Value(event.originalPrice));
    out.set("discounted_price", json::Value(event.discountedPrice));
    out.set("members_notified", json::Value(static_cast<long long>(memberObservers_.size())));
    out.set("message", json::Value("Discount activated. " +
                                   std::to_string(memberObservers_.size()) +
                                   " member(s) have been notified."));
    return out;
}

json::Value DiscountService::removeDiscount(long long farmerId, long long productId) {
    if (productId <= 0) {
        return failure("Please choose a product.");
    }

    const auto product = discounts_.findProductForFarmer(productId);
    if (!product) {
        return failure("Product not found.");
    }
    if (product->farmerId != farmerId) {
        return failure("You can only change your own products.");
    }

    const long long changed = discounts_.deactivate(productId, farmerId);
    if (changed == 0) {
        return failure("That product does not have an active discount.");
    }

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("product_id", json::Value(productId));
    out.set("message", json::Value("Discount removed from \"" + product->productName +
                                   "\". The original price is back."));
    return out;
}

json::Value DiscountService::listFarmerDiscounts(long long farmerId) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("products", db::rowsToJson(discounts_.listFarmerProductsWithDiscount(farmerId)));
    return out;
}

} // namespace service
} // namespace agri
