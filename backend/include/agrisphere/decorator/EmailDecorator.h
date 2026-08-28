//  AgriSphere - DECORATOR PATTERN : Concrete Decorator (Email)
//  Adds email delivery on top of whatever it wraps, and nothing else.
//
//  SIMULATED: AgriSphere has no mail server and none was added for this
//  feature, so no email is actually sent. Instead send() places the email
//  notification in the customer's own notification centre, titled
//  "(Email Notification) ...", and records the delivery in the backend log.
#pragma once

#include "agrisphere/decorator/NotificationDecorator.h"

#include <memory>
#include <string>

namespace agri {
namespace decorator {

class EmailDecorator : public NotificationDecorator {
public:
    EmailDecorator(std::unique_ptr<Notification> wrapped,
                   repo::NewNotification payload,
                   std::string emailAddress);

    void send() override;
    std::string getDescription() const override;

private:
    std::string emailAddress_;
};

} // namespace decorator
} // namespace agri
