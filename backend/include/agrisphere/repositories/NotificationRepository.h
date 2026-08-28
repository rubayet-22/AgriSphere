// AgriSphere C++ Backend - data access for the `notification` table.
//
// This is what makes a notification survive after the observer has finished
// running: a member who was offline when the discount was created still sees
// the row the next time they open their account.
#pragma once

#include "agrisphere/db/ResultSet.h"

#include <string>

namespace agri {
namespace repo {

struct NewNotification {
    long long userId = 0;          // who receives it
    std::string type = "discount";
    std::string title;
    std::string message;
    long long productId = 0;       // 0 -> stored as NULL
    long long farmerId = 0;        // 0 -> stored as NULL
    double discountPercent = 0.0;
};

class NotificationRepository {
public:
    // Returns the new notification_id.
    long long insert(const NewNotification& notification);

    db::ResultSet listForUser(long long userId, int limit);
    long long unreadCount(long long userId);

    // Both return the number of rows actually changed.
    long long markAsRead(long long notificationId, long long userId);
    long long markAllAsRead(long long userId);
};

} // namespace repo
} // namespace agri
