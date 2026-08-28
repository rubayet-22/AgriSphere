#include "agrisphere/decorator/PushNotificationDecorator.h"

#include "agrisphere/core/Logger.h"

#include <utility>

namespace agri {
namespace decorator {

PushNotificationDecorator::PushNotificationDecorator(std::unique_ptr<Notification> wrapped,
                                                     repo::NewNotification payload,
                                                     std::string memberName)
    : NotificationDecorator(std::move(wrapped), std::move(payload)),
      memberName_(std::move(memberName)) {}

void PushNotificationDecorator::send() {
    
    NotificationDecorator::send();
    
    storeChannelNotification("Push", "discount_push");
    Logger::info("[notify] Push notification shown in-app for " + memberName_);
}

std::string PushNotificationDecorator::getDescription() const {
    return NotificationDecorator::getDescription() + " + Push";
}

} // namespace decorator
} // namespace agri
