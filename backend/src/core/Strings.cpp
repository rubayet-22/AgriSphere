#include "agrisphere/core/Strings.h"

#include <algorithm>
#include <cctype>
#include <cstdio>
#include <ctime>
#include <mutex>
#include <random>
#include <sstream>

namespace agri {
namespace strings {

std::string trim(const std::string& s) {
    std::size_t begin = 0;
    std::size_t end = s.size();
    while (begin < end && std::isspace(static_cast<unsigned char>(s[begin]))) {
        ++begin;
    }
    while (end > begin && std::isspace(static_cast<unsigned char>(s[end - 1]))) {
        --end;
    }
    return s.substr(begin, end - begin);
}

std::string toLower(const std::string& s) {
    std::string out = s;
    std::transform(out.begin(), out.end(), out.begin(), [](unsigned char c) {
        return static_cast<char>(std::tolower(c));
    });
    return out;
}

bool iequals(const std::string& a, const std::string& b) {
    return a.size() == b.size() && toLower(a) == toLower(b);
}

bool startsWith(const std::string& s, const std::string& prefix) {
    return s.size() >= prefix.size() && s.compare(0, prefix.size(), prefix) == 0;
}

std::vector<std::string> split(const std::string& s, char delimiter) {
    std::vector<std::string> parts;
    std::string current;
    std::istringstream stream(s);
    while (std::getline(stream, current, delimiter)) {
        parts.push_back(current);
    }
    return parts;
}

std::string urlDecode(const std::string& s) {
    std::string out;
    out.reserve(s.size());
    for (std::size_t i = 0; i < s.size(); ++i) {
        if (s[i] == '+') {
            out.push_back(' ');
        } else if (s[i] == '%' && i + 2 < s.size() &&
                   std::isxdigit(static_cast<unsigned char>(s[i + 1])) &&
                   std::isxdigit(static_cast<unsigned char>(s[i + 2]))) {
            const std::string hex = s.substr(i + 1, 2);
            out.push_back(static_cast<char>(std::stoi(hex, nullptr, 16)));
            i += 2;
        } else {
            out.push_back(s[i]);
        }
    }
    return out;
}

std::string nowDateTime(long long offsetSeconds) {
    std::time_t raw = std::time(nullptr) + static_cast<std::time_t>(offsetSeconds);
    std::tm tmValue{};
#if defined(_WIN32)
    localtime_s(&tmValue, &raw);
#else
    localtime_r(&raw, &tmValue);
#endif
    char buffer[32];
    std::strftime(buffer, sizeof(buffer), "%Y-%m-%d %H:%M:%S", &tmValue);
    return std::string(buffer);
}

std::string randomDigits(std::size_t length) {
    static std::mutex mutex;
    static std::mt19937_64 engine(std::random_device{}());
    std::uniform_int_distribution<int> dist(0, 9);

    std::lock_guard<std::mutex> lock(mutex);
    std::string out;
    out.reserve(length);
    for (std::size_t i = 0; i < length; ++i) {
        out.push_back(static_cast<char>('0' + dist(engine)));
    }
    return out;
}

long long toInt(const std::string& s, long long fallback) {
    try {
        const std::string t = trim(s);
        if (t.empty()) {
            return fallback;
        }
        return std::stoll(t);
    } catch (...) {
        return fallback;
    }
}

double toDouble(const std::string& s, double fallback) {
    try {
        const std::string t = trim(s);
        if (t.empty()) {
            return fallback;
        }
        return std::stod(t);
    } catch (...) {
        return fallback;
    }
}

} // namespace strings
} // namespace agri
