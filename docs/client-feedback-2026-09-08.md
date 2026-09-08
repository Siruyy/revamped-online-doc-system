# Client feedback follow-up

## Staff controls

- Admin and SuperAdmin sidebars include **Payment Settings**. Add/edit bank or e-wallet name, account name, account number, instructions, and an optional QR image. Activate profiles to display them in public tracking; deactivate them to hide them. Tracking lists every active profile. The landing page retains the most recently created active profile.
- Payment QR replacement uses a multipart POST with a PATCH method override so PHP receives the uploaded file. Uploads validate image content, extension, size, and dimensions.
- Admin and SuperAdmin sidebars include **School Branding**. Upload a PNG/JPG school logo (2 MB maximum, 3000 × 3000 maximum), preview it, replace it, or remove it. The file stays on private storage and is used on the public landing page, staff layouts, newly generated claim-slip PDFs, and ready-notification attachments. Previously issued PDFs and sent emails do not change. Accounting can manage payment profiles from its own **Payment Settings** page; Admin and SuperAdmin retain their existing access.
- In **Announcements**, choose audience **All** and publish to display on the landing page. Drafts, future-dated announcements, and restricted audiences are excluded. The landing page displays the latest three public announcements, with pinned entries first.
- In **FAQs**, choose role **All** to display on the landing page (first eight by sort order). Staff/student entries remain scoped to their portals. These public content blocks do not depend on scroll-reveal animation.

## Release steps

Run `php artisan migrate --force` as part of deployment. The new branding table is additive. The separate data migration corrects known old `tor_transfer` titles to **Transfer Credentials** without changing fees, descriptions, or unrelated catalog entries. Its rollback intentionally retains the corrected title because the original spelling cannot be reconstructed safely. The branding migration rollback drops its settings table; back up the settings and private branding files before rolling back.

Build frontend assets with `npm run build`. Keep the web app and queue worker on the same release and shared private storage. Restart queue workers after deployment.

## Email delivery investigation

Run `php artisan mail:diagnose` in both the deployed web application and queue-worker environment. It prints the configured mailer/transport, queue connection, configuration-cache state, whether a Resend key exists when applicable, and database queue counts. It does not print credentials, send mail, or prove delivery/worker health.

The local environment checked on this task uses `MAIL_MAILER=log`, a database queue, and zero pending/failed jobs. A log mailer writes email locally and never delivers to inboxes. This is local evidence, not confirmation of the production configuration.

Production must use a delivery mailer such as the already supported `resend`, with `RESEND_KEY` available to both web and worker services and a provider-approved `MAIL_FROM_ADDRESS`. For SMTP, use the corresponding SMTP settings. Refresh cached configuration after changes and restart workers (`php artisan config:cache`, `php artisan queue:restart`; process supervision must start workers again). Inspect provider delivery events and recipient spam folders to distinguish accepted, rejected, bounced, and delivered mail. Retry only identified failed jobs after correcting the cause; bulk retries can duplicate notifications.

Existing automated tests cover notification queuing, anonymous-recipient mail, and private PDF attachments. A real inbox delivery test remains an operational check; no external email was sent by this task.
