<?php 
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/navbar.php';
$pdo = get_db();

try {
    $pdo->exec("ALTER TABLE acc_customers ADD COLUMN address TEXT NULL");
} catch (\Exception $e) {}

// Handle Customer Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_customer') {
    $id = $_POST['customer_id'] ?? 0;
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM acc_customers WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: customers.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_customer') {
    $ref = trim($_POST['account_ref'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $route_id = !empty($_POST['route_id']) ? $_POST['route_id'] : null;
    
    if ($ref && $name) {
        try {
            $stmt = $pdo->prepare("INSERT INTO acc_customers (account_ref, name, email, telephone, address, route_id) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), telephone=VALUES(telephone), address=VALUES(address), route_id=VALUES(route_id)");
            $stmt->execute([$ref, $name, $email, $telephone, $address, $route_id]);
        } catch (Exception $e) {}
    }
    header("Location: customers.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_customer') {
    $id = $_POST['customer_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $address = $_POST['address'] ?? '';
    $route_id = !empty($_POST['route_id']) ? $_POST['route_id'] : null;
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE acc_customers SET name = ?, email = ?, telephone = ?, address = ?, route_id = ? WHERE id = ?");
        $stmt->execute([$name, $email, $telephone, $address, $route_id, $id]);
    }
    header("Location: customers.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manage_login') {
    $customer_id = $_POST['customer_id'] ?? 0;
    $username = trim($_POST['portal_username'] ?? '');
    $password = $_POST['portal_password'] ?? '';
    $active = isset($_POST['portal_active']) ? 1 : 0;
    
    if ($customer_id && $username) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM acc_customer_logins WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                if ($password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE acc_customer_logins SET username=?, password=?, active=? WHERE customer_id=?")->execute([$username, $hash, $active, $customer_id]);
                } else {
                    $pdo->prepare("UPDATE acc_customer_logins SET username=?, active=? WHERE customer_id=?")->execute([$username, $active, $customer_id]);
                }
            } else {
                if ($password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("INSERT INTO acc_customer_logins (customer_id, username, password, active) VALUES (?, ?, ?, ?)")->execute([$customer_id, $username, $hash, $active]);
                }
            }
            $_SESSION['cust_msg'] = "Portal login updated successfully.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $_SESSION['cust_error'] = "Error: Username '$username' is already taken by another customer.";
            } else {
                $_SESSION['cust_error'] = "Database Error: " . $e->getMessage();
            }
        }
    }
    header("Location: customers.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_login') {
    $customer_id = $_POST['customer_id'] ?? 0;
    if ($customer_id) {
        $pdo->prepare("DELETE FROM acc_customer_logins WHERE customer_id = ?")->execute([$customer_id]);
        $_SESSION['cust_msg'] = "Portal login removed.";
    }
    header("Location: customers.php");
    exit;
}

require_once __DIR__ . '/navbar.php'; 
$customers = $pdo->query("
    SELECT c.*, l.username as portal_username, l.active as portal_active, r.route_name
    FROM acc_customers c 
    LEFT JOIN acc_customer_logins l ON c.id = l.customer_id 
    LEFT JOIN acc_routes r ON c.route_id = r.id
    ORDER BY c.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$routes = $pdo->query("SELECT * FROM acc_routes ORDER BY route_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$msg = $_SESSION['cust_msg'] ?? '';
$err = $_SESSION['cust_error'] ?? '';
unset($_SESSION['cust_msg'], $_SESSION['cust_error']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; padding: 0; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        h2 { color: #333; margin-bottom: 20px; }
        
        .search-bar { width: 100%; max-width: 300px; padding: 8px 12px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        
        .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; background: white; font-size: 13px; min-width: 700px; }
        th, td { padding: 12px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #e9ecef; font-weight: normal; color: #333; border-bottom: 2px solid #ddd; white-space: nowrap; }
        tbody tr:nth-child(odd) { background-color: #f4f8ff; }
        tbody tr:nth-child(even) { background-color: #fff; }

        .btn-edit { background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; display: inline-block; text-align: center; }
        .btn-edit:hover { background: #218838; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; display: inline-block; text-align: center; margin-left: 5px; }
        .btn-delete:hover { background: #c82333; }
        
        .btn-login-mgr { background: #17a2b8; color: white; border: none; padding: 4px 8px; border-radius: 3px; font-size: 11px; cursor: pointer; }
        .btn-login-mgr:hover { background: #138496; }
        
        .alert { padding: 10px 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: #fff; padding: 25px; border-radius: 5px; width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .modal-header h3 { margin: 0; font-size: 16px; color: #333; }
        .close-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: #888; }
        .close-btn:hover { color: #333; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: bold; color: #555; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; font-size: 13px; }
        .form-group input[readonly] { background: #e9ecef; cursor: not-allowed; }
        
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn-save { background: #0056b3; color: white; border: none; padding: 8px 15px; border-radius: 3px; font-size: 13px; cursor: pointer; }
        .btn-save:hover { background: #004494; }
        .btn-cancel { background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 3px; font-size: 13px; cursor: pointer; }
        .btn-cancel:hover { background: #5a6268; }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container { margin: 20px auto; padding: 0 15px; }
            h2 { font-size: 18px; }
            .search-bar { max-width: 100%; }
            table { font-size: 12px; min-width: 100%; }
            th, td { padding: 8px 10px; }
            .btn-edit, .btn-delete { padding: 5px 10px; font-size: 11px; margin-left: 3px; }
            .btn-login-mgr { padding: 3px 6px; font-size: 10px; }
            .modal-content { width: 90%; max-width: 400px; padding: 20px; }
        }
        
        @media (max-width: 480px) {
            .container { margin: 15px auto; padding: 0 12px; }
            h2 { font-size: 16px; margin-bottom: 15px; }
            table { font-size: 11px; }
            th, td { padding: 6px 8px; }
            .btn-edit, .btn-delete { padding: 4px 8px; font-size: 10px; }
            .btn-login-mgr { padding: 2px 5px; font-size: 9px; }
            .modal-content { width: 95%; padding: 15px; }
            .modal-header h3 { font-size: 14px; }
            .form-group input { font-size: 12px; padding: 6px; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/navbar.php'; ?>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <h2 style="margin: 0;">Customers</h2>
                <button class="btn-save" style="background: #28a745;" onclick="openCreateModal()">+ New Customer</button>
            </div>
            <form action="import.php" method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="import_csv">
                <button type="submit" class="btn-save" style="background: #17a2b8;">Import from CSV</button>
            </form>
        </div>
        
        <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        
        <input type="text" id="searchInput" class="search-bar" placeholder="Search customers..." onkeyup="filterTable()">

        <div class="table-wrapper">
            <table id="customersTable">
            <thead>
                <tr>
                    <th>Account Ref</th>
                    <th>Name</th>
                    <th>Route</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th style="text-align: center;">Portal Access</th>
                    <th style="width: 80px; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['account_ref']) ?></td>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><span style="background:#e9ecef; padding:2px 6px; border-radius:3px; font-size:11px;"><?= htmlspecialchars($c['route_name'] ?? 'Unassigned') ?></span></td>
                        <td><small><?= nl2br(htmlspecialchars($c['address'] ?? '')) ?></small></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= htmlspecialchars($c['telephone']) ?></td>
                        <td style="text-align: center;">
                            <?php if ($c['portal_username']): ?>
                                <span style="font-size: 11px; background: <?= $c['portal_active'] ? '#d4edda' : '#f8d7da' ?>; padding: 2px 5px; border-radius: 3px; border: 1px solid <?= $c['portal_active'] ? '#c3e6cb' : '#f5c6cb' ?>;"><?= htmlspecialchars($c['portal_username']) ?></span><br>
                                <button class="btn-login-mgr" style="margin-top: 4px;" onclick="openLoginModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($c['portal_username'], ENT_QUOTES) ?>', <?= $c['portal_active'] ?>)">Manage</button>
                            <?php else: ?>
                                <button class="btn-login-mgr" style="background: #6c757d;" onclick="openLoginModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>', '', 1)">Create Login</button>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <button class="btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                'id' => $c['id'],
                                'account_ref' => $c['account_ref'],
                                'name' => $c['name'],
                                'email' => $c['email'],
                                'telephone' => $c['telephone'],
                                'address' => $c['address'] ?? '',
                                'route_id' => $c['route_id'] ?? ''
                            ])) ?>)">Edit</button>
                            <form method="POST" style="display:inline-block; margin: 0;" onsubmit="return confirm('Are you sure you want to delete this customer? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete_customer">
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($c['id']) ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 20px;">No customers found. Run Import first.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal_title">Edit Customer</h3>
                <button type="button" class="close-btn" onclick="closeModal()">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="modal_action" value="edit_customer">
                <input type="hidden" name="customer_id" id="modal_id">
                
                <div class="form-group">
                    <label>Account Ref</label>
                    <input type="text" name="account_ref" id="modal_ref" required>
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="modal_name" required>
                </div>
                <div class="form-group">
                    <label>Delivery Route</label>
                    <select name="route_id" id="modal_route" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; font-size: 13px;">
                        <option value="">-- Unassigned --</option>
                        <?php foreach($routes as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['route_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" id="modal_address" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; font-size: 13px; font-family: inherit;"></textarea>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="modal_email">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="telephone" id="modal_phone">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Portal Login Modal -->
    <div class="modal-overlay" id="loginModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="login_modal_title">Portal Login for Customer</h3>
                <button type="button" class="close-btn" onclick="closeLoginModal()">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="manage_login">
                <input type="hidden" name="customer_id" id="login_modal_id">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="portal_username" id="login_modal_username" required placeholder="e.g. mamas_kitchen">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="text" name="portal_password" id="login_modal_password" placeholder="Leave blank to keep current">
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="portal_active" id="login_modal_active" value="1" style="width:auto;"> Account Active</label>
                </div>
                
                <div class="modal-actions" style="justify-content: space-between;">
                    <button type="button" class="btn-delete" id="btn_delete_login" style="margin: 0; display:none;" onclick="deleteLogin()">Remove Login</button>
                    <div style="display:flex; gap:10px;">
                        <button type="button" class="btn-cancel" onclick="closeLoginModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save Login</button>
                    </div>
                </div>
            </form>
            <form method="POST" id="form_delete_login" style="display:none;">
                <input type="hidden" name="action" value="delete_login">
                <input type="hidden" name="customer_id" id="delete_login_id">
            </form>
        </div>
    </div>

    <script>
        function filterTable() {
            let input = document.getElementById("searchInput");
            let filter = input.value.toLowerCase();
            let table = document.getElementById("customersTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                // skip the 'No customers found' row
                if (tr[i].getElementsByTagName("td").length === 1) continue;
                
                let rowContent = tr[i].textContent.toLowerCase();
                if (rowContent.indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }

        function openCreateModal() {
            document.getElementById('modal_title').innerText = 'New Customer';
            document.getElementById('modal_action').value = 'create_customer';
            document.getElementById('modal_id').value = '';
            
            let refInput = document.getElementById('modal_ref');
            refInput.value = '';
            refInput.readOnly = false;
            
            document.getElementById('modal_name').value = '';
            document.getElementById('modal_route').value = '';
            document.getElementById('modal_email').value = '';
            document.getElementById('modal_phone').value = '';
            document.getElementById('modal_address').value = '';
            
            document.getElementById('editModal').style.display = 'flex';
        }

        function openEditModal(data) {
            document.getElementById('modal_title').innerText = 'Edit Customer';
            document.getElementById('modal_action').value = 'edit_customer';
            document.getElementById('modal_id').value = data.id;
            
            let refInput = document.getElementById('modal_ref');
            refInput.value = data.account_ref;
            refInput.readOnly = true;
            
            document.getElementById('modal_name').value = data.name;
            document.getElementById('modal_route').value = data.route_id;
            document.getElementById('modal_email').value = data.email;
            document.getElementById('modal_phone').value = data.telephone;
            document.getElementById('modal_address').value = data.address || '';
            
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        document.getElementById('loginModal').addEventListener('click', function(e) {
            if (e.target === this) closeLoginModal();
        });

        function openLoginModal(id, name, username, active) {
            document.getElementById('login_modal_title').innerText = 'Portal Login: ' + name;
            document.getElementById('login_modal_id').value = id;
            document.getElementById('delete_login_id').value = id;
            document.getElementById('login_modal_username').value = username;
            document.getElementById('login_modal_password').value = '';
            document.getElementById('login_modal_active').checked = active ? true : false;
            
            document.getElementById('btn_delete_login').style.display = username ? 'block' : 'none';
            document.getElementById('loginModal').style.display = 'flex';
            
            if (!username) {
                document.getElementById('login_modal_password').required = true;
            } else {
                document.getElementById('login_modal_password').required = false;
            }
        }
        
        function closeLoginModal() {
            document.getElementById('loginModal').style.display = 'none';
        }
        
        function deleteLogin() {
            if (confirm('Are you sure you want to remove portal access for this customer?')) {
                document.getElementById('form_delete_login').submit();
            }
        }
    </script>
</body>
</html>
