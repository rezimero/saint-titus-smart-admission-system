# STSAMS implementation log — Supporting documents and Paystack
Date: 2026-10-31
Baseline commit: 8dd991f

Implemented in installable patch:
- Required, optional, conditional and programme-controlled document slots.
- Passport photograph image-only validation, PDF/image credentials, 5MB upload limit.
- Document preview, replacement and deletion while application is draft.
- Private document storage, authorization and optional ClamAV scanning.
- Submission checks for each required document and confirmed application payment.
- Administrators can configure approved admission fees and document requirements.
- Paystack server initialization, verification callback, signed webhook, and payment receipt.
- Electronically generated application acknowledgement following submission.
- Feature tests (use dedicated MySQL test schema; never run against production database).

Local deployment state: NOT YET VERIFIED. Migration MUST be run and the payment secret supplied securely before payments can operate.
