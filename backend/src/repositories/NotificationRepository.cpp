#include "agrisphere/repositories/NotificationRepository.h"

#include "agrisphere/db/DatabaseManager.h"

namespace agri {
namespace repo {

long long NotificationRepository::insert(const NewNotification& notification) {
    // A zero id means "not linked to a product/farmer", stored as SQL NULL.
    const db::SqlValue productId = notification.productId > 0
                                       ? db::SqlValue(notification.productId)
                                       : db::SqlValue::null();
    const db::SqlValue farmerId = notification.farmerId > 0
                                      ? db::SqlValue(notification.farmerId)
                                      : db::SqlValue::null();

    const db::ExecResult result = db::DatabaseManager::getInstance().execute(
        "INSERT INTO notification "
        "(user_id, type, title, message, product_id, farmer_id, discount_percent, is_read, created_at) "
        "VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())",
        {notification.userId, notification.type, notification.title, notification.message,
         productId, farmerId, notification.discountPercent});
    return result.insertId;
}

db::ResultSet NotificationRepository::listForUser(long long userId, int limit) {
    if (limit <= 0 || limit > 200) {
        limit = 50;
    }
    return db::DatabaseManager::getInstance().query(
        "SELECT n.notification_id, n.user_id, n.type, n.title, n.message, n.product_id, "
        "n.farmer_id, n.discount_percent, n.is_read, n.created_at, "
        "fp.product_name, fp.unit, "
        "CONCAT(u.First_name, ' ', u.Last_name) AS farmer_name "
        "FROM notification n "
        "LEFT JOIN farm_product fp ON fp.product_id = n.product_id "
        "LEFT JOIN user u ON u.User_id = n.farmer_id "
        "WHERE n.user_id = ? "
        "ORDER BY n.created_at DESC, n.notification_id DESC "
        "LIMIT " + std::to_string(limit),
        {userId});
}

long long NotificationRepository::unreadCount(long long userId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT COUNT(*) AS unread FROM notification WHERE user_id = ? AND is_read = 0",
        {userId});
    if (rows.empty()) {
        return 0;
    }
    return rows.at(0).getInt("unread", 0);
}

long long NotificationRepository::markAsRead(long long notificationId, long long userId) {
    // user_id is in the WHERE clause so a member cannot mark someone else's
    // notification as read.
    const db::ExecResult result = db::DatabaseManager::getInstance().execute(
        "UPDATE notification SET is_read = 1 WHERE notification_id = ? AND user_id = ?",
        {notificationId, userId});
    return result.affectedRows;
}

long long NotificationRepository::markAllAsRead(long long userId) {
    const db::ExecResult result = db::DatabaseManager::getInstance().execute(
        "UPDATE notification SET is_read = 1 WHERE user_id = ? AND is_read = 0", {userId});
    return result.affectedRows;
}

} // namespace repo
} // namespace agri
