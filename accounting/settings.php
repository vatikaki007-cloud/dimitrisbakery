<?php
/**
 * settings.php
 * Manage Business Details and Financial Years.
 */
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pdo = get_db();

// Ensure settings table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS acc_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Ensure default settings exist
$defaults = [
    'bus_name' => 'DIMITRIS CONFECTIONERY/BAKERY',
    'bus_address_left' => "120 VOORTREKKER ROAD\nGOODWOOD",
    'bus_address_mid' => "P O BOX 1291\nSANLAMHOF\nBELLVILLE",
    'bus_vat' => '4410265468',
    'bus_phone' => '079 9815410',
    'bus_ordering_no' => '0799815410',
    'bus_bank_info' => "BANK ...CAPITEC BUSINESS\nA/C 1053011342",
    'bus_halaal_no' => '55693'
];
$check = $pdo->query("SELECT COUNT(*) FROM acc_settings")->fetchColumn();
if ($check == 0) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO acc_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $k => $v) { $stmt->execute([$k, $v]); }
}

// Ensure financial years table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS acc_financial_years (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Handle Form Submissions
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_business'])) {
        $stmt = $pdo->prepare("UPDATE acc_settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($_POST['settings'] as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        $msg = "Business details updated successfully.";
    }

    if (isset($_POST['add_fy'])) {
        $name = $_POST['fy_name'];
        $start = $_POST['fy_start'];
        $end = $_POST['fy_end'];
        $stmt = $pdo->prepare("INSERT INTO acc_financial_years (name, start_date, end_date) VALUES (?, ?, ?)");
        $stmt->execute([$name, $start, $end]);
        $msg = "Financial year added.";
    }

    if (isset($_POST['delete_fy'])) {
        $id = $_POST['delete_fy'];
        $pdo->prepare("DELETE FROM acc_financial_years WHERE id = ?")->execute([$id]);
        $msg = "Financial year deleted.";
    }
}

// Fetch Settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM acc_settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch Financial Years
$stmt = $pdo->query("SELECT * FROM acc_financial_years ORDER BY start_date DESC");
$financial_years = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Accounting Settings</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; }
        .container { max-width: 1000px; margin: 20px auto; padding: 20px; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 8px; }
        .tabs { display: flex; border-bottom: 2px solid #eee; margin-bottom: 20px; }
        .tab { padding: 10px 20px; cursor: pointer; border-bottom: 2px solid transparent; }
        .tab.active { border-bottom: 2px solid #0056b3; font-weight: bold; color: #0056b3; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px; }
        input[type="text"], input[type="date"], textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 80px; font-family: inherit; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-primary { background: #0056b3; color: white; }
        .btn-danger { background: #dc3545; color: white; padding: 5px 10px; font-size: 12px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #eee; padding: 10px; text-align: left; }
        th { background: #f9f9f9; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Settings</h2>
        
        <?php if ($msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" onclick="showTab('business')">Business Details</div>
            <div class="tab" onclick="showTab('financial')">Financial Years</div>
        </div>

        <!-- Business Details Tab -->
        <div id="business" class="tab-content active">
            <form method="POST">
                <input type="hidden" name="save_business" value="1">
                <div class="form-group">
                    <label>Business Name</label>
                    <input type="text" name="settings[bus_name]" value="<?= htmlspecialchars($settings['bus_name'] ?? '') ?>">
                </div>
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex:1;">
                        <label>Left Address (e.g. Street)</label>
                        <textarea name="settings[bus_address_left]"><?= htmlspecialchars($settings['bus_address_left'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Middle Address (e.g. Box)</label>
                        <textarea name="settings[bus_address_mid]"><?= htmlspecialchars($settings['bus_address_mid'] ?? '') ?></textarea>
                    </div>
                </div>
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex:1;">
                        <label>VAT Number</label>
                        <input type="text" name="settings[bus_vat]" value="<?= htmlspecialchars($settings['bus_vat'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Office Phone</label>
                        <input type="text" name="settings[bus_phone]" value="<?= htmlspecialchars($settings['bus_phone'] ?? '') ?>">
                    </div>
                </div>
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex:1;">
                        <label>Ordering Number</label>
                        <input type="text" name="settings[bus_ordering_no]" value="<?= htmlspecialchars($settings['bus_ordering_no'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Halaal No</label>
                        <input type="text" name="settings[bus_halaal_no]" value="<?= htmlspecialchars($settings['bus_halaal_no'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Bank Details</label>
                    <textarea name="settings[bus_bank_info]"><?= htmlspecialchars($settings['bus_bank_info'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Business Details</button>
            </form>
        </div>

        <!-- Financial Years Tab -->
        <div id="financial" class="tab-content">
            <form method="POST" style="background: #f9f9f9; padding: 15px; border-radius: 8px;">
                <h4>Add New Financial Year</h4>
                <div style="display: flex; gap: 10px;">
                    <div style="flex:2;"><label>Name</label><input type="text" name="fy_name" placeholder="2024-2025" required></div>
                    <div style="flex:1;"><label>Start Date</label><input type="date" name="fy_start" required></div>
                    <div style="flex:1;"><label>End Date</label><input type="date" name="fy_end" required></div>
                </div>
                <button type="submit" name="add_fy" class="btn btn-primary" style="margin-top:10px;">Add Year</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($financial_years as $fy): ?>
                    <tr>
                        <td><?= htmlspecialchars($fy['name']) ?></td>
                        <td><?= htmlspecialchars($fy['start_date']) ?></td>
                        <td><?= htmlspecialchars($fy['end_date']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this year?')">
                                <button type="submit" name="delete_fy" value="<?= $fy['id'] ?>" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function showTab(id) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
