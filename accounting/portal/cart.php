<?php
require_once __DIR__ . '/config.php';
require_portal_login();

$last_order_text = '';
$last_order_lines_json = '[]';

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
    
    // Fetch last order lines
    $pdo = get_db();
    $stmt_lines = $pdo->prepare("SELECT l.*, p.photo FROM acc_invoice_lines l LEFT JOIN acc_products p ON l.code = p.code WHERE l.invoice_id = ? ORDER BY l.id ASC");
    $stmt_lines->execute([$_SESSION['portal_last_order_id']]);
    $lines = $stmt_lines->fetchAll(PDO::FETCH_ASSOC);
    
    $cart_lines = [];
    foreach ($lines as $l) {
        $cart_lines[] = [
            'code' => $l['code'],
            'description' => $l['description'],
            'unit' => $l['unit'],
            'unit_price' => $l['unit_price'],
            'tax_percent' => $l['tax_percent'],
            'disc_percent' => $l['disc_percent'],
            'nett_price' => $l['unit_price'] - ($l['unit_price'] * ($l['disc_percent']/100)),
            'photo' => $l['photo'],
            'quantity' => (float)$l['quantity']
        ];
    }
    $last_order_lines_json = json_encode($cart_lines);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Cart | Order Portal</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; margin: 0; padding-bottom: 90px; }
        .header { background: #0056b3; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 18px; }
        .header .logout { color: #d0e1f9; text-decoration: none; font-size: 14px; }
        
        .disclaimer { background: #fff3cd; color: #856404; padding: 10px 15px; font-size: 12px; text-align: center; border-bottom: 1px solid #ffeeba; }
        
        .container { padding: 15px; }
        .cart-item { background: white; border-radius: 8px; padding: 15px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 10px; }
        .item-row { display: flex; justify-content: space-between; align-items: flex-start; }
        .item-title { font-weight: bold; color: #333; font-size: 15px; }
        .item-price { color: #0056b3; font-weight: bold; }
        .item-remove { color: #dc3545; background: none; border: none; padding: 5px; font-size: 12px; font-weight: bold; cursor: pointer; }
        
        .add-controls { display: flex; align-items: center; gap: 10px; }
        .qty-btn { width: 30px; height: 30px; border-radius: 4px; border: 1px solid #ccc; background: white; font-size: 16px; font-weight: bold; color: #333; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .qty-input { width: 40px; height: 30px; text-align: center; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; font-weight: bold; }
        .item-subtotal { font-weight: bold; margin-left: auto; }

        .totals { background: white; padding: 15px; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .total-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 14px; }
        .total-row.grand { border-top: 1px solid #eee; margin-top: 10px; padding-top: 10px; font-weight: bold; font-size: 18px; color: #0056b3; }
        
        .btn-order { background: #28a745; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; margin-top: 20px; box-shadow: 0 4px 6px rgba(40,167,69,0.3); }
        .btn-order:disabled { background: #6c757d; cursor: not-allowed; box-shadow: none; }

        .success-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: white; z-index: 200; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 20px; box-sizing: border-box; }
        .success-icon { font-size: 80px; color: #28a745; margin-bottom: 20px; }
        .success-title { font-size: 24px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .success-msg { color: #666; margin-bottom: 30px; }
        .btn-continue { background: #0056b3; color: white; text-decoration: none; padding: 12px 30px; border-radius: 4px; font-weight: bold; }

        /* Bottom Nav */
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: white; display: flex; border-top: 1px solid #ddd; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); z-index: 90; }
        .nav-item { flex: 1; text-align: center; padding: 15px 0; color: #555; text-decoration: none; font-size: 14px; font-weight: bold; position: relative; }
        .nav-item.active { color: #0056b3; border-bottom: 3px solid #0056b3; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Your Cart</h1>
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

    <div class="container" id="cart-container">
        <!-- Rendered via JS -->
    </div>

    <div class="success-overlay" id="success-view">
        <div class="success-icon">✓</div>
        <div class="success-title">Order Placed!</div>
        <div class="success-msg">Your order has been sent successfully.<br>We will review and confirm your invoice shortly.</div>
        <a href="cart.php" class="btn-continue">Back to Cart</a>
    </div>

    <div class="bottom-nav">
        <a href="products.php" class="nav-item">Products</a>
        <a href="cart.php" class="nav-item active">Cart</a>
    </div>

    <script>
        let cart = JSON.parse(localStorage.getItem('portal_cart')) || [];
        
        <?php if (!empty($_SESSION['portal_last_order_id'])): ?>
        let cartInitialized = sessionStorage.getItem('cart_initialized');
        if (cart.length === 0 && !cartInitialized) {
            cart = <?= $last_order_lines_json ?>;
            localStorage.setItem('portal_cart', JSON.stringify(cart));
        }
        sessionStorage.setItem('cart_initialized', '1');
        <?php endif; ?>

        function renderCart() {
            let container = document.getElementById('cart-container');
            
            if (cart.length === 0) {
                container.innerHTML = '<div style="text-align:center; padding:50px 20px; color:#888;">Your cart is empty.<br><br><a href="products.php" style="color:#0056b3; font-weight:bold; text-decoration:none;">Go back to products</a></div>';
                return;
            }

            let html = '';
            let totalExcl = 0;
            let totalTax = 0;
            
            cart.forEach((item, index) => {
                let qty = parseInt(item.quantity);
                let priceExcl = parseFloat(item.unit_price);
                let discPercent = parseFloat(item.disc_percent || 0);
                let taxPercent = parseFloat(item.tax_percent || 0);
                
                let discAmt = priceExcl * (discPercent / 100);
                let nettExcl = priceExcl - discAmt;
                let lineTotalExcl = nettExcl * qty;
                let lineTax = lineTotalExcl * (taxPercent / 100);
                
                totalExcl += lineTotalExcl;
                totalTax += lineTax;

                let photoHtml = item.photo ? 
                    `<img src="../product_images/${item.photo}" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover; margin-right: 15px; background: #eee; flex-shrink: 0;">` :
                    `<div style="width: 50px; height: 50px; border-radius: 8px; background: #eee; margin-right: 15px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #aaa; text-align: center; flex-shrink: 0;">No Image</div>`;

                html += `
                    <div class="cart-item">
                        <div class="item-row">
                            <div style="display: flex; align-items: center;">
                                ${photoHtml}
                                <div>
                                    <div class="item-title">${item.description}</div>
                                    <div class="item-price">R ${nettExcl.toFixed(2)} / ${item.unit}</div>
                                </div>
                            </div>
                            <button class="item-remove" onclick="removeItem(${index})">Remove</button>
                        </div>
                        <div class="item-row" style="align-items:center; margin-top: 10px;">
                            <div class="add-controls">
                                <button class="qty-btn" onclick="updateCartQty(${index}, -1)">-</button>
                                <input type="number" class="qty-input" value="${qty}" readonly>
                                <button class="qty-btn" onclick="updateCartQty(${index}, 1)">+</button>
                            </div>
                            <div class="item-subtotal">R ${lineTotalExcl.toFixed(2)}</div>
                        </div>
                    </div>
                `;
            });
            
            let grandTotal = totalExcl + totalTax;

            html += `
                <div class="totals">
                    <div class="total-row"><span>Subtotal (Excl)</span> <span>R ${totalExcl.toFixed(2)}</span></div>
                    <div class="total-row"><span>Estimated Tax</span> <span>R ${totalTax.toFixed(2)}</span></div>
                    <div class="total-row grand"><span>ESTIMATED TOTAL</span> <span>R ${grandTotal.toFixed(2)}</span></div>
                </div>
                <button class="btn-order" id="btn-submit" onclick="placeOrder()">Place Order</button>
            `;

            container.innerHTML = html;
        }

        function updateCartQty(index, delta) {
            cart[index].quantity += delta;
            if (cart[index].quantity < 1) cart[index].quantity = 1;
            saveAndRender();
        }

        function removeItem(index) {
            cart.splice(index, 1);
            saveAndRender();
        }

        function saveAndRender() {
            localStorage.setItem('portal_cart', JSON.stringify(cart));
            renderCart();
        }

        function placeOrder() {
            let btn = document.getElementById('btn-submit');
            btn.disabled = true;
            btn.textContent = 'Sending...';

            fetch('place_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ lines: cart })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    // Do not clear cart as per user request
                    document.getElementById('success-view').style.display = 'flex';
                } else {
                    alert('Error placing order: ' + (res.error || 'Unknown error'));
                    btn.disabled = false;
                    btn.textContent = 'Place Order';
                }
            })
            .catch(err => {
                alert('Network error placing order.');
                btn.disabled = false;
                btn.textContent = 'Place Order';
            });
        }

        renderCart();
    </script>
</body>
</html>
