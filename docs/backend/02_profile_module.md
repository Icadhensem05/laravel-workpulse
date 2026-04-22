# Module 02: Profile

Project: `WorkPulse`

Date: `2026-03-27`

## Scope

The second backend module migrated into Laravel is `Profile`.

This module builds on the native Laravel auth foundation and provides authenticated profile read/update/password/photo flows.

## Implemented

- authenticated profile JSON endpoint
- profile update endpoint
- password update endpoint
- profile photo upload endpoint
- feature tests for profile fetch, update, password change, and photo upload

## Routes Added

- `GET /app-api/profile`
- `PUT /app-api/profile`
- `PUT /app-api/profile/password`
- `POST /app-api/profile/photo`

## Files Added

- `app/Http/Controllers/AppApi/ProfileController.php`
- `tests/Feature/ProfileModuleTest.php`
- `docs/backend/02_profile_module.md`

## Result

Laravel now owns the profile domain contract for:

- current authenticated user profile
- personal detail updates
- password change
- photo upload

## Next Logical Module

After profile, the next highest-value backend module is:

1. `Claims`
2. `Leave`
3. `Attendance`
