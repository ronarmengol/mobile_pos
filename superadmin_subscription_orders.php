<?php
require_once 'functions.php';
requireSuperAdmin();

$message = '';
$error = '';

// Handle order approval
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_order'])) {
    $order_id = (int)$_POST['order_id'];
    $shop_id = (int)$_POST['shop_id'];
    $months = (int)$_POST['months'];
    $admin_message = sanitize($conn, $_POST['admin_message']);
    
    // Get current subscription expiry
    $shop_query = "SELECT subscription_expiry FROM shops WHERE id = $shop_id";
    $shop_result = mysqli_query($conn, $shop_query);
    $shop = mysqli_fetch_assoc($shop_result);
    
    // Calculate new expiry date
    $current_expiry = $shop['subscription_expiry'];
    if ($current_expiry && strtotime($current_expiry) > time()) {
        // Extend from current expiry
        $new_expiry = date('Y-m-d', strtotime($current_expiry . " +$months months"));
    } else {
        // Start from today
        $new_expiry = date('Y-m-d', strtotime("+$months months"));
    }
    
    // Update shop subscription
    $update_shop = "UPDATE shops SET subscription_expiry = '$new_expiry', status = 'active' WHERE id = $shop_id";
    
    // Update order status
    $update_order = "UPDATE subscription_orders SET status = 'approved', admin_message = '$admin_message' WHERE id = $order_id";
    
    if (mysqli_query($conn, $update_shop) && mysqli_query($conn, $update_order)) {
        $message = "Order approved successfully! Subscription extended to $new_expiry.";
    } else {
        $error = "Error approving order: " . mysqli_error($conn);
    }
}

// Handle order rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reject_order'])) {
    $order_id = (int)$_POST['order_id'];
    $admin_message = sanitize($conn, $_POST['admin_message']);
    
    $sql = "UPDATE subscription_orders SET status = 'rejected', admin_message = '$admin_message' WHERE id = $order_id";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Order rejected.";
    } else {
        $error = "Error rejecting order: " . mysqli_error($conn);
    }
}

// Fetch all subscription orders
$orders_query = "
    SELECT so.*, s.name as shop_name, s.location, s.subscription_expiry
    FROM subscription_orders so
    JOIN shops s ON so.shop_id = s.id
    ORDER BY 
        CASE so.status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'rejected' THEN 3 
        END,
        so.created_at DESC
";
$orders_result = mysqli_query($conn, $orders_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Orders - Superadmin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .orders-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .order-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }
        .order-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
        }
        .order-card.pending {
            border-left: 4px solid #f59e0b;
        }
        .order-card.approved {
            border-left: 4px solid #10b981;
        }
        .order-card.rejected {
            border-left: 4px solid #ef4444;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            background: rgba(0,0,0,0.2);
            padding: 10px;
            border-radius: 8px;
        }
        .info-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-main);
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        .status-approved {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        .status-rejected {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        .action-form {
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
        }
        .message-input {
            flex: 1;
            min-width: 200px;
        }
    </style>
</head>
<body>
    <div class="super-header" style="background: rgba(0, 0, 0, 0.2); padding: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div>
            <h2 style="margin: 0; color: white;">Subscription Orders</h2>
            <span style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Manage subscription renewals</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="superadmin_dashboard.php" class="btn btn-secondary" style="background: rgba(30, 41, 59, 0.7); border: none; transition: background-color 0.4s ease;">← Back to Dashboard</a>
            <a href="logout.php" class="btn btn-secondary" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--danger); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; padding: 0; transition: all 0.3s;">⏻</a>
        </div>
    </div>

    <div class="orders-container">
        <?php if (mysqli_num_rows($orders_result) > 0): ?>
            <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                <div class="order-card <?php echo $order['status']; ?>">
                    <div class="order-header">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: white;"><?php echo htmlspecialchars($order['shop_name']); ?></h3>
                            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">📍 <?php echo htmlspecialchars($order['location']); ?></p>
                        </div>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>

                    <div class="order-info">
                        <div class="info-item">
                            <div class="info-label">Duration</div>
                            <div class="info-value"><?php echo $order['months']; ?> Month<?php echo $order['months'] > 1 ? 's' : ''; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Amount Paid</div>
                            <div class="info-value"><?php echo formatPrice($order['amount_paid']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Payment Method</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['payment_method']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Order Date</div>
                            <div class="info-value"><?php echo date('d M Y', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Current Expiry</div>
                            <div class="info-value">
                                <?php 
                                if ($order['subscription_expiry']) {
                                    echo date('d M Y', strtotime($order['subscription_expiry']));
                                } else {
                                    echo 'Not set';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($order['admin_message']): ?>
                        <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; padding: 12px; margin-bottom: 15px;">
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Admin Message:</div>
                            <div style="color: #93c5fd; font-size: 0.9rem;"><?php echo htmlspecialchars($order['admin_message']); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($order['status'] == 'pending'): ?>
                        <div class="action-form">
                            <form method="POST" style="display: flex; gap: 10px; flex: 1; flex-wrap: wrap;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="shop_id" value="<?php echo $order['shop_id']; ?>">
                                <input type="hidden" name="months" value="<?php echo $order['months']; ?>">
                                <div class="message-input">
                                    <input type="text" name="admin_message" placeholder="Optional message to shop owner..." style="width: 100%; padding: 10px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--card-border); border-radius: 8px; color: white; font-size: 0.9rem;">
                                </div>
                                <button type="submit" name="approve_order" class="btn btn-primary" style="background: linear-gradient(135deg, #10b981, #059669); padding: 10px 20px;">✓ Approve</button>
                                <button type="submit" name="reject_order" class="btn btn-danger" style="padding: 10px 20px;">✗ Reject</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--text-muted);">
                <h3>No subscription orders yet</h3>
                <p>Orders will appear here when shop owners submit subscription renewal requests.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Modal !== 'undefined') {
                    Modal.alert('<?php echo addslashes($message); ?>', 'Success');
                } else {
                    alert('<?php echo addslashes($message); ?>');
                }
            });
        </script>
    <?php endif; ?>
    <?php if ($error): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Modal !== 'undefined') {
                    Modal.alert('<?php echo addslashes($error); ?>', 'Error');
                } else {
                    alert('<?php echo addslashes($error); ?>');
                }
            });
        </script>
    <?php endif; ?>

    <script src="js/modal.js"></script>
</body>
</html>
