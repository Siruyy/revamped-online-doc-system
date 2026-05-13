# Phase 05 — Department Clearance Closeout

> **Goal:** Verify and finish department clearance behavior after the MVP implementation. Keep scope limited to department dashboard, clearance signing/denial, FAQ/profile access, tests, and documented deferrals.

**Status:** Finished. Core behavior is implemented and focused sign/deny tests were reconciled; remaining non-MVP work is deferred to Phase 07, 08, 09, 10, or 11 as named below.

**Depends on:** Phase 02, Phase 04.

**Primary docs:** [`03-roles-and-permissions.md`](../../03-roles-and-permissions.md), [`07-routes-and-controllers.md`](../../07-routes-and-controllers.md), [`10-security.md`](../../10-security.md).

---

## Agent Task 5.1 — Reconcile Department Implementation

**Delegate to:** code-explorer + backend-patterns

**Read first:**
- `routes/department.php`
- `app/Http/Controllers/Department/*`
- `resources/js/Pages/Department/*`
- `app/Policies/ClearancePolicy.php`
- `app/Services/ClearanceService.php`

**Steps:**
- [x] Confirm department dashboard, list, detail, sign, deny, profile, and FAQ routes exist.
- [x] Mark missing route/controller/page/test gaps in this phase file.
- [x] Confirm whether `StaffLayout.vue` already shows department role/context.
- [x] Confirm existing tests that cover department sign/deny and policy rules.
- [x] Record intentionally deferred items under Phase Notes.

**Acceptance:**
- [x] This phase reflects actual code status, not old assumptions.
- [x] Every missing item has a concrete follow-up task below.

## Agent Task 5.2 — Department Route And Page Gaps

**Delegate to:** frontend-patterns + backend-patterns

**Files likely touched:**
- `routes/department.php`
- `app/Http/Controllers/Department/DashboardController.php`
- `app/Http/Controllers/Department/ClearanceController.php`
- `app/Http/Controllers/Department/FaqController.php`
- `resources/js/Pages/Department/Dashboard.vue`
- `resources/js/Pages/Department/Clearances/Index.vue`
- `resources/js/Pages/Department/Clearances/Show.vue`
- `resources/js/Pages/Department/Faq.vue`

**Steps:**
- [x] Add or fix missing department route names from `docs/07-routes-and-controllers.md`.
- [x] Ensure dashboard shows pending, signed today, and denied counts scoped to current officer role.
- [x] Ensure clearance list supports status, course, year level, and student search filters.
- [x] Ensure detail page shows student info, linked request, supporting file preview, own action panel, and other department statuses.
- [x] Ensure FAQ page filters `staff` and `all` audience records.

**Acceptance:**
- [x] Department officer can reach dashboard, clearance list/detail, profile, notifications, and FAQ.
- [x] Non-department users receive 403 or redirect per route middleware.

## Agent Task 5.3 — Clearance Sign/Deny Correctness

**Delegate to:** tdd-workflow + backend-patterns

**Files likely touched:**
- `app/Http/Requests/Department/SignClearanceRequest.php`
- `app/Http/Requests/Department/DenyClearanceRequest.php`
- `app/Http/Controllers/Department/ClearanceController.php`
- `app/Services/ClearanceService.php`
- `tests/Feature/Department/ClearanceActionTest.php`

**Steps:**
- [x] Write focused tests for sign and deny happy paths for teacher, dean, accounting, and SAO.
- [x] Write focused test that each department route action only mutates the authenticated role's column.
- [x] Ensure sign request permits optional remarks only.
- [x] Ensure deny request requires remarks with minimum 10 characters.
- [x] Ensure service updates `{role}_status`, `{role}_remarks`, `{role}_signed_by`, `{role}_signed_at` in a DB transaction.
- [x] Ensure activity log is written for sign and deny.
- [x] Ensure `ClearanceUpdated` dispatches for both actions.

**Acceptance:**
- [x] All four department roles can only mutate their own column.
- [x] Overall clearance status recomputes after every action.
- [x] Tests prove authorization and state changes.

## Agent Task 5.4 — Completion Boundary With Phase 09

**Delegate to:** backend-patterns + code-reviewer

**Files likely touched:**
- `app/Services/ClearanceService.php`
- `tests/Feature/Department/ClearanceCompletionTest.php`

**Steps:**
- [x] Verify completion happens only when all required departments are cleared.
- [x] Verify completed transition fires `ClearanceCompleted` once.
- [x] Keep PDF generation as a Phase 09 responsibility unless `PdfService` already exists.
- [x] Remove or document any stub-PDF behavior so Phase 09 can replace it safely.

**Acceptance:**
- [x] Clearance completion can be tested without requiring real DomPDF output.
- [x] Phase 09 has a clear seam for real PDF generation.

## Agent Task 5.5 — Department Closeout Verification

**Delegate to:** code-reviewer

**Commands:**

```bash
php artisan test --filter=Department
php artisan test --filter=Clearance
./vendor/bin/pint --test app/Http/Controllers/Department app/Services/ClearanceService.php tests/Feature/Department
```

**Acceptance:**
- [x] Focused department/clearance tests pass.
- [x] No missing department MVP route remains undocumented.
- [x] Deferred realtime/message/PDF work points to Phase 07, 08, or 09.

## Phase Notes

- Realtime notification bell belongs to Phase 07.
- Messaging belongs to Phase 08.
- Clearance completion stores a generated PDF path through `PdfService`; richer printable/export templates remain Phase 09.
- Browser-level department UI and manual Reverb checks remain deferred to Phase 07/10/11 as applicable.
- Phase 05 is finished for MVP closeout; browser-level UI, manual Reverb, messaging, and broader polish remain tracked in later phases or deferred notes.
