// AgriSphere C++ Backend - small string helpers shared across the layers.
#pragma once

#include <string>
#include <vector>

namespace agri {
namespace strings {

std::string trim(const std::string& s);
std::string toLower(const std::string& s);
bool iequals(const std::string& a, const std::string& b);
bool startsWith(const std::string& s, const std::string& prefix);
std::vector<std::string> split(const std::string& s, char delimiter);

std::string urlDecode(const std::string& s);

// Formats "now" (local time) as MySQL DATETIME, optionally shifted by seconds.
std::string nowDateTime(long long offsetSeconds = 0);

// Zero padded random digits, used for the 6 digit registration OTP.
std::string randomDigits(std::size_t length);

long long toInt(const std::string& s, long long fallback = 0);
double toDouble(const std::string& s, double fallback = 0.0);

} // namespace strings
} // namespace agri
