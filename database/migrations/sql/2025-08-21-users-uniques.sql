USE drivejob;

-- Companies: drop duplicates before UNIQUE (keep smallest id)
SET @has_col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='companies' AND COLUMN_NAME='user_id');

SET @sql = IF(@has_col=1,
  'UPDATE companies c
   JOIN (
     SELECT user_id, id,
            ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY id) AS rn
     FROM companies WHERE user_id IS NOT NULL
   ) t ON t.id=c.id
   SET c.user_id = NULL
   WHERE t.rn>1',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_unique = (SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema=DATABASE() AND table_name='companies' AND index_name='uq_companies_user');
SET @sql = IF(@has_unique=0, 'ALTER TABLE companies ADD UNIQUE KEY uq_companies_user (user_id)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Drivers: drop duplicates before UNIQUE
SET @has_col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='drivers' AND COLUMN_NAME='user_id');

SET @sql = IF(@has_col=1,
  'UPDATE drivers d
   JOIN (
     SELECT user_id, id,
            ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY id) AS rn
     FROM drivers WHERE user_id IS NOT NULL
   ) t ON t.id=d.id
   SET d.user_id = NULL
   WHERE t.rn>1',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_unique = (SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema=DATABASE() AND table_name='drivers' AND index_name='uq_drivers_user');
SET @sql = IF(@has_unique=0, 'ALTER TABLE drivers ADD UNIQUE KEY uq_drivers_user (user_id)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
