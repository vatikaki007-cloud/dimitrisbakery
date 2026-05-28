<?php require_once __DIR__ . '/navbar.php'; 
if ($_SESSION['acc_role'] !== 'admin') {
    die("Access denied.");
}

$pdo = get_db();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($username && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO acc_users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hash, $role]);
            $msg = "User created successfully.";
        } catch (PDOException $e) {
            $msg = "Error creating user (might already exist).";
        }
    }
}

$users = $pdo->query("SELECT id, username, role, created_at FROM acc_users")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; display: flex; gap: 20px; }
        h2 { color: #333; }
        .list-section { flex: 2; }
        .form-section { flex: 1; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); align-self: flex-start; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-size: 14px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { background: #0056b3; color: white; padding: 10px; width: 100%; border: none; border-radius: 4px; cursor: pointer; }
        .alert { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="list-section">
            <h2>System Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['role']) ?></td>
                            <td><?= $u['created_at'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="form-section">
            <h3>Add New User</h3>
            <?php if ($msg) echo "<div class='alert'>$msg</div>"; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="user">User (Create invoices)</option>
                        <option value="auditor">Auditor (Read only)</option>
                        <option value="admin">Admin (Full access)</option>
                    </select>
                </div>
                <button type="submit" class="btn">Create User</button>
            </form>
        </div>
    </div>
</body>
</html>
