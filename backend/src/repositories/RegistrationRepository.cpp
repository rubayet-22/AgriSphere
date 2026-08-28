#include "agrisphere/repositories/RegistrationRepository.h"

#include "agrisphere/db/DatabaseManager.h"

namespace agri {
namespace repo {
namespace {

const char* kSelectColumns =
    "SELECT id, first_name, last_name, username, password, user_type, phone, email, address, "
    "nid, otp_code, expires_at FROM pending_registration ";

PendingRegistration toRecord(const db::Row& row) {
    PendingRegistration pending;
    pending.id = row.getInt("id");
    pending.firstName = row.get("first_name");
    pending.lastName = row.get("last_name");
    pending.username = row.get("username");
    pending.password = row.get("password");
    pending.userType = row.get("user_type");
    pending.phone = row.get("phone");
    pending.email = row.get("email");
    pending.address = row.get("address");
    pending.nid = row.get("nid");
    pending.otpCode = row.get("otp_code");
    pending.expiresAt = row.get("expires_at");
    return pending;
}

} // namespace

void RegistrationRepository::deleteByEmail(const std::string& email) {
    db::DatabaseManager::getInstance().execute("DELETE FROM pending_registration WHERE email = ?",
                                               {email});
}

void RegistrationRepository::insertPending(const PendingRegistration& pending) {
    db::DatabaseManager::getInstance().execute(
        "INSERT INTO pending_registration (first_name, last_name, username, password, user_type, "
        "phone, email, address, nid, otp_code, expires_at) "
        "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        {pending.firstName, pending.lastName, pending.username, pending.password, pending.userType,
         pending.phone, pending.email, pending.address, pending.nid, pending.otpCode,
         pending.expiresAt});
}

std::optional<PendingRegistration> RegistrationRepository::findValid(const std::string& email,
                                                                     const std::string& otp) {
    const db::ResultSet rows = db::DatabaseManager::getInstance().query(
        std::string(kSelectColumns) + "WHERE email = ? AND otp_code = ? AND expires_at > NOW()",
        {email, otp});
    if (rows.empty()) {
        return std::nullopt;
    }
    return toRecord(rows.at(0));
}

std::optional<PendingRegistration> RegistrationRepository::findByEmail(const std::string& email) {
    const db::ResultSet rows =
        db::DatabaseManager::getInstance().query(std::string(kSelectColumns) + "WHERE email = ?",
                                                 {email});
    if (rows.empty()) {
        return std::nullopt;
    }
    return toRecord(rows.at(0));
}

void RegistrationRepository::updateOtp(const std::string& email,
                                       const std::string& otp,
                                       const std::string& expiresAt) {
    db::DatabaseManager::getInstance().execute(
        "UPDATE pending_registration SET otp_code = ?, expires_at = ? WHERE email = ?",
        {otp, expiresAt, email});
}

} // namespace repo
} // namespace agri
