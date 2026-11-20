# MauzoHub — Laravel 12 + Vue 3 starter

A production-oriented starter for a Client, Sales, Invoices, Payments and Receivables Management System.

## Architecture

- Backend: Laravel 12, PHP 8.2+, MySQL, Sanctum, Spatie Permission
- Frontend: Vue 3, TypeScript, Vite, Pinia, Axios, Tailwind CSS
- API prefix: `/api/v1`
- Authentication: Sanctum bearer tokens for the included SPA starter
- Tenant boundary: every business-owned record carries `company_id`

## Backend setup

1. Create a fresh Laravel 12 application in `backend/` (or use an existing Laravel 12 installation).
2. Copy the contents of this `backend/` directory over the fresh application, preserving Laravel's standard `artisan`, `storage`, `vendor`, `public`, and other framework files.
3. Run:

```bash
composer install
php artisan install:api
php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider" --tag="permission-migrations"
cp .env.example .env
php artisan key:generate
```

4. Configure MySQL in `.env`.
5. Run:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Demo credentials after seeding:

- Email: `admin@mauzohub.test`
- Password: `password`

For a real deployment, immediately change this password and remove the demo credentials.

## Frontend setup

```bash
cd frontend
npm install
npm run dev
```

Optional `.env`:

```env
VITE_API_URL=http://localhost:8000/api/v1
```

## Included API

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`
- `GET /api/v1/dashboard`
- Client CRUD + client statement
- Product CRUD
- Sales list/show/create
- Invoice CRUD/create
- Payments list/show/create
- Receivables report

## Important next production steps

- Add Laravel Form Request classes instead of controller validation for every endpoint.
- Add Policies for every resource and enforce role/permission checks.
- Add rate limiting and authentication hardening.
- Add invoice/receipt PDF generation and email/SMS/WhatsApp adapters.
- Add purchase, supplier, inventory, expense, branch and multi-tenant administration modules.
- Add queued notifications, scheduled reminders and automated backups.
- Add feature tests for financial calculations and payment allocation.
- Add proper audit logging around all financial mutations.
- Add database indexes based on production query patterns.
