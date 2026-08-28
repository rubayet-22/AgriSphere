#include "agrisphere/services/OrderService.h"

#include "agrisphere/core/Logger.h"
#include "agrisphere/core/Strings.h"
#include "agrisphere/db/DatabaseManager.h"
#include "agrisphere/db/JsonMapper.h"
#include "agrisphere/payment/PaymentCreator.h"

#include <memory>
#include <vector>

namespace agri {
namespace service {
namespace {

struct LineItem {
    long long productId = 0;
    long long quantity = 0;
    
    double pricePerUnit = 0.0;
    std::string productName;
};

json::Value failure(const std::string& message) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(false));
    out.set("error", json::Value(message));
    return out;
}

std::vector<LineItem> toLineItems(const db::ResultSet& rows) {
    std::vector<LineItem> items;
    items.reserve(rows.size());
    for (std::size_t i = 0; i < rows.size(); ++i) {
        const db::Row row = rows.at(i);
        LineItem item;
        item.productId = row.getInt("product_id");
        item.quantity = row.getInt("quantity");
        item.pricePerUnit = row.getDouble("effective_price");
        item.productName = row.get("product_name");
        items.push_back(item);
    }
    return items;
}

} // namespace


strategy::DiscountContext OrderService::buildDiscountContext(long long customerId,
                                                             long long totalItems) {
    strategy::CustomerProfile profile;
    profile.isPremium = memberships_.isPremium(customerId);  // Approved + not expired
    profile.totalItems = totalItems;

    strategy::DiscountContext context;
    context.selectStrategy(profile);
    return context;
}

json::Value OrderService::checkoutItems(long long customerId) {
    const db::ResultSet rows = cart_.listForCheckout(customerId);

    // effective_price already includes any active FARMER product discount.
    double subtotal = 0.0;
    long long totalItems = 0;
    for (std::size_t i = 0; i < rows.size(); ++i) {
        const db::Row row = rows.at(i);
        const long long quantity = row.getInt("quantity", 0);
        subtotal += row.getDouble("effective_price", 0.0) * static_cast<double>(quantity);
        totalItems += quantity;
    }

    // The customer discount is applied on top of that subtotal, so the farmer
    // discount is counted once and only once.
    const strategy::DiscountContext discounts = buildDiscountContext(customerId, totalItems);
    const double customerDiscount = discounts.calculateDiscount(subtotal);

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("items", db::rowsToJson(rows));
    out.set("subtotal", json::Value(subtotal));
    out.set("total_items", json::Value(totalItems));
    out.set("discount_strategy", json::Value(discounts.strategyName()));
    out.set("discount_percent", json::Value(discounts.discountPercent()));
    out.set("discount_label", json::Value(discounts.description()));
    out.set("customer_discount", json::Value(customerDiscount));
    out.set("payable", json::Value(subtotal - customerDiscount));
    return out;
}

json::Value OrderService::placeOrder(const CheckoutRequest& request) {
    if (request.deliveryAddress.empty() || request.deliveryPhone.empty()) {
        return failure("Please fill in all delivery information");
    }

   
    const std::unique_ptr<payment::PaymentCreator> creator =
        payment::PaymentCreator::forMethod(request.paymentMethod);
    if (!creator) {
        return failure("Unsupported payment method");
    }
    const std::unique_ptr<payment::PaymentMethod> method = creator->createPayment();

    const std::vector<LineItem> items = toLineItems(cart_.listForCheckout(request.customerId));
    if (items.empty()) {
        return failure("Your cart is empty");
    }

    // Strategy pattern: pick the single customer discount rule for this order
    // (Regular 0% / Premium 5% / Bulk 10%) before the transaction starts.
    long long totalItems = 0;
    for (const LineItem& item : items) {
        totalItems += item.quantity;
    }
    const strategy::DiscountContext discounts =
        buildDiscountContext(request.customerId, totalItems);

    try {
        db::PooledConnection connection = db::DatabaseManager::getInstance().acquire();
        db::Transaction transaction(connection);

        double subtotal = 0.0;
        for (const LineItem& item : items) {
            const auto stock = orders_.lockAvailableStock(connection, item.productId);
            if (!stock || *stock < item.quantity) {
                return failure("Checkout failed: Not enough stock for " + item.productName);
            }
            subtotal += item.pricePerUnit * static_cast<double>(item.quantity);
        }

        
        const double customerDiscount = discounts.calculateDiscount(subtotal);
        const double payable = subtotal - customerDiscount;

        payment::PaymentInput paymentInput;
        paymentInput.orderTotal = payable;
        paymentInput.walletNumber = request.bkashNumber;
        paymentInput.cardNumber = request.cardNumber;
        paymentInput.cardExpiry = request.cardExpiry;
        paymentInput.cardCvc = request.cardCvc;

        const payment::ValidationOutcome outcome = method->validate(paymentInput);
        if (!outcome.ok) {
            return failure(outcome.errorMessage);
        }
        const double fee = method->calculateFee(payable);
        const std::string reference = method->generateReference();
        const std::string paymentStatus = method->resolveStatus();
        const double total = payable + fee;

        Logger::info("Checkout for customer " + std::to_string(request.customerId) + ": " +
                     discounts.strategyName() + " strategy (" +
                     std::to_string(static_cast<int>(discounts.discountPercent())) + "%), " +
                     std::to_string(totalItems) + " item(s)");

        const long long orderId =
            orders_.createOrder(connection, request.customerId, total, "Processing");

        for (const LineItem& item : items) {
            orders_.addOrderItem(connection, orderId, item.productId, item.quantity,
                                 item.pricePerUnit);
            orders_.decreaseProductQuantity(connection, item.productId, item.quantity);
            orders_.decreaseInventory(connection, item.productId, item.quantity);
        }

        orders_.insertPayment(connection, orderId, request.paymentMethod,
                              outcome.normalizedAccount, paymentStatus, fee, reference);
        orders_.upsertCustomerAddress(connection, request.customerId, request.deliveryAddress,
                                      request.deliveryPhone);
        cart_.clear(connection, request.customerId);

        transaction.commit();

        std::string message = "Order placed successfully! Order ID: #" + std::to_string(orderId);
        if (customerDiscount > 0.0) {
            message += " (" + discounts.strategyName() + " discount " +
                       std::to_string(static_cast<int>(discounts.discountPercent())) + "% applied)";
        }

        json::Value out = json::Value::object();
        out.set("success", json::Value(true));
        out.set("order_id", json::Value(orderId));
        out.set("total", json::Value(total));
        out.set("subtotal", json::Value(subtotal));
        out.set("discount_strategy", json::Value(discounts.strategyName()));
        out.set("discount_percent", json::Value(discounts.discountPercent()));
        out.set("customer_discount", json::Value(customerDiscount));
        out.set("payable", json::Value(payable));
        out.set("fee", json::Value(fee));
        out.set("message", json::Value(message));
        return out;
    } catch (const std::exception& error) {
        // The Transaction guard has already rolled back.
        return failure(std::string("Checkout failed: ") + error.what());
    }
}

json::Value OrderService::placeOrderDirect(const DirectOrderRequest& request) {
    if (request.name.empty() || request.phone.empty() || request.address.empty() ||
        request.paymentMethod.empty()) {
        return failure("Please fill in all required fields");
    }

   
    const std::unique_ptr<payment::PaymentCreator> creator =
        payment::PaymentCreator::forMethod(request.paymentMethod);
    if (!creator) {
        return failure("Unsupported payment method");
    }
    const std::unique_ptr<payment::PaymentMethod> method = creator->createPayment();

    const std::vector<LineItem> items = toLineItems(cart_.listForOrder(request.customerId));
    if (items.empty()) {
        return failure("Your cart is empty");
    }

    double subtotal = 0.0;
    long long totalItems = 0;
    for (const LineItem& item : items) {
        subtotal += item.pricePerUnit * static_cast<double>(item.quantity);
        totalItems += item.quantity;
    }

  
    const strategy::DiscountContext discounts =
        buildDiscountContext(request.customerId, totalItems);
    const double customerDiscount = discounts.calculateDiscount(subtotal);
    const double payable = subtotal - customerDiscount;

    payment::PaymentInput paymentInput;
    paymentInput.orderTotal = payable;
    paymentInput.walletNumber = request.bkashNumber;
    paymentInput.cardNumber = request.cardNumber;
    paymentInput.cardExpiry = request.cardExpiry;
    paymentInput.cardCvc = request.cardCvc;

    const payment::ValidationOutcome outcome = method->validate(paymentInput);
    if (!outcome.ok) {
        return failure(outcome.errorMessage);
    }
    const double fee = method->calculateFee(payable);
    const std::string reference = method->generateReference();
    const std::string paymentStatus = method->resolveStatus();
    const double total = payable + fee;

    try {
        db::PooledConnection connection = db::DatabaseManager::getInstance().acquire();
        db::Transaction transaction(connection);

        const long long orderId =
            orders_.createOrder(connection, request.customerId, total, "Pending");

        for (const LineItem& item : items) {
            orders_.addOrderItem(connection, orderId, item.productId, item.quantity,
                                 item.pricePerUnit);
            orders_.decreaseInventory(connection, item.productId, item.quantity);
        }

        orders_.insertPaymentWithTime(connection, orderId, request.paymentMethod,
                                      outcome.normalizedAccount, paymentStatus, fee, reference);
        cart_.clear(connection, request.customerId);

        transaction.commit();

        json::Value out = json::Value::object();
        out.set("success", json::Value(true));
        out.set("order_id", json::Value(orderId));
        out.set("total", json::Value(total));
        out.set("subtotal", json::Value(subtotal));
        out.set("discount_strategy", json::Value(discounts.strategyName()));
        out.set("discount_percent", json::Value(discounts.discountPercent()));
        out.set("customer_discount", json::Value(customerDiscount));
        out.set("payable", json::Value(payable));
        out.set("fee", json::Value(fee));
        out.set("message", json::Value("Order placed successfully! Your order number is #" +
                                       std::to_string(orderId)));
        return out;
    } catch (const std::exception&) {
        return failure("Failed to place order. Please try again.");
    }
}

} // namespace service
} // namespace agri
