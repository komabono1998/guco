# Deployment, configuration, backup and recovery

## Requirements
PHP 8.1+ (extensions: pdo_mysql, mbstring, fileinfo), MySQL 5.7+/MariaDB 10.4+, Apache with `mod_rewrite` not required, `AllowOverride All` so the `.htaccess` files in `/`, `uploads/` and `storage/` apply (they block script execution and direct file access). HTTPS strongly recommended.

## First install
1. Create an empty database (utf8mb4) and a database user with rights on it only.
2. Import `sql/schema.sql`, then run `php tools/migrate.php`.
3. Create `config.local.php` (git-ignored) next to `config.php`:
   ```php
   <?php
   return [
       'base_url'   => '',                         // '/guco' if the site is in a sub-folder
       'public_url' => 'https://guco.example.org', // used in emailed links
       'db'         => ['host' => '127.0.0.1', 'name' => 'guco', 'user' => 'guco_app', 'pass' => 'a-long-random-password'],
       'dev_mode'   => false,
   ];
   ```
4. Create the first administrator: `php tools/create_admin.php "Full Name" email@example.org "a-strong-password"`, then change the password under Account.
5. Make `uploads/` and `storage/` writable by the web server user.
6. Change `ErrorDocument 404 /guco/404.php` in `.htaccess` if the site is not under `/guco/`.
7. Configure SMTP so email is delivered (Admin > Mail log shows what failed).

## Updating
1. Admin > Backup > Download backup (and copy `uploads/` and `storage/`).
2. Deploy the new code.
3. `php tools/migrate.php --status`, then `php tools/migrate.php`. To rehearse, restore the backup into a scratch database and run `php tools/migrate.php --db=scratch`.

## Backup and recovery
- Database: Admin > Backup (SQL dump), or `mysqldump`. Weekly at minimum.
- Files: copy `uploads/` (public pictures) and `storage/` (documents, payment proofs, member files).
- Restore: create an empty database, `mysql -u USER -p DB < backup.sql`, copy the folders back, point `config.local.php` at it. Restore into a scratch database periodically to prove the backups work.

## Environment checklist before launch
- [ ] `dev_mode` is false and `config.local.php` holds real database credentials
- [ ] HTTPS enabled; `public_url` set
- [ ] SMTP working (send a test newsletter to yourself)
- [ ] Payment accounts entered in Settings > Giving and donations
- [ ] Default test accounts removed; every admin has a strong password
- [ ] Backups scheduled and one restore tested
