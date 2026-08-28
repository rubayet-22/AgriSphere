// =============================================================================
//  AgriSphere - discount use cases (the place that PUBLISHES the event)
// =============================================================================
//  This service is the bridge between the farmer's action and the Observer
//  pattern. When a farmer activates a discount it:
//
//    1. validates the request (owner, status, percentage range)
//    2. saves the discount through DiscountRepository
//    3. builds a DiscountEvent describing what happened
//    4. refreshes the member observers from the database
//    5. asks the DiscountSubject to notify every observer
//
//  Step 5 is a single call. This service does not know that notifications are
//  stored in a table, and it does not loop over members itself - that is the
//  observers' job. Adding a new kind of subscriber later does not change any
//  code in this file except one attach() call in the constructor.
// =============================================================================
#pragma once

#include "agrisphere/core/Json.h"
#include "agrisphere/observer/DiscountObserver.h"
#include "agrisphere/observer/DiscountSubject.h"
#include "agrisphere/repositories/DiscountRepository.h"
#include "agrisphere/repositories/UserRepository.h"

#include <memory>
#include <mutex>
#include <vector>

namespace agri {
namespace service {

class DiscountService {
public:
    DiscountService();

    // Farmer activates (or updates) a discount on one of their products.
    // This is the operation that triggers the Observer notification.
    json::Value activateDiscount(long long farmerId, long long productId, double discountPercent);

    // Farmer removes a discount. No event is published: members are only told
    // about new offers, not about offers being withdrawn.
    json::Value removeDiscount(long long farmerId, long long productId);

    // The farmer's discount management page.
    json::Value listFarmerDiscounts(long long farmerId);

    // Exposed so /health can show how many observers are registered.
    std::size_t observerCount() const { return subject_.observerCount(); }

private:
    observer::DiscountSubject subject_;
    repo::DiscountRepository discounts_;
    repo::UserRepository users_;

    // The member observers attached during the previous event. They are
    // detached and rebuilt each time so the observer list always matches the
    // members currently in the database.
    std::vector<std::shared_ptr<observer::DiscountObserver>> memberObservers_;

    // Only one discount event is published at a time, so the observer list
    // cannot be rebuilt by two requests at once.
    std::mutex publishMutex_;

    void refreshMemberObservers();
};

} // namespace service
} // namespace agri
