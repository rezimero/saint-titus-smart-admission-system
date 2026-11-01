# STSAMS Staff Profile — Handover

## Endpoints

- `GET /account/profile` — staff/superadmin profile
- `PATCH /account/profile` — name, phone, designation
- `PUT /account/password` — rotate password, force flag cleared

Signed-in identity + My Profile link is also shown in the admission header; website editor access is available from the institutional header when authenticated.

## Compatibility

Laravel 13, PHP 8.5, MySQL, Spatie Permission 8, Sanctum 4. Existing applicant login and applications remain unchanged. No new package dependencies.

## Release checklist

1. Preview installer, check local source anchors.
2. Install with backup external to repo.
3. Run verification scripts (syntax + routes + view compilation + `git diff --check`).
4. Confirm active database `saint_titus_stsams` before `php artisan migrate`.
5. Test a newly created staff account: temporary-password redirect, My Profile update, correct password change, new login, and refusal of old password.
6. Verify superadmin retains staff-management rights. Verify applicant cannot access staff profile.
7. Inspect staged changes for `.env`, credentials, backups; commit and push with an accurate release message.

Do not use `migrate:fresh`, `db:wipe`, or destructive tests against the application database. Feature tests with RefreshDatabase must target `stsams_test` only.
