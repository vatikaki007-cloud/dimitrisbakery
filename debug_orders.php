<?php
require_once __DIR__ . '/accounting/config.php';
$pdo = get_db();

echo "--- PENDING ORDERS ---\n";
$stmt = $pdo->query("SELECT i.id, i.invoice_no, i.entity_id, i.date, i.status, c.name as customer_name, c.route_id 
                    FROM acc_invoices i 
                    LEFT JOIN acc_customers c ON i.entity_id = c.id 
                    WHERE i.status = 'order'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\n--- ROUTES ---\n";
$stmt = $pdo->query("SELECT * FROM acc_routes");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
