#include "agrisphere/decorator/SmsDecorator.h"

#include "agrisphere/core/Logger.h"

#include <utility>

namespace agri {
namespace decorator {

SmsDecorator::SmsDecorator(std::unique_ptr<Notification> wrapped,
                           repo::NewNotification payload,
                           std::string phone)
    : NotificationDecorator(std::move(wrapped), std::move(payload)), phone_(std::move(phone)) {}

void SmsDecorator::send() {
    
    NotificationDecorator::send();
    
    storeChannelNotification("SMS", "discount_sms");
    Logger::info("[notify] SMS notification shown in-app for " +
                 (phone_.empty() ? std::string("(no number on file)") : phone_));
}

std::string SmsDecorator::getDescription() const {
    return NotificationDecorator::getDescription() + " + SMS";
}

} // namespace decorator
} // namespace agri
