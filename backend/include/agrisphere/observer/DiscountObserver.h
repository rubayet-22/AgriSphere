
#pragma once

#include "agrisphere/observer/DiscountEvent.h"

#include <string>

namespace agri {
namespace observer {

class DiscountObserver {
public:
    virtual ~DiscountObserver() = default;

    // Called by the Subject once for every observer when a discount goes live.
    virtual void onDiscountActivated(const DiscountEvent& event) = 0;

    // A readable name, used in the backend log so the flow can be traced.
    virtual std::string observerName() const = 0;
};

} // namespace observer
} // namespace agri
