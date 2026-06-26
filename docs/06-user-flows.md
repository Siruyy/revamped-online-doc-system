# 06 — User Flows

End-to-end workflows showing how actors interact with the system.

## Flow 1 — Public Document Request Intake

```
Requestor                  System                         Admin/SuperAdmin
   │                          │                                │
   │ 1. Visits /request-document                               │
   │ 2. Enters student/requestor details                       │
   │ 3. Selects document(s), uploads requirements + receipt    │
   │ ───────────────────────▶ │                                │
   │                          │ 3. Validate input              │
   │                          │ 4. Store requestor snapshot    │
   │                          │ 5. Create request + payment    │
   │                          │ 6. Store files privately       │
   │                          │ 7. Notify staff                │
   │ ◀── Show reference no.   │ ─────────────────────────────▶ │
   │                          │                                │ 8. Reviews in request queue
```

## Flow 2 — Admin Validates Public Request

```
Admin/SuperAdmin → /admin/requests/{id}
  → Reviews requestor snapshot details
  → Opens private requirement files
  → Opens private payment receipt
  → Checks payment method, reference number, and amount
  → If valid:
     → Validate each requirement
     → Approve request and payment together
     → request.status=approved, processing_stage=processing
     → payment.status=approved
     → Start clearance if the document type requires it
     → Email requestor when email is present
  → If invalid:
     → Enter denial reason
     → request.status=denied
     → payment.status=denied
     → Public tracking shows the denial reason
     → Email requestor when email is present
```

## Flow 3 — Public Reference Tracking

```
Requestor → /track-document
  → Enters reference number
  → Submit
    → Validate reference format
    → Lookup document_requests.reference_no
    → Return privacy-safe payload only:
       - reference number
       - document names
       - request status
       - payment status
       - processing stage
       - submitted date
       - expected release date
       - denial reason when denied
  → No uploaded files, contact info, email, internal IDs, or staff-only notes are exposed
```

## Flow 4 — Legacy Authenticated Student Request Flow

```
The existing /student/* pages and account-registration path remain in code for
now, but they are no longer the desired public requestor workflow. Hide these
links from public navigation during Phase 15 instead of deleting them.
```

## Flow 5 — Department Clearance Signing

```
Department officer (e.g., Teacher) logs in
  → Department dashboard
  → Sees list of students with pending teacher clearance (live-updating)
  → Filter by course/year/date
  → Click student → clearance detail
  → Reviews student info, course, attached files
  → Clicks "Mark as Cleared" (or "Deny" with remarks)
    → POST /department/clearance/{id}/sign
    → Validate: officer's role matches the column being signed
    → Authorize via ClearancePolicy
    → Update clearance row:
       - {role}_status = cleared
       - {role}_signed_by, {role}_signed_at
    → Recompute overall_status:
       - If all 4 = cleared → overall_status=completed
       - If any = denied → overall_status=denied
       - Else → in_progress
    → Dispatch ClearanceUpdated event
       → If completed: generate PDF, notify student
       → If denied: notify student with remarks
       → Always: notify admin
       → Broadcast to student's channel
```

## Flow 6 — Requestor Tracks Request

```
Requestor → /track-document
  → Enters reference number
  → Page renders privacy-safe request timeline
  → Sees timeline:
     ┌──────────────────────────────────────┐
     │ ✓ Request submitted (Apr 21 10:00)   │
     │ ✓ Payment uploaded (Apr 21 10:15)    │
     │ ✓ Payment approved (Apr 21 14:00)    │
     │ ✓ Request approved (Apr 21 14:05)    │
     │ ◐ Clearance in progress              │
     │   ✓ Teacher cleared                  │
     │   ✓ Dean cleared                     │
     │   ◐ Accounting (waiting)             │
     │   ◐ SAO (waiting)                    │
     │ ○ Document processing                │
     │ ○ Ready for pickup                   │
     │ ○ Released                           │
     └──────────────────────────────────────┘
  → Requestor refreshes or re-enters reference number for latest status
```

## Flow 7 — SuperAdmin Manages Staff And Legacy Registrations

```
SuperAdmin → /superadmin/users?status=pending
  → Legacy pending student registrations and staff-created accounts may appear here
  → Click student row → review
  → Verify name, email, course, year level
  → Approve OR Reject (with reason)
    → POST /superadmin/users/{id}/approve
    → user.status = active, approved_by, approved_at
    → Send email "Account approved, you can now log in"
    → Log activity
```

## Flow 8 — Forgot Password

```
User → /forgot-password
  → Enter email
  → Submit
    → If user exists: generate signed token, store in password_reset_tokens
    → Send email with reset link (expires 60 min)
  → User clicks link → /reset-password?token=...
  → Enter new password (confirmed)
  → Submit → password updated, all sessions invalidated, redirect to login
```

## Flow 9 — Real-Time Messaging

```
Student opens chat with Admin
  → Subscribes to private channel chat.{conversationId}
Admin opens chat with Student (same conversation)
  → Same channel

Student types message → POST /messages
  → Insert message row
  → Broadcast MessageSent event on chat.{conversationId}
  → Both clients receive event → Vue prepends message to thread
  → Receiver's notification bell increments live
  → On focus, mark as read → broadcast MessageRead
```

## Flow 10 — Cancel Request

```
Student → /student/requests/{id}
  → If status=pending AND no receipt uploaded:
     - "Cancel Request" button visible
  → Click → confirmation modal
  → Confirm → POST /student/requests/{id}/cancel
  → status=cancelled
  → Notify admin (informational)
  → Activity log
```

## State Diagrams

### Document Request State Machine

```
       [pending] ──approve──▶ [approved] ──processing complete──▶ [completed]
          │                       │
          ├──deny──▶ [denied]     ├──cancel (admin)──▶ [cancelled]
          │
          └──cancel (legacy student)──▶ [cancelled]
```

### Payment State Machine

```
[pending_approval] ──approve──▶ [approved]
                                       │
                                       └──deny──▶ [denied]

Public intake creates payment rows directly as `pending_approval` because the receipt is required during request submission. The older `pending → upload receipt` path is legacy authenticated-student behavior.
```

### Clearance Overall Status

```
       [in_progress] ──all 4 cleared──▶ [completed]
            │
            └──any denied──▶ [denied] ──officer reverses──▶ [in_progress]
```
