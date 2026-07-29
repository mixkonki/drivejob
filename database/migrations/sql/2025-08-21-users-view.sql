USE drivejob;

DROP VIEW IF EXISTS v_user_overview;
CREATE VIEW v_user_overview AS
SELECT
  u.id,
  u.username,
  u.email,
  c.id AS company_id,
  d.id AS driver_id,
  (SELECT r.name
     FROM user_roles ur
     JOIN roles r ON r.id=ur.role_id
     WHERE ur.user_id=u.id AND ur.is_primary=1
     LIMIT 1) AS primary_role,
  (SELECT GROUP_CONCAT(DISTINCT r2.name ORDER BY r2.name SEPARATOR ", ")
     FROM user_roles ur2
     JOIN roles r2 ON r2.id=ur2.role_id
     WHERE ur2.user_id=u.id) AS roles
FROM users u
LEFT JOIN companies c ON c.user_id=u.id
LEFT JOIN drivers   d ON d.user_id=u.id;
