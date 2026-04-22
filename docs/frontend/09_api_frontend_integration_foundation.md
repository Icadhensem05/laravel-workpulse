# Phase 9: API Frontend Integration Foundation

Project: `WorkPulse`

Date: `2026-03-26`

## What Was Implemented

The Laravel frontend workspace now includes the first shared API integration layer for the legacy PHP backend.

## Implemented Pieces

- shared Axios API client
- shared API error mapper
- legacy API base URL config via Laravel config
- page bootstrap wiring for frontend hydration
- first live integration targets for:
  - dashboard overview
  - attendance list, status, check-in/check-out flow, edit/upsert, admin daily view, and CSV export trigger
  - leave balances, apply flow, approval inbox, and allocation maintenance
  - claims listing, detail, draft save, submission, approval actions, and attachment flow
  - current profile, profile update, password update, and photo upload
  - tasks board load/create/move
  - team roster load/create/link
  - assets list/create/status update
  - reports summary/list/export trigger

## Files Added

- `config/workpulse.php`
- `resources/js/api/client.js`
- `resources/js/api/dashboard.js`
- `resources/js/api/attendance.js`
- `resources/js/api/claims.js`
- `resources/js/api/leave.js`
- `resources/js/api/profile.js`
- `resources/js/pages/dashboard.js`
- `resources/js/pages/attendance.js`
- `resources/js/pages/claims.js`
- `resources/js/pages/leave.js`
- `resources/js/pages/profile.js`
- `docs/frontend/09_api_frontend_integration_foundation.md`

## Files Updated

- `resources/js/app.js`
- `resources/css/app.css`
- `resources/views/components/layouts/app.blade.php`
- `resources/views/dashboard.blade.php`
- `resources/views/attendance.blade.php`
- `resources/views/claims.blade.php`
- `resources/views/leave.blade.php`
- `resources/views/profile_laravel.blade.php`

## Result

The Laravel frontend is no longer purely seeded/static.

It now has:

- a reusable request layer
- API environment configuration
- page-based hydration entry points
- initial integration with the existing PHP APIs
- working attendance refresh, next-action status, event trigger, date/range filter, edit save, admin daily view, and export flow
- working leave apply, personal list, admin approval, and allocation flow
- working claim draft workflow from the Laravel frontend
- working claim submission / review / finance action triggers from the Laravel frontend
- working claim attachment upload and delete flow from the Laravel frontend
- working tasks/team/assets/reports data wiring for the Laravel frontend shell
- normalized API error mapping for common legacy response shapes
- a global feedback surface for success, warning, and failure messages
- unauthorized/session-expired notices shown at the app-shell level instead of console-only handling
- retry-aware GET requests for the main read-only API surfaces
- upload progress feedback for claim attachments and profile photo updates

## Remaining API Work

- standardize response shapes across the legacy PHP APIs
- run slow-network verification across the main flows
- keep the endpoint/module inventory updated in `docs/frontend/10_api_audit_inventory.md`
- keep the frontend API adapter contract documented in `docs/frontend/11_api_contract_normalization.md`
