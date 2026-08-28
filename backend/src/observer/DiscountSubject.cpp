#include "agrisphere/observer/DiscountSubject.h"

#include "agrisphere/core/Logger.h"

#include <algorithm>

namespace agri {
namespace observer {

void DiscountSubject::attach(const std::shared_ptr<DiscountObserver>& observer) {
    if (!observer) {
        return;
    }
    std::lock_guard<std::mutex> lock(mutex_);

    // Do not register the same observer twice.
    const auto existing = std::find(observers_.begin(), observers_.end(), observer);
    if (existing != observers_.end()) {
        return;
    }
    observers_.push_back(observer);
}

void DiscountSubject::detach(const std::shared_ptr<DiscountObserver>& observer) {
    if (!observer) {
        return;
    }
    std::lock_guard<std::mutex> lock(mutex_);

    const auto found = std::find(observers_.begin(), observers_.end(), observer);
    if (found != observers_.end()) {
        observers_.erase(found);
    }
}

std::size_t DiscountSubject::observerCount() const {
    std::lock_guard<std::mutex> lock(mutex_);
    return observers_.size();
}

void DiscountSubject::notifyObservers(const DiscountEvent& event) {
    // Copy the list first, then release the lock. An observer is free to do
    // slow work (such as a database insert) without blocking the subject.
    std::vector<std::shared_ptr<DiscountObserver>> snapshot;
    {
        std::lock_guard<std::mutex> lock(mutex_);
        snapshot = observers_;
    }

    Logger::info("DiscountSubject: notifying " + std::to_string(snapshot.size()) +
                 " observer(s) about \"" + event.title() + "\"");

    for (const std::shared_ptr<DiscountObserver>& observer : snapshot) {
        try {
            observer->onDiscountActivated(event);
        } catch (const std::exception& error) {
            // One failing observer must not stop the rest from being told.
            Logger::error("Observer " + observer->observerName() + " failed: " + error.what());
        }
    }
}

} // namespace observer
} // namespace agri
