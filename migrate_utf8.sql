-- Run ONCE on an EXISTING database that was created as utf8mb4.
-- (Fresh installs using database.sql don't need this.)
-- Note: any 4-byte characters (emoji etc.) in existing data cannot be stored in utf8.
ALTER DATABASE construction_db CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE users      CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE categories CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE products   CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
