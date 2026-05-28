<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$pdo = get_db();

// Ensure database columns exist
try { $pdo->exec("ALTER TABLE acc_invoices ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"); } catch(\Exception $e){}
try { $pdo->exec("ALTER TABLE acc_invoices ADD COLUMN bakers_sheet_id VARCHAR(50) DEFAULT NULL"); } catch(\Exception $e){}

// Handle AJAX Line Update
if (isset($_GET['ajax_update_line'])) {
    header('Content-Type: application/json');
    try {
        $line_id = $_POST['line_id'] ?? 0;
        $qty = $_POST['qty'] ?? 0;
        
        if (!$line_id) throw new Exception("Invalid Line ID");
        
        $pdo->beginTransaction();
        
        $stmt_line = $pdo->prepare("SELECT * FROM acc_invoice_lines WHERE id = ?");
        $stmt_line->execute([$line_id]);
        $line = $stmt_line->fetch(PDO::FETCH_ASSOC);
        
        if ($line) {
            $unit_price = (float)$line['unit_price'];
            $disc_percent = (float)$line['disc_percent'];
            $invoice_id = $line['invoice_id'];
            
            $discount_amount = $unit_price * ($disc_percent / 100);
            $price_after_disc = $unit_price - $discount_amount;
            $nett_price = (float)$qty * $price_after_disc;
            
            $pdo->prepare("UPDATE acc_invoice_lines SET quantity = ?, nett_price = ? WHERE id = ?")
                ->execute([$qty, $nett_price, $line_id]);
                
            $stmt_lines = $pdo->prepare("SELECT SUM(nett_price) as total_nett, SUM(nett_price * (tax_percent/100)) as total_tax FROM acc_invoice_lines WHERE invoice_id = ?");
            $stmt_lines->execute([$invoice_id]);
            $totals = $stmt_lines->fetch(PDO::FETCH_ASSOC);
            
            $total_nett = $totals['total_nett'] ?: 0;
            $total_tax = $totals['total_tax'] ?: 0;
            $grand_total = $total_nett + $total_tax;
            
            $pdo->prepare("UPDATE acc_invoices SET total_nett = ?, amount_excl = ?, tax = ?, total = ? WHERE id = ?")
                ->execute([$total_nett, $total_nett, $total_tax, $grand_total, $invoice_id]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Handle AJAX Inline Dispatch
if (isset($_GET['ajax_inline_dispatch'])) {
    header('Content-Type: application/json');
    try {
        $invoice_id = $_POST['invoice_id'] ?? 0;
        $lines = $_POST['lines'] ?? []; // format: [line_id => new_quantity]
        
        if (!$invoice_id) throw new Exception("Invalid Invoice ID");
        
        $pdo->beginTransaction();
        
        $total_nett = 0;
        $total_tax = 0;
        
        // Fetch current lines
        $stmt_lines = $pdo->prepare("SELECT * FROM acc_invoice_lines WHERE invoice_id = ?");
        $stmt_lines->execute([$invoice_id]);
        $db_lines = $stmt_lines->fetchAll(PDO::FETCH_ASSOC);
        
        $update_line = $pdo->prepare("UPDATE acc_invoice_lines SET quantity = ?, nett_price = ? WHERE id = ?");
        
        foreach ($db_lines as $line) {
            $line_id = $line['id'];
            // If new quantity provided, use it, else keep old
            $qty = isset($lines[$line_id]) ? (float)$lines[$line_id] : (float)$line['quantity'];
            
            // Recalculate nett_price for this line
            $unit_price = (float)$line['unit_price'];
            $disc_percent = (float)$line['disc_percent'];
            $tax_percent = (float)$line['tax_percent'];
            
            $discount_amount = $unit_price * ($disc_percent / 100);
            $price_after_disc = $unit_price - $discount_amount;
            $nett_price = $qty * $price_after_disc;
            
            $line_tax = $nett_price * ($tax_percent / 100);
            
            $total_nett += $nett_price;
            $total_tax += $line_tax;
            
            $update_line->execute([$qty, $nett_price, $line_id]);
        }
        
        // Update Invoice totals and set to unpaid
        // Note: we assume invoice discount is 0 for simplicity, or we could fetch it.
        // The simplest approach is total_discount = 0, amount_excl = total_nett
        $amount_excl = $total_nett;
        $grand_total = $amount_excl + $total_tax;
        
        $pdo->prepare("UPDATE acc_invoices SET total_nett = ?, amount_excl = ?, tax = ?, total = ?, status = 'unpaid' WHERE id = ?")
            ->execute([$total_nett, $amount_excl, $total_tax, $grand_total, $invoice_id]);
            
        $pdo->commit();
        echo json_encode(['success' => true, 'invoice_id' => $invoice_id]);
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

require_once __DIR__ . '/navbar.php';

// Handle Dismiss (Skip for today)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'dismiss_customer') {
    $customer_id = $_POST['customer_id'] ?? 0;
    if ($customer_id) {
        try {
            $pdo->prepare("INSERT IGNORE INTO acc_daily_skips (customer_id, skip_date) VALUES (?, CURDATE())")
                ->execute([$customer_id]);
        } catch (\Exception $e) {}
    }
    header("Location: orders_dashboard.php");
    exit;
}

// Handle Archive Production Sheet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive_bakers') {
    $sheet_id = 'Sheet-' . date('Ymd-His');
    $pdo->prepare("
        UPDATE acc_invoices SET bakers_sheet_id = ? 
        WHERE type = 'customer' AND bakers_sheet_id IS NULL 
        AND (status = 'order' OR DATE(updated_at) = CURDATE())
    ")->execute([$sheet_id]);
    
    header("Location: orders_dashboard.php?archived=" . urlencode($sheet_id));
    exit;
}

// Handle Archive Date
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive_date') {
    $date_to_archive = $_POST['date'] ?? null;
    if ($date_to_archive) {
        $sheet_id = 'Archived-' . $date_to_archive . '-' . date('His');
        $pdo->prepare("
            UPDATE acc_invoices SET bakers_sheet_id = ? 
            WHERE type = 'customer' AND date = ? AND bakers_sheet_id IS NULL
        ")->execute([$sheet_id, $date_to_archive]);
    }
    header("Location: orders_dashboard.php");
    exit;
}

// Handle Reset All Data (Start Fresh)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_all') {
    $pdo->exec("DELETE FROM acc_invoice_lines");
    $pdo->exec("DELETE FROM acc_invoices");
    $pdo->exec("DELETE FROM acc_daily_skips");
    header("Location: orders_dashboard.php?reset=success");
    exit;
}

// Get active dates for tabs (dates with un-archived invoices)
$dates_stmt = $pdo->query("
    SELECT DISTINCT date 
    FROM acc_invoices 
    WHERE bakers_sheet_id IS NULL AND type = 'customer'
    ORDER BY date ASC
");
$active_dates = $dates_stmt->fetchAll(PDO::FETCH_COLUMN);

// Ensure today is always an option
$today = date('Y-m-d');
if (!in_array($today, $active_dates)) {
    $active_dates[] = $today;
    sort($active_dates);
}

$current_tab = $_GET['tab'] ?? $active_dates[0];

// Fetch all routes
$routes = $pdo->query("SELECT * FROM acc_routes ORDER BY route_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 1. Pending Orders for Selected Date
$stmt_orders = $pdo->prepare("
    SELECT i.*, c.name as customer_name, c.route_id 
    FROM acc_invoices i 
    JOIN acc_customers c ON i.entity_id = c.id 
    WHERE i.status = 'order' AND i.type = 'customer' AND i.date = ? AND i.bakers_sheet_id IS NULL
    ORDER BY c.name ASC
");
$stmt_orders->execute([$current_tab]);
$all_orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch lines for all pending orders
$order_ids = array_column($all_orders, 'id');
$lines_by_invoice = [];
$production_totals = [];

if (!empty($order_ids)) {
    $in = str_repeat('?,', count($order_ids) - 1) . '?';
    $stmt_lines = $pdo->prepare("SELECT * FROM acc_invoice_lines WHERE invoice_id IN ($in)");
    $stmt_lines->execute($order_ids);
    $all_lines = $stmt_lines->fetchAll(PDO::FETCH_ASSOC);
    
    // For each line, check if it has price history for the customer
    foreach ($all_lines as $line) {
        $invoice_id = $line['invoice_id'];
        $customer_id = null;
        
        // Find customer_id from the order
        foreach ($all_orders as $order) {
            if ($order['id'] == $invoice_id) {
                $customer_id = $order['entity_id'];
                break;
            }
        }
        
        // Check if this product has been invoiced before for this customer (completed invoices or current order with price)
        if ($customer_id) {
            // First check: does this line have a price in the current invoice?
            $has_price_in_current = (float)$line['unit_price'] > 0;
            
            // Second check: has this product been invoiced before (INV- invoices only)?
            $stmt_check = $pdo->prepare("
                SELECT COUNT(*) as count FROM acc_invoice_lines l
                JOIN acc_invoices i ON l.invoice_id = i.id
                WHERE i.entity_id = ? AND l.code = ? AND i.id != ? AND i.invoice_no LIKE 'INV-%'
            ");
            $stmt_check->execute([$customer_id, $line['code'], $invoice_id]);
            $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
            $has_price_in_history = $result['count'] > 0;
            
            // Show NEED TO PHONE only if no price in current line AND no price history
            $line['has_price'] = $has_price_in_current || $has_price_in_history;
        } else {
            $line['has_price'] = true;
        }
        
        $lines_by_invoice[$invoice_id][] = $line;
    }
}

// Fetch Bakers Sheet Totals for Selected Date
$sheet_title = "Production Sheet for " . date('D, d M Y', strtotime($current_tab));

$stmt_bakers = $pdo->prepare("
    SELECT l.code, l.description, SUM(l.quantity) as total_qty
    FROM acc_invoice_lines l
    JOIN acc_invoices i ON l.invoice_id = i.id
    WHERE i.type = 'customer' AND i.date = ? AND i.bakers_sheet_id IS NULL AND i.status = 'order'
    GROUP BY l.code, l.description
    HAVING total_qty > 0
    ORDER BY l.code ASC
");
$stmt_bakers->execute([$current_tab]);
$production_totals_db = $stmt_bakers->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent archived sheets for the sidebar/footer
$archived_sheets = $pdo->query("
    SELECT DISTINCT bakers_sheet_id 
    FROM acc_invoices 
    WHERE bakers_sheet_id IS NOT NULL 
    ORDER BY bakers_sheet_id DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_COLUMN);

$production_totals = [];
foreach ($production_totals_db as $pt) {
    $key = $pt['code'] . '|||' . $pt['description'];
    $production_totals[$key] = [
        'code' => $pt['code'],
        'description' => $pt['description'],
        'total_qty' => $pt['total_qty']
    ];
}

$orders_by_route = [];
foreach ($all_orders as $o) {
    $rid = $o['route_id'] ?: 0;
    $orders_by_route[$rid][] = $o;
}

// 3. Customers needing a call for the SELECTED date
$stmt_calls = $pdo->prepare("
    SELECT c.* 
    FROM acc_customers c
    WHERE c.id NOT IN (
        SELECT entity_id FROM acc_invoices WHERE type = 'customer' AND date = ?
    )
    AND c.id NOT IN (
        SELECT customer_id FROM acc_daily_skips WHERE skip_date = ?
    )
    ORDER BY c.name ASC
");
$stmt_calls->execute([$current_tab, $current_tab]);
$all_calls = $stmt_calls->fetchAll(PDO::FETCH_ASSOC);

$calls_by_route = [];
foreach ($all_calls as $c) {
    $rid = $c['route_id'] ?: 0;
    $calls_by_route[$rid][] = $c;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Orders Dashboard</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; padding-bottom: 50px; }
        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        h2 { color: #333; margin-bottom: 20px; font-size: 24px; }
        
        .route-section { background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 30px; overflow: hidden; }
        .route-header { background: #eef2f5; padding: 15px 20px; border-bottom: 1px solid #d1d9e0; font-size: 18px; font-weight: bold; color: #0056b3; }
        
        .route-body { display: flex; }
        .col { flex: 1; padding: 20px; }
        .col-orders { border-right: 1px solid #eee; background: #fff; }
        .col-calls { background: #fcfcfc; }
        
        .col-title { font-size: 14px; font-weight: bold; color: #555; text-transform: uppercase; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .badge-count { background: #6c757d; color: white; border-radius: 10px; padding: 2px 8px; font-size: 11px; }
        
        .item-list { list-style: none; padding: 0; margin: 0; }
        .item { padding: 12px 15px; border: 1px solid #eee; border-radius: 4px; margin-bottom: 8px; display: flex; flex-direction: column; background: white; transition: background 0.2s; }
        .item:hover { background: #f8f9fa; }
        
        /* Order Items */
        .order-item { border-left: 4px solid #17a2b8; }
        .order-name { font-weight: bold; color: #333; font-size: 15px; margin-bottom: 10px; display: flex; justify-content: space-between; }
        .order-lines { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 13px; }
        .order-lines td { padding: 4px 0; border-bottom: 1px dashed #eee; }
        .qty-input { width: 50px; padding: 4px; border: 1px solid #ccc; border-radius: 3px; text-align: center; }
        
        .btn-dispatch { background: #17a2b8; color: white; padding: 8px 15px; border-radius: 4px; border: none; cursor: pointer; font-weight: bold; font-size: 13px; flex: 1; }
        .btn-dispatch:hover { background: #138496; }
        .btn-dispatch:disabled { background: #ccc; color: #666; cursor: not-allowed; opacity: 0.6; }
        .btn-edit { background: #6c757d; color: white; padding: 8px 15px; border-radius: 4px; border: none; cursor: pointer; text-decoration: none; font-size: 13px; text-align: center; }
        .btn-edit:hover { background: #5a6268; }
        
        /* Call Items */
        .call-item { border-left: 4px solid #dc3545; flex-direction: row; justify-content: space-between; align-items: center; }
        .call-info { flex: 1; }
        .call-name { font-weight: bold; color: #333; font-size: 15px; }
        .call-meta { font-size: 12px; color: #888; margin-top: 4px; }
        .call-actions { display: flex; gap: 8px; }
        
        .btn-dismiss { background: #e9ecef; color: #555; border: 1px solid #ccc; padding: 5px 10px; border-radius: 4px; font-size: 12px; cursor: pointer; }
        .btn-dismiss:hover { background: #dde2e6; }
        
        .btn-create { background: #28a745; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; border: none; cursor: pointer; }
        .btn-create:hover { background: #218838; }

        .empty-state { padding: 20px; text-align: center; color: #aaa; font-size: 13px; font-style: italic; background: #fafafa; border-radius: 4px; border: 1px dashed #ddd; }

        /* Tabs */
        .tabs { display: flex; gap: 5px; margin-bottom: 20px; border-bottom: 1px solid #ddd; }
        .tab-item { padding: 10px 20px; background: #eef2f5; border: 1px solid #ddd; border-bottom: none; border-radius: 8px 8px 0 0; text-decoration: none; color: #555; font-weight: bold; font-size: 14px; }
        .tab-item:hover { background: #fff; color: #0056b3; }
        .tab-item.active { background: #fff; color: #0056b3; border-top: 3px solid #0056b3; margin-top: -3px; height: 100%; padding-bottom: 11px; margin-bottom: -1px; z-index: 10; }

        /* Bakers Sheet */
        .bakers-sheet { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-top: 40px; border: 2px solid #333; }
        .bakers-sheet h3 { margin-top: 0; color: #333; display: flex; justify-content: space-between; align-items: center; }
        .bakers-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .bakers-table th, .bakers-table td { border-bottom: 1px solid #ccc; padding: 10px; text-align: left; }
        .bakers-table th { background: #eee; }
        .bakers-table .qty-input { width: 70px; font-size: 16px; font-weight: bold; }
        
        .btn-print-sheet { background: #333; color: white; padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-print-sheet:hover { background: #000; }

        /* Print Media Queries */
        @media print {
            body * { visibility: hidden; }
            .bakers-sheet, .bakers-sheet * { visibility: visible; }
            .bakers-sheet { position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; margin: 0; padding: 0; }
            .bakers-sheet h3 div { display: none; }
            .past-sheets-list { display: none; }
            .qty-input { border: none !important; padding: 0 !important; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/navbar.php'; ?>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Daily Orders Dashboard</h2>
            <div style="font-size: 14px; color: #555;">Server Date: <strong><?= date('l, d M Y') ?></strong></div>
        </div>

        <div class="tabs">
            <?php foreach ($active_dates as $ad): 
                $label = $ad;
                if ($ad == date('Y-m-d')) $label = "Today";
                elseif ($ad == date('Y-m-d', strtotime('-1 day'))) $label = "Yesterday";
                else $label = date('D, d M', strtotime($ad));
            ?>
                <a href="?tab=<?= $ad ?>" class="tab-item <?= $current_tab == $ad ? 'active' : '' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>


        <?php foreach ($routes as $route): 
            $rid = $route['id'];
            $orders = $orders_by_route[$rid] ?? [];
            $calls = $calls_by_route[$rid] ?? [];
        ?>
            <div class="route-section">
                <div class="route-header">
                    <?= htmlspecialchars($route['route_name']) ?>
                </div>
                <div class="route-body">
                    <!-- Need to Phone -->
                    <div class="col col-calls">
                        <div class="col-title" style="color: #dc3545;">
                            Need to Phone
                            <span class="badge-count" style="background: <?= count($calls) > 0 ? '#dc3545' : '#6c757d' ?>"><?= count($calls) ?></span>
                        </div>
                        
                        <?php if (empty($calls)): ?>
                            <div class="empty-state">All customers checked off!</div>
                        <?php else: ?>
                            <ul class="item-list">
                                <?php foreach ($calls as $c): ?>
                                    <li class="item call-item">
                                        <div class="call-info">
                                            <div class="call-name"><?= htmlspecialchars($c['name']) ?></div>
                                            <?php if ($c['telephone']): ?>
                                                <div class="call-meta">📞 <?= htmlspecialchars($c['telephone']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="call-actions">
                                            <a href="invoice_create.php?new_customer_id=<?= $c['id'] ?>" class="btn-create" style="display:inline-block; text-align:center;">+ Order</a>
                                            <form method="POST" style="margin:0;">
                                                <input type="hidden" name="action" value="dismiss_customer">
                                                <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
                                                <button type="submit" class="btn-dismiss">Skip Today</button>
                                            </form>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- Orders Placed -->
                    <div class="col col-orders">
                        <div class="col-title">
                            Orders Placed
                            <span class="badge-count" style="background: <?= count($orders) > 0 ? '#17a2b8' : '#6c757d' ?>"><?= count($orders) ?></span>
                        </div>
                        
                        <?php if (empty($orders)): ?>
                            <div class="empty-state">No pending orders for this route.</div>
                        <?php else: ?>
                            <ul class="item-list">
                                <?php foreach ($orders as $o): ?>
                                    <li class="item order-item" id="order-box-<?= $o['id'] ?>">
                                        <div class="order-name">
                                            <span><?= htmlspecialchars($o['customer_name']) ?></span>
                                            <span style="font-size:12px; font-weight:normal; color:#888;">Inv: <?= htmlspecialchars($o['invoice_no']) ?></span>
                                        </div>
                                        <form class="inline-dispatch-form" onsubmit="dispatchOrder(event, <?= $o['id'] ?>)">
                                            <input type="hidden" name="invoice_id" value="<?= $o['id'] ?>">
                                            <table class="order-lines">
                                                <?php 
                                                $lines = $lines_by_invoice[$o['id']] ?? [];
                                                $has_missing_prices = false;
                                                if(empty($lines)): ?>
                                                    <tr><td colspan="3" style="color:#aaa; font-style:italic;">No items found.</td></tr>
                                                <?php else: 
                                                    foreach ($lines as $line): 
                                                        $has_price = $line['has_price'] ?? true;
                                                        if (!$has_price) $has_missing_prices = true;
                                                    ?>
                                                    <tr>
                                                        <td style="width: 60px;">
                                                            <input type="number" step="0.01" name="lines[<?= $line['id'] ?>]" class="qty-input" value="<?= number_format((float)$line['quantity'], 2, '.', '') ?>" min="0" onfocus="this.select(); this.dataset.oldQty = this.value;" onchange="updateLineQty(this, <?= $line['id'] ?>, '<?= htmlspecialchars($line['code'], ENT_QUOTES) ?>')" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); this.blur(); }">
                                                        </td>
                                                        <td><?= htmlspecialchars($line['description']) ?></td>
                                                        <td style="text-align: right; width: 100px;">
                                                            <?php if (!$has_price): ?>
                                                                <span style="color: #dc3545; font-weight: bold; font-size: 12px;">NEED TO PHONE</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; endif; ?>
                                            </table>
                                            <div style="display:flex; gap:10px; margin-top:10px;">
                                                <button type="submit" class="btn-dispatch" <?= $has_missing_prices ? 'disabled' : '' ?> title="<?= $has_missing_prices ? 'Cannot finalize: items need pricing' : 'Print and finalize this order' ?>">🖨 Print & Finalize</button>
                                                <a href="invoice_create.php?edit_id=<?= $o['id'] ?>&from_orders=1" class="btn-edit">✎ Edit</a>
                                            </div>
                                            <?php if ($has_missing_prices): ?>
                                                <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin-top: 10px; font-size: 12px; border: 1px solid #ffeeba;">
                                                    ⚠️ <strong>Cannot finalize:</strong> Some items need pricing. Click Edit to add prices before finalizing.
                                                </div>
                                            <?php endif; ?>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($routes)): ?>
            <div style="padding: 50px; text-align: center; color: #888; background: white; border-radius: 8px;">
                You haven't set up any Delivery Routes yet.<br><br>
                <a href="routes.php" style="color: #0056b3; font-weight: bold; text-decoration: none;">Set up Routes here</a>
            </div>
        <?php endif; ?>

        <!-- BAKERS PRODUCTION SHEET -->
        <div class="bakers-sheet">
            <h3>
                <?= $sheet_title ?>
                <div style="display: flex; gap: 10px;">
                    <form method="POST" onsubmit="return confirm('This will archive all orders for <?= $current_tab ?> and close this tab. Proceed?');" style="margin:0;">
                        <input type="hidden" name="action" value="archive_date">
                        <input type="hidden" name="date" value="<?= $current_tab ?>">
                        <button type="submit" class="btn-print-sheet" style="background: #dc3545;">📁 Close Day & Archive</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('WARNING: This will delete ALL orders, invoices, and skip records. Proceed?');" style="margin:0;">
                        <input type="hidden" name="action" value="reset_all">
                        <button type="submit" class="btn-print-sheet" style="background: #000; border: 1px solid #ff4d4d; color: #ff4d4d;">🔥 RESET ALL DATA</button>
                    </form>
                    <button class="btn-print-sheet" onclick="window.print()">🖨 Print Bakers Sheet</button>
                </div>
            </h3>
            <?php if (empty($production_totals)): ?>
                <div class="empty-state">No pending orders to produce.</div>
            <?php else: ?>
                <table class="bakers-table">
                    <thead>
                        <tr>
                            <th style="width: 15%">Code</th>
                            <th style="width: 60%">Product Description</th>
                            <th style="width: 25%">Total Required</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($production_totals as $pt): ?>
                            <tr>
                                <td><?= htmlspecialchars($pt['code']) ?></td>
                                <td style="font-weight:bold; font-size:16px;"><?= htmlspecialchars($pt['description']) ?></td>
                                <td>
                                    <input type="number" step="0.01" id="baker-qty-<?= htmlspecialchars($pt['code'], ENT_QUOTES) ?>" class="qty-input" value="<?= number_format($pt['total_qty'], 2, '.', '') ?>" onfocus="this.select()" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); this.blur(); }">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- PAST SHEETS FOOTER -->
            <?php if (!empty($archived_sheets)): ?>
                <div class="past-sheets-list" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 13px; color: #666;">
                    <strong>Recently Archived Sheets (for reprinting):</strong>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                        <?php foreach ($archived_sheets as $sheet): ?>
                            <a href="orders_dashboard.php?view_sheet=<?= urlencode($sheet) ?>" style="color: #0056b3; text-decoration: none; padding: 4px 12px; border: 1px solid #d1d9e0; border-radius: 4px; background: #f8f9fa;">
                                <?= htmlspecialchars($sheet) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script>
        function updateLineQty(input, lineId, code) {
            let oldQty = parseFloat(input.dataset.oldQty) || 0;
            let newQty = parseFloat(input.value) || 0;
            let diff = newQty - oldQty;
            
            // update oldQty for next change
            input.dataset.oldQty = newQty;
            
            // update baker's sheet
            let bakerInput = document.getElementById('baker-qty-' + code);
            if (bakerInput) {
                let currentBakerQty = parseFloat(bakerInput.value) || 0;
                bakerInput.value = (currentBakerQty + diff).toFixed(2);
            }
            
            let formData = new FormData();
            formData.append('line_id', lineId);
            formData.append('qty', newQty);
            
            input.style.backgroundColor = '#fff3cd';
            
            fetch('orders_dashboard.php?ajax_update_line=1', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.style.backgroundColor = '#d4edda';
                    setTimeout(() => input.style.backgroundColor = '', 1000);
                } else {
                    alert('Error updating quantity: ' + data.error);
                    input.style.backgroundColor = '#f8d7da';
                    // Revert UI on error
                    input.value = oldQty;
                    input.dataset.oldQty = oldQty;
                    if (bakerInput) bakerInput.value = (parseFloat(bakerInput.value) - diff).toFixed(2);
                }
            })
            .catch(error => {
                alert('Network error occurred.');
                input.style.backgroundColor = '#f8d7da';
            });
        }

        function dispatchOrder(event, invoiceId) {
            event.preventDefault();
            let form = event.target;
            let btn = form.querySelector('.btn-dispatch');
            
            // Visual feedback
            btn.innerHTML = 'Processing...';
            btn.disabled = true;

            let formData = new FormData(form);
            
            fetch('orders_dashboard.php?ajax_inline_dispatch=1', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 1. Open Print Dialog in same tab and return
                    window.location.href = 'print_invoice.php?id=' + invoiceId + '&print=1&return=orders_dashboard.php';
                    let box = document.getElementById('order-box-' + invoiceId);
                    box.style.opacity = '0';
                    setTimeout(() => {
                        box.style.display = 'none';
                        // Optional: decrease badge count
                        let badge = box.closest('.col-orders').querySelector('.badge-count');
                        if (badge) {
                            let count = parseInt(badge.innerText) - 1;
                            badge.innerText = count;
                            if (count <= 0) badge.style.background = '#6c757d';
                        }
                    }, 300);
                } else {
                    alert('Error dispatching order: ' + data.error);
                    btn.innerHTML = '🖨 Print & Finalize';
                    btn.disabled = false;
                }
            })
            .catch(error => {
                alert('Network error occurred.');
                btn.innerHTML = '🖨 Print & Finalize';
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
