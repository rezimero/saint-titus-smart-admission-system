# STSAMS staff authorization handover

1. Install package from repository's exact current state; review the installer preview.
2. Verify PHP syntax, route registration and Blade compilation.
3. Confirm correct MySQL DB `saint_titus_stsams`, back up DB, migrate audit table, reseed roles.
4. Sign in as superadmin, visit `/system-admin/staff`, create **review-only admin**.
5. Sign in with new admin, confirm review queue opens but offer action is absent and forged offer POST returns 403.
6. As superadmin, explicitly grant `applications.offer_admission`; confirm grant, offer against `under_review` test application; verify status `offered` plus audit event.
7. Revoke grant; confirm no further offers possible. Test website editor role separately.
8. Restrict public access before deployment; implement superadmin MFA, password reset/temporary password rotation, and offsite backups.
9. Review Git diff for secrets and existing uncommitted changes; commit and push only after local acceptance.

**Nothing in this deliverable changes Paystack's paused state.**
