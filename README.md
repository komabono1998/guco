# Gulu University Convocation (GUCO) website

Alumni website built with plain PHP 8 + MySQL/MariaDB (XAMPP). Content follows the *GUCO Website Content Document* and the 2023 Constitution.

## Features
**Public site:** Home, About (vision, mission, values, objectives), Membership (categories, rights, obligations, FAQ), Leadership & Governance, Programmes & Impact, Alumni Chapters, News (categories, search, scheduling), Events calendar with registration, Awards, Partnerships, Resources library, Gallery, Contact, Privacy and Accessibility pages, newsletter sign-up.

**Members:** online application with category and eligibility details, application status, Member Portal, profile and password change, password reset, consent-based members' directory (no contact details shown), member-only documents.

**Admin (`/admin/`)** with role-based access (Super Administrator, GUCO Secretariat, Communications Editor, Newsletter Editor, Chapter Coordinator):
members and applications (approve, category, subscription, CSV export), news with editorial review (draft / pending / published), events and registrations (CSV), chapters, leadership profiles, programme impact reports, awards, resources (documents stored outside the web root), gallery, messages, subscribers, site settings, users and roles, audit log.

**Safeguards from the content document:** membership fees, payment instructions, contact details, social links and office-bearer names stay hidden until entered and enabled in Admin > Settings / Leadership. Nomination status for awards defaults to "not open".

## Setup
1. Start Apache and MySQL in XAMPP; the project lives at `htdocs/guco`.
2. Create the database and tables: `mysql -u root < sql/schema.sql`
3. Create a super administrator: `php tools/create_admin.php "Your Name" you@example.com a-strong-password`
4. Visit http://localhost/guco/ and log in at `/login.php`.
5. Local overrides (DB credentials, `base_url`, `dev_mode`) go in `config.local.php` (git-ignored), returning an array shaped like `config.php`.

Before going live: set `dev_mode` to `false`, configure mail so password-reset emails send, upload the Constitution (Admin > Resources, category Constitution), confirm contact details, fees and office bearers with the Secretariat, and have the privacy notice approved.

## Layout
- `includes/` bootstrap (DB, roles, CSRF, spam checks, uploads), layout, shared forms, admin helpers (generic CRUD)
- `admin/` back-office pages; `assets/` CSS and logo; `uploads/` public images (git-ignored); `storage/resources/` protected documents (git-ignored); `sql/` schema
