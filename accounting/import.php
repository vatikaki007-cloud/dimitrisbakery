<?php
require_once __DIR__ . '/config.php';

$pdo = get_db();
$message = '';

/**
 * Clean BOM and trim headers
 */
function clean_headers($headers) {
    if (!$headers) return [];
    // Remove UTF-8 BOM if present on the first header
    $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
    return array_map('trim', $headers);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'install_db') {
        ob_start();
        require __DIR__ . '/install.php';
        $message .= ob_get_clean() . "<br>";
    }

    if ($_POST['action'] === 'import_csv') {
        ini_set('auto_detect_line_endings', true);
        
        // --- Import Customers ---
        $customers_file = '';
        $potential_cust = [__DIR__ . '/csv_imports/Customers', __DIR__ . '/csv_imports/Customers.csv'];
        foreach ($potential_cust as $f) { if (file_exists($f)) { $customers_file = $f; break; } }

        if ($customers_file) {
            $handle = fopen($customers_file, "r");
            if ($handle !== FALSE) {
                $raw_headers = fgetcsv($handle, 10000, ",");
                $headers = clean_headers($raw_headers);
                $header_count = count($headers);
                
                $stmt = $pdo->prepare("INSERT INTO acc_customers (account_ref, name, email, cc_email, telephone, tax_reference, address) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)
                                       ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), telephone=VALUES(telephone), tax_reference=VALUES(tax_reference), address=VALUES(address)");
                
                $count = 0;
                $skipped = 0;
                $row_idx = 1; // Header is row 1
                while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    $row_idx++;
                    if (count($data) == $header_count) {
                        $row = array_combine($headers, $data);
                        $ref = trim($row['Account Number'] ?? '');
                        if (empty($ref)) { $skipped++; continue; }

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

                        try {
                            $stmt->execute([$ref, $name, $email, $cc, $phone, $tax_ref, $addr]);
                            $count++;
                        } catch (Exception $e) {
                            $message .= "Error on row $row_idx ($ref): " . $e->getMessage() . "<br>";
                            $skipped++;
                        }
                    } else {
                        $skipped++;
                        if ($skipped <= 10) {
                            $message .= "Skipped row $row_idx: column count mismatch (found " . count($data) . ", expected $header_count).<br>";
                        }
                    }
                }
                fclose($handle);
                $message .= "<b>Customers:</b> Imported/Updated $count, Skipped $skipped.<br>";
            }
        } else {
            $message .= "Customers CSV not found (checked 'Customers' and 'Customers.csv').<br>";
        }

        // --- Import Inventory Items ---
        $inventory_file = '';
        $potential_inv = [__DIR__ . '/csv_imports/InventoryItems', __DIR__ . '/csv_imports/InventoryItems.csv'];
        foreach ($potential_inv as $f) { if (file_exists($f)) { $inventory_file = $f; break; } }

        if ($inventory_file) {
            $handle = fopen($inventory_file, "r");
            if ($handle !== FALSE) {
                $raw_headers = fgetcsv($handle, 10000, ",");
                $headers = clean_headers($raw_headers);
                $header_count = count($headers);
                
                $stmt = $pdo->prepare("INSERT INTO acc_products (code, description, unit_price, tax_percent, unit) 
                                       VALUES (?, ?, ?, ?, ?)
                                       ON DUPLICATE KEY UPDATE description=VALUES(description), unit_price=VALUES(unit_price), tax_percent=VALUES(tax_percent), unit=VALUES(unit)");
                
                $count = 0;
                $skipped = 0;
                $row_idx = 1;
                while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                    $row_idx++;
                    if (count($data) == $header_count) {
                        $row = array_combine($headers, $data);
                        $code = trim($row['Inventory Code'] ?? '');
                        if (empty($code)) { $skipped++; continue; }

                        $desc = trim($row['Inventory Description'] ?? '');
                        $price = floatval(trim($row['Exclusive Selling Price 1'] ?? 0));
                        $tax = floatval(trim($row['Selling Tax Type'] ?? 0));
                        $unit = trim($row['Unit Size'] ?? '');

                        try {
                            $stmt->execute([$code, $desc, $price, $tax, $unit]);
                            $count++;
                        } catch (Exception $e) {
                            $message .= "Error on inventory row $row_idx ($code): " . $e->getMessage() . "<br>";
                            $skipped++;
                        }
                    } else {
                        $skipped++;
                        if ($skipped <= 10) {
                            $message .= "Skipped inventory row $row_idx: column count mismatch (found " . count($data) . ", expected $header_count).<br>";
                        }
                    }
                }
                fclose($handle);
                $message .= "<b>Inventory:</b> Imported/Updated $count, Skipped $skipped.<br>";
            }
        } else {
            $message .= "InventoryItems CSV not found (checked 'InventoryItems' and 'InventoryItems.csv').<br>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Accounting Setup</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; padding: 40px; color: #333; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); max-width: 800px; margin: auto; }
        h1 { margin-top: 0; color: #0056b3; }
        .btn { padding: 12px 20px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px; font-weight: bold; }
        .btn:hover { background: #0056b3; }
        .btn-alt { background: #6c757d; }
        .msg { padding: 20px; background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; border-radius: 4px; margin-bottom: 25px; line-height: 1.5; font-family: monospace; font-size: 13px; }
        hr { border: 0; border-top: 1px solid #eee; margin: 30px 0; }
        code { background: #f0f0f0; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Accounting Setup & Import</h1>
        
        <?php if ($message): ?>
            <div class="msg"><?php echo $message; ?></div>
        <?php endif; ?>

        <section>
            <h3>Step 1: Database Initialization</h3>
            <p>Ensure all required tables (customers, products, invoices) are created and up to date.</p>
            <form method="POST">
                <button type="submit" name="action" value="install_db" class="btn btn-alt">Initialize / Reset Tables</button>
            </form>
        </section>

        <hr>

        <section>
            <h3>Step 2: Data Migration</h3>
            <p>This will process CSV files from the <code>csv_imports/</code> directory.</p>
            <ul>
                <li><strong>Customers:</strong> Looks for <code>Customers</code> or <code>Customers.csv</code></li>
                <li><strong>Inventory:</strong> Looks for <code>InventoryItems</code> or <code>InventoryItems.csv</code></li>
            </ul>
            <form method="POST">
                <button type="submit" name="action" value="import_csv" class="btn">Run Data Import</button>
            </form>
        </section>

        <div style="margin-top: 40px; font-size: 12px; color: #999;">
            <a href="dashboard.php" style="color: #007bff;">&larr; Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
