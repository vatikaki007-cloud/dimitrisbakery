<?php 
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$pdo = get_db();

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

// Handle supplier Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_supplier') {
    $id = $_POST['supplier_id'] ?? 0;
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM acc_suppliers WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: suppliers.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_supplier') {
    $ref = trim($_POST['account_ref'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if ($ref && $name) {
        try {
            $stmt = $pdo->prepare("INSERT INTO acc_suppliers (account_ref, name, email, telephone, address) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), telephone=VALUES(telephone), address=VALUES(address)");
            $stmt->execute([$ref, $name, $email, $telephone, $address]);
        } catch (Exception $e) {}
    }
    header("Location: suppliers.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_supplier') {
    $id = $_POST['supplier_id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE acc_suppliers SET name = ?, email = ?, telephone = ?, address = ? WHERE id = ?");
        $stmt->execute([$name, $email, $telephone, $address, $id]);
    }
    header("Location: suppliers.php");
    exit;
}

require_once __DIR__ . '/navbar.php'; 
$suppliers = $pdo->query("SELECT * FROM acc_suppliers ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>suppliers</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        h2 { color: #333; margin-bottom: 20px; }
        
        .search-bar { width: 100%; max-width: 300px; padding: 8px 12px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        
        table { width: 100%; border-collapse: collapse; background: white; font-size: 13px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #e9ecef; font-weight: normal; color: #333; border-bottom: 2px solid #ddd; }
        tbody tr:nth-child(odd) { background-color: #f4f8ff; }
        tbody tr:nth-child(even) { background-color: #fff; }

        .btn-edit { background: #28a745; color: white; border: none; padding: 5px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; display: inline-block; text-align: center; }
        .btn-edit:hover { background: #218838; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 5px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; display: inline-block; text-align: center; margin-left: 5px; }
        .btn-delete:hover { background: #c82333; }

        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: #fff; padding: 25px; border-radius: 5px; width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
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
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <h2 style="margin: 0;">suppliers</h2>
                <button class="btn-save" style="background: #28a745;" onclick="openCreateModal()">+ New supplier</button>
            </div>
            <form action="import.php" method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="import_csv">
                <button type="submit" class="btn-save" style="background: #17a2b8;">Import from CSV</button>
            </form>
        </div>
        
        <input type="text" id="searchInput" class="search-bar" placeholder="Search suppliers..." onkeyup="filterTable()">

        <table id="suppliersTable">
            <thead>
                <tr>
                    <th>Account Ref</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th style="width: 80px; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['account_ref']) ?></td>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><small><?= nl2br(htmlspecialchars($c['address'] ?? '')) ?></small></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= htmlspecialchars($c['telephone']) ?></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <button class="btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                'id' => $c['id'],
                                'account_ref' => $c['account_ref'],
                                'name' => $c['name'],
                                'email' => $c['email'],
                                'telephone' => $c['telephone'],
                                'address' => $c['address'] ?? ''
                            ])) ?>)">Edit</button>
                            <form method="POST" style="display:inline-block; margin: 0;" onsubmit="return confirm('Are you sure you want to delete this supplier? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete_supplier">
                                <input type="hidden" name="supplier_id" value="<?= htmlspecialchars($c['id']) ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($suppliers)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 20px;">No suppliers found. Run Import first.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal_title">Edit supplier</h3>
                <button type="button" class="close-btn" onclick="closeModal()">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="modal_action" value="edit_supplier">
                <input type="hidden" name="supplier_id" id="modal_id">
                
                <div class="form-group">
                    <label>Account Ref</label>
                    <input type="text" name="account_ref" id="modal_ref" required>
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="modal_name" required>
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

    <script>
        function filterTable() {
            let input = document.getElementById("searchInput");
            let filter = input.value.toLowerCase();
            let table = document.getElementById("suppliersTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                // skip the 'No suppliers found' row
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
            document.getElementById('modal_title').innerText = 'New supplier';
            document.getElementById('modal_action').value = 'create_supplier';
            document.getElementById('modal_id').value = '';
            
            let refInput = document.getElementById('modal_ref');
            refInput.value = '';
            refInput.readOnly = false;
            
            document.getElementById('modal_name').value = '';
            document.getElementById('modal_email').value = '';
            document.getElementById('modal_phone').value = '';
            document.getElementById('modal_address').value = '';
            
            document.getElementById('editModal').style.display = 'flex';
        }

        function openEditModal(data) {
            document.getElementById('modal_title').innerText = 'Edit supplier';
            document.getElementById('modal_action').value = 'edit_supplier';
            document.getElementById('modal_id').value = data.id;
            
            let refInput = document.getElementById('modal_ref');
            refInput.value = data.account_ref;
            refInput.readOnly = true;
            
            document.getElementById('modal_name').value = data.name;
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
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
