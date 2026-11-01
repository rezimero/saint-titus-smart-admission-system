# ADR-006 — First-Time Admission Fee and Financial Integrity

**Decision:** Approved, 2026-10-31. **Affected module:** Admissions Catalogue / Paystack.

### Context
The previous rule refused all fee configuration once the application table contained a row. Sessions created before payment functionality may have `application_fee_kobo = NULL` and one or more draft applications. The administrator cannot configure the first approved fee even though no application has been submitted or paid.

### Decision
- Fee `NULL`: first configuration may proceed if every associated application is still a draft AND none has any payment records. Record the approved fee in integer kobo; `0` is an explicit waiver.
- Fee not `NULL`: any different fee may be configured only while there are **zero** associated applications. An identical fee is idempotent.
- Non-draft applications or any payment records: initial fee setup is blocked for financial review. Never discard payment records or rewrite already established fees to bypass safeguards.
- A MySQL `FOR UPDATE` transaction lock on the admission-session row serializes concurrent administrative fee changes. Application creation and payment service remain responsible for their own rules.
- No migration or data rewrite. Authorization via `settings.manage` remains mandatory.

### Follow-up
Before public production launch: implement immutable financial audit events for fee approvals/changes, separate session-level fee schedules or price versions if genuinely required, more complete gateway payment reconciliation, and enforce approval sign-off by the finance office.
