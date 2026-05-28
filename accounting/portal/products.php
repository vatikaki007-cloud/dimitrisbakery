<?php
require_once __DIR__ . '/config.php';
require_portal_login();

$last_order_text = '';
if (!empty($_SESSION['portal_last_order_id'])) {
    $date = $_SESSION['portal_last_order_date'];
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($date == $today) {
        $last_order_text = "Today";
    } elseif ($date == $yesterday) {
        $last_order_text = "Yesterday";
    } else {
        $last_order_text = date('d M Y', strtotime($date));
    }
}

$pdo = get_db();
$customer_id = $_SESSION['portal_customer_id'];

// Fetch all online products
$stmt = $pdo->prepare("SELECT id, code, description, portal_description, unit_price, tax_percent, unit, photo FROM acc_products WHERE available_online = 1 ORDER BY description ASC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch custom pricing for this customer
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
        $p['tax_percent'] = $last_sale['tax_percent'];
        $p['disc_percent'] = $last_sale['disc_percent'];
        $p['has_price'] = true;
    } else {
        $p['unit_price'] = 0;
        $p['tax_percent'] = 0;
        $p['disc_percent'] = 0;
        $p['has_price'] = false;
    }
}
unset($p); // Fix PHP reference bug!
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Products | Order Portal</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; padding-bottom: 70px; }
        .header { background: #0056b3; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 18px; }
        .header .logout { color: #d0e1f9; text-decoration: none; font-size: 14px; }
        
        .disclaimer { background: #fff3cd; color: #856404; padding: 10px 15px; font-size: 12px; text-align: center; border-bottom: 1px solid #ffeeba; }
        
        .container { padding: 15px; }
        .product-card { background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; gap: 15px; }
        .product-img { width: 80px; height: 80px; border-radius: 8px; object-fit: cover; background: #eee; flex-shrink: 0; }
        .product-info { flex: 1; }
        .product-title { font-weight: bold; color: #333; margin-bottom: 5px; font-size: 15px; }
        .product-desc { font-size: 12px; color: #777; margin-bottom: 8px; line-height: 1.4; }
        .product-price { font-weight: bold; color: #0056b3; font-size: 16px; margin-bottom: 10px; }
        
        .add-controls { display: flex; align-items: center; justify-content: flex-end; }
        .btn-add { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 14px; }
        .btn-add.added { background: #6c757d; }
        
        /* Bottom Nav */
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: white; display: flex; border-top: 1px solid #ddd; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); z-index: 100; }
        .nav-item { flex: 1; text-align: center; padding: 15px 0; color: #555; text-decoration: none; font-size: 14px; font-weight: bold; position: relative; }
        .nav-item.active { color: #0056b3; border-bottom: 3px solid #0056b3; }
        .cart-badge { position: absolute; top: 8px; right: 25%; background: #dc3545; color: white; border-radius: 10px; padding: 2px 6px; font-size: 10px; min-width: 14px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Products</h1>
            <div style="font-size: 11px; color: #d0e1f9; margin-top: 2px;">Ordering for: <?= htmlspecialchars($_SESSION['portal_customer_name']) ?></div>
            <?php if ($last_order_text): ?>
                <div style="font-size: 11px; color: #a9cce3; margin-top: 2px;">Last Order: <strong><?= $last_order_text ?></strong></div>
            <?php endif; ?>
        </div>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="disclaimer">
        Prices shown are indicative based on your history.<br>Your confirmed invoice may differ.
    </div>

    <div class="container">
        <?php foreach ($products as $p): 
            $price_excl = $p['unit_price'];
            $disc_amt = $price_excl * ($p['disc_percent'] / 100);
            $nett_excl = $price_excl - $disc_amt;
            $has_price = $p['has_price'] ?? false;
        ?>
            <div class="product-card">
                <?php if (!empty($p['photo'])): ?>
                    <img src="../product_images/<?= htmlspecialchars($p['photo']) ?>" class="product-img" alt="Product">
                <?php else: ?>
                    <div class="product-img" style="display:flex; align-items:center; justify-content:center; color:#aaa; font-size:10px;">No Image</div>
                <?php endif; ?>
                
                <div class="product-info">
                    <div class="product-title"><?= htmlspecialchars($p['description']) ?></div>
                    <?php if (!empty($p['portal_description'])): ?>
                        <div class="product-desc"><?= htmlspecialchars($p['portal_description']) ?></div>
                    <?php endif; ?>
                    <?php if ($has_price): ?>
                        <div class="product-price">R <?= number_format($nett_excl, 2) ?> / <?= htmlspecialchars($p['unit']) ?></div>
                    <?php else: ?>
                        <div class="product-price" style="color: #ff9800; font-size: 13px;">Custom Quote Required<br>We'll contact you with pricing</div>
                    <?php endif; ?>
                </div>

                <div class="add-controls">
                    <button class="btn-add" id="add_<?= $p['code'] ?>" onclick="addToCart(<?= htmlspecialchars(json_encode([
                        'code' => $p['code'],
                        'description' => $p['description'],
                        'unit' => $p['unit'],
                        'unit_price' => $p['unit_price'],
                        'tax_percent' => $p['tax_percent'],
                        'disc_percent' => $p['disc_percent'],
                        'nett_price' => $nett_excl,
                        'has_price' => $has_price
                        'photo' => $p['photo']
                    ])) ?>)">Add</button>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
            <div style="text-align:center; padding: 30px; color:#888;">No products available for online ordering.</div>
        <?php endif; ?>
    </div>

    <div class="bottom-nav">
        <a href="products.php" class="nav-item active">Products</a>
        <a href="cart.php" class="nav-item">Cart <span class="cart-badge" id="cart-badge" style="display:none;">0</span></a>
    </div>

    <script>
        let cart = JSON.parse(localStorage.getItem('portal_cart')) || [];
        updateBadge();
        updateButtonStates();

        function addToCart(product) {
            let existing = cart.find(i => i.code === product.code);
            
            if (existing) {
                // Do not increase quantity, it's already in cart
                return;
            } else {
                product.quantity = 1;
                cart.push(product);
            }
            
            localStorage.setItem('portal_cart', JSON.stringify(cart));
            updateBadge();
            
            let btn = document.getElementById('add_' + product.code);
            btn.textContent = 'Added';
            btn.classList.add('added');
            setTimeout(() => {
                updateButtonStates();
            }, 1000);
        }

        function updateButtonStates() {
            // Reset all buttons first
            document.querySelectorAll('.btn-add').forEach(btn => {
                btn.textContent = 'Add';
                btn.classList.remove('added');
                btn.style.background = '#28a745';
                btn.style.cursor = 'pointer';
            });
            
            // Mark items in cart
            cart.forEach(item => {
                let btn = document.getElementById('add_' + item.code);
                if (btn) {
                    btn.textContent = 'In Cart ✓';
                    btn.style.background = '#6c757d'; 
                    btn.style.cursor = 'default';
                }
            });
        }

        function updateBadge() {
            let badge = document.getElementById('cart-badge');
            let count = cart.reduce((sum, item) => sum + parseInt(item.quantity), 0);
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    </script>
</body>
</html>
