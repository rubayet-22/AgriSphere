<?php


if (!defined('AGRISPHERE')) {
    define('AGRISPHERE', true);
}

require_once __DIR__ . '/../includes/backend_client.php';

class DesignPatternTest
{
    /** @var mysqli */
    private $conn;

    /** Root of the C++ backend source tree. */
    private $backendRoot;

    /** Cached file contents so a file is read at most once. */
    private $fileCache = [];

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->backendRoot = realpath(__DIR__ . '/../backend');
    }

    // -----------------------------------------------------------------
    //  Entry point
    // -----------------------------------------------------------------
    public function runAll()
    {
        return [
            $this->testDecoratorPattern(),
            $this->testFacadePattern(),
            $this->testStrategyPattern(),
            $this->testObserverPattern(),
            $this->testFactoryPattern(),
        ];
    }

    // -----------------------------------------------------------------
    //  1. DECORATOR - notification delivery enhancement
    // -----------------------------------------------------------------
    private function testDecoratorPattern()
    {
        $start = microtime(true);
        $tests = [];
        $dir = 'include/agrisphere/decorator/';

        // Component interface with the two operations every decorator shares
        $tests[] = $this->check(
            'Notification Component Interface',
            $this->fileMatches($dir . 'Notification.h', '/class\s+Notification\s*\{/')
            && $this->fileMatches($dir . 'Notification.h', '/virtual\s+void\s+send\(\)\s*=\s*0;/')
            && $this->fileMatches($dir . 'Notification.h', '/virtual\s+std::string\s+getDescription\(\)\s*const\s*=\s*0;/'),
            'Notification.h declares send() and getDescription() as pure virtual'
        );

        // Concrete Component - the real in-app notification
        $tests[] = $this->check(
            'Base Notification (Concrete Component)',
            $this->fileMatches($dir . 'BaseNotification.h', '/class\s+BaseNotification\s*:\s*public\s+Notification/'),
            'BaseNotification implements Notification and writes the DB row'
        );

        // The heart of the pattern: a decorator IS-A and HAS-A Notification
        $tests[] = $this->check(
            'Abstract Decorator wraps a Notification',
            $this->fileMatches($dir . 'NotificationDecorator.h', '/class\s+NotificationDecorator\s*:\s*public\s+Notification/')
            && $this->fileMatches($dir . 'NotificationDecorator.h', '/std::unique_ptr<Notification>\s+wrapped_/'),
            'NotificationDecorator extends Notification AND holds a Notification'
        );

        $tests[] = $this->check(
            'Email Decorator',
            $this->fileMatches($dir . 'EmailDecorator.h', '/class\s+EmailDecorator\s*:\s*public\s+NotificationDecorator/')
            && $this->fileMatches('src/decorator/EmailDecorator.cpp', '/NotificationDecorator::send\(\)/'),
            'EmailDecorator extends the abstract decorator and delegates inward'
        );

        $tests[] = $this->check(
            'SMS Decorator',
            $this->fileMatches($dir . 'SmsDecorator.h', '/class\s+SmsDecorator\s*:\s*public\s+NotificationDecorator/')
            && $this->fileMatches('src/decorator/SmsDecorator.cpp', '/NotificationDecorator::send\(\)/'),
            'SmsDecorator extends the abstract decorator and delegates inward'
        );

        $tests[] = $this->check(
            'Push Decorator',
            $this->fileMatches($dir . 'PushNotificationDecorator.h', '/class\s+PushNotificationDecorator\s*:\s*public\s+NotificationDecorator/')
            && $this->fileMatches('src/decorator/PushNotificationDecorator.cpp', '/NotificationDecorator::send\(\)/'),
            'PushNotificationDecorator extends the abstract decorator and delegates inward'
        );

        // Dynamic composition - the reason the pattern exists
        $chain = $this->readFile('src/decorator/NotificationChain.cpp');
        $composes = $chain !== null
            && preg_match('/make_unique<BaseNotification>/', $chain)
            && preg_match('/make_unique<SmsDecorator>\s*\(\s*std::move\(\s*notification\s*\)/', $chain)
            && preg_match('/make_unique<EmailDecorator>\s*\(\s*std::move\(\s*notification\s*\)/', $chain)
            && preg_match('/make_unique<PushNotificationDecorator>\s*\(\s*std::move\(\s*notification\s*\)/', $chain);
        $tests[] = $this->check(
            'Notification Chain (all three composed)',
            $composes,
            'buildDeliveryChain() wraps Base with SMS, then Email, then Push at runtime'
        );

        // BEHAVIOUR: the channel entries the decorators actually wrote
        $types = $this->fetchColumn(
            "SELECT type, COUNT(*) AS n FROM notification GROUP BY type"
        );
        $seen = [];
        foreach ($types as $row) {
            $seen[$row['type']] = (int)$row['n'];
        }
        $tests[] = $this->check(
            'Live evidence: channel entries in database',
            isset($seen['discount_email']) || isset($seen['discount_sms']) || isset($seen['discount_push']),
            $this->describeChannels($seen)
        );

        // BEHAVIOUR: the preferences that drive which decorators are applied
        $tests[] = $this->check(
            'Delivery preferences drive the chain',
            $this->columnExists('user', 'notify_email')
            && $this->columnExists('user', 'notify_sms')
            && $this->columnExists('user', 'notify_push'),
            'user.notify_email / notify_sms / notify_push select which decorators wrap the base'
        );

        return $this->pattern('decorator', 'DECORATOR PATTERN', 'All Components Working',
            'Notification delivery enhancement', 'fa-layer-group', $tests, $start,
            ['decorator/Notification.h', 'decorator/BaseNotification.h', 'decorator/NotificationDecorator.h',
             'decorator/EmailDecorator.h', 'decorator/SmsDecorator.h', 'decorator/PushNotificationDecorator.h',
             'decorator/NotificationChain.h']);
    }

   
    private function testFacadePattern()
    {
        $start = microtime(true);
        $tests = [];
        $header = $this->readFile('include/agrisphere/facade/PackageFacade.h');

        $tests[] = $this->check(
            'Package Facade exists',
            $header !== null && preg_match('/class\s+PackageFacade\s*\{/', $header),
            'PackageFacade declared in include/agrisphere/facade/PackageFacade.h'
        );

        $tests[] = $this->check(
            'Simple entry point: addPackageToCart()',
            $header !== null && preg_match('/addPackageToCart\s*\(\s*long\s+long\s+customerId\s*,\s*long\s+long\s+packageId\s*\)/', $header),
            'One method takes a customer and a package - no subsystem detail leaks out'
        );

        // The definition of a Facade: it coordinates several subsystems
        $subsystems = 0;
        foreach (['PackageRepository', 'CatalogRepository', 'CartRepository', 'OrderRepository'] as $repo) {
            if ($header !== null && preg_match('/repo::' . $repo . '\s+\w+_;/', $header)) {
                $subsystems++;
            }
        }
        $tests[] = $this->check(
            'Complex operations hidden (4 subsystems)',
            $subsystems === 4,
            $subsystems . ' of 4 subsystems coordinated: package contents, product validity, cart writes, stock'
        );

        // The Facade owns no SQL of its own - it only orchestrates.
        // Comments are stripped so prose about SQL does not fail the check.
        $impl = $this->readCode('src/facade/PackageFacade.cpp');
        $tests[] = $this->check(
            'Facade orchestrates, does not own SQL',
            $impl !== null && !preg_match('/\b(SELECT|INSERT|UPDATE|DELETE)\b/i', $impl),
            'No SQL in PackageFacade.cpp - every step is delegated to a repository'
        );

        // Atomic behaviour: validate everything, then one transaction
        $tests[] = $this->check(
            'Atomic: validated then wrapped in a transaction',
            $impl !== null && preg_match('/db::Transaction\s+transaction\s*\(\s*connection\s*\)/', $impl),
            'A package is added inside one db::Transaction, so it can never be half added'
        );

        // BEHAVIOUR: package retrieval through the facade
        $list = AgriSphereBackend::tryCall('/api/packages/list');
        $ok = !empty($list['success']) && isset($list['packages']);
        $count = $ok ? count($list['packages']) : 0;
        $tests[] = $this->check(
            'Package retrieval works through facade',
            $ok,
            $ok ? "listPackages() returned {$count} package(s) from the running backend"
                : 'Backend did not answer /api/packages/list'
        );

        // BEHAVIOUR: each package resolves its items and a total
        $withItems = 0;
        $totalsOk = true;
        if ($ok) {
            foreach ($list['packages'] as $p) {
                if (!empty($p['items'])) {
                    $withItems++;
                    // The facade's total must equal the sum of its line totals
                    $sum = 0.0;
                    foreach ($p['items'] as $i) {
                        $sum += (float)$i['line_total'];
                    }
                    if (abs($sum - (float)$p['total']) > 0.01) {
                        $totalsOk = false;
                    }
                }
            }
        }
        $tests[] = $this->check(
            'Package contents and totals resolved',
            $ok && $withItems > 0 && $totalsOk,
            $ok ? "{$withItems} package(s) resolved products, prices and stock in one call; totals reconcile"
                : 'Backend unavailable'
        );

        return $this->pattern('facade', 'FACADE PATTERN', 'Simplification Working',
            'One call hides the package subsystems', 'fa-object-group', $tests, $start,
            ['facade/PackageFacade.h', 'src/facade/PackageFacade.cpp']);
    }

    
    private function testStrategyPattern()
    {
        $start = microtime(true);
        $tests = [];
        $dir = 'include/agrisphere/strategy/';

        $tests[] = $this->check(
            'Strategy interface',
            $this->fileMatches($dir . 'DiscountStrategy.h', '/class\s+DiscountStrategy\s*\{/')
            && $this->fileMatches($dir . 'DiscountStrategy.h', '/virtual\s+double\s+calculateDiscount\(double\s+subtotal\)\s*const\s*=\s*0;/'),
            'DiscountStrategy declares calculateDiscount() as pure virtual'
        );

        $rates = [
            'Regular Strategy'      => ['RegularDiscountStrategy', 'src/strategy/RegularDiscountStrategy.cpp', '0.0'],
            'Premium Strategy'      => ['PremiumDiscountStrategy', 'src/strategy/PremiumDiscountStrategy.cpp', '5.0'],
            'Bulk Strategy'         => ['BulkDiscountStrategy', 'src/strategy/BulkDiscountStrategy.cpp', '10.0'],
            'Premium + Bulk'        => ['PremiumBulkDiscountStrategy', 'src/strategy/PremiumBulkDiscountStrategy.cpp', '15.0'],
        ];
        foreach ($rates as $label => $spec) {
            list($class, $src, $rate) = $spec;
            $ok = $this->fileMatches($dir . $class . '.h', '/class\s+' . $class . '\s*:\s*public\s+DiscountStrategy/')
                && $this->fileMatches($src, '/discountPercent\(\)\s*const\s*\{?\s*return\s+' . preg_quote($rate, '/') . ';/');
            $tests[] = $this->check($label, $ok, $class . ' returns ' . rtrim($rate, '.0') . '%');
        }

        // Context installs exactly ONE strategy - that is what stops stacking
        $ctx = $this->readFile('src/strategy/DiscountContext.cpp');
        $ordered = $ctx !== null
            && preg_match('/isPremium\s*&&\s*isBulk[\s\S]*?PremiumBulkDiscountStrategy[\s\S]*?else\s+if\s*\(\s*isBulk\s*\)[\s\S]*?BulkDiscountStrategy[\s\S]*?else\s+if\s*\(\s*profile\.isPremium\s*\)[\s\S]*?PremiumDiscountStrategy[\s\S]*?else[\s\S]*?RegularDiscountStrategy/', $ctx);
        $tests[] = $this->check(
            'Context selects the correct strategy',
            $ordered,
            'selectStrategy() tests Premium+Bulk, then Bulk, then Premium, then Regular - one strategy per order'
        );

        $tests[] = $this->check(
            'Bulk threshold defined once',
            $this->fileMatches($dir . 'DiscountContext.h', '/kBulkOrderThreshold\s*=\s*10;/'),
            'DiscountContext::kBulkOrderThreshold = 10 units'
        );

        // BEHAVIOUR: the running backend reports the live rates
        $status = AgriSphereBackend::tryCall('/api/membership/status', ['customer_id' => $this->anyCustomerId()]);
        $tests[] = $this->check(
            'Live rates exposed by backend',
            !empty($status['success'])
            && (float)($status['premium_discount_percent'] ?? 0) === 5.0
            && (int)($status['bulk_threshold'] ?? 0) === 10,
            !empty($status['success'])
                ? 'Backend reports premium 5% and bulk threshold ' . (int)$status['bulk_threshold'] . ' units'
                : 'Backend unavailable'
        );

        // BEHAVIOUR: a real cart selects a real strategy (read only)
        $bulk = $this->customerWithCartUnits(10, null);
        if ($bulk !== null) {
            $co = AgriSphereBackend::tryCall('/api/orders/checkout-items', ['customer_id' => $bulk]);
            $name = $co['discount_strategy'] ?? '';
            $pct  = (float)($co['discount_percent'] ?? -1);
            $tests[] = $this->check(
                'Live selection: bulk cart',
                in_array($name, ['Bulk', 'Premium + Bulk'], true) && ($pct === 10.0 || $pct === 15.0),
                "Customer #{$bulk} ({$co['total_items']} units) -> {$name} strategy at {$pct}%"
            );
        }

        // A non-bulk cart. If nobody currently has one, fall back to a
        // customer with an empty cart, which must select Regular at 0%.
        $small = $this->customerWithCartUnits(1, 9);
        if ($small !== null) {
            $co = AgriSphereBackend::tryCall('/api/orders/checkout-items', ['customer_id' => $small]);
            $name = $co['discount_strategy'] ?? '';
            $pct  = (float)($co['discount_percent'] ?? -1);
            $tests[] = $this->check(
                'Live selection: non-bulk cart',
                in_array($name, ['Regular', 'Premium'], true) && ($pct === 0.0 || $pct === 5.0),
                "Customer #{$small} ({$co['total_items']} units) -> {$name} strategy at {$pct}%"
            );
        } else {
            $empty = $this->customerWithEmptyCart();
            $co = AgriSphereBackend::tryCall('/api/orders/checkout-items', ['customer_id' => $empty]);
            $name = $co['discount_strategy'] ?? '';
            $pct  = (float)($co['discount_percent'] ?? -1);
            $tests[] = $this->check(
                'Live selection: non-bulk cart',
                $name === 'Regular' && $pct === 0.0,
                "Customer #{$empty} (empty cart) -> {$name} strategy at {$pct}%"
            );
        }

        return $this->pattern('strategy', 'STRATEGY PATTERN', 'Switching Strategies Working',
            'Customer discount calculation', 'fa-code-branch', $tests, $start,
            ['strategy/DiscountStrategy.h', 'strategy/RegularDiscountStrategy.h', 'strategy/PremiumDiscountStrategy.h',
             'strategy/BulkDiscountStrategy.h', 'strategy/PremiumBulkDiscountStrategy.h', 'strategy/DiscountContext.h']);
    }

    
    private function testObserverPattern()
    {
        $start = microtime(true);
        $tests = [];
        $dir = 'include/agrisphere/observer/';

        $tests[] = $this->check(
            'Observer interface',
            $this->fileMatches($dir . 'DiscountObserver.h', '/class\s+DiscountObserver\s*\{/')
            && $this->fileMatches($dir . 'DiscountObserver.h', '/onDiscountActivated/'),
            'DiscountObserver declares onDiscountActivated() for every subscriber'
        );

        $subject = $this->readFile($dir . 'DiscountSubject.h');
        $tests[] = $this->check(
            'Subject notifies all observers',
            $subject !== null
            && preg_match('/void\s+attach\s*\(/', $subject)
            && preg_match('/void\s+detach\s*\(/', $subject)
            && preg_match('/void\s+notifyObservers\s*\(/', $subject)
            && preg_match('/std::vector<std::shared_ptr<DiscountObserver>>\s+observers_/', $subject),
            'DiscountSubject holds a list of observers with attach / detach / notifyObservers'
        );

        $tests[] = $this->check(
            'Member Notification Observer',
            $this->fileMatches($dir . 'MemberNotificationObserver.h', '/class\s+MemberNotificationObserver\s*:\s*public\s+DiscountObserver/'),
            'One observer per member reacts to the event and stores a notification'
        );

        $tests[] = $this->check(
            'Backend Log Observer',
            $this->fileMatches($dir . 'BackendLogObserver.h', '/class\s+BackendLogObserver\s*:\s*public\s+DiscountObserver/')
            && $this->fileMatches('src/observer/BackendLogObserver.cpp', '/Logger::info/'),
            'A second, different observer logs the same event - proving the subject is generic'
        );

        $tests[] = $this->check(
            'Discount Event carries the payload',
            $this->fileMatches($dir . 'DiscountEvent.h', '/struct\s+DiscountEvent\s*\{/')
            && $this->fileMatches($dir . 'DiscountEvent.h', '/discountPercent/'),
            'DiscountEvent is plain data passed from subject to every observer'
        );

        // Subject is decoupled: it never writes notifications itself.
        // Comments are stripped first, so a comment mentioning "insert" does
        // not count as storage code.
        $subjectImpl = $this->readCode('src/observer/DiscountSubject.cpp');
        $tests[] = $this->check(
            'Subject decoupled from notification storage',
            $subjectImpl !== null && !preg_match('/NotificationRepository|\bINSERT\b/i', $subjectImpl),
            'DiscountSubject.cpp contains no storage code - it only loops over observers'
        );

        // BEHAVIOUR: observers were subscribed and did react
        $notified = $this->scalar("SELECT COUNT(*) FROM notification WHERE type LIKE 'discount%'");
        $tests[] = $this->check(
            'Live evidence: observers produced notifications',
            $notified > 0,
            number_format($notified) . ' notification row(s) written by MemberNotificationObserver'
        );

        $log = $this->tailLog();
        $tests[] = $this->check(
            'Live evidence: subject broadcast recorded',
            $log !== null && (strpos($log, 'member observer(s) subscribed') !== false
                              || strpos($log, '[discount]') !== false),
            $log !== null && strpos($log, 'member observer(s) subscribed') !== false
                ? 'backend.log shows observers subscribing to the discount subject'
                : 'backend.log shows discount broadcasts'
        );

        return $this->pattern('observer', 'OBSERVER PATTERN', 'Event Broadcasting Working',
            'Farmer discount event notifications', 'fa-broadcast-tower', $tests, $start,
            ['observer/DiscountObserver.h', 'observer/DiscountSubject.h', 'observer/DiscountEvent.h',
             'observer/MemberNotificationObserver.h', 'observer/BackendLogObserver.h']);
    }

    
    private function testFactoryPattern()
    {
        $start = microtime(true);
        $tests = [];
        $dir = 'include/agrisphere/payment/';

        $tests[] = $this->check(
            'Creator declares the factory method',
            $this->fileMatches($dir . 'PaymentCreator.h', '/class\s+PaymentCreator\s*\{/')
            && $this->fileMatches($dir . 'PaymentCreator.h', '/virtual\s+std::unique_ptr<PaymentMethod>\s+createPayment\(\)\s*=\s*0;/'),
            'PaymentCreator::createPayment() is the factory method, pure virtual'
        );

        $tests[] = $this->check(
            'Product interface',
            $this->fileMatches($dir . 'PaymentMethod.h', '/class\s+PaymentMethod\s*\{/')
            && $this->fileMatches($dir . 'PaymentMethod.h', '/virtual\s+ValidationOutcome\s+validate/'),
            'PaymentMethod is the product every creator builds'
        );

        // Each concrete creator builds its own concrete product
        $pairs = [
            'Bkash Factory' => ['BkashCreator', 'BkashPayment'],
            'Nagad Factory' => ['NagadCreator', 'NagadPayment'],
            'Card Factory'  => ['CardCreator', 'CardPayment'],
            'COD Factory'   => ['CashOnDeliveryCreator', 'CashOnDeliveryPayment'],
        ];
        foreach ($pairs as $label => $pair) {
            list($creator, $product) = $pair;
            $ok = $this->fileMatches($dir . $creator . '.h', '/class\s+' . $creator . '\s*:\s*public\s+PaymentCreator/')
                && $this->fileMatches('src/payment/' . $creator . '.cpp', '/make_unique<' . $product . '>/');
            $tests[] = $this->check($label, $ok, $creator . '::createPayment() instantiates ' . $product);
        }

        $tests[] = $this->check(
            'Wallet Payment Method shared by bKash and Nagad',
            $this->fileMatches($dir . 'WalletPaymentMethod.h', '/class\s+WalletPaymentMethod\s*:\s*public\s+PaymentMethod/')
            && $this->fileMatches($dir . 'BkashPayment.h', '/class\s+BkashPayment\s*:\s*public\s+WalletPaymentMethod/')
            && $this->fileMatches($dir . 'NagadPayment.h', '/class\s+NagadPayment\s*:\s*public\s+WalletPaymentMethod/'),
            'Both wallet products share WalletPaymentMethod, which implements PaymentMethod'
        );

        $tests[] = $this->check(
            'Creator registry replaces the if/else chain',
            $this->fileMatches($dir . 'PaymentCreatorRegistry.h', '/class\s+PaymentCreatorRegistry\s*\{/')
            && $this->fileMatches('src/payment/PaymentCreator.cpp', '/PaymentCreatorRegistry::getInstance\(\)\.getFactory/'),
            'forMethod() looks the method up in a registry instead of branching on strings'
        );

        // BEHAVIOUR: every method resolves to its creator on the live backend.
        // These calls deliberately omit account details, so validation fails
        // and NOTHING is ever inserted.
        $probeCustomer = $this->probeCustomerId();
        $expect = [
            'Bkash Factory'  => ['bkash', 'bKash'],
            'Nagad Factory'  => ['nagad', 'Nagad'],
            'Card Factory'   => ['card',  'card'],
            'COD Factory'    => ['cod',   'bKash, Nagad or Card'],
        ];
        foreach ($expect as $label => $spec) {
            list($method, $needle) = $spec;
            $res = AgriSphereBackend::tryCall('/api/membership/apply', [
                'customer_id'    => $probeCustomer,
                'payment_method' => $method,
            ]);
            $error = $res['error'] ?? '';
            $resolved = ($error !== '' && $error !== 'Unsupported payment method'
                         && stripos($error, $needle) !== false);
            $tests[] = $this->check(
                'Live: ' . $label,
                $resolved,
                $resolved ? "'{$method}' resolved to its creator (" . $error . ')'
                          : "'{$method}' did not resolve" . ($error ? ' (' . $error . ')' : '')
            );
        }

        $unknown = AgriSphereBackend::tryCall('/api/membership/apply', [
            'customer_id'    => $probeCustomer,
            'payment_method' => 'no_such_method',
        ]);
        $tests[] = $this->check(
            'Live: unknown method rejected',
            ($unknown['error'] ?? '') === 'Unsupported payment method',
            'An unregistered payment method returns nullptr, exactly as before'
        );

        return $this->pattern('factory', 'FACTORY PATTERN', 'Object Creation Working',
            'Payment object creation', 'fa-industry', $tests, $start,
            ['payment/PaymentCreator.h', 'payment/PaymentMethod.h', 'payment/BkashCreator.h',
             'payment/NagadCreator.h', 'payment/CardCreator.h', 'payment/CashOnDeliveryCreator.h',
             'payment/WalletPaymentMethod.h', 'payment/PaymentCreatorRegistry.h']);
    }

   

    private function pattern($key, $name, $headline, $tagline, $icon, $tests, $start, $files)
    {
        $passed = 0;
        foreach ($tests as $t) {
            if ($t['passed']) {
                $passed++;
            }
        }
        return [
            'key'      => $key,
            'name'     => $name,
            'headline' => $headline,
            'tagline'  => $tagline,
            'icon'     => $icon,
            'tests'    => $tests,
            'total'    => count($tests),
            'passed'   => $passed,
            'failed'   => count($tests) - $passed,
            'ok'       => $passed === count($tests),
            'ms'       => round((microtime(true) - $start) * 1000, 1),
            'files'    => $files,
        ];
    }

    private function check($name, $passed, $detail)
    {
        return ['name' => $name, 'passed' => (bool)$passed, 'detail' => $detail];
    }

    /** Reads a backend source file (read only), cached. Returns null if absent. */
    private function readFile($relative)
    {
        if (array_key_exists($relative, $this->fileCache)) {
            return $this->fileCache[$relative];
        }
        $path = $this->backendRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $this->fileCache[$relative] = is_file($path) ? file_get_contents($path) : null;
        return $this->fileCache[$relative];
    }

    private function fileMatches($relative, $pattern)
    {
        $content = $this->readFile($relative);
        return $content !== null && preg_match($pattern, $content) === 1;
    }

    /**
     * Same as readFile() but with comments stripped.
     *
     * Used for "this file does NOT contain X" checks. Without this, a
     * comment that merely mentions a word (for example "such as a database
     * insert") would make the check fail even though the code is clean.
     */
    private function readCode($relative)
    {
        $content = $this->readFile($relative);
        if ($content === null) {
            return null;
        }
        $content = preg_replace('#/\*[\s\S]*?\*/#', '', $content);
        $content = preg_replace('#//[^\n]*#', '', $content);
        return $content;
    }

    /** SELECT helper returning all rows. */
    private function fetchColumn($sql)
    {
        try {
            $result = $this->conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /** SELECT helper returning a single number. */
    private function scalar($sql)
    {
        try {
            $result = $this->conn->query($sql);
            if ($result && $row = $result->fetch_row()) {
                return (int)$row[0];
            }
        } catch (Throwable $e) {
            // table missing - treated as 0
        }
        return 0;
    }

    private function columnExists($table, $column)
    {
        try {
            $result = $this->conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
            return $result && $result->num_rows > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /** A customer id that exists, for read-only status calls. */
    private function anyCustomerId()
    {
        $rows = $this->fetchColumn("SELECT User_id FROM user WHERE User_type = 'Customer' ORDER BY User_id LIMIT 1");
        return $rows ? (int)$rows[0]['User_id'] : 0;
    }

    /**
     * A customer with no membership row, so the Factory probes reach the
     * payment-method lookup instead of stopping at an earlier check.
     */
    private function probeCustomerId()
    {
        $rows = $this->fetchColumn(
            "SELECT u.User_id
               FROM user u
          LEFT JOIN premium_membership pm ON pm.customer_id = u.User_id
              WHERE u.User_type = 'Customer' AND pm.membership_id IS NULL
              ORDER BY u.User_id LIMIT 1"
        );
        return $rows ? (int)$rows[0]['User_id'] : $this->anyCustomerId();
    }

    /** A customer whose cart holds between $min and $max units. */
    private function customerWithCartUnits($min, $max)
    {
        $having = "SUM(quantity) >= " . (int)$min;
        if ($max !== null) {
            $having .= " AND SUM(quantity) <= " . (int)$max;
        }
        $rows = $this->fetchColumn(
            "SELECT customer_id FROM customer_cart GROUP BY customer_id HAVING {$having} LIMIT 1"
        );
        return $rows ? (int)$rows[0]['customer_id'] : null;
    }

    /** A customer whose cart is empty - always selects Regular at 0%. */
    private function customerWithEmptyCart()
    {
        $rows = $this->fetchColumn(
            "SELECT u.User_id
               FROM user u
          LEFT JOIN customer_cart cc ON cc.customer_id = u.User_id
              WHERE u.User_type = 'Customer' AND cc.cart_id IS NULL
              ORDER BY u.User_id LIMIT 1"
        );
        return $rows ? (int)$rows[0]['User_id'] : $this->anyCustomerId();
    }

    /** Last part of backend.log, read only. */
    private function tailLog()
    {
        $path = $this->backendRoot . DIRECTORY_SEPARATOR . 'backend.log';
        if (!is_file($path)) {
            return null;
        }
        $size = filesize($path);
        $handle = @fopen($path, 'r');
        if (!$handle) {
            return null;
        }
        if ($size > 60000) {
            fseek($handle, -60000, SEEK_END);
        }
        $tail = stream_get_contents($handle);
        fclose($handle);
        return $tail;
    }

    private function describeChannels($seen)
    {
        $parts = [];
        $labels = [
            'discount'       => 'In-App',
            'discount_sms'   => 'SMS',
            'discount_email' => 'Email',
            'discount_push'  => 'Push',
        ];
        foreach ($labels as $type => $label) {
            if (isset($seen[$type])) {
                $parts[] = $label . ' ' . number_format($seen[$type]);
            }
        }
        return $parts ? implode(' · ', $parts) . ' entries written by the chain'
                      : 'No notification rows yet - fire a farmer discount to generate them';
    }
}
