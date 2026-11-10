-- Sub Categories. Optional: the app runs this automatically the first time it needs it (safe to skip).
-- Run by hand only if you prefer. Run ONCE on an existing database.
CREATE TABLE IF NOT EXISTS subcategories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_sub_name (category_id, name),
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE products ADD COLUMN subcategory_id INT NULL AFTER category_id,
  ADD CONSTRAINT fk_products_subcategory FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL;
-- Existing products keep working (their category stays). They have no sub category yet:
-- open each in Admin > Products > Edit and pick one.
