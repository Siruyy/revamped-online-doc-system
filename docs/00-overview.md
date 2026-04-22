# 00 — Project Overview

## Purpose

The **St. Vincent College Incorporated (SVCI) Online Document Request and Management System** allows students and alumni to request academic documents (e.g., transcripts, good moral certificates, certificates of enrollment) online, complete an online clearance process across multiple departments, upload payment receipts, and track their requests in real time — without setting foot on campus until they pick up the documents.

This document set covers a **full rewrite** of the legacy vanilla-PHP system into a modern Laravel 11 + Inertia + Vue stack.

## Goals

1. **Eliminate manual paperwork** — fully digital request and clearance pipeline.
2. **Real-time visibility** — students see status changes the moment they happen.
3. **Secure by default** — no SQL injection, CSRF protection, validated file uploads.
4. **Maintainable** — clean MVC, typed models, reusable Vue components.
5. **Mobile-friendly** — students primarily access the system from phones.
6. **Auditable** — every action logged with who/when/what.

## Non-Goals (out of scope)

- Online payment gateway integration (PayMongo, GCash API). Payments remain offline; the student uploads a receipt image.
- Document delivery via courier. Documents are picked up on campus.
- Public API for third-party integrations.
- Mobile native app (PWA-friendly responsive web is sufficient).

## Stakeholders & Roles

| Stakeholder | Role |
|-------------|------|
| **Students / Alumni** | Submit requests, upload payment, track, complete online clearance |
| **Admin** | Approve/deny requests and payments, manage announcements, FAQ, document types |
| **Department Officers** (Teacher, Dean, Accounting, SAO) | Sign off on student clearance |
| **SuperAdmin** | Full system control — users, reports, logs, account approvals |

## Success Criteria

- Student can submit a request and pay in **under 3 minutes**.
- Admin sees new requests **in real time** with no page refresh.
- Clearance status updates broadcast to the student dashboard live.
- Zero SQL injection vulnerabilities (verified via static scan).
- All forms protected with CSRF tokens.
- Mobile-responsive across iOS Safari, Chrome Mobile, Firefox Mobile.
- 80%+ test coverage on business logic.

## Constraints

- **Hosting:** DigitalOcean VPS (single droplet, 2GB+ RAM target).
- **Deployment platform:** Dokploy (Docker-based).
- **No domain at launch** — accessed via VPS IP initially; domain + SSL added later.
- **Self-hosted everything** — no Pusher, no Vercel, no third-party SaaS dependencies aside from SMTP for email.

## Migration from Legacy

Schema is similar enough that we can **port existing data** (users, document_types, requests, payments, clearances) via a one-time migration script after the new system is built. See [`14-deployment.md`](./14-deployment.md).
