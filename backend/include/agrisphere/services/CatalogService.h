// AgriSphere C++ Backend - product catalogue use cases (browse, search,
// categories, government reference prices).
#pragma once

#include "agrisphere/core/Json.h"
#include "agrisphere/repositories/CatalogRepository.h"

#include <string>

namespace agri {
namespace service {

class CatalogService {
public:
    json::Value categories();
    json::Value categoryName(long long categoryId);

    json::Value browse(long long categoryId, const std::string& search, const std::string& sort);

    // Mirrors the response shape of the old api/get_govt_price.php endpoint.
    json::Value governmentPrices(const std::string& productName, long long categoryId);

private:
    repo::CatalogRepository catalog_;
};

} // namespace service
} // namespace agri
