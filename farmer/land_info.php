<?php
require_once "../includes/auth.php";
require_once __DIR__ . "/../includes/backend_client.php";
require_role("Farmer");
block_check_or_exit($conn);

require_once "../includes/layout.php";
render_topbar("Land Info");

$farmer_id = (int)$_SESSION['user_id'];

// Land holdings come from the C++ FarmerService (/api/farmer/land)
$landResponse = AgriSphereBackend::tryCall('/api/farmer/land', [
    'farmer_id' => $farmer_id,
]);
$lands = $landResponse['land'] ?? [];
?>

<div class="card"><a href="dashboard.php">Back</a></div>

<?php if (empty($lands)): ?>
  <div class="card">No land info found for your farmer ID.</div>
<?php endif; ?>

<?php foreach ($lands as $r): ?>
  <div class="card">
    <b>🗺️ Land</b><br>
    Size: <?php echo htmlspecialchars($r['land_size'] ?? ''); ?> acres<br>
    Soil: <?php echo htmlspecialchars($r['soil_type'] ?? ''); ?><br>
    District: <?php echo htmlspecialchars($r['district'] ?? ''); ?><br>
    Upazila: <?php echo htmlspecialchars($r['upazila'] ?? ''); ?><br>
    Lat/Lon: <?php echo htmlspecialchars($r['latitude'] ?? ''); ?>, <?php echo htmlspecialchars($r['longitude'] ?? ''); ?><br>
  </div>
<?php endforeach; ?>

<?php render_footer(); ?>
