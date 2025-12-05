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
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['shop_id'] = $user['shop_id'];
                
                // Check if shop is inactive or subscription has expired
                if ($shop_data['status'] === 'inactive') {
                    $_SESSION['subscription_expired'] = true; // Treat inactive as expired for UI purposes
                } elseif ($shop_data['subscription_expiry'] && strtotime($shop_data['subscription_expiry']) < time()) {
                    $_SESSION['subscription_expired'] = true;
                } else {
                    $_SESSION['subscription_expired'] = false;
                }
                
                header("Location: dashboard.php");
                exit();
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
    <title>Login - Mobile POS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow: hidden;
        }



        @keyframes particle-float {
            0%, 100% { 
                transform: translate(0, 0) scale(1);
                opacity: 1;
            }
            50% { 
                transform: translate(-20px, -20px) scale(1.1);
                opacity: 0.8;
            }
        }



        .login-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            position: relative;
            overflow: hidden;
        }

        /* Animated background particles inside wrapper */
        .login-wrapper::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            box-shadow: 
                100px 200px 0 0 rgba(255, 255, 255, 0.8),
                -150px 300px 0 1px rgba(255, 255, 255, 0.6),
                250px -100px 0 0 rgba(255, 255, 255, 0.7),
                -300px -200px 0 1px rgba(255, 255, 255, 0.5),
                400px 150px 0 0 rgba(255, 255, 255, 0.9),
                -450px 50px 0 1px rgba(255, 255, 255, 0.6),
                200px -250px 0 0 rgba(255, 255, 255, 0.7),
                -100px -350px 0 1px rgba(255, 255, 255, 0.8),
                350px 300px 0 0 rgba(255, 255, 255, 0.5),
                -250px 150px 0 1px rgba(255, 255, 255, 0.7),
                150px -150px 0 0 rgba(255, 255, 255, 0.6),
                -350px 250px 0 1px rgba(255, 255, 255, 0.8),
                450px -50px 0 0 rgba(255, 255, 255, 0.5),
                -200px -100px 0 1px rgba(255, 255, 255, 0.9),
                300px 250px 0 0 rgba(255, 255, 255, 0.6),
                -400px -150px 0 1px rgba(255, 255, 255, 0.7),
                50px 350px 0 0 rgba(255, 255, 255, 0.8),
                -500px 100px 0 1px rgba(255, 255, 255, 0.5),
                500px 200px 0 0 rgba(255, 255, 255, 0.7),
                -50px 400px 0 1px rgba(255, 255, 255, 0.6);
            animation: particle-float 30s linear infinite;
            z-index: 0;
        }



        /* Mobile-first: Form section comes first */
        .form-section {
            padding: 30px 20px;
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 1;
            max-width: 400px;
            width: 100%;
            margin: auto;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-section h2 {
            color: white;
            font-size: 1.5rem;
            margin-bottom: 24px;
            font-weight: 600;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }

        .alert.warning {
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fcd34d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: rgba(99, 102, 241, 0.6);
            background: rgba(15, 23, 42, 0.8);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #10b981 0%, #ef4444 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 24px;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
        }

        .register-link {
            text-align: center;
            margin-top: 16px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .register-link a {
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #34d399;
        }

        /* Welcome section - hidden on mobile, shown only on desktop */
        .welcome-section {
            display: none;
            position: relative;
            z-index: 1;
        }

        /* Tablet and up - 640px+ */
        @media (min-width: 640px) {
            .form-section {
                padding: 40px 30px;
            }

        }

        /* Desktop - 968px+ */
        @media (min-width: 968px) {
            .login-wrapper {
                flex-direction: row;
                align-items: center;
                justify-content: center;
                padding: 40px;
            }

            .form-section {
                margin: 0;
                max-width: 450px;
            }



            .welcome-section {
                display: flex;
                flex: 1;
                padding: 60px 50px;
                color: white;
                flex-direction: column;
                justify-content: center;
                border-right: 1px solid rgba(255, 255, 255, 0.1);
                order: -1; /* Move to left side */
            }

            .welcome-section h1 {
                font-size: 3rem;
                font-weight: 700;
                margin-bottom: 20px;
                background: linear-gradient(135deg, #fff 0%, #e0e7ff 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .welcome-section .tagline {
                font-size: 1.25rem;
                color: rgba(255, 255, 255, 0.8);
                margin-bottom: 40px;
                font-weight: 400;
            }

            .features-list {
                list-style: none;
                margin-bottom: 40px;
            }

            .features-list li {
                padding: 10px 0;
                font-size: 1rem;
                color: rgba(255, 255, 255, 0.9);
                display: flex;
                align-items: center;
            }

            .features-list li::before {
                content: '•';
                color: #10b981;
                font-weight: bold;
                font-size: 1.5rem;
                margin-right: 12px;
            }

            .welcome-section .footer-text {
                font-size: 1.1rem;
                color: rgba(255, 255, 255, 0.7);
                font-style: italic;
            }

            .form-section {
                flex: 0 0 450px;
                padding: 60px 50px;
                border-left: 1px solid rgba(255, 255, 255, 0.1);
            }

            .form-section h2 {
                font-size: 1.75rem;
                margin-bottom: 30px;
            }

            .form-group {
                margin-bottom: 24px;
            }


        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="form-section">
            <h2>Mobile POS - Login</h2>
            
            <?php if ($error): ?>
                <div class="alert error">⚠️ <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['timeout'])): ?>
                <div class="alert warning">⏰ Session timed out. Please login again.</div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus placeholder="Enter username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter password">
                </div>
                <button type="submit" class="login-btn">
                    <span>Login to Dashboard</span>
                    <span>→</span>
                </button>
            </form>
            
            <p class="login-footer">
                Default Admin: admin / admin123
            </p>
            <p class="register-link">
                Don't have an account? 
                <a href="register.php">Register your shop</a>
            </p>
        </div>

        <div class="welcome-section">
            <h1>Welcome!</h1>
            <p class="tagline">Manage your business on the go.</p>
            
            <ul class="features-list">
                <li>Quick Orders</li>
                <li>Inventory Tracking</li>
                <li>Real-time Sales Data</li>
            </ul>
            
            <p class="footer-text">Your shop, everywhere</p>
        </div>
    </div>


</body>
</html>
