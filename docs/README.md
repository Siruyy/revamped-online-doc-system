# SVCI Online Document Request & Management System — Documentation

> Project: **St. Vincent College Incorporated — Online Document Request and Management System (Revamped)**
> Stack: **Laravel 13 + Inertia.js + Vue 3 + Tailwind CSS + Laravel Reverb**
> Deployment: **DigitalOcean VPS via Dokploy**

This documentation set is split into focused, single-topic files. Read them in order on first pass; reference individually after.

## Index

| # | Document | Purpose |
|---|----------|---------|
| 00 | [Overview](./00-overview.md) | Project goals, scope, and stakeholders |
| 01 | [Tech Stack](./01-tech-stack.md) | Technologies, versions, rationale |
| 02 | [Architecture](./02-architecture.md) | High-level system design and layers |
| 03 | [Roles & Permissions](./03-roles-and-permissions.md) | Role matrix and access control |
| 04 | [Database Schema](./04-database-schema.md) | Tables, relations, indexes |
| 05 | [Features](./05-features.md) | Feature list per role |
| 06 | [User Flows](./06-user-flows.md) | End-to-end workflows |
| 07 | [Routes & Controllers](./07-routes-and-controllers.md) | URL map and controller responsibilities |
| 08 | [Real-Time (Reverb)](./08-real-time.md) | WebSocket events and channels |
| 09 | [Frontend Design](./09-frontend-design.md) | UI/UX system, components, design tokens |
| 10 | [Security](./10-security.md) | Security requirements and practices |
| 11 | [File Storage](./11-file-storage.md) | Upload strategy, validation, structure |
| 12 | [Notifications & Email](./12-notifications-and-email.md) | In-app + email notification system |
| 13 | [PDF Generation](./13-pdf-generation.md) | Clearance certificate generation |
| 14 | [Deployment](./14-deployment.md) | Dokploy + DigitalOcean VPS setup |
| 15 | [Testing Strategy](./15-testing-strategy.md) | Pest + browser testing |
| 16 | [Policy Matrix](./16-policy-matrix.md) | Registrar policy → system rules mapping |
| 17 | [Design System](./17-design-system.md) | UI tokens, components, accessibility |
| 18 | [UAT Script](./18-uat-script.md) | Policy-mapped acceptance tests & release readiness |

## Related

- Implementation plans (phased, with checkboxes): [`../plan/`](../plan/README.md)
