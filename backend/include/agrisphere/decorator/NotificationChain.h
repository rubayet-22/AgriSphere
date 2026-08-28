//  AgriSphere - DECORATOR PATTERN : composing the chain at runtime
//  This is the whole point of the pattern in one function. It starts with a
//  BaseNotification and wraps it with one decorator per channel the customer
//  switched on, so ONE class per channel covers EVERY combination:
//
//      In-App only                 -> BaseNotification
//      In-App + SMS                -> Sms( Base )
//      In-App + Email + Push       -> Push( Email( Base ) )
//      In-App + SMS + Email + Push -> Push( Email( Sms( Base ) ) )
//
//  With inheritance instead, those four lines would need four classes, and
//  adding one more channel would double the count again.
//
//  NOTE: this is deliberately a plain function, not a creator class. The
//  payment module already owns the Factory Method pattern
//  (payment/PaymentCreator); a "NotificationFactory" class here would blur the
//  two. Composition is all this needs to do.
#pragma once

#include "agrisphere/decorator/DeliveryPreferences.h"
#include "agrisphere/decorator/Notification.h"
#include "agrisphere/repositories/NotificationRepository.h"

#include <memory>

namespace agri {
namespace decorator {

// Builds the delivery chain for one member. Calling send() on the result
// stores one labelled notification entry per ENABLED channel - always the
// In-App one, plus SMS/Email/Push where the customer switched them on. Each
// channel appears at most once, because each decorator is applied at most once.
std::unique_ptr<Notification> buildDeliveryChain(const repo::NewNotification& payload,
                                                 const DeliveryPreferences& preferences);

} // namespace decorator
} // namespace agri
