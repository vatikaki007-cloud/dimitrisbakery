<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['acc_user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = get_db();
$q = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'customer';

$table = ($type === 'supplier') ? 'acc_suppliers' : 'acc_customers';

// Search by name or account_ref (Case-insensitive 'contains')
if ($q) {
    $stmt = $pdo->prepare("SELECT id, name, account_ref, email, cc_email FROM $table WHERE LOWER(name) LIKE LOWER(?) OR LOWER(account_ref) LIKE LOWER(?) LIMIT 10");
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
} else {
    $stmt = $pdo->query("SELECT id, name, account_ref, email, cc_email FROM $table ORDER BY name ASC LIMIT 10");
}
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($customers);
