# ADR 002 — Secure identity authorization activation

**Decision:** Adopt least-privilege role matrix, single `web` Spatie guard (compatible with the current User model and Sanctum authentication), deterministic role seeding, and opt-in first-administrator provisioning.

**Status:** Accepted in this patch; pending execution on developer workstation.

## Rationale
- Public registration always creates an `applicant`; a client-supplied role is ignored.
- `DatabaseSeeder` only calls the role/permission seeder; superadmin provisioning cannot occur accidentally.
- `SuperadminSeeder` reads credentials from short-lived process environment variables; no defaults, passwords, or accounts are embedded in source.
- Provisioning refuses to elevate an existing user who is not already a superadmin; repeated explicit provisioning never changes an existing superadmin's password.
- Role permissions are canonical. Re-running the seeder **synchronizes** the five managed roles to the matrix. Legacy permissions are not deleted; superadmin receives all `web` permissions, including legacy names.
- Application-specific ownership/policy checks are STILL required. `applications.view` does **not** grant access to another applicant's record without a record-level policy.

## Permission matrix

| Role | Permissions |
|---|---|
| superadmin | All `web` permissions, including legacy names |
| admin | users.view/create/update; applications.view/create/review/approve/reject; reports.view |
| admission_officer | applications.view/review; reports.view |
| staff | applications.view |
| applicant | applications.view/create |

Permissions defined: `users.view`, `users.create`, `users.update`, `users.delete`, `applications.view`, `applications.create`, `applications.review`, `applications.approve`, `applications.reject`, `roles.manage`, `settings.manage`, `reports.view`.

## Known follow-ups
- Add record-level applicant authorization before introducing application data endpoints.
- Add HTTP rate limiting and user-status controls before public production deployment.
- Complete login/logout token lifecycle acceptance tests and audit logging.
- Review legacy snake_case permissions before future permissions cleanup.
