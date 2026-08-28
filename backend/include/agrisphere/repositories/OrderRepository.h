// AgriSphere C++ Backend - data access for `customer_order`, `order_items`
// and `payment`.
//
// The write side takes an explicit PooledConnection because placing an order
// must run as one transaction on one connection.
#pragma once

#include "agrisphere/db/DatabaseManager.h"
#include "agrisphere/db/ResultSet.h"

#include <optional>
#include <string>

namespace agri {
namespace repo {

struct OrderPaymentInfo {
    long long orderId = 0;
    std::string status;
    std::string paymentMethod;
    std::string paymentStatus;
};

class OrderRepository {
public:
    // --- placement (transactional) -----------------------------------------
    long long createOrder(db::PooledConnection& connection,
                          long long customerId,
                          double totalAmount,
                          const std::string& status);

    void addOrderItem(db::PooledConnection& connection,
                      long long orderId,
                      long long productId,
                      long long quantity,
                      double pricePerUnit);

    // SELECT ... FOR UPDATE, mirroring the previous checkout logic.
    std::optional<long long> lockAvailableStock(db::PooledConnection& connection,
                                                long long productId);

    void decreaseProductQuantity(db::PooledConnection& connection,
                                 long long productId,
                                 long long quantity);
    void decreaseInventory(db::PooledConnection& connection,
                           long long productId,
                           long long quantity);

    void insertPayment(db::PooledConnection& connection,
                       long long orderId,
                       const std::string& method,
                       const std::string& accountNumber,
                       const std::string& status,
                       double fee,
                       const std::string& referenceCode);

    void insertPaymentWithTime(db::PooledConnection& connection,
                               long long orderId,
                               const std::string& method,
                               const std::string& accountNumber,
                               const std::string& status,
                               double fee,
                               const std::string& referenceCode);

    void upsertCustomerAddress(db::PooledConnection& connection,
                               long long customerId,
                               const std::string& address,
                               const std::string& phone);

    // --- admin side ---------------------------------------------------------
    std::optional<OrderPaymentInfo> findOrderWithPayment(long long orderId);

    // `timestampColumn` must be one of the whitelisted status columns or empty.
    void updateStatus(long long orderId,
                      const std::string& status,
                      const std::string& timestampColumn,
                      long long adminId);

    bool paymentExists(long long orderId);
    void markPaymentPaid(long long orderId);
    void updatePaymentStatus(long long orderId, const std::string& status, bool touchTime);
    void insertUnknownPayment(long long orderId, const std::string& status);
};

} // namespace repo
} // namespace agri
