// AgriSphere C++ Backend - configuration loaded from backend/config/backend.ini.
//
// This is the single place where the MySQL credentials live for the C++ layer;
// PHP reads the same values from includes/config.php.
#pragma once

#include <cstdint>
#include <string>

namespace agri {

struct DbConfig {
    std::string host = "127.0.0.1";
    std::uint16_t port = 3306;
    std::string user = "root";
    std::string password;
    std::string database = "cse311 lab project";
    std::string charset = "utf8mb4";
    int connectTimeoutMs = 5000;
    int readTimeoutMs = 30000;
};

struct ServerConfig {
    std::string bindAddress = "127.0.0.1";
    std::uint16_t port = 8787;
    std::size_t workerThreads = 8;
    std::size_t poolSize = 4;
    std::string apiKey = "agrisphere-local-dev-key";
    std::string logFile; // empty -> stdout only
};

struct AppConfig {
    DbConfig db;
    ServerConfig server;

    // Mirrors ADMIN_USERNAME / ADMIN_PASSWORD from includes/config.php so the
    // built-in admin login can be validated inside the C++ AuthService.
    std::string adminUsername = "admin";
    std::string adminPassword = "admin123";
    long long adminFallbackUserId = 9999999;

    // Registration OTP lifetime in minutes (matches the previous PHP behaviour).
    int otpLifetimeMinutes = 10;
};

// Loads an INI file. Missing file or missing keys fall back to the defaults
// above; `error` receives a message only for unreadable/corrupt input.
bool loadConfig(const std::string& path, AppConfig& config, std::string& error);

} // namespace agri
