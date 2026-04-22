# 02 — Architecture

## High-Level Diagram

```
┌────────────────────────────────────────────────────────────────────┐
│                          DigitalOcean VPS                          │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                          Dokploy                             │  │
│  │                                                              │  │
│  │  ┌────────────┐   ┌────────────┐   ┌──────────────────────┐  │  │
│  │  │   Nginx    │──▶│  PHP-FPM   │──▶│   Laravel App        │  │  │
│  │  │  (proxy)   │   │  (Laravel) │   │   - Controllers      │  │  │
│  │  └─────┬──────┘   └────────────┘   │   - Models           │  │  │
│  │        │                           │   - Inertia render   │  │  │
│  │        │  WebSocket                └──────────┬───────────┘  │  │
│  │        ▼                                      │              │  │
│  │  ┌────────────┐                               ▼              │  │
│  │  │   Reverb   │                       ┌────────────┐         │  │
│  │  │ (port 8080)│◀──────────────────────│  Database  │         │  │
│  │  └────────────┘     Broadcasts        │  (MySQL 8) │         │  │
│  │                                       └────────────┘         │  │
│  │  ┌────────────────┐                          ▲               │  │
│  │  │  Queue Worker  │──────────────────────────┘               │  │
│  │  │ (Supervisor)   │                                          │  │
│  │  └────────────────┘                                          │  │
│  │                                                              │  │
│  │  ┌──────────────────────────────────────────────────────┐    │  │
│  │  │  Persistent volumes: storage/, .env, db data         │    │  │
│  │  └──────────────────────────────────────────────────────┘    │  │
│  └──────────────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────────┘
                                  ▲
                                  │ HTTPS (later) / HTTP (initial)
                                  │
                       ┌──────────┴──────────┐
                       │   Browser (Vue 3)   │
                       │   - Inertia client  │
                       │   - Echo (WS conn)  │
                       └─────────────────────┘
```

## Application Layers

### 1. Presentation Layer (Vue + Inertia)
- **Pages** — top-level Vue components mapped from controller responses
- **Components** — reusable UI primitives (`StatusBadge`, `DataTable`, `NotificationBell`)
- **Layouts** — role-specific shells (`StudentLayout`, `AdminLayout`, `DepartmentLayout`)
- **Composables** — shared logic (`useNotifications`, `useEcho`, `useFilters`)

### 2. Routing & Controllers
- **Routes** organized per role (`routes/student.php`, `routes/admin.php`, etc.)
- **Controllers** thin — delegate to services and form requests
- **Form Requests** — validation rules live here, not in controllers
- **Middleware** — `EnsureRole`, `EnsureApprovedAccount`

### 3. Domain Layer
- **Models** — Eloquent with relationships and scopes
- **Policies** — authorization (can this user view/approve this request?)
- **Services** — `RequestService`, `ClearanceService`, `PaymentService` for non-trivial business logic
- **Events** — `RequestSubmitted`, `PaymentApproved`, `ClearanceCompleted`
- **Listeners** — react to events (send notifications, broadcast)

### 4. Infrastructure
- **Notifications** — Laravel notifications (in-app + email channels)
- **Mail** — Mailable classes for transactional emails
- **Broadcasting** — Reverb-backed channels for live updates
- **Storage** — Laravel `Storage` facade with `local` and `public` disks
- **Queue** — database-backed queue for async work

## Request Lifecycle (Example: Student Submits Request)

```
1. Browser POST /student/requests (with CSRF + Inertia headers)
2. Route → StudentRequestController@store
3. Middleware chain: auth → role:student → approved-account
4. Form Request validation (StoreRequestRequest)
5. Controller calls RequestService->createRequest($user, $data)
6. Service:
   a. Begins DB transaction
   b. Inserts request rows (one per document)
   c. Inserts payment row (status: Pending)
   d. Dispatches RequestSubmitted event
   e. Commits transaction
7. Listeners react:
   - LogRequestActivity (audit log)
   - NotifyAdminsOfNewRequest (in-app + email + broadcast)
8. Controller returns Inertia redirect to /student/requests
9. Vue page receives flash message and re-renders
10. Admin dashboards (open in other browsers) receive Reverb broadcast
    → NotificationBell badge increments live
    → Request table prepends new row
```

## Folder Structure (Laravel side)

```
app/
├── Console/
├── Events/
│   ├── RequestSubmitted.php
│   ├── PaymentApproved.php
│   ├── ClearanceUpdated.php
│   └── MessageSent.php
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── Student/
│   │   ├── Admin/
│   │   ├── Department/
│   │   └── SuperAdmin/
│   ├── Middleware/
│   │   ├── EnsureRole.php
│   │   └── EnsureApprovedAccount.php
│   └── Requests/
│       ├── Student/
│       ├── Admin/
│       └── ...
├── Models/
│   ├── User.php
│   ├── DocumentRequest.php
│   ├── DocumentType.php
│   ├── Payment.php
│   ├── Clearance.php
│   ├── Announcement.php
│   ├── Faq.php
│   ├── Notification.php
│   ├── Message.php
│   └── ActivityLog.php
├── Notifications/
│   ├── RequestSubmittedNotification.php
│   ├── PaymentApprovedNotification.php
│   └── ClearanceCompletedNotification.php
├── Policies/
│   ├── DocumentRequestPolicy.php
│   ├── PaymentPolicy.php
│   └── ClearancePolicy.php
├── Providers/
│   └── BroadcastingServiceProvider.php
└── Services/
    ├── RequestService.php
    ├── PaymentService.php
    ├── ClearanceService.php
    └── PdfService.php

resources/
├── js/
│   ├── Pages/
│   │   ├── Auth/
│   │   ├── Student/
│   │   ├── Admin/
│   │   ├── Department/
│   │   └── SuperAdmin/
│   ├── Components/
│   ├── Layouts/
│   ├── Composables/
│   └── app.js
├── css/
│   └── app.css
└── views/
    └── pdf/
        └── clearance.blade.php

routes/
├── web.php          (entry, includes role-specific files)
├── auth.php         (login, register, password reset)
├── student.php
├── admin.php
├── department.php
├── superadmin.php
└── channels.php     (broadcast authorization)

database/
├── migrations/
├── seeders/
└── factories/

storage/
└── app/
    ├── public/
    │   ├── avatars/
    │   ├── payment-receipts/
    │   ├── clearance-files/
    │   └── signatures/
    └── private/
        └── pdfs/

tests/
├── Feature/
└── Unit/
```

## Design Principles

1. **Thin controllers, fat services** — controllers handle HTTP concerns only.
2. **Eloquent everywhere** — never raw SQL except for complex reporting queries.
3. **Form Requests for validation** — never validate in controllers.
4. **Policies for authorization** — never inline `if ($user->id !== ...)` checks.
5. **Events for side effects** — keep core logic decoupled from notifications/logging.
6. **One Vue page per route** — page components are entry points; pull logic into composables.
7. **Tailwind utilities first, custom CSS last** — extract `@apply` only when patterns repeat 3+ times.
