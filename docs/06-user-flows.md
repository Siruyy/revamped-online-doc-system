# 06 — User Flows

End-to-end workflows showing how actors interact with the system.

## Flow 1 — Student Registration & Approval

```
Student                    System                         SuperAdmin
   │                          │                                │
   │ 1. Visits /register      │                                │
   │ 2. Fills form, submits   │                                │
   │ ───────────────────────▶ │                                │
   │                          │ 3. Validate input              │
   │                          │ 4. Create user (status=pending)│
   │                          │ 5. Email verification link     │
   │                          │ 6. Notify SuperAdmin (Reverb + email)
   │                          │ ─────────────────────────────▶ │
   │ ◀── Show "pending" page  │                                │
   │                          │                                │ 7. Reviews queue
   │                          │                                │ 8. Approves
   │                          │ ◀───────────────────────────── │
   │                          │ 9. status=active               │
   │                          │ 10. Email "approved"           │
   │ ◀── (email)              │                                │
   │ 11. Logs in              │                                │
   │ ───────────────────────▶ │                                │
   │ ◀── Student dashboard    │                                │
```

## Flow 2 — Submit Document Request

```
Student → /student/requests/new
  → Select one or more document types (checkbox UI)
  → Optional: enter purpose
  → Vue calculates total fee live
  → Submit
    → POST /student/requests
    → Validate (max 5 docs, no active pending request)
    → Begin transaction:
       - Insert document_requests rows (one per doc)
       - Insert payments row (status=pending, total)
       - Generate reference number
    → Commit
    → Dispatch RequestSubmitted event
       → Listener: log activity
       → Listener: notify all admins (in-app + email + Reverb broadcast)
    → Inertia redirect to /student/requests/{id}
  → Student sees confirmation, payment instructions, next steps
```

## Flow 3 — Upload Payment Receipt

```
Student → /student/payments
  → Sees pending payment for their request
  → Selects payment method (Cash / GCash / Bank Transfer)
  → Enters reference number (if applicable)
  → Uploads receipt image or PDF
  → Submit
    → POST /student/payments/{id}/upload
    → Validate file (jpg/png/pdf, max 5MB)
    → Store in storage/app/public/payment-receipts/
    → Update payment row: status=pending_approval, receipt_path, submitted_at
    → Dispatch PaymentSubmitted event
       → Notify admins
    → Inertia redirect with success flash
```

## Flow 4 — Admin Approves Payment & Request

```
Admin dashboard
  → Sees new request in real-time list (via Reverb)
  → Clicks request → detail page
  → Reviews payment receipt (inline preview)
  → Clicks "Approve Payment"
    → POST /admin/payments/{id}/approve
    → Update payment: status=approved, approved_by, approved_at
    → Dispatch PaymentApproved event
       → Notify student (in-app + email + Reverb)
       → Initialize clearance row for the student (if document requires clearance)
  → Clicks "Approve Request"
    → POST /admin/requests/{id}/approve
    → Update request: status=approved, processing_stage=processing
    → Dispatch RequestApproved event
       → Notify student
       → Notify all department officers (clearance now visible)

Later (when document is ready):
  → Admin updates stage to "ready_for_pickup" then "released"
    → Student receives status updates live
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

## Flow 6 — Student Tracks Request

```
Student → /student/requests/{id}
  → Inertia page renders with all request data
  → Subscribes to private channel student.{user_id}
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
  → As events fire, Vue updates timeline live (no refresh)
```

## Flow 7 — SuperAdmin Approves New Registration

```
SuperAdmin → /superadmin/users?status=pending
  → Live list (Reverb-updated when new student registers)
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
          └──cancel (student)──▶ [cancelled]
```

### Payment State Machine

```
[pending] ──upload receipt──▶ [pending_approval] ──approve──▶ [approved]
                                       │
                                       └──deny──▶ [denied]
                                                    │
                                                    └──upload again──▶ [pending_approval]
```

### Clearance Overall Status

```
       [in_progress] ──all 4 cleared──▶ [completed]
            │
            └──any denied──▶ [denied] ──officer reverses──▶ [in_progress]
```
