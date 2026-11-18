# Roles and permissions

Enforced on the server by `require_perm()` / `can()` in `includes/bootstrap.php` (the `PERMS` map). Hiding a menu item is never the only protection.

| Role | Area of responsibility |
|---|---|
| Super Administrator | Everything, including granting the Super Administrator role |
| System Administrator | Users and roles (not Super Administrator), settings, mail log, audit log, backup |
| GUCO Secretariat | Day-to-day operations across membership, payments, donations, careers, content, events, messages, reports |
| Convocation Executive | Reviews member-led projects, views reports, Hall of Fame |
| Membership Officer | Members, approvals, import/export, reports |
| Finance Officer | Member payments, donations and campaigns, finance reports |
| Communications / Publicity | News, pages, content, newsletters, events, gallery, documents, Hall of Fame |
| Newsletter Editor | Newsletters, news (needs approval to publish), subscribers |
| Events Officer | Events, registrations, check-in |
| Career Officer | Jobs, internships, scholarships, applications, Mentorship Hub oversight |
| Chapter Coordinator | Only their own chapter and its members |
| Alumni Member | Own profile, card, payments, events and tickets, careers, giving, directory (if approved) |
| Public Visitor | Public pages, give, careers (non member-only), event registration |

Create staff accounts under Admin > Users & roles. Every grant, approval, verification, export, import, backup and check-in is written to the audit log.
