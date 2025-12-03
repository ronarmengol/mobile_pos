-- Migration: Add Shop Status and Subscription Expiry
USE takeaway_pos;

-- Add status and subscription_expiry columns to shops table
ALTER TABLE shops 
ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active',
ADD COLUMN subscription_expiry DATETIME DEFAULT NULL;

-- Set default subscription expiry for existing shops to 1 month from now
UPDATE shops SET subscription_expiry = DATE_ADD(NOW(), INTERVAL 1 MONTH) WHERE id != 9999;
