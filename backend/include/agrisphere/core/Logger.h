// AgriSphere C++ Backend - minimal thread safe logger.
#pragma once

#include <string>

namespace agri {

enum class LogLevel { Debug, Info, Warn, Error };

class Logger {
public:
    static void configure(const std::string& file, LogLevel minimumLevel = LogLevel::Info);
    static void log(LogLevel level, const std::string& message);

    static void debug(const std::string& message) { log(LogLevel::Debug, message); }
    static void info(const std::string& message) { log(LogLevel::Info, message); }
    static void warn(const std::string& message) { log(LogLevel::Warn, message); }
    static void error(const std::string& message) { log(LogLevel::Error, message); }
};

} // namespace agri
