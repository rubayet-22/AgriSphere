<?php
/**
 * Admin - Design Pattern Regression Tests
 *
 * Runs tests/DesignPatternTest.php and renders one card per pattern.
 * Everything it does is READ ONLY: source files are opened for reading,
 * the database is queried with SELECT only, and the backend calls either
 * read data or fail validation before any insert can occur.
 */

$pageTitle = 'Design Pattern Tests';
$currentPage = 'design_pattern_tests';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../tests/DesignPatternTest.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

$runStarted = microtime(true);
$runAt      = date('d M Y, h:i:s A');

$tester  = new DesignPatternTest($conn);
$results = $tester->runAll();

$totalMs     = round((microtime(true) - $runStarted) * 1000, 1);
$totalTests  = 0;
$totalPassed = 0;
foreach ($results as $r) {
    $totalTests  += $r['total'];
    $totalPassed += $r['passed'];
}
$totalFailed = $totalTests - $totalPassed;
$allGreen    = ($totalFailed === 0);
$backendUp   = AgriSphereBackend::isUp();

// Include sidebar
include __DIR__ . '/../includes/sidebar_admin.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Design Pattern Tests</h1>
    <p class="page-subtitle">
        Read-only verification that all five design patterns are implemented and working
    </p>
</div>

<?php if (!$backendUp): ?>
    <div class="alert alert-error">
        <i class="fas fa-plug"></i>
        The C++ backend is not running, so the live behaviour checks cannot pass.
        Start <code>backend\start_backend.bat</code> and reload this page.
    </div>
<?php endif; ?>

<!-- Overall summary -->
<div class="dpt-summary <?php echo $allGreen ? 'dpt-ok' : 'dpt-bad'; ?>">
    <div class="dpt-summary-main">
        <div class="dpt-summary-icon">
            <i class="fas <?php echo $allGreen ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        </div>
        <div>
            <h2>
                <?php echo $allGreen
                    ? 'All Design Patterns Verified - Ready for Demo!'
                    : $totalFailed . ' check(s) failed'; ?>
            </h2>
            <p>
                <?php echo count($results); ?> patterns &middot;
                <?php echo $totalPassed; ?>/<?php echo $totalTests; ?> checks passed &middot;
                run at <?php echo $runAt; ?>
            </p>
        </div>
    </div>
    <div class="dpt-summary-time">
        <div class="dpt-time-value"><?php echo number_format($totalMs, 1); ?><span>ms</span></div>
        <div class="dpt-time-label">total execution</div>
    </div>
</div>

<!-- Pattern cards -->
<div class="dpt-grid">
    <?php foreach ($results as $r): ?>
        <div class="dpt-card <?php echo $r['ok'] ? 'pass' : 'fail'; ?>">
            <div class="dpt-card-head">
                <div class="dpt-card-icon">
                    <i class="fas <?php echo $r['icon']; ?>"></i>
                </div>
                <div class="dpt-card-title">
                    <h3><?php echo sanitize($r['name']); ?></h3>
                    <p><?php echo sanitize($r['tagline']); ?></p>
                </div>
                <span class="dpt-badge <?php echo $r['ok'] ? 'ok' : 'bad'; ?>">
                    <i class="fas <?php echo $r['ok'] ? 'fa-check' : 'fa-xmark'; ?>"></i>
                    <?php echo $r['ok'] ? 'PASS' : $r['failed'] . ' FAILED'; ?>
                </span>
            </div>

            <div class="dpt-card-meta">
                <span><i class="fas fa-list-check"></i> <?php echo $r['passed']; ?>/<?php echo $r['total']; ?> checks</span>
                <span><i class="fas fa-stopwatch"></i> <?php echo $r['ms']; ?> ms</span>
                <span class="dpt-headline"><?php echo sanitize($r['headline']); ?></span>
            </div>

            <ul class="dpt-tests">
                <?php foreach ($r['tests'] as $t): ?>
                    <li class="<?php echo $t['passed'] ? 'pass' : 'fail'; ?>">
                        <span class="dpt-mark">
                            <i class="fas <?php echo $t['passed'] ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                        </span>
                        <span class="dpt-test-body">
                            <strong><?php echo sanitize($t['name']); ?></strong>
                            <em class="dpt-status"><?php echo $t['passed'] ? 'PASS' : 'FAIL'; ?></em>
                            <span class="dpt-detail"><?php echo sanitize($t['detail']); ?></span>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <details class="dpt-files">
                <summary><i class="fas fa-file-code"></i> Verified source files (<?php echo count($r['files']); ?>)</summary>
                <ul>
                    <?php foreach ($r['files'] as $f): ?>
                        <li><code><?php echo sanitize($f); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        </div>
    <?php endforeach; ?>
</div>

<!-- Plain-text summary, easy to read out during a demo -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-terminal"></i> Summary</h3>
    </div>
    <div class="card-body">
        <pre class="dpt-console"><?php
foreach ($results as $r) {
    echo ($r['ok'] ? "OK  " : "!!  ") . $r['name'] . ': ' . $r['headline'] . "\n";
    foreach ($r['tests'] as $t) {
        echo '      - ' . str_pad($t['name'], 46, '.') . ' ' . ($t['passed'] ? 'PASS' : 'FAIL') . "\n";
    }
    echo "\n";
}
echo str_repeat('-', 62) . "\n";
echo ($allGreen ? 'ALL DESIGN PATTERNS WORKING FINE' : 'SOME CHECKS FAILED') . "\n";
echo 'Checks : ' . $totalPassed . '/' . $totalTests . "\n";
echo 'Time   : ' . number_format($totalMs, 1) . " ms\n";
echo 'Run at : ' . $runAt . "\n";
?></pre>
        <p class="text-muted text-sm" style="margin-top: 12px;">
            <i class="fas fa-shield-halved"></i>
            This page never writes: source files are read only, the database is queried with SELECT only,
            and the payment probes deliberately omit account details so they fail validation before any insert.
        </p>
        <a href="design_pattern_tests.php" class="btn btn-primary" style="margin-top: 12px;">
            <i class="fas fa-rotate"></i> Run Tests Again
        </a>
    </div>
</div>

<style>
.dpt-summary {
    display: flex; justify-content: space-between; align-items: center; gap: 24px;
    flex-wrap: wrap; padding: 24px 28px; border-radius: var(--radius-lg);
    margin-bottom: 24px; border: 2px solid;
}
.dpt-summary.dpt-ok  { background: linear-gradient(135deg,#f0fdf4,#dcfce7); border-color: var(--primary); }
.dpt-summary.dpt-bad { background: linear-gradient(135deg,#fef2f2,#fee2e2); border-color: var(--error); }
.dpt-summary-main { display: flex; align-items: center; gap: 18px; }
.dpt-summary-icon { font-size: 2.6rem; }
.dpt-ok  .dpt-summary-icon { color: var(--primary); }
.dpt-bad .dpt-summary-icon { color: var(--error); }
.dpt-summary h2 { margin: 0 0 4px; font-size: 1.35rem; }
.dpt-summary p  { margin: 0; color: var(--gray-600); font-size: .9rem; }
.dpt-summary-time { text-align: right; }
.dpt-time-value { font-size: 1.9rem; font-weight: 700; color: var(--gray-800); }
.dpt-time-value span { font-size: .9rem; font-weight: 500; color: var(--gray-500); margin-left: 2px; }
.dpt-time-label { font-size: .75rem; text-transform: uppercase; color: var(--gray-500); letter-spacing: .04em; }

.dpt-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(430px, 1fr)); gap: 20px; }

.dpt-card {
    background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius-lg);
    padding: 20px; border-top: 4px solid var(--gray-300);
}
.dpt-card.pass { border-top-color: var(--primary); }
.dpt-card.fail { border-top-color: var(--error); }

.dpt-card-head { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 14px; }
.dpt-card-icon {
    width: 46px; height: 46px; flex-shrink: 0; border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    background: var(--primary-50); color: var(--primary); font-size: 1.2rem;
}
.dpt-card.fail .dpt-card-icon { background: #fee2e2; color: var(--error); }
.dpt-card-title { flex: 1; min-width: 0; }
.dpt-card-title h3 { margin: 0; font-size: 1rem; letter-spacing: .02em; }
.dpt-card-title p  { margin: 2px 0 0; font-size: .8125rem; color: var(--gray-500); }

.dpt-badge {
    font-size: .7rem; font-weight: 700; padding: 5px 10px; border-radius: var(--radius-full);
    white-space: nowrap; letter-spacing: .04em;
}
.dpt-badge.ok  { background: #dcfce7; color: #15803d; }
.dpt-badge.bad { background: #fee2e2; color: #b91c1c; }

.dpt-card-meta {
    display: flex; gap: 16px; flex-wrap: wrap; align-items: center;
    font-size: .78rem; color: var(--gray-500);
    padding-bottom: 12px; border-bottom: 1px solid var(--gray-100); margin-bottom: 12px;
}
.dpt-headline { margin-left: auto; font-weight: 600; color: var(--gray-600); }

.dpt-tests { list-style: none; margin: 0; padding: 0; }
.dpt-tests li { display: flex; gap: 10px; padding: 8px 0; border-bottom: 1px dashed var(--gray-100); }
.dpt-tests li:last-child { border-bottom: none; }
.dpt-mark { flex-shrink: 0; margin-top: 1px; }
.dpt-tests li.pass .dpt-mark { color: var(--primary); }
.dpt-tests li.fail .dpt-mark { color: var(--error); }
.dpt-test-body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.dpt-test-body strong { font-size: .875rem; font-weight: 600; }
.dpt-status { font-style: normal; font-size: .68rem; font-weight: 700; letter-spacing: .06em; }
.dpt-tests li.pass .dpt-status { color: #15803d; }
.dpt-tests li.fail .dpt-status { color: #b91c1c; }
.dpt-detail { font-size: .78rem; color: var(--gray-500); line-height: 1.4; }

.dpt-files { margin-top: 14px; font-size: .8rem; }
.dpt-files summary { cursor: pointer; color: var(--gray-500); }
.dpt-files ul { margin: 8px 0 0 18px; color: var(--gray-600); }
.dpt-files code { font-size: .75rem; }

.dpt-console {
    background: #0f172a; color: #e2e8f0; padding: 18px; border-radius: var(--radius);
    font-size: .8rem; line-height: 1.5; overflow-x: auto; margin: 0;
}

@media (max-width: 900px) { .dpt-grid { grid-template-columns: 1fr; } }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
