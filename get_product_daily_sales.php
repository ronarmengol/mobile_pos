<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$shop_id = $_SESSION['shop_id'];
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$filter = $_GET['filter'] ?? 'week';
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

$start_date = '';
$end_date = '';

if ($filter == 'week') {
    if ($offset == 0) {
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
    } else {
        $start_date = date('Y-m-d', strtotime('monday this week ' . ($offset > 0 ? '+' : '') . $offset . ' weeks'));
        $end_date = date('Y-m-d', strtotime('sunday this week ' . ($offset > 0 ? '+' : '') . $offset . ' weeks'));
    }
} elseif ($filter == 'month') {
    if ($offset == 0) {
        $target_month = date('Y-m');
    } else {
        $target_month = date('Y-m', strtotime(($offset > 0 ? '+' : '') . $offset . ' months'));
    }
    $start_date = date('Y-m-01', strtotime($target_month));
    $end_date = date('Y-m-t', strtotime($target_month));
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid filter']);
    exit;
}

// Query to get daily sales for the product
$query = "
    SELECT DATE(s.created_at) as sale_date, SUM(si.quantity) as total_quantity
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    WHERE si.product_id = $product_id 
    AND s.shop_id = $shop_id
    AND DATE(s.created_at) BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(s.created_at)
    ORDER BY sale_date ASC
";

$result = mysqli_query($conn, $query);

$daily_sales = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $daily_sales[$row['sale_date']] = (int)$row['total_quantity'];
    }
}

// Only include dates with sales > 0
$final_data = [];
foreach ($daily_sales as $date => $quantity) {
    if ($quantity > 0) {
        $final_data[] = [
            'date' => date('d M Y', strtotime($date)),
            'quantity' => $quantity
        ];
    }
}

echo json_encode(['success' => true, 'data' => $final_data]);
