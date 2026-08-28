// AgriSphere C++ Backend - administrator use cases (product moderation, order
// and payment status, blocking users).
#pragma once

#include "agrisphere/core/Json.h"
#include "agrisphere/repositories/CatalogRepository.h"
#include "agrisphere/repositories/OrderRepository.h"
#include "agrisphere/repositories/UserRepository.h"

#include <string>

namespace agri {
namespace service {

class AdminService {
public:
    // action: approve | reject | pending
    json::Value moderateProduct(long long adminId,
                                long long productId,
                                const std::string& action,
                                const std::string& rejectionReason);

    // Replaces the photo of ANY product, whichever farmer owns it. The upload
    // itself stays in PHP; only the resulting file name arrives here.
    json::Value updateProductImage(long long productId, const std::string& imageFileName);

    json::Value updateOrderStatus(long long adminId, long long orderId, const std::string& status);

    json::Value updatePaymentStatus(long long orderId, const std::string& paymentStatus);

    // admin/user_action.php: block sets is_blocked = 1, unblock sets it to 0.
    json::Value setUserBlocked(long long userId, bool blocked);

    // admin/block_action.php: unblocking there deletes the row instead.
    json::Value toggleUserBlockRecord(long long userId, bool blocked);

private:
    repo::CatalogRepository catalog_;
    repo::OrderRepository orders_;
    repo::UserRepository users_;
};

} // namespace service
} // namespace agri
