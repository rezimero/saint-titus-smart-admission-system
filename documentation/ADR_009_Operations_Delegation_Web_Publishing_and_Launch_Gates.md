# ADR-009: Admissions operations, dual-control offers, editorial workflow and public launch gates
Status: Accepted for local implementation; production acceptance pending
Date: 2026-11-01

## Decision
- Keep one Laravel 13/MySQL STSAMS application with centralized identities and Spatie permissions.
- Staff may claim unassigned cases; only users with `applications.assign` may reassign cases.
- An assigned reviewer records individual document-verification decisions and the original scan status is retained separately.
- An offer needs `applications.offer_admission`, a stated rationale, individually verified uploads and a prior review. Non-superadmin officers cannot issue offers on cases they themselves put under review. Superadmin can override the separation of duties through the existing Gate::before, with an audit event.
- Superadmin owns access management; grantable permissions include `applications.assign`, `website.publish`, `reports.audit`.
- Website section updates are saved as drafts until separately published; website_editor role can edit but not publish unless individually granted. Published posts and media are controlled by `website.publish`.
- All staff decisions and changes to public content create operational audit entries in MySQL; staff authorization changes retain their existing separate access audit table.
- In production, public registration and application writes default closed until enabled through `STSAMS_PUBLIC_ADMISSIONS_ENABLED=true`. Paystack initialization defaults closed through `STSAMS_PAYMENTS_ENABLED=false`. Never change financial statuses to bypass a missing gateway.

## Consequences
- Migration adds reviewer assignment, verification status and operational audit records; prior uploaded documents start as `pending` for human review.
- No extra identity table or change to shared website branding.
- Existing published website section body remains visible; future edits remain private until approved.
- Website post edits of already published material require publish authority; draft editors can prepare new posts.
- Both operation audit and decision event tables are append-only in normal application code; database superuser access remains privileged.
- Production must separately establish malware scanning, private durable document storage, backups, HTTPS, PHP 8.3+, cron/queue and risk review before live admissions.
