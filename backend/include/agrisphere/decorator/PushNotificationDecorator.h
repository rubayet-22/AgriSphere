//  AgriSphere - DECORATOR PATTERN : Concrete Decorator (Push)
//  Adds push delivery on top of whatever it wraps, and nothing else.
//
//  SIMULATED: AgriSphere has no push service (no Firebase, no mobile app) and
//  none was added for this feature, so no push is transmitted. Instead send()
//  places the push notification in the customer's own notification centre,
//  titled "(Push Notification) ...", and records it in the backend log.
#pragma once

#include "agrisphere/decorator/NotificationDecorator.h"

#include <memory>
#include <string>

namespace agri {
namespace decorator {

class PushNotificationDecorator : public NotificationDecorator {
public:
    PushNotificationDecorator(std::unique_ptr<Notification> wrapped,
                              repo::NewNotification payload,
                              std::string memberName);

    void send() override;
    std::string getDescription() const override;

private:
    std::string memberName_;
};

} // namespace decorator
} // namespace agri
