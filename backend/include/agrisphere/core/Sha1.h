// AgriSphere C++ Backend - SHA-1, required by MySQL's mysql_native_password
// handshake. Implemented locally so the backend needs no crypto library.
#pragma once

#include <array>
#include <cstdint>
#include <string>

namespace agri {
namespace crypto {

using Sha1Digest = std::array<std::uint8_t, 20>;

Sha1Digest sha1(const std::uint8_t* data, std::size_t length);
Sha1Digest sha1(const std::string& data);

} // namespace crypto
} // namespace agri
