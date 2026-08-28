
#pragma once

#include <string>

namespace agri {
namespace observer {

struct DiscountEvent {
    long long productId = 0;
    std::string productName;

    long long farmerId = 0;
    std::string farmerName;

    double discountPercent = 0.0;   // for example 15.00
    double originalPrice = 0.0;     // the farmer's list price
    double discountedPrice = 0.0;   // what a customer now pays

    std::string createdAt;          // "YYYY-MM-DD HH:MM:SS"

    
    std::string message() const;

   
    std::string title() const;
};

} // namespace observer
} // namespace agri
