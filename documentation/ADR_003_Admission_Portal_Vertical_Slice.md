# ADR-003: Admission Portal Vertical Slice — approved for local integration

## Context
STSAMS Phase 1 identity architecture is frozen: one users table, Sanctum API auth, Spatie authorization, applicant/staff profiles. The existing application is Laravel 13/MySQL. Earlier work stalled on test environment configuration instead of delivering usable admission functionality.

## Decision
- Add a server-rendered, session-authenticated **web portal** at `/admissions` without changing `/api/v1` or the existing Sanctum authentication scheme.
- Keep the canonical `users` table and reuse the existing `RegistrationService` so public registration only grants the applicant role.
- Use Spatie policy-based access controls with an owner-isolated applicant workspace and role-limited staff queue.
- Model admission programmes, sessions, applications, private file attachments and status events in dedicated `stsams_*` tables, avoiding collision with existing profile tables.
- Require applicant submission to happen only during an open session. Use a database transaction and row lock for state transitions.
- Application state machine: `draft -> submitted -> under_review -> approved|rejected`. Decisions and actor notes are persisted as events.
- Store files on Laravel's non-public `local` disk and expose downloads through ownership-checked routes, not a public symlink.
- Keep the MySQL test database separate from the application's actual MySQL database.

## Scope limitations and release gate
This is a working portal **foundation**, NOT a completed or certified production deployment. Before public launch: confirm programme data; define school-specific document requirements and eligibility; implement email verification, password reset, staff MFA, retention/privacy policies (including NDPA compliance review), malware scanning/storage hardening, comprehensive audits and monitoring, backups/restore drills, accessibility and load testing, and deploy behind HTTPS with an operations plan. All local code must be reviewed and integrated/tested against the actual Windows/Laravel instance.

## Consequences
- Applicant portals and staff workflows are visible immediately after migrations and explicit roles/admin bootstrap.
- Role names stay compatible with existing permission seeder.
- Programme catalogue must be created by an authorized administrator, rather than shipping fabricated school data.
- Existing applicant_profiles are not silently populated because legacy application_number semantics need domain clarification. Applications themselves get unique references on submission.
