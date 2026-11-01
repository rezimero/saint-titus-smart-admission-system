# STSAMS — Admission Fee Setup Fix / Implementation Log

Date: 2026-10-31
Baseline: GitHub commit `8dd991f`; documents/Paystack v1 patch locally installed (not necessarily committed).

## Root cause
`CatalogueController::setSessionFee()` checked `stsams_admission_applications` for any row, even a draft, then threw `The fee cannot be changed after applications have started.` The `NULL` initial-fee configuration was incorrectly treated as a fee change.

## Changes
- First-time fee setup accepts existing draft applications without any payment records.
- Established fees remain immutable after any application exists.
- No retroactive charges for submitted/processed applications or existing payment attempts.
- Row locking; validation errors for financial conflicts; clear admin guidance.
- Five focused feature tests included for isolated MySQL testing.

## Validation
- PHP syntax: checked on package; results must be confirmed on local PHP 8.5.5.
- Source package compatibility: installer compares exact previous sources before overwriting.
- MySQL schema/data: unchanged by installer; local fee-save acceptance pending.
- Paystack testing and GitHub checkpoint: pending local execution.
