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

$nav_items = [
    ['url' => 'dashboard.php', 'label' => 'Dashboard'],
    ['url' => 'invoice_create.php?new=1', 'label' => 'Create Invoice'],
    ['url' => 'invoices.php', 'label' => 'Invoices'],
    ['url' => 'orders_dashboard.php', 'label' => 'Orders'],
    ['url' => 'customers.php', 'label' => 'Customers'],
    ['url' => 'routes.php', 'label' => 'Routes'],
    ['url' => 'suppliers.php', 'label' => 'Suppliers'],
    ['url' => 'products.php', 'label' => 'Products'],
    ['url' => 'settings.php', 'label' => 'Settings'],
];

if ($_SESSION['acc_role'] === 'admin') {
    $nav_items[] = ['url' => 'users.php', 'label' => 'Users'];
}

?>
<style>
    * { box-sizing: border-box; }
    
    .navbar { 
        background: #0056b3; 
        padding: 0; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        color: white; 
        position: sticky; 
        top: 0; 
        z-index: 1000;
        min-height: 50px;
    }
    
    .navbar-brand { 
        padding: 12px 15px; 
        font-size: 18px; 
        font-weight: bold; 
        flex-shrink: 0;
    }
    
    .navbar-brand a { 
        color: white; 
        text-decoration: none; 
    }
    
    .navbar-brand a:hover { 
        text-decoration: underline; 
    }
    
    .hamburger { 
        display: none; 
        background: none; 
        border: none; 
        color: white; 
        font-size: 28px; 
        cursor: pointer; 
        padding: 10px 15px; 
        width: auto; 
        height: auto;
        flex-shrink: 0;
    }
    
    .hamburger:hover { 
        background: rgba(255,255,255,0.1); 
    }
    
    .nav-menu { 
        display: flex; 
        align-items: center; 
        gap: 0;
        flex: 1;
    }
    
    .nav-menu a { 
        color: white; 
        text-decoration: none; 
        padding: 15px 12px; 
        font-size: 14px; 
        white-space: nowrap;
        display: block;
    }
    
    .nav-menu a:hover { 
        background: rgba(255,255,255,0.1); 
    }
    
    .nav-menu .user-info { 
        color: #d0e1f9; 
        padding: 15px 12px; 
        font-size: 12px;
        white-space: nowrap;
    }
    
    .nav-menu .btn-logout { 
        background: #d9534f; 
        padding: 8px 12px; 
        border-radius: 4px; 
        text-decoration: none; 
        color: white;
        margin: 0 12px;
        display: block;
    }
    
    .nav-menu .btn-logout:hover { 
        background: #c9302c; 
        text-decoration: none; 
    }
    
    .zoom-controls { 
        display: flex; 
        align-items: center; 
        gap: 4px; 
        padding: 0 12px;
        margin-left: auto;
    }
    
    .zoom-btn { 
        background: rgba(255,255,255,0.15); 
        border: 1px solid rgba(255,255,255,0.35); 
        color: white; 
        width: 32px; 
        height: 32px; 
        border-radius: 4px; 
        cursor: pointer; 
        font-size: 18px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        padding: 0; 
        transition: background 0.15s; 
    }
    
    .zoom-btn:hover { 
        background: rgba(255,255,255,0.3); 
    }
    
    .zoom-label { 
        font-size: 12px; 
        color: #d0e1f9; 
        min-width: 36px; 
        text-align: center; 
    }

    /* Mobile Responsive */
    @media (max-width: 1200px) {
        .nav-menu a { 
            padding: 15px 10px; 
            font-size: 13px; 
        }
    }

    @media (max-width: 768px) {
        .hamburger { 
            display: flex; 
        }
        
        .nav-menu { 
            position: fixed; 
            top: 50px; 
            left: 0; 
            right: 0; 
            background: #004494; 
            flex-direction: column; 
            align-items: stretch; 
            max-height: 0; 
            overflow: hidden; 
            transition: max-height 0.3s ease; 
            z-index: 999;
            gap: 0;
        }
        
        .nav-menu.active { 
            max-height: 600px; 
            overflow-y: auto; 
        }
        
        .nav-menu a { 
            padding: 14px 15px; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
            font-size: 15px;
            margin: 0;
        }
        
        .nav-menu a:hover { 
            background: rgba(255,255,255,0.15); 
        }
        
        .nav-menu .user-info { 
            padding: 14px 15px; 
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin: 0;
        }
        
        .nav-menu .btn-logout { 
            padding: 14px 15px; 
            border-radius: 0; 
            margin: 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .zoom-controls { 
            padding: 14px 15px; 
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-left: 0;
            justify-content: flex-start;
        }
    }

    @media (max-width: 480px) {
        .navbar-brand { 
            padding: 10px 12px; 
            font-size: 16px; 
        }
        
        .hamburger { 
            padding: 8px 12px; 
            font-size: 24px; 
        }
        
        .nav-menu a { 
            padding: 12px 15px; 
            font-size: 14px;
        }
    }
</style>

<!-- Apply saved zoom before page renders -->
<script>
    (function() {
        var z = <?= $user_zoom ?>;
        document.documentElement.style.zoom = z + '%';
    })();

    var _currentZoom = <?= $user_zoom ?>;

    function toggleMenu() {
        var menu = document.getElementById('navMenu');
        menu.classList.toggle('active');
    }

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

    // Close menu when a link is clicked
    document.addEventListener('DOMContentLoaded', function() {
        var links = document.querySelectorAll('#navMenu a');
        links.forEach(function(link) {
            link.addEventListener('click', function() {
                document.getElementById('navMenu').classList.remove('active');
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            var menu = document.getElementById('navMenu');
            var hamburger = document.querySelector('.hamburger');
            if (menu && hamburger && !menu.contains(event.target) && !hamburger.contains(event.target)) {
                menu.classList.remove('active');
            }
        });
    });
</script>

<div class="navbar">
    <div class="navbar-brand"><a href="dashboard.php">Dashboard</a></div>
    <button class="hamburger" onclick="toggleMenu()" title="Menu">☰</button>
    <div class="nav-menu" id="navMenu">
        <?php foreach ($nav_items as $item): ?>
            <a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a>
        <?php endforeach; ?>
        
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
