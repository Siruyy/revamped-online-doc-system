# 05 — Features

Feature catalog grouped by role. Each feature lists its **trigger**, **inputs**, and **outcomes**.

## Authentication & Account

| Feature | Description |
|---------|-------------|
| Self-registration | Student fills form → account created with `pending` status → SuperAdmin notified |
| Login | Email + password; checks `status=active` before granting access |
| Logout | Invalidate session |
| Forgot password | Email link with signed token, expires in 60 min |
| Reset password | Set new password using token |
| Email verification | Optional but recommended; enforced for students |
| Profile editing | Update name, contact, avatar, password |

## Student Features

| Feature | Description |
|---------|-------------|
| Dashboard | Stats (active requests, pending payments, clearance status), announcements, FAQs |
| Browse document types | View available documents with fees and processing times |
| Submit document request | Multi-select documents → calculates total fee → creates request batch |
| View own requests | List with filters (status, date) and reference numbers |
| Track request | Detail page showing approval, payment, clearance, processing stages |
| Cancel pending request | Only allowed if status=`pending` and no payment uploaded |
| Upload payment receipt | Attach image/PDF + payment method + reference number |
| View payment status | Pending → Approved/Denied with reason |
| Submit clearance | Optional supporting file upload (e.g., library clearance scan) |
| Track clearance | See per-department status (teacher, dean, accounting, SAO) |
| Download clearance PDF | Available when overall status = `completed` |
| In-app notifications | Bell icon with unread count, real-time updates |
| Messaging | Chat with admin / superadmin |
| FAQ access | Filtered to student-relevant entries |

## Admin Features

| Feature | Description |
|---------|-------------|
| Dashboard | Aggregate stats: total/pending/denied/completed requests, payment stats |
| Real-time queue | Live-updating list of new requests |
| Approve / deny request | With optional denial reason; sends notification + email |
| Update processing stage | Move request from Processing → Ready for Pickup → Released |
| Approve / deny payment | View receipt image, reference, amount; approve or deny with reason |
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
| Approve registrations | Review pending student registrations; approve or reject with reason |
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
| Email notifications | Sent via queue for: registration approval, request status, payment status, clearance updates |
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
