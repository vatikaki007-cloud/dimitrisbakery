<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['acc_user_id'])) {
    header("Location: login.php");
    exit;
}

$pdo = get_db();

// Ensure zoom_level column exists
try { $pdo->exec("ALTER TABLE acc_users ADD COLUMN zoom_level INT DEFAULT 100"); } catch(\Exception $e) {}

// Ensure routes table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_routes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        route_name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch(\Exception $e) {}

// Ensure daily skips table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_daily_skips (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        skip_date DATE NOT NULL,
        UNIQUE KEY unique_skip (customer_id, skip_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch(\Exception $e) {}

// Ensure route_id exists on customers
try { $pdo->exec("ALTER TABLE acc_customers ADD COLUMN route_id INT NULL"); } catch(\Exception $e) {}

// Handle AJAX zoom save
if (isset($_GET['ajax_zoom'])) {
    header('Content-Type: application/json');
    $zoom = max(70, min(150, (int)($_POST['zoom'] ?? 100)));
    $pdo->prepare("UPDATE acc_users SET zoom_level = ? WHERE id = ?")
        ->execute([$zoom, $_SESSION['acc_user_id']]);
    echo json_encode(['ok' => true, 'zoom' => $zoom]);
    exit;
}

// Load current user's zoom level
$stmt = $pdo->prepare("SELECT zoom_level FROM acc_users WHERE id = ?");
$stmt->execute([$_SESSION['acc_user_id']]);
$user_zoom = (int)($stmt->fetchColumn() ?: 100);

?>
<style>
    .navbar { background: #0056b3; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; color: white; position: sticky; top: 0; z-index: 1000; }
    .navbar a { color: white; text-decoration: none; margin-right: 20px; font-size: 16px; position: relative; }
    .navbar a:hover { text-decoration: underline; }
    .navbar .brand { font-size: 20px; font-weight: bold; }
    .nav-links { display: flex; align-items: center; }
    .user-info { font-size: 14px; margin-right: 20px; color: #d0e1f9; }
    .btn-logout { background: #d9534f; padding: 6px 12px; border-radius: 4px; text-decoration: none; color: white; }
    .btn-logout:hover { background: #c9302c; text-decoration: none; }

    /* Zoom controls */
    .zoom-controls { display: flex; align-items: center; gap: 4px; margin-right: 18px; }
    .zoom-btn { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.35); color: white; width: 28px; height: 28px; border-radius: 4px; cursor: pointer; font-size: 18px; line-height: 1; display: flex; align-items: center; justify-content: center; padding: 0; transition: background 0.15s; }
    .zoom-btn:hover { background: rgba(255,255,255,0.3); }
    .zoom-label { font-size: 12px; color: #d0e1f9; min-width: 36px; text-align: center; }
</style>

<!-- Apply saved zoom before page renders -->
<script>
    (function() {
        var z = <?= $user_zoom ?>;
        document.documentElement.style.zoom = z + '%';
    })();
</script>

<div class="navbar">
    <div class="brand"><a href="dashboard.php" style="margin:0;">Dashboard</a></div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="invoice_create.php?new=1">Create Invoice</a>
        <a href="invoices.php">Invoices</a>
        <a href="orders_dashboard.php">Orders</a>
        <a href="customers.php">Customers</a>
        <a href="routes.php">Routes</a>
        <a href="suppliers.php">Suppliers</a>
        <a href="products.php">Products</a>
        <a href="settings.php">Settings</a>
        <?php if ($_SESSION['acc_role'] === 'admin'): ?>
            <a href="users.php">Users</a>
        <?php endif; ?>

        <!-- Zoom controls -->
        <div class="zoom-controls">
            <button class="zoom-btn" onclick="adjustZoom(-5)" title="Zoom out">−</button>
            <span class="zoom-label" id="zoom-label"><?= $user_zoom ?>%</span>
            <button class="zoom-btn" onclick="adjustZoom(5)" title="Zoom in">+</button>
        </div>

        <span class="user-info">Logged in as <?php echo htmlspecialchars($_SESSION['acc_username']); ?> (<?php echo htmlspecialchars($_SESSION['acc_role']); ?>)</span>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<script>
    var _currentZoom = <?= $user_zoom ?>;

    function adjustZoom(delta) {
        _currentZoom = Math.max(70, Math.min(150, _currentZoom + delta));
        document.documentElement.style.zoom = _currentZoom + '%';
        document.getElementById('zoom-label').textContent = _currentZoom + '%';

        // Save to DB for this user
        var fd = new FormData();
        fd.append('zoom', _currentZoom);
        fetch('<?= dirname($_SERVER['PHP_SELF']) ?>/navbar.php?ajax_zoom=1', {
            method: 'POST', body: fd
        });
    }
</script>
