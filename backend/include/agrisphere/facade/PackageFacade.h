
#pragma once

#include "agrisphere/core/Json.h"
#include "agrisphere/repositories/CartRepository.h"
#include "agrisphere/repositories/CatalogRepository.h"
#include "agrisphere/repositories/OrderRepository.h"
#include "agrisphere/repositories/PackageRepository.h"

namespace agri {
namespace facade {

class PackageFacade {
public:
    // Every active package with its items, unit prices and total, for
    // customer/packages.php.
    json::Value listPackages();

    // THE one operation the whole pattern exists for. Either every product in
    // the package is added, or none is and the reason is returned.
    json::Value addPackageToCart(long long customerId, long long packageId);

private:
    repo::PackageRepository packages_;  // package contents  (new)
    repo::CatalogRepository catalog_;   // product validity  (existing)
    repo::CartRepository cart_;         // cart writes       (existing)
    repo::OrderRepository orders_;      // stock lock        (existing)
};

} 
} 
