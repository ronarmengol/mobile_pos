<?php
require_once 'functions.php';
requireAdmin();

$shop_id = $_SESSION['shop_id'];

// Simple Reporting
$filter = $_GET['filter'] ?? 'today';
$where = "";

if ($filter == 'today') {
    $where = "AND DATE(created_at) = CURDATE()";
} elseif ($filter == 'week') {
    $where = "AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter == 'month') {
    $where = "AND MONTH(created_at) = MONTH(CURDATE())";
}

$sales = mysqli_query($conn, "SELECT * FROM sales WHERE shop_id = $shop_id $where ORDER BY created_at DESC");
$total_revenue = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Takeaway POS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <a href="dashboard.php" class="btn btn-secondary">← Back</a>
        <h2>Sales Reports</h2>
    </div>

    <div class="app-container">
        <div style="margin: 20px 0; text-align: center;">
            <div style="margin-bottom: 15px;">
                <a href="?filter=today" class="btn <?php echo $filter=='today'?'btn-primary':'btn-secondary'; ?>" style="padding: 8px 15px; font-size: 0.8rem;">Today</a>
                <a href="?filter=week" class="btn <?php echo $filter=='week'?'btn-primary':'btn-secondary'; ?>" style="padding: 8px 15px; font-size: 0.8rem;">This Week</a>
                <a href="?filter=month" class="btn <?php echo $filter=='month'?'btn-primary':'btn-secondary'; ?>" style="padding: 8px 15px; font-size: 0.8rem;">This Month</a>
            </div>
            
            <?php
            $date_display = '';
            if ($filter == 'today') {
                $date_display = date('d M y');
            } elseif ($filter == 'week') {
                // Assuming week starts on Monday
                $start = date('d M', strtotime('monday this week'));
                $end = date('d M', strtotime('sunday this week'));
                $date_display = "$start - $end";
            } elseif ($filter == 'month') {
                $date_display = date('M');
            }
            ?>
            <div class="current-date" style="display: inline-block; font-size: 0.9rem; padding: 4px 12px;">
                <?php echo $date_display; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php echo ($filter == 'today') ? 'Time' : 'Date'; ?></th>
                        <th>Method</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($sales) > 0):
                        while ($row = mysqli_fetch_assoc($sales)): 
                            $total_revenue += $row['total'];
                    ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td>
                            <?php 
                            if ($filter == 'today') {
                                echo date('H:i', strtotime($row['created_at'])); 
                            } else {
                                echo date('d M', strtotime($row['created_at']));
                            }
                            ?>
                        </td>
                        <td><?php echo $row['payment_method']; ?></td>
                        <td><?php echo formatPrice($row['total']); ?></td>
                    </tr>
                    <?php endwhile; 
                    else: ?>
                    <tr><td colspan="4" style="text-align: center;">No sales found.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold; border-top: 2px solid var(--card-border);">
                        <td colspan="3" style="text-align: right;">Total Revenue:</td>
                        <td><?php echo formatPrice($total_revenue); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</body>
</html>
