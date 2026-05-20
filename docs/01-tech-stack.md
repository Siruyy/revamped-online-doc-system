# 01 — Tech Stack

## Summary Table

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| Runtime | PHP | 8.4+ | Backend language |
| Framework | Laravel | 13.x | MVC, ORM, routing, queues |
| Frontend bridge | Inertia.js | 2.x | SPA-like navigation, no API layer |
| UI framework | Vue.js | 3.x (Composition API) | Component-based UI |
| Styling | Tailwind CSS | 3.x | Utility-first CSS |
| Component library | Headless UI for Vue | latest | Accessible primitives (modals, menus) |
| Icons | Heroicons / Lucide Vue | latest | SVG icons |
| Build tool | Vite | 5.x | Bundling, HMR |
| Database | MySQL | 8.x | Primary data store |
| ORM | Eloquent | bundled | Query builder + models |
| Real-time | Laravel Reverb | 1.x | Self-hosted WebSocket |
| Client WS | Laravel Echo + Pusher.js (Reverb-compatible) | latest | Subscribe to broadcasts |
| Auth scaffolding | Laravel Breeze (Inertia + Vue) | latest | Login, registration boilerplate |
| Email | Laravel Mail (SMTP driver) | bundled | Transactional emails |
| PDF | barryvdh/laravel-dompdf | latest | Clearance certificate generation |
| Excel/CSV export | maatwebsite/laravel-excel | latest | SuperAdmin reports |
| Queue | Laravel Queue (database driver) | bundled | Async email + notifications |
| Process supervisor | Supervisor (in Docker) | latest | Run `queue:work` and `reverb:start` |
| Cache/Session | File / Database | bundled | Avoid Redis for v1 |
| Testing | Pest | 3.x | Unit + feature tests |
| Browser testing | Playwright (optional) | latest | E2E for critical flows |
| Static analysis | Larastan (PHPStan for Laravel) | latest | Type safety |
| Code style | Laravel Pint | bundled | PHP formatting |
| JS lint/format | ESLint + Prettier | latest | Vue/JS formatting |
| Containerization | Docker + Docker Compose | latest | Dev parity, deployment |
| Deployment | Dokploy | latest | Docker orchestration on VPS |
| Web server | Nginx | latest | Reverse proxy + static files |
| PHP runtime | PHP-FPM 8.4 | latest | FastCGI |
| OS | Ubuntu 22.04 LTS | — | VPS base |

## Why These Choices

### Laravel 13
Mature, batteries-included PHP framework. Eloquent eliminates SQL injection by default. Built-in CSRF middleware, validation, queues, mail, and notifications. The legacy system already uses PHP, so the team's existing PHP knowledge transfers. Laravel 13 (released March 17, 2026) is the current LTS-supported version with security fixes through March 2028 and full PHP 8.5 compatibility.

### Inertia.js + Vue 3
Inertia provides SPA-like navigation without building a separate API. Controllers return Vue components with props, sessions still work, and CSRF is automatic. Vue 3's Composition API gives clean reactivity for complex UIs (filtered tables, dynamic forms, real-time dashboards).

### Tailwind CSS
Utility-first CSS scales well with component-based frameworks. No CSS naming bikeshedding. Pairs naturally with Vue components.

### Laravel Reverb
Official first-party Laravel WebSocket server. Self-hosted, free, no Pusher subscription. Production-ready and integrates seamlessly with Laravel Echo. Fully supported on Laravel 13.

### MySQL (not Postgres)
The legacy system uses MySQL. Keeping the same engine simplifies data migration and matches the team's familiarity. Postgres would be technically nicer but the migration risk isn't worth it.

### DomPDF (over Browsershot/Puppeteer)
Lightweight, no Chromium dependency on the VPS, sufficient for the simple A4 clearance certificate layout we need.

### Database queue driver (not Redis)
Single-VPS deployment with modest concurrent load. Database queue is dead-simple, requires no extra service, and works fine at this scale. Easy to swap for Redis later if needed.

## Versions to Pin

```json
{
  "php": "^8.4",
  "laravel/framework": "^13.0",
  "inertiajs/inertia-laravel": "^2.0",
  "laravel/reverb": "^1.0",
  "laravel/tinker": "^3.0",
  "laravel/sanctum": "^4.0",
  "intervention/image-laravel": "^4.0",
  "barryvdh/laravel-dompdf": "^3.0",
  "maatwebsite/excel": "^3.1",
  "tightenco/ziggy": "^2.0",
  "laravel/breeze": "^2.0"
}
```

```json
{
  "vue": "^3.4",
  "@inertiajs/vue3": "^2.0",
  "@vitejs/plugin-vue": "^6.0",
  "tailwindcss": "^3.4",
  "laravel-echo": "^2.3",
  "pusher-js": "^8.5",
  "@headlessui/vue": "^1.7",
  "@heroicons/vue": "^2.2"
}
```

## Local Development Requirements

- PHP 8.4 with extensions: `mbstring`, `pdo_mysql`, `gd`, `bcmath`, `intl`, `zip`, `xml`
- Composer 2.x
- Node.js 20 LTS + npm
- MySQL 8 (or MariaDB 10.6+)
- Git
- Docker Desktop (optional, for parity with production)
