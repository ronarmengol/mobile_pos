<?php
require_once 'functions.php';
requireSuperAdmin();

// Fetch all shops except the superadmin shop
$shops_query = "
    SELECT s.*, 
           COUNT(DISTINCT u.id) as user_count,
           COUNT(DISTINCT sa.id) as sale_count,
           COALESCE(SUM(sa.total), 0) as total_revenue
    FROM shops s
    LEFT JOIN users u ON s.id = u.shop_id
    LEFT JOIN sales sa ON s.id = sa.shop_id
    WHERE s.id != 9999
    GROUP BY s.id
    ORDER BY s.created_at DESC
";
$shops_result = mysqli_query($conn, $shops_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin Dashboard - Takeaway POS</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .super-header {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .shop-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .shop-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 20px;
            backdrop-filter: blur(10px);
            transition: transform 0.2s;
        }
        .shop-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
        }
        .shop-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .shop-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: white;
        }
        .shop-location {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .shop-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .stat-box {
            background: rgba(0,0,0,0.2);
            padding: 10px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--primary);
        }
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class="super-header">
        <div>
            <h2 style="margin: 0; color: white;">Superadmin Dashboard</h2>
            <span style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">Overseeing <?php echo mysqli_num_rows($shops_result); ?> Shops</span>
        </div>
        <a href="logout.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.2); border: none;">Logout</a>
    </div>

    <div class="app-container">
        <div class="shop-grid">
            <?php if (mysqli_num_rows($shops_result) > 0): ?>
                <?php while ($shop = mysqli_fetch_assoc($shops_result)): ?>
                    <div class="shop-card">
                        <div class="shop-header">
                            <div>
                                <div class="shop-name"><?php echo htmlspecialchars($shop['name']); ?></div>
                                <div class="shop-location">📍 <?php echo htmlspecialchars($shop['location']); ?></div>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 5px;">
                                <span class="badge" style="<?php echo isset($shop['status']) && $shop['status'] == 'inactive' ? 'background: rgba(239, 68, 68, 0.2); color: #ef4444;' : ''; ?>">
                                    <?php echo isset($shop['status']) ? ucfirst($shop['status']) : 'Active'; ?>
                                </span>
                                <a href="superadmin_shop_details.php?id=<?php echo $shop['id']; ?>" class="btn btn-primary" style="padding: 4px 10px; font-size: 0.8rem;">Manage</a>
                            </div>
                        </div>
                        
                        <div class="shop-stats">
                            <div class="stat-box">
                                <div class="stat-value"><?php echo formatPrice($shop['total_revenue']); ?></div>
                                <div class="stat-label">Total Revenue</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-value"><?php echo $shop['sale_count']; ?></div>
                                <div class="stat-label">Total Sales</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-value"><?php echo $shop['user_count']; ?></div>
                                <div class="stat-label">Users</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-value"><?php echo date('d M Y', strtotime($shop['created_at'])); ?></div>
                                <div class="stat-label">Joined</div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-muted);">
                    <h3>No shops found</h3>
                    <p>When customers register, they will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
