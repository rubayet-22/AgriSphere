#include "agrisphere/payment/CardPayment.h"

#include "agrisphere/core/Strings.h"

#include <ctime>

namespace agri {
namespace payment {
namespace {

std::string digitsOnly(const std::string& s) {
    std::string out;
    out.reserve(s.size());
    for (char c : s) {
        if (c >= '0' && c <= '9') {
            out.push_back(c);
        }
    }
    return out;
}

bool isAllDigits(const std::string& s) {
    if (s.empty()) {
        return false;
    }
    for (char c : s) {
        if (c < '0' || c > '9') {
            return false;
        }
    }
    return true;
}

// "MM/YY" -> {month, year}, year expanded to 2000+YY. Returns false if the
// string isn't in that shape.
bool parseExpiry(const std::string& expiry, int& month, int& year) {
    if (expiry.size() != 5 || expiry[2] != '/') {
        return false;
    }
    const std::string monthPart = expiry.substr(0, 2);
    const std::string yearPart = expiry.substr(3, 2);
    if (!isAllDigits(monthPart) || !isAllDigits(yearPart)) {
        return false;
    }
    month = std::stoi(monthPart);
    year = 2000 + std::stoi(yearPart);
    return month >= 1 && month <= 12;
}

void currentYearMonth(int& year, int& month) {
    const std::time_t raw = std::time(nullptr);
    std::tm tmValue{};
#if defined(_WIN32)
    localtime_s(&tmValue, &raw);
#else
    localtime_r(&raw, &tmValue);
#endif
    year = tmValue.tm_year + 1900;
    month = tmValue.tm_mon + 1;
}

} // namespace

std::string CardPayment::methodKey() const { return "card"; }

std::string CardPayment::displayName() const { return "Credit/Debit Card"; }

ValidationOutcome CardPayment::validate(const PaymentInput& input) const {
    ValidationOutcome outcome;

    const std::string number = digitsOnly(input.cardNumber);
    if (number.size() < 13 || number.size() > 19) {
        outcome.errorMessage = "Please enter a valid card number";
        return outcome;
    }

    int expiryMonth = 0;
    int expiryYear = 0;
    if (!parseExpiry(strings::trim(input.cardExpiry), expiryMonth, expiryYear)) {
        outcome.errorMessage = "Please enter a valid expiry date (MM/YY)";
        return outcome;
    }

    int currentYear = 0;
    int currentMonth = 0;
    currentYearMonth(currentYear, currentMonth);
    if (expiryYear < currentYear || (expiryYear == currentYear && expiryMonth < currentMonth)) {
        outcome.errorMessage = "Card has expired";
        return outcome;
    }

    const std::string cvc = strings::trim(input.cardCvc);
    if (!isAllDigits(cvc) || cvc.size() < 3 || cvc.size() > 4) {
        outcome.errorMessage = "Please enter a valid security code";
        return outcome;
    }

    outcome.ok = true;
    outcome.normalizedAccount = "**** **** **** " + number.substr(number.size() - 4);
    return outcome;
}

double CardPayment::calculateFee(double orderTotal) const { return orderTotal * 0.02; }

std::string CardPayment::generateReference() const { return "AUTH-" + strings::randomDigits(8); }

std::string CardPayment::resolveStatus() const { return "Paid"; }

} // namespace payment
} // namespace agri
