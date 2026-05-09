# SVCI Policy-to-System Matrix

> Source of truth mapping every record type from [`SVCI_School_Records_Policy.md`](SVCI_School_Records_Policy.md)
> to concrete system rules: requirements, offices, fees, SLA, and release flow.
>
> This document drives seed data, the `RequestRulesEngine`, clearance routing, and the
> student request wizard. If policy changes, update this matrix first, then the
> `config/policy.php` file and `DocumentTypeSeeder`.

## 1. Document Type Catalog

Record types sourced from policy sections 1, 3–11, 13, and 17.

| Code | Name | Policy § | Category | Fee (PHP) | SLA (work days) | Submission Window | Release Window |
|------|------|----------|----------|-----------|------------------|-------------------|----------------|
| `tor` | Transcript of Records | §1.1, §13.1, §17 | Academic | `140.00 / page` | 14 | Window 5 | Window 9 |
| `tor_transfer` | Transcript of Records (valid for Transfer) | §3, §13.1, §17 | Academic | `140.00 / page` | 14 (after receiving Honorable Dismissal lower portion) | Window 5 | Window 9 |
| `diploma` | Diploma | §1.2, §13.1, §17 | Academic | `15.00 / 5 copies` (authentication) | 5 | Window 5 | Window 9 |
| `diploma_reissue_college` | Diploma Re-issuance (College/Masteral/Doctoral) | §7.2.1, §17 | Academic | `310.00` | 5 | Window 5 | Window 9 |
| `diploma_reissue_basic` | Diploma Re-issuance (Kinder/Elem/JHS/SHS) | §7.2.2, §17 | Academic | `310.00` | 5 | OP (Annex) | OP (Annex) |
| `cert_transfer_credential` | Certificate of Transfer Credential (Honorable Dismissal) | §1.3.1, §17 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_graduation` | Certificate of Graduation | §1.3.2 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_enrollment` | Certificate of Enrollment | §1.3.3 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_units_earned` | Certificate of Units Earned | §1.3.4 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_gwa` | Certificate of GWA | §1.3.5 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_car` | Certificate of Complete Academic Requirements (CAR, graduate only) | §1.3.6 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_medium` | Certificate of Medium of Instruction | §1.3.7 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_grades` | Certificate of Grades | §1.3.8 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_serial` | Certificate of Serial Number (NSTP graduates) | §1.3.9 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_special_class` | Certificate of Special Class | §1.3.10, §12 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_permit_cross_enroll` | Certificate of Permit to Cross-Enroll | §1.3.11 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_no_objection` | Certificate of No Objection (CNO) | §1.3.12, §16 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_no_scholarship` | Certificate of No Scholarship | §1.3.13 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `cert_appearance` | Certificate of Appearance | §1.3.14 | Certification | `100.00` | 3 | Window 5 | Window 9 |
| `special_order` | Special Order | §1.4, §17 | Academic | `100.00` (re-issuance) | 5 | Window 5 | Window 9 |
| `form_137a_personal` | Form 137-A (Personal Copy) | §4.1, §17 | BasicEd | `130.00` | 5 | Window 6 | Window 6 |
| `form_137a_transfer` | Form 137-A (Valid for Transfer) | §4.2 | BasicEd | `130.00` | 5 | Window 6 | Window 6 |
| `cert_basic_ed` | Certification (Basic Education Curriculum) | §1.6, §5 | BasicEd | `100.00` | 3 | OP (Annex) | OP (Annex) |
| `form_138` | Form 138 (Report Card) BasicEd | §1.7, §13.1 | BasicEd | `100.00` | 5 | OP (Annex) | OP (Annex) |
| `form_138_reissue` | Form 138 Re-issuance | §6, §17 | BasicEd | `100.00` (penalty) | 5 | OP (Annex) | OP (Annex) |
| `enrollment_survey` | Enrollment Population Survey | §1.8, §10, §13.1 | Special | `0.00` (policy does not list fee) | 2 | Registrar face-to-face | Registrar face-to-face |
| `records_verification` | Request for Student Records Verification | §1.9, §11 | Special | `100.00` (certification) | 3 | Registrar or `registrarsoffice@gmail.com` | Personal or email |
| `cav` | CAV (Certification, Authentication, Verification) | §8, §17 | Special | `140.00` (w/ doc stamp) per certification | 3 | Window 1 | Window 1 |
| `authentication` | Authentication of Records | §9, §17 | Special | `15.00` per set (TOR) / `15.00 / 5` diploma-SO | 2 | Window 4 | Window 4 |
| `statement_of_account` | Statement of Account | §17 | Special | `65.00` | 3 | Window 5 | Window 5 |
| `grades_printout` | Grades Print-Out | §17 | Special | `15.00 / page` | 3 | Window 5 | Window 5 |

> Fee notes: the system stores a canonical fee and an optional `fee_formula` key
> (`per_page`, `per_set`, `per_5_copies`, `flat`) so the wizard can compute
> totals when pricing is not flat.

## 2. Clearance Routing per Record Type

Offices per policy § 2, 3, 4, 5, 7, 8, and 15. Office codes map to `role` or to
a new `offices` reference table:

- `president` — Office of the School President
- `dean` — Office of the College Dean
- `alumni` — Alumni Office
- `guidance` — Guidance Office
- `sao` — Student Affairs Office
- `library` — Library
- `registrar` — Office of the Registrar
- `accounting` — Accounting / Cashier

| Record Type(s) | Required Clearance Offices |
|----------------|-----------------------------|
| `tor`, `tor_transfer`, `diploma`, `diploma_reissue_college`, `cert_*` (college-level), `special_order`, **all College / Graduate records**, **deceased records (§15)** | `president`, `dean`, `alumni`, `guidance`, `sao`, `library`, `registrar`, `accounting` |
| `form_137a_personal`, `form_137a_transfer` | `registrar`, `accounting` |
| `cert_basic_ed`, `form_138`, `form_138_reissue`, `diploma_reissue_basic` | `registrar`, `accounting` (Principal/BasicEd releases) |
| `cav` | `registrar`, `accounting` |
| `authentication`, `statement_of_account`, `grades_printout` | `registrar`, `accounting` |
| `enrollment_survey`, `records_verification` | Manual registrar handling (no student clearance) |

## 3. Attachment / Requirement Rules

Mandatory uploads are policy-driven.

| Requirement Key | Applies To | Required | Rule |
|-----------------|-----------|----------|------|
| `affidavit_of_loss` | `diploma_reissue_*`, `form_138_reissue` | Yes | Notarized affidavit (§6, §7) |
| `official_request_letter` | `form_137a_transfer` | Yes | Bears original School Principal/Registrar signature (§4.2) |
| `authenticated_tor` | `cav` | Yes (graduates and non-graduates) | §8.1 |
| `authenticated_diploma` | `cav` | Yes (graduates only) | §8.1.1 |
| `authenticated_special_order` | `cav` | Yes (graduates only) | §8.1.1 |
| `authenticated_cert_units_earned` | `cav` | Yes (non-graduates) | §8.1.2 |
| `photocopy_records` | `authentication` | Yes | §9.1 |
| `request_letter` | `records_verification` | Yes | Full §11.1 contents |
| `authorization_letter` | `records_verification`, release by proxy | Yes | §11.1.5, §14.4 |
| `spa` | Release by proxy (owner in PH) | Yes | §14.4.1 |
| `valid_id_photocopy_owner` | All releases if not the owner | Yes | §14.4.3 |
| `valid_id_photocopy_claimant` | All in-person releases | Yes | §14.3 |
| `death_certificate` | Deceased record requisition | Yes | §15.1 |
| `notarized_affidavit_deceased` | Deceased record requisition | Yes | §15.1 |
| `certificate_of_no_objection` | Transferred/dismissed exception requests | Yes | §16.7–16.9 |
| `official_external_notice` | Transferred/dismissed exception requests | Yes | §16.6.2 |

## 4. Special Workflow Flags

- **Transfer Credentials (`tor_transfer`):** `requires_hd_return = true`. TOR clock
  (14 days) starts only after the Office of the Registrar receives the lower
  portion of the Honorable Dismissal from the new school. Expose this in
  the student timeline as a "Waiting on receiving school" state (§3.2).
- **Transferred/Dismissed Students (§16):** block ALL record release by default.
  Allowed ONLY if `certificate_of_no_objection` + `official_external_notice`
  are submitted and an admin flips the `transfer_exception_approved` flag.
- **Records Verification (§11):** email-based intake; no student clearance; the
  system still creates a `document_requests` row flagged as `mode = email` so
  admins can track and respond via `registrarsoffice@gmail.com`.
- **Enrollment Survey (§10):** face-to-face only; returns total population only;
  never expose PII. The system generates a manual ticket with policy reminder.
- **CAV (§8):** fee paid first, then documents submitted at Window 1; release is
  at Window 1, not Window 9. Admin also must track subsequent CHED submission.
- **Authentication (§9):** fee paid at Window 4 for dry seal; clearance does not
  apply; tracked as a lightweight job.
- **Certificate of Special Class (§12):** eligibility is policy-gated. The
  wizard enforces `eligibility_checklist` (graduating this term, deficiency
  certified, subject conflict) before accepting the request.

## 5. Release Rules

Applies at the `release` stage of every request:

1. Requests can only be released on the date stamped on the claim slip (§14.1).
2. The claimant must present the claim slip and **one valid ID photocopy** (§14.2–3).
3. If the claimant is not the owner:
   - Owner inside PH → SPA (§14.4.1)
   - Owner outside PH → Authorization letter (§14.4.2)
   - Both owner and claimant valid IDs (§14.4.3–4)
4. Release channel is set by the document type's `release_channel`:
   - `registrar_window_9`, `registrar_window_6`, `registrar_window_5`,
     `registrar_window_4`, `registrar_window_1`, `annex_principal`, `email`.

## 6. SLA Engine Rules (§13)

- `sla_days` comes from Section 13.1:
  - Transcript of Records: 14
  - Diploma and Special Order: 5
  - Certifications: 3
  - Form 137-A and Form 138: 5
  - Survey of Data: 2
- Business days only — holidays and weekends excluded (§13.2).
- `sla_start_at` is set the moment the request becomes `approved` (post-payment
  approval), not at submission.
- Admin can mark `sla_paused_at` with a reason (enrollment period, graduation
  week, board exams) per §13.2 notes. Elapsed time resumes on `sla_resumed_at`.
- The student dashboard and admin queues render `expected_release_on` as
  `sla_start_at + sla_days (working)`.

## 7. Claim Slip Lifecycle

States per §13–14 and §2.1 steps 8–9:

1. `pending` — not yet generated (records still processing)
2. `ready` — record ready, claim date published
3. `released` — presented and record handed over
4. `expired` — student did not claim within grace period (operational policy)
5. `void` — cancelled (e.g., exception revoked under §16)

Each claim slip stores: `claim_number`, `claim_date`, `release_channel`,
`released_by`, `released_at`, `claimant_name`, `id_reference`, `authorized_by`,
and `notes`.

## 8. Matrix → Config Mapping

- `config/policy.php` exposes an array for each `code`:
  ```php
  'tor' => [
      'name' => 'Transcript of Records',
      'category' => 'Academic',
      'fee' => 140.00,
      'fee_formula' => 'per_page',
      'sla_days' => 14,
      'submission_window' => 'registrar_window_5',
      'release_channel' => 'registrar_window_9',
      'offices' => ['president', 'dean', 'alumni', 'guidance', 'sao', 'library', 'registrar', 'accounting'],
      'requirements' => [],
      'flags' => [],
  ],
  ```
- `DocumentTypeSeeder` reads this config to build the canonical catalog.
- `RequestRulesEngine::rulesFor(DocumentType $type)` returns the same shape at
  runtime so the wizard, controller, and tests stay in sync.

## 9. Change-Management

- Policy updates land in [`SVCI_School_Records_Policy.md`](SVCI_School_Records_Policy.md).
- Update this matrix, then `config/policy.php`, then re-run
  `php artisan migrate:fresh --seed` (dev) or write an additive migration +
  seeder (prod).
- Tests under `tests/Feature/Policy/*` assert every table row in §1 is covered
  in code.
