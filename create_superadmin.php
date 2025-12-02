<?php
require_once 'config.php';

// 1. Update users table to include 'superadmin' role
$sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'manager', 'superadmin') DEFAULT 'cashier'";
if (mysqli_query($conn, $sql)) {
    echo "Role enum updated successfully.\n";
} else {
    echo "Error updating role enum: " . mysqli_error($conn) . "\n";
}

// 2. Create a dedicated shop for Superadmin
$shop_id = 9999;
$shop_name = 'Superadmin HQ';
$shop_location = 'System';

$check_shop = mysqli_query($conn, "SELECT id FROM shops WHERE id = $shop_id");
if (mysqli_num_rows($check_shop) == 0) {
    $sql = "INSERT INTO shops (id, name, location) VALUES ($shop_id, '$shop_name', '$shop_location')";
    if (mysqli_query($conn, $sql)) {
        echo "Superadmin shop created.\n";
    } else {
        echo "Error creating shop: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Superadmin shop already exists.\n";
}

// 3. Insert Superadmin User
$username = 'superadmin';
$password = '123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'superadmin';

$check_user = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
if (mysqli_num_rows($check_user) == 0) {
    $sql = "INSERT INTO users (shop_id, username, password, role) VALUES ($shop_id, '$username', '$hashed_password', '$role')";
    if (mysqli_query($conn, $sql)) {
        echo "Superadmin user created successfully.\n";
    } else {
        echo "Error creating user: " . mysqli_error($conn) . "\n";
    }
} else {
    // Update password if user exists
    $sql = "UPDATE users SET password = '$hashed_password', role = '$role', shop_id = $shop_id WHERE username = '$username'";
    if (mysqli_query($conn, $sql)) {
        echo "Superadmin user updated.\n";
    } else {
        echo "Error updating user: " . mysqli_error($conn) . "\n";
    }
}
?>
