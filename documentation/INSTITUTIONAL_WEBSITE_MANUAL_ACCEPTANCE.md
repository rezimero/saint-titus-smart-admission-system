# STSAMS institutional website — manual acceptance checklist

Before browser acceptance, install this release, confirm database `saint_titus_stsams`, apply the website migration, and run `php artisan serve`.

- [ ] `/` shows SAINT TITUS COLLEGE OF HEALTH SCIENCES AND TECHNOLOGY and the actual uploaded logo.
- [ ] The browser tab displays the Saint Titus favicon (clear favicon cache if necessary).
- [ ] `/about`, `/academics`, `/admissions-info`, `/campus-life`, `/news-events`, `/administration`, `/contact` work on desktop and mobile.
- [ ] Every admission CTA links to the existing `/admissions` portal and does not break login/registration.
- [ ] No fabricated accreditation, academic programme, tuition, admission fee, or officeholder claims appear.
- [ ] Contact shows confirmed address, phone 08034418715, WhatsApp 09091219057, email, JFK and 08153796480.
- [ ] Contact form rejects missing consent, rejects invalid email, and stores accepted messages for admins only.
- [ ] Unauthenticated and applicant accounts cannot read `/institution-admin` content or change publications.
- [ ] Authorized administrator can publish an admission notice; it appears on homepage without developer assistance.
- [ ] Authorized administrator can upload a verified campus image, mark it published and see it in gallery.
- [ ] Existing applicant register/login/application forms and staff review queue still work.
- [ ] Git diff is reviewed; new migration and documentation are committed; `git push origin master` succeeds.

## Isolated optional automated website checks
The provided `InstitutionalSiteTest.php` is intentionally guarded: RefreshDatabase may ONLY execute against the dedicated `stsams_test` MySQL database with environment variables `DB_DATABASE=stsams_test` and `DB_CONNECTION=mysql`. No test suite invocation is part of the installation workflow. Run only when you deliberately choose to use the separate MySQL testing database.
