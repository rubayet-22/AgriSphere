#include "agrisphere/repositories/DiscountRepository.h"

#include "agrisphere/db/DatabaseManager.h"

namespace agri {
namespace repo {

std::optional<DiscountableProduct> DiscountRepository::findProductForFarmer(long long productId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT product_id, farmer_id, product_name, status, price_per_unit "
        "FROM farm_product WHERE product_id = ?",
        {productId});
    if (rows.empty()) {
        return std::nullopt;
    }

    DiscountableProduct product;
    product.productId = rows.at(0).getInt("product_id");
    product.farmerId = rows.at(0).getInt("farmer_id");
    product.productName = rows.at(0).get("product_name");
    product.status = rows.at(0).get("status");
    product.pricePerUnit = rows.at(0).getDouble("price_per_unit");
    return product;
}

void DiscountRepository::activate(long long productId, long long farmerId, double discountPercent) {
    // product_id is UNIQUE, so this either inserts a new discount or updates
    // (and re-activates) the existing one for that product.
    db::DatabaseManager::getInstance().execute(
        "INSERT INTO product_discount (product_id, farmer_id, discount_percent, is_active, created_at) "
        "VALUES (?, ?, ?, 1, NOW()) "
        "ON DUPLICATE KEY UPDATE discount_percent = VALUES(discount_percent), is_active = 1",
        {productId, farmerId, discountPercent});
}

long long DiscountRepository::deactivate(long long productId, long long farmerId) {
    // farmer_id is in the WHERE clause so one farmer cannot remove another
    // farmer's discount.
    const db::ExecResult result = db::DatabaseManager::getInstance().execute(
        "UPDATE product_discount SET is_active = 0 WHERE product_id = ? AND farmer_id = ?",
        {productId, farmerId});
    return result.affectedRows;
}

double DiscountRepository::activeDiscountPercent(long long productId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT discount_percent FROM product_discount WHERE product_id = ? AND is_active = 1",
        {productId});
    if (rows.empty()) {
        return 0.0;
    }
    return rows.at(0).getDouble("discount_percent", 0.0);
}

db::ResultSet DiscountRepository::listFarmerProductsWithDiscount(long long farmerId) {
    return db::DatabaseManager::getInstance().query(
        "SELECT fp.product_id, fp.product_name, fp.unit, fp.status, fp.product_image, "
        "fp.price_per_unit, c.category_name, "
        "COALESCE(fi.quantity_available, fp.quantity, 0) AS stock, "
        "COALESCE(pd.discount_percent, 0) AS discount_percent, "
        "IF(pd.discount_percent IS NULL, 0, 1) AS has_discount, "
        "ROUND(fp.price_per_unit * (1 - COALESCE(pd.discount_percent, 0) / 100), 2) AS effective_price, "
        "pd.created_at AS discount_created_at "
        "FROM farm_product fp "
        "JOIN category c ON c.category_id = fp.category_id "
        "LEFT JOIN farm_inventory fi ON fi.product_id = fp.product_id "
        "LEFT JOIN product_discount pd "
        "       ON pd.product_id = fp.product_id AND pd.is_active = 1 "
        "WHERE fp.farmer_id = ? "
        "ORDER BY fp.product_id DESC",
        {farmerId});
}

} // namespace repo
} // namespace agri
