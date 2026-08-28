#include "agrisphere/observer/BackendLogObserver.h"

#include "agrisphere/core/Logger.h"

namespace agri {
namespace observer {

std::string BackendLogObserver::observerName() const { return "BackendLogObserver"; }

void BackendLogObserver::onDiscountActivated(const DiscountEvent& event) {
    Logger::info("[discount] " + event.message());
}

} // namespace observer
} // namespace agri
