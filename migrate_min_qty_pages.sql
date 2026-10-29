-- Optional: the app runs both of these automatically (first product save / first pages request).
-- Run by hand only if you prefer. Safe to run once on an existing database.
ALTER TABLE products ADD COLUMN min_qty VARCHAR(100) NOT NULL DEFAULT '' AFTER description;
CREATE TABLE IF NOT EXISTS pages (
  slug VARCHAR(30) NOT NULL PRIMARY KEY,
  content MEDIUMTEXT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
