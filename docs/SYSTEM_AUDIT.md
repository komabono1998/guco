# GUCO platform: audit, inventory and delivery report

Last updated: 18 November 2026. Stack: plain PHP 8 + MySQL/MariaDB (no framework), Apache on XAMPP.

## 1. Condition of the original site

The site was already a working alumni platform: public pages driven by a content editor, registration with proof of payment, approval and membership IDs, QR membership cards, a member portal, chapters, projects, events with email invitations, news, newsletters, gallery, documents, awards, a corporate admin console with audit log. Weaknesses found and fixed in this and the previous round:

| Finding | Severity | Status |
|---|---|---|
| `csrf_check()` accepted a form with no token when the session had none | High | Fixed |
| Sign-in throttling was per-session only (cleared by dropping cookies) | High | Fixed: database throttle by IP and by account |
| `dev_mode` on by default (shows reset links on screen, PHP errors) | High | Fixed: off by default, on only via `config.local.php` |
| Password-reset links built from the `Host` header | Medium | Fixed: validated host or `public_url` |
| Open redirect in `subscribe.php` (prefix host match) | Medium | Fixed |
| `uploads/` could execute scripts | High | Fixed: `.htaccess` denies scripts |
| Database errors shown to visitors | Medium | Fixed: friendly 500 page, errors logged |
| No security headers, no session hardening | Medium | Fixed |
| CSV exports vulnerable to spreadsheet formula injection | Medium | Fixed |
| CR/LF could be injected into mail recipient or subject | Low | Fixed |
| Logo shipped at 378 KB on every page | Low | Fixed: 20 KB copy |

## 2. Existing features retained (unchanged in behaviour)

Public site and CMS (home, about, membership, leadership, programmes, chapters, awards, partnerships, contact, legal), menu manager, branding, settings; registration with proof of payment, approval, automatic member number and membership ID, card with QR and public verification, revoke/reinstate, member portal, directory with consent, chapters, member-led projects reviewed by the executive; events with flyers, countdowns, calendar, `.ics`, invitations; news (written, link, document), newsletters (written or PDF, emailed), gallery albums, documents library, awards and nominations, partners, anthem and campus sections; admin dashboard, search, reports, audit log, mail log; admin-created members and password reset; standalone admin and member sign-in pages.

## 3. New in this round

| Module | What it does |
|---|---|
| Migrations | `sql/migrations/*.sql` + `php tools/migrate.php` (idempotent, recorded in `schema_migrations`, `--status`, `--db=` for scratch copies) |
| RBAC | New roles: System Administrator, Membership Officer, Finance Officer, Events Officer, Career Officer. New permissions `donations`, `careers`, `checkin`, `backup`. Only a Super Administrator can grant or change the Super Administrator role |
| Giving | Campaigns with targets and progress, public Give page, donor form (MTN, Airtel, bank, cash), anonymous option, recurring pledges, Finance Officer verification, numbered receipts (`GUCO-R-YYYY-NNNNNN`) with secret print link, donor list, member giving history, CSV export, direct cash/bank recording |
| Careers | Jobs, internships, scholarships, fellowships, volunteering, resources; public listing with search and filters; members-only listings; apply through the portal with notes; applicant management with status and notification; CSV export |
| Event tickets | Unique ticket per registration, QR ticket page, emailed link, capacity limit, check-in console (scan or type), double-scan detection, attendance export |
| Notification centre | In-portal notifications (membership approved, payment decisions, donation receipts, tickets, application updates) with unread count |
| Import / export | Member CSV template, test-run import, duplicate detection (email exact; name + year flagged), temporary passwords shown once, member export, duplicate list |
| Backup | One-click portable SQL dump (admin only, audited); tested by restoring into a scratch database |
| Reports | Added faculty/programme, event attendance, careers, donations by campaign and by method/month, administrative activity |

## 3a. Added after the feature-list review (migration 003)

| Module | What it does |
|---|---|
| Alumni Hall of Fame | Admin-managed honourees (name, title, category, citation, portrait, year inducted, featured, order, published); public gallery with category filter and profile pages; home-page spotlight (toggle under Branding); menu entry |
| Mentorship Hub | Members opt in as mentor, mentee or both with areas and availability; mentees search open mentors and send a request (max 5 waiting); mentors accept or decline; contact details are shared only after acceptance; either side can end; admin overview, CSV export, and remove/restore for conduct concerns (closes active pairings) |
| Alumni Connect | Directory upgraded with graduation-year and chapter filters and a Connect button; connection requests (max 20 waiting); accepting shares contact details both ways; remove at any time; notifications at each step. Only members who gave directory consent are discoverable |

## 4. Improved

Hero loading (priority image, fixed size, mobile order), configurable hero links, sign-in security, headers, error handling, role separation, audit coverage of exports, imports, verifications and check-ins.

## 5. Not implemented, and why

These were in the brief but are separate products; none was faked with an empty screen. They are listed in priority order for the next phase:

1. **Live MoMo / Airtel API collection** and card payments: needs merchant accounts and credentials. Structure and steps in `docs/PAYMENTS.md`. Until then payments are matched by a human against the statement; no payment is ever auto-marked successful.
2. SMS notifications (needs an SMS gateway account), WhatsApp.
3. Alumni forums and private messaging (need moderation tooling and a privacy review first). Connection requests and mentorship are now built (see section 3a).
4. Voting / elections platform (needs voter-roll rules from the Electoral Committee and an independent audit).
5. Business directory, success stories, surveys and polls, volunteer registration, scholarship applications, alumni map, public API, AI search and drafting.
6. PDF/Excel-native report export (CSV opens in Excel; use the browser's Print to PDF for reports).

## 6. External dependencies and unresolved items

- SMTP: PHP `mail()` has no server on this XAMPP, so emails are logged as "failed". Configure SMTP (`php.ini` `[mail function]` or a sendmail replacement) before launch.
- QR codes load `qrcodejs` from cdnjs; if offline, the code text is shown instead.
- Database user is `root` with no password locally: set real credentials in `config.local.php` before going live.
- Events have no dates yet (shown as "TBA"); content placeholders (anthem, contacts, payment accounts) need real data in the admin.

## 7. Testing results

Automated checks run against the live site with temporary data (removed afterwards):

- All 108+ PHP files lint clean; public, admin and member pages return no PHP errors.
- Donation journey: submit, duplicate reference blocked, invalid amount blocked, stays pending, verify, receipt number issued, receipt opens only with the secret token, campaign progress updates, HTML in messages escaped, CSV formula neutralised.
- Careers: public listing, visitor cannot apply, member can apply once, duplicate blocked, status change notifies the member.
- Events: ticket issued, QR page, capacity enforced, check-in works, second scan flagged, wrong code rejected, attendance export.
- Import: test run saves nothing; real run creates accounts; bad rows and duplicates reported.
- Backup: downloaded dump restored into an empty database with identical row counts; backup refuses a POST without a CSRF token.
- RBAC matrix verified for finance, membership, career, events and system admin roles; a System Administrator cannot create a Super Administrator.
- Migrations: applied to a copy of live data first, re-run to prove idempotence, then applied to live (a pre-migration SQL backup was taken).

Known failures: none open from this run.

## 8. Database changes

See `sql/migrations/001_platform_modules.sql` and `002_giving_careers_menu.sql`: new tables `campaigns`, `donations`, `jobs`, `job_applications`, `notifications`, `login_attempts`, `schema_migrations`, `hall_of_fame`, `mentorship_profiles`, `mentorship_requests`, `connections` (migration 003); new columns `events.capacity`, `event_registrations.ticket_code/checked_in_at/checked_in_by`, `donations.access_token`; widened `users.role` enum; two menu items (Give, Careers). No existing rows were modified except giving existing event registrations a ticket code.
