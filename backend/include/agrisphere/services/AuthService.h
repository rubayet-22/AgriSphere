// AgriSphere C++ Backend - authentication use cases.
//
// Services hold the business rules. They know nothing about HTTP, sessions or
// HTML, which is what lets the same service back both the PHP site and a future
// Android client.
#pragma once

#include "agrisphere/core/Config.h"
#include "agrisphere/core/Json.h"
#include "agrisphere/repositories/UserRepository.h"

#include <string>

namespace agri {
namespace service {

class AuthService {
public:
    explicit AuthService(const AppConfig& config) : config_(config) {}

    // Validates credentials and returns the payload PHP needs to build its
    // session. reCAPTCHA stays in PHP: it is a web-frontend concern.
    //
    // Password checking mirrors the previous PHP behaviour exactly:
    //   1. built-in admin credentials from the configuration;
    //   2. a plain text match against user.Password;
    //   3. a crypt()-style hash, which is finished off by PHP's
    //      password_verify() - see completeHashLogin().
    json::Value login(const std::string& username,
                      const std::string& password,
                      const std::string& role);

    // Second half of a bcrypt/argon2 login, once PHP has confirmed the hash.
    json::Value completeHashLogin(long long userId);

    json::Value blockStatus(long long userId);

private:
    AppConfig config_;
    repo::UserRepository users_;

    json::Value sessionPayload(const repo::UserRecord& user) const;
};

} // namespace service
} // namespace agri
