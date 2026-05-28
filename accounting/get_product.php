<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['acc_user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = get_db();
$code = $_GET['code'] ?? '';
$customer_id = $_GET['customer_id'] ?? 0;

if (!$code) {
    echo json_encode([]);
    exit;
}

// Support partial match for autocomplete or exact match (Case-insensitive 'contains')
$stmt = $pdo->prepare("SELECT code, description, unit_price, tax_percent, unit FROM acc_products WHERE LOWER(code) LIKE LOWER(?) LIMIT 10");
$stmt->execute(['%' . $code . '%']);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for last price sold to this specific customer
if ($customer_id && !empty($products)) {
    foreach ($products as &$p) {
        $stmt_price = $pdo->prepare("
            SELECT l.unit_price, l.disc_percent, l.tax_percent 
            FROM acc_invoice_lines l 
            JOIN acc_invoices i ON l.invoice_id = i.id 
            WHERE i.entity_id = ? AND l.code = ? 
            ORDER BY i.date DESC, i.id DESC LIMIT 1
        ");
        $stmt_price->execute([$customer_id, $p['code']]);
        $last_sale = $stmt_price->fetch(PDO::FETCH_ASSOC);
        if ($last_sale) {
            $p['unit_price'] = $last_sale['unit_price'];
            $p['tax_percent'] = $last_sale['tax_percent']; // Keep tax same as last time too
            $p['last_disc'] = $last_sale['disc_percent'];
        }
    }
}

echo json_encode($products);
