<?php
require_once 'functions.php';
requireAdmin();

$shop_id = $_SESSION['shop_id'];

// Simple Reporting
$filter = $_GET['filter'] ?? 'today';
$week_offset = isset($_GET['week_offset']) ? (int)$_GET['week_offset'] : 0;
$month_offset = isset($_GET['month_offset']) ? (int)$_GET['month_offset'] : 0;
$where = "";

if ($filter == 'today') {
    $where = "AND DATE(created_at) = CURDATE()";
} elseif ($filter == 'week') {
    // Calculate the Monday of the target week based on offset
    if ($week_offset == 0) {
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));
    } else {
        $monday = date('Y-m-d', strtotime('monday this week ' . ($week_offset > 0 ? '+' : '') . $week_offset . ' weeks'));
        $sunday = date('Y-m-d', strtotime('sunday this week ' . ($week_offset > 0 ? '+' : '') . $week_offset . ' weeks'));
    }
    $where = "AND DATE(created_at) BETWEEN '$monday' AND '$sunday'";
} elseif ($filter == 'month') {
    // Calculate the target month based on offset
    if ($month_offset == 0) {
        $target_month = date('Y-m');
    } else {
        $target_month = date('Y-m', strtotime(($month_offset > 0 ? '+' : '') . $month_offset . ' months'));
    }
    $where = "AND DATE_FORMAT(created_at, '%Y-%m') = '$target_month'";
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
                <a href="?filter=week&week_offset=0" class="btn <?php echo $filter=='week'?'btn-primary':'btn-secondary'; ?>" style="padding: 8px 15px; font-size: 0.8rem;">Weekly</a>
                <a href="?filter=month&month_offset=0" class="btn <?php echo $filter=='month'?'btn-primary':'btn-secondary'; ?>" style="padding: 8px 15px; font-size: 0.8rem;">Monthly</a>
            </div>
            
            <?php
            $date_display = '';
            if ($filter == 'today') {
                $date_display = date('d M y');
            } elseif ($filter == 'week') {
                // Display the week range with navigation arrows
                if ($week_offset == 0) {
                    $start = date('d M', strtotime('monday this week'));
                    $end = date('d M', strtotime('sunday this week'));
                } else {
                    $start = date('d M', strtotime('monday this week ' . ($week_offset > 0 ? '+' : '') . $week_offset . ' weeks'));
                    $end = date('d M', strtotime('sunday this week ' . ($week_offset > 0 ? '+' : '') . $week_offset . ' weeks'));
                }
                $prev_offset = $week_offset - 1;
                $next_offset = $week_offset + 1;
                ?>
                <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 10px;">
                    <a href="?filter=week&week_offset=<?php echo $prev_offset; ?>" class="btn btn-secondary" style="padding: 8px 12px; font-size: 1rem; line-height: 1;" title="Previous week">←</a>
                    <div class="current-date" style="display: inline-block; font-size: 0.9rem; padding: 4px 12px; min-width: 120px; text-align: center;">
                        <?php echo "$start - $end"; ?>
                    </div>
                    <a href="?filter=week&week_offset=<?php echo $next_offset; ?>" class="btn btn-secondary" style="padding: 8px 12px; font-size: 1rem; line-height: 1;" title="Next week">→</a>
                </div>
                <?php
            } elseif ($filter == 'month') {
                // Display the month with navigation arrows
                if ($month_offset == 0) {
                    $month_display = date('F Y');
                } else {
                    $month_display = date('F Y', strtotime(($month_offset > 0 ? '+' : '') . $month_offset . ' months'));
                }
                $prev_offset = $month_offset - 1;
                $next_offset = $month_offset + 1;
                ?>
                <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 10px;">
                    <a href="?filter=month&month_offset=<?php echo $prev_offset; ?>" class="btn btn-secondary" style="padding: 8px 12px; font-size: 1rem; line-height: 1;" title="Previous month">←</a>
                    <div class="current-date" style="display: inline-block; font-size: 0.9rem; padding: 4px 12px; min-width: 120px; text-align: center;">
                        <?php echo $month_display; ?>
                    </div>
                    <a href="?filter=month&month_offset=<?php echo $next_offset; ?>" class="btn btn-secondary" style="padding: 8px 12px; font-size: 1rem; line-height: 1;" title="Next month">→</a>
                </div>
                <?php
            }
            
            // Display date for non-week/month filters
            if ($filter == 'today' && $date_display):
            ?>
            <div class="current-date" style="display: inline-block; font-size: 0.9rem; padding: 4px 12px;">
                <?php echo $date_display; ?>
            </div>
            <?php endif; ?>
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
                        <td><a href="#" onclick="viewSaleDetails(<?php echo $row['id']; ?>); return false;" style="color: var(--primary); cursor: pointer; text-decoration: underline;">#<?php echo $row['id']; ?></a></td>
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

    <!-- Sale Details Modal -->
    <div id="saleDetailsModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px; text-align: left;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 class="modal-title" id="saleDetailsTitle">Sale #<span id="saleIdDisplay"></span></h3>
                <button onclick="closeSaleDetails()" class="btn-close" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">✕</button>
            </div>
            <div id="saleDetailsContent" style="max-height: 400px; overflow-y: auto;">
                <div style="text-align: center; padding: 20px; color: var(--text-muted);">Loading...</div>
            </div>
            <div id="saleDetailsFooter" style="margin-top: 20px; padding-top: 15px; border-top: 2px solid var(--card-border);">
                <!-- Total will be displayed here -->
            </div>
        </div>
    </div>

    <script src="js/modal.js"></script>
    <script>
        function viewSaleDetails(saleId) {
            document.getElementById('saleIdDisplay').textContent = saleId;
            document.getElementById('saleDetailsModal').classList.add('active');
            document.getElementById('saleDetailsContent').innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text-muted);">Loading...</div>';
            
            // Fetch sale items via AJAX
            fetch('get_sale_items.php?sale_id=' + saleId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySaleItems(data.items, data.sale_info);
                    } else {
                        document.getElementById('saleDetailsContent').innerHTML = '<div style="text-align: center; padding: 20px; color: var(--danger);">Error loading sale details.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('saleDetailsContent').innerHTML = '<div style="text-align: center; padding: 20px; color: var(--danger);">Error loading sale details.</div>';
                });
        }

        function displaySaleItems(items, saleInfo) {
            let html = '<table style="width: 100%; border-collapse: collapse;">';
            html += '<thead><tr style="border-bottom: 1px solid var(--card-border);"><th style="text-align: left; padding: 8px;">Item</th><th style="text-align: center; padding: 8px;">Qty</th><th style="text-align: right; padding: 8px;">Price</th><th style="text-align: right; padding: 8px;">Total</th></tr></thead>';
            html += '<tbody>';
            
            items.forEach(item => {
                const itemTotal = parseFloat(item.price) * parseInt(item.quantity);
                html += `<tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 8px;">${item.product_name}</td>
                    <td style="text-align: center; padding: 8px;">${item.quantity}</td>
                    <td style="text-align: right; padding: 8px;">K${parseFloat(item.price).toFixed(2)}</td>
                    <td style="text-align: right; padding: 8px;">K${itemTotal.toFixed(2)}</td>
                </tr>`;
            });
            
            html += '</tbody></table>';
            
            document.getElementById('saleDetailsContent').innerHTML = html;
            
            // Display footer with sale info
            let footerHtml = '<div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 10px;">';
            footerHtml += `<span>Payment Method:</span><span style="font-weight: 600;">${saleInfo.payment_method}</span>`;
            footerHtml += '</div>';
            footerHtml += '<div style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: bold;">';
            footerHtml += `<span>Total:</span><span style="color: var(--primary);">K${parseFloat(saleInfo.total).toFixed(2)}</span>`;
            footerHtml += '</div>';
            
            document.getElementById('saleDetailsFooter').innerHTML = footerHtml;
        }

        function closeSaleDetails() {
            document.getElementById('saleDetailsModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('saleDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSaleDetails();
            }
        });
    </script>
</body>
</html>
