# Module 01: Auth

Project: `WorkPulse`

Date: `2026-03-27`

## Scope

The first backend module migrated into Laravel is `Auth`.

This module was implemented as a native Laravel backend foundation without forcing the rest of the legacy module APIs to migrate in the same step.

## Implemented

- native Laravel auth JSON endpoints under `/app-api/auth/*`
- user schema extended for WorkPulse profile fields
- database-backed sessions and password reset support from the Laravel base schema
- local demo seed account
- feature tests for register, login, me, logout, and forgot-password

## Routes Added

- `POST /app-api/auth/login`
- `POST /app-api/auth/register`
- `GET /app-api/auth/me`
- `POST /app-api/auth/logout`
- `POST /app-api/auth/forgot-password`
- `GET /app-api/auth/reset-password/check`
- `POST /app-api/auth/reset-password`

## Files Added

- `app/Http/Controllers/AppApi/AuthController.php`
- `database/migrations/2026_03_27_000005_add_workpulse_profile_fields_to_users_table.php`
- `tests/Feature/AuthModuleTest.php`
- `docs/backend/01_auth_module.md`

## Files Updated

- `routes/web.php`
- `app/Models/User.php`
- `database/seeders/DatabaseSeeder.php`
- `resources/js/bootstrap.js`
- `resources/views/components/layouts/app.blade.php`
- `resources/views/components/layouts/auth.blade.php`

## Notes

- The current frontend shell still consumes legacy module APIs for dashboard, attendance, leave, claims, tasks, team, assets, and reports.
- This auth module is the first native Laravel backend foundation layer for later module migration.
- The local demo seed account is:
  - `admin@workpulse.test`
  - `Password123!`

## Next Logical Module

After auth, the next highest-value backend module is usually:

1. `Profile`
2. `Claims`
3. `Leave`

`Profile` is the cleanest next step because it already depends directly on authenticated user data and has a smaller domain surface than claims or leave.
