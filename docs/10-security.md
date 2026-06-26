# 10 — Security

## Threat Model

This system handles:
- Personally Identifiable Information (PII): full names, emails, contact numbers, student IDs
- Payment receipt images (potentially containing financial data)
- Identity-related documents (clearance forms, signatures)
- Authentication credentials
- Public request lookup by reference number

**Threat actors:** opportunistic attackers (script kiddies), disgruntled students/staff, credential stuffing bots.

## Security Requirements

### Authentication & Sessions

- [x] Passwords hashed with **bcrypt** (cost 12) — Laravel default
- [x] **Rate limiting on login** — max 5 attempts per minute per IP+email (`throttle:5,1`)
- [x] **Rate limiting on password reset** — max 3 per hour per email
- [x] Sessions invalidated on password change
- [x] Session timeout: 120 minutes idle
- [x] CSRF tokens on all state-changing requests (Laravel default)
- [x] HTTPS enforced via middleware once SSL is configured
- [x] Secure cookies: `Secure`, `HttpOnly`, `SameSite=Lax`
- [x] Email verification required for students (optional for staff)
- [x] Account lockout after 10 failed attempts (24h cooldown via custom middleware)
- [ ] Phase 15: public request tracking must be rate limited because it uses reference number only.

### Authorization

- [x] Role enforced via **`EnsureRole` middleware** at route level
- [x] Resource access via **Laravel Policies** (e.g., `DocumentRequestPolicy`)
- [x] SuperAdmin override via `Gate::before()` only
- [x] `EnsureApprovedAccount` middleware blocks `pending`/`suspended`/`rejected` users
- [x] Department officers can only sign for their own department (verified in policy)

### Input Validation

- [x] All input validated via **Form Request classes** (no controller-level validation)
- [x] Strict types where possible
- [x] Whitelist allowed values for enums (status, role, etc.)
- [x] Email format validated, normalized to lowercase
- [x] String length limits enforced

### SQL Injection

- [x] **Eloquent ORM and query builder only** — no raw SQL
- [x] If raw SQL is unavoidable (rare reporting queries), use **bound parameters** (`DB::select(?)`)
- [x] Never concatenate user input into queries
- [x] **Static scan in CI** with Larastan to detect raw query usage

### Cross-Site Scripting (XSS)

- [x] All Inertia/Vue output is escaped by default — Vue's `{{ }}` interpolation is safe
- [x] **Never use `v-html`** with user-supplied content
- [x] Sanitize rich text (announcements, FAQs) with **HTMLPurifier** if rich text is added later (not in v1; use plain text)
- [x] Set `Content-Security-Policy` header (script-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline')

### Cross-Site Request Forgery (CSRF)

- [x] Laravel's `VerifyCsrfToken` middleware on all `web` routes
- [x] Inertia automatically includes the CSRF token
- [x] No CSRF exclusions

### File Uploads

| Concern | Mitigation |
|---------|-----------|
| Malicious file types | **Whitelist MIME and extensions**: `jpg`, `jpeg`, `png`, `pdf` only |
| Oversized files | Max 5 MB per file, validated server-side |
| Filename injection | Generate UUID filenames, never trust the original name |
| Stored XSS via SVG | Block SVG uploads |
| Path traversal | Use `Storage::putFile()` — never construct paths manually |
| Virus/malware | Out of scope for v1; document as a known risk |
| Public exposure | Receipts, request requirements, signatures, clearance files, and PDFs stored on **`local`** disk (not public); served via authorized controller routes |

```php
// Example validation rule
'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
```

### File Serving

Receipts, request requirements, signatures, clearance files, and PDFs are not directly accessible. Files are served through a controller:

```php
// GET /files/payment-receipt/{payment}
public function paymentReceipt(Payment $payment) {
    $this->authorize('view', $payment);
    return Storage::disk('local')->download($payment->receipt_path);
}
```

### Secrets Management

- [x] No secrets in source code or commits
- [x] All secrets in `.env` (gitignored)
- [x] Production `.env` deployed via Dokploy environment variables
- [x] Database password is strong, randomly generated
- [x] `APP_KEY` regenerated per environment
- [x] Reverb app secret is strong, environment-specific
- [x] SMTP credentials stored in environment variables only

### HTTP Headers

```php
// Security headers middleware
'X-Content-Type-Options'    => 'nosniff',
'X-Frame-Options'           => 'DENY',
'X-XSS-Protection'          => '1; mode=block',
'Referrer-Policy'           => 'same-origin',
'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains', // when HTTPS
'Content-Security-Policy'   => "default-src 'self'; ...",
'Permissions-Policy'        => 'camera=(), microphone=(), geolocation=()',
```

### Logging & Monitoring

- [x] All authentication events logged (success + failure)
- [x] Failed authorization attempts logged with IP + user
- [x] Audit trail for all state changes (`activity_logs` table)
- [x] Sensitive data **never** logged (no passwords, no full receipts content)
- [x] Use Laravel's structured logging
- [x] Configure log rotation (daily, retain 14 days)

### Error Handling

- [x] **Production:** generic error pages (`APP_DEBUG=false`)
- [x] **Never** expose stack traces, SQL queries, or internal paths to users
- [x] Custom Inertia error pages: 403, 404, 419, 500
- [x] Log full error details server-side

### Rate Limiting

| Endpoint | Limit |
|----------|-------|
| Login | 5 / minute / IP+email |
| Password reset request | 3 / hour / email |
| Legacy registration | 3 / hour / IP |
| Public request submission | 5 / hour / IP |
| Public reference tracking | 20 / hour / IP |
| File upload | 10 / minute / user |
| Message send | 30 / minute / user |
| General API | 60 / minute / user |

### Public Request Workflow

Phase 15 public request intake must not create hidden student users. The requestor submits details, requirement files, and payment receipt in one public form. The system creates:

- a `document_requests` row with requestor snapshot fields,
- `document_request_items`,
- `request_requirements`,
- a `payments` row with `status=pending_approval`.

Admin/SuperAdmin validates the request, attachments, and payment together. If invalid, staff deny the whole request with a requestor-visible reason.

Public tracking uses reference number only by client decision. To reduce exposure:

- Return only status/timeline fields and denial reason.
- Do not return uploaded file URLs, email, contact number, internal IDs, staff notes, or full audit history.
- Apply rate limiting to lookups.
- Keep reference numbers high-entropy enough to resist casual guessing.

### Account Approval Workflow

Self-registration is legacy for requestors after Phase 15. Staff account approval and any retained authenticated-student registration still use `pending` accounts blocked from login until SuperAdmin approval.

### Database

- [x] Use a **non-root** MySQL user with privileges scoped to the application database only
- [x] No `GRANT ALL` — only `SELECT, INSERT, UPDATE, DELETE` (no `DROP`, no `CREATE`)
- [x] Backups encrypted, stored off-VPS
- [x] Migrations checked into git, never run manually in production

### Dependency Security

- [x] Run `composer audit` and `npm audit` in CI on every PR
- [x] Renovate or Dependabot for dependency PRs
- [x] Pin major versions, allow minor/patch updates

## OWASP Top 10 Coverage

| Risk | Status |
|------|--------|
| A01 Broken Access Control | Policies + middleware + tests |
| A02 Cryptographic Failures | bcrypt + HTTPS + secure cookies |
| A03 Injection | Eloquent ORM + parameterized raw queries |
| A04 Insecure Design | Threat modeling done; approval workflow; rate limiting |
| A05 Security Misconfiguration | `APP_DEBUG=false`, security headers, restricted DB user |
| A06 Vulnerable Components | `composer audit`, `npm audit`, dependency scanning |
| A07 Identification & Auth Failures | Throttling, lockout, strong hashing |
| A08 Software & Data Integrity | Signed cookies, CSRF, code review |
| A09 Security Logging Failures | Audit logs, login logs, retention |
| A10 SSRF | No outbound URL fetching from user input |

## Pre-Deployment Security Checklist

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY`
- [ ] All `.env` secrets set, none default
- [ ] Database user has restricted privileges
- [ ] Storage permissions: 755 dirs, 644 files
- [ ] HTTPS configured (when domain available)
- [ ] CSP and security headers verified
- [ ] Default SuperAdmin password changed
- [ ] No `phpinfo()` or debug routes exposed
- [ ] `.env` not web-accessible
- [ ] `composer audit` passes
- [ ] `npm audit --production` passes
