// AgriSphere C++ Backend - registration and OTP verification use cases.
#pragma once

#include "agrisphere/core/Config.h"
#include "agrisphere/core/Json.h"
#include "agrisphere/repositories/RegistrationRepository.h"
#include "agrisphere/repositories/UserRepository.h"

#include <string>

namespace agri {
namespace service {

struct RegistrationRequest {
    std::string firstName;
    std::string lastName;
    std::string username;
    std::string email;
    std::string phone;
    std::string address;
    std::string password;
    std::string confirmPassword;
    std::string role;
};

class RegistrationService {
public:
    explicit RegistrationService(const AppConfig& config) : config_(config) {}

    // Validates, stores a pending_registration row and returns the OTP.
    json::Value start(const RegistrationRequest& request);

    // Checks the OTP, creates the account and returns the session payload.
    json::Value verify(const std::string& email, const std::string& otp);

    // Issues a fresh OTP for an existing pending registration.
    json::Value resend(const std::string& email);

private:
    AppConfig config_;
    repo::RegistrationRepository pending_;
    repo::UserRepository users_;
};

} // namespace service
} // namespace agri
