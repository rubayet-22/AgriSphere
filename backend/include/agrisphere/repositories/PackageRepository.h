// AgriSphere C++ Backend - data access for `product_package` and `package_item`.
//
// A package is only a list of EXISTING products: package_item.product_id is a
// foreign key into farm_product. This repository therefore never stores a
// price, a stock level or a product name of its own - it reads them from the
// product tables every time, exactly like the cart does.
//
// Like every other repository it reaches MySQL only through the Singleton
// DatabaseManager::getInstance().
#pragma once

#include "agrisphere/db/DatabaseManager.h"
#include "agrisphere/db/ResultSet.h"

#include <string>
#include <vector>

namespace agri {
namespace repo {

struct PackageSummary {
    long long packageId = 0;
    std::string packageName;
    std::string description;
};

// One line of a package, joined with the product it points at. `unitPrice` is
// the discounted price when the farmer has an active discount, so a package
// total always matches what the cart will charge.
struct PackageLine {
    long long productId = 0;
    std::string productName;
    std::string productImage;     // farm_product.product_image, "" when unset
    std::string unit;
    long long quantity = 0;       // how many units the package includes
    double unitPrice = 0.0;       // effective_price (farmer discount applied)
    double listPrice = 0.0;       // farm_product.price_per_unit
    long long availableStock = 0;
    bool approved = false;
};

class PackageRepository {
public:
    // Every active package, for the customer's Packages page.
    std::vector<PackageSummary> listActivePackages();

    bool packageExists(long long packageId);
    std::string packageName(long long packageId);

    // The contents of one package, joined with live product data. An empty
    // result means the package has no items.
    std::vector<PackageLine> listPackageLines(long long packageId);
};

} // namespace repo
} // namespace agri
