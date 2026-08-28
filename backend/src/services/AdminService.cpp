#include "agrisphere/services/AdminService.h"

#include "agrisphere/core/Strings.h"

namespace agri {
namespace service {
namespace {

json::Value failure(const std::string& message, const std::string& kind = std::string()) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(false));
    out.set("error", json::Value(message));
    if (!kind.empty()) {
        out.set("error_kind", json::Value(kind));
    }
    return out;
}

json::Value success(const std::string& message) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("message", json::Value(message));
    return out;
}

std::string timestampColumnFor(const std::string& status) {
    if (status == "Processing") return "processed_at";
    if (status == "Shipped") return "shipped_at";
    if (status == "Delivered") return "delivered_at";
    if (status == "Cancelled") return "cancelled_at";
    return std::string();
}

bool isValidOrderStatus(const std::string& status) {
    return status == "Pending" || status == "Processing" || status == "Shipped" ||
           status == "Delivered" || status == "Cancelled";
}

bool isValidPaymentStatus(const std::string& status) {
    return status == "Pending" || status == "Paid" || status == "Failed";
}

} // namespace

json::Value AdminService::moderateProduct(long long adminId,
                                          long long productId,
                                          const std::string& action,
                                          const std::string& rejectionReason) {
    if (productId <= 0 || (action != "approve" && action != "reject" && action != "pending")) {
        return failure("Invalid request");
    }

    const auto product = catalog_.findProduct(productId);
    if (!product) {
        return failure("Product not found");
    }

    if (action == "approve") {
        catalog_.approveProduct(productId, adminId);
        return success("Product \"" + product->productName + "\" has been approved");
    }

    if (action == "reject") {
        if (rejectionReason.empty()) {
            // The old handler bounced back to the review screen for this case.
            return failure("Please provide a reason for rejection", "needs_reason");
        }
        catalog_.rejectProduct(productId, rejectionReason);
        return success("Product \"" + product->productName + "\" has been rejected");
    }

    catalog_.resetProductToPending(productId);
    return success("Product \"" + product->productName + "\" has been set to pending");
}

json::Value AdminService::updateProductImage(long long productId,
                                             const std::string& imageFileName) {
    if (productId <= 0 || imageFileName.empty()) {
        return failure("Invalid request");
    }

    const auto product = catalog_.findProduct(productId);
    if (!product) {
        return failure("Product not found");
    }

    // The photo being replaced, so PHP can delete the old file.
    const std::string previousImage = catalog_.productImageName(productId);
    catalog_.updateProductImage(productId, imageFileName);

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("product_id", json::Value(productId));
    out.set("product_image", json::Value(imageFileName));
    out.set("previous_image", json::Value(previousImage));
    out.set("message", json::Value("Photo updated for \"" + product->productName + "\""));
    return out;
}

json::Value AdminService::updateOrderStatus(long long adminId,
                                            long long orderId,
                                            const std::string& status) {
    if (orderId <= 0) {
        return failure("Invalid request");
    }
    if (!isValidOrderStatus(status)) {
        return failure("Invalid status");
    }

    const auto order = orders_.findOrderWithPayment(orderId);
    if (!order) {
        return failure("Order not found");
    }

    orders_.updateStatus(orderId, status, timestampColumnFor(status), adminId);

    if (status == "Delivered") {
        const std::string method = strings::toLower(order->paymentMethod);
        if (method == "cod" || method == "cash on delivery") {
            orders_.markPaymentPaid(orderId);
            return success("Order #" + std::to_string(orderId) +
                           " marked as Delivered. Payment status auto-updated to Paid (COD).");
        }
    }

    return success("Order #" + std::to_string(orderId) + " status updated to " + status);
}

json::Value AdminService::updatePaymentStatus(long long orderId,
                                              const std::string& paymentStatus) {
    if (orderId <= 0) {
        return failure("Invalid request");
    }
    if (!isValidPaymentStatus(paymentStatus)) {
        return failure("Invalid payment status");
    }

    const auto order = orders_.findOrderWithPayment(orderId);
    if (!order) {
        return failure("Order not found");
    }

    if (orders_.paymentExists(orderId)) {
        orders_.updatePaymentStatus(orderId, paymentStatus, paymentStatus == "Paid");
    } else {
        orders_.insertUnknownPayment(orderId, paymentStatus);
    }

    return success("Order #" + std::to_string(orderId) + " payment status updated to " +
                   paymentStatus);
}

json::Value AdminService::setUserBlocked(long long userId, bool blocked) {
    if (userId <= 0) {
        return failure("Invalid request");
    }

    const auto user = users_.findById(userId);
    if (!user) {
        return failure("User not found");
    }
    if (user->userType == "Admin") {
        return failure("Cannot block admin users");
    }

    if (blocked) {
        users_.block(userId);
        return success("User has been blocked successfully");
    }
    users_.unblock(userId);
    return success("User has been unblocked successfully");
}

json::Value AdminService::toggleUserBlockRecord(long long userId, bool blocked) {
    if (userId <= 0) {
        return failure("Invalid request");
    }
    if (blocked) {
        users_.block(userId);
        return success("User has been blocked");
    }
    users_.deleteBlockRecord(userId);
    return success("User has been unblocked");
}

} // namespace service
} // namespace agri
