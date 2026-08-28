//  AgriSphere - DECORATOR PATTERN : Concrete Decorator (SMS)
//  Adds SMS delivery on top of whatever it wraps, and nothing else.
//
//  SIMULATED: AgriSphere has no SMS provider and none was added for this
//  feature, so no SMS is actually transmitted. Instead send() places the SMS
//  notification in the customer's own notification centre, titled
//  "(SMS Notification) ...", and records the delivery in the backend log.
#pragma once

#include "agrisphere/decorator/NotificationDecorator.h"

#include <memory>
#include <string>

namespace agri {
namespace decorator {

class SmsDecorator : public NotificationDecorator {
public:
    SmsDecorator(std::unique_ptr<Notification> wrapped,
                 repo::NewNotification payload,
                 std::string phone);

    void send() override;
    std::string getDescription() const override;

private:
    std::string phone_;
};

} // namespace decorator
} // namespace agri
