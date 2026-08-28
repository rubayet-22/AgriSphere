// AgriSphere C++ Backend - data access for `category`, `farm_product`,
// `farm_inventory` and `government_prices`.
#pragma once

#include "agrisphere/db/ResultSet.h"

#include <optional>
#include <string>

namespace agri {
namespace repo {

struct ProductSummary {
    long long productId = 0;
    long long farmerId = 0;
    std::string productName;
};

struct NewProduct {
    long long farmerId = 0;
    long long categoryId = 0;
    std::string productName;
    std::string description;
    std::optional<std::string> productImage; // absent -> NULL, as before
    std::string unit;
    double pricePerUnit = 0.0;
    long long quantity = 0;
};

class CatalogRepository {
public:
    // --- categories ---------------------------------------------------------
    db::ResultSet listCategories();
    std::string categoryName(long long categoryId);

    // --- customer facing catalogue -----------------------------------------
    // `sort` accepts price_low | price_high | name | newest (anything else).
    db::ResultSet browseProducts(long long categoryId,
                                 const std::string& search,
                                 const std::string& sort);

    bool isApprovedProduct(long long productId);
    std::optional<ProductSummary> findProduct(long long productId);

    // --- farmer facing ------------------------------------------------------
    db::ResultSet listFarmerProducts(long long farmerId);
    long long createProduct(const NewProduct& product);
    void upsertInventory(long long productId, long long quantity);
    bool inventoryExists(long long productId);
    void setInventoryQuantity(long long productId, long long quantity);
    void insertInventory(long long productId, long long quantity);
    void updateProductPriceForFarmer(long long productId, long long farmerId, double price);

    // Product photo. The upload itself stays in PHP (move_uploaded_file); only
    // the resulting file name is stored here.
    //
    // The farmer variant carries farmer_id in the WHERE clause, so one farmer
    // cannot replace another farmer's photo. It returns the number of rows
    // changed, which is 0 when the product is not theirs. The admin variant is
    // deliberately unscoped - an administrator may fix any product's photo.
    long long updateProductImageForFarmer(long long productId,
                                          long long farmerId,
                                          const std::string& imageFileName);
    long long updateProductImage(long long productId, const std::string& imageFileName);

    // Previous file name, so a replaced photo can be deleted from disk.
    std::string productImageName(long long productId);

    // Land holdings registered against a farmer (farmer/land_info.php).
    db::ResultSet listFarmerLand(long long farmerId);

    // --- admin moderation ---------------------------------------------------
    void approveProduct(long long productId, long long adminId);
    void rejectProduct(long long productId, const std::string& reason);
    void resetProductToPending(long long productId);

    // --- government reference prices ---------------------------------------
    db::ResultSet searchGovernmentPrices(const std::string& productName);
    db::ResultSet governmentPricesForCategory(long long categoryId);
};

} // namespace repo
} // namespace agri
