// AgriSphere C++ Backend - data access for the `premium_membership` table.
//
// This is the ONLY place that answers "is this customer Premium right now?".
// The answer is derived from the data (status = 'Approved' and not expired),
// so there is no separate flag column that could drift out of date.
//
// Like every other repository it reaches MySQL only through the Singleton
// DatabaseManager::getInstance().
#pragma once

#include "agrisphere/db/DatabaseManager.h"
#include "agrisphere/db/ResultSet.h"

#include <optional>
#include <string>

namespace agri {
namespace repo {

struct MembershipRecord {
    long long membershipId = 0;
    long long customerId = 0;
    double amount = 0.0;
    std::string paymentMethod;
    std::string paymentReference;
    std::string status;      // Pending | Approved | Rejected
    std::string appliedAt;
    std::string reviewedAt;
    std::string expiresAt;
    bool active = false;     // Approved AND expires_at > NOW()
};

class MembershipRepository {
public:
    // The premium test used by the checkout. Pending and Rejected both
    // return false, and an approved membership stops counting once
    // expires_at has passed.
    bool isPremium(long long customerId);

    // The customer's most recent application, for the "Become Premium" page.
    std::optional<MembershipRecord> findLatestForCustomer(long long customerId);

    bool hasPendingApplication(long long customerId);

    // Returns the new membership_id. The row always starts as 'Pending'.
    long long createApplication(long long customerId,
                                double amount,
                                const std::string& paymentMethod,
                                const std::string& paymentAccount,
                                const std::string& paymentReference);

    std::optional<MembershipRecord> findById(long long membershipId);

    // Admin list. An empty status returns every application.
    db::ResultSet listApplications(const std::string& status);

    long long pendingCount();

    // Both only affect a row that is still 'Pending', so a decision cannot be
    // silently overwritten. They return the number of rows changed.
    // approve() also sets expires_at to one month from now - this is what
    // makes the membership monthly.
    long long approve(long long membershipId, long long adminId);
    long long reject(long long membershipId, long long adminId);
};

} // namespace repo
} // namespace agri
