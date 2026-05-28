<?php
/**
 * wipe_and_import.php
 * Wipes all accounting data and re-imports from CSVs.
 */
require_once __DIR__ . '/accounting/config.php';
$pdo = get_db();

header('Content-Type: text/plain');

try {
    $pdo->beginTransaction();
    
    // Disable FK checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tables = [
        'acc_invoice_lines',
        'acc_invoices',
        'acc_daily_skips',
        'acc_customers',
        'acc_products',
        'acc_suppliers',
        'acc_routes',
        'acc_financial_years'
    ];
    
    foreach ($tables as $table) {
        echo "Wiping $table...\n";
        $pdo->exec("DELETE FROM $table");
        // Reset auto-increment
        $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
    }
    
    // Re-enable FK checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    $pdo->commit();
    echo "DATABASE WIPED SUCCESSFULLY.\n\n";
    
    // Now call the import logic from import.php
    // Since import.php is designed as a web page, we'll just simulate its POST action or extract the logic.
    // Actually, it's easier to just run the import logic here.
    
    function clean_headers($headers) {
        if (!$headers) return [];
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        return array_map('trim', $headers);
    }

    ini_set('auto_detect_line_endings', true);
    
    // --- Import Customers ---
    $customers_file = __DIR__ . '/accounting/csv_imports/Customers';
    if (!file_exists($customers_file)) $customers_file .= '.csv';
    
    if (file_exists($customers_file)) {
        echo "Importing Customers from $customers_file...\n";
        $handle = fopen($customers_file, "r");
        if ($handle !== FALSE) {
            $raw_headers = fgetcsv($handle, 10000, ",");
            $headers = clean_headers($raw_headers);
            $header_count = count($headers);
            
            $stmt = $pdo->prepare("INSERT INTO acc_customers (account_ref, name, email, cc_email, telephone, tax_reference, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $count = 0;
            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                if (count($data) == $header_count) {
                    $row = array_combine($headers, $data);
                    $ref = trim($row['Account Number'] ?? '');
                    if (empty($ref)) continue;
                    
                    $name = trim($row['Description'] ?? '');
                    $email = trim($row['Email Address'] ?? '');
                    $cc = trim($row['Documents Email'] ?? '');
                    $phone = trim($row['Telephone'] ?? '');
                    $tax_ref = trim($row['Tax Reference'] ?? '');
                    
                    $addr = implode("\n", array_filter([
                        trim($row['Postal Address 1'] ?? ''),
                        trim($row['Postal Address 2'] ?? ''),
                        trim($row['Postal Address 3'] ?? '')
                    ]));
                    
                    $stmt->execute([$ref, $name, $email, $cc, $phone, $tax_ref, $addr]);
                    $count++;
                }
            }
            fclose($handle);
            echo "Customers: Imported $count.\n";
        }
    } else {
        echo "Customers CSV not found.\n";
    }

    // --- Import Inventory Items ---
    $inventory_file = __DIR__ . '/accounting/csv_imports/InventoryItems';
    if (!file_exists($inventory_file)) $inventory_file .= '.csv';
    
    if (file_exists($inventory_file)) {
        echo "Importing Inventory from $inventory_file...\n";
        $handle = fopen($inventory_file, "r");
        if ($handle !== FALSE) {
            $raw_headers = fgetcsv($handle, 10000, ",");
            $headers = clean_headers($raw_headers);
            $header_count = count($headers);
            
            $stmt = $pdo->prepare("INSERT INTO acc_products (code, description, unit_price, tax_percent, unit) VALUES (?, ?, ?, ?, ?)");
            
            $count = 0;
            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                if (count($data) == $header_count) {
                    $row = array_combine($headers, $data);
                    $code = trim($row['Inventory Code'] ?? '');
                    if (empty($code)) continue;
                    
                    $desc = trim($row['Inventory Description'] ?? '');
                    $price = floatval(trim($row['Exclusive Selling Price 1'] ?? 0));
                    $tax = floatval(trim($row['Selling Tax Type'] ?? 0));
                    $unit = trim($row['Unit Size'] ?? '');
                    
                    $stmt->execute([$code, $desc, $price, $tax, $unit]);
                    $count++;
                }
            }
            fclose($handle);
            echo "Inventory: Imported $count.\n";
        }
    } else {
        echo "InventoryItems CSV not found.\n";
    }

    echo "\nWIPE AND IMPORT COMPLETE.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}
