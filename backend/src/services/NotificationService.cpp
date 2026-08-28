#include "agrisphere/services/NotificationService.h"

#include "agrisphere/db/JsonMapper.h"

namespace agri {
namespace service {
namespace {

json::Value failure(const std::string& message) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(false));
    out.set("error", json::Value(message));
    return out;
}

} // namespace

json::Value NotificationService::list(long long userId, int limit) {
    if (userId <= 0) {
        return failure("Please log in to see your notifications.");
    }

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("notifications", db::rowsToJson(notifications_.listForUser(userId, limit)));
    out.set("unread_count", json::Value(notifications_.unreadCount(userId)));
    return out;
}

json::Value NotificationService::unreadCount(long long userId) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("unread_count", json::Value(userId > 0 ? notifications_.unreadCount(userId) : 0));
    return out;
}

json::Value NotificationService::markAsRead(long long userId, long long notificationId) {
    if (userId <= 0 || notificationId <= 0) {
        return failure("Invalid request.");
    }
    if (notifications_.markAsRead(notificationId, userId) == 0) {
        return failure("Notification not found.");
    }

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("message", json::Value("Notification marked as read."));
    out.set("unread_count", json::Value(notifications_.unreadCount(userId)));
    return out;
}

json::Value NotificationService::markAllAsRead(long long userId) {
    if (userId <= 0) {
        return failure("Invalid request.");
    }
    const long long changed = notifications_.markAllAsRead(userId);

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("message", json::Value(changed > 0 ? "All notifications marked as read."
                                               : "No unread notifications."));
    out.set("unread_count", json::Value(0));
    return out;
}

} // namespace service
} // namespace agri
