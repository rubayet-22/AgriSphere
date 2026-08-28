#include "agrisphere/repositories/OrderRepository.h"

namespace agri {
namespace repo {
namespace {

// Only these columns may be written by updateStatus; anything else is ignored.
bool isKnownTimestampColumn(const std::string& column) {
    return column == "processed_at" || column == "shipped_at" || column == "delivered_at" ||
           column == "cancelled_at";
}

} // namespace

long long OrderRepository::createOrder(db::PooledConnection& connection,
                                       long long customerId,
                                       double totalAmount,
                                       const std::string& status) {
    const db::ExecResult result = connection.execute(
        "INSERT INTO customer_order (customer_id, order_date, total_amount, status) "
        "VALUES (?, NOW(), ?, ?)",
        {customerId, totalAmount, status});
    return result.insertId;
}

void OrderRepository::addOrderItem(db::PooledConnection& connection,
                                   long long orderId,
                                   long long productId,
                                   long long quantity,
                                   double pricePerUnit) {
    connection.execute(
        "INSERT INTO order_items (order_id, product_id, quantity, price_per_unit) "
        "VALUES (?, ?, ?, ?)",
        {orderId, productId, quantity, pricePerUnit});
}

std::optional<long long> OrderRepository::lockAvailableStock(db::PooledConnection& connection,
                                                             long long productId) {
    const db::ResultSet rows = connection.query(
        "SELECT COALESCE(fp.quantity, fi.quantity_available, 0) as quantity "
        "FROM farm_product fp "
        "LEFT JOIN farm_inventory fi ON fp.product_id = fi.product_id "
        "WHERE fp.product_id = ? FOR UPDATE",
        {productId});
    if (rows.empty()) {
        return std::nullopt;
    }
    return rows.at(0).getInt("quantity", 0);
}

void OrderRepository::decreaseProductQuantity(db::PooledConnection& connection,
                                              long long productId,
                                              long long quantity) {
    connection.execute("UPDATE farm_product SET quantity = quantity - ? WHERE product_id = ?",
                       {quantity, productId});
}

void OrderRepository::decreaseInventory(db::PooledConnection& connection,
                                        long long productId,
                                        long long quantity) {
    connection.execute(
        "UPDATE farm_inventory SET quantity_available = quantity_available - ? WHERE product_id = ?",
        {quantity, productId});
}

void OrderRepository::insertPayment(db::PooledConnection& connection,
                                    long long orderId,
                                    const std::string& method,
                                    const std::string& accountNumber,
                                    const std::string& status,
                                    double fee,
                                    const std::string& referenceCode) {
    connection.execute(
        "INSERT INTO payment (order_id, payment_method, bkash_number, payment_status, "
        "payment_fee, payment_reference) VALUES (?, ?, ?, ?, ?, ?)",
        {orderId, method, accountNumber, status, fee, referenceCode});
}

void OrderRepository::insertPaymentWithTime(db::PooledConnection& connection,
                                            long long orderId,
                                            const std::string& method,
                                            const std::string& accountNumber,
                                            const std::string& status,
                                            double fee,
                                            const std::string& referenceCode) {
    connection.execute(
        "INSERT INTO payment (order_id, payment_method, bkash_number, payment_status, "
        "payment_fee, payment_reference, payment_time) VALUES (?, ?, ?, ?, ?, ?, NOW())",
        {orderId, method, accountNumber, status, fee, referenceCode});
}

void OrderRepository::upsertCustomerAddress(db::PooledConnection& connection,
                                            long long customerId,
                                            const std::string& address,
                                            const std::string& phone) {
    connection.execute(
        "INSERT INTO customer (customer_id, address, phone) VALUES (?, ?, ?) "
        "ON DUPLICATE KEY UPDATE address = ?, phone = ?",
        {customerId, address, phone, address, phone});
}

std::optional<OrderPaymentInfo> OrderRepository::findOrderWithPayment(long long orderId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT co.order_id, co.status, p.payment_method, p.payment_status "
        "FROM customer_order co "
        "LEFT JOIN payment p ON co.order_id = p.order_id "
        "WHERE co.order_id = ?",
        {orderId});
    if (rows.empty()) {
        return std::nullopt;
    }
    OrderPaymentInfo info;
    info.orderId = rows.at(0).getInt("order_id");
    info.status = rows.at(0).get("status");
    info.paymentMethod = rows.at(0).get("payment_method");
    info.paymentStatus = rows.at(0).get("payment_status");
    return info;
}

void OrderRepository::updateStatus(long long orderId,
                                   const std::string& status,
                                   const std::string& timestampColumn,
                                   long long adminId) {
    if (isKnownTimestampColumn(timestampColumn)) {
        db::DatabaseManager::getInstance().execute(
            "UPDATE customer_order SET status = ?, " + timestampColumn +
                " = NOW(), updated_by = ? WHERE order_id = ?",
            {status, adminId, orderId});
    } else {
        db::DatabaseManager::getInstance().execute(
            "UPDATE customer_order SET status = ?, updated_by = ? WHERE order_id = ?",
            {status, adminId, orderId});
    }
}

bool OrderRepository::paymentExists(long long orderId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT order_id FROM payment WHERE order_id = ?", {orderId});
    return !rows.empty();
}

void OrderRepository::markPaymentPaid(long long orderId) {
    db::DatabaseManager::getInstance().execute(
        "UPDATE payment SET payment_status = 'Paid', payment_time = NOW() WHERE order_id = ?",
        {orderId});
}

void OrderRepository::updatePaymentStatus(long long orderId,
                                          const std::string& status,
                                          bool touchTime) {
    if (touchTime) {
        db::DatabaseManager::getInstance().execute(
            "UPDATE payment SET payment_status = ?, payment_time = NOW() WHERE order_id = ?",
            {status, orderId});
    } else {
        db::DatabaseManager::getInstance().execute(
            "UPDATE payment SET payment_status = ? WHERE order_id = ?", {status, orderId});
    }
}

void OrderRepository::insertUnknownPayment(long long orderId, const std::string& status) {
    db::DatabaseManager::getInstance().execute(
        "INSERT INTO payment (order_id, payment_method, payment_status, payment_time) "
        "VALUES (?, 'Unknown', ?, NOW())",
        {orderId, status});
}

} // namespace repo
} // namespace agri
