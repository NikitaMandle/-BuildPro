-- Optional: the app runs all of this automatically the first time it needs it (safe to skip).
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

CREATE TABLE IF NOT EXISTS popup (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  image VARCHAR(255) NULL,
  product_id INT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
-- Existing products keep working: they have no sub category yet. Open each in Admin > Products > Edit and pick one.
