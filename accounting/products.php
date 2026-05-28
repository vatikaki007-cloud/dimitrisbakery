<?php 
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$pdo = get_db();

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    $id = $_POST['product_id'] ?? 0;
    if ($id) {
        $pdo->prepare("DELETE FROM acc_products WHERE id = ?")->execute([$id]);
    }
    header("Location: products.php");
    exit;
}

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_product') {
    $code  = trim($_POST['code'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = floatval($_POST['unit_price'] ?? 0);
    $tax   = floatval($_POST['tax_percent'] ?? 0);
    $unit  = trim($_POST['unit'] ?? '');
    $portal_desc = trim($_POST['portal_description'] ?? '');
    $available = isset($_POST['available_online']) ? 1 : 0;
    
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo = 'prod_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/product_images/' . $photo);
    }

    if ($code && $desc) {
        try {
            $pdo->prepare("INSERT INTO acc_products (code, description, unit_price, tax_percent, unit, portal_description, available_online, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$code, $desc, $price, $tax, $unit, $portal_desc, $available, $photo]);
        } catch (Exception $e) {}
    }
    header("Location: products.php");
    exit;
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    $id    = $_POST['product_id'] ?? 0;
    $code  = trim($_POST['code'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = floatval($_POST['unit_price'] ?? 0);
    $tax   = floatval($_POST['tax_percent'] ?? 0);
    $unit  = trim($_POST['unit'] ?? '');
    $portal_desc = trim($_POST['portal_description'] ?? '');
    $available = isset($_POST['available_online']) ? 1 : 0;
    
    if ($id && $code && $desc) {
        $photo_sql = "";
        $params = [$code, $desc, $price, $tax, $unit, $portal_desc, $available];
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo = 'prod_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/product_images/' . $photo);
            $photo_sql = ", photo=?";
            $params[] = $photo;
        }
        $params[] = $id;

        $pdo->prepare("UPDATE acc_products SET code=?, description=?, unit_price=?, tax_percent=?, unit=?, portal_description=?, available_online=? $photo_sql WHERE id=?")
            ->execute($params);
    }
    header("Location: products.php");
    exit;
}

require_once __DIR__ . '/navbar.php';
$products = $pdo->query("SELECT * FROM acc_products ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products</title>
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
        tbody tr:nth-child(odd)  { background-color: #f4f8ff; }
        tbody tr:nth-child(even) { background-color: #fff; }

        .btn-edit   { background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; }
        .btn-edit:hover   { background: #218838; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; margin-left: 5px; }
        .btn-delete:hover { background: #c82333; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: #fff; padding: 25px; border-radius: 5px; width: 420px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .modal-header h3 { margin: 0; font-size: 16px; color: #333; }
        .close-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: #888; }
        .close-btn:hover { color: #333; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: bold; color: #555; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; font-size: 13px; }

        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn-save   { background: #0056b3; color: white; border: none; padding: 8px 15px; border-radius: 3px; font-size: 13px; cursor: pointer; }
        .btn-save:hover   { background: #004494; }
        .btn-cancel-modal { background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 3px; font-size: 13px; cursor: pointer; }
        .btn-cancel-modal:hover { background: #5a6268; }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container { margin: 20px auto; padding: 0 15px; }
            h2 { font-size: 18px; }
            .search-bar { max-width: 100%; }
            table { font-size: 12px; min-width: 100%; }
            th, td { padding: 8px 10px; }
            .btn-edit, .btn-delete { padding: 5px 10px; font-size: 11px; margin-left: 3px; }
            .modal-content { width: 90%; max-width: 420px; padding: 20px; }
        }
        
        @media (max-width: 480px) {
            .container { margin: 15px auto; padding: 0 12px; }
            h2 { font-size: 16px; margin-bottom: 15px; }
            table { font-size: 11px; }
            th, td { padding: 6px 8px; }
            .btn-edit, .btn-delete { padding: 4px 8px; font-size: 10px; }
            .modal-content { width: 95%; padding: 15px; }
            .modal-header h3 { font-size: 14px; }
            .form-group input { font-size: 12px; padding: 6px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <h2 style="margin: 0;">Products</h2>
                <button class="btn-save" style="background: #28a745;" onclick="openCreateModal()">+ New Product</button>
            </div>
        </div>

        <input type="text" id="searchInput" class="search-bar" placeholder="Search products..." onkeyup="filterTable()">

        <div class="table-wrapper">
            <table id="productsTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Photo</th>
                    <th>Description</th>
                    <th>Price (Excl)</th>
                    <th>Tax %</th>
                    <th>Unit</th>
                    <th>Online</th>
                    <th style="width: 120px; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['code']) ?></td>
                        <td>
                            <?php if (!empty($p['photo'])): ?>
                                <img src="product_images/<?= htmlspecialchars($p['photo']) ?>" alt="Photo" style="height: 30px; width: 30px; object-fit: cover; border-radius: 3px;">
                            <?php else: ?>
                                <span style="color:#aaa; font-size:11px;">No photo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($p['description']) ?>
                            <?php if (!empty($p['portal_description'])): ?>
                                <div style="font-size: 11px; color: #777; margin-top: 2px;" title="Portal description"><?= htmlspecialchars(substr($p['portal_description'], 0, 50)) ?>...</div>
                            <?php endif; ?>
                        </td>
                        <td>R <?= number_format($p['unit_price'], 2) ?></td>
                        <td><?= number_format($p['tax_percent'], 2) ?>%</td>
                        <td><?= htmlspecialchars($p['unit']) ?></td>
                        <td>
                            <?php if ($p['available_online']): ?>
                                <span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">Yes</span>
                            <?php else: ?>
                                <span style="background: #6c757d; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">No</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <button class="btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                'id'                 => $p['id'],
                                'code'               => $p['code'],
                                'description'        => $p['description'],
                                'unit_price'         => $p['unit_price'],
                                'tax_percent'        => $p['tax_percent'],
                                'unit'               => $p['unit'],
                                'portal_description' => $p['portal_description'] ?? '',
                                'available_online'   => $p['available_online'] ?? 0
                            ])) ?>)">Edit</button>
                            <form method="POST" style="display:inline-block; margin: 0;" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($p['id']) ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 20px;">No products found. Import InventoryItems CSV or add manually.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal_title">New Product</h3>
                <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="modal_action" value="create_product">
                <input type="hidden" name="product_id" id="modal_id">

                <div class="form-group">
                    <label>Product Code</label>
                    <input type="text" name="code" id="modal_code" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" id="modal_description" required>
                </div>
                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex:1;">
                        <label>Unit Price (Excl Tax)</label>
                        <input type="number" step="0.01" name="unit_price" id="modal_price" value="0.00">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Tax %</label>
                        <input type="number" step="0.01" name="tax_percent" id="modal_tax" value="15.00">
                    </div>
                </div>
                <div class="form-group">
                    <label>Unit Size (e.g. Each, Kg)</label>
                    <input type="text" name="unit" id="modal_unit" placeholder="e.g. Each, Kg">
                </div>

                <hr style="border:0; border-top: 1px solid #eee; margin: 15px 0;">
                <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #0056b3;">Customer Portal Settings</h4>

                <div class="form-group">
                    <label><input type="checkbox" name="available_online" id="modal_available_online" value="1" style="width: auto;"> Show in Customer Portal</label>
                </div>
                
                <div class="form-group">
                    <label>Portal Description (Optional)</label>
                    <textarea name="portal_description" id="modal_portal_description" rows="2" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px; font-size: 13px; font-family: inherit; resize: vertical;" placeholder="Customer-friendly description..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Product Photo</label>
                    <input type="file" name="photo" accept="image/png, image/jpeg, image/webp" style="padding: 4px;">
                    <div style="font-size: 11px; color: #888; margin-top: 4px;">Upload new photo to replace existing.</div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel-modal" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filterTable() {
            let filter = document.getElementById("searchInput").value.toLowerCase();
            let rows = document.querySelectorAll("#productsTable tbody tr");
            rows.forEach(tr => {
                if (tr.cells.length === 1) return; // skip empty row
                tr.style.display = tr.textContent.toLowerCase().includes(filter) ? "" : "none";
            });
        }

        function openCreateModal() {
            document.getElementById('modal_title').innerText = 'New Product';
            document.getElementById('modal_action').value = 'create_product';
            document.getElementById('modal_id').value = '';
            document.getElementById('modal_code').value = '';
            document.getElementById('modal_description').value = '';
            document.getElementById('modal_price').value = '0.00';
            document.getElementById('modal_tax').value = '15.00';
            document.getElementById('modal_unit').value = '';
            document.getElementById('modal_portal_description').value = '';
            document.getElementById('modal_available_online').checked = false;
            document.getElementById('productModal').style.display = 'flex';
        }

        function openEditModal(data) {
            document.getElementById('modal_title').innerText = 'Edit Product';
            document.getElementById('modal_action').value = 'edit_product';
            document.getElementById('modal_id').value = data.id;
            document.getElementById('modal_code').value = data.code;
            document.getElementById('modal_description').value = data.description;
            document.getElementById('modal_price').value = data.unit_price;
            document.getElementById('modal_tax').value = data.tax_percent;
            document.getElementById('modal_unit').value = data.unit;
            document.getElementById('modal_portal_description').value = data.portal_description || '';
            document.getElementById('modal_available_online').checked = (data.available_online == 1);
            document.getElementById('productModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
