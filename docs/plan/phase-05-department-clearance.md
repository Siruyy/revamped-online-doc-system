# Phase 05 — Department Clearance

> **Goal:** Teacher/Dean/Accounting/SAO can sign or deny student clearances. Department-scoped views and policies.

**Subagents:** `tdd-guide`, `code-reviewer`.
**Skills:** `tdd-workflow`, `backend-patterns`.
**Depends on:** Phase 02 (auth) + Phase 04 (admin must approve payment to create a clearance).
**Can run in parallel with:** Phase 04.

---

## 5.1 Department Layout

- [ ] Reuse `StaffLayout.vue` with department-specific sidebar items.
- [ ] Display the officer's department prominently in the top bar.

## 5.2 Department Dashboard

- [ ] `Department\DashboardController@index` returns:
    - Counts: pending clearances for this department, signed today, denied
    - Latest 10 pending clearances assigned to this department
- [ ] `Pages/Department/Dashboard.vue` with stat cards and pending list.

## 5.3 Clearance List

- [ ] `Department\ClearanceController@index`:
    - Returns clearances where `{role}_status = 'pending'` (filterable to other statuses)
    - Filters: course, year level, search by student name
    - Pagination
- [ ] `Pages/Department/Clearances/Index.vue` with `<DataTable>` and `<FilterBar>`.

## 5.4 Clearance Detail

- [ ] `@show` returns clearance with student, document request, and per-department status.
- [ ] `Pages/Department/Clearances/Show.vue`:
    - Student info card
    - Course / year level
    - Linked document request reference
    - Attached supporting file preview (if any)
    - This department's current status + remarks
    - Action buttons: "Mark as Cleared" / "Deny with Remarks"
    - Other departments' statuses (read-only context)

## 5.5 Sign / Deny Actions

- [ ] `SignClearanceRequest` Form Request — optional remarks.
- [ ] `DenyClearanceRequest` Form Request — required remarks (min 10 chars).
- [ ] `@sign` and `@deny` controller methods.
- [ ] `ClearanceService::signFor($clearance, $officer, $remarks)`:
    - Authorize via `ClearancePolicy::signFor`
    - Update `{role}_status`, `{role}_remarks`, `{role}_signed_by`, `{role}_signed_at`
    - Recompute `overall_status`
    - If now `completed`: trigger PDF generation, notify student
    - Dispatch `ClearanceUpdated` event
- [ ] `ClearanceService::denyFor($clearance, $officer, $remarks)`:
    - Same pattern with denied status
- [ ] All within DB transaction.
- [ ] Activity logged.

## 5.6 Department-Scoped Policy

- [ ] `ClearancePolicy::signFor($user, $clearance, $department)`:
    - User's role must equal `$department`
    - SuperAdmin always allowed
- [ ] `ClearancePolicy::view($user, $clearance)`:
    - Department officers can view all clearances (read-only for non-own dept)
    - OR scope to own dept only — confirm with client. **Default: see all but only sign own.**
- [ ] Tests for all policy methods.

## 5.7 Profile

- [ ] Department profile page includes signature upload (PNG, transparent background).
- [ ] Signature stored in private disk.

## 5.8 Notifications & Messages

- [ ] Notification bell wired (real-time in Phase 07).
- [ ] Messages access (Phase 08).

## 5.9 FAQ

- [ ] Department FAQ page (audience='staff' or 'all').

## 5.10 Tests

- [ ] Sign / deny happy paths per role.
- [ ] Cross-department signing attempt → 403.
- [ ] Recompute logic (parametrized: all status combinations).
- [ ] PDF generation triggered on completion.
- [ ] Coverage 80%+.

---

## Exit Criteria

- ✅ Each of the 4 department roles can sign / deny their column on a clearance.
- ✅ Clearance overall status updates correctly across all combinations.
- ✅ PDF generation triggered automatically on completion (handler may be stubbed; full PDF in Phase 09).
- ✅ Signature image embedded in PDF (Phase 09).
- ✅ Department officers cannot sign columns outside their role.
