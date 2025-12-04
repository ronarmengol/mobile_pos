<?php
require_once 'functions.php';
requireSuperAdmin();

$message = '';
$error = '';

// Handle superadmin credential update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_superadmin'])) {
    $new_username = sanitize($conn, $_POST['username']);
    $new_password = $_POST['password'];
    
    $sql = "UPDATE users SET username = '$new_username', password = '$new_password' WHERE role = 'superadmin'";
    if (mysqli_query($conn, $sql)) {
        $message = "Superadmin credentials updated successfully.";
        $_SESSION['username'] = $new_username;
    } else {
        $error = "Error updating credentials: " . mysqli_error($conn);
    }
}

// Get current superadmin info
$superadmin_query = "SELECT * FROM users WHERE role = 'superadmin' LIMIT 1";
$superadmin_result = mysqli_query($conn, $superadmin_query);
$superadmin = mysqli_fetch_assoc($superadmin_result);


// Fetch all shops except the superadmin shop
$shops_query = "
    SELECT s.*, 
           COUNT(DISTINCT u.id) as user_count,
           COUNT(DISTINCT sa.id) as sale_count,
           COALESCE(SUM(sa.total), 0) as total_revenue
    FROM shops s
    LEFT JOIN users u ON s.id = u.shop_id
    LEFT JOIN sales sa ON s.id = sa.shop_id
    WHERE s.id != 9999
    GROUP BY s.id
    ORDER BY s.created_at DESC
";
$shops_result = mysqli_query($conn, $shops_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Dashboard - Takeaway POS</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .super-header {
            /* background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); */
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .shop-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .shop-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 20px;
            backdrop-filter: blur(10px);
            transition: transform 0.2s;
        }
        .shop-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
        }
        .shop-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .shop-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: white;
        }
        .shop-location {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .shop-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .stat-box {
            background: rgba(0,0,0,0.2);
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--primary);
        }
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        .super-btn {
            background: rgba(30, 41, 59, 0.7) !important;
            border: none !important;
            transition: background-color 0.4s ease !important;
        }
        .super-btn:hover {
            background: rgba(255, 255, 255, 0.15) !important;
        }
        .super-logout-btn {
            background: rgba(239, 68, 68, 0.2) !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
            color: var(--danger) !important;
            width: 40px !important;
            height: 40px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.3rem !important;
            padding: 0 !important;
            transition: all 0.3s !important;
        }
        .super-logout-btn:hover {
            background: rgba(239, 68, 68, 0.3) !important;
            transform: rotate(90deg) !important;
        }
    </style>
</head>
<body>
    <div class="super-header">
        <div>
            <h2 style="margin: 0; color: white;">Superadmin Dashboard</h2>
            <span style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Overseeing <?php echo mysqli_num_rows($shops_result); ?> Shops</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="openCredentialsModal()" class="btn btn-secondary super-btn">⚙️ Settings</button>
            <a href="logout.php" class="btn btn-secondary super-logout-btn">⏻</a>
        </div>
    </div>

    <div class="app-container">
        <div class="shop-grid">
            <?php if (mysqli_num_rows($shops_result) > 0): ?>
                <?php while ($shop = mysqli_fetch_assoc($shops_result)): ?>
                    <div class="shop-card">
                        <div class="shop-header">
                            <div>
                                <div class="shop-name"><?php echo htmlspecialchars($shop['name']); ?></div>
                                <div class="shop-location">📍 <?php echo htmlspecialchars($shop['location']); ?></div>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 5px;">
                                <span class="badge" style="<?php echo isset($shop['status']) && $shop['status'] == 'inactive' ? 'background: rgba(239, 68, 68, 0.2); color: #ef4444;' : ''; ?>">
                                    <?php echo isset($shop['status']) ? ucfirst($shop['status']) : 'Active'; ?>
                                </span>
                                <a href="superadmin_shop_details.php?id=<?php echo $shop['id']; ?>" class="btn btn-primary" style="padding: 4px 10px; font-size: 0.8rem;">Manage</a>
                            </div>
                        </div>
                        
                        <div class="shop-stats">
                            <div class="stat-box">
                                <div class="stat-value"><?php echo formatPrice($shop['total_revenue']); ?></div>
                                <div class="stat-label">Total Revenue</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-value"><?php echo $shop['sale_count']; ?></div>
                                <div class="stat-label">Total Sales</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-value"><?php echo $shop['user_count']; ?></div>
                                <div class="stat-label">Users</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-value"><?php echo date('d M Y', strtotime($shop['created_at'])); ?></div>
                                <div class="stat-label">Joined</div>
                            </div>
                        </div>
                        
                        <?php
                        // Calculate subscription info
                        if ($shop['subscription_expiry']) {
                            $expiry_timestamp = strtotime($shop['subscription_expiry']);
                            $days_left = ceil(($expiry_timestamp - time()) / 86400);
                            $expiry_date = date('d M Y', $expiry_timestamp);
                            
                            // Determine color based on days left
                            $color = '#10b981'; // Green
                            if ($days_left <= 0) {
                                $color = '#ef4444'; // Red - Expired
                            } elseif ($days_left <= 3) {
                                $color = '#ef4444'; // Red
                            } elseif ($days_left <= 7) {
                                $color = '#f59e0b'; // Orange
                            }
                        ?>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 2px;"><?php echo $days_left <= 0 ? 'Subscription Expired' : 'Subscription Expires'; ?></div>
                                    <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);"><?php echo $expiry_date; ?></div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 1.3rem; font-weight: bold; color: <?php echo $color; ?>;"><?php echo abs($days_left); ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);"><?php echo $days_left <= 0 ? 'days expired' : 'days left'; ?></div>
                                </div>
                            </div>
                        </div>
                        <?php } else { ?>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                            No subscription set
                        </div>
                        <?php } ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-muted);">
                    <h3>No shops found</h3>
                    <p>When customers register, they will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Superadmin Credentials Modal -->
    <div id="credentialsModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 400px;">
            <h3>Update Superadmin Credentials</h3>
            <form method="POST">
                <input type="hidden" name="update_superadmin" value="1">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: var(--text-muted);">Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($superadmin['username']); ?>" required style="width: 100%; padding: 10px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--card-border); border-radius: 6px; color: white;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: var(--text-muted);">Password</label>
                    <input type="text" name="password" value="<?php echo htmlspecialchars($superadmin['password']); ?>" required style="width: 100%; padding: 10px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--card-border); border-radius: 6px; color: white;">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeCredentialsModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
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
    <script>
        function openCredentialsModal() {
            document.getElementById('credentialsModal').classList.add('active');
        }

        function closeCredentialsModal() {
            document.getElementById('credentialsModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('credentialsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCredentialsModal();
            }
        });
    </script>
</body>
</html>
