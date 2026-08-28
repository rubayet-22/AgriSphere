// AgriSphere C++ Backend - result of a SELECT plus the outcome of a write.
#pragma once

#include <optional>
#include <stdexcept>
#include <string>
#include <vector>

namespace agri {
namespace db {

// Thrown by the database layer; the HTTP layer converts it into a JSON error.
class DbError : public std::runtime_error {
public:
    explicit DbError(const std::string& message) : std::runtime_error(message) {}
};

class Row {
public:
    Row() = default;
    Row(const std::vector<std::string>* columns, std::vector<std::optional<std::string>> values)
        : columns_(columns), values_(std::move(values)) {}

    bool isNull(const std::string& column) const;
    bool has(const std::string& column) const;

    // Missing column or SQL NULL both yield the fallback.
    std::string get(const std::string& column, const std::string& fallback = std::string()) const;
    long long getInt(const std::string& column, long long fallback = 0) const;
    double getDouble(const std::string& column, double fallback = 0.0) const;

    const std::optional<std::string>& raw(std::size_t index) const { return values_.at(index); }
    std::size_t size() const { return values_.size(); }
    const std::vector<std::string>& columns() const { return *columns_; }

private:
    const std::vector<std::string>* columns_ = nullptr;
    std::vector<std::optional<std::string>> values_;

    int indexOf(const std::string& column) const;
};

class ResultSet {
public:
    ResultSet() = default;

    void setColumns(std::vector<std::string> columns) { columns_ = std::move(columns); }
    void addRow(std::vector<std::optional<std::string>> values) { rows_.push_back(std::move(values)); }

    const std::vector<std::string>& columns() const { return columns_; }
    std::size_t size() const { return rows_.size(); }
    bool empty() const { return rows_.empty(); }

    Row at(std::size_t index) const { return Row(&columns_, rows_.at(index)); }
    std::optional<Row> first() const {
        if (rows_.empty()) return std::nullopt;
        return Row(&columns_, rows_.front());
    }

    class Iterator {
    public:
        Iterator(const ResultSet* owner, std::size_t index) : owner_(owner), index_(index) {}
        bool operator!=(const Iterator& other) const { return index_ != other.index_; }
        void operator++() { ++index_; }
        Row operator*() const { return owner_->at(index_); }

    private:
        const ResultSet* owner_;
        std::size_t index_;
    };

    Iterator begin() const { return Iterator(this, 0); }
    Iterator end() const { return Iterator(this, rows_.size()); }

private:
    std::vector<std::string> columns_;
    std::vector<std::vector<std::optional<std::string>>> rows_;
};

struct ExecResult {
    long long affectedRows = 0;
    long long insertId = 0;
};

} // namespace db
} // namespace agri
