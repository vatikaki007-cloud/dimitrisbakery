<?php
/**
 * login.php — CMS Login page
 */
require_once __DIR__ . '/auth.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . CMS_ROOT . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $pdo  = get_db();
        $stmt = $pdo->prepare('SELECT id, username, password, role, full_name FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
            header('Location: ' . CMS_ROOT . '/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CMS Login — Dimitri's Bakery</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="<?= CMS_ROOT ?>/css/cms.css">
  <link rel="icon" type="image/png" href="<?= SITE_ROOT ?>/index_images/logo.png">
</head>
<body>

<div class="login-page">
  <div class="login-card animate-in">

    <img class="login-logo" src="<?= SITE_ROOT ?>/index_images/logo.png" alt="Dimitri's Bakery">
    <h1 class="login-title">Staff Portal</h1>
    <p class="login-subtitle">Dimitri's Bakery — Content Manager</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="username">Username</label>
        <input
          type="text"
          id="username"
          name="username"
          autocomplete="username"
          value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
          required
          autofocus
        >
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          autocomplete="current-password"
          required
        >
      </div>

      <button type="submit" class="btn btn-gold btn-full" style="margin-top:8px;">
        Sign In
      </button>
    </form>

    <p class="text-center text-muted text-sm" style="margin-top:24px;">
      Not a staff member? <a href="<?= SITE_ROOT ?>">Return to website</a>
    </p>
  </div>
</div>

</body>
</html>
