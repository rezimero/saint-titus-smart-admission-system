# GitHub checkpoint — operations and public website management

The last confirmed remote commit before these local batches was **8dd991f**. No tool in this package pushes your local work automatically.

## Preconditions
- Install successfully and migrate MySQL after backing up.
- RolePermissionSeeder runs and `permission:cache-reset` succeeds.
- Superadmin can assign cases, see audit, review-only staff cannot offer admission, and a different authorized officer can offer only after document verification.
- Editor can save unpublished content; publisher can approve it; the public website remains available with admissions and payments suspended in production.
- The isolated MySQL `stsams_test` verification script passes or its failures are documented and resolved.

## Stage only the known implementation areas
From repository root, PowerShell:
```powershell
git status --short
git add -A -- Setup Verification backend/app backend/bootstrap/providers.php backend/config backend/database/migrations backend/database/seeders backend/public/stsams backend/resources/views backend/routes backend/tests/Feature documentation

git diff --cached --check
git --no-pager diff --cached --stat
git diff --cached --name-only | Select-String -Pattern '(^|/)\.env($|\.)|\.bak$|\.sql$|\.pem$|\.key$|/storage/|\.log$'
```
The last command should emit no filenames. Review the staged diff before committing. Do not stage environment secrets, uploads, or private data.

```powershell
git commit -m "feat: add audited admissions operations and governed website publishing"
git push origin master
git status
git log -1 --oneline
```
Only claim the checkpoint is on GitHub if the push finishes successfully and `git status` confirms synchronization.

## Status recording
Update the implementation log with the actual PHP, MySQL, browser, permissions and QServers readiness verification results. A Git commit is a development milestone, not a public-launch security certification.
