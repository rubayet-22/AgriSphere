
#pragma once

#include "agrisphere/core/Config.h"
#include "agrisphere/db/ResultSet.h"
#include "agrisphere/db/SqlValue.h"

#include <condition_variable>
#include <cstddef>
#include <memory>
#include <mutex>
#include <string>
#include <vector>

namespace agri {
namespace db {

class MySqlConnection;
class DatabaseManager;

// RAII lease on one pooled connection. Needed whenever several statements must
// run on the same connection - i.e. transactions.
class PooledConnection {
public:
    ~PooledConnection();

    PooledConnection(const PooledConnection&) = delete;
    PooledConnection& operator=(const PooledConnection&) = delete;
    PooledConnection(PooledConnection&& other) noexcept;
    PooledConnection& operator=(PooledConnection&& other) noexcept;

    ResultSet query(const std::string& sql, const SqlParams& params = {});
    ExecResult execute(const std::string& sql, const SqlParams& params = {});

    void begin();
    void commit();
    void rollback();

private:
    friend class DatabaseManager;
    PooledConnection(DatabaseManager* owner, MySqlConnection* connection);

    DatabaseManager* owner_ = nullptr;
    MySqlConnection* connection_ = nullptr;
    bool inTransaction_ = false;

    void release();
};

// Scope guard: rolls the transaction back unless commit() was called.
class Transaction {
public:
    explicit Transaction(PooledConnection& connection);
    ~Transaction();

    Transaction(const Transaction&) = delete;
    Transaction& operator=(const Transaction&) = delete;

    void commit();

private:
    PooledConnection& connection_;
    bool finished_ = false;
};

class DatabaseManager {
public:
    
    static DatabaseManager& getInstance();

    DatabaseManager(const DatabaseManager&) = delete;
    DatabaseManager& operator=(const DatabaseManager&) = delete;
    DatabaseManager(DatabaseManager&&) = delete;
    DatabaseManager& operator=(DatabaseManager&&) = delete;

    // --- Lifecycle ----------------------------------------------------------
    // Called once from main() before the HTTP server starts accepting requests.
    void initialize(const DbConfig& config, std::size_t poolSize);
    void shutdown();
    bool isInitialized() const;

    
    ResultSet query(const std::string& sql, const SqlParams& params = {});
    ExecResult execute(const std::string& sql, const SqlParams& params = {});

    // Leases a connection for multi-statement work (transactions).
    PooledConnection acquire();

    struct Stats {
        std::size_t poolSize = 0;
        std::size_t available = 0;
        unsigned long long totalQueries = 0;
        unsigned long long totalReconnects = 0;
    };
    Stats stats() const;

    const DbConfig& config() const { return config_; }

    // Binds parameters into a statement. Exposed for testing and for the few
    // places that need the final SQL text (logging).
    std::string bind(const std::string& sql, const SqlParams& params, MySqlConnection& connection);

private:
    DatabaseManager();
    ~DatabaseManager();

    friend class PooledConnection;

    MySqlConnection* checkout();
    void checkin(MySqlConnection* connection);

    mutable std::mutex mutex_;
    std::condition_variable available_;
    std::vector<std::unique_ptr<MySqlConnection>> connections_;
    std::vector<MySqlConnection*> idle_;
    DbConfig config_;
    bool initialized_ = false;
    unsigned long long totalQueries_ = 0;
    unsigned long long totalReconnects_ = 0;
};

} // namespace db
} // namespace agri
