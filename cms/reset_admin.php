<?php
/**
 * reset_admin.php — Temporary script to force reset the admin password.
 * 
 * IMPORTANT: DELETE THIS FILE from your server after running it!
 */

require_once __DIR__ . '/config.php';

try {
    $pdo = get_db();
    
    // Ensure an admin user exists, or create one if it doesn't
    $username = 'admin';
    $password = 'Bakery@2026!'; // The new password we are setting
    
    // Create the hash natively on your server
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Check if user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update existing admin
        $update = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE username = ?");
        $update->execute([$hash, $username]);
        echo '<h2 style="color:green;font-family:sans-serif;">Admin password reset successfully!</h2>';
    } else {
        // Insert new admin if none exists
        $insert = $pdo->prepare("INSERT INTO users (username, password, role, full_name) VALUES (?, ?, 'admin', 'Administrator')");
        $insert->execute([$username, $hash]);
        echo '<h2 style="color:green;font-family:sans-serif;">Admin user created successfully!</h2>';
    }
    
    echo '<p style="font-family:sans-serif;font-size:18px;">You can now log in with:</p>';
    echo '<ul style="font-family:monospace;font-size:18px;background:#eee;padding:15px;display:inline-block;border-radius:5px;">';
    echo '<li>Username: <strong>admin</strong></li>';
    echo '<li>Password: <strong>Bakery@2026!</strong></li>';
    echo '</ul>';
    echo '<p style="color:red;font-weight:bold;font-family:sans-serif;">🚨 IMPORTANT: DELETE this reset_admin.php file from your server right now!</p>';
    
} catch (Exception $e) {
    echo '<h2 style="color:red;font-family:sans-serif;">Error:</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
}
