-- Migration: Add Superadmin Role and User
USE takeaway_pos;

-- 1. Update users table to include 'superadmin' role
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'manager', 'superadmin') DEFAULT 'cashier';

-- 2. Create a dedicated shop for Superadmin (if not exists)
INSERT INTO shops (id, name, location) 
SELECT 9999, 'Superadmin HQ', 'System' 
WHERE NOT EXISTS (SELECT 1 FROM shops WHERE id = 9999);

-- 3. Insert Superadmin User
-- Password '123' needs to be hashed. I will use a simple hash for now or plain text if the app uses plain text (checking functions.php next).
-- Assuming password_hash() is used in the app, I'll use a placeholder and update it via PHP or use a known hash.
-- For now, I will insert it. If the app uses password_verify, I need the hash for '123'.
-- Hash for '123' (bcrypt): $2y$10$2.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1.1 (This is fake, I will generate a real one in PHP or just insert and let the user know)

-- Let's check functions.php first to see how passwords are handled.
