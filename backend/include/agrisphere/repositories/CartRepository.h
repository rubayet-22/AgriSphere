// AgriSphere C++ Backend - data access for `customer_cart`.
#pragma once

#include "agrisphere/db/DatabaseManager.h"
#include "agrisphere/db/ResultSet.h"

#include <optional>

namespace agri {
namespace repo {

struct CartLine {
    long long cartId = 0;
    long long quantity = 0;
};

class CartRepository {
public:
    // customer_cart.customer_id is a foreign key onto `customer`. A stale PHP
    // session can still carry a user id whose customer row is gone, so the
    // insert is guarded by this check rather than letting MySQL raise 1452.
    bool customerExists(long long customerId);

    // Cart page listing (customer/cart.php).
    db::ResultSet listDetailed(long long customerId);

    // Checkout listing, which also exposes the available stock
    // (customer/checkout.php).
    db::ResultSet listForCheckout(long long customerId);

    // Order placement listing (customer/process_order.php).
    db::ResultSet listForOrder(long long customerId);

    long long totalQuantity(long long customerId);

    std::optional<CartLine> findLine(long long customerId, long long productId);
    void insertLine(long long customerId, long long productId, long long quantity);
    void setQuantityById(long long cartId, long long quantity);
    void setQuantityForCustomer(long long cartId, long long customerId, long long quantity);
    void removeLine(long long cartId, long long customerId);
    void clear(long long customerId);

    // Transactional variant used while placing an order.
    void clear(db::PooledConnection& connection, long long customerId);

    // Transactional variants of the three write helpers above, used by the
    // Facade so a whole package is added as one unit: every line lands or
    // none does. They run identical SQL on a caller-supplied connection.
    std::optional<CartLine> findLine(db::PooledConnection& connection,
                                     long long customerId,
                                     long long productId);
    void insertLine(db::PooledConnection& connection,
                    long long customerId,
                    long long productId,
                    long long quantity);
    void setQuantityById(db::PooledConnection& connection, long long cartId, long long quantity);
};

} // namespace repo
} // namespace agri
