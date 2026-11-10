-- Dashboard popup. Optional: the app creates this table automatically the first time it is needed (safe to skip).
-- Run by hand only if you prefer. Safe to run more than once.
CREATE TABLE IF NOT EXISTS popup (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  image VARCHAR(255) NULL,
  product_id INT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
