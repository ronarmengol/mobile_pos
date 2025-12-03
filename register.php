<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_name = mysqli_real_escape_string($conn, trim($_POST['shop_name']));
    $location = mysqli_real_escape_string($conn, trim($_POST['location']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($shop_name) || empty($location) || empty($username) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if username already exists
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username already exists. Please choose another.";
        } else {
            // Create shop
            $sql = "INSERT INTO shops (name, location) VALUES ('$shop_name', '$location')";
            if (mysqli_query($conn, $sql)) {
                $shop_id = mysqli_insert_id($conn);
                
                // Create admin user
                $hashed_password = $password;
                $sql = "INSERT INTO users (shop_id, username, password, role) VALUES ($shop_id, '$username', '$hashed_password', 'admin')";
                
                if (mysqli_query($conn, $sql)) {
                    $success = "Registration successful! You can now login.";
                    // Redirect after 2 seconds
                    header("refresh:2;url=index.php");
                } else {
                    $error = "Error creating user: " . mysqli_error($conn);
                }
            } else {
                $error = "Error creating shop: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Takeaway POS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <h2 style="text-align: center; margin-bottom: 10px; font-size: 1.8rem;">Create Your Shop</h2>
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 30px; font-size: 0.9rem;">
                Register your business to start using our POS system
            </p>

            <?php if ($error): ?>
                <div class="alert error">⚠️ <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success">✅ <?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Shop Name</label>
                    <input type="text" name="shop_name" placeholder="e.g., Joe's Takeaway" required 
                           value="<?php echo isset($_POST['shop_name']) ? htmlspecialchars($_POST['shop_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" placeholder="e.g., Downtown, Main Street" required
                           value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Admin Username</label>
                    <input type="text" name="username" placeholder="Choose a username" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="At least 6 characters" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Re-enter password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 20px;">
                    Register Shop
                </button>
            </form>

            <p style="text-align: center; margin-top: 20px; color: var(--text-muted); font-size: 0.9rem;">
                Already have an account? 
                <a href="index.php" style="color: var(--primary); font-weight: 600;">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>
