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
    } elseif (isset($_POST['set_subscription_date'])) {
        $expiry_date = $_POST['expiry_date'];
        
        // Validate date format (DD/MM/YYYY)
        $date_obj = DateTime::createFromFormat('d/m/Y', $expiry_date);
        
        if ($date_obj && $date_obj->format('d/m/Y') === $expiry_date) {
            $new_expiry = $date_obj->format('Y-m-d') . ' 23:59:59';
            
            // Check if date is in the past
            if ($new_expiry < date('Y-m-d H:i:s')) {
                $error = "Expiry date cannot be in the past.";
            } else {
                $sql = "UPDATE shops SET subscription_expiry = '$new_expiry', status = 'active' WHERE id = $shop_id";
                if (mysqli_query($conn, $sql)) {
                    $message = "Subscription expiry date set to: " . $date_obj->format('d M Y');
                } else {
                    $error = "Error setting subscription date: " . mysqli_error($conn);
                }
            }
        } else {
            $error = "Invalid date format. Please use DD/MM/YYYY.";
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
    } elseif (isset($_POST['delete_shop'])) {
        // Verify superadmin password before deletion
        $entered_password = $_POST['superadmin_password'];
        $superadmin_query = "SELECT password FROM users WHERE role = 'superadmin' LIMIT 1";
        $superadmin_result = mysqli_query($conn, $superadmin_query);
        $superadmin = mysqli_fetch_assoc($superadmin_result);
        
        if ($entered_password !== $superadmin['password']) {
            $error = "Incorrect superadmin password. Shop deletion cancelled.";
        } else {
            // Delete shop and all related data
            mysqli_begin_transaction($conn);
            
            try {
                // Delete sale items first (foreign key constraint)
                mysqli_query($conn, "DELETE si FROM sale_items si JOIN sales s ON si.sale_id = s.id WHERE s.shop_id = $shop_id");
                
                // Delete sales
                mysqli_query($conn, "DELETE FROM sales WHERE shop_id = $shop_id");
                
                // Delete products
                mysqli_query($conn, "DELETE FROM products WHERE shop_id = $shop_id");
                
                // Delete users
                mysqli_query($conn, "DELETE FROM users WHERE shop_id = $shop_id");
                
                // Delete shop
                mysqli_query($conn, "DELETE FROM shops WHERE id = $shop_id");
                
                mysqli_commit($conn);
                
                // Redirect to dashboard after successful deletion
                header("Location: superadmin_dashboard.php?deleted=1");
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Error deleting shop: " . $e->getMessage();
            }
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
        .form-group select option {
            background: white;
            color: #000;
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
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Modal.alert('<?php echo addslashes($message); ?>', 'Success');
                });
            </script>
        <?php endif; ?>
        <?php if ($error): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Modal.alert('<?php echo addslashes($error); ?>', 'Error');
                });
            </script>
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
                <form method="POST" id="deactivateForm">
                    <input type="hidden" name="deactivate_shop" value="1">
                    <button type="button" onclick="confirmDeactivate()" class="btn btn-danger" style="width: 100%;">Deactivate Shop</button>
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
                        <th style="padding: 10px;">Password</th>
                        <th style="padding: 10px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 10px;"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td style="padding: 10px;"><?php echo ucfirst($user['role']); ?></td>
                        <td style="padding: 10px; font-family: monospace; color: var(--primary);"><?php echo htmlspecialchars($user['password']); ?></td>
                        <td style="padding: 10px;">
                            <button onclick="openPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">Change Password</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Manual Subscription Date Setter -->
        <div class="card" style="margin-top: 30px;">
            <h3>📅 Set Subscription Expiry Date</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                Manually set a specific expiration date for this shop's subscription. This will override the current expiry date and activate the account.
            </p>
            <form method="POST">
                <input type="hidden" name="set_subscription_date" value="1">
                <div style="display: flex; gap: 15px; align-items: end;">
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label>Expiration Date (DD/MM/YYYY)</label>
                        <input type="text" name="expiry_date" required 
                               placeholder="DD/MM/YYYY"
                               pattern="\d{2}/\d{2}/\d{4}"
                               maxlength="10"
                               oninput="let v=this.value.replace(/\D/g,''); if(v.length>8) v=v.substring(0,8); if(v.length>=5) this.value=v.substring(0,2)+'/'+v.substring(2,4)+'/'+v.substring(4,8); else if(v.length>=3) this.value=v.substring(0,2)+'/'+v.substring(2); else this.value=v;"
                               value="<?php echo $shop['subscription_expiry'] ? date('d/m/Y', strtotime($shop['subscription_expiry'])) : date('d/m/Y', strtotime('+30 days')); ?>"
                               style="width: 100%; padding: 10px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--card-border); border-radius: 6px; color: white;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">
                        Set Expiry Date
                    </button>
                </div>
                <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 10px;">
                    💡 Current expiry: <strong><?php echo $shop['subscription_expiry'] ? date('d M Y', strtotime($shop['subscription_expiry'])) : 'Not set'; ?></strong>
                </div>
            </form>
        </div>
        
        <!-- Danger Zone -->
        <div class="card" style="margin-top: 30px; border-color: #ef4444;">
            <h3 style="color: #ef4444; margin-bottom: 15px;">⚠️ Danger Zone</h3>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 20px;">
                <div style="margin-bottom: 15px;">
                    <h4 style="color: #fca5a5; margin: 0 0 8px 0; font-size: 1rem;">Delete This Shop</h4>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0 0 15px 0;">
                        Permanently delete this shop and all associated data. This action cannot be undone.
                    </p>
                    <p style="color: #fecaca; font-size: 0.85rem; margin: 0 0 15px 0;">
                        <strong>The following data will be permanently deleted:</strong><br>
                        • All users and their credentials<br>
                        • All products and inventory<br>
                        • All sales records and transactions<br>
                        • All historical data
                    </p>
                </div>
                <button onclick="confirmShopDeletion()" class="btn btn-danger" style="width: 100%;">
                    🗑️ Delete Shop Permanently
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Shop Confirmation Modal -->
    <div id="deleteShopModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <h3 style="color: #ef4444;">⚠️ Confirm Shop Deletion</h3>
            <div style="margin: 20px 0;">
                <p style="color: var(--text-main); margin-bottom: 15px;">
                    You are about to <strong style="color: #ef4444;">permanently delete</strong> the shop:
                </p>
                <div style="background: rgba(239, 68, 68, 0.1); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <div style="font-size: 1.2rem; font-weight: bold; color: var(--text-main);"><?php echo htmlspecialchars($shop['name']); ?></div>
                    <div style="color: var(--text-muted); font-size: 0.9rem;">📍 <?php echo htmlspecialchars($shop['location']); ?></div>
                </div>
                <div style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444;">
                    <p style="color: #fca5a5; font-weight: 600; margin: 0 0 10px 0;">This will permanently delete:</p>
                    <ul style="color: #fecaca; margin: 0; padding-left: 20px; font-size: 0.9rem;">
                        <li>All shop users and login credentials</li>
                        <li>All products and inventory data</li>
                        <li>All sales records and transactions</li>
                        <li>All historical and financial data</li>
                    </ul>
                    <p style="color: #ff0000; font-weight: bold; margin: 15px 0 0 0; font-size: 0.95rem;">
                        ⚠️ THIS ACTION CANNOT BE UNDONE!
                    </p>
                </div>
            </div>
            <form method="POST" id="deleteShopForm" onsubmit="return validateDeletion()">
                <input type="hidden" name="delete_shop" value="1">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; color: #fca5a5; font-weight: 600;">Enter Superadmin Password to Confirm</label>
                    <input type="password" name="superadmin_password" id="superadminPassword" required 
                           placeholder="Your superadmin password" 
                           style="width: 100%; padding: 12px; background: rgba(0, 0, 0, 0.4); border: 2px solid #ef4444; border-radius: 6px; color: white; font-size: 1rem;">
                    <div style="color: #fecaca; font-size: 0.85rem; margin-top: 6px;">
                        🔒 Password verification required for security
                    </div>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeDeleteShopModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete Permanently</button>
                </div>
            </form>
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
        
        
        function confirmDeactivate() {
            Modal.confirm(
                'Are you sure you want to deactivate this shop? Users will not be able to login.',
                function() {
                    document.getElementById('deactivateForm').submit();
                },
                'Confirm Deactivation'
            );
        }
        
        
        // Close modal when clicking outside
        document.getElementById('passwordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePasswordModal();
            }
        });
        
        // Shop deletion functions
        function confirmShopDeletion() {
            document.getElementById('deleteShopModal').classList.add('active');
        }
        
        function closeDeleteShopModal() {
            document.getElementById('deleteShopModal').classList.remove('active');
        }
        
        // Close delete modal when clicking outside
        document.getElementById('deleteShopModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteShopModal();
            }
        });
        
        // Validate deletion form
        function validateDeletion() {
            const password = document.getElementById('superadminPassword').value;
            if (!password || password.trim() === '') {
                Modal.alert('Please enter your superadmin password to confirm deletion.', 'Password Required');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
