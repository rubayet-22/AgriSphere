// AgriSphere C++ Backend - data access for `pending_registration`.
#pragma once

#include <optional>
#include <string>

namespace agri {
namespace repo {

struct PendingRegistration {
    long long id = 0;
    std::string firstName;
    std::string lastName;
    std::string username;
    std::string password;
    std::string userType;
    std::string phone;
    std::string email;
    std::string address;
    std::string nid;
    std::string otpCode;
    std::string expiresAt;
};

class RegistrationRepository {
public:
    void deleteByEmail(const std::string& email);

    void insertPending(const PendingRegistration& pending);

    // Matching row whose OTP is correct and not yet expired.
    std::optional<PendingRegistration> findValid(const std::string& email, const std::string& otp);

    // Any pending row for the address, used to distinguish "wrong OTP" from
    // "expired OTP" and "no registration at all".
    std::optional<PendingRegistration> findByEmail(const std::string& email);

    void updateOtp(const std::string& email, const std::string& otp, const std::string& expiresAt);
};

} // namespace repo
} // namespace agri
