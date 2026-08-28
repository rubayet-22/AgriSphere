#include "agrisphere/core/Logger.h"

#include "agrisphere/core/Strings.h"

#include <fstream>
#include <iostream>
#include <mutex>

namespace agri {
namespace {

std::mutex g_mutex;
std::ofstream g_file;
LogLevel g_minimum = LogLevel::Info;

const char* levelName(LogLevel level) {
    switch (level) {
        case LogLevel::Debug: return "DEBUG";
        case LogLevel::Info: return "INFO ";
        case LogLevel::Warn: return "WARN ";
        case LogLevel::Error: return "ERROR";
    }
    return "INFO ";
}

} // namespace

void Logger::configure(const std::string& file, LogLevel minimumLevel) {
    std::lock_guard<std::mutex> lock(g_mutex);
    g_minimum = minimumLevel;
    if (g_file.is_open()) {
        g_file.close();
    }
    if (!file.empty()) {
        g_file.open(file, std::ios::app);
    }
}

void Logger::log(LogLevel level, const std::string& message) {
    if (static_cast<int>(level) < static_cast<int>(g_minimum)) {
        return;
    }
    const std::string line =
        "[" + strings::nowDateTime() + "] [" + levelName(level) + "] " + message;

    std::lock_guard<std::mutex> lock(g_mutex);
    if (level == LogLevel::Error) {
        std::cerr << line << std::endl;
    } else {
        std::cout << line << std::endl;
    }
    if (g_file.is_open()) {
        g_file << line << std::endl;
    }
}

} // namespace agri
