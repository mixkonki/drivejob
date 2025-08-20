-- Migration: Add user_id foreign keys to companies/drivers tables
-- Date: 2025-08-20
-- Purpose: Link companies and drivers to users table for proper RBAC

-- 1) Προσθήκη user_id στις companies/drivers (με έλεγχο αν υπάρχουν)
-- Check and add user_id to companies
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'companies' 
     AND COLUMN_NAME = 'user_id') = 0,
    'ALTER TABLE companies ADD COLUMN user_id INT NULL AFTER id',
    'SELECT "user_id already exists in companies" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add user_id to drivers
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'drivers' 
     AND COLUMN_NAME = 'user_id') = 0,
    'ALTER TABLE drivers ADD COLUMN user_id INT NULL AFTER id',
    'SELECT "user_id already exists in drivers" as message'
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
-- Index for companies.user_id
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'companies' 
     AND INDEX_NAME = 'idx_companies_user_id') = 0,
    'CREATE INDEX idx_companies_user_id ON companies(user_id)',
    'SELECT "idx_companies_user_id already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index for drivers.user_id
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'drivers' 
     AND INDEX_NAME = 'idx_drivers_user_id') = 0,
    'CREATE INDEX idx_drivers_user_id ON drivers(user_id)',
    'SELECT "idx_drivers_user_id already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4) (Προαιρετικά) Constraints, ΜΟΝΟ αν δεν έχεις orphan rows:
-- ALTER TABLE companies ADD CONSTRAINT fk_companies_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;
-- ALTER TABLE drivers  ADD CONSTRAINT fk_drivers_user_id  FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE;
