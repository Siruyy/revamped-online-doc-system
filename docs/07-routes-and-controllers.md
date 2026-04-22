# 07 — Routes & Controllers

> All routes use Laravel route groups with middleware. Inertia handles rendering — no `/api` prefix needed.

## Route Files Organization

```
routes/
├── web.php          (entry, public + Inertia setup)
├── auth.php         (Breeze-generated)
├── student.php
├── admin.php
├── department.php
├── superadmin.php
└── channels.php     (Reverb channel auth)
```

`web.php` includes the role-specific files:

```php
require __DIR__.'/auth.php';
require __DIR__.'/student.php';
require __DIR__.'/admin.php';
require __DIR__.'/department.php';
require __DIR__.'/superadmin.php';
```

## Public Routes

| Method | URL | Controller | Page |
|--------|-----|-----------|------|
| GET | `/` | `LandingController@index` | `Landing.vue` |
| GET | `/login` | Breeze | `Auth/Login.vue` |
| POST | `/login` | Breeze | — |
| GET | `/register` | Breeze | `Auth/Register.vue` |
| POST | `/register` | `RegisterController@store` | — |
| GET | `/forgot-password` | Breeze | `Auth/ForgotPassword.vue` |
| POST | `/forgot-password` | Breeze | — |
| GET | `/reset-password/{token}` | Breeze | `Auth/ResetPassword.vue` |
| POST | `/reset-password` | Breeze | — |
| POST | `/logout` | Breeze | — |

## Student Routes (`prefix=student`, middleware=`['auth', 'role:student', 'approved']`)

| Method | URL | Controller @ Action | Page |
|--------|-----|--------------------|------|
| GET | `/student/dashboard` | `Student\DashboardController@index` | `Student/Dashboard.vue` |
| GET | `/student/profile` | `Student\ProfileController@edit` | `Student/Profile.vue` |
| PATCH | `/student/profile` | `Student\ProfileController@update` | — |
| GET | `/student/requests` | `Student\RequestController@index` | `Student/Requests/Index.vue` |
| GET | `/student/requests/new` | `Student\RequestController@create` | `Student/Requests/Create.vue` |
| POST | `/student/requests` | `Student\RequestController@store` | — |
| GET | `/student/requests/{request}` | `Student\RequestController@show` | `Student/Requests/Show.vue` |
| POST | `/student/requests/{request}/cancel` | `Student\RequestController@cancel` | — |
| GET | `/student/payments` | `Student\PaymentController@index` | `Student/Payments/Index.vue` |
| POST | `/student/payments/{payment}/upload` | `Student\PaymentController@upload` | — |
| GET | `/student/clearance` | `Student\ClearanceController@show` | `Student/Clearance/Show.vue` |
| POST | `/student/clearance` | `Student\ClearanceController@submit` | — |
| GET | `/student/clearance/pdf` | `Student\ClearanceController@downloadPdf` | (PDF download) |
| GET | `/student/notifications` | `NotificationController@index` | `Notifications/Index.vue` |
| POST | `/student/notifications/{id}/read` | `NotificationController@markRead` | — |
| GET | `/student/messages` | `MessageController@index` | `Messages/Index.vue` |
| GET | `/student/messages/{user}` | `MessageController@show` | `Messages/Show.vue` |
| POST | `/student/messages` | `MessageController@store` | — |
| GET | `/student/faq` | `Student\FaqController@index` | `Student/Faq.vue` |

## Admin Routes (`prefix=admin`, middleware=`['auth', 'role:admin']`)

| Method | URL | Controller @ Action | Page |
|--------|-----|--------------------|------|
| GET | `/admin/dashboard` | `Admin\DashboardController@index` | `Admin/Dashboard.vue` |
| GET | `/admin/profile` | `Admin\ProfileController@edit` | `Admin/Profile.vue` |
| PATCH | `/admin/profile` | `Admin\ProfileController@update` | — |
| GET | `/admin/requests` | `Admin\RequestController@index` | `Admin/Requests/Index.vue` |
| GET | `/admin/requests/{request}` | `Admin\RequestController@show` | `Admin/Requests/Show.vue` |
| POST | `/admin/requests/{request}/approve` | `Admin\RequestController@approve` | — |
| POST | `/admin/requests/{request}/deny` | `Admin\RequestController@deny` | — |
| POST | `/admin/requests/{request}/stage` | `Admin\RequestController@updateStage` | — |
| GET | `/admin/payments` | `Admin\PaymentController@index` | `Admin/Payments/Index.vue` |
| POST | `/admin/payments/{payment}/approve` | `Admin\PaymentController@approve` | — |
| POST | `/admin/payments/{payment}/deny` | `Admin\PaymentController@deny` | — |
| GET | `/admin/clearances` | `Admin\ClearanceMonitorController@index` | `Admin/Clearances/Index.vue` |
| GET | `/admin/clearances/{clearance}` | `Admin\ClearanceMonitorController@show` | `Admin/Clearances/Show.vue` |
| GET | `/admin/document-types` | `Admin\DocumentTypeController@index` | `Admin/DocumentTypes/Index.vue` |
| POST | `/admin/document-types` | `Admin\DocumentTypeController@store` | — |
| PATCH | `/admin/document-types/{type}` | `Admin\DocumentTypeController@update` | — |
| DELETE | `/admin/document-types/{type}` | `Admin\DocumentTypeController@destroy` | — |
| GET | `/admin/announcements` | `Admin\AnnouncementController@index` | `Admin/Announcements/Index.vue` |
| POST | `/admin/announcements` | `Admin\AnnouncementController@store` | — |
| PATCH | `/admin/announcements/{a}` | `Admin\AnnouncementController@update` | — |
| DELETE | `/admin/announcements/{a}` | `Admin\AnnouncementController@destroy` | — |
| GET | `/admin/faqs` | `Admin\FaqController@index` | `Admin/Faqs/Index.vue` |
| POST/PATCH/DELETE | `/admin/faqs/...` | `Admin\FaqController` | — |
| GET | `/admin/notifications` | `NotificationController@index` | shared |
| GET | `/admin/messages` | `MessageController@index` | shared |
| GET | `/admin/reports` | `Admin\ReportController@index` | `Admin/Reports.vue` |
| GET | `/admin/reports/export` | `Admin\ReportController@export` | (download) |

## Department Routes (`prefix=department`, middleware=`['auth', 'role:teacher,dean,accounting,sao']`)

| Method | URL | Controller @ Action | Page |
|--------|-----|--------------------|------|
| GET | `/department/dashboard` | `Department\DashboardController@index` | `Department/Dashboard.vue` |
| GET | `/department/clearances` | `Department\ClearanceController@index` | `Department/Clearances/Index.vue` |
| GET | `/department/clearances/{clearance}` | `Department\ClearanceController@show` | `Department/Clearances/Show.vue` |
| POST | `/department/clearances/{clearance}/sign` | `Department\ClearanceController@sign` | — |
| POST | `/department/clearances/{clearance}/deny` | `Department\ClearanceController@deny` | — |
| GET | `/department/profile` | `Department\ProfileController@edit` | `Department/Profile.vue` |
| PATCH | `/department/profile` | `Department\ProfileController@update` | — |
| GET | `/department/notifications` | shared | shared |
| GET | `/department/messages` | shared | shared |
| GET | `/department/faq` | `Department\FaqController@index` | `Department/Faq.vue` |

## SuperAdmin Routes (`prefix=superadmin`, middleware=`['auth', 'role:superadmin']`)

| Method | URL | Controller @ Action | Page |
|--------|-----|--------------------|------|
| GET | `/superadmin/dashboard` | `SuperAdmin\DashboardController@index` | `SuperAdmin/Dashboard.vue` |
| GET | `/superadmin/users` | `SuperAdmin\UserController@index` | `SuperAdmin/Users/Index.vue` |
| GET | `/superadmin/users/pending` | `SuperAdmin\UserController@pending` | `SuperAdmin/Users/Pending.vue` |
| POST | `/superadmin/users/{user}/approve` | `SuperAdmin\UserController@approve` | — |
| POST | `/superadmin/users/{user}/reject` | `SuperAdmin\UserController@reject` | — |
| POST | `/superadmin/users/{user}/suspend` | `SuperAdmin\UserController@suspend` | — |
| POST | `/superadmin/users/{user}/reactivate` | `SuperAdmin\UserController@reactivate` | — |
| DELETE | `/superadmin/users/{user}` | `SuperAdmin\UserController@destroy` | — |
| POST | `/superadmin/users/bulk-delete` | `SuperAdmin\UserController@bulkDelete` | — |
| GET | `/superadmin/users/{user}/edit` | `SuperAdmin\UserController@edit` | `SuperAdmin/Users/Edit.vue` |
| PATCH | `/superadmin/users/{user}` | `SuperAdmin\UserController@update` | — |
| POST | `/superadmin/users` | `SuperAdmin\UserController@store` | — |
| GET | `/superadmin/users/export` | `SuperAdmin\UserController@export` | (download) |
| GET | `/superadmin/requests` | `SuperAdmin\RequestController@index` | `SuperAdmin/Requests/Index.vue` |
| GET | `/superadmin/reports/export` | `SuperAdmin\ReportController@export` | (download) |
| GET | `/superadmin/logs` | `SuperAdmin\LogController@index` | `SuperAdmin/Logs.vue` |
| GET | `/superadmin/announcements` | reuses Admin controller | shared |
| GET | `/superadmin/faqs` | reuses Admin controller | shared |
| GET | `/superadmin/document-types` | reuses Admin controller | shared |
| GET | `/superadmin/profile` | `SuperAdmin\ProfileController@edit` | `SuperAdmin/Profile.vue` |

## Broadcast Channels (`channels.php`)

```php
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('role.admin', function ($user) {
    return in_array($user->role, ['admin', 'superadmin']);
});

Broadcast::channel('role.superadmin', function ($user) {
    return $user->role === 'superadmin';
});

Broadcast::channel('role.department.{department}', function ($user, $department) {
    return $user->role === $department;
});

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    return ChatService::userBelongsToConversation($user, $conversationId);
});
```

## Middleware Pipeline Examples

```php
// Student-only, must be approved
Route::middleware(['auth', 'role:student', 'approved'])
    ->prefix('student')
    ->name('student.')
    ->group(base_path('routes/student.php'));

// Department roles share routes
Route::middleware(['auth', 'role:teacher,dean,accounting,sao', 'approved'])
    ->prefix('department')
    ->name('department.')
    ->group(base_path('routes/department.php'));
```

## Naming Conventions

- Routes named with dot notation: `student.requests.show`, `admin.payments.approve`.
- Use `Route::resource()` where it cleanly fits, custom routes otherwise.
- Form action POSTs go to a verb suffix: `/approve`, `/deny`, `/cancel`, `/sign`, `/upload`.
