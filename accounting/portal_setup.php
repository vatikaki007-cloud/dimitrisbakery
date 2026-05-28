<?php
/**
 * portal_setup.php — Run once to create portal DB structure.
 * Safe to run multiple times.
 */
require_once __DIR__ . '/config.php';
$pdo = get_db();
$msgs = [];

// 1. Add columns to acc_products
foreach ([
    "ALTER TABLE acc_products ADD COLUMN photo VARCHAR(255) DEFAULT ''",
    "ALTER TABLE acc_products ADD COLUMN portal_description TEXT",
    "ALTER TABLE acc_products ADD COLUMN available_online TINYINT(1) DEFAULT 0",
] as $sql) {
    try { $pdo->exec($sql); $msgs[] = "✅ $sql"; }
    catch(\Exception $e) { $msgs[] = "⏭ Already exists: " . $e->getMessage(); }
}

// 2. Alter acc_invoices status ENUM to include 'order'
try {
    $pdo->exec("ALTER TABLE acc_invoices MODIFY status ENUM('paid','unpaid','overdue','order') DEFAULT 'unpaid'");
    $msgs[] = "✅ Added 'order' to acc_invoices.status ENUM";
} catch(\Exception $e) { $msgs[] = "⏭ " . $e->getMessage(); }

// 3. Create acc_customer_logins
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_customer_logins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $msgs[] = "✅ Created acc_customer_logins table";
} catch(\Exception $e) { $msgs[] = "⏭ " . $e->getMessage(); }

// 4. Create product_images directory
$imgDir = __DIR__ . '/product_images';
if (!is_dir($imgDir)) { mkdir($imgDir, 0755, true); $msgs[] = "✅ Created product_images/"; }
else { $msgs[] = "⏭ product_images/ already exists"; }

echo "<pre>" . implode("\n", $msgs) . "\n\nDone!</pre>";
