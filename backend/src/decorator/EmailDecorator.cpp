#include "agrisphere/decorator/EmailDecorator.h"

#include "agrisphere/core/Logger.h"

#include <utility>

namespace agri {
namespace decorator {

EmailDecorator::EmailDecorator(std::unique_ptr<Notification> wrapped,
                               repo::NewNotification payload,
                               std::string emailAddress)
    : NotificationDecorator(std::move(wrapped), std::move(payload)),
      emailAddress_(std::move(emailAddress)) {}

void EmailDecorator::send() {
   
    NotificationDecorator::send();
    
    storeChannelNotification("Email", "discount_email");
    Logger::info("[notify] Email notification shown in-app for " +
                 (emailAddress_.empty() ? std::string("(no address on file)") : emailAddress_));
}

std::string EmailDecorator::getDescription() const {
    return NotificationDecorator::getDescription() + " + Email";
}

} // namespace decorator
} // namespace agri
