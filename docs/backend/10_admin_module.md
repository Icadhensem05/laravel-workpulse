# Module 10: Admin Settings And Configuration

Status: completed

## Scope

- admin overview counters
- user listing
- approval settings
- app settings get and update
- manual employee code linking

## Routes Added

- `GET /app-api/admin/overview`
- `GET /app-api/admin/users`
- `GET /app-api/admin/approvals`
- `POST /app-api/admin/approvals`
- `GET /app-api/admin/assets`
- `GET /app-api/admin/settings`
- `POST /app-api/admin/settings`
- `POST /app-api/admin/link-person`

## Tables Added

- `app_settings`
- `approval_settings`

## Verification

- migration refreshed successfully
- feature tests cover settings, approvals, and person linking
