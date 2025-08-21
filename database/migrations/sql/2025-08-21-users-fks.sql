USE drivejob;

-- Ensure engines
ALTER TABLE companies ENGINE=InnoDB;
ALTER TABLE drivers   ENGINE=InnoDB;

-- Companies.user_id FK (only if column exists & FK not already there)
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='companies' AND COLUMN_NAME='user_id');
SET @has_fk  := (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_companies_user');
SET @sql := IF(@has_col=1 AND @has_fk=0,
  'ALTER TABLE companies
     ADD INDEX idx_companies_user (user_id),
     ADD CONSTRAINT fk_companies_user
       FOREIGN KEY (user_id) REFERENCES users(id)
       ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Drivers.user_id FK
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='drivers' AND COLUMN_NAME='user_id');
SET @has_fk  := (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_drivers_user');
SET @sql := IF(@has_col=1 AND @has_fk=0,
  'ALTER TABLE drivers
     ADD INDEX idx_drivers_user (user_id),
     ADD CONSTRAINT fk_drivers_user
       FOREIGN KEY (user_id) REFERENCES users(id)
       ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
