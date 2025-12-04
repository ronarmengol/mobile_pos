-- Create subscription_orders table
CREATE TABLE IF NOT EXISTS subscription_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    months INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- Add index for faster queries
CREATE INDEX idx_shop_status ON subscription_orders(shop_id, status);
CREATE INDEX idx_status ON subscription_orders(status);
