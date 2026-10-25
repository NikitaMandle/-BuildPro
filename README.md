# BuildPro
Construction materials admin panel (PHP + MySQL). No public home page: opening the site goes to `/admin`.

- `/admin` - login (username or mobile + password/PIN), then Categories, Products and (admin only) Users.
- API docs: `api.md`
- New DB: run `database.sql`. Existing DB: run `migrate_username.sql` once (adds the `username` column).
