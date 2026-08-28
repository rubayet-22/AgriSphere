#include "agrisphere/repositories/UserRepository.h"

#include "agrisphere/db/DatabaseManager.h"

namespace agri {
namespace repo {
namespace {

UserRecord toRecord(const db::Row& row) {
    UserRecord record;
    record.userId = row.getInt("User_id");
    record.firstName = row.get("First_name");
    record.lastName = row.get("Last_name");
    record.username = row.get("Username");
    record.password = row.get("Password");
    record.userType = row.get("User_type");
    record.phone = row.get("Phone");
    record.email = row.get("Email");
    // Queries that do not select these columns fall back to the defaults -
    // Row::getInt() returns the fallback for a missing column.
    record.notifyEmail = row.getInt("notify_email", 1) == 1;
    record.notifySms = row.getInt("notify_sms", 0) == 1;
    record.notifyPush = row.getInt("notify_push", 0) == 1;
    return record;
}

} // namespace

std::optional<UserRecord> UserRepository::findByUsernameAndType(const std::string& username,
                                                                const std::string& userType) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT User_id, First_name, Last_name, Username, Password, User_type, Phone, Email "
        "FROM user WHERE Username = ? AND User_type = ?",
        {username, userType});
    if (rows.empty()) {
        return std::nullopt;
    }
    return toRecord(rows.at(0));
}

std::optional<UserRecord> UserRepository::findById(long long userId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT User_id, First_name, Last_name, Username, Password, User_type, Phone, Email "
        "FROM user WHERE User_id = ?",
        {userId});
    if (rows.empty()) {
        return std::nullopt;
    }
    return toRecord(rows.at(0));
}

bool UserRepository::usernameExists(const std::string& username) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT User_id FROM user WHERE Username = ?", {username});
    return !rows.empty();
}

bool UserRepository::emailExists(const std::string& email) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT User_id FROM user WHERE Email = ?", {email});
    return !rows.empty();
}

long long UserRepository::createVerifiedUser(db::PooledConnection& connection,
                                             const std::string& firstName,
                                             const std::string& lastName,
                                             const std::string& username,
                                             const std::string& password,
                                             const std::string& userType,
                                             const std::string& phone,
                                             const std::string& email,
                                             const std::string& nid) {
    const db::ExecResult result = connection.execute(
        "INSERT INTO user (First_name, Last_name, Username, Password, User_type, Phone, Email, "
        "NID, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
        {firstName, lastName, username, password, userType, phone, email, nid});
    return result.insertId;
}

void UserRepository::upsertFarmerProfile(db::PooledConnection& connection,
                                         long long userId,
                                         const std::string& address) {
    connection.execute("INSERT INTO farmer (farmer_id, address) VALUES (?, ?) "
                       "ON DUPLICATE KEY UPDATE address = VALUES(address)",
                       {userId, address});
}

void UserRepository::upsertCustomerProfile(db::PooledConnection& connection,
                                           long long userId,
                                           const std::string& address,
                                           const std::string& phone) {
    connection.execute("INSERT INTO customer (customer_id, address, phone) VALUES (?, ?, ?) "
                       "ON DUPLICATE KEY UPDATE address = VALUES(address), phone = VALUES(phone)",
                       {userId, address, phone});
}

void UserRepository::recordLogin(long long userId) {
    db::DatabaseManager::getInstance().execute(
        "UPDATE user SET last_login = NOW() WHERE User_id = ?", {userId});
}

std::vector<UserRecord> UserRepository::listActiveMembers() {
    // "Member" here means a Customer account that is not currently blocked.
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT u.User_id, u.First_name, u.Last_name, u.Username, u.Password, u.User_type, "
        "u.Phone, u.Email, u.notify_email, u.notify_sms, u.notify_push "
        "FROM user u "
        "LEFT JOIN user_block b ON b.user_id = u.User_id "
        "WHERE u.User_type = 'Customer' AND COALESCE(b.is_blocked, 0) = 0 "
        "ORDER BY u.User_id");

    std::vector<UserRecord> members;
    members.reserve(rows.size());
    for (std::size_t i = 0; i < rows.size(); ++i) {
        members.push_back(toRecord(rows.at(i)));
    }
    return members;
}

bool UserRepository::isBlocked(long long userId) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        "SELECT is_blocked FROM user_block WHERE user_id = ?", {userId});
    if (rows.empty()) {
        return false;
    }
    return rows.at(0).getInt("is_blocked", 0) == 1;
}

void UserRepository::block(long long userId) {
    db::DatabaseManager::getInstance().execute(
        "INSERT INTO user_block (user_id, is_blocked, blocked_at) VALUES (?, 1, NOW()) "
        "ON DUPLICATE KEY UPDATE is_blocked = 1, blocked_at = NOW()",
        {userId});
}

void UserRepository::unblock(long long userId) {
    db::DatabaseManager::getInstance().execute(
        "UPDATE user_block SET is_blocked = 0 WHERE user_id = ?", {userId});
}

void UserRepository::deleteBlockRecord(long long userId) {
    db::DatabaseManager::getInstance().execute("DELETE FROM user_block WHERE user_id = ?", {userId});
}

} // namespace repo
} // namespace agri
