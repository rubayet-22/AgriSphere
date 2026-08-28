#include "agrisphere/services/MembershipService.h"

#include "agrisphere/core/Strings.h"
#include "agrisphere/db/JsonMapper.h"
#include "agrisphere/payment/PaymentCreator.h"
#include "agrisphere/strategy/DiscountContext.h"
#include "agrisphere/strategy/PremiumDiscountStrategy.h"

#include <memory>

namespace agri {
namespace service {
namespace {

json::Value failure(const std::string& message) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(false));
    out.set("error", json::Value(message));
    return out;
}

// Adds the customer's latest application to a response, or nothing when the
// customer has never applied.
void putApplication(json::Value& out, const std::optional<repo::MembershipRecord>& record) {
    if (!record) {
        out.set("has_application", json::Value(false));
        return;
    }
    out.set("has_application", json::Value(true));
    out.set("membership_id", json::Value(record->membershipId));
    out.set("application_status", json::Value(record->status));
    out.set("amount", json::Value(record->amount));
    out.set("payment_method", json::Value(record->paymentMethod));
    out.set("payment_reference", json::Value(record->paymentReference));
    out.set("applied_at", json::Value(record->appliedAt));
    out.set("reviewed_at", json::Value(record->reviewedAt));
    out.set("expires_at", json::Value(record->expiresAt));
}

} // namespace

json::Value MembershipService::apply(const MembershipApplication& application) {
    if (application.customerId <= 0) {
        return failure("Invalid request");
    }

    if (memberships_.isPremium(application.customerId)) {
        return failure("You already have an active Premium membership.");
    }
    if (memberships_.hasPendingApplication(application.customerId)) {
        return failure("Your Premium membership request is already waiting for admin approval.");
    }

    
    const std::unique_ptr<payment::PaymentCreator> creator =
        payment::PaymentCreator::forMethod(application.paymentMethod);
    if (!creator) {
        return failure("Unsupported payment method");
    }
    const std::string methodKey = strings::toLower(strings::trim(application.paymentMethod));
    if (methodKey == "cod" || methodKey == "cash on delivery") {
        return failure("Membership must be paid with bKash, Nagad or Card.");
    }
    const std::unique_ptr<payment::PaymentMethod> method = creator->createPayment();

    payment::PaymentInput paymentInput;
    paymentInput.orderTotal = kMonthlyFee;  // fixed ৳300, never sent by the client
    paymentInput.walletNumber = application.bkashNumber;
    paymentInput.cardNumber = application.cardNumber;
    paymentInput.cardExpiry = application.cardExpiry;
    paymentInput.cardCvc = application.cardCvc;

    const payment::ValidationOutcome outcome = method->validate(paymentInput);
    if (!outcome.ok) {
        return failure(outcome.errorMessage);
    }
    const std::string reference = method->generateReference();

    // No payment fee is added: the membership price is exactly ৳300/month.
    const long long membershipId = memberships_.createApplication(
        application.customerId, kMonthlyFee, method->methodKey(), outcome.normalizedAccount,
        reference);

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("membership_id", json::Value(membershipId));
    out.set("amount", json::Value(kMonthlyFee));
    out.set("status", json::Value("Pending"));
    out.set("payment_reference", json::Value(reference));
    out.set("message",
            json::Value("Premium membership request submitted (BDT 300 / month, reference " +
                        reference + "). You are still a Regular customer until an admin "
                                    "approves it."));
    return out;
}

json::Value MembershipService::status(long long customerId) {
    const bool premium = memberships_.isPremium(customerId);

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("is_premium", json::Value(premium));
    out.set("monthly_fee", json::Value(kMonthlyFee));

    
    const strategy::PremiumDiscountStrategy premiumStrategy;
    out.set("premium_discount_percent", json::Value(premiumStrategy.discountPercent()));
    out.set("bulk_threshold",
            json::Value(static_cast<long long>(strategy::DiscountContext::kBulkOrderThreshold)));

    putApplication(out, memberships_.findLatestForCustomer(customerId));
    return out;
}

json::Value MembershipService::listApplications(const std::string& status) {
    if (!status.empty() && status != "Pending" && status != "Approved" && status != "Rejected") {
        return failure("Invalid status");
    }

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("applications", db::rowsToJson(memberships_.listApplications(status)));
    out.set("pending_count", json::Value(memberships_.pendingCount()));
    out.set("monthly_fee", json::Value(kMonthlyFee));
    return out;
}

json::Value MembershipService::review(long long adminId,
                                      long long membershipId,
                                      const std::string& action) {
    if (membershipId <= 0 || (action != "approve" && action != "reject")) {
        return failure("Invalid request");
    }

    const auto record = memberships_.findById(membershipId);
    if (!record) {
        return failure("Membership application not found");
    }
    if (record->status != "Pending") {
        return failure("This application was already " + record->status + ".");
    }

    if (action == "approve") {
        if (memberships_.approve(membershipId, adminId) == 0) {
            return failure("This application was already reviewed.");
        }
        json::Value out = json::Value::object();
        out.set("success", json::Value(true));
        out.set("status", json::Value("Approved"));
        out.set("message", json::Value("Membership #" + std::to_string(membershipId) +
                                       " approved. The customer is Premium for one month and "
                                       "now receives the 5% Premium discount."));
        return out;
    }

    if (memberships_.reject(membershipId, adminId) == 0) {
        return failure("This application was already reviewed.");
    }
    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("status", json::Value("Rejected"));
    out.set("message", json::Value("Membership #" + std::to_string(membershipId) +
                                   " rejected. The customer stays Regular."));
    return out;
}

} // namespace service
} // namespace agri
