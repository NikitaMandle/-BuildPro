# API Documentation

**Base URL:** `http://localhost/construction-site/api/index.php?path=<resource>[/<id>]`
All responses are JSON: `{"success":true,"data":...}` or `{"success":false,"error":"message"}`.
**Every endpoint except `auth/login` requires login.** Mobile apps: call `auth/login`, then send `Authorization: Bearer <token>` on every request (token valid 30 days). The web admin panel uses the session cookie instead. For file uploads use `multipart/form-data`.
To send PUT/DELETE with multipart, POST with a `_method=PUT` field (the site's JS does this automatically).

**Roles:** `admin` (from `.env`), `subadmin`, `user`.
| Action | admin | subadmin | user | public |
|---|---|---|---|---|
| View categories/products | ✅ | ✅ | ✅ | ❌ (login required) |
| Add/Edit/Delete categories & products | ✅ | ✅ | ❌ | ❌ |
| Manage users (create subadmin/user) | ✅ | ❌ | ❌ | ❌ |

## 1. Auth
### POST `path=auth/login`
Admin: `{"login":"<ADMIN_USER>","secret":"<ADMIN_PASS>"}` (values from the server `.env`; never put real credentials in docs). Subadmin/User: `{"login":"<username or 10-digit mobile>","secret":"<4-digit PIN>"}` (username is case-insensitive).
Response: `{"success":true,"data":{"id":0,"name":"Admin","role":"admin","token":"<TOKEN>"}}` — 401 on wrong credentials. Use `<TOKEN>` as `Authorization: Bearer <TOKEN>`.
### GET `path=auth/me` — current logged-in user (401 if not logged in)
### POST `path=auth/logout` — ends session

## 2. Users (admin only)
Fields: `name`, `username` (3-30 chars, starts with a letter, letters/numbers/`_`/`.`, unique, used for login), `mobile` (10 digits, unique), `email`, `gst` (15 chars), `pin` (4 digits, stored hashed), `role` (`subadmin` | `user`).
| Method | path | Description |
|---|---|---|
| GET | `users` | List all users (admin only, PIN never returned) |
| GET | `users/{id}` | Get one user |
| POST | `users` | Create — all fields required |
| PUT | `users/{id}` | Update — send only fields to change (blank `pin` = unchanged) |
| DELETE | `users/{id}` | Delete user |

Example: `curl -b c.txt -X POST "…?path=users" -H "Content-Type: application/json" -d '{"name":"Ravi","username":"ravi_k","mobile":"9876543210","email":"ravi@x.com","gst":"27ABCDE1234F1Z5","pin":"1234","role":"subadmin"}'`

## 3. Categories (write: admin, subadmin)
Fields: `name` (unique, required), `image` (file, optional JPG/PNG/WEBP ≤ 3MB).
| Method | path | Description |
|---|---|---|
| GET | `categories` | List all (login required) |
| GET | `categories/{id}` | Get one (login required) |
| POST | `categories` | Create (multipart: `name`, `image`) |
| PUT | `categories/{id}` | Update (multipart with `_method=PUT`, or JSON for name only) |
| DELETE | `categories/{id}` | Delete category + its products + image |

## 4. Products (write: admin, subadmin)
Fields: `category_id` (required), `name` (the product **title**, required), `description` (optional **HTML** from the admin editor — already sanitized; allowed tags: `p br b strong i em u s ul ol li h2 h3 h4 blockquote a`), `price` (required, **free text**, e.g. `"₹85 / Kg"`, max 100 chars). No image/unit.
| Method | path | Description |
|---|---|---|
| GET | `products` | List all (login required) |
| GET | `products&category_id=2` | **Category-wise list** (login required) |
| GET | `products/{id}` | Get one (login required) |
| POST | `products` | Create (JSON or form fields) |
| PUT | `products/{id}` | Update |
| DELETE | `products/{id}` | Delete product |

## Errors
`401` login required · `403` forbidden · `404` not found · `405` method not allowed · `422` validation / duplicate.
