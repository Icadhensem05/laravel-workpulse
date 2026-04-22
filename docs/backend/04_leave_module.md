# Module 04: Leave

Status: completed

## Scope

- leave balance summary
- employee leave application
- admin approval or rejection
- leave allocation maintenance
- default allocation seeding

## Routes Added

- `GET /app-api/leave/balances`
- `GET /app-api/leave/requests`
- `POST /app-api/leave/requests`
- `POST /app-api/leave/requests/{leaveRequest}/status`
- `GET /app-api/leave/allocations`
- `POST /app-api/leave/allocations`
- `POST /app-api/leave/allocations/seed-defaults`

## Tables Added

- `leave_types`
- `leave_allocations`
- `leave_requests`

## Notes

- current approval authority is mapped to `admin`
- approved leave consumption is calculated from `leave_requests.days_count`
- allocation defaults are seeded from `leave_types.default_days`
- part-day supports `full`, `half_am`, and `half_pm`

## Verification

- migration refreshed successfully
- feature tests cover balances, request creation, approval, and allocation maintenance
