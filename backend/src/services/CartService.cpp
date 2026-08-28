#include "agrisphere/services/CartService.h"

#include "agrisphere/db/JsonMapper.h"

namespace agri {
namespace service {
namespace {

json::Value failure(const std::string& message) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(false));
    out.set("error", json::Value(message));
    return out;
}

json::Value ok(const std::string& message, long long cartCount) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("message", json::Value(message));
    out.set("cartCount", json::Value(cartCount));
    return out;
}

} // namespace

json::Value CartService::list(long long customerId) {
    const db::ResultSet rows = cart_.listDetailed(customerId);

    // Totals use effective_price so an active discount is reflected in the
    // cart. price_per_unit stays the farmer's list price for display.
    double subtotal = 0.0;
    long long totalItems = 0;
    for (std::size_t i = 0; i < rows.size(); ++i) {
        const db::Row row = rows.at(i);
        const long long quantity = row.getInt("quantity", 0);
        subtotal += row.getDouble("effective_price", 0.0) * static_cast<double>(quantity);
        totalItems += quantity;
    }

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("items", db::rowsToJson(rows));
    out.set("subtotal", json::Value(subtotal));
    out.set("total_items", json::Value(totalItems));
    return out;
}

json::Value CartService::count(long long customerId) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("count", json::Value(cart_.totalQuantity(customerId)));
    return out;
}

json::Value CartService::add(long long customerId, long long productId, long long quantity) {
    if (productId <= 0 || quantity <= 0) {
        return failure("Invalid product or quantity");
    }
    if (!catalog_.isApprovedProduct(productId)) {
        return failure("Product not available");
    }
    if (!cart_.customerExists(customerId)) {
        return failure("Your session is no longer valid. Please login again.");
    }

    const auto existing = cart_.findLine(customerId, productId);
    if (existing) {
        cart_.setQuantityById(existing->cartId, existing->quantity + quantity);
    } else {
        cart_.insertLine(customerId, productId, quantity);
    }

    return ok("Product added to cart", cart_.totalQuantity(customerId));
}

json::Value CartService::update(long long customerId, long long cartId, long long quantity) {
    if (cartId <= 0 || quantity <= 0) {
        return failure("Invalid request");
    }
    cart_.setQuantityForCustomer(cartId, customerId, quantity);
    return ok("Cart updated", cart_.totalQuantity(customerId));
}

json::Value CartService::remove(long long customerId, long long cartId) {
    if (cartId <= 0) {
        return failure("Invalid request");
    }
    cart_.removeLine(cartId, customerId);
    return ok("Item removed from cart", cart_.totalQuantity(customerId));
}

json::Value CartService::clear(long long customerId) {
    cart_.clear(customerId);
    return ok("Cart cleared", 0);
}

} // namespace service
} // namespace agri
