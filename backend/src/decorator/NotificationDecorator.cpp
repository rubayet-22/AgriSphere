#include "agrisphere/decorator/NotificationDecorator.h"

#include <utility>

namespace agri {
namespace decorator {
namespace {

// notification.title is VARCHAR(150).
constexpr std::size_t kMaxTitleLength = 150;

} // namespace

std::string labelledTitle(const std::string& channelLabel, const std::string& title) {
    std::string labelled = "(" + channelLabel + " Notification) " + title;
    if (labelled.size() > kMaxTitleLength) {
        labelled.resize(kMaxTitleLength);
    }
    return labelled;
}

NotificationDecorator::NotificationDecorator(std::unique_ptr<Notification> wrapped,
                                             repo::NewNotification payload)
    : wrapped_(std::move(wrapped)), payload_(std::move(payload)) {}

void NotificationDecorator::send() {
    if (wrapped_) {
        wrapped_->send();
    }
}

std::string NotificationDecorator::getDescription() const {
    return wrapped_ ? wrapped_->getDescription() : std::string();
}


void NotificationDecorator::storeChannelNotification(const std::string& channelLabel,
                                                     const std::string& notificationType) {
    repo::NewNotification entry = payload_;
    entry.title = labelledTitle(channelLabel, payload_.title);
    entry.type = notificationType;
    notifications_.insert(entry);
}

} // namespace decorator
} // namespace agri
