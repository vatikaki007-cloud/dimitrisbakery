<?php 
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$pdo = get_db();

// Ensure email_sent column exists
try {
    $pdo->exec("ALTER TABLE acc_invoices ADD COLUMN email_sent TINYINT(1) DEFAULT 0");
} catch (\Exception $e) {}

// Ensure bakers_sheet_id column exists
try {
    $pdo->exec("ALTER TABLE acc_invoices ADD COLUMN bakers_sheet_id VARCHAR(50) DEFAULT NULL");
} catch (\Exception $e) {}

// Ensure updated_at column exists
try {
    $pdo->exec("ALTER TABLE acc_invoices ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
} catch (\Exception $e) {}

// Ensure type column exists
try {
    $pdo->exec("ALTER TABLE acc_invoices ADD COLUMN type ENUM('customer', 'supplier') DEFAULT 'customer'");
} catch (\Exception $e) {}

// Ensure suppliers table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_ref VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) DEFAULT '',
        cc_email VARCHAR(255) DEFAULT '',
        telephone VARCHAR(50) DEFAULT '',
        tax_exempt BOOLEAN DEFAULT 0,
        tax_reference VARCHAR(50) DEFAULT '',
        address TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (\Exception $e) {}

// Handle bulk status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_update_status'])) {
    $selected_invoices = $_POST['selected_invoices'] ?? [];
    $new_status = $_POST['new_status'] ?? '';
    if (!empty($selected_invoices) && in_array($new_status, ['unpaid', 'paid', 'overdue', 'order'])) {
        $in = str_repeat('?,', count($selected_invoices) - 1) . '?';
        $params = array_merge([$new_status], $selected_invoices);
        $stmt = $pdo->prepare("UPDATE acc_invoices SET status = ? WHERE id IN ($in)");
        $stmt->execute($params);
    }
    header("Location: invoices.php");
    exit;
}

// Handle individual status update via AJAX
if (isset($_GET['ajax_status_update'])) {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';
    if ($id && in_array($status, ['unpaid', 'paid', 'overdue', 'order'])) {
        $stmt = $pdo->prepare("UPDATE acc_invoices SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

require_once __DIR__ . '/navbar.php';

// Fetch filters
$year_filter = $_GET['year'] ?? date('Y');
$type_filter = $_GET['type'] ?? 'customer';
$customer_filter = $_GET['customer'] ?? '';
$keyword_filter = $_GET['keyword'] ?? '';
$status_filter = $_GET['status'] ?? '';
$route_filter = $_GET['route'] ?? '';

$where = "1=1";
$params = [];

if ($type_filter !== 'all') {
    $where .= " AND i.type = ?";
    $params[] = $type_filter;
}

if ($year_filter) {
    $where .= " AND YEAR(i.date) = ?";
    $params[] = $year_filter;
}

if ($status_filter) {
    $where .= " AND i.status = ?";
    $params[] = $status_filter;
}

if ($customer_filter) {
    $where .= " AND i.entity_id = ?";
    $params[] = $customer_filter;
}

if ($route_filter && $type_filter === 'customer') {
    $where .= " AND c.route_id = ?";
    $params[] = $route_filter;
}

if ($keyword_filter) {
    $where .= " AND (i.invoice_no LIKE ? OR c.name LIKE ? OR s.name LIKE ?)";
    $params[] = "%$keyword_filter%";
    $params[] = "%$keyword_filter%";
    $params[] = "%$keyword_filter%";
}

$stmt = $pdo->prepare("
    SELECT i.*, 
        COALESCE(c.name, s.name) as customer_name, 
        COALESCE(c.account_ref, s.account_ref) as account_ref 
    FROM acc_invoices i 
    LEFT JOIN acc_customers c ON i.type = 'customer' AND i.entity_id = c.id
    LEFT JOIN acc_suppliers s ON i.type = 'supplier' AND i.entity_id = s.id
    WHERE $where
    ORDER BY i.date DESC, i.id DESC
");
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch contacts for dropdown based on type filter
if ($type_filter === 'supplier') {
    $stmt_cust = $pdo->query("SELECT id, name FROM acc_suppliers ORDER BY name ASC");
    $contacts_label = 'Supplier';
} else {
    $stmt_cust = $pdo->query("SELECT id, name FROM acc_customers ORDER BY name ASC");
    $contacts_label = 'Customer';
}
$customers = $stmt_cust->fetchAll(PDO::FETCH_ASSOC);

// Fetch available years for dropdown
$stmt_years = $pdo->query("SELECT DISTINCT YEAR(date) as yr FROM acc_invoices ORDER BY yr DESC");
$years = $stmt_years->fetchAll(PDO::FETCH_ASSOC);
if (empty($years)) {
    $years = [['yr' => date('Y')]];
}

// Fetch routes for filter
$routes = $pdo->query("SELECT id, route_name FROM acc_routes ORDER BY route_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; padding: 0; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        
        /* Filter bar styles */
        .filter-bar { background: #f8f9fa; padding: 15px; margin-bottom: 20px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap; border-bottom: 1px solid #ddd; border-radius: 4px; }
        .filter-group { display: flex; align-items: center; gap: 8px; }
        .filter-group label { font-size: 13px; color: #333; white-space: nowrap; }
        .filter-group select, .filter-group input { padding: 6px 8px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; }
        .btn-search { padding: 6px 15px; background: #fff; border: 1px solid #ccc; border-radius: 3px; cursor: pointer; font-size: 13px; color: #333; }
        .btn-search:hover { background: #e9ecef; }
        
        .bulk-actions { background: #eef2f5; padding: 10px 15px; margin-bottom: 10px; display: flex; gap: 10px; align-items: center; border-radius: 4px; border: 1px solid #d1d9e0; }

        .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; background: white; font-size: 13px; min-width: 600px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #e9ecef; font-weight: normal; color: #333; border-bottom: 2px solid #ddd; white-space: nowrap; }
        
        /* Striped rows */
        tbody tr:nth-child(odd) { background-color: #f4f8ff; }
        tbody tr:nth-child(even) { background-color: #fff; }

        .badge { padding: 4px 8px; border-radius: 3px; font-size: 11px; color: white; display: inline-block; min-width: 50px; text-align: center; cursor: pointer; position: relative; z-index: 10; }
        .badge-unpaid { background: #f0ad4e; }
        .badge-paid { background: #5cb85c; }
        .badge-overdue { background: #d9534f; }
        .badge-order { background: #17a2b8; }
        
        /* Dropdown Actions */
        .action-dropdown { position: relative; display: inline-block; }
        .action-btn { background: #28a745; color: white; border: none; padding: 5px 10px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: space-between; min-width: 70px; }
        .action-btn::after { content: '▼'; font-size: 10px; margin-left: 5px; }
        .action-btn:hover { background: #218838; }
        .action-menu { display: none; position: fixed; background-color: #fff; min-width: 120px; box-shadow: 0px 4px 8px rgba(0,0,0,0.1); z-index: 1000; border: 1px solid #ddd; }
        .action-menu a, .action-menu button { color: #555; padding: 8px 12px; text-decoration: none; display: block; font-size: 12px; background: none; border: none; text-align: left; width: 100%; cursor: pointer; }
        .action-menu a:hover, .action-menu button:hover { background-color: #f8f9fa; color: #000; }
        .action-dropdown:hover .action-menu { display: block; }
        .action-dropdown:focus-within .action-menu { display: block; }
        
        /* Status Dropdown */
        .status-dropdown { display: none; position: fixed; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.2); z-index: 1001; border: 1px solid #ccc; border-radius: 3px; min-width: 100px; }
        .status-dropdown button { display: block; width: 100%; padding: 6px; border: none; background: none; text-align: left; cursor: pointer; font-size: 11px; }
        .status-dropdown button:hover { background: #f0f0f0; }
        
        .sent-icon { font-size: 14px; color: #0056b3; text-align: center; font-weight: bold; }
        .sent-envelope { font-size: 14px; color: #333; }
        
        .link-blue { color: #0056b3; text-decoration: none; text-transform: uppercase; }
        .link-blue:hover { text-decoration: underline; }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container { margin: 20px auto; padding: 0 15px; }
            .filter-bar { flex-direction: column; align-items: stretch; gap: 10px; padding: 12px; }
            .filter-group { flex-direction: column; align-items: stretch; }
            .filter-group label { margin-bottom: 4px; }
            .filter-group select, .filter-group input { width: 100%; }
            .btn-search { width: 100%; }
            
            table { font-size: 12px; }
            th, td { padding: 8px 10px; }
            .badge { padding: 3px 6px; font-size: 10px; }
            .action-btn { padding: 4px 8px; font-size: 11px; min-width: 60px; }
        }
        
        @media (max-width: 480px) {
            .container { margin: 15px auto; padding: 0 12px; }
            .filter-bar { padding: 10px; }
            
            table { font-size: 11px; }
            th, td { padding: 6px 8px; }
            .link-blue { font-size: 11px; }
            .badge { padding: 2px 5px; font-size: 9px; min-width: 45px; }
            .action-btn { padding: 3px 6px; font-size: 10px; min-width: 55px; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/navbar.php'; ?>
    <form method="GET" class="filter-bar">
        <div class="filter-group">
            <label>Year:</label>
            <select name="year">
                <option value="">All</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y['yr'] ?>" <?= $year_filter == $y['yr'] ? 'selected' : '' ?>><?= $y['yr'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Type:</label>
            <select name="type" onchange="this.form.submit()">
                <option value="customer" <?= $type_filter == 'customer' ? 'selected' : '' ?>>Customer (Default)</option>
                <option value="supplier" <?= $type_filter == 'supplier' ? 'selected' : '' ?>>Supplier</option>
                <option value="all" <?= $type_filter == 'all' ? 'selected' : '' ?>>All</option>
            </select>
        </div>
        
        <?php if ($type_filter === 'customer'): ?>
        <div class="filter-group">
            <label>Route:</label>
            <select name="route" onchange="this.form.submit()">
                <option value="">All Routes</option>
                <?php foreach ($routes as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $route_filter == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['route_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <div class="filter-group">
            <label><?= $contacts_label ?>:</label>
            <select name="customer">
                <option value="">All</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $customer_filter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Status:</label>
            <select name="status">
                <option value="">All</option>
                <option value="unpaid" <?= $status_filter == 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                <option value="paid" <?= $status_filter == 'paid' ? 'selected' : '' ?>>Paid</option>
                <option value="overdue" <?= $status_filter == 'overdue' ? 'selected' : '' ?>>Overdue</option>
                <option value="order" <?= $status_filter == 'order' ? 'selected' : '' ?>>Order (Pending)</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Keyword:</label>
            <input type="text" name="keyword" placeholder="Customer, Invoice #, Item" value="<?= htmlspecialchars($keyword_filter) ?>" style="width: 200px;">
        </div>

        <button type="submit" class="btn-search">Search</button>
    </form>

    <div style="padding: 0 15px;">
        <div class="table-wrapper">
            <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Number</th>
                    <th>Customer</th>
                    <th>Amount Due</th>
                    <th>Total</th>
                    <th><input type="checkbox" id="select_all" onclick="toggleAll(this)" title="Select all"> Status</th>
                    <th style="text-align:center;">Sent</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): 
                    $amount_due = in_array($inv['status'], ['unpaid', 'overdue', 'order']) ? $inv['total'] : 0;
                ?>
                    <tr>
                        <td><?= date('Y/m/d', strtotime($inv['date'])) ?></td>
                        <td><a href="invoice_create.php?edit_id=<?= $inv['id'] ?>" class="link-blue"><?= htmlspecialchars($inv['invoice_number'] ?: $inv['invoice_no']) ?></a></td>
                        <td><a href="invoice_create.php?edit_id=<?= $inv['id'] ?>" class="link-blue"><?= htmlspecialchars($inv['customer_name'] ?: 'cash sale') ?></a></td>
                        <td>R<?= number_format($amount_due, 2) ?></td>
                        <td>R<?= number_format($inv['total'], 2) ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <input type="checkbox" class="invoice-checkbox" value="<?= $inv['id'] ?>">
                                <?php 
                                $badge_class = 'badge-paid';
                                if ($inv['status'] === 'unpaid') $badge_class = 'badge-unpaid';
                                if ($inv['status'] === 'overdue') $badge_class = 'badge-overdue';
                                if ($inv['status'] === 'order') $badge_class = 'badge-order';
                                ?>
                                <div class="badge <?= $badge_class ?>" onclick="toggleStatusDropdown(this, event)">
                                    <span class="status-text"><?= ucfirst($inv['status']) ?></span>
                                    <div class="status-dropdown">
                                        <button type="button" onclick="updateStatus(<?= $inv['id'] ?>, 'order', event)">Order</button>
                                        <button type="button" onclick="updateStatus(<?= $inv['id'] ?>, 'unpaid', event)">Unpaid</button>
                                        <button type="button" onclick="updateStatus(<?= $inv['id'] ?>, 'paid', event)">Paid</button>
                                        <button type="button" onclick="updateStatus(<?= $inv['id'] ?>, 'overdue', event)">Overdue</button>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <?php if (!empty($inv['email_sent'])): ?>
                                <span class="sent-envelope" title="Email sent">✉</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-dropdown">
                                <button type="button" class="action-btn">Actions</button>
                                <div class="action-menu">
                                    <a href="invoice_create.php?edit_id=<?= $inv['id'] ?>">Edit / Email</a>
                                    <a href="print_invoice.php?id=<?= $inv['id'] ?>&print=1&return=invoices.php">Print</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 20px;">No invoices found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Hidden form for bulk status updates -->
    <form method="POST" id="bulkForm" style="display:none;">
        <input type="hidden" name="new_status" id="bulkNewStatus">
        <input type="hidden" name="bulk_update_status" value="1">
        <div id="bulkCheckboxes"></div>
    </form>

    <script>
        function toggleAll(source) {
            document.querySelectorAll('.invoice-checkbox').forEach(cb => cb.checked = source.checked);
        }
        
        function toggleStatusDropdown(el, event) {
            event.stopPropagation();
            let dropdown = el.querySelector('.status-dropdown');
            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            } else {
                // Close all other dropdowns
                document.querySelectorAll('.status-dropdown').forEach(d => d.style.display = 'none');
                dropdown.style.display = 'block';
                
                // Position the dropdown using fixed positioning
                let rect = el.getBoundingClientRect();
                dropdown.style.top = (rect.bottom + window.scrollY) + 'px';
                dropdown.style.left = (rect.left + window.scrollX) + 'px';
            }
        }
        
        function updateStatus(id, newStatus, event) {
            event.preventDefault();
            event.stopPropagation();
            
            let checkbox = document.querySelector(`.invoice-checkbox[value="${id}"]`);
            if (checkbox && checkbox.checked) {
                document.getElementById('bulkNewStatus').value = newStatus;
                let container = document.getElementById('bulkCheckboxes');
                container.innerHTML = '';
                document.querySelectorAll('.invoice-checkbox:checked').forEach(cb => {
                    let inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'selected_invoices[]';
                    inp.value = cb.value;
                    container.appendChild(inp);
                });
                document.getElementById('bulkForm').submit();
                return;
            }
            
            let formData = new FormData();
            formData.append('id', id);
            formData.append('status', newStatus);
            
            fetch('invoices.php?ajax_status_update=1', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if(res.success) { 
                    window.location.reload(); 
                }
                else { 
                    alert('Failed to update status'); 
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error updating status');
            });
        }
        
        document.addEventListener('click', function(e) {
            if(!e.target.closest('.badge')) {
                document.querySelectorAll('.status-dropdown').forEach(d => d.style.display = 'none');
            }
        });

        // Position action menus properly
        document.querySelectorAll('.action-dropdown').forEach(dropdown => {
            dropdown.addEventListener('click', function(e) {
                if (e.target.closest('.action-btn')) {
                    let menu = this.querySelector('.action-menu');
                    let rect = this.getBoundingClientRect();
                    menu.style.top = (rect.bottom + window.scrollY) + 'px';
                    menu.style.left = (rect.left + window.scrollX - 50) + 'px';
                }
            });
        });
    </script>
</body>
</html>

