# V2 Roadmap

These items are candidates for future work. They are not part of the current launch closeout.

## Must-Have Follow-Up

- Complete Phase 08 messaging only after stakeholders reactivate the scope.
- Keep manual verification logs for realtime, queues, mail, role access, private files, and exports.
- Review support tickets after launch and update FAQs/training guides.

## Candidate Enhancements

| Item | Value | Risk | Notes |
|---|---|---|---|
| Online payment gateway | High | High | Requires provider integration, reconciliation rules, and payment-security review. |
| SMS notifications | Medium | Medium | Useful for student updates; requires opt-in, provider cost, and message templates. |
| Bulk request approval | Medium | Medium | Useful for staff throughput; must preserve per-request audit logs and gates. |
| Two-factor authentication | High | Medium | Strong fit for SuperAdmin and admin accounts. |
| Document version history | Medium | Medium | Helpful for policy/doc changes, but adds storage and UI complexity. |
| Notification preferences | Medium | Low | Lets users reduce email/realtime noise. |
| Dark mode | Low | Low | UX enhancement only. |
| PWA hardening | Low | Medium | Requires offline-state decisions for private workflows. |
| Analytics dashboard | Medium | Medium | Should use aggregate data and avoid exposing student PII. |

## Ranking Rule

Prioritize fixes that reduce security, authorization, payment, clearance, or data-loss risk before adding new convenience features.
