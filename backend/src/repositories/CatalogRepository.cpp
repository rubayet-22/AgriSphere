#include "agrisphere/repositories/CatalogRepository.h"

#include "agrisphere/db/DatabaseManager.h"

namespace agri {
namespace repo {
namespace {


const char* orderByClause(const std::string& sort) {
    if (sort == "price_low") return "fp.price_per_unit ASC";
    if (sort == "price_high") return "fp.price_per_unit DESC";
    if (sort == "name") return "fp.product_name ASC";
    return "fp.product_id DESC";
}

} // namespace

db::ResultSet CatalogRepository::listCategories() {
    return db::DatabaseManager::getInstance().query(
        "SELECT * FROM category ORDER BY category_name");
}

std::string CatalogRepository::categoryName(long long categoryId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT category_name FROM category WHERE category_id = ?", {categoryId});
    if (rows.empty()) {
        return "Unknown";
    }
    return rows.at(0).get("category_name", "Unknown");
}

db::ResultSet CatalogRepository::browseProducts(long long categoryId,
                                                const std::string& search,
                                                const std::string& sort) {
    std::string where = "fp.status = 'approved'";
    db::SqlParams params;

    if (categoryId > 0) {
        where += " AND fp.category_id = ?";
        params.emplace_back(categoryId);
    }
    if (!search.empty()) {
        where += " AND (fp.product_name LIKE ? OR fp.description LIKE ?)";
        const std::string term = "%" + search + "%";
        params.emplace_back(term);
        params.emplace_back(term);
    }

    // price_per_unit always stays the farmer's list price. The discount is
    // reported separately, and effective_price is what a customer pays.
    const std::string sql =
        "SELECT fp.*, c.category_name, "
        "CONCAT(u.First_name, ' ', u.Last_name) as farmer_name, "
        "COALESCE(fi.quantity_available, fp.quantity, 0) as stock, "
        "COALESCE(pd.discount_percent, 0) AS discount_percent, "
        "IF(pd.discount_percent IS NULL, 0, 1) AS has_discount, "
        "ROUND(fp.price_per_unit * (1 - COALESCE(pd.discount_percent, 0) / 100), 2) AS effective_price "
        "FROM farm_product fp "
        "JOIN category c ON c.category_id = fp.category_id "
        "JOIN user u ON fp.farmer_id = u.User_id "
        "LEFT JOIN farm_inventory fi ON fi.product_id = fp.product_id "
        "LEFT JOIN product_discount pd "
        "       ON pd.product_id = fp.product_id AND pd.is_active = 1 "
        "WHERE " + where + " ORDER BY " + orderByClause(sort);

    return db::DatabaseManager::getInstance().query(sql, params);
}

bool CatalogRepository::isApprovedProduct(long long productId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT product_id FROM farm_product WHERE product_id = ? AND status = 'approved'",
        {productId});
    return !rows.empty();
}

std::optional<ProductSummary> CatalogRepository::findProduct(long long productId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT product_id, product_name, farmer_id FROM farm_product WHERE product_id = ?",
        {productId});
    if (rows.empty()) {
        return std::nullopt;
    }
    ProductSummary summary;
    summary.productId = rows.at(0).getInt("product_id");
    summary.farmerId = rows.at(0).getInt("farmer_id");
    summary.productName = rows.at(0).get("product_name");
    return summary;
}

db::ResultSet CatalogRepository::listFarmerProducts(long long farmerId) {
    return db::DatabaseManager::getInstance().query(
        "SELECT fp.*, c.category_name, "
        "COALESCE(fi.quantity_available, 0) AS quantity_available, "
        "MAX(gp.price_per_unit) as govt_price "
        "FROM farm_product fp "
        "JOIN category c ON c.category_id = fp.category_id "
        "LEFT JOIN farm_inventory fi ON fi.product_id = fp.product_id "
        "LEFT JOIN government_prices gp ON LOWER(fp.product_name) = LOWER(gp.product_name) "
        "WHERE fp.farmer_id = ? "
        "GROUP BY fp.product_id "
        "ORDER BY fp.product_id DESC",
        {farmerId});
}

long long CatalogRepository::createProduct(const NewProduct& product) {
    const db::ExecResult result = db::DatabaseManager::getInstance().execute(
        "INSERT INTO farm_product (farmer_id, category_id, product_name, description, "
        "product_image, unit, price_per_unit, quantity, status, submitted_at, created_at) "
        "VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())",
        {product.farmerId, product.categoryId, product.productName, product.description,
         product.productImage, product.unit, product.pricePerUnit, product.quantity});
    return result.insertId;
}

void CatalogRepository::upsertInventory(long long productId, long long quantity) {
    db::DatabaseManager::getInstance().execute(
        "INSERT INTO farm_inventory (product_id, quantity_available) VALUES (?, ?) "
        "ON DUPLICATE KEY UPDATE quantity_available = ?",
        {productId, quantity, quantity});
}

bool CatalogRepository::inventoryExists(long long productId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT product_id FROM farm_inventory WHERE product_id = ?", {productId});
    return !rows.empty();
}

void CatalogRepository::setInventoryQuantity(long long productId, long long quantity) {
    db::DatabaseManager::getInstance().execute(
        "UPDATE farm_inventory SET quantity_available = ? WHERE product_id = ?",
        {quantity, productId});
}

void CatalogRepository::insertInventory(long long productId, long long quantity) {
    db::DatabaseManager::getInstance().execute(
        "INSERT INTO farm_inventory (product_id, quantity_available) VALUES (?, ?)",
        {productId, quantity});
}

void CatalogRepository::updateProductPriceForFarmer(long long productId,
                                                    long long farmerId,
                                                    double price) {
    db::DatabaseManager::getInstance().execute(
        "UPDATE farm_product SET price_per_unit = ? WHERE product_id = ? AND farmer_id = ?",
        {price, productId, farmerId});
}

long long CatalogRepository::updateProductImageForFarmer(long long productId,
                                                         long long farmerId,
                                                         const std::string& imageFileName) {
    
                                                            
    const db::ExecResult result = db::DatabaseManager::getInstance().execute(
        "UPDATE farm_product SET product_image = ? WHERE product_id = ? AND farmer_id = ?",
        {imageFileName, productId, farmerId});
    return result.affectedRows;
}

long long CatalogRepository::updateProductImage(long long productId,
                                                const std::string& imageFileName) {
    const db::ExecResult result = db::DatabaseManager::getInstance().execute(
        "UPDATE farm_product SET product_image = ? WHERE product_id = ?",
        {imageFileName, productId});
    return result.affectedRows;
}

std::string CatalogRepository::productImageName(long long productId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT product_image FROM farm_product WHERE product_id = ?", {productId});
    if (rows.empty()) {
        return std::string();
    }
    return rows.at(0).get("product_image");
}

db::ResultSet CatalogRepository::listFarmerLand(long long farmerId) {
    return db::DatabaseManager::getInstance().query(
        "SELECT land_size, soil_type, district, upazila, latitude, longitude "
        "FROM land WHERE farmer_id = ?",
        {farmerId});
}

void CatalogRepository::approveProduct(long long productId, long long adminId) {
    db::DatabaseManager::getInstance().execute(
        "UPDATE farm_product SET status = 'approved', rejection_reason = NULL, "
        "approved_at = NOW(), approved_by = ? WHERE product_id = ?",
        {adminId, productId});
}

void CatalogRepository::rejectProduct(long long productId, const std::string& reason) {
    db::DatabaseManager::getInstance().execute(
        "UPDATE farm_product SET status = 'rejected', rejection_reason = ? WHERE product_id = ?",
        {reason, productId});
}

void CatalogRepository::resetProductToPending(long long productId) {
    db::DatabaseManager::getInstance().execute(
        "UPDATE farm_product SET status = 'pending', rejection_reason = NULL, "
        "approved_at = NULL, approved_by = NULL WHERE product_id = ?",
        {productId});
}

db::ResultSet CatalogRepository::searchGovernmentPrices(const std::string& productName) {
    return db::DatabaseManager::getInstance().query(
        "SELECT gp.*, c.category_name FROM government_prices gp "
        "JOIN category c ON gp.category_id = c.category_id "
        "WHERE gp.product_name LIKE ? ORDER BY gp.product_name LIMIT 5",
        {"%" + productName + "%"});
}

db::ResultSet CatalogRepository::governmentPricesForCategory(long long categoryId) {
    return db::DatabaseManager::getInstance().query(
        "SELECT * FROM government_prices WHERE category_id = ? ORDER BY product_name",
        {categoryId});
}

} // namespace repo
} // namespace agri
