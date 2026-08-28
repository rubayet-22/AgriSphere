#include "agrisphere/observer/MemberNotificationObserver.h"

#include "agrisphere/core/Logger.h"
#include "agrisphere/decorator/NotificationChain.h"

#include <memory>
#include <utility>

namespace agri {
namespace observer {

MemberNotificationObserver::MemberNotificationObserver(long long memberId,
                                                       std::string memberName,
                                                       decorator::DeliveryPreferences preferences)
    : memberId_(memberId), memberName_(std::move(memberName)),
      preferences_(std::move(preferences)) {}

std::string MemberNotificationObserver::observerName() const {
    return "MemberNotificationObserver(" + memberName_ + " #" + std::to_string(memberId_) + ")";
}

// This is the observer's reaction to the event. It decides WHAT the member is
// told; the decorator chain below decides HOW it is delivered.
void MemberNotificationObserver::onDiscountActivated(const DiscountEvent& event) {
    repo::NewNotification notification;
    notification.userId = memberId_;
    notification.type = "discount";
    notification.title = event.title();
    notification.message = event.message();
    notification.productId = event.productId;
    notification.farmerId = event.farmerId;
    notification.discountPercent = event.discountPercent;

    // Decorator pattern: BaseNotification stores the row exactly as before,
    // and each channel this member enabled wraps it with one extra delivery.
    const std::unique_ptr<decorator::Notification> delivery =
        decorator::buildDeliveryChain(notification, preferences_);
    delivery->send();

    Logger::info("[notify] " + memberName_ + " #" + std::to_string(memberId_) +
                 " delivered via: " + delivery->getDescription());
}

} // namespace observer
} // namespace agri
