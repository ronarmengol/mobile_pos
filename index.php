<?php
require_once 'config.php';
require_once 'functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($conn, $_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if ($password === $user['password']) {
            // Check Shop Status & Subscription (skip for superadmin)
            if ($user['role'] !== 'superadmin') {
                $shop_id = $user['shop_id'];
                $shop_check = mysqli_query($conn, "SELECT status, subscription_expiry FROM shops WHERE id = $shop_id");
                $shop_data = mysqli_fetch_assoc($shop_check);
                
                if ($shop_data['status'] === 'inactive') {
                    $error = "Your shop account has been deactivated. Please contact support.";
                } elseif ($shop_data['subscription_expiry'] && strtotime($shop_data['subscription_expiry']) < time()) {
                    // Automatically deactivate expired accounts
                    mysqli_query($conn, "UPDATE shops SET status = 'inactive' WHERE id = $shop_id");
                    $error = "Your subscription has expired on " . date('d M Y', strtotime($shop_data['subscription_expiry'])) . ". Please renew to continue.";
                }
                
                if (!empty($error)) {
                    // Don't set session if error
                    session_unset();
                    session_destroy();
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['shop_id'] = $user['shop_id'];
                    
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['shop_id'] = $user['shop_id'];
                
                header("Location: superadmin_dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Takeaway POS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h1>Takeaway POS</h1>
        <?php if ($error): ?>
            <div class="alert error">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['timeout'])): ?>
            <div class="alert warning">⏰ Session timed out. Please login again.</div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Username</label>
                <input type="text" name="username" required autofocus placeholder="Enter username">
            </div>
            <div class="form-group" style="margin-bottom: 30px;">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <span>Login to Dashboard</span>
                <span>→</span>
            </button>
        </form>
        <p class="login-footer" style="margin-top: 24px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
            Default Admin: admin / admin123
        </p>
        <p style="text-align: center; margin-top: 16px; color: var(--text-muted); font-size: 0.9rem;">
            Don't have an account? 
            <a href="register.php" style="color: var(--primary); font-weight: 600;">Register your shop</a>
        </p>
    </div>
</body>
</html>
