
#pragma once

#include "agrisphere/observer/DiscountEvent.h"
#include "agrisphere/observer/DiscountObserver.h"

#include <cstddef>
#include <memory>
#include <mutex>
#include <vector>

namespace agri {
namespace observer {

class DiscountSubject {
public:
    DiscountSubject() = default;

    // Registers an observer. Ignores null pointers and observers that are
    // already registered, so calling attach() twice is harmless.
    void attach(const std::shared_ptr<DiscountObserver>& observer);

    // Unregisters a previously attached observer.
    void detach(const std::shared_ptr<DiscountObserver>& observer);

    // Sends the event to every registered observer, one at a time.
    // If one observer throws, the others still get the event.
    void notifyObservers(const DiscountEvent& event);

    std::size_t observerCount() const;

private:
    mutable std::mutex mutex_;
    std::vector<std::shared_ptr<DiscountObserver>> observers_;
};

} // namespace observer
} // namespace agri
