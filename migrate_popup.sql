-- Dashboard popups (Admin > Popup tab, full CRUD). Optional: the app creates this table automatically the first time it is needed (safe to skip).
-- Run by hand only if you prefer. Safe to run more than once.
CREATE TABLE IF NOT EXISTS popups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NULL,
  image VARCHAR(255) NULL,
  product_id INT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- If you used the old single-popup table (`popup`), copy it over once, then drop it (the app also does this automatically):
-- INSERT INTO popups(image,product_id,active) SELECT image,product_id,active FROM popup WHERE image IS NOT NULL OR product_id IS NOT NULL;
-- DROP TABLE popup;
