// AgriSphere C++ Backend - data access for the `product_discount` table.
//
// The farmer's list price in farm_product.price_per_unit is never modified.
// A discount is stored here as a percentage, and the price a customer pays is
// calculated when products are read.
#pragma once

#include "agrisphere/db/ResultSet.h"

#include <optional>
#include <string>

namespace agri {
namespace repo {

// What a farmer is allowed to discount, plus the product details the
// notification message needs.
struct DiscountableProduct {
    long long productId = 0;
    long long farmerId = 0;
    std::string productName;
    std::string status;         // pending / approved / rejected
    double pricePerUnit = 0.0;
};

class DiscountRepository {
public:
    // The product together with its owner and price, used for validation.
    std::optional<DiscountableProduct> findProductForFarmer(long long productId);

    // Creates the discount, or updates and re-activates an existing one.
    void activate(long long productId, long long farmerId, double discountPercent);

    // Keeps the row for history but turns the discount off.
    long long deactivate(long long productId, long long farmerId);

    double activeDiscountPercent(long long productId);

    // Every product of one farmer with its discount state and the price a
    // customer currently pays - this is what the farmer's discount page shows.
    db::ResultSet listFarmerProductsWithDiscount(long long farmerId);
};

} // namespace repo
} // namespace agri
