#include "agrisphere/services/CatalogService.h"

#include "agrisphere/db/JsonMapper.h"

namespace agri {
namespace service {

json::Value CatalogService::categories() {
    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("categories", db::rowsToJson(catalog_.listCategories()));
    return out;
}

json::Value CatalogService::categoryName(long long categoryId) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("category_name", json::Value(catalog_.categoryName(categoryId)));
    return out;
}

json::Value CatalogService::browse(long long categoryId,
                                   const std::string& search,
                                   const std::string& sort) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("products", db::rowsToJson(catalog_.browseProducts(categoryId, search, sort)));
    return out;
}

json::Value CatalogService::governmentPrices(const std::string& productName, long long categoryId) {
    json::Value out = json::Value::object();

    if (productName.empty() && categoryId <= 0) {
        out.set("error", json::Value("Product name or category required"));
        return out;
    }

    if (!productName.empty()) {
        const db::ResultSet rows = catalog_.searchGovernmentPrices(productName);
        if (rows.empty()) {
            out.set("success", json::Value(false));
            out.set("message", json::Value("No government price found for this product"));
            return out;
        }
        out.set("success", json::Value(true));
        out.set("prices", db::rowsToJson(rows));
        out.set("match", db::rowToJson(rows.at(0)));
        return out;
    }

    out.set("success", json::Value(true));
    out.set("prices", db::rowsToJson(catalog_.governmentPricesForCategory(categoryId)));
    return out;
}

} // namespace service
} // namespace agri
