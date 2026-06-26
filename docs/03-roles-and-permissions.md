# 03 — Roles & Permissions

## Roles

| Role | Description |
|------|-------------|
| Public requestor | Students and alumni who submit document requests without an account |
| `student` | Legacy authenticated student role retained in code for now; hide from public request flow |
| `admin` | School registrar admin — approves requests, manages records |
| `teacher` | Teacher department — signs off on student clearance |
| `dean` | Dean department — signs off on student clearance |
| `accounting` | Accounting department — signs off on financial clearance |
| `sao` | Student Affairs Office — signs off on conduct clearance |
| `superadmin` | Full system control |

> Department roles (`teacher`, `dean`, `accounting`, `sao`) share the same dashboard but see role-scoped data. They are collectively referred to as **department roles**.

## Account Status

Independent of role, every account has a `status`:

| Status | Meaning |
|--------|---------|
| `pending` | Legacy self-registration or staff-created account awaiting SuperAdmin approval. Cannot log in. |
| `active` | Approved; full access per role. |
| `suspended` | Disabled by SuperAdmin. Cannot log in. |
| `rejected` | Registration denied. Cannot log in. |

## Permission Matrix

### Document Requests

| Action | student | admin | dept | superadmin |
|--------|:-------:|:-----:|:----:|:----------:|
| Submit public request | Public | — | — | — |
| Track by reference number | Public | — | — | — |
| Submit own authenticated request | Legacy | — | — | — |
| View own authenticated requests | Legacy | — | — | — |
| Cancel own pending authenticated request | Legacy | — | — | — |
| View all requests | — | ✅ | — | ✅ |
| Approve / Deny request | — | ✅ | — | ✅ |
| Update request stage (Processing → Released) | — | ✅ | — | ✅ |
| Delete request | — | — | — | ✅ |

### Payments

| Action | student | admin | dept | superadmin |
|--------|:-------:|:-----:|:----:|:----------:|
| Upload receipt during public request intake | Public | — | — | — |
| View payment status by reference tracking | Public | — | — | — |
| Upload own authenticated receipt | Legacy | — | — | — |
| View own authenticated payment | Legacy | — | — | — |
| View all payments | — | ✅ | — | ✅ |
| Approve / Deny payment | — | ✅ | — | ✅ |

### Clearance

| Action | student | admin | dept | superadmin |
|--------|:-------:|:-----:|:----:|:----------:|
| Submit clearance file | ✅ | — | — | — |
| View own clearance | ✅ | — | — | — |
| Sign clearance for own dept | — | — | ✅ | ✅ |
| View all clearances | — | ✅ | — | ✅ |
| Monitor clearance progress | — | ✅ | ✅ (own dept) | ✅ |
| Download clearance PDF | ✅ (own) | ✅ | — | ✅ |

### Users

| Action | student | admin | dept | superadmin |
|--------|:-------:|:-----:|:----:|:----------:|
| View own profile | ✅ | ✅ | ✅ | ✅ |
| Edit own profile | ✅ | ✅ | ✅ | ✅ |
| View all users | — | — | — | ✅ |
| Approve / reject pending registrations | Legacy | — | — | ✅ |
| Create staff accounts (admin/dept) | — | — | — | ✅ |
| Suspend / reactivate users | — | — | — | ✅ |
| Delete users | — | — | — | ✅ |

### Document Types & Fees

| Action | student | admin | dept | superadmin |
|--------|:-------:|:-----:|:----:|:----------:|
| View document types | ✅ | ✅ | ✅ | ✅ |
| Create / edit / delete document types | — | ✅ | — | ✅ |
| Set fees | — | ✅ | — | ✅ |

### Announcements

| Action | student | admin | dept | superadmin |
|--------|:-------:|:-----:|:----:|:----------:|
| View announcements | ✅ | ✅ | ✅ | ✅ |
| Create / edit / delete announcements | — | ✅ | — | ✅ |
| Pin announcements | — | ✅ | — | ✅ |

### FAQs

| Action | student | admin | dept | superadmin |
|--------|:-------:|:-----:|:----:|:----------:|
| View FAQs (filtered by role) | ✅ | ✅ | ✅ | ✅ |
| Create / edit / delete FAQs | — | ✅ | — | ✅ |

### Reports & Logs

| Action | student | admin | dept | superadmin |
|--------|:-------:|:-----:|:----:|:----------:|
| View activity logs | — | — | — | ✅ |
| Export users (CSV/Excel) | — | — | — | ✅ |
| Export request reports | — | ✅ | — | ✅ |

### Messaging

| Action | student | admin | dept | superadmin |
|--------|:-------:|:-----:|:----:|:----------:|
| Message admin/superadmin | ✅ | ✅ | ✅ | ✅ |
| Message students | — | ✅ | ✅ | ✅ |
| Broadcast message | — | — | — | ✅ |

## Implementation Notes

- Role checks use **middleware**: `Route::middleware(['auth', 'role:admin'])`.
- Resource-level checks use **Laravel Policies**: `$this->authorize('approve', $request)`.
- Public request submission and tracking are guest routes. Tracking uses only a reference number and must return a privacy-safe payload.
- Department officers can only view/sign clearances for their own department — enforced in the `ClearancePolicy`.
- SuperAdmin bypasses all policy checks via a `Gate::before()` callback.
