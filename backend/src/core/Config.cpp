#include "agrisphere/core/Config.h"

#include "agrisphere/core/Strings.h"

#include <fstream>
#include <map>

namespace agri {
namespace {

std::string lookup(const std::map<std::string, std::string>& values,
                   const std::string& key,
                   const std::string& fallback) {
    const auto it = values.find(key);
    return it == values.end() ? fallback : it->second;
}

} // namespace

bool loadConfig(const std::string& path, AppConfig& config, std::string& error) {
    error.clear();

    std::ifstream file(path);
    if (!file.is_open()) {
        error = "config file not found: " + path + " (using built-in defaults)";
        return false;
    }

    std::map<std::string, std::string> values;
    std::string section;
    std::string line;
    while (std::getline(file, line)) {
        line = strings::trim(line);
        if (line.empty() || line[0] == '#' || line[0] == ';') {
            continue;
        }
        if (line.front() == '[' && line.back() == ']') {
            section = strings::toLower(strings::trim(line.substr(1, line.size() - 2)));
            continue;
        }
        const std::size_t eq = line.find('=');
        if (eq == std::string::npos) {
            continue;
        }
        const std::string key = strings::toLower(strings::trim(line.substr(0, eq)));
        std::string value = strings::trim(line.substr(eq + 1));
        if (value.size() >= 2 && value.front() == '"' && value.back() == '"') {
            value = value.substr(1, value.size() - 2);
        }
        values[section + "." + key] = value;
    }

    config.db.host = lookup(values, "database.host", config.db.host);
    config.db.port = static_cast<std::uint16_t>(
        strings::toInt(lookup(values, "database.port", std::to_string(config.db.port)), config.db.port));
    config.db.user = lookup(values, "database.user", config.db.user);
    config.db.password = lookup(values, "database.password", config.db.password);
    config.db.database = lookup(values, "database.name", config.db.database);
    config.db.charset = lookup(values, "database.charset", config.db.charset);
    config.db.connectTimeoutMs = static_cast<int>(strings::toInt(
        lookup(values, "database.connect_timeout_ms", std::to_string(config.db.connectTimeoutMs)),
        config.db.connectTimeoutMs));
    config.db.readTimeoutMs = static_cast<int>(strings::toInt(
        lookup(values, "database.read_timeout_ms", std::to_string(config.db.readTimeoutMs)),
        config.db.readTimeoutMs));

    config.server.bindAddress = lookup(values, "server.bind", config.server.bindAddress);
    config.server.port = static_cast<std::uint16_t>(strings::toInt(
        lookup(values, "server.port", std::to_string(config.server.port)), config.server.port));
    config.server.workerThreads = static_cast<std::size_t>(strings::toInt(
        lookup(values, "server.worker_threads", std::to_string(config.server.workerThreads)),
        static_cast<long long>(config.server.workerThreads)));
    config.server.poolSize = static_cast<std::size_t>(strings::toInt(
        lookup(values, "server.db_pool_size", std::to_string(config.server.poolSize)),
        static_cast<long long>(config.server.poolSize)));
    config.server.apiKey = lookup(values, "server.api_key", config.server.apiKey);
    config.server.logFile = lookup(values, "server.log_file", config.server.logFile);

    config.adminUsername = lookup(values, "app.admin_username", config.adminUsername);
    config.adminPassword = lookup(values, "app.admin_password", config.adminPassword);
    config.adminFallbackUserId = strings::toInt(
        lookup(values, "app.admin_fallback_user_id", std::to_string(config.adminFallbackUserId)),
        config.adminFallbackUserId);
    config.otpLifetimeMinutes = static_cast<int>(strings::toInt(
        lookup(values, "app.otp_lifetime_minutes", std::to_string(config.otpLifetimeMinutes)),
        config.otpLifetimeMinutes));

    if (config.server.workerThreads == 0) config.server.workerThreads = 1;
    if (config.server.poolSize == 0) config.server.poolSize = 1;

    return true;
}

} // namespace agri
