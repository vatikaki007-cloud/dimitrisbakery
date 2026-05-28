<?php
require_once __DIR__ . '/accounting/config.php';
$pdo = get_db();

try {
    $pdo->beginTransaction();
    
    echo "Clearing invoice lines...\n";
    $pdo->exec("DELETE FROM acc_invoice_lines");
    
    echo "Clearing invoices/orders...\n";
    $pdo->exec("DELETE FROM acc_invoices");
    
    echo "Clearing daily skips...\n";
    $pdo->exec("DELETE FROM acc_daily_skips");
    
    $pdo->commit();
    echo "SUCCESS: System reset for fresh start.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
