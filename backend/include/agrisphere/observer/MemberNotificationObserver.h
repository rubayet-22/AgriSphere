
#pragma once

#include "agrisphere/decorator/DeliveryPreferences.h"
#include "agrisphere/observer/DiscountObserver.h"

#include <string>

namespace agri {
namespace observer {

class MemberNotificationObserver : public DiscountObserver {
public:
    MemberNotificationObserver(long long memberId,
                               std::string memberName,
                               decorator::DeliveryPreferences preferences);

    // Stores one notification row for this member, and hands it to the
    // delivery chain built from that member's preferences.
    void onDiscountActivated(const DiscountEvent& event) override;

    std::string observerName() const override;

    long long memberId() const { return memberId_; }

private:
    long long memberId_;
    std::string memberName_;

    // Decorator pattern: WHICH optional channels this member turned on. The
    // observer still decides when and for whom a notification happens; how it
    // is delivered belongs to the chain built from these preferences.
    decorator::DeliveryPreferences preferences_;
};

} // namespace observer
} // namespace agri
