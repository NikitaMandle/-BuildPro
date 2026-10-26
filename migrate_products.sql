-- Optional: the app runs this automatically the first time a product is saved.
-- Run it by hand only if you prefer.
ALTER TABLE products MODIFY price VARCHAR(100) NOT NULL DEFAULT '';
-- Old columns `unit` and `image` are no longer used by the app. They can stay, or be dropped:
-- ALTER TABLE products DROP COLUMN unit, DROP COLUMN image;
