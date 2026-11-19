# GUCO website: deployment checklist

## 1. Server
- PHP 8.1+ with `pdo_mysql`, `mbstring`, `gd` (images), `fileinfo`; MariaDB/MySQL 10.4+.
- Apache with `mod_rewrite` (sitemap.xml / robots.txt), optional `mod_deflate`, `mod_expires`.
- Upload the project; make `uploads/` and `storage/` writable by the web server.
- If the site is not at `/guco/`, change the base path in `.htaccess` (ErrorDocument, RewriteBase) and `base_url` in `config.php`.

## 2. Database
1. Create an empty database and a dedicated MySQL user (not root).
2. Import `sql/schema.sql`, then run `php tools/migrate.php` (applies every file in `sql/migrations/`).

## 3. Configuration (`config.local.php`, never committed)
```php
<?php
return [
    'db' => ['host' => 'localhost', 'name' => 'guco', 'user' => 'guco_user', 'pass' => '...'],
    'public_url' => 'https://your-domain.org',   // used in emailed links, sitemap, canonical URLs
    'dev_mode' => false,
    'smtp' => ['host' => 'smtp.example.org', 'port' => 587, 'encryption' => 'tls',
               'username' => 'mailer@example.org', 'password' => '...', 'from' => 'no-reply@example.org'],
];
```
Keep `dev_mode` off in production.

## 4. Email and two-step sign-in
- Every staff/admin login asks for a 6-digit code emailed to the account.
- Before relying on it: Admin > Mail log > "Send a test email to me". If it fails, fix SMTP first.
- After the first login, open Admin > My account and generate **backup codes**; store them safely.
- Emergency, from the server command line: `php tools/twofa.php off` (turns the check off), `php tools/twofa.php unlock EMAIL`.
- `'admin_2fa' => false` in `config.local.php` also overrides the setting.

## 5. Security
- Create the first super admin, then change/remove any seeded demo accounts.
- Enable HTTPS and uncomment the redirect in `.htaccess`.
- `.htaccess` blocks `.sql`, `.md`, config files, `.git`; `includes/`, `sql/`, `tools/`, `storage/` are denied.
- Schedule `Admin > Backup` downloads (or a cron database dump).

## 6. Search engines
- Sitemap: `/sitemap.xml`, robots: `/robots.txt`. Submit the sitemap in Google Search Console.
- Set the site name/tagline in Admin > Settings; per-page titles and descriptions come from each page.

## 7. Go-live test
Sign in as admin (with the emailed code), open every Admin menu, submit the contact form, register a test member, check the emails arrive, then delete the test data.

## 8. Deploying from GitHub (cPanel)
Pushing to `main` runs `.github/workflows/deploy.yml`, which lint-checks PHP and uploads changed files over FTPS. It never touches `config.local.php`, `uploads/` or `storage/` on the server.

One-time setup:
1. In cPanel create an FTP account whose home is the site folder (e.g. `public_html`), note the FTP host name.
2. In GitHub: repo > Settings > Secrets and variables > Actions > New repository secret, add:
   `FTP_SERVER` (host), `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_DIR` (e.g. `/public_html/` or `/`).
3. On the server, once: create the database (cPanel MySQL), upload `config.local.php` by File Manager with the live settings (section 3), import `sql/schema.sql` in phpMyAdmin.
4. Run migrations after each release that adds files under `sql/migrations/`: cPanel Terminal (`php tools/migrate.php`) or a cron job run once. The FTP upload does not run them.
5. If the site is in the domain root, set `base_url` to `''` in `config.php` and update `.htaccess` (ErrorDocument paths, `RewriteBase /`).
6. Re-run the workflow from the Actions tab if a deploy fails; use FTP-over-TLS (`ftps`). Change `protocol` to `ftp` only if the host has no TLS.

## 9. Hostinger specifics (hPanel)
- **Database:** hPanel > Databases > MySQL Databases: create the database and user. Names get a prefix (e.g. `u123456789_guco`); put those exact values in `config.local.php`, with `'host' => 'localhost'`.
- **Import:** hPanel > Databases > phpMyAdmin > Import `sql/schema.sql`. Then apply each file in `sql/migrations/` in order (or use SSH below).
- **PHP:** hPanel > Advanced > PHP Configuration: choose PHP 8.2, and make sure `pdo_mysql`, `mbstring`, `gd`, `fileinfo` are on.
- **FTP:** hPanel > Files > FTP Accounts. Secrets for GitHub: `FTP_SERVER` = the FTP hostname shown there (or the server IP), `FTP_USERNAME` = the FTP user (e.g. `u123456789.yourdomain`), `FTP_DIR` = `/public_html/` (or `/domains/yourdomain.com/public_html/` on some plans). If the deploy fails on TLS, change `protocol: ftps` to `ftp` in the workflow.
- **Alternative to FTP:** hPanel > Advanced > Git can pull straight from `https://github.com/komabono1998/guco.git` (branch `main`) into `public_html`, with an auto-deploy webhook you add under GitHub > Settings > Webhooks. Use one method, not both.
- **SSH (Business plans and up):** hPanel > Advanced > SSH Access, then `cd domains/yourdomain.com/public_html && php tools/migrate.php`.
- **Email/SMTP:** create a mailbox in hPanel > Emails; use host `smtp.hostinger.com`, port 465, encryption `ssl` (or 587 `tls`), the full mailbox address as username, and the same address as `from`. Then run the test email.
- **Domain root:** set `base_url` to `''` in `config.php`, and `.htaccess` paths to `/` (`ErrorDocument 404 /404.php`, `RewriteBase /`). Turn on the free SSL in hPanel, then enable the HTTPS redirect in `.htaccess`.
