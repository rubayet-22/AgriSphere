// AgriSphere C++ Backend - turns a ResultSet into JSON.
//
// Values are emitted as JSON strings (or null), which is exactly what PHP's
// mysqli_result::fetch_all(MYSQLI_ASSOC) used to hand the templates. Keeping
// that shape is what allows the existing views to render unchanged.
#pragma once

#include "agrisphere/core/Json.h"
#include "agrisphere/db/ResultSet.h"

namespace agri {
namespace db {

json::Value rowToJson(const Row& row);
json::Value rowsToJson(const ResultSet& rows);

} // namespace db
} // namespace agri
