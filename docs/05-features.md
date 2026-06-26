# 05 — Features

Feature catalog grouped by role. Each feature lists its **trigger**, **inputs**, and **outcomes**.

## Authentication & Account

| Feature | Description |
|---------|-------------|
| Public request intake | Requestor submits a document request without creating an account |
| Reference tracking | Requestor enters reference number to view privacy-safe status |
| Staff login | Email + password; checks `status=active` before granting access |
| Logout | Invalidate session |
| Forgot password | Email link with signed token, expires in 60 min |
| Reset password | Set new password using token |
| Legacy student self-registration | Existing code path retained but hidden from public request flow |
| Email verification | Optional but recommended for authenticated accounts |
| Profile editing | Update name, contact, avatar, password |

## Public Requestor Features

| Feature | Description |
|---------|-------------|
| Browse document types | View available documents with fees and processing times |
| Submit document request | Enter requestor details, choose documents/copies, provide purpose, upload requirements and receipt |
| Upload payment receipt | Required during request submission; attach image/PDF + method + reference number |
| Receive reference number | Confirmation page displays generated `REQ-YYYY-######` |
| Track request | Reference-number lookup shows request, payment, clearance, and processing status |
| Email notifications | Sent to requestor email when provided |
| FAQ access | Filtered to student-relevant entries |

## Legacy Authenticated Student Features

These pages/routes exist in code but should be hidden from public navigation while Phase 15 is implemented.

| Feature | Description |
|---------|-------------|
| Dashboard | Stats (active requests, pending payments, clearance status), announcements, FAQs |
| View own requests | List with filters (status, date) and reference numbers |
| Cancel pending request | Only allowed if status=`pending` and no payment uploaded |
| Submit clearance | Optional supporting file upload (e.g., library clearance scan) |
| Download clearance PDF | Available when overall status = `completed` |
| In-app notifications | Bell icon with unread count, real-time updates |

## Admin Features

| Feature | Description |
|---------|-------------|
| Dashboard | Aggregate stats: total/pending/denied/completed requests, payment stats |
| Real-time queue | Live-updating list of new requests |
| Approve / deny request | Validate requestor details, attachments, and receipt together; denial requires requestor-visible reason |
| Update processing stage | Move request from Processing → Ready for Pickup → Released |
| Approve / deny payment | View receipt image, reference, amount; public intake approval happens on request detail |
| Manage document types | CRUD on document types and fees |
| Manage announcements | CRUD, pin/unpin, set audience |
| Manage FAQs | CRUD, set audience filter |
| Clearance monitoring | Read-only view of all clearances; filter by course, year, department |
| Profile | Edit own profile and signature |
| Messaging | Receive and reply to student messages |
| Notifications | Real-time bell icon |

## Department (Teacher / Dean / Accounting / SAO) Features

| Feature | Description |
|---------|-------------|
| Dashboard | Pending clearances assigned to this department |
| Review student clearance | View student details, course, year, attached files |
| Sign clearance (cleared) | Marks the department's section as cleared, signs with timestamp + signature |
| Deny clearance | With remarks; student is notified |
| Filter / search students | By course, year level, name, date |
| Profile | Edit own profile, upload signature image |
| Messaging | Receive messages from admin/superadmin |
| Notifications | Real-time bell icon |

## SuperAdmin Features

| Feature | Description |
|---------|-------------|
| Dashboard | System-wide stats and quick actions |
| User management | List, search, filter by role/status |
| Approve registrations | Legacy authenticated-student workflow only; not part of public request intake |
| Create staff accounts | Manually create admin/department accounts |
| Suspend / reactivate | Toggle account status |
| Bulk delete users | With confirmation modal |
| Export users | CSV/Excel download |
| Export reports | Request reports filtered by date range, status, course |
| Activity logs | Searchable log of all system actions |
| Manage all entities | Override admin permissions on any record |
| Manage announcements & FAQs | Same as admin |
| System notifications | Broadcast announcements to all users |
| Profile | Edit own profile |

## Cross-Cutting Features

| Feature | Description |
|---------|-------------|
| Real-time notifications | Reverb-broadcast events update bell icon and lists without refresh |
| Real-time chat | Live message delivery; "user is typing" optional |
| Email notifications | Sent via queue for: public request submission/status, payment status, clearance updates, legacy registration approval |
| Audit logging | Every state-changing action recorded in `activity_logs` |
| Mobile responsive | All pages usable on phone screens (≥360px) |
| Dark mode (stretch) | Tailwind dark mode toggle, optional |
| Search | Global search bar (admin/superadmin) for users and requests |
| Pagination | Server-side pagination on all large lists |
| Filtering | Server-side filters on list pages (status, date, course, etc.) |
| File previews | Inline preview for image receipts and PDFs |

## Feature Flags / Future Enhancements

Not in v1, but documented for future:
- Online payment gateway (PayMongo/GCash API)
- SMS notifications (Twilio/Semaphore)
- Bulk request approval
- Document version history
- Two-factor authentication
- Audit log export
