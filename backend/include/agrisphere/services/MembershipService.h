//  AgriSphere - Premium Membership use cases (customer side + admin review)
//  Flow implemented here:
//
//    customer applies  ->  premium_membership row, status = 'Pending'
//                          (the customer is NOT Premium yet)
//    admin approves    ->  status = 'Approved', expires_at = now + 1 month
//    admin rejects     ->  status = 'Rejected', customer stays Regular
//
//  The ৳300/month fee is fixed here (kMonthlyFee). The customer never sends a
//  price - the amount is written by the backend.
//
//  Payment: this reuses the existing Factory Method payment classes
//  (payment/PaymentCreator + PaymentMethod) to validate the customer's
//  bKash/Nagad/Card details and to generate the transaction reference, exactly
//  as OrderService does for a product order. It deliberately does NOT write to
//  the `payment` table: that table belongs to customer_order and cannot
//  represent a membership. It is a simulated payment, like the rest of the
//  project.
#pragma once

#include "agrisphere/core/Json.h"
#include "agrisphere/repositories/MembershipRepository.h"

#include <string>

namespace agri {
namespace service {

struct MembershipApplication {
    long long customerId = 0;
    std::string paymentMethod;
    std::string bkashNumber;  // also carries the Nagad number
    std::string cardNumber;   // Card only
    std::string cardExpiry;   // Card only, "MM/YY"
    std::string cardCvc;      // Card only
};

class MembershipService {
public:
    // The fixed AgriSphere membership price. Not configurable by the customer.
    static constexpr double kMonthlyFee = 300.0;

    // Customer submits an application. Always ends as 'Pending'.
    json::Value apply(const MembershipApplication& application);

    // Membership state for the customer's "Become Premium" page.
    json::Value status(long long customerId);

    // Admin list. An empty status returns every application.
    json::Value listApplications(const std::string& status);

    // Admin decision: action is "approve" or "reject".
    json::Value review(long long adminId, long long membershipId, const std::string& action);

private:
    repo::MembershipRepository memberships_;
};

} // namespace service
} // namespace agri
