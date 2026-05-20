# Manual Verification Checklist

Use this checklist for items that automated tests cannot prove reliably. Run these after pulling the latest code and before final client handoff.

## Environment

- [ ] Copy `.env.example` to `.env` if needed and configure MySQL, mail capture, queue, and Reverb values.
- [ ] Run `composer install` and `npm install` if dependencies are missing or stale.
- [ ] Run `php artisan migrate:fresh --seed` in a disposable local database.
- [ ] Start the app stack in separate terminals:

```bash
php artisan serve
php artisan queue:work
php artisan reverb:start
npm run dev
```

## Realtime Notifications

- [ ] Log in with two browser sessions: one student and one admin or SuperAdmin.
- [ ] Submit a student document request and confirm the admin/SuperAdmin notification bell updates without refresh.
- [ ] Approve or deny the request as admin/SuperAdmin and confirm the student notification bell updates without refresh.
- [ ] Upload a payment receipt as student and confirm admin/SuperAdmin receives a realtime notification.
- [ ] Approve or deny the payment and confirm the student receives a realtime notification.
- [ ] Complete or deny a department clearance step and confirm the affected student receives a realtime notification.
- [ ] Stop `php artisan reverb:start`, repeat one notification-triggering action, and confirm polling fallback still shows the notification after refresh or polling delay.

## Queue And Mail

- [ ] Keep `php artisan queue:work` running while triggering registration, password reset, request, payment, and clearance events.
- [ ] Confirm no failed jobs with `php artisan queue:failed`.
- [ ] Open Mailpit/Mailhog and confirm expected emails are captured for registration approval/rejection, password reset, and workflow notifications that send mail.
- [ ] Confirm email bodies do not expose reset tokens in notification array payloads or on-page debug output.

## Role Walkthrough

- [ ] Student can register, verify email, wait for approval, submit requests, upload receipts, view clearance, and download completed clearance PDF.
- [ ] Admin can review requests, validate/reject requirements, approve/deny payments, update stages, release documents, and manage content.
- [ ] Department roles can only sign or deny their own clearance column.
- [ ] SuperAdmin can access users, pending registrations, logs, reports, requests, document types, announcements, FAQs, notifications, profile, and CSV exports.
- [ ] Non-authorized roles receive `403` or redirect behavior for protected pages.

## Files And Exports

- [ ] Receipt, signature, clearance supporting file, and PDF downloads are served only through authorized routes.
- [ ] Direct public access to private storage paths fails.
- [ ] CSV exports download correctly and preserve active filters for users, requests, payments, and activity logs.
- [ ] Clearance PDF includes the expected student/request data and only safe signature images.

## Mobile And Accessibility

- [ ] Test student, admin, department, and SuperAdmin dashboards on mobile viewport widths.
- [ ] Check keyboard navigation for menus, forms, dialogs, and action buttons.
- [ ] Check visible focus states and form validation errors.
- [ ] Run a screen reader smoke test on the main dashboards and request/clearance detail pages.
- [ ] Check print/PDF-related pages for readable layout.

## Handoff Notes

- [ ] Record browser/device versions used for manual verification.
- [ ] Record any failed checklist item with route, role, steps to reproduce, and screenshot if useful.
- [ ] Do not mark post-launch handoff complete until failed checklist items are fixed or explicitly accepted as deferred.
