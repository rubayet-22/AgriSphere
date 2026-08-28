#include "agrisphere/decorator/NotificationChain.h"

#include "agrisphere/decorator/BaseNotification.h"
#include "agrisphere/decorator/EmailDecorator.h"
#include "agrisphere/decorator/PushNotificationDecorator.h"
#include "agrisphere/decorator/SmsDecorator.h"

#include <utility>

namespace agri {
namespace decorator {

std::unique_ptr<Notification> buildDeliveryChain(const repo::NewNotification& payload,
                                                 const DeliveryPreferences& preferences) {
    
    std::unique_ptr<Notification> notification =
        std::make_unique<BaseNotification>(payload, preferences.memberName);

   
    if (preferences.sms) {
        notification =
            std::make_unique<SmsDecorator>(std::move(notification), payload, preferences.phone);
    }
    if (preferences.email) {
        notification = std::make_unique<EmailDecorator>(std::move(notification), payload,
                                                        preferences.emailAddress);
    }
    if (preferences.push) {
        notification = std::make_unique<PushNotificationDecorator>(std::move(notification), payload,
                                                                   preferences.memberName);
    }

    return notification;
}

} // namespace decorator
} // namespace agri
