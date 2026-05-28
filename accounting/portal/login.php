<?php
require_once __DIR__ . '/config.php';
$pdo = get_db();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT id, customer_id, password FROM acc_customer_logins WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $stmt_cust = $pdo->prepare("SELECT name FROM acc_customers WHERE id = ?");
            $stmt_cust->execute([$user['customer_id']]);
            $cust_name = $stmt_cust->fetchColumn();
            
            $_SESSION['portal_customer_id'] = $user['customer_id'];
            $_SESSION['portal_customer_name'] = $cust_name;
            
            // Check for previous orders
            $stmt_last = $pdo->prepare("SELECT id, date FROM acc_invoices WHERE entity_id = ? AND type = 'customer' ORDER BY date DESC, id DESC LIMIT 1");
            $stmt_last->execute([$user['customer_id']]);
            $last_order = $stmt_last->fetch(PDO::FETCH_ASSOC);
            
            if ($last_order) {
                $_SESSION['portal_last_order_id'] = $last_order['id'];
                $_SESSION['portal_last_order_date'] = $last_order['date'];
                header("Location: cart.php");
            } else {
                $_SESSION['portal_last_order_id'] = 0;
                $_SESSION['portal_last_order_date'] = null;
                header("Location: products.php");
            }
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}

// Helper query to fetch business name
$stmt = $pdo->prepare("SELECT setting_value FROM acc_settings WHERE setting_key = 'bus_name'");
$stmt->execute();
$bus_name = $stmt->fetchColumn() ?: 'Bakery Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Customer Login | <?= htmlspecialchars($bus_name) ?></title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { background: white; padding: 30px 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 90%; max-width: 350px; text-align: center; }
        .login-card h2 { color: #0056b3; margin-top: 0; font-size: 20px; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 5px; color: #555; font-size: 14px; font-weight: bold; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 16px; }
        .btn-login { background: #0056b3; color: white; border: none; padding: 14px; width: 100%; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .btn-login:hover { background: #004494; }
        .error { color: #d9534f; background: #fdf2f2; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; border: 1px solid #d9534f; }
        .branding { font-size: 12px; color: #888; margin-top: 25px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2><?= htmlspecialchars($bus_name) ?><br><span style="font-size:14px; color:#555; font-weight:normal;">Customer Ordering Portal</span></h2>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login">Log In</button>
        </form>
        
        <div class="branding">Powered by Dimitris Accounting</div>
    </div>
</body>
</html>
