-- Migration: Add shop_order_number to sales table
-- This creates a per-shop sequential order counter

USE takeaway_pos;

-- Add the shop_order_number column
ALTER TABLE sales 
ADD COLUMN shop_order_number INT NOT NULL DEFAULT 0 AFTER shop_id;

-- Create an index for better performance
CREATE INDEX idx_shop_order ON sales(shop_id, shop_order_number);

-- Update existing records using a temporary table workaround
UPDATE sales
JOIN (
    SELECT s1.id, 
           (SELECT COUNT(*) FROM sales s2 
            WHERE s2.shop_id = s1.shop_id AND s2.id <= s1.id) AS order_num
    FROM sales s1
) AS numbered ON sales.id = numbered.id
SET sales.shop_order_number = numbered.order_num;
