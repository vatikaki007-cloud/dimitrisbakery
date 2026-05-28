<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['portal_customer_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$pdo = get_db();
$customer_id = $_SESSION['portal_customer_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['lines'])) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Calculate totals
    $total_nett = 0;
    $total_tax = 0;
    $total_discount = 0; // Not explicitly tracked at header level from portal, we calculate from lines

    foreach ($input['lines'] as $line) {
        $qty = floatval($line['quantity'] ?? 0);
        $price = floatval($line['unit_price'] ?? 0);
        $disc_pct = floatval($line['disc_percent'] ?? 0);
        $tax_pct = floatval($line['tax_percent'] ?? 0);
        
        $disc_amt = $price * ($disc_pct / 100);
        $nett_price = $price - $disc_amt;
        $line_excl = $nett_price * $qty;
        $line_tax = $line_excl * ($tax_pct / 100);
        
        $total_nett += ($price * $qty);
        $total_discount += ($disc_amt * $qty);
        $total_tax += $line_tax;
    }

    $amount_excl = $total_nett - $total_discount;
    $grand_total = $amount_excl + $total_tax;

    // Check if there is an existing pending order to update
    $stmt_check = $pdo->prepare("SELECT id FROM acc_invoices WHERE entity_id = ? AND type = 'customer' AND status = 'order' LIMIT 1");
    $stmt_check->execute([$customer_id]);
    $existing_id = $stmt_check->fetchColumn();

    if ($existing_id) {
        $invoice_id = $existing_id;
        
        // Delete existing lines to replace them
        $pdo->prepare("DELETE FROM acc_invoice_lines WHERE invoice_id = ?")->execute([$invoice_id]);
        
        // Update Invoice Header
        $stmt_update = $pdo->prepare("
            UPDATE acc_invoices 
            SET total_nett = ?, discount = ?, amount_excl = ?, tax = ?, total = ?, date = ?, notes = 'Online Portal Order (Updated)'
            WHERE id = ?
        ");
        $stmt_update->execute([
            $total_nett, 
            $total_discount, 
            $amount_excl, 
            $total_tax, 
            $grand_total,
            date('Y-m-d'),
            $invoice_id
        ]);
        
        $order_no = "Updated Order"; // We don't need to change the invoice_no usually
    } else {
        // Generate Order Number
        $year = date('Y');
        $stmt_seq = $pdo->prepare("SELECT invoice_no FROM acc_invoices WHERE invoice_no LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt_seq->execute(["ORD-$year-%"]);
        $last_no = $stmt_seq->fetchColumn();
        
        if ($last_no) {
            $seq = (int)substr($last_no, -4) + 1;
        } else {
            $seq = 1;
        }
        $order_no = "ORD-$year-" . str_pad($seq, 4, '0', STR_PAD_LEFT);

        // Insert Invoice Header
        $stmt_inv = $pdo->prepare("
            INSERT INTO acc_invoices 
            (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status, notes) 
            VALUES (?, 'customer', ?, ?, ?, ?, ?, ?, ?, 'order', 'Online Portal Order')
        ");
        $stmt_inv->execute([
            $order_no, 
            $customer_id, 
            date('Y-m-d'),
            $total_nett, 
            $total_discount, 
            $amount_excl, 
            $total_tax, 
            $grand_total
        ]);
        
        $invoice_id = $pdo->lastInsertId();
    }

    // Insert Lines
    $stmt_line = $pdo->prepare("
        INSERT INTO acc_invoice_lines 
        (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($input['lines'] as $line) {
        $qty = floatval($line['quantity'] ?? 0);
        $price = floatval($line['unit_price'] ?? 0);
        $disc_pct = floatval($line['disc_percent'] ?? 0);
        $tax_pct = floatval($line['tax_percent'] ?? 0);
        
        $disc_amt = $price * ($disc_pct / 100);
        $nett_price = $price - $disc_amt;
        $line_excl = $nett_price * $qty;
        
        $stmt_line->execute([
            $invoice_id,
            $line['code'] ?? '',
            $line['description'] ?? '',
            $qty,
            $line['unit'] ?? '',
            $price,
            $disc_pct,
            $tax_pct,
            $line_excl
        ]);
    }

    $pdo->commit();
    
    // Update session so it reflects the order just placed
    $_SESSION['portal_last_order_id'] = $invoice_id;
    $_SESSION['portal_last_order_date'] = date('Y-m-d');
    
    echo json_encode(['success' => true, 'order_no' => $order_no]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
