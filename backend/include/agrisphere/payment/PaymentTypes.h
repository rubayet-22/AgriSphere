// =============================================================================
//  AgriSphere - FACTORY METHOD PATTERN : payment data types
// =============================================================================
//  Plain data passed into and out of a PaymentMethod. Keeping this as plain
//  data (no behavior) is what lets OrderService drive the four-call sequence
//  itself instead of a PaymentMethod orchestrating itself.
// =============================================================================
#pragma once

#include <string>

namespace agri {
namespace payment {

struct PaymentInput {
    double orderTotal = 0.0;   // pre-fee subtotal - the fee is a percentage of this
    std::string walletNumber;  // bKash / Nagad mobile number
    std::string cardNumber;    // Card only, raw (spaces/dashes allowed)
    std::string cardExpiry;    // Card only, "MM/YY"
    std::string cardCvc;       // Card only
};

// Returned by PaymentMethod::validate().
struct ValidationOutcome {
    bool ok = false;
    std::string errorMessage;      // set when ok == false, safe to show the customer
    std::string normalizedAccount; // normalized wallet number / masked card / "" for COD
};

} // namespace payment
} // namespace agri
