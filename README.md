# BuildPro
Construction materials admin panel (PHP + MySQL). No public home page: opening the site goes to `/admin`.

- `/admin` - login (username or mobile + password/PIN), then Categories, Products (title, category, description, min. quantity, price), About Us, Contact Us (HTML editors) and (admin only) Users.
- API docs: `api.md`
- New DB: run `database.sql`. Existing DB: run `migrate_username.sql` once (adds the `username` column) and `migrate_min_qty_pages.sql` once (adds `products.min_qty` and the `pages` table; the app also does this automatically on first use).
