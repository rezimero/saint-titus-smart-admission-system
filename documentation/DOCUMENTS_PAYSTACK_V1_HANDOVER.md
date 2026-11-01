# STSAMS admissions documents and payments — handover

## Paths and architecture
- `config/stsams_documents.php` — official document slots and default policies.
- `config/stsams_payments.php` — Paystack environment-backed credentials.
- `DocumentRequirements` — programme override resolution and submission gates.
- `DocumentController` — upload, replace, delete, preview, download with private storage.
- `DocumentScanService` — ClamAV scanning; fail-closed production setting.
- `ApplicationPaymentService` / `PaystackGateway` — initialization and transaction verification.
- `PaymentController` — applicant checkout, callback, receipt and acknowledgement.
- `PaystackWebhookController` — HMAC-SHA512 validation and Paystack re-verification.

## Institution action items
- Set up approved programmes CHEW, ND Community Health, ND Public Health in the administrator catalogue only after official programme approval.
- Explicitly set each programme's requirements, particularly JAMB/NIN and professional documents.
- Configure approved fee for every admission session. Use 0 only for an explicitly approved fee waiver.
- Use Paystack TEST keys (`sk_test_...`) on staging, never in Git. `STSAMS_PAYSTACK_SECRET_KEY` is a backend-only secret.
- When using Paystack test checkout, the webhook URL is `https://YOUR_HOST/api/v1/payments/paystack/webhook`.
- Deploy ClamAV to production and set `STSAMS_DOCUMENT_SCAN_MODE=clamav`.

## Acceptance walkthrough
1. Configure admission session fee and programme requirements.
2. Start an applicant draft and save biodata.
3. Upload all four required files; preview, replace, and remove one; verify missing-file gate.
4. Test a conditional document requirement set to required by administrator.
5. Verify private files inaccessible to another applicant.
6. Test Paystack test transaction, return callback, receipt and idempotent webhook.
7. Confirm wrong amount, wrong email, invalid signature never mark payment paid.
8. Submit, print acknowledgement, confirm draft is locked, review in staff workspace.
9. Check documentation, new migration status, Git staging and push only after successful local checks.

## Safety
This package does not create Paystack credentials, approved tuition rates, programme accreditation, or guarantee production-readiness. Never run `migrate:fresh` or test `RefreshDatabase` against `saint_titus_stsams`.
