#include "agrisphere/payment/WalletPaymentMethod.h"

#include "agrisphere/core/Strings.h"

namespace agri {
namespace payment {
namespace {


bool isValidWalletNumber(const std::string& number) {
    if (number.size() != 11) {
        return false;
    }
    if (number[0] != '0' || number[1] != '1') {
        return false;
    }
    for (char c : number) {
        if (c < '0' || c > '9') {
            return false;
        }
    }
    return true;
}

} // namespace

ValidationOutcome WalletPaymentMethod::validate(const PaymentInput& input) const {
    const std::string number = strings::trim(input.walletNumber);
    ValidationOutcome outcome;
    if (number.empty()) {
        outcome.errorMessage = "Please enter your " + displayName() + " number";
        return outcome;
    }
    if (!isValidWalletNumber(number)) {
        outcome.errorMessage =
            displayName() + " number must be an 11-digit number starting with 01";
        return outcome;
    }
    outcome.ok = true;
    outcome.normalizedAccount = number;
    return outcome;
}

double WalletPaymentMethod::calculateFee(double orderTotal) const { return orderTotal * 0.015; }

std::string WalletPaymentMethod::resolveStatus() const { return "Paid"; }

std::string WalletPaymentMethod::generateReference() const {
    return referencePrefix() + "-" + strings::randomDigits(8);
}

} // namespace payment
} // namespace agri
