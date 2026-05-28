<?php
require_once 'portal/config.php';
$pdo = get_db();

// Find duplicates by code
$stmt = $pdo->query("SELECT code, COUNT(*) as count FROM acc_products GROUP BY code HAVING count > 1");
$dups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Print all products
$stmt = $pdo->query("SELECT id, code, description FROM acc_products");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT DISTINCT code FROM acc_invoice_lines");
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = "DUPLICATE CODES:\n" . print_r($dups, true) . "\nALL PRODUCTS:\n" . print_r($all, true) . "\nINVOICE LINES CODES:\n" . print_r($lines, true);
file_put_contents('scratch_out.txt', $out);
echo "Done";
