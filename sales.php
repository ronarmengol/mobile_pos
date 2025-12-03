<?php
require_once 'functions.php';
requireLogin();

// Prevent access if subscription expired
if (isset($_SESSION['subscription_expired']) && $_SESSION['subscription_expired']) {
    header("Location: dashboard.php");
    exit();
}

$shop_id = $_SESSION['shop_id'];

// Handle Sale Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_sale'])) {
    $data = json_decode($_POST['cart_data'], true);
    $total = (float)$_POST['final_total'];
    $payment = sanitize($conn, $_POST['payment_method']);
    $user_id = $_SESSION['user_id'];

    if (!empty($data)) {
        // Get the next shop order number for this shop
        $order_num_result = mysqli_query($conn, "SELECT COALESCE(MAX(shop_order_number), 0) + 1 AS next_num FROM sales WHERE shop_id = $shop_id");
        $order_num_row = mysqli_fetch_assoc($order_num_result);
        $shop_order_number = $order_num_row['next_num'];
        
        // Create Sale with shop-specific order number
        $sql = "INSERT INTO sales (shop_id, shop_order_number, user_id, total, payment_method) VALUES ($shop_id, $shop_order_number, $user_id, $total, '$payment')";
        if (mysqli_query($conn, $sql)) {
            $sale_id = mysqli_insert_id($conn);
            
            // Create Sale Items & Update Stock
            foreach ($data as $item) {
                $pid = (int)$item['id'];
                $qty = (int)$item['qty'];
                $price = (float)$item['price'];
                
                mysqli_query($conn, "INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES ($sale_id, $pid, $qty, $price)");
                mysqli_query($conn, "UPDATE products SET stock = stock - $qty WHERE id = $pid");
            }
            $success = "Sale completed!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

// Fetch Products for JS
$products_res = mysqli_query($conn, "SELECT * FROM products WHERE shop_id = $shop_id AND stock > 0 ORDER BY name");
$products = [];
while ($row = mysqli_fetch_assoc($products_res)) {
    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Sale - Takeaway POS</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        const products = <?php echo json_encode($products); ?>;
    </script>
</head>
<body class="sales-page">
    <!-- Minimal Top Bar -->
    <div class="sales-topbar">
        <a href="dashboard.php" class="btn-back">←</a>
        <h2 style="margin: 0; font-size: 1.2rem;">New Sale</h2>
        <button onclick="resetCart()" class="btn-reset" title="Clear all orders">🗑️</button>
    </div>

    <!-- Main Content: Products Grid (Full Screen) -->
    <div class="products-fullscreen">
        <div class="product-grid" id="productGrid">
            <!-- JS will populate this -->
        </div>
    </div>

    <!-- Cart Items Overlay (Slides up when items added) -->
    <div class="cart-overlay" id="cartOverlay">
        <div class="cart-handle" onclick="toggleCart()">
            <div class="handle-bar"></div>
        </div>
        <div class="cart-items-list" id="cartItems">
            <!-- Cart items here -->
        </div>
    </div>

    <!-- Bottom Action Bar (Fixed) -->
    <div class="bottom-action-bar">
        <div class="total-display">
            <span class="total-label">TOTAL</span>
            <span class="total-value" id="cartTotal">K0.00</span>
        </div>
        <div class="action-buttons">
            <button type="button" onclick="toggleCart()" class="btn-view-cart">
                <span id="cartItemCount">0</span> Items
            </button>
            <button type="button" onclick="showPaymentModal()" class="btn-checkout">
                PAY NOW →
            </button>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="payment-modal" id="paymentModal">
        <div class="payment-content">
            <div class="payment-header">
                <h3>Complete Payment</h3>
                <button onclick="closePaymentModal()" class="btn-close">✕</button>
            </div>
            <div class="payment-body">
                <div class="payment-total">
                    <span>Total Amount</span>
                    <strong id="paymentTotal">K0.00</strong>
                </div>
                <form method="POST" id="saleForm">
                    <input type="hidden" name="cart_data" id="cartData">
                    <input type="hidden" name="final_total" id="finalTotal">
                    
                    <label>Payment Method</label>
                    <div class="payment-methods">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="Cash" checked>
                            <span>💵 Cash</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="Card">
                            <span>💳 Card</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="Mobile">
                            <span>📱 Mobile Money</span>
                        </label>
                    </div>
                    
                    <button type="button" onclick="completeSale()" class="btn-confirm-payment">
                        CONFIRM PAYMENT
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/modal.js"></script>
    <script src="js/main.js"></script>
    <script>
        let cartExpanded = false;
        
        // Toggle cart overlay
        function toggleCart() {
            cartExpanded = !cartExpanded;
            const overlay = document.getElementById('cartOverlay');
            overlay.classList.toggle('expanded', cartExpanded);
        }
        
        // Show payment modal
        function showPaymentModal() {
            if (cart.length === 0) {
                Modal.alert("Please add items to cart first.", "Cart is Empty");
                return;
            }
            document.getElementById('paymentModal').classList.add('active');
            document.getElementById('paymentTotal').textContent = document.getElementById('cartTotal').textContent;
        }
        
        // Close payment modal
        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
        }
        
        // Update cart badge
        function updateCartBadge() {
            const count = cart.reduce((sum, item) => sum + item.qty, 0);
            document.getElementById('cartItemCount').textContent = count;
            
            // Auto-expand cart when items added
            if (count > 0 && !cartExpanded) {
                toggleCart();
            }
        }
        
        // Reset cart (clear all orders)
        function resetCart() {
            if (cart.length === 0) {
                Modal.alert("Cart is already empty.", "No Orders");
                return;
            }
            
            Modal.confirm(
                "Are you sure you want to clear all orders from the cart?",
                () => {
                    cart = [];
                    renderCart();
                    if (cartExpanded) {
                        toggleCart();
                    }
                },
                "Clear Cart"
            );
        }
        
        // Override renderCart to update badge
        const originalRenderCart = renderCart;
        renderCart = function() {
            originalRenderCart();
            updateCartBadge();
        };
    </script>
</body>
</html>
