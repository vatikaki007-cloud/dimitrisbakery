<?php
/**
 * invoice_create.php
 * Handles creation of new tax invoices with searchable customers/products,
 * persistent pricing per customer, and auto-emailing with PDF attachment.
 */

require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// AJAX Save Handler - MUST be before navbar include
if (isset($_GET['ajax_save'])) {
    header('Content-Type: application/json');
    $pdo = get_db();
    
    // Fetch Business Settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM acc_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $customer_id = $_POST['customer_id'] ?? 0;
    $date = $_POST['date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'unpaid';
    $subject = $_POST['mail_subject'] ?? 'Tax Invoice';
    $cc = $_POST['mail_cc'] ?? '';

    $total_nett = $_POST['total_nett'] ?? 0;
    $total_discount = $_POST['total_discount'] ?? 0;
    $amount_excl = $_POST['amount_excl'] ?? 0;
    $total_tax = $_POST['total_tax'] ?? 0;
    $grand_total = $_POST['grand_total'] ?? 0;
    $notes        = $_POST['notes'] ?? '';

    $edit_invoice_id = $_POST['edit_invoice_id'] ?? 0;

    // Ensure notes column exists (safe to run repeatedly)
    try { $pdo->exec("ALTER TABLE acc_invoices ADD COLUMN notes TEXT"); } catch(\Exception $e) {}
    
    try {
        $pdo->beginTransaction();
        
        if ($edit_invoice_id) {
            $stmt = $pdo->prepare("UPDATE acc_invoices SET entity_id=?, date=?, total_nett=?, discount=?, amount_excl=?, tax=?, total=?, status=?, notes=? WHERE id=?");
            $stmt->execute([$customer_id, $date, $total_nett, $total_discount, $amount_excl, $total_tax, $grand_total, $status, $notes, $edit_invoice_id]);
            $invoice_id = $edit_invoice_id;
            
            $stmt_no = $pdo->prepare("SELECT invoice_no FROM acc_invoices WHERE id=?");
            $stmt_no->execute([$invoice_id]);
            $inv_no = $stmt_no->fetchColumn();
            
            $pdo->prepare("DELETE FROM acc_invoice_lines WHERE invoice_id=?")->execute([$invoice_id]);
        } else {
            $inv_type = $_POST['invoice_type'] ?? 'customer';
            $inv_no = 'INV-' . strtoupper(uniqid());
            $stmt = $pdo->prepare("INSERT INTO acc_invoices (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$inv_no, $inv_type, $customer_id, $date, $total_nett, $total_discount, $amount_excl, $total_tax, $grand_total, $status, $notes]);
            $invoice_id = $pdo->lastInsertId();
        }

        if (!empty($_POST['lines'])) {
            $stmt_line = $pdo->prepare("INSERT INTO acc_invoice_lines (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($_POST['lines'] as $line) {
                if (empty($line['code'])) continue;
                $stmt_line->execute([
                    $invoice_id,
                    $line['code'],
                    $line['description'],
                    $line['quantity'],
                    $line['unit'],
                    $line['unit_price'],
                    $line['disc_percent'],
                    $line['tax_percent'],
                    $line['nett_price']
                ]);
            }
        }

        if ($customer_id && !empty($_POST['customer_email'])) {
            $pdo->prepare("UPDATE acc_customers SET email = ?, cc_email = ? WHERE id = ?")
                ->execute([$_POST['customer_email'], $cc, $customer_id]);
        }

        $pdo->commit();

        // Handle Auto-Mail
        $mail_status = "";
        $email_was_sent = false;
        if (isset($_POST['auto_mail']) && !empty($_POST['customer_email'])) {
            try {
                require_once __DIR__ . '/fpdf.php';
                $pdf = new FPDF();
                $pdf->AddPage();
                $pdf->SetFont('Arial', '', 9);
                $y_start = $pdf->GetY();
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->MultiCell(60, 5, $_POST['customer_search'] ?? 'Customer', 0, 'L');
                
                $cust_addr = '';
                $route_name = '';
                if ($customer_id) {
                    $stmt_ca = $pdo->prepare("SELECT c.address, r.route_name FROM acc_customers c LEFT JOIN acc_routes r ON c.route_id = r.id WHERE c.id = ?");
                    $stmt_ca->execute([$customer_id]);
                    $row = $stmt_ca->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $cust_addr = $row['address'] ?: '';
                        $route_name = $row['route_name'] ?: '';
                    }
                }
                if ($route_name) {
                    $pdf->SetFont('Arial', 'B', 9);
                    $pdf->MultiCell(60, 5, "Route: " . $route_name, 0, 'L');
                }
                if ($cust_addr) {
                    $pdf->SetFont('Arial', '', 9);
                    $pdf->MultiCell(60, 5, $cust_addr, 0, 'L');
                }
                
                $pdf->SetY($y_start);
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(0, 5, $settings['bus_name'] ?? '', 0, 1, 'C');
                $pdf->SetFont('Arial', '', 9);
                $pdf->SetX(70);
                $pdf->MultiCell(70, 5, ($settings['bus_address_mid'] ?? '') . "\nVAT: " . ($settings['bus_vat'] ?? '') . "\nOFFICE: " . ($settings['bus_phone'] ?? ''), 0, 'C');
                $pdf->SetY($y_start);
                $pdf->SetFont('Arial', '', 9);
                $pdf->MultiCell(0, 5, "ORDERING NUMBER\n" . ($settings['bus_ordering_no'] ?? '') . "\n" . ($settings['bus_bank_info'] ?? '') . "\nHALAAL NO. " . ($settings['bus_halaal_no'] ?? ''), 0, 'R');
                $pdf->Ln(10);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, 'Copy Tax Invoice', 0, 1, 'C');
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetX(130); $pdf->Cell(35, 6, 'Document No'); $pdf->Cell(0, 6, $inv_no, 0, 1, 'R');
                $pdf->SetX(130); $pdf->Cell(35, 6, 'Date'); $pdf->Cell(0, 6, date('d/m/y', strtotime($date)), 0, 1, 'R');
                $pdf->SetX(130); $pdf->Cell(35, 6, 'Page'); $pdf->Cell(0, 6, '1', 0, 1, 'R');
                $pdf->Ln(10);
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(25, 7, 'Code', 'B'); $pdf->Cell(70, 7, 'Description', 'B'); $pdf->Cell(15, 7, 'Qty', 'B', 0, 'R'); $pdf->Cell(15, 7, 'Unit', 'B', 0, 'C'); $pdf->Cell(25, 7, 'Price', 'B', 0, 'R'); $pdf->Cell(15, 7, 'Disc%', 'B', 0, 'R'); $pdf->Cell(10, 7, 'Tax', 'B', 0, 'R'); $pdf->Cell(0, 7, 'Nett', 'B', 1, 'R');
                $pdf->SetFont('Arial', '', 9);
                if (!empty($_POST['lines'])) {
                    foreach ($_POST['lines'] as $line) {
                        if (empty($line['code'])) continue;
                        $pdf->Cell(25, 6, $line['code'] ?? ''); $pdf->Cell(70, 6, substr($line['description'] ?? '', 0, 40)); $pdf->Cell(15, 6, number_format((float)($line['quantity'] ?? 0), 2), 0, 0, 'R'); $pdf->Cell(15, 6, $line['unit'] ?? '', 0, 0, 'C'); $pdf->Cell(25, 6, number_format((float)($line['unit_price'] ?? 0), 2), 0, 0, 'R'); $pdf->Cell(15, 6, ((float)($line['disc_percent'] ?? 0)) . '%', 0, 0, 'R'); $pdf->Cell(10, 6, ((float)($line['tax_percent'] ?? 0)) . '%', 0, 0, 'R'); $pdf->Cell(0, 6, number_format((float)($line['nett_price'] ?? 0), 2), 0, 1, 'R');
                    }
                }
                $pdf->Ln(10);
                // Record Y position after line items — start of the notes/totals row
                $y_start_row = $pdf->GetY();

                // --- RIGHT column: Totals (printed first to occupy X=130) ---
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetX(130); $pdf->Cell(40, 6, 'Total nett price'); $pdf->Cell(0, 6, number_format((float)($total_nett ?? 0), 2), 0, 1, 'R');
                $pdf->SetX(130); $pdf->Cell(40, 6, 'Discount'); $pdf->Cell(0, 6, number_format((float)($total_discount ?? 0), 2), 0, 1, 'R');
                $pdf->Ln(2);
                $pdf->SetX(130); $pdf->Cell(40, 6, 'Amount excl tax'); $pdf->Cell(0, 6, number_format((float)($amount_excl ?? 0), 2), 0, 1, 'R');
                $pdf->SetX(130); $pdf->Cell(40, 6, 'Tax'); $pdf->Cell(0, 6, number_format((float)($total_tax ?? 0), 2), 0, 1, 'R');
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->SetX(130); $pdf->Cell(40, 8, 'TOTAL', 'T', 0); $pdf->Cell(0, 8, 'R ' . number_format((float)($grand_total ?? 0), 2), 'T', 1, 'R');
                $y_after_totals = $pdf->GetY();

                // --- LEFT column: Notes (printed beside totals) ---
                if (!empty(trim($notes))) {
                    $pdf->SetXY(10, $y_start_row);
                    $pdf->SetFont('Arial', 'B', 9);
                    $pdf->Cell(110, 5, 'Notes:', 0, 1);
                    $pdf->SetX(10);
                    $pdf->SetFont('Arial', 'I', 9);
                    $pdf->MultiCell(115, 5, $notes, 0, 'L');
                }
                $y_after_notes = $pdf->GetY();

                // Advance past whichever column is taller
                $pdf->SetY(max($y_after_totals, $y_after_notes));

                // Signature
                $pdf->Ln(15);
                $pdf->SetFont('Arial', '', 9);
                $pdf->Cell(80, 5, 'Received in good order', 0, 0);
                $pdf->Cell(0, 5, 'Date: ________________________________', 0, 1, 'R');
                $pdf->Ln(10);
                $pdf->Cell(80, 5, 'Signature ___________________________', 0, 0);
                $pdf->Cell(0, 5, 'Name: ________________________________', 0, 1, 'R');
                $pdf_content = $pdf->Output('S');


                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'mail.dimitrisbakery.co.za';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'info@dimitrisbakery.co.za';
                $mail->Password   = 'w1=v(S)xcg7!R;OW';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->SMTPOptions = array('ssl'=>array('verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true));
                $mail->setFrom('info@dimitrisbakery.co.za', 'Dimitris Bakery');
                $mail->addAddress($_POST['customer_email']);
                if ($cc) $mail->addCC($cc);
                $mail->addStringAttachment($pdf_content, "$inv_no.pdf");
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = "Dear Customer,<br><br>Please find your tax invoice <b>$inv_no</b> attached as a PDF.<br><br>Thank you,<br>Dimitris Bakery";
                $mail->send();
                $mail_status = "Emailed to " . $_POST['customer_email'];
                $email_was_sent = true;
            } catch (\Exception $mailEx) {
                $mail_status = "Email failed: " . ($mail->ErrorInfo ?: $mailEx->getMessage());
            }
        }
        
        if ($email_was_sent) {
            $pdo->prepare("UPDATE acc_invoices SET email_sent = 1 WHERE id = ?")->execute([$invoice_id]);
        }

        echo json_encode(['success' => true, 'id' => $invoice_id, 'no' => $inv_no, 'mail_status' => $mail_status]);
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Include navbar after AJAX handler
require_once __DIR__ . '/navbar.php';

// 1. Move POST handling to the very top to allow clean redirects
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = get_db();
    
    // Fetch Business Settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM acc_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $customer_id = $_POST['customer_id'] ?? 0;
    $date = $_POST['date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'unpaid';
    $subject = $_POST['mail_subject'] ?? 'Tax Invoice';
    $cc = $_POST['mail_cc'] ?? '';

    $total_nett = $_POST['total_nett'] ?? 0;
    $total_discount = $_POST['total_discount'] ?? 0;
    $amount_excl = $_POST['amount_excl'] ?? 0;
    $total_tax = $_POST['total_tax'] ?? 0;
    $grand_total = $_POST['grand_total'] ?? 0;

    $inv_no = 'INV-' . strtoupper(uniqid());

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO acc_invoices (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status) VALUES (?, 'customer', ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$inv_no, $customer_id, $date, $total_nett, $total_discount, $amount_excl, $total_tax, $grand_total, $status]);
        $invoice_id = $pdo->lastInsertId();

        if (!empty($_POST['lines'])) {
            $stmt_line = $pdo->prepare("INSERT INTO acc_invoice_lines (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($_POST['lines'] as $line) {
                if (empty($line['code'])) continue;
                $stmt_line->execute([
                    $invoice_id,
                    $line['code'],
                    $line['description'],
                    $line['quantity'],
                    $line['unit'],
                    $line['unit_price'],
                    $line['disc_percent'],
                    $line['tax_percent'],
                    $line['nett_price']
                ]);
            }
        }

        // Update customer email if provided
        if ($customer_id && !empty($_POST['customer_email'])) {
            $pdo->prepare("UPDATE acc_customers SET email = ?, cc_email = ? WHERE id = ?")
                ->execute([$_POST['customer_email'], $cc, $customer_id]);
        }

        $pdo->commit();

        // 2. Handle Auto-Actions (Mail/Print)
        $auto_mail = isset($_POST['auto_mail']);
        $auto_print = isset($_POST['auto_print']);

        if ($auto_mail && !empty($_POST['customer_email'])) {
            // Wrap in try-catch to prevent blank page on mail failure
            try {
                require_once __DIR__ . '/fpdf.php';

                // Generate PDF
                $pdf = new FPDF();
                $pdf->AddPage();
                $pdf->SetFont('Arial', '', 9);
                
                // Header (Left, Mid, Right)
                $y_start = $pdf->GetY();
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->MultiCell(60, 5, $_POST['customer_search'] ?? 'Customer', 0, 'L');
                
                $cust_addr = '';
                $route_name = '';
                if ($customer_id) {
                    $stmt_ca = $pdo->prepare("SELECT c.address, r.route_name FROM acc_customers c LEFT JOIN acc_routes r ON c.route_id = r.id WHERE c.id = ?");
                    $stmt_ca->execute([$customer_id]);
                    $row = $stmt_ca->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $cust_addr = $row['address'] ?: '';
                        $route_name = $row['route_name'] ?: '';
                    }
                }
                if ($route_name) {
                    $pdf->SetFont('Arial', 'B', 9);
                    $pdf->MultiCell(60, 5, "Route: " . $route_name, 0, 'L');
                }
                if ($cust_addr) {
                    $pdf->SetFont('Arial', '', 9);
                    $pdf->MultiCell(60, 5, $cust_addr, 0, 'L');
                }
                
                $pdf->SetY($y_start);
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->Cell(0, 5, $settings['bus_name'] ?? 'DIMITRIS CONFECTIONERY/BAKERY', 0, 1, 'C');
                $pdf->SetFont('Arial', '', 9);
                $pdf->SetX(70);
                $pdf->MultiCell(70, 5, ($settings['bus_address_mid'] ?? '') . "\nVAT: " . ($settings['bus_vat'] ?? '') . "\nOFFICE: " . ($settings['bus_phone'] ?? ''), 0, 'C');
                
                $pdf->SetY($y_start);
                $pdf->SetFont('Arial', '', 9);
                $pdf->MultiCell(0, 5, "ORDERING NUMBER\n" . ($settings['bus_ordering_no'] ?? '') . "\n" . ($settings['bus_bank_info'] ?? '') . "\nHALAAL NO. " . ($settings['bus_halaal_no'] ?? ''), 0, 'R');
                
                $pdf->Ln(10);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, 'Copy Tax Invoice', 0, 1, 'C');
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetX(130); $pdf->Cell(35, 6, 'Document No'); $pdf->Cell(0, 6, $inv_no, 0, 1, 'R');
                $pdf->SetX(130); $pdf->Cell(35, 6, 'Date'); $pdf->Cell(0, 6, date('d/m/y', strtotime($date)), 0, 1, 'R');
                $pdf->SetX(130); $pdf->Cell(35, 6, 'Page'); $pdf->Cell(0, 6, '1', 0, 1, 'R');
                
                $pdf->Ln(10);

                // Account Bar
                $pdf->SetFillColor(240, 240, 240);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(45, 5, 'Account', 'TB', 0);
                $pdf->Cell(45, 5, 'Your Ref', 'TB', 0);
                $pdf->Cell(45, 5, 'Tax Exempt', 'TB', 0);
                $pdf->Cell(0, 5, 'Sales Code', 'TB', 1);
                $pdf->SetFont('Arial', '', 9);
                $pdf->Cell(45, 6, $_POST['account_ref'] ?? '', 0, 0);
                $pdf->Cell(45, 6, '', 0, 0);
                $pdf->Cell(45, 6, '', 0, 0);
                $pdf->Cell(0, 6, '', 0, 1);
                
                $pdf->Ln(2);
                $pdf->SetFont('Arial', '', 7);
                $pdf->Cell(0, 4, 'Exclusive', 0, 1, 'R');

                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(25, 7, 'Code', 'B');
                $pdf->Cell(70, 7, 'Description', 'B');
                $pdf->Cell(15, 7, 'Qty', 'B', 0, 'R');
                $pdf->Cell(15, 7, 'Unit', 'B', 0, 'C');
                $pdf->Cell(25, 7, 'Price', 'B', 0, 'R');
                $pdf->Cell(15, 7, 'Disc%', 'B', 0, 'R');
                $pdf->Cell(10, 7, 'Tax', 'B', 0, 'R');
                $pdf->Cell(0, 7, 'Nett', 'B', 1, 'R');

                $pdf->SetFont('Arial', '', 9);
                if (!empty($_POST['lines'])) {
                    foreach ($_POST['lines'] as $line) {
                        if (empty($line['code'])) continue;
                        $pdf->Cell(25, 6, $line['code'] ?? '');
                        $pdf->Cell(70, 6, substr($line['description'] ?? '', 0, 40));
                        $pdf->Cell(15, 6, number_format((float)($line['quantity'] ?? 0), 2), 0, 0, 'R');
                        $pdf->Cell(15, 6, $line['unit'] ?? '', 0, 0, 'C');
                        $pdf->Cell(25, 6, number_format((float)($line['unit_price'] ?? 0), 2), 0, 0, 'R');
                        $pdf->Cell(15, 6, ((float)($line['disc_percent'] ?? 0)) . '%', 0, 0, 'R');
                        $pdf->Cell(10, 6, ((float)($line['tax_percent'] ?? 0)) . '%', 0, 0, 'R');
                        $pdf->Cell(0, 6, number_format((float)($line['nett_price'] ?? 0), 2), 0, 1, 'R');
                    }
                }
                
                $pdf->Ln(10);
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetX(130); $pdf->Cell(40, 6, 'Total nett price', 0, 0); $pdf->Cell(0, 6, number_format((float)($total_nett ?? 0), 2), 0, 1, 'R');
                $pdf->SetX(130); $pdf->Cell(40, 6, 'Discount', 0, 0); $pdf->Cell(0, 6, number_format((float)($total_discount ?? 0), 2), 0, 1, 'R');
                $pdf->Ln(2);
                $pdf->SetX(130); $pdf->Cell(40, 6, 'Amount excl tax', 0, 0); $pdf->Cell(0, 6, number_format((float)($amount_excl ?? 0), 2), 0, 1, 'R');
                $pdf->SetX(130); $pdf->Cell(40, 6, 'Tax', 0, 0); $pdf->Cell(0, 6, number_format((float)($total_tax ?? 0), 2), 0, 1, 'R');
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->SetX(130); $pdf->Cell(40, 8, 'TOTAL', 'T', 0); $pdf->Cell(0, 8, 'R ' . number_format((float)($grand_total ?? 0), 2), 'T', 1, 'R');

                $pdf->Ln(20);
                $pdf->SetFont('Arial', '', 9);
                $pdf->Cell(80, 5, 'Received in good order', 0, 0);
                $pdf->Cell(0, 5, 'Date: ________________________________', 0, 1, 'R');
                $pdf->Ln(10);
                $pdf->Cell(80, 5, 'Signature ___________________________', 0, 0);
                $pdf->Cell(0, 5, 'Name: ________________________________', 0, 1, 'R');

                $pdf_content = $pdf->Output('S');

                // Send Email
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'mail.dimitrisbakery.co.za';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'info@dimitrisbakery.co.za';
                $mail->Password   = 'w1=v(S)xcg7!R;OW';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                // Fix for local development SSL issues
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                $mail->setFrom('info@dimitrisbakery.co.za', 'Dimitris Bakery');
                $mail->addAddress($_POST['customer_email']);
                if ($cc) $mail->addCC($cc);
                $mail->addStringAttachment($pdf_content, "$inv_no.pdf");
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = "Dear Customer,<br><br>Please find your tax invoice <b>$inv_no</b> attached as a PDF.<br><br>Thank you,<br>Dimitris Bakery";
                $mail->send();
                $mail_success = true;
            } catch (\Exception $mailEx) {
                $mail_error_msg = $mail->ErrorInfo ?: $mailEx->getMessage();
                $mail_success = false;
            }
        }

        // 3. Prepare result messages
        $status_msg = "Invoice $inv_no saved successfully!";
        $mail_error = "";
        
        if ($auto_mail && !empty($_POST['customer_email'])) {
            if (isset($mail_success) && $mail_success) {
                $status_msg .= " and emailed to " . htmlspecialchars($_POST['customer_email']);
            } else if (isset($mail_error_msg)) {
                $mail_error = "Email failed: " . $mail_error_msg;
            }
        }

        // 4. Handle Redirect/Print
        if ($auto_print) {
            $return_url = 'invoice_create.php?msg=' . urlencode($status_msg) . '&err=' . urlencode($mail_error);
            header("Location: print_invoice.php?id=" . urlencode($invoice_id) . "&print=1&return=" . urlencode($return_url));
            exit;
        } else {
            header("Location: invoice_create.php?msg=" . urlencode($status_msg) . "&err=" . urlencode($mail_error));
            exit;
        }

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error_msg = "Critical Error: " . $e->getMessage();
    }
}

$edit_data_json = '';
$edit_id = $_GET['edit_id'] ?? 0;
$from_orders = isset($_GET['from_orders']) ? 1 : 0;
if ($edit_id) {
    $pdo = get_db();
    // Fetch the invoice first to know its type
    $stmt = $pdo->prepare("
        SELECT i.*, 
            COALESCE(c.name, s.name) as customer_search, 
            COALESCE(c.email, s.email) as customer_email, 
            COALESCE(c.cc_email, s.cc_email) as mail_cc 
        FROM acc_invoices i 
        LEFT JOIN acc_customers c ON i.type = 'customer' AND i.entity_id = c.id
        LEFT JOIN acc_suppliers s ON i.type = 'supplier' AND i.entity_id = s.id
        WHERE i.id = ?
    ");
    $stmt->execute([$edit_id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($inv) {
        $stmt_lines = $pdo->prepare("SELECT * FROM acc_invoice_lines WHERE invoice_id = ? ORDER BY id ASC");
        $stmt_lines->execute([$edit_id]);
        $lines = $stmt_lines->fetchAll(PDO::FETCH_ASSOC);
        
        $draft = [
            'edit_invoice_id' => $edit_id,
            'invoice_type'    => $inv['type'] ?? 'customer',
            'status'          => $inv['status'],
            'customer_id'     => $inv['entity_id'],
            'customer_search' => $inv['customer_search'],
            'date'            => $inv['date'],
            'customer_email'  => $inv['customer_email'],
            'mail_cc'         => $inv['mail_cc'],
            'mail_subject'    => 'Tax Invoice ' . $inv['invoice_no'],
            'auto_mail'       => false,
            'auto_print'      => true,
            'from_orders'     => $from_orders,
            'lines'           => array_map(function($l) {
                return [
                    'code'         => $l['code'],
                    'description'  => $l['description'],
                    'quantity'     => $l['quantity'],
                    'unit'         => $l['unit'],
                    'unit_price'   => $l['unit_price'],
                    'disc_percent' => $l['disc_percent'],
                    'tax_percent'  => $l['tax_percent'],
                    'nett_price'   => $l['nett_price']
                ];
            }, $lines)
        ];
        $edit_data_json = json_encode($draft);
    }
} elseif (isset($_GET['new_customer_id'])) {
    $new_cust_id = (int)$_GET['new_customer_id'];
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id, name as customer_search, account_ref, email as customer_email, cc_email as mail_cc FROM acc_customers WHERE id = ?");
    $stmt->execute([$new_cust_id]);
    $cust = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cust) {
        $draft = [
            'edit_invoice_id' => 0,
            'invoice_type'    => 'customer',
            'status'          => 'order',
            'customer_id'     => $cust['id'],
            'account_ref'     => $cust['account_ref'],
            'customer_search' => $cust['customer_search'],
            'date'            => date('Y-m-d'),
            'customer_email'  => $cust['customer_email'],
            'mail_cc'         => $cust['mail_cc'],
            'mail_subject'    => 'Tax Invoice',
            'auto_mail'       => false,
            'auto_print'      => true,
            'lines'           => []
        ];
        $edit_data_json = json_encode($draft);
    }
}

require_once __DIR__ . '/navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; padding-bottom: 50px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header-section { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; font-size: 14px; color: #333; display: block; margin-bottom: 5px; }
        select, input[type="text"], input[type="date"], input[type="number"], input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        input:focus { background: #fffde7 !important; }
        
        table.invoice-grid { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.invoice-grid th, table.invoice-grid td { border: 1px solid #ddd; padding: 5px; }
        table.invoice-grid th { background: #f9f9f9; text-align: left; font-size: 13px; }
        table.invoice-grid input { border: none; width: 100%; padding: 5px; background: transparent; }
        table.invoice-grid input:focus { outline: 2px solid #0056b3; background: #fff; }
        
        .btn-delete-line { background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 16px; font-weight: bold; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; }
        .btn-delete-line:hover { background: #c82333; }
        .btn-delete-line:active { background: #bd2130; }
        
        .totals-box { float: right; width: 300px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; }
        .totals-box div { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px; }
        .grand-total { font-weight: bold; font-size: 18px; margin-top: 10px; border-top: 2px solid #ccc; padding-top: 10px; }
        
        .autocomplete-results { position: absolute; background: white; border: 1px solid #ddd; max-height: 150px; overflow-y: auto; z-index: 1000; width: 100%; display: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .autocomplete-results div { padding: 8px; cursor: pointer; border-bottom: 1px solid #eee; font-size: 13px; }
        .autocomplete-results div:hover, .autocomplete-results div.selected { background: #f0f0f0; }
        
        .actions { clear: both; margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px; display: flex; justify-content: flex-end; }
        .btn-update { background: #17a2b8; color: white; padding: 10px 30px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-right: 15px; }
        .btn-update:focus { outline: 3px solid #0056b3; }
        .btn-save { background: #28a745; color: white; padding: 10px 30px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-save:focus { outline: 3px solid #218838; }
        .btn-cancel { background: #6c757d; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-right: auto; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/navbar.php'; ?>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin:0;">Create Tax Invoice</h2>
            <button type="button" class="btn-cancel" onclick="cancelInvoice()">Cancel Invoice</button>
        </div>

        <div id="ajax-status" style="display:none; padding:15px; margin-bottom:15px; border-radius:4px;"></div>

        <form method="POST" id="invoiceForm">
            <div class="header-section">
                <div style="width: 45%; position: relative;">
                    <div class="form-group">
                        <label>Invoice For</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <select id="invoice_type" name="invoice_type" onchange="onTypeChange()" style="width: auto; min-width: 140px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="customer">Customer (Default)</option>
                                <option value="supplier">Supplier</option>
                            </select>
                            <div style="flex:1; position: relative;">
                                <input type="hidden" name="edit_invoice_id" id="edit_invoice_id" value="">
                                <input type="hidden" name="from_orders" id="from_orders" value="">
                                <input type="hidden" name="customer_id" id="customer_id">
                                <input type="hidden" name="account_ref" id="account_ref">
                                <input type="text" id="customer_search" placeholder="Search customer..." name="customer_search" autocomplete="off" onkeydown="handleCustomerKey(event)" oninput="searchCustomer(this)" onblur="setTimeout(hideCustomerResults, 200)" onfocus="this.select()">
                                <div class="autocomplete-results" id="customer-autocomplete"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="width: 25%; display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Date</label>
                        <input type="date" name="date" id="inv_date" value="<?= date('Y-m-d') ?>" required onchange="saveDraft()" onkeydown="if(event.key==='Enter'){event.preventDefault(); document.querySelector('.input-code').focus();}">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Status</label>
                        <select name="status" id="invoice_status" onchange="saveDraft(); toggleUpdateBtn();" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 100%;">
                            <option value="unpaid">Unpaid</option>
                            <option value="paid">Paid</option>
                            <option value="order">Order (Pending)</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                </div>
            </div>

            <table class="invoice-grid" id="linesTable">
                <thead>
                    <tr>
                        <th style="width: 15%">Code</th>
                        <th style="width: 30%">Description</th>
                        <th style="width: 10%">Qty</th>
                        <th style="width: 10%">Unit</th>
                        <th style="width: 10%">Price</th>
                        <th style="width: 5%">Disc%</th>
                        <th style="width: 5%">Tax%</th>
                        <th style="width: 15%">Nett</th>
                        <th style="width: 3%; text-align: center;">Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <tr data-index="0">
                        <td style="position: relative;">
                            <input type="text" name="lines[0][code]" class="input-code" autocomplete="off" onkeydown="handleCodeKey(event, this, 0)" oninput="searchProduct(this, 0)" onblur="setTimeout(() => hideResults(0), 200)" onfocus="this.select()">
                            <div class="autocomplete-results" id="autocomplete-0"></div>
                        </td>
                        <td><input type="text" name="lines[0][description]" class="input-desc" onchange="saveDraft()" onfocus="this.select()"></td>
                        <td><input type="number" step="0.01" name="lines[0][quantity]" class="input-qty calc" onchange="calcLine(this); saveDraft()" onkeyup="calcLine(this)" onfocus="this.select()"></td>
                        <td><input type="text" name="lines[0][unit]" class="input-unit" onchange="saveDraft()" onfocus="this.select()"></td>
                        <td><input type="number" step="0.01" name="lines[0][unit_price]" class="input-price calc" onchange="calcLine(this); saveDraft()" onkeyup="calcLine(this)" onfocus="this.select()"></td>
                        <td><input type="number" step="0.01" name="lines[0][disc_percent]" class="input-disc calc" value="0.00" onchange="calcLine(this); saveDraft()" onkeyup="calcLine(this)" onfocus="this.select()"></td>
                        <td><input type="number" step="0.01" name="lines[0][tax_percent]" class="input-tax calc" value="0.00" onchange="calcLine(this); saveDraft()" onkeyup="calcLine(this)" onfocus="this.select()"></td>
                        <td><input type="number" step="0.01" name="lines[0][nett_price]" class="input-nett" readonly></td>
                        <td style="text-align: center;"><button type="button" class="btn-delete-line" onclick="deleteLine(this)" title="Delete this line">✕</button></td>
                    </tr>
                </tbody>
            </table>

            <div style="display:flex; gap:20px; align-items:flex-start; margin-top:10px;">
                <div style="flex:1;">
                    <label style="font-weight:bold; font-size:14px; color:#333;">Notes <small style="font-weight:normal; color:#888;">(printed on invoice if not empty)</small></label>
                    <textarea name="notes" id="invoice_notes" rows="5" placeholder="Optional notes for this invoice..." onchange="saveDraft()" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; font-family:inherit; font-size:14px; resize:vertical; min-height:110px;"></textarea>
                </div>
                <div class="totals-box" style="float:none; flex-shrink:0;">
                    <div><span>Subtotal:</span> <input type="number" readonly id="t_nett" name="total_nett" value="0.00" style="width:100px;text-align:right;border:none;background:transparent;"></div>
                    <div><span>Discount:</span> <input type="number" readonly id="t_disc" name="total_discount" value="0.00" style="width:100px;text-align:right;border:none;background:transparent;"></div>
                    <div><span>Excl Tax:</span> <input type="number" readonly id="t_excl" name="amount_excl" value="0.00" style="width:100px;text-align:right;border:none;background:transparent;"></div>
                    <div><span>Tax:</span> <input type="number" readonly id="t_tax" name="total_tax" value="0.00" style="width:100px;text-align:right;border:none;background:transparent;"></div>
                    <div class="grand-total"><span>TOTAL:</span> <input type="number" readonly id="t_grand" name="grand_total" value="0.00" style="width:100px;text-align:right;font-weight:bold;font-size:18px;border:none;background:transparent;"></div>
                </div>
            </div>

            <div style="padding-top: 20px;">
                <div style="background: #fcfcfc; padding: 15px; border: 1px solid #eee; border-radius: 4px;">
                    <h4 style="margin-top:0;">Post-Save Actions</h4>
                    <div style="display: flex; gap: 15px; margin-bottom: 10px;">
                        <div style="flex:1;"><label>Customer Email</label><input type="email" name="customer_email" id="customer_email" onchange="saveDraft()"></div>
                        <div style="flex:1;"><label>CC</label><input type="text" name="mail_cc" id="mail_cc" placeholder="Optional CC..." onchange="saveDraft()"></div>
                    </div>
                    <div class="form-group"><label>Subject</label><input type="text" name="mail_subject" id="mail_subject" value="Tax Invoice" onchange="saveDraft()"></div>
                    <label style="font-weight:normal;"><input type="checkbox" name="auto_mail" id="auto_mail" onchange="saveDraft()"> Mail invoice as PDF attachment when Done</label><br>
                    <label style="font-weight:normal;"><input type="checkbox" name="auto_print" id="auto_print" onchange="saveDraft()" checked> Print</label>
                </div>
            </div>

            <div class="actions">
                <button type="button" class="btn-update" id="btn_update" onclick="submitForm('update')" style="display: none;">Update</button>
                <button type="button" class="btn-save" id="btn_done" onclick="submitForm('done')">Done</button>
            </div>
        </form>
    </div>

    <script>
        let lineCount = 1;
        let custSearchTimeout, searchTimeout;
        let custSelectedIdx = -1, selectedIdx = -1;

        // Navigation & New Lines
        document.getElementById('linesTable').addEventListener('keydown', function(e) {
            if (e.target.tagName !== 'INPUT') return;
            let row = e.target.closest('tr');
            let inputs = Array.from(row.querySelectorAll('input:not([readonly])'));
            let index = inputs.indexOf(e.target);

            // Enter or Tab on last field
            if ((e.key === 'Enter' || e.key === 'Tab') && !e.target.classList.contains('input-code')) {
                if (e.key === 'Enter' && e.target.classList.contains('input-desc') && e.target.value.trim() === '') {
                    // Blank description + Enter = done adding lines (if we have items)
                    if (parseFloat(document.getElementById('t_grand').value) > 0) {
                        e.preventDefault();
                        submitForm();
                        return;
                    }
                }

                if (index === inputs.length - 1) {
                    e.preventDefault();
                    addNewLine();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    inputs[index + 1].focus();
                }
            } 
            else if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                e.preventDefault(); inputs[index - 1].focus(); inputs[index - 1].select();
            }
        });

        function addNewLine() {
            let tbody = document.querySelector('#linesTable tbody');
            let tr = document.createElement('tr');
            tr.setAttribute('data-index', lineCount);
            tr.innerHTML = `
                <td style="position: relative;">
                    <input type="text" name="lines[${lineCount}][code]" class="input-code" autocomplete="off" onkeydown="handleCodeKey(event, this, ${lineCount})" oninput="searchProduct(this, ${lineCount})" onblur="setTimeout(() => hideResults(${lineCount}), 200)" onfocus="this.select()">
                    <div class="autocomplete-results" id="autocomplete-${lineCount}"></div>
                </td>
                <td><input type="text" name="lines[${lineCount}][description]" class="input-desc" onchange="saveDraft()" onfocus="this.select()"></td>
                <td><input type="number" step="0.01" name="lines[${lineCount}][quantity]" class="input-qty calc" onchange="calcLine(this); saveDraft()" onkeyup="calcLine(this)" onfocus="this.select()"></td>
                <td><input type="text" name="lines[${lineCount}][unit]" class="input-unit" onchange="saveDraft()" onfocus="this.select()"></td>
                <td><input type="number" step="0.01" name="lines[${lineCount}][unit_price]" class="input-price calc" onchange="calcLine(this); saveDraft()" onkeyup="calcLine(this)" onfocus="this.select()"></td>
                <td><input type="number" step="0.01" name="lines[${lineCount}][disc_percent]" class="input-disc calc" value="0.00" onchange="calcLine(this); saveDraft()" onkeyup="calcLine(this)" onfocus="this.select()"></td>
                <td><input type="number" step="0.01" name="lines[${lineCount}][tax_percent]" class="input-tax calc" value="0.00" onchange="calcLine(this); saveDraft()" onkeyup="calcLine(this)" onfocus="this.select()"></td>
                <td><input type="number" step="0.01" name="lines[${lineCount}][nett_price]" class="input-nett" readonly></td>
                <td style="text-align: center;"><button type="button" class="btn-delete-line" onclick="deleteLine(this)" title="Delete this line">✕</button></td>`;
            tbody.appendChild(tr); tr.querySelector('.input-code').focus(); lineCount++; saveDraft();
        }

        function deleteLine(btn) {
            let row = btn.closest('tr');
            let tbody = row.closest('tbody');
            
            // Don't allow deleting if it's the only row
            if (tbody.querySelectorAll('tr').length <= 1) {
                alert('You must have at least one line item.');
                return;
            }
            
            row.remove();
            calcTotals();
            saveDraft();
        }

        // Customer/Supplier Search
        function onTypeChange() {
            let type = document.getElementById('invoice_type').value;
            let searchInput = document.getElementById('customer_search');
            searchInput.placeholder = type === 'supplier' ? 'Search supplier...' : 'Search customer...';
            document.getElementById('customer_id').value = '';
            document.getElementById('account_ref').value = '';
            searchInput.value = '';
            document.getElementById('customer_email').value = '';
            document.getElementById('mail_cc').value = '';
        }

        function searchCustomer(input) {
            clearTimeout(custSearchTimeout);
            let resultsDiv = document.getElementById('customer-autocomplete');
            let type = document.getElementById('invoice_type').value;
            custSearchTimeout = setTimeout(() => {
                fetch('get_customer.php?q=' + encodeURIComponent(input.value) + '&type=' + encodeURIComponent(type))
                .then(r => r.json()).then(data => {
                    resultsDiv.innerHTML = ''; custSelectedIdx = -1;
                    if(data.length > 0) {
                        data.forEach(item => {
                            let div = document.createElement('div');
                            div.textContent = item.name + ' (' + item.account_ref + ')';
                            div.onclick = function() {
                                document.getElementById('customer_id').value = item.id;
                                document.getElementById('account_ref').value = item.account_ref;
                                document.getElementById('customer_search').value = item.name;
                                document.getElementById('customer_email').value = item.email || '';
                                document.getElementById('mail_cc').value = item.cc_email || '';
                                resultsDiv.style.display = 'none';
                                document.getElementById('inv_date').focus();
                                saveDraft();
                            };
                            resultsDiv.appendChild(div);
                        });
                        resultsDiv.style.display = 'block';
                    } else resultsDiv.style.display = 'none';
                });
            }, 200);
        }

        function handleCustomerKey(e) {
            let res = document.getElementById('customer-autocomplete');
            if (res.style.display === 'block') {
                let items = res.querySelectorAll('div');
                if (e.key === 'ArrowDown') { e.preventDefault(); custSelectedIdx = (custSelectedIdx + 1) % items.length; items.forEach((it, i) => it.classList.toggle('selected', i === custSelectedIdx)); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); custSelectedIdx = (custSelectedIdx - 1 + items.length) % items.length; items.forEach((it, i) => it.classList.toggle('selected', i === custSelectedIdx)); }
                else if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); (items[custSelectedIdx] || items[0]).click(); }
            } else if (e.key === 'Enter') { e.preventDefault(); document.getElementById('inv_date').focus(); }
            else if (e.key === 'ArrowDown') { e.preventDefault(); searchCustomer(document.getElementById('customer_search')); }
        }
        function hideCustomerResults() { document.getElementById('customer-autocomplete').style.display = 'none'; }

        // Product Search
        function handleCodeKey(e, input, idx) {
            let res = document.getElementById('autocomplete-' + idx);
            if (res.style.display === 'block') {
                let items = res.querySelectorAll('div');
                if (e.key === 'ArrowDown') { e.preventDefault(); navigateResults(idx, 1); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); navigateResults(idx, -1); }
                else if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); (items[selectedIdx] || items[0]).click(); }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                // Move focus to description field
                input.closest('tr').querySelectorAll('input')[1].focus();
            }
            else if (e.key === 'ArrowDown') { e.preventDefault(); searchProduct(input, idx); }
        }

        function searchProduct(input, idx) {
            clearTimeout(searchTimeout);
            let res = document.getElementById('autocomplete-' + idx);
            let custId = document.getElementById('customer_id').value;
            searchTimeout = setTimeout(() => {
                fetch(`get_product.php?code=${encodeURIComponent(input.value)}&customer_id=${custId}`)
                .then(r => r.json()).then(data => {
                    res.innerHTML = ''; selectedIdx = -1;
                    if(data.length > 0) {
                        data.forEach(item => {
                            let div = document.createElement('div'); div.textContent = item.code + ' - ' + item.description;
                            div.onclick = function() {
                                let r = input.closest('tr'); input.value = item.code;
                                r.querySelector('.input-desc').value = item.description || '';
                                r.querySelector('.input-unit').value = item.unit || '';
                                r.querySelector('.input-price').value = item.unit_price || 0;
                                r.querySelector('.input-tax').value = item.tax_percent || 0;
                                if (item.last_disc) r.querySelector('.input-disc').value = item.last_disc;
                                res.style.display = 'none'; r.querySelector('.input-qty').focus(); calcLine(input); saveDraft();
                            };
                            res.appendChild(div);
                        });
                        res.style.display = 'block';
                    } else res.style.display = 'none';
                });
            }, 200);
        }

        function navigateResults(idx, dir) {
            let res = document.getElementById('autocomplete-' + idx), items = res.querySelectorAll('div');
            if (!items.length) return;
            selectedIdx = (selectedIdx + dir + items.length) % items.length;
            items.forEach((it, i) => it.classList.toggle('selected', i === selectedIdx));
        }
        function hideResults(idx) { document.getElementById('autocomplete-' + idx).style.display = 'none'; }

        // Calculations
        function calcLine(el) {
            let row = el.closest('tr');
            let q = parseFloat(row.querySelector('.input-qty').value) || 0, p = parseFloat(row.querySelector('.input-price').value) || 0, d = parseFloat(row.querySelector('.input-disc').value) || 0;
            let gross = q * p, nett = gross - (gross * (d / 100));
            row.querySelector('.input-nett').value = nett.toFixed(2); calcTotals();
        }

        function calcTotals() {
            let n = 0, t = 0, d = 0;
            document.querySelectorAll('#linesTable tbody tr').forEach(row => {
                let q = parseFloat(row.querySelector('.input-qty').value) || 0, p = parseFloat(row.querySelector('.input-price').value) || 0, ds = parseFloat(row.querySelector('.input-disc').value) || 0, tx = parseFloat(row.querySelector('.input-tax').value) || 0;
                let gr = q * p, dam = gr * (ds / 100), nt = gr - dam;
                n += gr; d += dam; t += nt * (tx / 100);
            });
            document.getElementById('t_nett').value = n.toFixed(2); document.getElementById('t_disc').value = d.toFixed(2); document.getElementById('t_excl').value = (n-d).toFixed(2); document.getElementById('t_tax').value = t.toFixed(2); document.getElementById('t_grand').value = (n-d+t).toFixed(2);
        }

        // Persistence
        function saveDraft() {
            let d = { lines: [] };
            d.edit_invoice_id = document.getElementById('edit_invoice_id').value;
            d.from_orders = document.getElementById('from_orders').value;
            d.status = document.getElementById('invoice_status').value;
            d.customer_id = document.getElementById('customer_id').value;
            d.customer_search = document.getElementById('customer_search').value;
            d.date = document.querySelector('input[name="date"]').value;
            d.customer_email = document.getElementById('customer_email').value;
            d.mail_cc = document.getElementById('mail_cc').value;
            d.mail_subject = document.getElementById('mail_subject').value;
            d.auto_mail = document.getElementById('auto_mail').checked;
            d.auto_print = document.getElementById('auto_print').checked;
            d.notes = document.getElementById('invoice_notes').value;
            document.querySelectorAll('#linesTable tbody tr').forEach(tr => {
                d.lines.push({ code: tr.querySelector('.input-code').value, description: tr.querySelector('.input-desc').value, quantity: tr.querySelector('.input-qty').value, unit: tr.querySelector('.input-unit').value, unit_price: tr.querySelector('.input-price').value, disc_percent: tr.querySelector('.input-disc').value, tax_percent: tr.querySelector('.input-tax').value, nett_price: tr.querySelector('.input-nett').value });
            });
            localStorage.setItem('acc_invoice_draft', JSON.stringify(d));
        }

        function loadDraft() {
            let dr = localStorage.getItem('acc_invoice_draft'); if (!dr) return;
            let data = JSON.parse(dr);
            document.getElementById('edit_invoice_id').value = data.edit_invoice_id || '';
            document.getElementById('from_orders').value = data.from_orders || '';
            document.getElementById('invoice_status').value = data.status || 'unpaid';
            toggleUpdateBtn();
            document.getElementById('customer_id').value = data.customer_id || '';
            document.getElementById('customer_search').value = data.customer_search || '';
            // Set invoice type dropdown and update placeholder
            if (data.invoice_type) {
                let typeSelect = document.getElementById('invoice_type');
                typeSelect.value = data.invoice_type;
                document.getElementById('customer_search').placeholder = 
                    data.invoice_type === 'supplier' ? 'Search supplier...' : 'Search customer...';
            }
            document.querySelector('input[name="date"]').value = data.date || '';
            document.getElementById('customer_email').value = data.customer_email || '';
            document.getElementById('mail_cc').value = data.mail_cc || '';
            document.getElementById('mail_subject').value = data.mail_subject || 'Tax Invoice';
            document.getElementById('auto_mail').checked = !!data.auto_mail;
            document.getElementById('auto_print').checked = data.auto_print !== false ? !!data.auto_print : true;
            document.getElementById('invoice_notes').value = data.notes || '';
            if (data.lines && data.lines.length) {
                let tb = document.querySelector('#linesTable tbody'); tb.innerHTML = ''; lineCount = 0;
                data.lines.forEach((l, i) => {
                    let tr = document.createElement('tr'); tr.setAttribute('data-index', i);
                    tr.innerHTML = `<td style="position: relative;"><input type="text" name="lines[${i}][code]" class="input-code" value="${l.code}" autocomplete="off" onkeydown="handleCodeKey(event, this, ${i})" oninput="searchProduct(this, ${i})" onblur="setTimeout(() => hideResults(${i}), 200)" onfocus="this.select()"><div class="autocomplete-results" id="autocomplete-${i}"></div></td><td><input type="text" name="lines[${i}][description]" class="input-desc" value="${l.description}" onchange="saveDraft()" onfocus="this.select()"></td><td><input type="number" step="0.01" name="lines[${i}][quantity]" class="input-qty calc" value="${l.quantity}" onchange="calcLine(this); saveDraft()" onkeyup="calcLine(this)" onfocus="this.select()"></td><td><input type="text" name="lines[${i}][unit]" class="input-unit" value="${l.unit}" onchange="saveDraft()" onfocus="this.select()"></td><td><input type="number" step="0.01" name="lines[${i}][unit_price]" class="input-price calc" value="${l.unit_price}" onchange="calcLine(this); saveDraft()" onkeyup="calcLine(this)" onfocus="this.select()"></td><td><input type="number" step="0.01" name="lines[${i}][disc_percent]" class="input-disc calc" value="${l.disc_percent}" onchange="calcLine(this); saveDraft()" onkeyup="calcLine(this)" onfocus="this.select()"></td><td><input type="number" step="0.01" name="lines[${i}][tax_percent]" class="input-tax calc" value="${l.tax_percent}" onchange="calcLine(this); saveDraft()" onkeyup="calcLine(this)" onfocus="this.select()"></td><td><input type="number" step="0.01" name="lines[${i}][nett_price]" class="input-nett" value="${l.nett_price}" readonly></td><td style="text-align: center;"><button type="button" class="btn-delete-line" onclick="deleteLine(this)" title="Delete this line">✕</button></td>`;
                    tb.appendChild(tr); lineCount++;
                });
                calcTotals();
            }
        }

        function cancelInvoice() { if (confirm("Clear this invoice?")) { localStorage.removeItem('acc_invoice_draft'); window.location.reload(); } }
        
        function resetForm() {
            const form = document.getElementById('invoiceForm');
            localStorage.removeItem('acc_invoice_draft');
            form.reset();
            document.getElementById('customer_id').value = '';
            document.getElementById('account_ref').value = '';
            document.getElementById('invoice_notes').value = '';
            document.querySelector('#linesTable tbody').innerHTML = '';
            lineCount = 0;
            addNewLine();
            calcTotals();
            toggleUpdateBtn();
            setTimeout(() => document.getElementById('customer_search').focus(), 50);
        }

        function toggleUpdateBtn() {
            const status = document.getElementById('invoice_status').value;
            const btn = document.getElementById('btn_update');
            const btnDone = document.getElementById('btn_done');
            const fromOrders = document.getElementById('from_orders').value;
            
            if (status === 'order') {
                btn.style.display = 'inline-block';
                btn.textContent = document.getElementById('edit_invoice_id').value ? 'Update' : 'Save as Order';
                
                // If editing from orders dashboard, hide Done button
                if (fromOrders) {
                    btnDone.style.display = 'none';
                } else {
                    btnDone.style.display = 'inline-block';
                }
            } else {
                btn.style.display = 'none';
                btnDone.style.display = 'inline-block';
            }
        }

        function printInIframe(invoiceId) {
            const iframe = document.getElementById('print-frame');
            iframe.onload = function() {
                try {
                    // Refocus customer search once the print dialog closes
                    iframe.contentWindow.addEventListener('afterprint', function() {
                        window.focus();
                        setTimeout(() => document.getElementById('customer_search').focus(), 100);
                    });
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch(e) {
                    window.open('print_invoice.php?id=' + invoiceId, '_blank');
                    setTimeout(() => document.getElementById('customer_search').focus(), 500);
                }
                iframe.onload = null;
            };
            iframe.src = 'print_invoice.php?id=' + invoiceId;
        }

        function submitForm(actionType = 'done') {
            if (actionType === 'update') {
                document.getElementById('invoice_status').value = 'order';
                document.getElementById('auto_print').checked = false;
            } else if (actionType === 'done') {
                // Keep status as 'order' - don't change it to 'unpaid'
                // Status will only change to 'unpaid' when invoice is finalized/printed
            }

            const form = document.getElementById('invoiceForm');
            const formData = new FormData(form);
            const btn = actionType === 'update' ? document.getElementById('btn_update') : document.getElementById('btn_done');
            const statusDiv = document.getElementById('ajax-status');
            
            btn.disabled = true;
            let originalText = btn.textContent;
            btn.textContent = 'Saving...';
            
            fetch('invoice_create.php?ajax_save=1', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    statusDiv.style.display = 'block';
                    statusDiv.style.background = '#e8f5e9';
                    statusDiv.style.color = '#2e7d32';
                    statusDiv.style.border = '1px solid #2e7d32';
                    statusDiv.innerHTML = `<strong>Invoice ${data.no} saved!</strong> ${data.mail_status}`;

                    const shouldPrint = document.getElementById('auto_print').checked;

                    if (actionType === 'update') {
                        window.location.href = 'orders_dashboard.php';
                        return;
                    }

                    // Reset form immediately so it's ready for next invoice
                    resetForm();
                    
                    if (shouldPrint) {
                        // Print via hidden iframe — no new tab, print dialog appears over this page.
                        // Works for both physical printers and PDF printers.
                        printInIframe(data.id);
                    }
                    
                    setTimeout(() => { statusDiv.style.display = 'none'; }, 5000);
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(err => {
                alert('Connection Error: ' + err);
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = originalText;
            });
        }

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('new')) {
                localStorage.removeItem('acc_invoice_draft');
            } else {
                <?php if (!empty($edit_data_json)): ?>
                let dbDraft = <?= $edit_data_json ?>;
                localStorage.setItem('acc_invoice_draft', JSON.stringify(dbDraft));
                // Update title and button
                document.querySelector('h2').textContent = 'Edit Tax Invoice ' + dbDraft.mail_subject.replace('Tax Invoice ', '');
                <?php endif; ?>
                loadDraft();
            }
            
            <?php if (!empty($edit_data_json)): ?>
            // If editing an existing invoice or starting a new customer order, add an empty row (if needed) and focus its code cell
            if (document.querySelectorAll('#linesTable tbody tr').length === 0 || document.querySelector('#linesTable tbody tr:last-child .input-code').value !== '') {
                addNewLine();
            } else {
                document.querySelector('#linesTable tbody tr:last-child .input-code').focus();
            }
            <?php else: ?>
            document.getElementById('customer_search').focus();
            <?php endif; ?>
        };
    </script>

    <!-- Hidden iframe used for in-page printing (avoids opening a new tab) -->
    <iframe id="print-frame" style="display:none; width:0; height:0; border:none;" tabindex="-1"></iframe>

</body>
</html>
