-- OPTIONAL: the app adds this column automatically the first time a sub category is saved (safe to skip).
-- Run by hand ONCE only if you prefer.
ALTER TABLE subcategories ADD COLUMN image VARCHAR(255) NULL AFTER name;
