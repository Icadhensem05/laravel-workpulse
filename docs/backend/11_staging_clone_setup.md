# Staging Copy Setup

Goal: use a local/staging copy of live WorkPulse data without pointing Laravel directly at production.

## Recommended flow

1. Export a DB dump from live.
2. Import that dump into a local MySQL database such as `workpulse_staging_copy`.
3. Point Laravel `.env` to that local copy.
4. Run Laravel against the copied data and polish parity there.
5. Only point to production after the Laravel app is stable.

## Files added for this flow

- `.env.staging-copy.example`
- `scripts/import-live-dump.ps1`
- `php artisan workpulse:staging:status`

## Local import steps

From the Laravel project root:

```powershell
Copy-Item .env.staging-copy.example .env
```

Adjust `.env` if your local MySQL credentials differ.

Import a dump:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\import-live-dump.ps1 -DumpPath C:\path\to\workpulse_live.sql
```

Then refresh Laravel caches and ensure schema compatibility:

```powershell
php artisan optimize:clear
php artisan migrate --force
php artisan workpulse:staging:status
```

## Important guardrails

- Do not run `php artisan db:seed` against a staging copy that should mirror live.
- Do not point `.env` directly to production while parity work is still ongoing.
- Keep `APP_ENV=staging` or `APP_ENV=local` while validating.
- Confirm `DB_HOST` is local or an internal staging host before testing writes.

## Quick validation checklist

- Login works through Auth2.
- Dashboard and attendance show copied data.
- Leave, claims, assets, team, tasks, reports all load from copied records.
- Admin access reflects the copied users/roles.
- Export buttons use Laravel-local behavior where already migrated.
