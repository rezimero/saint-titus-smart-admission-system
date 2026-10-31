# STSAMS Handover — Shared Institutional Branding v1

**Scope:** Integrate the institutional identity into the admission portal without changing admissions business logic.

## Implementation

- The institutional website and admissions portal now render the same reusable `institutional.partials.brand` Blade component.
- The component uses the existing uploaded logo at `/institutional/saint-titus-official-logo.jpg` and the full institutional designation.
- The entire logo/name link resolves to the named `institution.home` route (`/`).
- The admissions page additionally shows the institutional contact strip and uses the same favicon assets as the public website.
- Applicant sign-in, registration, dashboard, setup, and sign-out links remain admissions-specific.
- Admission service logic, authentication, MySQL configuration, and database schema are unchanged.

## Browser acceptance

1. Open `/` and `/admissions` side by side: confirm the same logo, institutional name and header treatment.
2. From `/admissions`, `/admissions/login`, and `/admissions/register`, click the logo/name; it should navigate to `/`.
3. Check the site favicon and admissions favicon match.
4. Return to the admissions portal using Apply Now on the website.
5. Check narrow/mobile widths and keyboard navigation; no overlap or lost actions.
6. Verify applicant session, dashboard and sign-out remain functional.

## Safety and Git

The installer previews changes and backs up overwritten files to `C:\Projects\STSAMS_Backups\shared_brand_<timestamp>` outside the Git repository. No migration or seeding is needed. Run `Verification/Verify-STSAMS-Shared-Brand.ps1` and inspect `git diff --check` before committing. This patch should be staged alongside existing uncommitted STSAMS work only after reviewing those files for secrets and generated backup artifacts.

**Status:** Packaged and source-checked. Browser acceptance and local Laravel verification are pending until run on the user's Windows environment. Do not mark production approved based on this package alone.
