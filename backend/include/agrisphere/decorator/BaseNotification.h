//  AgriSphere - DECORATOR PATTERN : the Concrete Component
//  BaseNotification is the notification AgriSphere already had: the row in the
//  `notification` table that the member sees on customer/notifications.php.
//
//  It is the object every chain ends with, and it is always present: In-App
//  delivery cannot be switched off. Its entry is titled
//  "(In-App Notification) ..." so every row in the notification centre states
//  which channel produced it.
//
//  Like the decorators, it writes through the existing
//  repo::NotificationRepository (and therefore through the Singleton
//  DatabaseManager) - no second notification table, no duplicated SQL.
//  Because a chain bottoms out here exactly once, the In-App entry is never
//  written twice however many channels are enabled.
#pragma once

#include "agrisphere/decorator/Notification.h"
#include "agrisphere/repositories/NotificationRepository.h"

#include <string>

namespace agri {
namespace decorator {

class BaseNotification : public Notification {
public:
    BaseNotification(repo::NewNotification payload, std::string memberName);

    // Stores the notification row - the existing in-app behaviour.
    void send() override;

    // "In-App"
    std::string getDescription() const override;

private:
    repo::NewNotification payload_;
    std::string memberName_;
    repo::NotificationRepository notifications_;
};

} // namespace decorator
} // namespace agri
