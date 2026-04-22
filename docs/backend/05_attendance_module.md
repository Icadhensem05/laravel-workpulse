# Module 05: Attendance

Status: completed

## Scope

- employee attendance list and current status
- check in and check out event recording
- employee correction/upsert
- admin daily attendance overview and update

## Routes Added

- `GET /app-api/attendance/entries`
- `GET /app-api/attendance/status`
- `POST /app-api/attendance/event`
- `POST /app-api/attendance/entries/upsert`
- `GET /app-api/attendance/admin/daily`
- `POST /app-api/attendance/admin/daily`

## Tables Added

- `attendance_entries`

## Verification

- migration refreshed successfully
- feature tests cover check in/out, upsert, and admin daily update
