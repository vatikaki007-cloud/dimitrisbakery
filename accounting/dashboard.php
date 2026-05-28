<?php require_once __DIR__ . '/navbar.php'; 

$pdo = get_db();

// Handle Financial Year Selection
if (isset($_POST['fy_id'])) {
    $_SESSION['acc_fy_id'] = $_POST['fy_id'];
}

$all_fys = $pdo->query("SELECT * FROM acc_financial_years ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Determine active FY
$active_fy_id = $_SESSION['acc_fy_id'] ?? '';
$active_fy = null;

if ($active_fy_id) {
    foreach ($all_fys as $f) {
        if ($f['id'] == $active_fy_id) {
            $active_fy = $f;
            break;
        }
    }
}

// Default to current date if no FY selected or found
if (!$active_fy && !empty($all_fys)) {
    $today = date('Y-m-d');
    foreach ($all_fys as $f) {
        if ($today >= $f['start_date'] && $today <= $f['end_date']) {
            $active_fy = $f;
            $_SESSION['acc_fy_id'] = $f['id'];
            break;
        }
    }
    // If still not found, take the most recent one
    if (!$active_fy) {
        $active_fy = $all_fys[0];
        $_SESSION['acc_fy_id'] = $active_fy['id'];
    }
}

// Build where clause for FY
$where_fy = "";
if ($active_fy) {
    $where_fy = " AND date BETWEEN '{$active_fy['start_date']}' AND '{$active_fy['end_date']}'";
}

// Fetch stats
$total_customers = $pdo->query("SELECT COUNT(*) FROM acc_customers")->fetchColumn();

// Filtered stats
$total_invoices = $pdo->query("SELECT COUNT(*) FROM acc_invoices WHERE 1=1 $where_fy")->fetchColumn();
$unpaid_invoices = $pdo->query("SELECT COUNT(*) FROM acc_invoices WHERE status = 'unpaid' $where_fy")->fetchColumn();
$total_sales = $pdo->query("SELECT SUM(total) FROM acc_invoices WHERE 1=1 $where_fy")->fetchColumn() ?: 0;
$unpaid_amt = $pdo->query("SELECT SUM(total) FROM acc_invoices WHERE status = 'unpaid' $where_fy")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Dashboard</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h1 { color: #333; margin: 0; }
        .fy-selector { background: white; padding: 10px 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .fy-selector select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; font-size: 14px; }
        
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 30px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; border-top: 4px solid #0056b3; }
        .card h3 { margin: 0 0 10px 0; color: #555; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        .card .stat { font-size: 36px; font-weight: bold; color: #333; margin-bottom: 15px; }
        .card a { display: inline-block; background: #eef2f9; color: #0056b3; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: 500; }
        .card a:hover { background: #d0e1f9; }
        
        .fy-info { font-size: 14px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['acc_username']); ?>!</h1>
                <?php if ($active_fy): ?>
                    <div class="fy-info">Showing data for: <strong><?= htmlspecialchars($active_fy['name']) ?></strong> (<?= $active_fy['start_date'] ?> to <?= $active_fy['end_date'] ?>)</div>
                <?php else: ?>
                    <div class="fy-info">Showing All Time Data (No Financial Year Defined)</div>
                <?php endif; ?>
            </div>
            
            <div class="fy-selector">
                <form method="POST" id="fyForm">
                    <label style="font-size: 12px; font-weight: bold; color: #888; display: block; margin-bottom: 4px;">Select Financial Year</label>
                    <select name="fy_id" onchange="document.getElementById('fyForm').submit()">
                        <option value="">All Time</option>
                        <?php foreach ($all_fys as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= $active_fy_id == $f['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <div class="card-grid">
            <div class="card">
                <h3>Total Sales (FY)</h3>
                <div class="stat">R <?= number_format($total_sales, 2); ?></div>
                <div style="font-size: 12px; color: #888; margin-bottom: 10px;"><?= number_format($total_invoices); ?> Invoices</div>
                <a href="invoices.php">View Invoices</a>
            </div>
            
            <div class="card" style="border-top-color: #ff4d4d;">
                <h3>Total Unpaid (FY)</h3>
                <div class="stat" style="color: #d9534f;">R <?= number_format($unpaid_amt, 2); ?></div>
                <div style="font-size: 12px; color: #888; margin-bottom: 10px;"><?= number_format($unpaid_invoices); ?> Pending</div>
                <a href="invoices.php?status=unpaid" style="color: #d9534f; background: #fdf7f7;">View Unpaid</a>
            </div>

            <div class="card" style="border-top-color: #6c757d;">
                <h3>Total Customers</h3>
                <div class="stat"><?php echo number_format($total_customers); ?></div>
                <div style="font-size: 12px; color: #888; margin-bottom: 10px;">Lifetime total</div>
                <a href="customers.php">Manage Customers</a>
            </div>

            <div class="card" style="border-top-color: #28a745;">
                <h3>Quick Actions</h3>
                <div class="stat">+</div>
                <div style="font-size: 12px; color: #888; margin-bottom: 10px;">New Transaction</div>
                <a href="invoice_create.php" style="background: #e8f5e9; color: #28a745;">Create New Invoice</a>
            </div>
        </div>
    </div>
</body>
</html>
