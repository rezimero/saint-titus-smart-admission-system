# ADR 008 — Staff self-service and temporary credential lifecycle

**Status:** Implemented in package; activation and end-to-end verification pending on the user-hosted Laravel environment.

## Context

The delegated administration module creates staff accounts with temporary passwords, but staff lack a self-service profile and password-change mechanism. Navigation also fails to identify the signed-in administrator.

## Decisions

- Keep the single `users` authentication table, with 1:1 `staff_profiles`; do not create a separate staff authentication store.
- Provide `/account/profile` to all staff/superadmin roles, and block applicants from staff-only profile endpoints.
- Show the signed-in name, role and profile link in the admission portal header; offer a profile link to institutional website editors as well.
- Permit a staff user to change their own display name, phone and job title; email, role, and permissions remain exclusively centrally administered.
- Require password rotation for staff created through the superadmin staff-management service, plus already-existing non-superadmin staff via a one-time migration.
- Enforce the requirement across all session-authenticated web routes using a web-group middleware; permit profile management and sign-out until completion.
- Require verified current password, confirmed strong new password, session rotation, invalidation of other sessions using Laravel AuthenticateSession, and revocation of Sanctum personal access tokens.
- Existing superadmin accounts are not forcibly locked by the migration; superadmins may change passwords voluntarily. Applicants remain unaffected.

## Security boundaries

This does not provide MFA, password-reset email flow, or staff identity verification. These remain prerequisites for public production administrative access. Session invalidation requires consistent use of Laravel's `AuthenticateSession` web middleware.
