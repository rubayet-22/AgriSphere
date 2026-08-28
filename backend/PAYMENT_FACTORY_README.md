# Payment handling: Factory Method pattern

## What changed

Payment logic used to live inline inside `OrderService::placeOrder()` and
`OrderService::placeOrderDirect()`, duplicated with different bugs in each:

- `placeOrder()` checked `paymentMethod == "cod"`; `placeOrderDirect()` checked
  `paymentMethod == "Cash on Delivery"` — two different string conventions for the
  same thing.
- bKash validation was just `request.bkashNumber.empty()` — a 3-digit or non-numeric
  "number" was accepted. Nagad had no validation at all. `placeOrderDirect()` didn't
  even check for an empty wallet number.
- No fee, no transaction reference/authorization code, no Card option.

This has been replaced with a `payment/` module built around the **Factory Method**
pattern: an abstract `PaymentCreator` declares the Factory Method `createPayment()`,
and each Concrete Creator (`BkashCreator`, `NagadCreator`, `CardCreator`,
`CashOnDeliveryCreator`) overrides it to instantiate exactly one Concrete Product
(`BkashPayment`, `NagadPayment`, `CardPayment`, `CashOnDeliveryPayment`).
`PaymentCreator::forMethod()` is the one place a `payment_method` string picks *which
Concrete Creator* to use — it never constructs a product itself, that's each Creator's
job via its `createPayment()` override. `OrderService` calls `validate()` →
`calculateFee()` → `generateReference()` → `resolveStatus()` on the resulting object
itself, in that order — running those four steps stays the caller's job, not something
the object orchestrates internally.

A new **Card** payment option was added end to end (checkout UI → API → validation →
DB), and the bKash/Nagad number bug is fixed: both now require exactly 11 digits
starting with `01`, enforced in the backend and mirrored (for instant feedback only) in
the checkout page's JavaScript.

Only payment handling was touched. Login, browsing, cart, and admin/farmer features are
unchanged.

## Module structure

```
backend/include/agrisphere/payment/
    PaymentTypes.h              - PaymentInput, ValidationOutcome (plain data)
    PaymentMethod.h             - abstract Product
    WalletPaymentMethod.h       - shared base for bKash/Nagad (not one of the 4 products)
    BkashPayment.h              - Concrete Product
    NagadPayment.h              - Concrete Product
    CashOnDeliveryPayment.h     - Concrete Product
    CardPayment.h               - Concrete Product
    PaymentCreator.h            - abstract Creator; declares the Factory Method
    BkashCreator.h               - Concrete Creator -> BkashPayment
    NagadCreator.h                - Concrete Creator -> NagadPayment
    CashOnDeliveryCreator.h       - Concrete Creator -> CashOnDeliveryPayment
    CardCreator.h                 - Concrete Creator -> CardPayment

backend/src/payment/
    WalletPaymentMethod.cpp     - 11-digit "01" wallet validation, 1.5% fee, BKS-/NGD- reference
    BkashPayment.cpp            - identity only ("bkash", "bKash", "BKS")
    NagadPayment.cpp            - identity only ("nagad", "Nagad", "NGD")
    CashOnDeliveryPayment.cpp   - no wallet number, no fee, no reference, status "Pending"
    CardPayment.cpp             - card number/expiry/CVC validation, 2% fee, AUTH- reference
    PaymentCreator.cpp          - forMethod(): string -> Concrete Creator, normalizes "cod"/"Cash on Delivery"
    BkashCreator.cpp            - createPayment() { return make_unique<BkashPayment>(); }
    NagadCreator.cpp            - createPayment() { return make_unique<NagadPayment>(); }
    CashOnDeliveryCreator.cpp   - createPayment() { return make_unique<CashOnDeliveryPayment>(); }
    CardCreator.cpp             - createPayment() { return make_unique<CardPayment>(); }
```

(`PaymentMethod.h` has no `.cpp` — every member is pure virtual, so there's nothing to
implement at that level.)

## How the factory works

```cpp
auto creator = payment::PaymentCreator::forMethod(request.paymentMethod);
if (!creator) {
    return failure("Unsupported payment method");
}
auto method = creator->createPayment();  // the Factory Method, overridden per Creator

payment::PaymentInput input;
input.orderTotal = subtotal;
input.walletNumber = request.bkashNumber;  // also carries the Nagad number
input.cardNumber = request.cardNumber;
input.cardExpiry = request.cardExpiry;
input.cardCvc = request.cardCvc;

const payment::ValidationOutcome outcome = method->validate(input);
if (!outcome.ok) {
    return failure(outcome.errorMessage);
}
const double fee = method->calculateFee(subtotal);
const std::string reference = method->generateReference();
const std::string status = method->resolveStatus();
const double total = subtotal + fee;
```

Both `OrderService::placeOrder()` (used by `customer/checkout.php`) and
`OrderService::placeOrderDirect()` (the legacy `customer/process_order.php` path) run
this exact sequence. `PaymentCreator::forMethod()` normalizes the method string
(case-insensitive, trimmed) and picks the matching Concrete Creator, mapping `"cod"` and
`"cash on delivery"` to the same `CashOnDeliveryCreator` — that's the one place the old
string-convention mismatch is resolved, so both order-placement paths now behave
identically. Product construction itself never happens in `forMethod()` — only inside
each Concrete Creator's `createPayment()` override.

Fee model: bKash/Nagad 1.5% of the order subtotal, Card 2%, Cash on Delivery free. The
fee is added to `total_amount` (what's actually charged), not just recorded — the
`customer/checkout.php` order summary recalculates live via JavaScript when the payment
method changes, mirroring these same rates for preview purposes only; the C++ backend
recomputes independently and is the source of truth.

Card numbers/CVCs are **never persisted**. `CardPayment::validate()` only stores a
masked value (`**** **** **** 1234`) as the account reference.

## Database migration

`database/payment_migration.sql` adds two columns to the existing `payment` table:

- `payment_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00`
- `payment_reference VARCHAR(40) NULL DEFAULT NULL`

It does not alter or drop anything else — existing rows get the defaults
(`payment_fee = 0.00`, `payment_reference = NULL`) and are otherwise untouched. Safe to
run more than once (`ADD COLUMN IF NOT EXISTS`).

Run it with phpMyAdmin (select `cse311 lab project` → Import → this file) or:

```
D:\XAMPP\mysql\bin\mysql.exe -u root < database\payment_migration.sql
```

## Rebuilding the backend

```
cd backend
build.bat            REM release build
build.bat debug      REM debug build with symbols
```

or with CMake:

```
cmake -S . -B build -G "MinGW Makefiles"
cmake --build build --config Release
```

Requires a C++17 MinGW-w64 toolchain (`g++` on PATH, or installed under
`C:\mingw64`, `C:\msys64\ucrt64`, or `C:\msys64\mingw64`). The legacy MinGW.org
compiler (pre-GCC 7) does **not** work — it's missing `<optional>`/`<mutex>`/`<thread>`.
If you don't have MinGW-w64, `winget install BrechtSanders.WinLibs.POSIX.UCRT` installs
one.

Stop `agrisphere_backend.exe` before rebuilding (Windows locks the running .exe), then
restart it with `start_backend.bat`.

## Testing the fix

With the backend running (`start_backend.bat`) and a customer logged in with items in
their cart, go to `customer/checkout.php`:

1. **bKash / Nagad**: enter fewer than 11 digits, or 11 digits not starting with `01` —
   blocked client-side immediately, and rejected server-side if you bypass the form. A
   valid `01XXXXXXXXX` number succeeds; the `payment` row gets `payment_status='Paid'`,
   `payment_fee` = 1.5% of the subtotal, and `payment_reference` like `BKS-12345678` /
   `NGD-12345678`.
2. **Cash on Delivery**: no wallet/card fields required; `payment_status='Pending'`,
   `payment_fee=0.00`, `payment_reference` empty.
3. **Card**: enter a card number, `MM/YY` expiry, and CVC. An expired date, a
   non-13–19-digit number, or a non-3/4-digit CVC is rejected. On success,
   `payment_method='card'`, the stored account number is masked
   (`**** **** **** 1234`), `payment_fee` = 2% of the subtotal, `payment_reference`
   like `AUTH-12345678`.
4. Confirm the order summary's "Total" updates live when you switch payment methods,
   and matches what's actually charged after submitting.

To exercise the backend directly (useful for confirming server-side validation, since
the browser form also validates client-side):

```
curl -X POST http://127.0.0.1:8787/api/orders/place ^
  -H "Content-Type: application/json" ^
  -H "X-Agri-Key: agrisphere-local-dev-key" ^
  -d "{\"customer_id\":6,\"delivery_address\":\"...\",\"delivery_phone\":\"...\",\"payment_method\":\"bkash\",\"bkash_number\":\"123\"}"
```

should return `{"success":false,"error":"bKash number must be an 11-digit number
starting with 01"}` with no order or payment row created.
