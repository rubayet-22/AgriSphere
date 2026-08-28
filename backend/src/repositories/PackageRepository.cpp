#include "agrisphere/repositories/PackageRepository.h"

namespace agri {
namespace repo {

std::vector<PackageSummary> PackageRepository::listActivePackages() {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT package_id, package_name, description FROM product_package "
        "WHERE is_active = 1 ORDER BY package_id");

    std::vector<PackageSummary> packages;
    packages.reserve(rows.size());
    for (std::size_t i = 0; i < rows.size(); ++i) {
        const db::Row row = rows.at(i);
        PackageSummary summary;
        summary.packageId = row.getInt("package_id");
        summary.packageName = row.get("package_name");
        summary.description = row.get("description");
        packages.push_back(summary);
    }
    return packages;
}

bool PackageRepository::packageExists(long long packageId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT package_id FROM product_package WHERE package_id = ? AND is_active = 1",
        {packageId});
    return !rows.empty();
}

std::string PackageRepository::packageName(long long packageId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT package_name FROM product_package WHERE package_id = ?", {packageId});
    if (rows.empty()) {
        return std::string();
    }
    return rows.at(0).get("package_name");
}



std::vector<PackageLine> PackageRepository::listPackageLines(long long packageId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT pi.product_id, pi.quantity, "
        "       fp.product_name, fp.product_image, fp.unit, fp.price_per_unit, fp.status, "
        "       ROUND(fp.price_per_unit * (1 - COALESCE(pd.discount_percent, 0) / 100), 2) "
        "           AS effective_price, "
        "       COALESCE(fp.quantity, 0) AS stock "
        "FROM package_item pi "
        "JOIN farm_product fp ON fp.product_id = pi.product_id "
        "LEFT JOIN product_discount pd "
        "       ON pd.product_id = fp.product_id AND pd.is_active = 1 "
        "WHERE pi.package_id = ? "
        "ORDER BY pi.package_item_id",
        {packageId});

    std::vector<PackageLine> lines;
    lines.reserve(rows.size());
    for (std::size_t i = 0; i < rows.size(); ++i) {
        const db::Row row = rows.at(i);
        PackageLine line;
        line.productId = row.getInt("product_id");
        line.productName = row.get("product_name");
        line.productImage = row.get("product_image");
        line.unit = row.get("unit");
        line.quantity = row.getInt("quantity");
        line.unitPrice = row.getDouble("effective_price", 0.0);
        line.listPrice = row.getDouble("price_per_unit", 0.0);
        line.availableStock = row.getInt("stock", 0);
        line.approved = (row.get("status") == "approved");
        lines.push_back(line);
    }
    return lines;
}

} // namespace repo
} // namespace agri
