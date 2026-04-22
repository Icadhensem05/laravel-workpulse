# Module 09: Reports

Status: completed

## Scope

- report summary
- attendance dataset
- leave dataset
- claims dataset

## Routes Added

- `GET /app-api/reports/summary`
- `GET /app-api/reports/attendance`
- `GET /app-api/reports/leave`
- `GET /app-api/reports/claims`

## Notes

- datasets are built from migrated native modules
- responses are export-ready, but file export endpoints are not added in this slice

## Verification

- migration refreshed successfully
- feature tests cover all report endpoints
