USE drivejob;

-- 1) Drop FK if exists (fk_users_role)
SET @has_fk := (SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_users_role');
SET @sql := IF(@has_fk=1, 'ALTER TABLE users DROP FOREIGN KEY fk_users_role', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) Drop column role_id if exists
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='role_id');
SET @sql := IF(@has_col=1, 'ALTER TABLE users DROP COLUMN role_id', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3) Drop legacy string column role if exists
SET @has_col := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='role');
SET @sql := IF(@has_col=1, 'ALTER TABLE users DROP COLUMN role', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
