#include "agrisphere/db/JsonMapper.h"

namespace agri {
namespace db {

json::Value rowToJson(const Row& row) {
    json::Value object = json::Value::object();
    const std::vector<std::string>& columns = row.columns();
    for (std::size_t i = 0; i < columns.size(); ++i) {
        const auto& value = row.raw(i);
        if (value.has_value()) {
            object.set(columns[i], json::Value(*value));
        } else {
            object.set(columns[i], json::Value(nullptr));
        }
    }
    return object;
}

json::Value rowsToJson(const ResultSet& rows) {
    json::Value array = json::Value::array();
    for (std::size_t i = 0; i < rows.size(); ++i) {
        array.push(rowToJson(rows.at(i)));
    }
    return array;
}

} // namespace db
} // namespace agri
