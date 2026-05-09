# 18. UAT Script – Policy-Mapped Acceptance Tests

This script is for registrar / SAO / accounting / IT staff to validate the
SVCI Online Document System end-to-end against
[`SVCI_School_Records_Policy.md`](./SVCI_School_Records_Policy.md) before
production cutover.

Every step cites the policy section it validates and, where available, the
**automated test** that also exercises the same rule. The automated coverage
lives in `tests/Unit/Policy/` and `tests/Feature/{Student,Admin,Department}`.

Legend:
- [A] = Automated test exists (see mapping below)
- [M] = Manual-only UAT step
- §X.Y = Registrar policy section

---

## 0. Prerequisites

1. Fresh install with `php artisan migrate:fresh --seed`.
2. Test accounts (seeded):
   - Student: `student@example.com` / `password`
   - Admin (Registrar): `admin@example.com` / `password`
   - Dean, Accounting, SAO, Teacher: `dean@example.com`, `accounting@example.com`, `sao@example.com`, `teacher@example.com` (all `password`)
   - Superadmin: from `.env` (`SUPERADMIN_EMAIL` / `SUPERADMIN_PASSWORD`)
3. Queue + broadcast workers running (`composer dev`).

---

## 1. Student Onboarding & Profile

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 1.1 | Register, receive verification email, verify, login | §19 privacy | Verified + active | [M] |
| 1.2 | Complete profile (course, year, ID) | §1 | Required for requesting | [M] |

---

## 2. Student Request Submission (Wizard) — Policy-Initial Flow

> **Policy-initial flow:** Admin approves the request *first*; payment unlocks only after approval.
> 1. Submit multi-doc request → 2. Admin reviews and approves → 3. Student sees payment details → 4. Student pays and uploads receipt → 5. Admin verifies payment → 6. Processing continues.

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 2.0 | Submit request **without Purpose** | Policy-Initial plan | Blocked – "purpose is required" | [A] `WizardPolicyTest::test_purpose_is_required_for_wizard_submission` |
| 2.0b | Submit request with **no items selected** | Policy-Initial plan | Blocked – "items field is required" | [A] `WizardPolicyTest::test_items_are_required_for_wizard_submission` |
| 2.1 | Select **TOR** (3 pages, admin-set) and **Diploma (2 copies)** in one request | §3, §4, §13.1 | Both appear in the cart; line totals and grand total computed in real time | [A] `WizardPolicyTest` |
| 2.2 | Request **TOR** with per-page fee | §3, §13.1 | `line_total = fee_per_page × page_count × copies`; `fee_snapshot = sum of line totals` | [A] `WizardPolicyTest::test_wizard_computes_per_page_fee_for_tor` |
| 2.3 | Add more copies of a document via the stepper | §13.1 | Copies capped at 20; total updates live | [M] |
| 2.4 | Request **TOR (for Transfer)** as transferred student **without CNO** | §16 | Blocked with policy error | [A] `test_wizard_blocks_transferred_student_without_cno` |
| 2.5 | Request same with CNO + external notice uploaded | §16 | Allowed and tagged `transfer_exception_requested` | [A] `test_wizard_allows_transferred_student_with_cno_and_notice` |
| 2.6 | Request **Certificate of Special Class** without ticking eligibility | §12.1 | Blocked | [A] `test_special_class_without_checklist_is_blocked` |
| 2.7 | Request Cert of Special Class with "graduating" ticked | §12.1 | Allowed | [A] `test_special_class_with_checklist_is_allowed` |
| 2.8 | Request **Enrollment Survey** online | §10 | Blocked with "must be filed in person" message | [A] `test_enrollment_survey_is_face_to_face_only` |
| 2.9 | Request **Diploma Re-issuance** | §4 | Requirements seeded: Affidavit of Loss + Valid ID | [A] `test_wizard_seeds_policy_requirements` |
| 2.10 | Request **CAV** (single) | §7 | Requires authenticated TOR attachment | [M] |
| 2.11 | Request **Records Verification** | §11 | Channel = email; requires request + authorization letters | [M] |
| 2.12 | After submission, payment card shows `pending` and no bank details yet | Policy-Initial plan | Bank details and upload button hidden until request is approved | [M] |

---

## 3. Admin (Registrar) – Request Triage

> **Policy-initial:** Admin reviews and approves the request *before* the student can pay.
> No payment gate is enforced on the admin side — the payment step opens for the student only after the request is approved.

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 3.1 | Review a pending request; see itemized breakdown (doc, pages, per-page fee, copies, line total) | §13.2 | Table shows each document item with computed line total and grand total | [A] `RequestManagementTest::test_admin_can_view_request_details` |
| 3.2 | Validate requirement attachment | §13.2 | Status flips to `validated` | [A] `PolicyLifecycleTest::test_admin_can_validate_requirement` |
| 3.3 | Reject requirement with notes | §13.2 | Status = `rejected`, notes stored | [A] `test_admin_can_reject_requirement_with_notes` |
| 3.4 | **Approve** a regular request directly (no payment needed first) | §13.1 | `status = approved`; `sla_start_at` set; student notified; payment step unlocks on student side | [A] `PolicyLifecycleTest::test_approve_non_hd_request_starts_sla_clock` |
| 3.5 | **Approve** a TOR-for-Transfer request | §3.2 | SLA clock **does not** start until HD returns | [A] `test_approve_hd_required_request_defers_sla_clock` |
| 3.6 | **Deny** request with reason | §13 | Request = `denied`, reason stored, student notified | [A] `RequestManagementTest::test_admin_can_deny_request_with_reason` |

---

## 4. Student Payment – Upload Receipt After Approval

> After the admin approves the request, the **Payments** page unlocks school bank/QR details and the receipt upload form.

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 4.0 | Visit **Payments** page *before* request is approved | Policy-Initial plan | Upload button is locked; message says "Your request is awaiting admin approval" | [A] `PaymentUploadTest::test_student_cannot_upload_receipt_while_request_is_pending` |
| 4.1 | Visit **Payments** page *after* request is approved | Policy-Initial plan | School bank details (name, account number, QR) and itemized totals visible; upload form enabled | [M] |
| 4.2 | View **Request detail page** after approval | Policy-Initial plan | Itemized breakdown table shows each document item with page count, copies, and line total | [M] |
| 4.3 | Upload payment receipt with method & reference | §13.1 | Payment becomes `pending_approval`; admin is notified | [A] `PaymentUploadTest::test_student_can_upload_payment_receipt` |
| 4.4 | Upload Affidavit of Loss for Diploma Re-issue | §4 | Requirement status → `submitted` | [M] |
| 4.5 | Cancel request **while pending** (no payment yet) | §13 | Allowed | [A] `RequestSubmissionTest::test_student_can_cancel_pending_request_without_receipt` |
| 4.6 | Attempt cancel after receipt uploaded | §13 | Blocked | [A] `test_student_cannot_cancel_when_receipt_exists` |

---

## 5. Admin – School Payment Profile Settings

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 5.1 | Visit **Admin → Payment Settings** | Policy-Initial plan | Form shows current bank name, account name, account number, QR, instructions | [M] |
| 5.2 | Update account details and save | Policy-Initial plan | Changes reflect on student payment page immediately | [M] |
| 5.3 | Upload QR code image | Policy-Initial plan | QR image appears in the current profile preview; displayed to students after approval | [M] |
| 5.4 | Remove QR code | Policy-Initial plan | QR removed; students see "QR not yet configured" placeholder | [M] |

---

## 6. Admin – Payment Verification Queue

> Admin reviews uploaded payment receipts *after* the student has uploaded them.

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 6.1 | Navigate to **Admin → Payments** | Policy-Initial plan | Uploaded receipts appear with status `pending_approval`; itemized breakdown visible per payment | [M] |
| 6.2 | Approve a payment receipt | §13.1 | Status → `approved`; clearance records created for clearance-required doc types | [M] |
| 6.3 | Deny a payment receipt with reason | §13.1 | Status → `denied`; student notified and prompted to re-upload | [M] |

---

## 7. Admin – Document Type Management (Page Counts)

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 7.1 | Create document type with `fee_formula = per_page` and `default_page_count = 5` | Admin plan | Saved; student wizard shows page count in cart | [A] `AdminContentManagementTest::test_admin_can_crud_document_types` |
| 7.2 | Update `default_page_count` on an existing type | Admin plan | Reflected in new requests; existing requests retain snapshotted value | [M] |
| 7.3 | Set `default_page_count = 0` | Admin plan | Validation error – minimum is 1 | [M] |

---

## 8. SLA Management

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 8.1 | Pause SLA with `enrollment_period` | §13.2 | `sla_paused_at` set, reason stored | [A] `test_admin_can_pause_and_resume_sla` |
| 8.2 | Pause SLA with an unknown reason | §13.2 | Rejected by validation | [A] `test_pause_sla_with_invalid_reason_is_rejected` |
| 8.3 | Resume SLA | §13.2 | `sla_paused_at` cleared; `expected_release_on` shifted | [A] `test_admin_can_pause_and_resume_sla` |
| 8.4 | Simulate holiday-spanning due date | §13 | `SlaCalculator` skips configured holiday | [A] `SlaCalculatorTest::test_excludes_configured_holidays` |
| 8.5 | 14-day SLA spans two weekends | §13 | Correct date computation | [A] `test_expected_release_with_14_days` |

---

## 9. Honorable Dismissal Return Flow (TOR for Transfer)

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 9.1 | Approve TOR-for-Transfer – no SLA yet | §3.2 | Clock not running | [A] `test_approve_hd_required_request_defers_sla_clock` |
| 9.2 | Receive HD lower portion from receiving school; admin marks HD received | §3.2 | `hd_received_at` set; 14-day SLA starts | [A] `test_marking_hd_received_starts_clock` |

---

## 10. Department Clearance (Dean/Accounting/SAO/Teacher)

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 10.1 | Dean sees own pending queue | §1 | Only own office's pending | [A] `ClearanceWorkflowTest` |
| 10.2 | Sign clearance | §1 | Row updated + event dispatched | [A] same |
| 10.3 | Deny clearance with reason | §1 | Row denied, student notified | [A] same |

---

## 11. Ready for Pickup & Claim Slip

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 11.1 | Admin marks request `ready_for_pickup` | §15.3 | Claim slip generated with `state=ready`, release channel from policy | [A] `test_moving_to_ready_for_pickup_issues_claim_slip` |
| 11.2 | Student sees claim schedule + window on dashboard | §15.3 | Claim date + window label visible | [M] |

---

## 12. Release Window (§15)

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 12.1 | Release to owner at Window 9 with valid ID | §15.4 | Slip = `released`; request = `completed` | [A] `test_release_records_claimant_details` |
| 12.2 | Release to proxy without authorization type | §15.4 | Blocked with validation error | [A] `test_proxy_release_without_authorization_is_rejected` |
| 12.3 | Release to proxy with SPA + ID | §15.4 | Allowed, `is_proxy_release=true` stored | [A] `test_release_records_claimant_details` |
| 12.4 | Void a claim slip with reason | §15.5 | State = `void`, reason appended to notes | [A] `test_admin_can_void_claim_slip` |

---

## 13. Special Compliance Scenarios

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 13.1 | CAV for **college graduate** | §7 | Requires authenticated diploma + special order | [M] |
| 13.2 | Records verification via email channel | §11 | Marked `email_intake`; no clearance gate | [M] |
| 13.3 | Deceased record requisition | §17 | Requires death certificate + notarized affidavit | [M] |
| 13.4 | Authentication of photocopy records | §8 | Fee = ₱15 per set; 2 wd SLA; Window 4 | [M] |
| 13.5 | Statement of Account | §9 | Fee = ₱65 flat; 3 wd; accounting | [M] |
| 13.6 | Enrollment Survey – walk-in manual ticket | §10 | System refuses online submission; staff logs in person | [M] |

---

## 14. Reporting & Audit

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 14.1 | Reports page loads with counts | §13 | Totals per status, per type | [A] `AdminContentManagementTest`, `SuperAdminManagementTest::test_superadmin_can_view_reports` |
| 14.2 | Activity log captures key events | §19 | Log has approve / deny / release / void | [A] `SuperAdminManagementTest::test_superadmin_can_view_activity_logs` |

---

## 15. Security & Privacy (§19)

| # | Step | Policy | Expected | Auto |
|---|------|--------|----------|------|
| 15.1 | Unverified student cannot request | §19 | Blocked by `verified` middleware | [M] |
| 15.2 | Non-admin cannot hit admin routes | §19 | 403 | [A] `RequestManagementTest::test_non_admin_cannot_access_admin_request_routes` |
| 15.3 | Uploaded receipts not publicly enumerable | §19 | Served via signed route or private disk | [M] |
| 15.4 | Student cannot upload receipt before request is approved | Policy-Initial plan | 403 Forbidden | [A] `PaymentUploadTest::test_student_cannot_upload_receipt_while_request_is_pending`, `RequestManagementTest::test_student_cannot_upload_receipt_before_request_is_approved` |
| 15.5 | Deletion respects soft-delete + audit trail | §19 | Row soft-deleted, log entry created | [M] |

---

## 16. Automated Coverage Summary

Run locally:

```bash
php artisan test
```

Policy-specific suites:

```bash
php artisan test tests/Unit/Policy
php artisan test tests/Feature/Student/WizardPolicyTest.php
php artisan test tests/Feature/Student/PaymentUploadTest.php
php artisan test tests/Feature/Admin/PolicyLifecycleTest.php
php artisan test tests/Feature/Admin/RequestManagementTest.php
```

Expected: **all green**, 163+ tests (current baseline).

---

## 17. Known Manual Fallbacks

These steps are **intentionally manual** because policy requires in-person /
out-of-band validation:

- §10 Enrollment Survey (letter + face-to-face intake)
- §11 Records Verification (email intake + manual logging)
- §17 Deceased records (notarized affidavit + death certificate review)
- §16 Transfer exception approval (registrar judgment call)

The system captures the paperwork but does **not** auto-approve any of these;
the registrar still signs off.

---

## 18. Release Readiness Checklist

Before go-live, ensure:

- [ ] All sections 2–15 green (automated + manual).
- [ ] Section 15 (security) independently reviewed.
- [ ] Holiday calendar populated in `config/policy.php` for the current academic year.
- [ ] Release channel labels verified with the registrar.
- [ ] `default_page_count` set for all active document types in production.
- [ ] School payment profile (bank name, account number, QR) configured in Admin → Payment Settings.
- [ ] Email sending (`resend`/SMTP) tested against a real inbox.
- [ ] Backup + restore rehearsed on staging.
- [ ] Staff training completed for admin, department, and superadmin roles.

Once checked, file the report in `docs/` and tag a release.
