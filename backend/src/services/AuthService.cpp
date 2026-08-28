#include "agrisphere/services/AuthService.h"

#include "agrisphere/core/Strings.h"

namespace agri {
namespace service {
namespace {

// Detects the crypt() formats PHP's password_verify() understands.
bool looksLikePhpHash(const std::string& stored) {
    return strings::startsWith(stored, "$2y$") || strings::startsWith(stored, "$2a$") ||
           strings::startsWith(stored, "$2b$") || strings::startsWith(stored, "$argon2") ||
           strings::startsWith(stored, "$P$") || strings::startsWith(stored, "$6$");
}

json::Value failure(const std::string& message) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(false));
    out.set("error", json::Value(message));
    return out;
}

std::string dashboardFor(const std::string& userType) {
    if (userType == "Admin") return "admin/dashboard.php";
    if (userType == "Farmer") return "farmer/dashboard.php";
    if (userType == "Customer") return "customer/dashboard.php";
    return "auth/login.php";
}

} // namespace

json::Value AuthService::sessionPayload(const repo::UserRecord& user) const {
    json::Value session = json::Value::object();
    session.set("user_id", json::Value(user.userId));
    session.set("user_name", json::Value(user.fullName()));
    session.set("user_type", json::Value(user.userType));
    session.set("username", json::Value(user.username));
    session.set("first_name", json::Value(user.firstName));

    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("session", session);
    out.set("redirect", json::Value(dashboardFor(user.userType)));
    return out;
}

json::Value AuthService::login(const std::string& username,
                               const std::string& password,
                               const std::string& role) {
    if (username.empty() || password.empty() || role.empty()) {
        return failure("All fields are required");
    }

  
    if (role == "Admin" && username == config_.adminUsername &&
        password == config_.adminPassword) {
        repo::UserRecord admin;
        const auto stored = users_.findByUsernameAndType(username, "Admin");
        if (stored) {
            admin = *stored;
            users_.recordLogin(admin.userId);
        } else {
            // Same fallback identity the PHP handler used when no admin row
            // exists in the database.
            admin.userId = config_.adminFallbackUserId;
            admin.firstName = "System";
            admin.lastName = "Admin";
            admin.username = username;
        }
        admin.userType = "Admin";
        json::Value out = sessionPayload(admin);
        out.set("message", json::Value("Welcome back, Admin!"));
        return out;
    }

    // --- ordinary accounts --------------------------------------------------
    const auto found = users_.findByUsernameAndType(username, role);
    if (!found) {
        return failure("Invalid username or password");
    }
    const repo::UserRecord& user = *found;

    if (password != user.password) {
        if (looksLikePhpHash(user.password)) {
            
            json::Value out = json::Value::object();
            out.set("success", json::Value(false));
            out.set("requires_hash_verification", json::Value(true));
            out.set("user_id", json::Value(user.userId));
            out.set("password_hash", json::Value(user.password));
            out.set("error", json::Value("Invalid username or password"));
            return out;
        }
        return failure("Invalid username or password");
    }

    if (users_.isBlocked(user.userId)) {
        return failure("Your account has been blocked. Please contact support.");
    }

    users_.recordLogin(user.userId);
    json::Value out = sessionPayload(user);
    out.set("message", json::Value("Welcome back, " + user.firstName + "!"));
    return out;
}

json::Value AuthService::completeHashLogin(long long userId) {
    const auto found = users_.findById(userId);
    if (!found) {
        return failure("Invalid username or password");
    }
    if (users_.isBlocked(found->userId)) {
        return failure("Your account has been blocked. Please contact support.");
    }
    users_.recordLogin(found->userId);
    json::Value out = sessionPayload(*found);
    out.set("message", json::Value("Welcome back, " + found->firstName + "!"));
    return out;
}

json::Value AuthService::blockStatus(long long userId) {
    json::Value out = json::Value::object();
    out.set("success", json::Value(true));
    out.set("blocked", json::Value(userId > 0 ? users_.isBlocked(userId) : false));
    return out;
}

} // namespace service
} // namespace agri
