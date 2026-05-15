# Support Workflow

Use this workflow after launch for user support that is not deployment-specific.

## Intake

- Primary channel: client-designated support email. If email is not ready before launch, use one named in-app support account as the temporary intake owner.
- Required details: reporter name, role, affected request reference, screenshot when possible, steps attempted, and time observed.
- Do not request passwords, raw database exports, or private uploaded files through email.

## Severity

| Severity | Definition | Response target |
|---|---|---|
| P0 | System unavailable or login blocked for all roles | Same day, immediate escalation |
| P1 | Critical workflow blocked for one or more roles | Same business day |
| P2 | Workflow degraded but workaround exists | 2 business days |
| P3 | Minor content, wording, or usability issue | Next planned maintenance window |

## Triage Steps

1. Confirm role and exact route or workflow.
2. Reproduce on a test account when possible.
3. Check whether the issue is data-specific, permission-specific, or browser-specific.
4. Record the finding and link the request reference or user ID when relevant.
5. For code defects, open a hotfix ticket and follow `docs/operations/hotfix.md`.

## Communication

- For P0/P1, send a short status update when triage starts and when a workaround or fix is available.
- For P2/P3, acknowledge receipt and provide the expected review window.
- Keep student-specific data out of public announcements.
