#include "agrisphere/db/DatabaseManager.h"

#include "agrisphere/core/Logger.h"
#include "agrisphere/db/MySqlConnection.h"

#include <cstdio>
#include <sstream>
#include <utility>

namespace agri {
namespace db {


DatabaseManager& DatabaseManager::getInstance() {
    // A function-local static: the C++11 standard guarantees this is
    // constructed exactly once, even when several worker threads race here.
    static DatabaseManager instance;
    return instance;
}

DatabaseManager::DatabaseManager() = default;

DatabaseManager::~DatabaseManager() { shutdown(); }

void DatabaseManager::initialize(const DbConfig& config, std::size_t poolSize) {
    std::unique_lock<std::mutex> lock(mutex_);
    if (initialized_) {
        throw DbError("DatabaseManager is already initialized");
    }
    config_ = config;
    if (poolSize == 0) {
        poolSize = 1;
    }

    connections_.reserve(poolSize);
    idle_.reserve(poolSize);
    for (std::size_t i = 0; i < poolSize; ++i) {
        auto connection = std::make_unique<MySqlConnection>(config_);
        connection->connect(); // throws DbError if MySQL is unreachable
        idle_.push_back(connection.get());
        connections_.push_back(std::move(connection));
    }

    initialized_ = true;
    Logger::info("DatabaseManager ready: " + std::to_string(poolSize) +
                 " connection(s) to " + config_.host + ":" + std::to_string(config_.port) +
                 "/" + config_.database);
}

void DatabaseManager::shutdown() {
    std::unique_lock<std::mutex> lock(mutex_);
    if (!initialized_ && connections_.empty()) {
        return;
    }
    idle_.clear();
    for (auto& connection : connections_) {
        connection->close();
    }
    connections_.clear();
    initialized_ = false;
}

bool DatabaseManager::isInitialized() const {
    std::lock_guard<std::mutex> lock(mutex_);
    return initialized_;
}

DatabaseManager::Stats DatabaseManager::stats() const {
    std::lock_guard<std::mutex> lock(mutex_);
    Stats out;
    out.poolSize = connections_.size();
    out.available = idle_.size();
    out.totalQueries = totalQueries_;
    out.totalReconnects = totalReconnects_;
    return out;
}

MySqlConnection* DatabaseManager::checkout() {
    std::unique_lock<std::mutex> lock(mutex_);
    if (!initialized_) {
        throw DbError("DatabaseManager has not been initialized");
    }
    available_.wait(lock, [this]() { return !idle_.empty(); });
    MySqlConnection* connection = idle_.back();
    idle_.pop_back();
    ++totalQueries_;
    const bool alive = connection->isOpen();
    lock.unlock();

    // Revive connections the server dropped (wait_timeout, restart, ...).
    if (!alive || !connection->ping()) {
        try {
            connection->connect();
            std::lock_guard<std::mutex> relock(mutex_);
            ++totalReconnects_;
            Logger::warn("Reconnected a pooled MySQL connection");
        } catch (const std::exception& error) {
            checkin(connection);
            throw DbError(std::string("MySQL reconnect failed: ") + error.what());
        }
    }
    return connection;
}

void DatabaseManager::checkin(MySqlConnection* connection) {
    if (!connection) {
        return;
    }
    {
        std::lock_guard<std::mutex> lock(mutex_);
        idle_.push_back(connection);
    }
    available_.notify_one();
}


std::string DatabaseManager::bind(const std::string& sql,
                                  const SqlParams& params,
                                  MySqlConnection& connection) {
    if (params.empty()) {
        if (sql.find('?') != std::string::npos) {
            throw DbError("statement has placeholders but no parameters were supplied");
        }
        return sql;
    }

    std::string out;
    out.reserve(sql.size() + params.size() * 16);

    std::size_t paramIndex = 0;
    bool inSingle = false;
    bool inDouble = false;
    bool inBacktick = false;

    for (std::size_t i = 0; i < sql.size(); ++i) {
        const char c = sql[i];
        if (inSingle) {
            out.push_back(c);
            if (c == '\'') inSingle = false;
            continue;
        }
        if (inDouble) {
            out.push_back(c);
            if (c == '"') inDouble = false;
            continue;
        }
        if (inBacktick) {
            out.push_back(c);
            if (c == '`') inBacktick = false;
            continue;
        }
        if (c == '\'') { inSingle = true; out.push_back(c); continue; }
        if (c == '"') { inDouble = true; out.push_back(c); continue; }
        if (c == '`') { inBacktick = true; out.push_back(c); continue; }

        if (c != '?') {
            out.push_back(c);
            continue;
        }

        if (paramIndex >= params.size()) {
            throw DbError("not enough parameters for statement");
        }
        const SqlValue& value = params[paramIndex++];
        switch (value.kind()) {
            case SqlValue::Kind::Null:
                out += "NULL";
                break;
            case SqlValue::Kind::Int:
                out += std::to_string(value.intValue());
                break;
            case SqlValue::Kind::Double: {
                char buffer[64];
                std::snprintf(buffer, sizeof(buffer), "%.10f", value.doubleValue());
                out += buffer;
                break;
            }
            case SqlValue::Kind::String:
                out.push_back('\'');
                out += connection.escape(value.stringValue());
                out.push_back('\'');
                break;
        }
    }

    if (paramIndex != params.size()) {
        throw DbError("too many parameters for statement (" + std::to_string(params.size()) +
                      " given, " + std::to_string(paramIndex) + " placeholders)");
    }
    return out;
}

ResultSet DatabaseManager::query(const std::string& sql, const SqlParams& params) {
    PooledConnection lease = acquire();
    return lease.query(sql, params);
}

ExecResult DatabaseManager::execute(const std::string& sql, const SqlParams& params) {
    PooledConnection lease = acquire();
    return lease.execute(sql, params);
}

PooledConnection DatabaseManager::acquire() {
    return PooledConnection(this, checkout());
}


PooledConnection::PooledConnection(DatabaseManager* owner, MySqlConnection* connection)
    : owner_(owner), connection_(connection) {}

PooledConnection::PooledConnection(PooledConnection&& other) noexcept
    : owner_(other.owner_), connection_(other.connection_), inTransaction_(other.inTransaction_) {
    other.owner_ = nullptr;
    other.connection_ = nullptr;
    other.inTransaction_ = false;
}

PooledConnection& PooledConnection::operator=(PooledConnection&& other) noexcept {
    if (this != &other) {
        release();
        owner_ = other.owner_;
        connection_ = other.connection_;
        inTransaction_ = other.inTransaction_;
        other.owner_ = nullptr;
        other.connection_ = nullptr;
        other.inTransaction_ = false;
    }
    return *this;
}

PooledConnection::~PooledConnection() { release(); }

void PooledConnection::release() {
    if (!owner_ || !connection_) {
        owner_ = nullptr;
        connection_ = nullptr;
        return;
    }
    if (inTransaction_) {
        // A lease must never return to the pool mid-transaction.
        try {
            connection_->execute("ROLLBACK");
        } catch (...) {
            connection_->close();
        }
        inTransaction_ = false;
    }
    owner_->checkin(connection_);
    owner_ = nullptr;
    connection_ = nullptr;
}

ResultSet PooledConnection::query(const std::string& sql, const SqlParams& params) {
    if (!connection_) {
        throw DbError("using a released database connection");
    }
    return connection_->query(owner_->bind(sql, params, *connection_));
}

ExecResult PooledConnection::execute(const std::string& sql, const SqlParams& params) {
    if (!connection_) {
        throw DbError("using a released database connection");
    }
    return connection_->execute(owner_->bind(sql, params, *connection_));
}

void PooledConnection::begin() {
    if (!connection_) {
        throw DbError("using a released database connection");
    }
    connection_->execute("START TRANSACTION");
    inTransaction_ = true;
}

void PooledConnection::commit() {
    if (!connection_ || !inTransaction_) {
        return;
    }
    connection_->execute("COMMIT");
    inTransaction_ = false;
}

void PooledConnection::rollback() {
    if (!connection_ || !inTransaction_) {
        return;
    }
    connection_->execute("ROLLBACK");
    inTransaction_ = false;
}


Transaction::Transaction(PooledConnection& connection) : connection_(connection) {
    connection_.begin();
}

Transaction::~Transaction() {
    if (!finished_) {
        try {
            connection_.rollback();
        } catch (...) {
            // Destructors must not throw; the lease will close the socket.
        }
    }
}

void Transaction::commit() {
    connection_.commit();
    finished_ = true;
}

} // namespace db
} // namespace agri
