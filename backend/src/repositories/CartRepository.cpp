#include "agrisphere/repositories/CartRepository.h"

namespace agri {
namespace repo {
namespace {



const char* kPriceColumns =
    "fp.price_per_unit, "
    "COALESCE(pd.discount_percent, 0) AS discount_percent, "
    "IF(pd.discount_percent IS NULL, 0, 1) AS has_discount, "
    "ROUND(fp.price_per_unit * (1 - COALESCE(pd.discount_percent, 0) / 100), 2) AS effective_price ";

const char* kDiscountJoin =
    "LEFT JOIN product_discount pd "
    "       ON pd.product_id = fp.product_id AND pd.is_active = 1 ";

} // namespace

bool CartRepository::customerExists(long long customerId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT customer_id FROM customer WHERE customer_id = ?", {customerId});
    return !rows.empty();
}

db::ResultSet CartRepository::listDetailed(long long customerId) {
    return db::DatabaseManager::getInstance().query(
        std::string("SELECT cc.*, fp.product_name, fp.unit, fp.product_image, ") + kPriceColumns +
            ", COALESCE(fp.quantity, 0) as stock, fp.description, "
            "CONCAT(u.First_name, ' ', u.Last_name) as farmer_name, "
            "c.category_name "
            "FROM customer_cart cc "
            "JOIN farm_product fp ON cc.product_id = fp.product_id "
            "JOIN user u ON fp.farmer_id = u.User_id "
            "JOIN category c ON fp.category_id = c.category_id " +
            kDiscountJoin +
            "WHERE cc.customer_id = ? AND fp.status = 'approved' "
            "ORDER BY cc.cart_id DESC",
        {customerId});
}

db::ResultSet CartRepository::listForCheckout(long long customerId) {
    return db::DatabaseManager::getInstance().query(
        std::string("SELECT cc.*, fp.product_name, fp.unit, fp.product_image, ") + kPriceColumns +
            ", COALESCE(fp.quantity, fi.quantity_available, 0) as stock, "
            "CONCAT(u.First_name, ' ', u.Last_name) as farmer_name "
            "FROM customer_cart cc "
            "JOIN farm_product fp ON cc.product_id = fp.product_id "
            "JOIN user u ON fp.farmer_id = u.User_id "
            "LEFT JOIN farm_inventory fi ON fp.product_id = fi.product_id " +
            kDiscountJoin +
            "WHERE cc.customer_id = ? AND fp.status = 'approved'",
        {customerId});
}

db::ResultSet CartRepository::listForOrder(long long customerId) {
    return db::DatabaseManager::getInstance().query(
        std::string("SELECT cc.*, fp.product_name, fp.unit, ") + kPriceColumns +
            "FROM customer_cart cc "
            "JOIN farm_product fp ON cc.product_id = fp.product_id " +
            kDiscountJoin +
            "WHERE cc.customer_id = ?",
        {customerId});
}

long long CartRepository::totalQuantity(long long customerId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT SUM(quantity) as count FROM customer_cart WHERE customer_id = ?", {customerId});
    if (rows.empty()) {
        return 0;
    }
    return rows.at(0).getInt("count", 0);
}

std::optional<CartLine> CartRepository::findLine(long long customerId, long long productId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT cart_id, quantity FROM customer_cart WHERE customer_id = ? AND product_id = ?",
        {customerId, productId});
    if (rows.empty()) {
        return std::nullopt;
    }
    CartLine line;
    line.cartId = rows.at(0).getInt("cart_id");
    line.quantity = rows.at(0).getInt("quantity");
    return line;
}

void CartRepository::insertLine(long long customerId, long long productId, long long quantity) {
    db::DatabaseManager::getInstance().execute(
        "INSERT INTO customer_cart (customer_id, product_id, quantity) VALUES (?, ?, ?)",
        {customerId, productId, quantity});
}

void CartRepository::setQuantityById(long long cartId, long long quantity) {
    db::DatabaseManager::getInstance().execute(
        "UPDATE customer_cart SET quantity = ? WHERE cart_id = ?", {quantity, cartId});
}

void CartRepository::setQuantityForCustomer(long long cartId,
                                            long long customerId,
                                            long long quantity) {
    db::DatabaseManager::getInstance().execute(
        "UPDATE customer_cart SET quantity = ? WHERE cart_id = ? AND customer_id = ?",
        {quantity, cartId, customerId});
}

void CartRepository::removeLine(long long cartId, long long customerId) {
    db::DatabaseManager::getInstance().execute(
        "DELETE FROM customer_cart WHERE cart_id = ? AND customer_id = ?", {cartId, customerId});
}

void CartRepository::clear(long long customerId) {
    db::DatabaseManager::getInstance().execute("DELETE FROM customer_cart WHERE customer_id = ?",
                                               {customerId});
}

void CartRepository::clear(db::PooledConnection& connection, long long customerId) {
    connection.execute("DELETE FROM customer_cart WHERE customer_id = ?", {customerId});
}




std::optional<CartLine> CartRepository::findLine(db::PooledConnection& connection,
                                                 long long customerId,
                                                 long long productId) {
    const db::ResultSet rows = connection.query(
        "SELECT cart_id, quantity FROM customer_cart WHERE customer_id = ? AND product_id = ?",
        {customerId, productId});
    if (rows.empty()) {
        return std::nullopt;
    }
    CartLine line;
    line.cartId = rows.at(0).getInt("cart_id");
    line.quantity = rows.at(0).getInt("quantity");
    return line;
}

void CartRepository::insertLine(db::PooledConnection& connection,
                                long long customerId,
                                long long productId,
                                long long quantity) {
    connection.execute(
        "INSERT INTO customer_cart (customer_id, product_id, quantity) VALUES (?, ?, ?)",
        {customerId, productId, quantity});
}

void CartRepository::setQuantityById(db::PooledConnection& connection,
                                     long long cartId,
                                     long long quantity) {
    connection.execute("UPDATE customer_cart SET quantity = ? WHERE cart_id = ?",
                       {quantity, cartId});
}

} // namespace repo
} // namespace agri
