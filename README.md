# AURU — Software Development Role (Your Part)

## Project
AURU — Secure Academic & Identity Document Vault (CSE472 Cloud Computing, 2-person team)

## Your Role
Build the PHP/MySQL web application. Auronya handles AWS/EC2/LAMP/AMI/Auto Scaling.

## Files (all in `auru/`)

| File | Purpose |
|------|---------|
| `config.php` | DB connection (PDO), session hardening, CSRF, security headers, access-control helpers |
| `register.php` | Student signup with password complexity rules |
| `login.php` | Role-based login (student / officer / admin), generic failure messages |
| `logout.php` | Session destruction |
| `vault.php` | My documents dashboard (upload, download, delete, status badges) |
| `upload.php` | File upload with extension + MIME + size validation |
| `download.php` | Authenticated owner-only file download |
| `delete.php` | Owner-only document deletion (POST + CSRF) |
| `verify.php` | Public officer verification portal (enter code → view status) |
| `admin_verify.php` | Admin approve/reject queue |
| `css/style.css` | Professional government-grade UI theme |
| `uploads/.htaccess` | Deny all direct web access to uploaded files |
| `.htaccess` | Security headers (CSP, X-Frame-Options, etc.) |

## Security Layer (professional, undergraduate-appropriate)

1. **CSRF protection** on every state-changing form (`csrf_token`, `random_bytes(32)`, `hash_equals`)
2. **SQL injection defense**: PDO with `ATTR_EMULATE_PREPARES => false` (real prepared statements)
3. **XSS defense**: `htmlspecialchars(ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` on all output
4. **Password security**: `password_hash` / `password_verify` with complexity rules (8+ chars, uppercase, number)
5. **Session hardening**: `httponly`, `samesite=Lax`, `use_only_cookies`, `session_regenerate_id(true)` on login
6. **File upload security**: extension whitelist (pdf/jpg/jpeg/png), MIME verification via `finfo`, 5 MB cap, stored filename is `uniqid()` (original name never on disk), `chmod 0640`, `uploads/` blocked by `.htaccess`
7. **Generic error messages**: no user enumeration on login, no stack traces to browser
8. **Security headers**: CSP, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`
9. **Access control**: `requireLogin()`, `requireRole('admin')`, owner-only download/delete
10. **Verification logs**: every officer lookup logged with IP, user agent, timestamp

## Handoff — Send to Auronya (cloud member)

```
EC2 Public IP: <from Auronya>
phpMyAdmin URL: http://<IP>/phpmyadmin
Database: auru_db
DB user: root
DB password: SecurePassword123! (or whatever Auronya set)
Web root path: /var/www/html/auru/
Uploads path: /var/www/html/auru/uploads/
Admin account: admin@auru.local / admin123  (change after first login)
```

## Local Testing (before sending to Auronya)

1. Install XAMPP / MAMP / PHP + MySQL locally.
2. Create database `auru_db` and run the DDL from the cloud deployment guide (users, documents, verification_logs tables).
3. Place the `auru/` folder in your local web root (`htdocs` / `/var/www/html`).
4. Update `config.php` DB credentials to match your local setup.
5. Test: register → login → upload → vault → download → verify → admin approve → verify again.

## Deploy to EC2 (after Auronya finishes cloud setup)

1. Auronya creates `/var/www/html/auru/` and `uploads/` with `.htaccess`.
2. Copy all PHP files + `css/` into `/var/www/html/auru/`.
3. Update `config.php` with the EC2 DB credentials from Auronya.
4. Visit `http://<EC2-PUBLIC-IP>/auru/register.php`.

## Demo Flow (10 minutes)

1. Register as student → log in.
2. Upload transcript / NID / driving license → copy verification code.
3. Open `verify.php` → enter code → see "Pending".
4. Log in as admin → approve document.
5. Re-open `verify.php` → enter same code → see "Verified & Authentic".
6. Show Auronya's stress test scaling up the ASG.
