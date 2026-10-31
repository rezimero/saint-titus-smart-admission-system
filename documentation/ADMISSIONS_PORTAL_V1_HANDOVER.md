# Handover — STSAMS Admissions Portal Functional Core v1

## Starting state (from latest developer transcript)
- GitHub master at `8086fb8` before Phase 2 changes (local Phase 2 authorization changes not yet committed).
- Phase 2 secure authorization package installed; `stsams_test` MySQL schema created; prior test crashed in `SuperadminSeederTest` due inherited method-name conflict.
- Package replaces that test file with a compatible version but does not overwrite application .env or any existing migrations/identity controller.

## Installation
1. Extract ZIP. Preview, then execute `Setup/Install-STSAMS-Admission-Core.ps1` from the extracted directory.
2. Run `Verification/STSAMS_Portal_Smoke.ps1` (no database writes).
3. From `backend`: `php artisan migrate` to add the STSAMS admission tables.
4. `php artisan db:seed --class=RolePermissionSeeder` to initialize roles (if not already done). This seeder is safe and does not provision an admin.
5. For staff operations, provision a privileged account using the existing secure Phase 2 setup script `-ProvisionSuperadmin` after reviewing it. Do not use or commit fixed passwords.
6. From `backend`: `php artisan serve` and visit `http://127.0.0.1:8000/admissions`.
7. Admin: `/admissions/admin/catalogue` to create real programmes and sessions. Applicant: `/admissions/register`.

## Limits
Do not call this package fully production-ready yet. The portal is a functional vertical slice to be expanded and hardened, not a substitute for a formal acceptance cycle. See ADR-003 for release gaps.

## Git
After integration and acceptance, `git diff --check`, inspect `git status`, selectively stage new files and authorized changes, then commit/push. Do not add any `.env`, `*.bak`, uploaded documents or backup directories. Avoid `git add .` without reviewing.
