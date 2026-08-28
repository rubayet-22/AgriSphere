#include "agrisphere/decorator/BaseNotification.h"

#include "agrisphere/core/Logger.h"
#include "agrisphere/decorator/NotificationDecorator.h"

#include <utility>

namespace agri {
namespace decorator {

BaseNotification::BaseNotification(repo::NewNotification payload, std::string memberName)
    : payload_(std::move(payload)), memberName_(std::move(memberName)) {}

// The existing behaviour: the notification row the member reads on
// customer/notifications.php. Its title is labelled "(In-App Notification)"
// so every entry in the notification centre states which channel it came from.
void BaseNotification::send() {
    repo::NewNotification entry = payload_;
    entry.title = labelledTitle("In-App", payload_.title);
    notifications_.insert(entry);

    Logger::info("[notify] In-App notification stored for " + memberName_ + " #" +
                 std::to_string(payload_.userId));
}

std::string BaseNotification::getDescription() const { return "In-App"; }

} // namespace decorator
} // namespace agri
