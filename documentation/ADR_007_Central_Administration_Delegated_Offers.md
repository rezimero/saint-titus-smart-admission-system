# ADR-007 — Central administration and delegated admission offers

**Status**: Implemented in package; pending local installation/verification.

## Decision
Use existing single `users` table, Spatie roles/permissions (`web` guard), and an explicit `superadmin` role. A global Laravel `Gate::before` grants the superadmin authorization across modules registered in the STSAMS Laravel application. No separate authentication table.

Grant `applications.review`, `applications.offer_admission`, and `applications.reject` independently. Admin role defaults to review only. Superadmin manages staff rights via a protected web UI. Staff role/permissions mutations are audited; admission decisions are traced in `stsams_application_events` under an enforced row lock.

## Scope and limitations
- `offered` represents an admission offer, not a student enrolment or letter issuance.
- `approved` remains for historical records but no new code creates it.
- Superadmin authority does not automatically extend to external apps or host infrastructure; shared SSO/identity for future apps must be deliberately integrated.
- Role-based access is not MFA. Superadmin MFA, forced initial password reset, accounts suspension and high-risk action step-up authentication are production prerequisites.
- Existing role matrix must be reseeded after installing this patch; prior `admin` grants included approval.
