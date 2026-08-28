// AgriSphere C++ Backend - reading the notifications the observers wrote.
//
// The Observer pattern is the WRITE side (a discount event creates rows).
// This service is the READ side used by a member's account page: list them,
// count the unread ones, and mark them as read.
#pragma once

#include "agrisphere/core/Json.h"
#include "agrisphere/repositories/NotificationRepository.h"

namespace agri {
namespace service {

class NotificationService {
public:
    json::Value list(long long userId, int limit);
    json::Value unreadCount(long long userId);
    json::Value markAsRead(long long userId, long long notificationId);
    json::Value markAllAsRead(long long userId);

private:
    repo::NotificationRepository notifications_;
};

} // namespace service
} // namespace agri
