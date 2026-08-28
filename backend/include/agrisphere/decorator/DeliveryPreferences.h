//  AgriSphere - DECORATOR PATTERN : one member's delivery preferences
//  Plain data, no behaviour. It carries the three optional channels a customer
//  switched on in customer/profile.php (stored in user.notify_email /
//  notify_sms / notify_push) plus the contact details the simulated deliveries
//  print.
//
//  In-App is not in this struct on purpose: it is always on and is the
//  BaseNotification itself, so it can never be switched off.
#pragma once

#include <string>

namespace agri {
namespace decorator {

struct DeliveryPreferences {
    bool email = false;
    bool sms = false;
    bool push = false;

    std::string emailAddress;  // user.Email
    std::string phone;         // user.Phone
    std::string memberName;    // "First Last", for readable log lines
};

} // namespace decorator
} // namespace agri
