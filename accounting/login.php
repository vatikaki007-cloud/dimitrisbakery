<?php
require_once __DIR__ . '/config.php';
session_start();

if (isset($_SESSION['acc_user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM acc_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['acc_user_id'] = $user['id'];
            $_SESSION['acc_username'] = $user['username'];
            $_SESSION['acc_role'] = $user['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Login</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .login-box h2 { margin-top: 0; color: #333; margin-bottom: 20px; }
        .input-group { margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; margin-bottom: 8px; color: #555; }
        .input-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 16px; }
        .input-group input:focus { border-color: #0056b3; outline: none; }
        .btn { background: #0056b3; color: white; padding: 12px; width: 100%; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #004494; }
        .error { color: #d9534f; background: #fdf7f7; padding: 10px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f2dede; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Accounting System</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>
