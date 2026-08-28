#include "agrisphere/core/Sha1.h"

#include <cstring>
#include <vector>

namespace agri {
namespace crypto {
namespace {

inline std::uint32_t rotl(std::uint32_t value, int bits) {
    return (value << bits) | (value >> (32 - bits));
}

void processBlock(const std::uint8_t* block, std::uint32_t state[5]) {
    std::uint32_t w[80];
    for (int i = 0; i < 16; ++i) {
        w[i] = (static_cast<std::uint32_t>(block[i * 4]) << 24) |
               (static_cast<std::uint32_t>(block[i * 4 + 1]) << 16) |
               (static_cast<std::uint32_t>(block[i * 4 + 2]) << 8) |
               (static_cast<std::uint32_t>(block[i * 4 + 3]));
    }
    for (int i = 16; i < 80; ++i) {
        w[i] = rotl(w[i - 3] ^ w[i - 8] ^ w[i - 14] ^ w[i - 16], 1);
    }

    std::uint32_t a = state[0];
    std::uint32_t b = state[1];
    std::uint32_t c = state[2];
    std::uint32_t d = state[3];
    std::uint32_t e = state[4];

    for (int i = 0; i < 80; ++i) {
        std::uint32_t f = 0;
        std::uint32_t k = 0;
        if (i < 20) {
            f = (b & c) | ((~b) & d);
            k = 0x5A827999u;
        } else if (i < 40) {
            f = b ^ c ^ d;
            k = 0x6ED9EBA1u;
        } else if (i < 60) {
            f = (b & c) | (b & d) | (c & d);
            k = 0x8F1BBCDCu;
        } else {
            f = b ^ c ^ d;
            k = 0xCA62C1D6u;
        }
        const std::uint32_t temp = rotl(a, 5) + f + e + k + w[i];
        e = d;
        d = c;
        c = rotl(b, 30);
        b = a;
        a = temp;
    }

    state[0] += a;
    state[1] += b;
    state[2] += c;
    state[3] += d;
    state[4] += e;
}

} // namespace

Sha1Digest sha1(const std::uint8_t* data, std::size_t length) {
    std::uint32_t state[5] = {0x67452301u, 0xEFCDAB89u, 0x98BADCFEu, 0x10325476u, 0xC3D2E1F0u};

    std::size_t offset = 0;
    while (offset + 64 <= length) {
        processBlock(data + offset, state);
        offset += 64;
    }

    std::vector<std::uint8_t> tail(data + offset, data + length);
    tail.push_back(0x80);
    while (tail.size() % 64 != 56) {
        tail.push_back(0x00);
    }
    const std::uint64_t bitLength = static_cast<std::uint64_t>(length) * 8ull;
    for (int i = 7; i >= 0; --i) {
        tail.push_back(static_cast<std::uint8_t>((bitLength >> (i * 8)) & 0xFF));
    }
    for (std::size_t i = 0; i < tail.size(); i += 64) {
        processBlock(tail.data() + i, state);
    }

    Sha1Digest digest{};
    for (int i = 0; i < 5; ++i) {
        digest[i * 4 + 0] = static_cast<std::uint8_t>((state[i] >> 24) & 0xFF);
        digest[i * 4 + 1] = static_cast<std::uint8_t>((state[i] >> 16) & 0xFF);
        digest[i * 4 + 2] = static_cast<std::uint8_t>((state[i] >> 8) & 0xFF);
        digest[i * 4 + 3] = static_cast<std::uint8_t>(state[i] & 0xFF);
    }
    return digest;
}

Sha1Digest sha1(const std::string& data) {
    return sha1(reinterpret_cast<const std::uint8_t*>(data.data()), data.size());
}

} // namespace crypto
} // namespace agri
