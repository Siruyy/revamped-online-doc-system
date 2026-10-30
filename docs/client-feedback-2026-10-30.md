# Client revision — 2026-10-30

The client message supplied in the screenshot asks for:

1. A landing-page button to view a document price list.
2. Prices maintainable by Admin, SuperAdmin, and Accounting.
3. An asterisk beside required fields in the public document request form.

## Implementation

- `/pricelist` (`public.pricelist`) shows active document types, grouped by category, with current database fees and their calculation units. It excludes inactive types and explains Registrar evaluation and additional charges. Transfer Credentials remain subject to bundle evaluation.
- Admin and SuperAdmin keep their existing Document Types editors. Accounting has a fee-only editor at `/department/prices`, linked as Price List in its staff navigation. The endpoint validates amounts, authorizes Accounting access, and logs changes; extra document metadata in a submitted payload is not applied.
- Catalog updates affect the public reference list and subsequent evaluations. Confirmed request quote snapshots are not rewritten. No schema change or production seeding is needed.
- Required labels follow `StorePublicDocumentRequest` and the existing wizard rules. Delivery, employment, representative details, and visible education sections are marked when applicable. Year level or academic status is identified as a required choice of one. Optional fields remain unmarked; required uploads retain their asterisks and accessible required state.

## Deployment handoff

The user confirmed that the app is already on the VPS through Dokploy. [`14-deployment.md`](14-deployment.md), [`operations/dokploy-runbook.md`](operations/dokploy-runbook.md), and root [`CLAUDE.md`](../CLAUDE.md) provide the handoff. This task prepares local code and documentation; publishing and deployment require a separate request.

Focused regression coverage: `tests/Feature/Public/PriceListTest.php` verifies public visibility, live rates, role restrictions, validation, and metadata protection. Browser inspection and broader verification results are reported with the completed task.

## Local verification — 2026-10-30

- Full PHP suite: 434 tests passed (SQLite in-memory). After adding quote-snapshot assertions, focused price-list tests passed: 5 tests / 97 assertions.
- Pint, PHPStan, ESLint, and Vite build passed. Local links in the updated handoff and plan documents resolved.
- Browser checks used a separate temporary SQLite database and synthetic Accounting account. The landing link opened the price list; fees and formula units rendered on desktop and at 375px. An Accounting rate edit saved successfully and appeared in the public list.
- At 375px, personal and conditional employment, custom-purpose, delivery, and representative labels displayed asterisks and accessible required text. Optional personal fields remained unmarked; the rendered conditional form had no horizontal overflow.
- Security review: existing auth/approved/verified middleware remains; new mutation is Accounting-only, policy-authorized, CSRF-protected, throttled, validated, fee-only, and audit-logged. Public serialization includes only document catalog fields. Confirmed quote snapshots remain unchanged in regression coverage.
- No production deployment, DNS change, production data edit, real-inbox delivery test, or full legacy browser-suite run was performed for this change.
