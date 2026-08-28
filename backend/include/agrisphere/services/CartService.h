// AgriSphere C++ Backend - shopping cart use cases.
#pragma once

#include "agrisphere/core/Json.h"
#include "agrisphere/repositories/CartRepository.h"
#include "agrisphere/repositories/CatalogRepository.h"

namespace agri {
namespace service {

class CartService {
public:
    json::Value list(long long customerId);
    json::Value count(long long customerId);

    json::Value add(long long customerId, long long productId, long long quantity);
    json::Value update(long long customerId, long long cartId, long long quantity);
    json::Value remove(long long customerId, long long cartId);
    json::Value clear(long long customerId);

private:
    repo::CartRepository cart_;
    repo::CatalogRepository catalog_;
};

} // namespace service
} // namespace agri
