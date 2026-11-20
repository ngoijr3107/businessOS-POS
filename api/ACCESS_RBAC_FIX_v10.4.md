# MauzoHub v10.4 — Permanent RBAC / Roles Access Fix

## Fixed
- Administrator and Super Admin roles now receive the full generated permission set, including roles.* and permissions.*.
- Existing Administrator accounts are repaired automatically whenever `AccessManager::ensureDefaults()` runs.
- `mauzohub:access-sync` repairs administrator role and direct permissions and clears the Spatie permission cache.
- Role-list authorization now uses the same `User::can()` authorization path as the rest of the API, and Super Admin is explicitly honored.
- Existing installations do not need `migrate:fresh`.

## Upgrade existing installation
Run from backend:

```bash
php artisan mauzohub:access-sync --all-users
php artisan permission:cache-reset
php artisan optimize:clear
```

Then log out and log back in.

## Verify

```bash
php artisan tinker
```

```php
$user = App\\Models\\User::where('email', 'admin@mauzohub.test')->first();
$user->getRoleNames();
$user->can('users.view');
$user->can('roles.view');
$user->can('permissions.view');
```

All three permission checks should return `true` for the Administrator account.
