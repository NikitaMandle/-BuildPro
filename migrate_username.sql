-- Run once on an existing database (adds username login support)
ALTER TABLE users ADD COLUMN username VARCHAR(30) NULL UNIQUE AFTER name;
-- Existing users have no username yet: set one from Admin > Users > Edit,
-- or manually, e.g.  UPDATE users SET username='ravi' WHERE id=1;
