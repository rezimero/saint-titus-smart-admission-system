# STSAMS operations & publishing v1 — Handover

## Pre-requisites
Install and migrate preceding Document/Paystack, Central Delegated Administration and Staff Profile packages first. The user reports these are installed; verify migration status before adding the new migration.

## Exact sequence
1. Extract package in a folder outside repo.
2. `& "$package\Setup\Install-STSAMS-Operations.ps1" -Repository $repo -Preview`
3. Inspect report, then call same command without `-Preview`.
4. `& "$repo\Verification\Verify-STSAMS-Operations.ps1" -Repository $repo`
5. Inspect `config('database.default')` and MySQL DB name. Migrate main DB only after backup review.
6. `php artisan migrate` then `php artisan db:seed --class=RolePermissionSeeder` and `php artisan permission:cache-reset`.
7. Test in browser with superadmin and two non-superadmin staff users. Claim, reassign, review credentials, reject, offer, and audit with dedicated fake records.
8. Test website_editor save draft, verify public page does not change, grant `website.publish` to authorized editor, then approve content and check audit.
9. Configure launch gates for public site. By default production applicant creation and Paystack checkout are disabled.
10. Review `git status`, documentation, sanitized staged files, and create one integration checkpoint only after acceptance.

## Invariants
- Never use `migrate:fresh`, `db:wipe` or RefreshDatabase tests against `saint_titus_stsams`.
- Existing applicant documents with `verification_status=pending` require individual review.
- A verification decision records human document review, **not** malware scanning.
- Application offers are admission offers, not enrolment.
- Keep payments suspended until Paystack has passed an independent test-mode transaction and webhook verification.
- An activated route or syntax check is **not** production acceptance.

## Acceptance matrix
| Scenario | Expected |
|---|---|
| Regular applicant requests staff page | 403 |
| Review-only admin attempts offer | 403 |
| Authorized reviewer claims unassigned submitted case | Assignment recorded |
| Other reviewer tries to start assigned case | Denied |
| Authorized officer issues offer with pending document | Rejected |
| Reviewer tries to offer on own case | Rejected unless superadmin |
| Website editor saves section | Draft only, public site unchanged |
| Website editor publishes without grant | 403 |
| Superadmin publishes draft | Public site updated, audited |
| Production registration when admissions disabled | 503 |
| Production Paystack checkout when payments disabled | 503 |
