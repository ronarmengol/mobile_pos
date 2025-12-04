<?php
require_once 'functions.php';
requireLogin();

header('Content-Type: application/json');

$sale_id = (int)$_GET['sale_id'];
$shop_id = $_SESSION['shop_id'];

// Verify the sale belongs to the user's shop
$sale_check = mysqli_query($conn, "SELECT * FROM sales WHERE id = $sale_id AND shop_id = $shop_id");

if (mysqli_num_rows($sale_check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Sale not found']);
    exit;
}

$sale_info = mysqli_fetch_assoc($sale_check);

// Fetch sale items with product names
$query = "SELECT si.*, p.name as product_name 
          FROM sale_items si 
          JOIN products p ON si.product_id = p.id 
          WHERE si.sale_id = $sale_id 
          ORDER BY p.name";

$result = mysqli_query($conn, $query);

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

echo json_encode([
    'success' => true,
    'items' => $items,
    'sale_info' => [
        'total' => $sale_info['total'],
        'payment_method' => $sale_info['payment_method'],
        'created_at' => $sale_info['created_at'],
        'shop_order_number' => $sale_info['shop_order_number']
    ]
]);
?>
