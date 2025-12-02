-- Migration: Update shop_order_number for existing records
USE takeaway_pos;

-- Update existing records using a temporary table workaround
UPDATE sales
JOIN (
    SELECT s1.id, 
           (SELECT COUNT(*) FROM sales s2 
            WHERE s2.shop_id = s1.shop_id AND s2.id <= s1.id) AS order_num
    FROM sales s1
) AS numbered ON sales.id = numbered.id
SET sales.shop_order_number = numbered.order_num;
