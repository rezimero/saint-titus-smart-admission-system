# QServers — STSAMS Institutional Website Launch Runbook
Date: 2026-11-01 | STATUS: Preparation only. Do NOT expose unfinished admissions to real applicants.

## Deployment topology
- Canonical: `https://stcollegeofhealthsciences.edu.ng` for the public institution website and `/admissions` for the integrated admission portal. Optional `admissions.` subdomain redirects to `/admissions` rather than duplicating PHP application/cookie state.
- Laravel application root: private directory **outside** public webroot.
- Apache/hosting document root must be the Laravel `backend/public` folder. Never publish the repository root or the `.env` file. Do not use a `public_html` copy of the entire backend.
- Single MySQL 8 instance with a distinct, restricted production database login. Do not import development applicant records or test scans/identity documents.

## Hosting prerequisites — request in writing before purchase
- PHP 8.3+ runtime, `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `curl`, `dom`, `xml`, `ctype`, `tokenizer`, `zip` and Composer 2.
- SSH/Terminal and support for `php artisan` and Composer, custom web document-root, Git deploy/Composer dependencies.
- PHP memory limit appropriate for Laravel (at least 256MB is a reasonable initial target), upload limits >= 5MB plus overhead, TLS + force HTTPS, predictable CPU/RAM/IO quotas.
- Scheduled tasks (cron), queues (persistent if supported; if not, time-limited workers), secure file ownership and durable private storage, outbound HTTPS to Paystack if enabled later.
- **Off-server, tested backups** of production DB + private uploaded files, retention policy, recovery objective and domain email/DNS preservation.
- Shared hosting may not support the full platform reliably; upgrade to VPS or managed Laravel hosting if any essential prerequisite is absent.

## Environment: store on server only, never in GitHub
`APP_ENV=production`
`APP_DEBUG=false`
`APP_URL=https://stcollegeofhealthsciences.edu.ng`
`SESSION_SECURE_COOKIE=true`
`STSAMS_PUBLIC_ADMISSIONS_ENABLED=false`
`STSAMS_PAYMENTS_ENABLED=false`
`STSAMS_DOCUMENT_SCAN_MODE=clamav` (only after a real scanner is correctly installed)
Configure APP_KEY, separate production MySQL secrets, SMTP and persistent storage. Use `php artisan config:cache` only after setting environment values.

## First publication path
1. Preserve QServers domain, MX, SPF, DKIM and DMARC records; do not break the college's email.
2. Deploy approved **release branch** from GitHub into a staging directory. No automatic migrations on raw pushes.
3. Configure app environment, check `php -v`, `php -m`, `composer --version`, MySQL access and document root.
4. Run `composer install --no-dev --optimize-autoloader`; use stable PHP extensions matching composer.lock.
5. Confirm a recent off-server DB backup, then run `php artisan migrate --force` against **production database** only.
6. Ensure writable `storage/` and `bootstrap/cache`; only public institutional images use `storage:link`, never applicant documents.
7. Run `php artisan optimize`, check `/up` if configured, homepage, college contact info, favicon, `/admissions`, website sign-in and authorization. Ensure admissions sign-up and applicant writes are still blocked.
8. Test TLS, redirects, headers, brute-force prevention, contact form rate limit, storage isolation, error reporting and backup restoration before pointing domain traffic.
9. Schedule `php artisan schedule:run` every minute only if hosting supports it; run queues with supported supervisor/cron strategy. Monitor errors and capacity.
10. Document host details and rollout/rollback plan in a **private operations file** (do not store credentials or personal details in public GitHub repository).

## Rollback / release controls
Keep release history, DB backup, and previous deployment directory. Use maintenance mode during coordinated deployments. Database migrations may be irreversible: always back up before migration. A code rollback alone is not sufficient for an incompatible schema change. Require explicit authorized approval before enabling live admissions or live payments.

## Domain decision
Keep `/admissions` canonical because the existing STSAMS system runs as one Laravel application. A subdomain redirect can be created later without purchasing multi-site hosting; independent apps need independent resource planning.
