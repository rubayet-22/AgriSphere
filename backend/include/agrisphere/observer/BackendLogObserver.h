
#pragma once

#include "agrisphere/observer/DiscountObserver.h"

namespace agri {
namespace observer {

class BackendLogObserver : public DiscountObserver {
public:
    void onDiscountActivated(const DiscountEvent& event) override;
    std::string observerName() const override;
};

} // namespace observer
} // namespace agri
