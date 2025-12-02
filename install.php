<?php
define('SKIP_DB_CONNECT', true);
require_once 'config.php';

// Connect without DB selected first to create it
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create Database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully or already exists.<br>";
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Select Database
mysqli_select_db($conn, DB_NAME);

// Create Tables

// 1. Shops
$sql = "CREATE TABLE IF NOT EXISTS shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (mysqli_query($conn, $sql)) echo "Table 'shops' created.<br>";
else echo "Error creating 'shops': " . mysqli_error($conn) . "<br>";

// 2. Users
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cashier', 'manager') DEFAULT 'cashier',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
)";
if (mysqli_query($conn, $sql)) echo "Table 'users' created.<br>";
else echo "Error creating 'users': " . mysqli_error($conn) . "<br>";

// 3. Products
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
)";
if (mysqli_query($conn, $sql)) echo "Table 'products' created.<br>";
else echo "Error creating 'products': " . mysqli_error($conn) . "<br>";

// 4. Sales
$sql = "CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    user_id INT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
if (mysqli_query($conn, $sql)) echo "Table 'sales' created.<br>";
else echo "Error creating 'sales': " . mysqli_error($conn) . "<br>";

// 5. Sale Items
$sql = "CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
)";
if (mysqli_query($conn, $sql)) echo "Table 'sale_items' created.<br>";
else echo "Error creating 'sale_items': " . mysqli_error($conn) . "<br>";

// Insert Default Data if empty
$check_shops = mysqli_query($conn, "SELECT * FROM shops");
if (mysqli_num_rows($check_shops) == 0) {
    // Create default shop
    mysqli_query($conn, "INSERT INTO shops (name, location) VALUES ('My Takeaway', 'Downtown')");
    $shop_id = mysqli_insert_id($conn);

    // Create default admin user (password: admin123)
    // Note: User asked for password protected login. I will use password_hash for security even if they said "simplified".
    // Wait, user said "Password-protected login".
    // I will use plain text if requested or simple hash. The prompt said "password-protected login".
    // I'll use password_hash().
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users (shop_id, username, password, role) VALUES ($shop_id, 'admin', '$password', 'admin')");
    
    // Create default cashier
    $cashier_pass = password_hash('1234', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users (shop_id, username, password, role) VALUES ($shop_id, 'cashier', '$cashier_pass', 'cashier')");

    // Create some products
    mysqli_query($conn, "INSERT INTO products (shop_id, name, category, price, stock) VALUES 
        ($shop_id, 'Burger', 'Main', 5.00, 100),
        ($shop_id, 'Fries', 'Snacks', 2.50, 200),
        ($shop_id, 'Coke', 'Drinks', 1.50, 150),
        ($shop_id, 'Water', 'Drinks', 1.00, 300)
    ");

    echo "Default data inserted.<br>";
    echo "Admin Login: admin / admin123<br>";
    echo "Cashier Login: cashier / 1234<br>";
}

echo "Installation Complete. <a href='index.php'>Go to Login</a>";
?>
