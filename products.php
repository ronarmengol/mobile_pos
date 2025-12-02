<?php
require_once 'functions.php';
requireAdmin();

$shop_id = $_SESSION['shop_id'];
$message = '';

// Handle Add/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $name = sanitize($conn, $_POST['name']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        
        $sql = "INSERT INTO products (shop_id, name, price, stock) VALUES ($shop_id, '$name', $price, $stock)";
        if (mysqli_query($conn, $sql)) {
            $message = "Product added successfully!";
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['delete_product'])) {
        $id = (int)$_POST['product_id'];
        mysqli_query($conn, "DELETE FROM products WHERE id = $id AND shop_id = $shop_id");
        $message = "Product deleted.";
    } elseif (isset($_POST['update_stock'])) {
        $id = (int)$_POST['product_id'];
        $qty = (int)$_POST['quantity'];
        // Allow negative numbers to reduce stock, but don't go below 0 if you want logic check
        mysqli_query($conn, "UPDATE products SET stock = stock + $qty WHERE id = $id AND shop_id = $shop_id");
        $message = "Stock updated.";
    } elseif (isset($_POST['update_product'])) {
        $id = (int)$_POST['product_id'];
        $name = sanitize($conn, $_POST['name']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];

        $sql = "UPDATE products SET name = '$name', price = $price, stock = $stock WHERE id = $id AND shop_id = $shop_id";
        if (mysqli_query($conn, $sql)) {
            $message = "Product updated successfully!";
        } else {
            $message = "Error updating product: " . mysqli_error($conn);
        }
    }
}

// Fetch Products
$products = mysqli_query($conn, "SELECT * FROM products WHERE shop_id = $shop_id ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Takeaway POS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <a href="dashboard.php" class="btn btn-secondary">← Back</a>
        <h2>Manage Menu</h2>
    </div>

    <div class="app-container">
        <?php if ($message): ?>
            <div class="alert success">✅ <?php echo $message; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h3>Add New Item</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>

                <div class="form-group">
                    <label>Price</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" value="100">
                </div>
                <button type="submit" name="add_product" class="btn btn-primary btn-block">Add Product</button>
            </form>
        </div>

        <div class="table-responsive" style="margin: 20px;">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($products)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo formatPrice($row['price']); ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-weight: bold; font-size: 1.1rem;"><?php echo $row['stock']; ?></span>
                                <form method="POST" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                    <input type="number" name="quantity" placeholder="+/-" style="width: 70px; padding: 6px; font-size: 0.9rem;" required>
                                    <button type="submit" name="update_stock" class="btn btn-secondary" style="padding: 6px 10px;" title="Update Stock">↻</button>
                                </form>
                            </div>
                        </td>
                        <td>
                            <button type="button" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>)' class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem; margin-right: 5px;">Edit</button>
                            <form method="POST" onsubmit="return confirmDelete(this);" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_product" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="js/modal.js"></script>
    <script>
        function confirmDelete(form) {
            Modal.confirm(
                "Are you sure you want to delete this item?",
                () => form.submit(),
                "Delete Product"
            );
            return false;
        }

        function openEditModal(product) {
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_stock').value = product.stock;
            document.getElementById('editProductModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editProductModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('editProductModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal-overlay">
        <div class="modal-content" style="text-align: left;">
            <h3 class="modal-title">Edit Product</h3>
            <form method="POST">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="edit_name" required style="width: 100%; padding: 8px; margin-bottom: 10px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--card-border); color: white; border-radius: 4px;">
                </div>
                <div class="form-group">
                    <label>Price</label>
                    <input type="number" step="0.01" name="price" id="edit_price" required style="width: 100%; padding: 8px; margin-bottom: 10px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--card-border); color: white; border-radius: 4px;">
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" id="edit_stock" required style="width: 100%; padding: 8px; margin-bottom: 20px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--card-border); color: white; border-radius: 4px;">
                </div>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_product" class="modal-btn modal-btn-confirm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
