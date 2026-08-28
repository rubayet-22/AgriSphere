#include "agrisphere/repositories/MembershipRepository.h"

namespace agri {
namespace repo {
namespace {

MembershipRecord toRecord(const db::Row& row) {
    MembershipRecord record;
    record.membershipId = row.getInt("membership_id");
    record.customerId = row.getInt("customer_id");
    record.amount = row.getDouble("amount");
    record.paymentMethod = row.get("payment_method");
    record.paymentReference = row.get("payment_reference");
    record.status = row.get("status");
    record.appliedAt = row.get("applied_at");
    record.reviewedAt = row.get("reviewed_at");
    record.expiresAt = row.get("expires_at");
    record.active = row.getInt("is_active", 0) == 1;
    return record;
}

// Repeated in every SELECT so "active" always means the same thing.
const char* kActiveColumn =
    "IF(status = 'Approved' AND expires_at IS NOT NULL AND expires_at > NOW(), 1, 0) AS is_active ";

} // namespace

bool MembershipRepository::isPremium(long long customerId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT membership_id FROM premium_membership "
        "WHERE customer_id = ? AND status = 'Approved' AND expires_at > NOW() "
        "LIMIT 1",
        {customerId});
    return !rows.empty();
}

std::optional<MembershipRecord> MembershipRepository::findLatestForCustomer(long long customerId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        std::string("SELECT *, ") + kActiveColumn +
            "FROM premium_membership WHERE customer_id = ? "
            "ORDER BY membership_id DESC LIMIT 1",
        {customerId});
    if (rows.empty()) {
        return std::nullopt;
    }
    return toRecord(rows.at(0));
}

bool MembershipRepository::hasPendingApplication(long long customerId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT membership_id FROM premium_membership "
        "WHERE customer_id = ? AND status = 'Pending' LIMIT 1",
        {customerId});
    return !rows.empty();
}

long long MembershipRepository::createApplication(long long customerId,
                                                  double amount,
                                                  const std::string& paymentMethod,
                                                  const std::string& paymentAccount,
                                                  const std::string& paymentReference) {
    // status defaults to 'Pending' - the customer is NOT Premium yet.
    const db::ExecResult result = db::DatabaseManager::getInstance().execute(
        "INSERT INTO premium_membership "
        "(customer_id, amount, payment_method, payment_account, payment_reference, "
        "status, applied_at) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())",
        {customerId, amount, paymentMethod, paymentAccount, paymentReference});
    return result.insertId;
}

std::optional<MembershipRecord> MembershipRepository::findById(long long membershipId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        std::string("SELECT *, ") + kActiveColumn +
            "FROM premium_membership WHERE membership_id = ?",
        {membershipId});
    if (rows.empty()) {
        return std::nullopt;
    }
    return toRecord(rows.at(0));
}

db::ResultSet MembershipRepository::listApplications(const std::string& status) {
    const std::string select =
        std::string("SELECT pm.*, ") +
        "IF(pm.status = 'Approved' AND pm.expires_at IS NOT NULL AND pm.expires_at > NOW(), 1, 0) "
        "AS is_active, "
        "CONCAT(u.First_name, ' ', u.Last_name) AS customer_name, u.Email AS customer_email "
        "FROM premium_membership pm "
        "JOIN user u ON u.User_id = pm.customer_id ";

    if (status.empty()) {
        return db::DatabaseManager::getInstance().query(
            select + "ORDER BY pm.membership_id DESC LIMIT 200");
    }
    return db::DatabaseManager::getInstance().query(
        select + "WHERE pm.status = ? ORDER BY pm.membership_id DESC LIMIT 200", {status});
}

long long MembershipRepository::pendingCount() {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT COUNT(*) AS pending FROM premium_membership WHERE status = 'Pending'");
    if (rows.empty()) {
        return 0;
    }
    return rows.at(0).getInt("pending", 0);
}

long long MembershipRepository::approve(long long membershipId, long long adminId) {
    // One month of membership, counted from the moment the admin approves.
    const db::ExecResult result = db::DatabaseManager::getInstance().execute(
        "UPDATE premium_membership "
        "SET status = 'Approved', reviewed_at = NOW(), reviewed_by = ?, "
        "    expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH) "
        "WHERE membership_id = ? AND status = 'Pending'",
        {adminId, membershipId});
    return result.affectedRows;
}

long long MembershipRepository::reject(long long membershipId, long long adminId) {
    // expires_at stays NULL, so the customer remains Regular.
    const db::ExecResult result = db::DatabaseManager::getInstance().execute(
        "UPDATE premium_membership "
        "SET status = 'Rejected', reviewed_at = NOW(), reviewed_by = ? "
        "WHERE membership_id = ? AND status = 'Pending'",
        {adminId, membershipId});
    return result.affectedRows;
}

} // namespace repo
} // namespace agri
