<?php 
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$pdo = get_db();

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_route') {
    $id = $_POST['route_id'] ?? 0;
    if ($id) {
        // Unassign customers from this route first
        $pdo->prepare("UPDATE acc_customers SET route_id = NULL WHERE route_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM acc_routes WHERE id = ?")->execute([$id]);
    }
    header("Location: routes.php");
    exit;
}

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_route') {
    $name = trim($_POST['route_name'] ?? '');
    if ($name) {
        try {
            $pdo->prepare("INSERT INTO acc_routes (route_name) VALUES (?)")->execute([$name]);
        } catch (Exception $e) {}
    }
    header("Location: routes.php");
    exit;
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_route') {
    $id = $_POST['route_id'] ?? 0;
    $name = trim($_POST['route_name'] ?? '');
    
    if ($id && $name) {
        try {
            $pdo->prepare("UPDATE acc_routes SET route_name = ? WHERE id = ?")->execute([$name, $id]);
        } catch (Exception $e) {}
    }
    header("Location: routes.php");
    exit;
}

require_once __DIR__ . '/navbar.php'; 

// Fetch routes and customer counts
$routes = $pdo->query("
    SELECT r.*, COUNT(c.id) as customer_count 
    FROM acc_routes r 
    LEFT JOIN acc_customers c ON r.id = c.route_id 
    GROUP BY r.id 
    ORDER BY r.route_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Routes</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; }
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        h2 { color: #333; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; background: white; font-size: 13px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #e9ecef; font-weight: normal; color: #333; border-bottom: 2px solid #ddd; }
        tbody tr:nth-child(odd) { background-color: #f4f8ff; }
        tbody tr:nth-child(even) { background-color: #fff; }

        .btn-edit { background: #28a745; color: white; border: none; padding: 5px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; display: inline-block; text-align: center; }
        .btn-edit:hover { background: #218838; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 5px 12px; border-radius: 3px; font-size: 12px; cursor: pointer; display: inline-block; text-align: center; margin-left: 5px; }
        .btn-delete:hover { background: #c82333; }
        .btn-save-main { background: #0056b3; color: white; border: none; padding: 8px 15px; border-radius: 3px; font-size: 13px; cursor: pointer; }
        .btn-save-main:hover { background: #004494; }

        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: #fff; padding: 25px; border-radius: 5px; width: 350px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .modal-header h3 { margin: 0; font-size: 16px; color: #333; }
        .close-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: #888; }
        .close-btn:hover { color: #333; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: bold; color: #555; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; font-size: 13px; }
        
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn-cancel { background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 3px; font-size: 13px; cursor: pointer; }
        .btn-cancel:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Delivery Routes</h2>
            <button class="btn-save-main" onclick="openCreateModal()">+ New Route</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Route Name</th>
                    <th style="text-align:center;">Assigned Customers</th>
                    <th style="width: 120px; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routes as $r): ?>
                    <tr>
                        <td style="font-weight:bold;"><?= htmlspecialchars($r['route_name']) ?></td>
                        <td style="text-align:center;">
                            <span style="background:#e9ecef; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:bold;"><?= $r['customer_count'] ?></span>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <button class="btn-edit" onclick="openEditModal(<?= $r['id'] ?>, '<?= htmlspecialchars($r['route_name'], ENT_QUOTES) ?>')">Edit</button>
                            <form method="POST" style="display:inline-block; margin: 0;" onsubmit="return confirm('Delete this route? Customers assigned to it will be unassigned.');">
                                <input type="hidden" name="action" value="delete_route">
                                <input type="hidden" name="route_id" value="<?= htmlspecialchars($r['id']) ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($routes)): ?>
                    <tr><td colspan="3" style="text-align: center; padding: 20px;">No routes defined.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="routeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal_title">New Route</h3>
                <button type="button" class="close-btn" onclick="closeModal()">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="modal_action" value="create_route">
                <input type="hidden" name="route_id" id="modal_id">
                
                <div class="form-group">
                    <label>Route Name</label>
                    <input type="text" name="route_name" id="modal_name" required placeholder="e.g. Cape Town">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save-main">Save Route</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modal_title').innerText = 'New Route';
            document.getElementById('modal_action').value = 'create_route';
            document.getElementById('modal_id').value = '';
            document.getElementById('modal_name').value = '';
            document.getElementById('routeModal').style.display = 'flex';
        }

        function openEditModal(id, name) {
            document.getElementById('modal_title').innerText = 'Edit Route';
            document.getElementById('modal_action').value = 'edit_route';
            document.getElementById('modal_id').value = id;
            document.getElementById('modal_name').value = name;
            document.getElementById('routeModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('routeModal').style.display = 'none';
        }
        
        document.getElementById('routeModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
