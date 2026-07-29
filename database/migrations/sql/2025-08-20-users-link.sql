-- Migration: Add user_id foreign keys to companies and drivers tables
-- Date: 2025-08-20
-- Purpose: Link companies and drivers to users table for proper admin/users endpoint functionality

USE drivejob;

-- 1) Προσθήκη user_id στις companies/drivers (με έλεγχο αν υπάρχουν)
-- Προσθήκη user_id στο companies table
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'drivejob' AND TABLE_NAME = 'companies' AND COLUMN_NAME = 'user_id') > 0,
    'SELECT "Column user_id already exists in companies table" AS message',
    'ALTER TABLE companies ADD COLUMN user_id INT NULL AFTER id'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Προσθήκη user_id στο drivers table
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'drivejob' AND TABLE_NAME = 'drivers' AND COLUMN_NAME = 'user_id') > 0,
    'SELECT "Column user_id already exists in drivers table" AS message',
    'ALTER TABLE drivers ADD COLUMN user_id INT NULL AFTER id'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Backfill: σύνδεση με βάση το email (όπου υπάρχει ακριβές match)
UPDATE companies c
JOIN users u ON u.email = c.email
SET c.user_id = u.id
WHERE c.user_id IS NULL;

UPDATE drivers d
JOIN users u ON u.email = d.email
SET d.user_id = u.id
WHERE d.user_id IS NULL;

-- 3) Ευρετήρια για τα νέα πεδία (με έλεγχο αν υπάρχουν)
-- Index για companies.user_id
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = 'drivejob' AND TABLE_NAME = 'companies' AND INDEX_NAME = 'idx_companies_user_id') > 0,
    'SELECT "Index idx_companies_user_id already exists" AS message',
    'CREATE INDEX idx_companies_user_id ON companies(user_id)'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index για drivers.user_id
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = 'drivejob' AND TABLE_NAME = 'drivers' AND INDEX_NAME = 'idx_drivers_user_id') > 0,
    'SELECT "Index idx_drivers_user_id already exists" AS message',
    'CREATE INDEX idx_drivers_user_id ON drivers(user_id)'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4) (Προαιρετικά) Constraints, ΜΟΝΟ αν δεν έχεις orphan rows:
-- ALTER TABLE companies ADD CONSTRAINT fk_companies_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;
-- ALTER TABLE drivers  ADD CONSTRAINT fk_drivers_user_id  FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Επιβεβαίωση ολοκλήρωσης
SELECT 'Migration 2025-08-20-users-link.sql completed successfully' AS status;
