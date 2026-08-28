// AgriSphere C++ Backend - data access for `user`, `farmer`, `customer`
// and `user_block`.
//
// Repositories own SQL. They never talk to HTTP and never open a connection
// themselves - they go through DatabaseManager::getInstance().
#pragma once

#include "agrisphere/db/DatabaseManager.h"
#include "agrisphere/db/ResultSet.h"

#include <optional>
#include <string>
#include <vector>

namespace agri {
namespace repo {

struct UserRecord {
    long long userId = 0;
    std::string firstName;
    std::string lastName;
    std::string username;
    std::string password;
    std::string userType;
    std::string phone;
    std::string email;

    // Notification delivery preferences (user.notify_email / notify_sms /
    // notify_push). Read by listActiveMembers() so the Decorator layer can
    // build each member's delivery chain without an extra query. In-App is
    // not here: it is always on.
    bool notifyEmail = true;
    bool notifySms = false;
    bool notifyPush = false;

    std::string fullName() const { return firstName + " " + lastName; }
};

class UserRepository {
public:
    std::optional<UserRecord> findByUsernameAndType(const std::string& username,
                                                    const std::string& userType);
    std::optional<UserRecord> findById(long long userId);

    bool usernameExists(const std::string& username);
    bool emailExists(const std::string& email);

    // Returns the new User_id. Completing a registration writes to `user` and
    // then to `farmer`/`customer`, so it takes an explicit PooledConnection and
    // runs as one transaction - a failure on the second write must not leave a
    // half-created account behind.
    long long createVerifiedUser(db::PooledConnection& connection,
                                 const std::string& firstName,
                                 const std::string& lastName,
                                 const std::string& username,
                                 const std::string& password,
                                 const std::string& userType,
                                 const std::string& phone,
                                 const std::string& email,
                                 const std::string& nid);

    // The live schema may or may not carry the `after_user_insert` trigger that
    // seeds farmer/customer rows, so these upserts are idempotent either way.
    // `farmer` has no phone column - the number lives on `user`.`Phone` only.
    void upsertFarmerProfile(db::PooledConnection& connection,
                             long long userId,
                             const std::string& address);
    void upsertCustomerProfile(db::PooledConnection& connection,
                               long long userId,
                               const std::string& address,
                               const std::string& phone);

    void recordLogin(long long userId);

    // Every registered (and not blocked) member. The Observer layer uses this
    // to build one observer per member, which is why the notification system
    // adjusts automatically as members are added or removed.
    std::vector<UserRecord> listActiveMembers();

    bool isBlocked(long long userId);
    void block(long long userId);
    void unblock(long long userId);        // keeps the row, sets is_blocked = 0
    void deleteBlockRecord(long long userId);
};

} // namespace repo
} // namespace agri
