<?php
require_once 'functions.php';
requireSuperAdmin();

$shop_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($shop_id == 0 || $shop_id == 9999) {
    header("Location: superadmin_dashboard.php");
    exit();
}

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_shop'])) {
        $name = sanitize($conn, $_POST['name']);
        $location = sanitize($conn, $_POST['location']);
        $sql = "UPDATE shops SET name = '$name', location = '$location' WHERE id = $shop_id";
        if (mysqli_query($conn, $sql)) {
            $message = "Shop details updated.";
        } else {
            $error = "Error updating shop: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['add_subscription'])) {
        $months = (int)$_POST['months'];
        
        // Get current expiry
        $res = mysqli_query($conn, "SELECT subscription_expiry FROM shops WHERE id = $shop_id");
        $row = mysqli_fetch_assoc($res);
        $current_expiry = $row['subscription_expiry'];
        
        // If expired or null, start from now. Otherwise add to existing expiry.
        $start_date = ($current_expiry && strtotime($current_expiry) > time()) ? $current_expiry : date('Y-m-d H:i:s');
        $new_expiry = date('Y-m-d H:i:s', strtotime("$start_date +$months months"));
        
        $sql = "UPDATE shops SET subscription_expiry = '$new_expiry', status = 'active' WHERE id = $shop_id";
        if (mysqli_query($conn, $sql)) {
            $message = "Subscription extended by $months month(s). New expiry: " . date('d M Y', strtotime($new_expiry));
        } else {
            $error = "Error extending subscription: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['deactivate_shop'])) {
        $sql = "UPDATE shops SET status = 'inactive' WHERE id = $shop_id";
        if (mysqli_query($conn, $sql)) {
            $message = "Shop deactivated.";
        } else {
            $error = "Error deactivating shop: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['update_user_password'])) {
        $user_id = (int)$_POST['user_id'];
        $new_password = $_POST['new_password'];
        $hashed_password = $new_password;
        
        $sql = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id AND shop_id = $shop_id";
        if (mysqli_query($conn, $sql)) {
            $message = "User password updated.";
        } else {
            $error = "Error updating password: " . mysqli_error($conn);
        }
    }
}

// Fetch Shop Details
$shop_query = "SELECT * FROM shops WHERE id = $shop_id";
$shop_result = mysqli_query($conn, $shop_query);
$shop = mysqli_fetch_assoc($shop_result);

if (!$shop) {
    die("Shop not found.");
}

// Fetch Users
$users_query = "SELECT * FROM users WHERE shop_id = $shop_id";
$users_result = mysqli_query($conn, $users_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shop - Superadmin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .status-active { color: #10b981; }
        .status-inactive { color: #ef4444; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: var(--text-muted); }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            border-radius: 6px;
            color: white;
        }
        .btn-danger { background: #ef4444; color: white; border: none; }
        .btn-success { background: #10b981; color: white; border: none; }
    </style>
</head>
<body>
    <div class="header">
        <a href="superadmin_dashboard.php" class="btn btn-secondary">← Back</a>
        <h2>Manage Shop: <?php echo htmlspecialchars($shop['name']); ?></h2>
    </div>

    <div class="app-container">
        <?php if ($message): ?>
            <div class="alert success" style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ✅ <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ⚠️ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Shop Details -->
            <div class="card">
                <h3>Shop Details</h3>
                <form method="POST">
                    <input type="hidden" name="update_shop" value="1">
                    <div class="form-group">
                        <label>Shop Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($shop['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($shop['location']); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Details</button>
                </form>
            </div>

            <!-- Subscription Management -->
            <div class="card">
                <h3>Subscription Status</h3>
                <div style="margin-bottom: 20px;">
                    <div>Status: 
                        <strong class="<?php echo $shop['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo strtoupper($shop['status']); ?>
                        </strong>
                    </div>
                    <div>Expires: 
                        <strong>
                            <?php echo $shop['subscription_expiry'] ? date('d M Y', strtotime($shop['subscription_expiry'])) : 'Never'; ?>
                        </strong>
                        <?php
                        if ($shop['subscription_expiry']) {
                            $days_left = ceil((strtotime($shop['subscription_expiry']) - time()) / 86400);
                            if ($days_left > 0) {
                                echo " <span style='color: var(--text-muted); font-size: 0.9rem;'>($days_left days left)</span>";
                            } else {
                                echo " <span style='color: #ef4444; font-size: 0.9rem;'>(Expired)</span>";
                            }
                        }
                        ?>
                    </div>
                </div>

                <form method="POST" style="margin-bottom: 15px;">
                    <input type="hidden" name="add_subscription" value="1">
                    <div class="form-group">
                        <label>Extend Subscription</label>
                        <select name="months">
                            <option value="1">1 Month</option>
                            <option value="3">3 Months</option>
                            <option value="6">6 Months</option>
                            <option value="12">1 Year</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success" style="width: 100%;">Add Subscription & Activate</button>
                </form>

                <?php if ($shop['status'] == 'active'): ?>
                <form method="POST" onsubmit="return confirm('Are you sure you want to deactivate this shop? Users will not be able to login.');">
                    <input type="hidden" name="deactivate_shop" value="1">
                    <button type="submit" class="btn btn-danger" style="width: 100%;">Deactivate Shop</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Users Management -->
        <div class="card">
            <h3>Shop Users</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--card-border); text-align: left;">
                        <th style="padding: 10px;">Username</th>
                        <th style="padding: 10px;">Role</th>
                        <th style="padding: 10px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 10px;"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td style="padding: 10px;"><?php echo ucfirst($user['role']); ?></td>
                        <td style="padding: 10px;">
                            <button onclick="openPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">Change Password</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Password Modal -->
    <div id="passwordModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Change Password for <span id="modalUsername"></span></h3>
            <form method="POST">
                <input type="hidden" name="update_user_password" value="1">
                <input type="hidden" name="user_id" id="modalUserId">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="text" name="new_password" required placeholder="Enter new password">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closePasswordModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/modal.js"></script>
    <script>
        function openPasswordModal(userId, username) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalUsername').textContent = username;
            document.getElementById('passwordModal').classList.add('active');
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('passwordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePasswordModal();
            }
        });
    </script>
</body>
</html>
