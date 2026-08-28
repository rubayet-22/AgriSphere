// AgriSphere C++ Backend - wires the services onto HTTP routes.
//
// This is the only place that knows about both HTTP and the services, which
// keeps the services transport agnostic and reusable by a future Android app.
#pragma once

#include "agrisphere/core/Config.h"
#include "agrisphere/http/Router.h"

namespace agri {
namespace api {

void registerRoutes(http::Router& router, const AppConfig& config);

} // namespace api
} // namespace agri
