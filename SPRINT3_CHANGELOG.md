# Dura Cabs — Sprint 3 Fast-Track

## Completed

- Split the large `resources/js/app.js` entry file into focused modules:
  - `resources/js/modules/homepage.js`
  - `resources/js/modules/rides.js`
- Kept `resources/js/app.js` as a small Vite entry point.
- Extracted shared design tokens into `resources/css/foundation/tokens.css`.
- Preserved existing Blade views, Livewire events, inline handler exports, booking logic, and backend APIs.
- Removed `.DS_Store`, `__MACOSX`, and two unreferenced duplicate legacy Vite build folders (`public/new/buildx`, `public/new/xxbuild`).
- Removed `.env` from the distributable ZIP to avoid exposing credentials. Copy `.env.example` to `.env` locally.

## Intentionally deferred

The 1,800+ line service search Blade view was not aggressively split in this fast-track build because its Livewire state and inline bindings require browser-level regression testing. A risky visual or booking-flow rewrite was avoided.

## Local setup

```bash
copy .env.example .env
composer install
php artisan key:generate
npm install
npm run build
php artisan optimize:clear
php artisan serve
```
