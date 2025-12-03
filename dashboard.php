<?php
require_once 'functions.php';
requireLogin();

$message = '';
$error = '';

// Handle user profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $new_username = sanitize($conn, $_POST['username']);
    $new_password = $_POST['password'];
    $user_id = $_SESSION['user_id'];
    $current_username = $_SESSION['username'];
    
    // Check if username is being changed and if it's unique
    if ($new_username !== $current_username) {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$new_username' AND id != $user_id");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username already exists. Please choose another.";
        }
    }
    
    if (empty($error)) {
        $sql = "UPDATE users SET username = '$new_username', password = '$new_password' WHERE id = $user_id";
        if (mysqli_query($conn, $sql)) {
            $message = "Profile updated successfully.";
            $_SESSION['username'] = $new_username;
        } else {
            $error = "Error updating profile: " . mysqli_error($conn);
        }
    }
}

// Get current user info
$user_query = "SELECT * FROM users WHERE id = " . $_SESSION['user_id'];
$user_result = mysqli_query($conn, $user_query);
$current_user = mysqli_fetch_assoc($user_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Takeaway POS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Desktop Header -->
    <div class="desktop-header">
        <div class="header-logo">
            <h2><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        </div>
        <nav class="desktop-nav">
            <a href="dashboard.php" class="active">Home</a>
            <?php if (isset($_SESSION['subscription_expired']) && $_SESSION['subscription_expired']): ?>
                <a href="#" style="opacity: 0.5; pointer-events: none; cursor: not-allowed;">New Sale</a>
                <?php if (isAdmin()): ?>
                <a href="#" style="opacity: 0.5; pointer-events: none; cursor: not-allowed;">Products</a>
                <a href="reports.php">Reports</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="sales.php">New Sale</a>
                <?php if (isAdmin()): ?>
                <a href="products.php">Products</a>
                <a href="reports.php">Reports</a>
                <?php else: ?>
                <a href="#">Stock</a>
                <a href="#">Profile</a>
                <?php endif; ?>
                <button onclick="openProfileModal()" class="btn btn-secondary" style="padding: 8px 16px;">⚙️ Settings</button>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout-desktop">Logout</a>
        </nav>
    </div>

    <!-- Top Header (minimal on mobile) -->
    <div class="mobile-header">
        <h2><?php echo htmlspecialchars($_SESSION['username']); ?>'s Shop</h2>
        <div style="display: flex; gap: 10px;">
            <?php if (!isset($_SESSION['subscription_expired']) || !$_SESSION['subscription_expired']): ?>
            <button onclick="openProfileModal()" class="btn-logout" style="background: rgba(255,255,255,0.1);">⚙️</button>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout">⏻</a>
        </div>
    </div>

    <div class="dashboard-container">
        <!-- Expired Account Warning -->
        <?php if (isset($_SESSION['subscription_expired']) && $_SESSION['subscription_expired']): ?>
        <?php
        // Get shop subscription info
        $shop_id = $_SESSION['shop_id'];
        $shop_query = "SELECT subscription_expiry, status FROM shops WHERE id = $shop_id";
        $shop_result = mysqli_query($conn, $shop_query);
        $shop_data = mysqli_fetch_assoc($shop_result);
        
        $days_expired = 0;
        $days_until_deletion = 90;
        
        if ($shop_data['subscription_expiry']) {
            $expiry_timestamp = strtotime($shop_data['subscription_expiry']);
            $days_expired = ceil((time() - $expiry_timestamp) / 86400);
            $days_until_deletion = max(0, 90 - $days_expired);
        }
        
        // Determine urgency color
        $urgency_color = '#fca5a5';
        if ($days_until_deletion <= 7) {
            $urgency_color = '#ff0000'; // Bright red
        } elseif ($days_until_deletion <= 30) {
            $urgency_color = '#ff6b6b'; // Orange-red
        }
        ?>
        <div style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2)); border: 2px solid #ef4444; border-radius: 12px; padding: 20px; margin-bottom: 20px; backdrop-filter: blur(12px);">
            <div style="display: flex; align-items: start; gap: 15px;">
                <div style="font-size: 2rem;">⚠️</div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 8px 0; color: #fca5a5; font-size: 1.1rem;">Account Access Limited</h3>
                    <p style="margin: 0 0 12px 0; color: #fecaca; font-size: 0.95rem; line-height: 1.5;">
                        Your subscription has expired or your account has been deactivated. You currently have <strong>read-only access</strong> to view reports and historical data only.
                    </p>
                    <?php if ($days_expired > 0): ?>
                    <div style="background: rgba(0, 0, 0, 0.3); border-left: 4px solid <?php echo $urgency_color; ?>; padding: 12px; margin-bottom: 12px; border-radius: 4px;">
                        <div style="color: #fef2f2; font-size: 0.9rem; margin-bottom: 6px;">
                            <strong>Account expired <?php echo $days_expired; ?> day<?php echo $days_expired != 1 ? 's' : ''; ?> ago</strong>
                        </div>
                        <div style="color: <?php echo $urgency_color; ?>; font-size: 0.95rem; font-weight: 600;">
                            ⏰ <strong><?php echo $days_until_deletion; ?> days remaining</strong> until permanent deletion
                        </div>
                        <div style="color: #fecaca; font-size: 0.85rem; margin-top: 6px;">
                            🗑️ After 90 days of expiration, your account and all corresponding data will be <strong>permanently and irrecoverably deleted</strong>.
                        </div>
                    </div>
                    <?php endif; ?>
                    <p style="margin: 0; color: #fef2f2; font-size: 0.9rem;">
                        📊 You can access <a href="reports.php" style="color: #fca5a5; font-weight: 600; text-decoration: underline;">Reports</a> to view your sales history.<br>
                        💬 Please contact support to renew your subscription and restore full access.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Quick Stats at Top -->
        <div class="stats-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="margin: 0;">Today's Overview</h3>
                <div class="current-date"><?php echo date('d M y'); ?></div>
            </div>
            <?php
            $shop_id = $_SESSION['shop_id'];
            $today = date('Y-m-d');
            $sql = "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as revenue FROM sales WHERE shop_id = $shop_id AND DATE(created_at) = '$today'";
            $result = mysqli_query($conn, $sql);
            $stats = mysqli_fetch_assoc($result);
            ?>
            <div class="stats-card">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $stats['count']; ?></div>
                    <div class="stat-label">Orders Today</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-value primary"><?php echo formatPrice($stats['revenue']); ?></div>
                    <div class="stat-label">Revenue Today</div>
                </div>
            </div>
            
            <?php
            // Get subscription info for non-superadmin users
            if ($_SESSION['role'] !== 'superadmin') {
                $shop_query = "SELECT subscription_expiry FROM shops WHERE id = $shop_id";
                $shop_result = mysqli_query($conn, $shop_query);
                $shop_info = mysqli_fetch_assoc($shop_result);
                
                if ($shop_info['subscription_expiry']) {
                    $expiry_timestamp = strtotime($shop_info['subscription_expiry']);
                    $days_left = ceil(($expiry_timestamp - time()) / 86400);
                    $expiry_date = date('d M Y', $expiry_timestamp);
                    
                    // Determine color based on days left
                    $color = '#10b981'; // Green
                    if ($days_left <= 3) {
                        $color = '#ef4444'; // Red
                    } elseif ($days_left <= 7) {
                        $color = '#f59e0b'; // Orange
                    }
            ?>
            <div style="background: rgba(30, 41, 59, 0.7); border: 1px solid var(--card-border); border-radius: 12px; padding: 15px; margin-top: 15px; backdrop-filter: blur(12px);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px;">Subscription Expires</div>
                        <div style="font-size: 1rem; font-weight: 600; color: var(--text-main);"><?php echo $expiry_date; ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: <?php echo $color; ?>;"><?php echo max(0, $days_left); ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">days left</div>
                    </div>
                </div>
                <?php if ($days_left <= 7 && $days_left > 0): ?>
                <div style="margin-top: 10px; padding: 8px; background: rgba(239, 68, 68, 0.1); border-radius: 6px; font-size: 0.85rem; color: #fca5a5;">
                    ⚠️ Your subscription is expiring soon. Please contact support to renew.
                </div>
                
                <!-- Expiration Reminder Popover -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const daysLeft = <?php echo $days_left; ?>;
                        const now = new Date().getTime();
                        const lastReminded = localStorage.getItem('subscription_reminder_timestamp');
                        const twoHours = 2 * 60 * 60 * 1000; // 2 hours in milliseconds
                        
                        // Show reminder if less than 8 days AND (never shown OR more than 2 hours ago)
                        if (daysLeft < 8) {
                            if (!lastReminded || (now - lastReminded) > twoHours) {
                                if (typeof Modal !== 'undefined') {
                                    Modal.alert(
                                        `⚠️ <strong>Subscription Expiring Soon!</strong><br><br>You have <strong>${daysLeft} days</strong> remaining on your subscription.<br>Please contact support to renew and avoid service interruption.`, 
                                        'Subscription Reminder'
                                    );
                                    localStorage.setItem('subscription_reminder_timestamp', now);
                                }
                            }
                        }
                    });
                </script>
                <?php endif; ?>
            </div>
            <?php
                }
            }
            ?>
        </div>

        <!-- Main Content Area (scrollable) -->
        <div class="content-area">
            <h3 style="margin-bottom: 16px; color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">Quick Actions</h3>
            
            <!-- Quick action cards for additional features -->
            <div class="quick-actions">
                <?php if (isAdmin()): ?>
                <a href="products.php" class="action-card">
                    <span class="action-icon">🍔</span>
                    <span class="action-title">Manage Products</span>
                </a>
                <a href="reports.php" class="action-card">
                    <span class="action-icon">📊</span>
                    <span class="action-title">View Reports</span>
                </a>
                <?php else: ?>
                <a href="#" class="action-card">
                    <span class="action-icon">📦</span>
                    <span class="action-title">My Stock</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation (Mobile First) -->
    <nav class="bottom-nav">
        <a href="dashboard.php" class="nav-item active">
            <span class="nav-icon">
                <svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22 22L2 22" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M2 11L10.1259 4.49931C11.2216 3.62279 12.7784 3.62279 13.8741 4.49931L22 11" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M15.5 5.5V3.5C15.5 3.22386 15.7239 3 16 3H18.5C18.7761 3 19 3.22386 19 3.5V8.5" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M4 22V9.5" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M20 22V9.5" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M15 22V17C15 15.5858 15 14.8787 14.5607 14.4393C14.1213 14 13.4142 14 12 14C10.5858 14 9.87868 14 9.43934 14.4393C9 14.8787 9 15.5858 9 17V22" stroke="#1C274C" stroke-width="1.5"/>
                    <path d="M14 9.5C14 10.6046 13.1046 11.5 12 11.5C10.8954 11.5 10 10.6046 10 9.5C10 8.39543 10.8954 7.5 12 7.5C13.1046 7.5 14 8.39543 14 9.5Z" stroke="#1C274C" stroke-width="1.5"/>
                </svg>
            </span>
            <span class="nav-label">Home</span>
        </a>
        <a href="sales.php" class="nav-item nav-item-primary">
            <span class="nav-icon">🛒</span>
            <span class="nav-label">New Sale</span>
        </a>
        <?php if (isAdmin()): ?>
        <a href="products.php" class="nav-item">
            <span class="nav-icon">
                <svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M16.5285 6C16.5098 5.9193 16.4904 5.83842 16.4701 5.75746C16.2061 4.70138 15.7904 3.55383 15.1125 2.65C14.4135 1.71802 13.3929 1 12 1C10.6071 1 9.58648 1.71802 8.88749 2.65C8.20962 3.55383 7.79387 4.70138 7.52985 5.75747C7.50961 5.83842 7.49016 5.9193 7.47145 6H5.8711C4.29171 6 2.98281 7.22455 2.87775 8.80044L2.14441 19.8004C2.02898 21.532 3.40238 23 5.13777 23H18.8622C20.5976 23 21.971 21.532 21.8556 19.8004L21.1222 8.80044C21.0172 7.22455 19.7083 6 18.1289 6H16.5285ZM8 11C8.57298 11 8.99806 10.5684 9.00001 9.99817C9.00016 9.97438 9.00044 9.9506 9.00084 9.92682C9.00172 9.87413 9.00351 9.79455 9.00718 9.69194C9.01451 9.48652 9.0293 9.18999 9.05905 8.83304C9.08015 8.57976 9.10858 8.29862 9.14674 8H14.8533C14.8914 8.29862 14.9198 8.57976 14.941 8.83305C14.9707 9.18999 14.9855 9.48652 14.9928 9.69194C14.9965 9.79455 14.9983 9.87413 14.9992 9.92682C14.9996 9.95134 14.9999 9.97587 15 10.0004C15 10.0004 15 11 16 11C17 11 17 9.99866 17 9.99866C16.9999 9.9636 16.9995 9.92854 16.9989 9.89349C16.9978 9.829 16.9957 9.7367 16.9915 9.62056C16.9833 9.38848 16.9668 9.06001 16.934 8.66695C16.917 8.46202 16.8953 8.23812 16.8679 8H18.1289C18.6554 8 19.0917 8.40818 19.1267 8.93348L19.86 19.9335C19.8985 20.5107 19.4407 21 18.8622 21H5.13777C4.55931 21 4.10151 20.5107 4.13998 19.9335L4.87332 8.93348C4.90834 8.40818 5.34464 8 5.8711 8H7.13208C7.10465 8.23812 7.08303 8.46202 7.06595 8.66696C7.0332 9.06001 7.01674 9.38848 7.00845 9.62056C7.0043 9.7367 7.00219 9.829 7.00112 9.89349C7.00054 9.92785 7.00011 9.96221 7 9.99658C6.99924 10.5672 7.42833 11 8 11ZM9.53352 6H14.4665C14.2353 5.15322 13.921 4.39466 13.5125 3.85C13.0865 3.28198 12.6071 3 12 3C11.3929 3 10.9135 3.28198 10.4875 3.85C10.079 4.39466 9.76472 5.15322 9.53352 6Z" fill=""/>
                </svg>
            </span>
            <span class="nav-label">Products</span>
        </a>
        <a href="reports.php" class="nav-item">
            <span class="nav-icon">
                <svg fill="#000000" height="800px" width="800px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
	 viewBox="0 0 492.308 492.308" xml:space="preserve">
<g>
	<g>
		<path d="M355.813,0H32.264v492.308h427.779V104.231L355.813,0z M361.582,33.615l64.846,64.846h-64.846V33.615z M440.351,472.615
			H51.957V19.692h289.933v98.462h98.462V472.615z"/>
	</g>
</g>
<g>
	<g>
		<path d="M315.543,331.885v109.947h92.308V331.885H315.543z M388.159,422.139h-52.923v-70.563h52.923V422.139z"/>
	</g>
</g>
<g>
	<g>
		<path d="M199.995,272.808v169.024h92.308V272.808H199.995z M272.611,422.139h-52.923V292.5h52.923V422.139z"/>
	</g>
</g>
<g>
	<g>
		<path d="M84.447,213.731v228.101h92.308V213.731H84.447z M157.062,422.139h-52.923V233.423h52.923V422.139z"/>
	</g>
</g>
</svg>
            </span>
            <span class="nav-label">Reports</span>
        </a>
        <?php else: ?>
        <a href="#" class="nav-item">
            <span class="nav-icon">📦</span>
            <span class="nav-label">Stock</span>
        </a>
        <a href="#" class="nav-item">
            <span class="nav-icon">👤</span>
            <span class="nav-label">Profile</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- Profile Settings Modal -->
    <div id="profileModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 400px;">
            <h3>Profile Settings</h3>
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: var(--text-muted);">Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($current_user['username']); ?>" required style="width: 100%; padding: 10px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--card-border); border-radius: 6px; color: white;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; color: var(--text-muted);">Password</label>
                    <input type="text" name="password" value="<?php echo htmlspecialchars($current_user['password']); ?>" required style="width: 100%; padding: 10px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--card-border); border-radius: 6px; color: white;">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeProfileModal()" class="btn btn-secondary">Cancel</button>
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
        function openProfileModal() {
            document.getElementById('profileModal').classList.add('active');
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('profileModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeProfileModal();
            }
        });
    </script>
</body>
</html>
