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

- [ ] Open one public browser session and one admin or SuperAdmin session.
- [ ] Submit a public document request with required attachments and receipt; confirm the admin/SuperAdmin notification bell updates without refresh.
- [ ] Approve or deny the request as admin/SuperAdmin and confirm email is queued when the requestor provided email.
- [ ] Complete or deny a department clearance step and confirm staff notifications update as expected.
- [ ] Stop `php artisan reverb:start`, repeat one notification-triggering action, and confirm polling fallback still shows the notification after refresh or polling delay.

## Public No-Login Request Flow

- [ ] Visit `/request-document` as a guest and submit requestor details, document items, required files, and payment receipt in one intake.
- [ ] Confirm the requestor receives a reference number and no `users` table row is created for that public requestor.
- [ ] Visit `/track-document`, enter the reference number, and confirm the result shows public-safe status and next-step copy only.
- [ ] As Admin or SuperAdmin, open the request detail, preview the receipt and requirement files, and confirm the inline help explains that no student account is needed.
- [ ] Validate all submitted requirements, approve request + payment, and confirm a clearance is created only when the requested document type requires department clearance.
- [ ] As teacher, dean, accounting, and SAO, open the department clearance detail and confirm the original request attachments are visible for review.
- [ ] Sign or deny a public clearance without uploading a separate clearance supporting file.
- [ ] If denied, confirm public tracking shows the denial reason without exposing private file paths, staff-only links, or clearance PDFs.
- [ ] Complete all department signatures, confirm the private clearance PDF is generated, and confirm Admin/SuperAdmin can download it.
- [ ] Confirm student and department users cannot download the public clearance PDF.
- [ ] Move the request to ready for pickup and then released; confirm public tracking shows the correct next-step copy at each stage.
- [ ] Confirm public requestor email receives public-safe updates when email is present and no email error occurs when email is absent.
- [ ] Record whether Reverb, queue worker, and Mailpit/Mailhog were running; note any environment limitation instead of marking the item verified.

## Queue And Mail

- [ ] Keep `php artisan queue:work` running while triggering public request, password reset, request approval/denial, payment approval/denial, and clearance events.
- [ ] Confirm no failed jobs with `php artisan queue:failed`.
- [ ] Open Mailpit/Mailhog and confirm expected emails are captured for public request status, password reset, and workflow notifications that send mail.
- [ ] Confirm email bodies do not expose reset tokens in notification array payloads or on-page debug output.

## Role Walkthrough

- [ ] Public requestor can submit a request without an account, receive a reference number, and track status with that reference number.
- [ ] Admin can review public requests, validate/reject requirements, approve/deny the whole request/payment package, update stages, release documents, and manage content.
- [ ] Department roles can only sign or deny their own clearance column.
- [ ] SuperAdmin can access users, legacy pending registrations, logs, reports, requests, document types, announcements, FAQs, notifications, profile, and CSV exports.
- [ ] Non-authorized roles receive `403` or redirect behavior for protected pages.

## Files And Exports

- [ ] Receipt, request requirement, signature, clearance supporting file, and PDF downloads are served only through authorized routes.
- [ ] Direct public access to private storage paths fails.
- [ ] CSV exports download correctly and preserve active filters for users, requests, payments, and activity logs.
- [ ] Clearance PDF includes the expected student/request data and only safe signature images.

## Mobile And Accessibility

- [ ] Test public request/tracking pages plus admin, department, and SuperAdmin dashboards on mobile viewport widths.
- [ ] Check keyboard navigation for menus, forms, dialogs, and action buttons.
- [ ] Check visible focus states and form validation errors.
- [ ] Run a screen reader smoke test on the main dashboards and request/clearance detail pages.
- [ ] Check print/PDF-related pages for readable layout.

## Handoff Notes

- [ ] Record browser/device versions used for manual verification.
- [ ] Record any failed checklist item with route, role, steps to reproduce, and screenshot if useful.
- [ ] Do not mark post-launch handoff complete until failed checklist items are fixed or explicitly accepted as deferred.
