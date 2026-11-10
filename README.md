# BuildPro
Construction materials admin panel (PHP + MySQL). No public home page: opening the site goes to `/admin`.

- `/admin` - login (username or mobile + password/PIN), then Categories, Products (title, category, description, min. quantity, price), About Us, Contact Us (3 HTML editors: Address, Phone Number, Email ID) and (admin only) Users.
- Sub Categories: each belongs to a category. A product is added under a **sub category** (its category is set automatically from it).
- API docs: `api.md`
- New DB: run `database.sql`. Existing DB: run `migrate_username.sql` once (adds the `username` column) and `migrate_min_qty_pages.sql` once (adds `products.min_qty` and the `pages` table; the app also does this automatically on first use). Existing DB: also run `migrate_subcategories.sql` once (the app also does this automatically on first use). The dashboard popup table (`migrate_popup.sql`) is also created automatically.
- Popup: opens when the dashboard loads. Admin/subadmin pick the image + product (and on/off) right inside it; users just see it.
