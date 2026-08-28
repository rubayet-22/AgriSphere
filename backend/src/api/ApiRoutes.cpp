#include "agrisphere/api/ApiRoutes.h"

#include "agrisphere/db/DatabaseManager.h"
#include "agrisphere/facade/PackageFacade.h"
#include "agrisphere/services/AdminService.h"
#include "agrisphere/services/AuthService.h"
#include "agrisphere/services/CartService.h"
#include "agrisphere/services/CatalogService.h"
#include "agrisphere/services/DiscountService.h"
#include "agrisphere/services/FarmerService.h"
#include "agrisphere/services/MembershipService.h"
#include "agrisphere/services/NotificationService.h"
#include "agrisphere/services/OrderService.h"
#include "agrisphere/services/RegistrationService.h"

#include <memory>

namespace agri {
namespace api {
namespace {

using http::Request;
using http::Response;

// Services are stateless apart from their configuration, so one shared instance
// per service is enough; the only shared mutable resource is the Singleton
// DatabaseManager, which is internally synchronised.
struct Services {
    explicit Services(const AppConfig& config) : auth(config), registration(config) {}

    service::AuthService auth;
    service::RegistrationService registration;
    service::CatalogService catalog;
    service::CartService cart;
    service::OrderService orders;
    service::FarmerService farmer;
    service::AdminService admin;

    // Observer pattern: DiscountService owns the DiscountSubject and
    // publishes the event; NotificationService reads what the observers wrote.
    service::DiscountService discounts;
    service::NotificationService notifications;

    // Premium Membership - the feature that decides which Strategy the
    // checkout uses. OrderService reads the approved membership itself.
    service::MembershipService memberships;

    // Facade pattern: one entry point that adds a whole product package to
    // the cart by coordinating the product, stock and cart subsystems.
    facade::PackageFacade packages;
};

Response ok(const json::Value& payload) { return Response::ofJson(payload, 200); }

// Reads an optional string parameter; an empty value maps to "not provided".
std::optional<std::string> optionalParam(const Request& request, const std::string& name) {
    const std::string value = request.param(name);
    if (value.empty()) {
        return std::nullopt;
    }
    return value;
}

void registerHealthRoutes(http::Router& router, const std::shared_ptr<Services>&) {
    auto health = [](const Request&) {
        const db::DatabaseManager::Stats stats = db::DatabaseManager::getInstance().stats();
        json::Value payload = json::Value::object();
        payload.set("success", json::Value(true));
        payload.set("service", json::Value("agrisphere-backend"));
        payload.set("database_ready", json::Value(db::DatabaseManager::getInstance().isInitialized()));
        payload.set("pool_size", json::Value(static_cast<long long>(stats.poolSize)));
        payload.set("pool_available", json::Value(static_cast<long long>(stats.available)));
        payload.set("queries_served", json::Value(static_cast<long long>(stats.totalQueries)));
        payload.set("reconnects", json::Value(static_cast<long long>(stats.totalReconnects)));
        return ok(payload);
    };
    router.get("/health", health);
    router.get("/api/status", health);
    router.post("/api/status", health);
}

void registerAuthRoutes(http::Router& router, const std::shared_ptr<Services>& services) {
    router.post("/api/auth/login", [services](const Request& request) {
        return ok(services->auth.login(request.param("username"), request.param("password"),
                                       request.param("role")));
    });

    router.post("/api/auth/login/complete", [services](const Request& request) {
        return ok(services->auth.completeHashLogin(request.intParam("user_id")));
    });

    router.post("/api/auth/block-status", [services](const Request& request) {
        return ok(services->auth.blockStatus(request.intParam("user_id")));
    });
    router.get("/api/auth/block-status", [services](const Request& request) {
        return ok(services->auth.blockStatus(request.intParam("user_id")));
    });

    router.post("/api/auth/register", [services](const Request& request) {
        service::RegistrationRequest form;
        form.firstName = request.param("first_name");
        form.lastName = request.param("last_name");
        form.username = request.param("username");
        form.email = request.param("email");
        form.phone = request.param("phone");
        form.address = request.param("address");
        form.password = request.param("password");
        form.confirmPassword = request.param("confirm_password");
        form.role = request.param("role");
        return ok(services->registration.start(form));
    });

    router.post("/api/auth/register/verify", [services](const Request& request) {
        return ok(services->registration.verify(request.param("email"), request.param("otp")));
    });

    router.post("/api/auth/register/resend", [services](const Request& request) {
        return ok(services->registration.resend(request.param("email")));
    });
}

void registerCatalogRoutes(http::Router& router, const std::shared_ptr<Services>& services) {
    auto categories = [services](const Request&) { return ok(services->catalog.categories()); };
    router.get("/api/catalog/categories", categories);
    router.post("/api/catalog/categories", categories);

    auto categoryName = [services](const Request& request) {
        return ok(services->catalog.categoryName(request.intParam("category_id")));
    };
    router.get("/api/catalog/category-name", categoryName);
    router.post("/api/catalog/category-name", categoryName);

    auto browse = [services](const Request& request) {
        return ok(services->catalog.browse(request.intParam("category_id"), request.param("search"),
                                           request.param("sort", "newest")));
    };
    router.get("/api/catalog/products", browse);
    router.post("/api/catalog/products", browse);

    auto governmentPrices = [services](const Request& request) {
        return ok(services->catalog.governmentPrices(request.param("product"),
                                                     request.intParam("category")));
    };
    router.get("/api/catalog/government-prices", governmentPrices);
    router.post("/api/catalog/government-prices", governmentPrices);
}

void registerCartRoutes(http::Router& router, const std::shared_ptr<Services>& services) {
    auto list = [services](const Request& request) {
        return ok(services->cart.list(request.intParam("customer_id")));
    };
    router.get("/api/cart/list", list);
    router.post("/api/cart/list", list);

    auto count = [services](const Request& request) {
        return ok(services->cart.count(request.intParam("customer_id")));
    };
    router.get("/api/cart/count", count);
    router.post("/api/cart/count", count);

    router.post("/api/cart/add", [services](const Request& request) {
        return ok(services->cart.add(request.intParam("customer_id"),
                                     request.intParam("product_id"),
                                     request.intParam("quantity", 1)));
    });
    router.post("/api/cart/update", [services](const Request& request) {
        return ok(services->cart.update(request.intParam("customer_id"),
                                        request.intParam("cart_id"),
                                        request.intParam("quantity", 1)));
    });
    router.post("/api/cart/remove", [services](const Request& request) {
        return ok(services->cart.remove(request.intParam("customer_id"),
                                        request.intParam("cart_id")));
    });
    router.post("/api/cart/clear", [services](const Request& request) {
        return ok(services->cart.clear(request.intParam("customer_id")));
    });
}

void registerOrderRoutes(http::Router& router, const std::shared_ptr<Services>& services) {
    auto checkoutItems = [services](const Request& request) {
        return ok(services->orders.checkoutItems(request.intParam("customer_id")));
    };
    router.get("/api/orders/checkout-items", checkoutItems);
    router.post("/api/orders/checkout-items", checkoutItems);

    router.post("/api/orders/place", [services](const Request& request) {
        service::CheckoutRequest checkout;
        checkout.customerId = request.intParam("customer_id");
        checkout.deliveryAddress = request.param("delivery_address");
        checkout.deliveryPhone = request.param("delivery_phone");
        checkout.paymentMethod = request.param("payment_method");
        checkout.bkashNumber = request.param("bkash_number");
        checkout.cardNumber = request.param("card_number");
        checkout.cardExpiry = request.param("card_expiry");
        checkout.cardCvc = request.param("card_cvc");
        return ok(services->orders.placeOrder(checkout));
    });

    router.post("/api/orders/place-direct", [services](const Request& request) {
        service::DirectOrderRequest order;
        order.customerId = request.intParam("customer_id");
        order.name = request.param("name");
        order.phone = request.param("phone");
        order.address = request.param("address");
        order.instructions = request.param("instructions");
        order.paymentMethod = request.param("payment_method");
        order.bkashNumber = request.param("bkash_number");
        order.cardNumber = request.param("card_number");
        order.cardExpiry = request.param("card_expiry");
        order.cardCvc = request.param("card_cvc");
        return ok(services->orders.placeOrderDirect(order));
    });
}

void registerFarmerRoutes(http::Router& router, const std::shared_ptr<Services>& services) {
    router.post("/api/farmer/products", [services](const Request& request) {
        service::ProductSubmission submission;
        submission.farmerId = request.intParam("farmer_id");
        submission.productName = request.param("product_name");
        submission.categoryId = request.intParam("category_id");
        submission.description = request.param("description");
        submission.quantity = request.intParam("quantity");
        submission.unit = request.param("unit");
        submission.pricePerUnit = request.doubleParam("price_per_unit");
        submission.imageFileName = optionalParam(request, "product_image");
        return ok(services->farmer.submitProduct(submission));
    });

    auto inventory = [services](const Request& request) {
        return ok(services->farmer.inventory(request.intParam("farmer_id")));
    };
    router.get("/api/farmer/inventory", inventory);
    router.post("/api/farmer/inventory", inventory);

    auto land = [services](const Request& request) {
        return ok(services->farmer.land(request.intParam("farmer_id")));
    };
    router.get("/api/farmer/land", land);
    router.post("/api/farmer/land", land);

    // The photo file is uploaded by PHP; only its file name arrives here.
    router.post("/api/farmer/products/image", [services](const Request& request) {
        return ok(services->farmer.updateProductImage(request.intParam("farmer_id"),
                                                      request.intParam("product_id"),
                                                      request.param("product_image")));
    });

    router.post("/api/farmer/inventory/update", [services](const Request& request) {
        return ok(services->farmer.updateInventory(request.intParam("farmer_id"),
                                                   request.intParam("product_id"),
                                                   request.doubleParam("price_per_unit"),
                                                   request.intParam("quantity_available")));
    });
}

void registerAdminRoutes(http::Router& router, const std::shared_ptr<Services>& services) {
    router.post("/api/admin/products/moderate", [services](const Request& request) {
        return ok(services->admin.moderateProduct(request.intParam("admin_id"),
                                                  request.intParam("product_id"),
                                                  request.param("action"),
                                                  request.param("rejection_reason")));
    });

    // Admin may replace the photo of any product, whoever owns it.
    router.post("/api/admin/products/image", [services](const Request& request) {
        return ok(services->admin.updateProductImage(request.intParam("product_id"),
                                                     request.param("product_image")));
    });

    router.post("/api/admin/orders/status", [services](const Request& request) {
        return ok(services->admin.updateOrderStatus(request.intParam("admin_id"),
                                                    request.intParam("order_id"),
                                                    request.param("status")));
    });

    router.post("/api/admin/orders/payment", [services](const Request& request) {
        return ok(services->admin.updatePaymentStatus(request.intParam("order_id"),
                                                      request.param("payment_status")));
    });

    router.post("/api/admin/users/block", [services](const Request& request) {
        return ok(services->admin.setUserBlocked(request.intParam("user_id"),
                                                 request.param("action") == "block"));
    });

    router.post("/api/admin/users/block-record", [services](const Request& request) {
        return ok(services->admin.toggleUserBlockRecord(request.intParam("user_id"),
                                                        request.param("action") == "block"));
    });
}

// -----------------------------------------------------------------------------
//  Observer pattern routes
// -----------------------------------------------------------------------------
//  POST /api/farmer/discounts/activate is the request that starts the whole
//  chain: farmer -> DiscountService -> DiscountSubject -> observers ->
//  notification rows -> member sees them via /api/notifications/list.
// -----------------------------------------------------------------------------
void registerDiscountRoutes(http::Router& router, const std::shared_ptr<Services>& services) {
    router.post("/api/farmer/discounts/activate", [services](const Request& request) {
        return ok(services->discounts.activateDiscount(request.intParam("farmer_id"),
                                                       request.intParam("product_id"),
                                                       request.doubleParam("discount_percent")));
    });

    router.post("/api/farmer/discounts/remove", [services](const Request& request) {
        return ok(services->discounts.removeDiscount(request.intParam("farmer_id"),
                                                     request.intParam("product_id")));
    });

    auto listDiscounts = [services](const Request& request) {
        return ok(services->discounts.listFarmerDiscounts(request.intParam("farmer_id")));
    };
    router.get("/api/farmer/discounts", listDiscounts);
    router.post("/api/farmer/discounts", listDiscounts);
}

void registerNotificationRoutes(http::Router& router, const std::shared_ptr<Services>& services) {
    auto list = [services](const Request& request) {
        return ok(services->notifications.list(request.intParam("user_id"),
                                               static_cast<int>(request.intParam("limit", 50))));
    };
    router.get("/api/notifications/list", list);
    router.post("/api/notifications/list", list);

    auto unread = [services](const Request& request) {
        return ok(services->notifications.unreadCount(request.intParam("user_id")));
    };
    router.get("/api/notifications/unread-count", unread);
    router.post("/api/notifications/unread-count", unread);

    router.post("/api/notifications/read", [services](const Request& request) {
        return ok(services->notifications.markAsRead(request.intParam("user_id"),
                                                     request.intParam("notification_id")));
    });

    router.post("/api/notifications/read-all", [services](const Request& request) {
        return ok(services->notifications.markAllAsRead(request.intParam("user_id")));
    });
}

// -----------------------------------------------------------------------------
//  Premium Membership routes (Strategy pattern feature)
// -----------------------------------------------------------------------------
//  /apply  creates a 'Pending' application - the customer is NOT Premium yet.
//  /review is the admin decision that activates (or refuses) the membership,
//  which is what later makes the checkout pick PremiumDiscountStrategy.
// -----------------------------------------------------------------------------
void registerMembershipRoutes(http::Router& router, const std::shared_ptr<Services>& services) {
    router.post("/api/membership/apply", [services](const Request& request) {
        service::MembershipApplication application;
        application.customerId = request.intParam("customer_id");
        application.paymentMethod = request.param("payment_method");
        application.bkashNumber = request.param("bkash_number");
        application.cardNumber = request.param("card_number");
        application.cardExpiry = request.param("card_expiry");
        application.cardCvc = request.param("card_cvc");
        return ok(services->memberships.apply(application));
    });

    auto status = [services](const Request& request) {
        return ok(services->memberships.status(request.intParam("customer_id")));
    };
    router.get("/api/membership/status", status);
    router.post("/api/membership/status", status);

    auto applications = [services](const Request& request) {
        return ok(services->memberships.listApplications(request.param("status")));
    };
    router.get("/api/membership/applications", applications);
    router.post("/api/membership/applications", applications);

    router.post("/api/membership/review", [services](const Request& request) {
        return ok(services->memberships.review(request.intParam("admin_id"),
                                               request.intParam("membership_id"),
                                               request.param("action")));
    });
}

// -----------------------------------------------------------------------------
//  Facade pattern routes (product packages)
// -----------------------------------------------------------------------------
//  /api/packages/add is the whole point: ONE request adds every product in a
//  package to the cart. The browser never calls /api/cart/add per product.
// -----------------------------------------------------------------------------
void registerPackageRoutes(http::Router& router, const std::shared_ptr<Services>& services) {
    auto list = [services](const Request&) { return ok(services->packages.listPackages()); };
    router.get("/api/packages/list", list);
    router.post("/api/packages/list", list);

    router.post("/api/packages/add", [services](const Request& request) {
        return ok(services->packages.addPackageToCart(request.intParam("customer_id"),
                                                      request.intParam("package_id")));
    });
}

} // namespace

void registerRoutes(http::Router& router, const AppConfig& config) {
    const auto services = std::make_shared<Services>(config);

    registerHealthRoutes(router, services);
    registerAuthRoutes(router, services);
    registerCatalogRoutes(router, services);
    registerCartRoutes(router, services);
    registerOrderRoutes(router, services);
    registerFarmerRoutes(router, services);
    registerAdminRoutes(router, services);
    registerDiscountRoutes(router, services);
    registerNotificationRoutes(router, services);
    registerMembershipRoutes(router, services);
    registerPackageRoutes(router, services);
}

} // namespace api
} // namespace agri
