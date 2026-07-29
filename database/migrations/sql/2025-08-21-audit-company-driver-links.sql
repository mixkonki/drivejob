USE drivejob;

-- Requires rbac_actor_id() from Phase 8 (audit). Safe to re-create triggers.

-- Drop existing triggers first
DROP TRIGGER IF EXISTS trg_companies_user_ai;
DROP TRIGGER IF EXISTS trg_companies_user_au;
DROP TRIGGER IF EXISTS trg_drivers_user_ai;
DROP TRIGGER IF EXISTS trg_drivers_user_au;

-- Create companies triggers
CREATE TRIGGER trg_companies_user_ai
AFTER INSERT ON companies
FOR EACH ROW
BEGIN
  IF NEW.user_id IS NOT NULL THEN
    INSERT INTO rbac_audit (actor_user_id,event,entity,entity_id,details)
    VALUES (rbac_actor_id(),'insert','companies', NEW.id,
            JSON_OBJECT('user_id', NEW.user_id));
  END IF;
END;

CREATE TRIGGER trg_companies_user_au
AFTER UPDATE ON companies
FOR EACH ROW
BEGIN
  IF NOT (OLD.user_id <=> NEW.user_id) THEN
    INSERT INTO rbac_audit (actor_user_id,event,entity,entity_id,details)
    VALUES (rbac_actor_id(),'update','companies', NEW.id,
            JSON_OBJECT('old_user_id', OLD.user_id, 'new_user_id', NEW.user_id));
  END IF;
END;

-- Create drivers triggers
CREATE TRIGGER trg_drivers_user_ai
AFTER INSERT ON drivers
FOR EACH ROW
BEGIN
  IF NEW.user_id IS NOT NULL THEN
    INSERT INTO rbac_audit (actor_user_id,event,entity,entity_id,details)
    VALUES (rbac_actor_id(),'insert','drivers', NEW.id,
            JSON_OBJECT('user_id', NEW.user_id));
  END IF;
END;

CREATE TRIGGER trg_drivers_user_au
AFTER UPDATE ON drivers
FOR EACH ROW
BEGIN
  IF NOT (OLD.user_id <=> NEW.user_id) THEN
    INSERT INTO rbac_audit (actor_user_id,event,entity,entity_id,details)
    VALUES (rbac_actor_id(),'update','drivers', NEW.id,
            JSON_OBJECT('old_user_id', OLD.user_id, 'new_user_id', NEW.user_id));
  END IF;
END;
