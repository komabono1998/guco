# Gulu University Convocation (GUCO) website

Alumni website built with plain PHP 8 + MySQL (XAMPP).

## Features
- Public: home, about, news, events, gallery, contact, donation pledges
- Members: registration (admin-approved), login, profile, searchable alumni directory
- Admin (`/admin/`): approve members, manage news, events, gallery, view messages and pledges

## Setup
1. Start Apache and MySQL in XAMPP; the project lives at `htdocs/guco`.
2. Import the schema: `mysql -u root < sql/schema.sql` (or via phpMyAdmin).
3. Create an admin: `php tools/create_admin.php "Your Name" you@example.com a-strong-password`
4. Visit http://localhost/guco/
5. Local overrides (DB credentials, contact email, donation details): copy values into `config.local.php` (git-ignored), returning an array shaped like `config.php`.

## Layout
- `includes/` bootstrap (DB, helpers, CSRF, auth), header/footer
- `admin/` admin pages; `assets/` CSS; `uploads/` gallery images (git-ignored); `sql/` schema
