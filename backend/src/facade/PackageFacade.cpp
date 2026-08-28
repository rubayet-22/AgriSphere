#include "agrisphere/facade/PackageFacade.h"

#include "agrisphere/core/Logger.h"
#include "agrisphere/db/DatabaseManager.h"

#include <string>
#include <vector>

namespace agri {
namespace facade {
namespace {

json::Value failure(const std::string& message) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(false));
    out.set("error", json::Value(message));
    return out;
}

// One package item as the Packages page wants to render it.
json::Value lineToJson(const repo::PackageLine& line) {
    json::Value item = json::Value::object();
    item.set("product_id", json::Value(line.productId));
    item.set("product_name", json::Value(line.productName));
    item.set("product_image", json::Value(line.productImage));
    item.set("unit", json::Value(line.unit));
    item.set("quantity", json::Value(line.quantity));
    item.set("unit_price", json::Value(line.unitPrice));
    item.set("list_price", json::Value(line.listPrice));
    item.set("line_total", json::Value(line.unitPrice * static_cast<double>(line.quantity)));
    item.set("available_stock", json::Value(line.availableStock));
    item.set("in_stock", json::Value(line.approved && line.availableStock >= line.quantity));
    return item;
}

} // namespace

//  Read side: what the Packages page shows
json::Value PackageFacade::listPackages() {
    json::Value packages = json::Value::array();

    for (const repo::PackageSummary& summary : packages_.listActivePackages()) {
        const std::vector<repo::PackageLine> lines = packages_.listPackageLines(summary.packageId);

        json::Value items = json::Value::array();
        double total = 0.0;
        long long totalUnits = 0;
        bool available = !lines.empty();

        for (const repo::PackageLine& line : lines) {
            items.push(lineToJson(line));
            total += line.unitPrice * static_cast<double>(line.quantity);
            totalUnits += line.quantity;
            if (!line.approved || line.availableStock < line.quantity) {
                available = false;
            }
        }

        json::Value package = json::Value::object();
        package.set("package_id", json::Value(summary.packageId));
        package.set("package_name", json::Value(summary.packageName));
        package.set("description", json::Value(summary.description));
        package.set("items", items);
        package.set("item_count", json::Value(static_cast<long long>(lines.size())));
        package.set("total_units", json::Value(totalUnits));
        package.set("total", json::Value(total));
        package.set("available", json::Value(available));
        packages.push(package);
    }

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("packages", packages);
    return out;
}


json::Value PackageFacade::addPackageToCart(long long customerId, long long packageId) {
    if (customerId <= 0 || packageId <= 0) {
        return failure("Invalid request");
    }
    if (!cart_.customerExists(customerId)) {
        return failure("Your session is no longer valid. Please login again.");
    }
    if (!packages_.packageExists(packageId)) {
        return failure("That package is not available.");
    }

    const std::vector<repo::PackageLine> lines = packages_.listPackageLines(packageId);
    if (lines.empty()) {
        return failure("That package is empty.");
    }

    
    for (const repo::PackageLine& line : lines) {
        if (!catalog_.isApprovedProduct(line.productId)) {
            return failure("\"" + line.productName +
                           "\" is no longer available, so the package was not added.");
        }

        
        const auto existing = cart_.findLine(customerId, line.productId);
        const long long alreadyInCart = existing ? existing->quantity : 0;
        const long long required = alreadyInCart + line.quantity;

        if (line.availableStock < required) {
            return failure("Not enough stock for \"" + line.productName + "\" (" +
                           std::to_string(line.availableStock) + " left, package needs " +
                           std::to_string(required) +
                           "). The package was not added.");
        }
    }

    
    try {
        db::PooledConnection connection = db::DatabaseManager::getInstance().acquire();
        db::Transaction transaction(connection);

        double total = 0.0;
        long long addedUnits = 0;

        for (const repo::PackageLine& line : lines) {
            const auto existing = cart_.findLine(connection, customerId, line.productId);
            if (existing) {
                cart_.setQuantityById(connection, existing->cartId,
                                      existing->quantity + line.quantity);
            } else {
                cart_.insertLine(connection, customerId, line.productId, line.quantity);
            }
            total += line.unitPrice * static_cast<double>(line.quantity);
            addedUnits += line.quantity;
        }

        transaction.commit();

        const std::string name = packages_.packageName(packageId);
        Logger::info("[package] \"" + name + "\" added to the cart of customer " +
                     std::to_string(customerId) + " - " + std::to_string(lines.size()) +
                     " product(s), " + std::to_string(addedUnits) + " unit(s)");

        json::Value out = json::Value::object();
        out.set("success", json::Value(true));
        out.set("package_id", json::Value(packageId));
        out.set("package_name", json::Value(name));
        out.set("products_added", json::Value(static_cast<long long>(lines.size())));
        out.set("units_added", json::Value(addedUnits));
        out.set("package_total", json::Value(total));
        out.set("cartCount", json::Value(cart_.totalQuantity(customerId)));
        out.set("message", json::Value("\"" + name + "\" added to your cart (" +
                                       std::to_string(lines.size()) + " products)."));
        return out;
    } catch (const std::exception& error) {
        
        return failure(std::string("Could not add the package: ") + error.what());
    }
}

} 
} 
