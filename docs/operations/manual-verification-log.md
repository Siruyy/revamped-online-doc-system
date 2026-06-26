# Manual Verification Log

Copy this template for each manual verification pass.

## YYYY-MM-DD Verification

**Verifier:**
**Environment:** local / staging / production
**Commit:**
**Result:** pass / fail / partial

### Realtime And Notifications

- [ ] Start app, Vite, queue worker, and Reverb when applicable.
- [ ] Submit a public document request and verify admin/SuperAdmin notification appears.
- [ ] Submit a payment receipt and verify Admin payment queue updates or polling refreshes within fallback window.
- [ ] Approve a payment requiring clearance and verify department queue updates or polling refreshes within fallback window.
- [ ] Disconnect or stop realtime service and confirm polling fallback refreshes visible counts/queues.

### Queue And Email

- [ ] Run queue worker and confirm queued notifications process.
- [ ] Confirm expected mail is visible in local/staging mail capture when mail is enabled.

### Role Walkthrough

- [ ] Public requestor can submit request with receipt, receive reference number, and track status.
- [ ] Admin can approve/deny requests, approve/deny payments, and manage release flow gates.
- [ ] Department officer can only view and act on own clearance queue.
- [ ] SuperAdmin can approve users, view logs, and export reports.

### Private Files And Exports

- [ ] Admin/SuperAdmin can open public request receipt and requirement files through authorized file routes.
- [ ] Direct public storage URLs for private request files fail.
- [ ] SuperAdmin report exports download with selected filters.
- [ ] Non-SuperAdmin users are forbidden from SuperAdmin report exports.

### Notes And Follow-Up

- Issues found:
- Follow-up owner:
- Follow-up deadline:
