// AgriSphere C++ Backend - farmer side use cases (listing produce, inventory).
#pragma once

#include "agrisphere/core/Json.h"
#include "agrisphere/repositories/CatalogRepository.h"

#include <optional>
#include <string>

namespace agri {
namespace service {

struct ProductSubmission {
    long long farmerId = 0;
    std::string productName;
    long long categoryId = 0;
    std::string description;
    long long quantity = 0;
    std::string unit;
    double pricePerUnit = 0.0;
    // The upload itself stays in PHP (move_uploaded_file); only the resulting
    // file name is persisted here.
    std::optional<std::string> imageFileName;
};

class FarmerService {
public:
    json::Value submitProduct(const ProductSubmission& submission);

    json::Value inventory(long long farmerId);

    json::Value updateInventory(long long farmerId,
                                long long productId,
                                double pricePerUnit,
                                long long quantityAvailable);

    // Replaces the photo of one of the farmer's own products. `imageFileName`
    // is the file PHP already moved into uploads/products/. Rejects a product
    // that belongs to another farmer.
    json::Value updateProductImage(long long farmerId,
                                   long long productId,
                                   const std::string& imageFileName);

    json::Value land(long long farmerId);

private:
    repo::CatalogRepository catalog_;
};

} // namespace service
} // namespace agri
