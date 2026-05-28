<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

try {
    $pdo = get_db();
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

if (!isset($_GET['id'])) {
    die("Invoice ID required.");
}

$invoice_id = $_GET['id'];

// Fetch Business Settings (with safety check)
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM acc_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $settings = [];
}

// Ensure defaults if settings are empty
$settings['bus_name'] = $settings['bus_name'] ?? 'DIMITRIS CONFECTIONERY/BAKERY';
$settings['bus_address_mid'] = $settings['bus_address_mid'] ?? "P O BOX 1291\nSANLAMHOF\nBELLVILLE";
$settings['bus_vat'] = $settings['bus_vat'] ?? '4410265468';
$settings['bus_phone'] = $settings['bus_phone'] ?? '079 9815410';
$settings['bus_ordering_no'] = $settings['bus_ordering_no'] ?? '0799815410';
$settings['bus_bank_info'] = $settings['bus_bank_info'] ?? "BANK ...CAPITEC BUSINESS\nA/C 1053011342";
$settings['bus_halaal_no'] = $settings['bus_halaal_no'] ?? '55693';

// Fetch Header (use LEFT JOIN in case customer is missing)
$stmt = $pdo->prepare("SELECT i.*, c.name, c.account_ref, c.tax_reference, c.email, c.telephone, c.address, r.route_name 
                       FROM acc_invoices i 
                       LEFT JOIN acc_customers c ON i.entity_id = c.id 
                       LEFT JOIN acc_routes r ON c.route_id = r.id
                       WHERE i.id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    die("Invoice not found (ID: " . htmlspecialchars($invoice_id) . ")");
}

// Fetch Lines
try {
    $stmt = $pdo->prepare("SELECT * FROM acc_invoice_lines WHERE invoice_id = ?");
    $stmt->execute([$invoice_id]);
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Table Error (Lines): " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice <?= htmlspecialchars($invoice['invoice_no']) ?></title>
    <style>
        @page { size: A4; margin: 0; }
        body { font-family: "Courier New", Courier, monospace; font-size: 12px; color: #000; background: #fff; margin: 0; padding: 0; line-height: 1.4; }
        .page-container { width: 100%; position: relative; padding: 15mm; box-sizing: border-box; }
        
        /* Header Layout */
        .header-top { display: flex; justify-content: space-between; margin-bottom: 20px; align-items: flex-start; }
        .col-left { width: 30%; }
        .col-mid { width: 35%; text-align: center; }
        .col-right { width: 30%; text-align: right; }
        
        .bus-name { font-weight: bold; font-size: 14px; display: block; margin-bottom: 5px; }
        
        /* Document Info */
        .doc-info { margin: 20px 0; text-align: right; }
        .doc-type { text-align: center; font-weight: bold; margin-bottom: 10px; display: block; width: 100%; }
        .doc-header-row { display: flex; justify-content: flex-end; margin-bottom: 5px; }
        .doc-header-label { width: 120px; text-align: left; }
        .doc-header-value { width: 100px; text-align: right; }
        
        /* Customer & Account Info */
        .customer-section { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .cust-details { width: 45%; }
        .acc-details { width: 50%; display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 5px 0; margin-top: 20px; }
        .acc-details div { font-size: 11px; }
        .acc-label { font-weight: bold; margin-bottom: 5px; }
        
        /* Grid */
        table.invoice-grid { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table.invoice-grid th { text-align: left; border-bottom: 1px solid #000; padding: 5px 0; font-size: 11px; }
        table.invoice-grid td { padding: 4px 0; font-size: 11px; vertical-align: top; }
        table.invoice-grid .num { text-align: right; padding-right: 5px; }
        
        /* Totals */
        .totals-section { display: flex; align-items: flex-start; gap: 20px; margin-top: 20px; }
        .notes-col { flex: 1; font-size: 11px; }
        .totals-container { width: 300px; flex-shrink: 0; }
        .totals-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .totals-bold { font-weight: bold; margin-top: 10px; padding-top: 5px; border-top: 1px solid #000; }
        
        /* Footer */
        .footer-section { clear: both; margin-top: 100px; display: flex; justify-content: space-between; }
        .footer-col { width: 45%; }
        .sig-row { display: flex; align-items: flex-end; margin-top: 20px; }
        .sig-line { border-bottom: 1px solid #000; flex-grow: 1; margin: 0 5px; min-width: 150px; height: 1px; }
        
        .no-print { background: #f0f0f0; padding: 10px; border-bottom: 1px solid #ddd; margin-bottom: 20px; text-align: center; }
        @media print { 
            .no-print { display: none; } 
            .page-container { page-break-after: always; }
            .page-container:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 8px 20px; cursor: pointer; font-weight: bold; background: #0056b3; color: white; border: none; border-radius: 4px;">Print Invoice</button>
        <?php
        $back_url = "invoice_create.php";
        if (!empty($_GET['return'])) {
            $back_url = $_GET['return'];
        }
        ?>
        <a href="<?= htmlspecialchars($back_url) ?>" style="margin-left: 15px; color: #0056b3;">Back</a>
    </div>

    <?php for ($i = 0; $i < 2; $i++): ?>
    <div class="page-container">
        <!-- Header -->
        <div class="header-top">
            <div class="col-left">
                <strong><?= htmlspecialchars($invoice['name'] ?? '') ?></strong><br>
                <?php if (!empty($invoice['route_name'])): ?>
                    <div style="font-weight: bold; margin-top: 5px;">Route: <?= htmlspecialchars($invoice['route_name']) ?></div>
                <?php endif; ?>
                <?php if (!empty($invoice['address'])): ?>
                    <div style="margin-top: 5px; font-weight: normal;">
                        <?= nl2br(htmlspecialchars($invoice['address'])) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-mid">
                <span class="bus-name"><?= htmlspecialchars($settings['bus_name'] ?? '') ?></span>
                <?= nl2br(htmlspecialchars($settings['bus_address_mid'] ?? '')) ?><br>
                VAT: <?= htmlspecialchars($settings['bus_vat'] ?? '') ?><br>
                OFFICE: <?= htmlspecialchars($settings['bus_phone'] ?? '') ?>
            </div>
            <div class="col-right">
                ORDERING NUMBER<br>
                <?= htmlspecialchars($settings['bus_ordering_no'] ?? '') ?><br>
                <?= nl2br(htmlspecialchars($settings['bus_bank_info'] ?? '')) ?><br>
                HALAAL NO. <?= htmlspecialchars($settings['bus_halaal_no'] ?? '') ?>
            </div>
        </div>

        <!-- Document Header -->
        <div class="doc-info">
            <span class="doc-type">Copy Tax Invoice</span>
            <div class="doc-header-row">
                <div class="doc-header-label">Document No</div>
                <div class="doc-header-value"><?= htmlspecialchars($invoice['invoice_no'] ?? '') ?></div>
            </div>
            <div class="doc-header-row">
                <div class="doc-header-label">Date</div>
                <div class="doc-header-value"><?= date('d/m/y', strtotime($invoice['date'])) ?></div>
            </div>
            <div class="doc-header-row">
                <div class="doc-header-label">Page</div>
                <div class="doc-header-value">1</div>
            </div>
        </div>

        <!-- Customer section moved to top left -->

        <div style="text-align: right; font-size: 10px; margin-bottom: 5px;">Exclusive</div>

        <!-- Lines Grid -->
        <table class="invoice-grid">
            <thead>
                <tr>
                    <th style="width:10%">Code</th>
                    <th style="width:35%">Description</th>
                    <th style="width:10%" class="num">Quantity</th>
                    <th style="width:10%">Unit</th>
                    <th style="width:10%" class="num">Unit price</th>
                    <th style="width:8%" class="num">Disc%</th>
                    <th style="width:8%" class="num">Tax</th>
                    <th style="width:12%" class="num">Nett price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $line): ?>
                    <tr>
                        <td><?= htmlspecialchars($line['code'] ?? '') ?></td>
                        <td><?= htmlspecialchars($line['description'] ?? '') ?></td>
                        <td class="num"><?= number_format((float)($line['quantity'] ?? 0), 2, ',', '') ?></td>
                        <td><?= htmlspecialchars($line['unit'] ?? '') ?></td>
                        <td class="num"><?= number_format((float)($line['unit_price'] ?? 0), 2, ',', '') ?></td>
                        <td class="num"><?= number_format((float)($line['disc_percent'] ?? 0), 2, ',', '') ?>%</td>
                        <td class="num"><?= number_format((float)($line['tax_percent'] ?? 0), 2, ',', '') ?>%</td>
                        <td class="num"><?= number_format((float)($line['nett_price'] ?? 0), 2, ',', '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals + Notes side by side -->
        <div class="totals-section">
            <div class="notes-col">
                <?php if (!empty(trim($invoice['notes'] ?? ''))): ?>
                <strong>Notes:</strong><br>
                <?= nl2br(htmlspecialchars($invoice['notes'])) ?>
                <?php endif; ?>
            </div>
            <div class="totals-container">
                <div class="totals-row"><span>Total nett price</span> <span><?= number_format((float)($invoice['total_nett'] ?? 0), 2, ',', '') ?></span></div>
                <div class="totals-row"><span>Discount</span> <span style="margin-left: 20px;"><?= number_format((float)($invoice['discount_percent'] ?? 0), 2, ',', '') ?>%</span> <span><?= number_format((float)($invoice['discount'] ?? 0), 2, ',', '') ?></span></div>
                <div class="totals-row" style="margin-top:10px;"><span>Amount excl tax</span> <span><?= number_format((float)($invoice['amount_excl'] ?? 0), 2, ',', '') ?></span></div>
                <div class="totals-row"><span>Tax</span> <span><?= number_format((float)($invoice['tax'] ?? 0), 2, ',', '') ?></span></div>
                <div class="totals-row totals-bold"><span>TOTAL</span> <span><?= number_format((float)($invoice['total'] ?? 0), 2, ',', '') ?></span></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-section">
            <div class="footer-col" style="width: 40%;">
                <div class="sig-row">Received in good order</div>
                <div class="sig-row" style="margin-top:40px;">Signature <div class="sig-line"></div></div>
            </div>
            <div class="footer-col" style="width: 50%;">
                <div class="sig-row">Date: <div class="sig-line" style="min-width: 250px;"></div></div>
                <div class="sig-row" style="margin-top:40px;">Name: <div class="sig-line" style="min-width: 250px;"></div></div>
            </div>
        </div>
    </div>
    <?php endfor; ?>


    <script>
        <?php if (isset($_GET['print'])): ?>
        window.onload = function() {
            // Wait for any rendering to finish
            setTimeout(function() {
                window.print();
            }, 800);
        }
        <?php if (!empty($_GET['return'])): ?>
        window.onafterprint = function() {
            window.location.href = <?= json_encode($_GET['return']) ?>;
        }
        <?php endif; ?>
        <?php endif; ?>
    </script>
</body>
</html>
