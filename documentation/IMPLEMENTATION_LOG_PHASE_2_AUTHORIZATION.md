# STSAMS Phase 2 Identity authorization — implementation log

**Last confirmed upstream commit before patch:** `8086fb8` on `master`.

**Verified by the user:** 7 migrations marked Ran; applicant/staff profile migrations batch 3. Four auth routes registered. Two framework example tests passed. These are **not** full identity acceptance tests.

**Latest reported working tree:** modified `backend/database/seeders/DatabaseSeeder.php`, `RolePermissionSeeder.php`, `SuperadminSeeder.php`; no activation script copied.

**Patch v2 prepared:** secure role and superadmin seeders, registration role assignment, dedicated feature tests, local PowerShell install/activate/verify scripts, ADR and handover.

**Execution state:** NOT YET APPLIED, NOT YET VERIFIED AGAINST USER'S LOCAL MYSQL/PHP/LARAVEL, NOT COMMITTED OR PUSHED. Update this log after the actual results.

**Important corrective action:** v1 package embedded a known fixed superadmin password. The v1 seeder must never be executed. This v2 removes defaults and makes bootstrap opt-in.
