# ADR-004 — Institutional website as an admissions-first public gateway
Date: 2026-10-31
Status: Accepted for initial implementation, subject to local deployment checks.

## Decision
Create a public-facing institutional website in the existing Laravel application. Keep the admission portal under `/admissions` and link to it prominently throughout the website; give the institutional homepage the `/` route. Retain all existing Sanctum authentication, Spatie permissions, MySQL application data and portal controllers.

## Website architecture
The institutional site is a separate `App\Modules\Institutional` module with its own route include, controllers, CMS models, CSS and Blade templates. A safe installer backs up and modifies the Laravel `routes/web.php` default root route only when it is the stock Laravel welcome page; a custom root route causes installation to abort for manual review. This limits cross-module coupling.

## Editorial governance
Institution-specific claims (history, vision, mission, accreditation, governing council, programmes, fees, leadership, and regulations) are not fabricated. They are stored as approved plaintext CMS blocks editable only by existing `admin` or `superadmin` users. News and event posts are initially drafts until explicitly published. Admin/media routes reject unprivileged and unauthenticated access.

## Admissions priority
Primary call-to-action links directly to the existing admission module. Academic programme availability is delegated to the admissions catalogue (single source of truth), not duplicated in the website CMS.

## Privacy and security
Contact form includes CSRF protection, input validation, a hidden honeypot and route throttling; submissions are stored in a restricted administrative inbox and are not emailed automatically. Campus images are uploaded via MIME/image validation and placed in the public Laravel filesystem only with an explicit publication flag. Published HTML is escaped by Blade; no arbitrary HTML is allowed in CMS content. Production launch still requires GDPR/NDPA review, automated retention rules, content security headers, backup/restore procedures, staff MFA, contact-notification integration, and full security testing.

## Brand decisions
The user-provided low-resolution JPEG is preserved without redesign; a padded favicon and mobile touch icons are derived from it. Request a higher-resolution original logo for production use if available. No campus photography is invented; the public site shows designated placeholders until authentic images are provided.
