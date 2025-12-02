# Shop-Specific Order Numbering Implementation

## Problem

Previously, the system used a global auto-increment ID for all sales across all shops. This meant that each customer (shop) would see non-sequential order numbers because other shops' orders were incrementing the counter.

## Solution

Implemented a per-shop order counter system where each shop has its own sequential order numbering starting from 1.

## Changes Made

### 1. Database Schema Update

- Added `shop_order_number` column to the `sales` table
- Added index on `(shop_id, shop_order_number)` for performance
- Updated `schema.sql` for future installations

### 2. Sales Creation Logic (`sales.php`)

- Modified to query the maximum `shop_order_number` for the current shop
- Increments by 1 to get the next order number
- Inserts the new sale with the shop-specific order number

```php
// Get the next shop order number for this shop
$order_num_result = mysqli_query($conn, "SELECT COALESCE(MAX(shop_order_number), 0) + 1 AS next_num FROM sales WHERE shop_id = $shop_id");
$order_num_row = mysqli_fetch_assoc($order_num_result);
$shop_order_number = $order_num_row['next_num'];
```

### 3. Reports Display (`reports.php`)

- Changed to display `shop_order_number` instead of global `id`
- The clickable link still uses the global `id` for fetching sale details
- Display shows shop-specific sequential numbers (e.g., #1, #2, #3...)

## Benefits

1. **Sequential Order Numbers**: Each shop sees their orders numbered 1, 2, 3, etc.
2. **Independent Counters**: Shop A's orders don't affect Shop B's order numbers
3. **Better Customer Experience**: Order numbers are predictable and sequential
4. **Maintains Compatibility**: Global ID still used internally for relationships

## Migration

Existing sales records have been updated with sequential shop_order_number values based on their creation order within each shop.

## Example

**Before:**

- Shop A: Order #1, #5, #8, #12 (gaps from other shops)
- Shop B: Order #2, #3, #6, #9

**After:**

- Shop A: Order #1, #2, #3, #4 (sequential)
- Shop B: Order #1, #2, #3, #4 (sequential)
