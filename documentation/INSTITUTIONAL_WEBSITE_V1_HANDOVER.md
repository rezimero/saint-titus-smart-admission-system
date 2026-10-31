# Saint Titus College — Institutional Website v1 Handover

## Institution (approved display text)
SAINT TITUS COLLEGE OF HEALTH SCIENCES AND TECHNOLOGY

KM 11 BENIN - LAGOS EXPRESSWAY, AFTER UNIBEN MAIN GATE,
BY EVIDENCE
BENIN CITY
EDO STATE

Telephone: 08034418715
WhatsApp: 09091219057
Email: info@stcollegeofhealthsciences.edu.ng
Contact person: JFK — 08153796480

## Pages
- `/` institutional home
- `/about`
- `/academics`
- `/admissions-info`
- `/campus-life`
- `/news-events`
- `/administration`
- `/contact`
- `/admissions` **existing admission portal**
- `/institution-admin` CMS for authorized admin/superadmin users, sign in first at `/admissions/login`

## Operators
Run installer with `-Preview`, apply, run the verification script, then `php artisan migrate` on the main MySQL connection after confirming `config('database.default')` and `config('database.connections.mysql.database')`. Image uploads require `php artisan storage:link` from `backend`; depending on Windows settings, creating the symbolic link may require an elevated prompt or alternative supported deployment configuration.

The website uses the admissions module for applicant journeys and programme selection. Do NOT substitute the public CMS text for the operational admissions catalogue.

## Public launch checklist
- Authenticate and test superadmin account using confidential environment-based provisioning; never use static passwords.
- Populate programme/session records in admissions portal; confirm application submit/review/download access boundaries.
- Get institution's approved rector/provost welcome message, accreditation evidence, academic programme approvals, official fee schedule, and campus photos.
- Confirm online privacy notice, cookie approach, contact message retention/erasure, image consent and consent records.
- Configure HTTPS, mail, storage backups, logging/alerting, rate limits/WAF, staff MFA and least privilege.
- Run integration and security testing against a dedicated MySQL test database, never the main live schema.
- Verify real campus Google Map pin before publishing coordinate-based directions.
- Review `git status`, `git diff --check`, commit and push only after the browser smoke checks pass.
