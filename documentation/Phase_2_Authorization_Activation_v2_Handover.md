# Phase 2 identity authorization activation — handover

## Contents
- Idempotent roles and permissions (`web` guard)
- Secure opt-in superadmin bootstrap with non-escalation semantics
- Public registration assigned to applicant role only
- Separate install, activate and isolated verification scripts
- 7 feature test methods across authorization, superadmin and HTTP boundaries

## Apply
1. Extract ZIP **outside the Git repository**.
2. Run `Setup/Install-STSAMS-Authorization.ps1` from the extracted folder, passing `-Repository`.
3. Confirm PHP syntax and identity feature tests using `Verification/STSAMS_Phase_2_Authorization_Verification.ps1` in the repository.
4. Seed roles by running `Setup/STSAMS_Phase_2_Authorization_Setup.ps1` (no superadmin creation).
5. Explicitly bootstrap superadmin with `-ProvisionSuperadmin` (interactive prompts); password never appears in terminal history.
6. Verify roles and Git changes; then commit/push. Do NOT include `.env`, credentials, or `.bak` copies.

## Local constraints
- Isolated tests require `pdo_sqlite` and uncached Laravel configuration; testing script never points at the live MySQL DB.
- Installation backs up overwritten files **outside the repository**.
- PHP/Laravel behavior has not been executed in this environment; successful compilation and tests depend on user's local application and dependencies.
- Existing prior `*.bak` files already tracked should be removed from Git index separately.

## Acceptance gates
- PHP syntax checks pass.
- Authorization seeding idempotent and least privilege assertions pass.
- Superadmin cannot be created with weak credentials or by promoting an existing applicant.
- Registration creates only applicant role; `/me` and `/logout` reject guests.
- Git changes reviewed and pushed only after successful local validation.
