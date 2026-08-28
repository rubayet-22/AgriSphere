//  AgriSphere - DECORATOR PATTERN : the Abstract Decorator
//  NotificationDecorator is a Notification that HOLDS another Notification.
//  That single fact is what makes the pattern work: because the wrapped object
//  is itself a Notification, a decorator can wrap the base notification or
//  another decorator, to any depth, without any class knowing what it wrapped.
//
//  It implements send() and getDescription() by delegating to the wrapped
//  object. A concrete decorator overrides them, calls this base version first
//  (so the original behaviour still happens), then adds exactly one extra
//  responsibility.
//
//  AgriSphere has no SMS gateway, mail server or push service, so a channel
//  cannot really leave the application. Instead each concrete decorator calls
//  storeChannelNotification() to place its own clearly labelled entry in the
//  customer's notification centre - "(Email Notification) 10% off Katla" -
//  which is what makes the simulated delivery visible to the customer.
#pragma once

#include "agrisphere/decorator/Notification.h"
#include "agrisphere/repositories/NotificationRepository.h"

#include <memory>
#include <string>

namespace agri {
namespace decorator {

class NotificationDecorator : public Notification {
public:
    NotificationDecorator(std::unique_ptr<Notification> wrapped, repo::NewNotification payload);

    // Delegates to the wrapped notification. Concrete decorators call this
    // first and then add their own delivery.
    void send() override;

    // Delegates to the wrapped notification. Concrete decorators append their
    // own channel name.
    std::string getDescription() const override;

protected:
    // Stores this channel's own entry in the notification centre, with the
    // title prefixed "(<channelLabel> Notification) ". `notificationType` is
    // written to notification.type so the page can pick a matching icon.
    void storeChannelNotification(const std::string& channelLabel,
                                  const std::string& notificationType);

    std::unique_ptr<Notification> wrapped_;
    repo::NewNotification payload_;
    repo::NotificationRepository notifications_;
};

// Builds "(<channelLabel> Notification) <title>", trimmed to fit
// notification.title (VARCHAR(150)).
std::string labelledTitle(const std::string& channelLabel, const std::string& title);

} // namespace decorator
} // namespace agri
