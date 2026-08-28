#include "agrisphere/services/FarmerService.h"

#include "agrisphere/db/JsonMapper.h"

namespace agri {
namespace service {

json::Value FarmerService::submitProduct(const ProductSubmission& submission) {
   
    json::Value errors = json::Value::array();
    if (submission.productName.empty()) errors.push(json::Value("Product name is required"));
    if (submission.categoryId <= 0) errors.push(json::Value("Please select a category"));
    if (submission.quantity <= 0) errors.push(json::Value("Quantity must be greater than 0"));
    if (submission.unit.empty()) errors.push(json::Value("Please select a unit"));
    if (submission.pricePerUnit <= 0) errors.push(json::Value("Price must be greater than 0"));

    if (errors.size() > 0) {
        json::Value out = json::Value::object();
        out.set("success", json::Value(false));
        out.set("errors", errors);
        return out;
    }

    repo::NewProduct product;
    product.farmerId = submission.farmerId;
    product.categoryId = submission.categoryId;
    product.productName = submission.productName;
    product.description = submission.description;
    product.productImage = submission.imageFileName;
    product.unit = submission.unit;
    product.pricePerUnit = submission.pricePerUnit;
    product.quantity = submission.quantity;

    const long long productId = catalog_.createProduct(product);
    if (productId <= 0) {
        json::Value out = json::Value::object();
        out.set("success", json::Value(false));
        out.set("error", json::Value("Failed to add product. Please try again."));
        return out;
    }

    catalog_.upsertInventory(productId, submission.quantity);

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("product_id", json::Value(productId));
    out.set("message",
            json::Value("Product submitted for approval! It will be visible to customers once "
                        "approved by admin."));
    return out;
}

json::Value FarmerService::inventory(long long farmerId) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("products", db::rowsToJson(catalog_.listFarmerProducts(farmerId)));
    return out;
}

json::Value FarmerService::updateProductImage(long long farmerId,
                                              long long productId,
                                              const std::string& imageFileName) {
    json::Value out = json::Value::object();

    if (productId <= 0 || imageFileName.empty()) {
        out.set("success", json::Value(false));
        out.set("error", json::Value("Invalid request"));
        return out;
    }

   
    const auto product = catalog_.findProduct(productId);
    if (!product) {
        out.set("success", json::Value(false));
        out.set("error", json::Value("Product not found"));
        return out;
    }
    if (product->farmerId != farmerId) {
        out.set("success", json::Value(false));
        out.set("error", json::Value("You can only change the photo of your own products."));
        return out;
    }

    
    const std::string previousImage = catalog_.productImageName(productId);

    // The UPDATE still carries farmer_id as defence in depth.
    catalog_.updateProductImageForFarmer(productId, farmerId, imageFileName);

    out.set("success", json::Value(true));
    out.set("product_id", json::Value(productId));
    out.set("product_image", json::Value(imageFileName));
    out.set("previous_image", json::Value(previousImage));
    out.set("message", json::Value("Photo updated for \"" + product->productName + "\""));
    return out;
}

json::Value FarmerService::land(long long farmerId) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("land", db::rowsToJson(catalog_.listFarmerLand(farmerId)));
    return out;
}

json::Value FarmerService::updateInventory(long long farmerId,
                                           long long productId,
                                           double pricePerUnit,
                                           long long quantityAvailable) {
    // The WHERE clause carries farmer_id, so one farmer cannot reprice another
    // farmer's produce.
    catalog_.updateProductPriceForFarmer(productId, farmerId, pricePerUnit);

    if (catalog_.inventoryExists(productId)) {
        catalog_.setInventoryQuantity(productId, quantityAvailable);
    } else {
        catalog_.insertInventory(productId, quantityAvailable);
    }

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("message", json::Value("Product updated successfully!"));
    return out;
}

} // namespace service
} // namespace agri
