//  AgriSphere - DECORATOR PATTERN : the Component interface
//  One notification that can be delivered. Both the plain notification and
//  every optional delivery behaviour implement this same interface, which is
//  what allows a decorator to wrap another decorator:
//
//      Notification                        <- this interface (Component)
//        BaseNotification                  <- Concrete Component
//        NotificationDecorator             <- Abstract Decorator
//          SmsDecorator                    <- Concrete Decorators
//          EmailDecorator
//          PushNotificationDecorator
//
//  SCOPE: this folder is only about HOW a notification is delivered. Deciding
//  WHEN a notification happens and WHO receives it stays entirely with the
//  Observer pattern (observer/), which is not changed by this feature.
#pragma once

#include <string>

namespace agri {
namespace decorator {

class Notification {
public:
    virtual ~Notification() = default;

    // Delivers the notification. A decorator delegates to the object it wraps
    // first, then adds its own delivery on top.
    virtual void send() = 0;

    // The channels this notification will use, built up as the chain is
    // composed, e.g. "In-App + SMS + Email". Used for the backend log so the
    // whole chain can be traced.
    virtual std::string getDescription() const = 0;
};

} // namespace decorator
} // namespace agri
