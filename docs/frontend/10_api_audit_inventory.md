# Phase 10: API Audit Inventory

Project: `WorkPulse`

Date: `2026-03-27`

## Purpose

This document records the current legacy PHP API surface in `/api`, groups endpoints by module, and marks which endpoints are already consumed by the Laravel frontend workspace.

Status labels used here:

- `used` = already called by the Laravel frontend
- `ready` = looks usable as-is for frontend work
- `needs normalization` = works, but response shape / naming / errors are inconsistent
- `admin-only` = intended for admin flow
- `special-case` = export, print, webhook, cron, mail, or integration helper
- `legacy helper` = internal helper or config file, not a direct frontend endpoint

## Module Inventory

### Auth

| Endpoint | Status | Notes |
| --- | --- | --- |
| `auth_login.php` | used, needs normalization | Login entry point for web and Laravel auth UI |
| `auth_logout.php` | used, ready | Ends current session |
| `auth_me.php` | used, ready | Current session / profile bootstrap |
| `auth_register.php` | ready, needs normalization | Present but not fully adopted in Laravel frontend flow yet |
| `auth_password_reset_request.php` | used, ready | Forgot password request |
| `auth_password_reset_check.php` | used, ready | Reset token validation |
| `auth_password_reset_complete.php` | used, ready | Complete password reset |
| `auth_api.php` | legacy helper | Auth helper/bootstrap |
| `auth2_client.php` | special-case | External auth integration helper |

Auth requirement:
- public for login / forgot / reset
- session-based for `auth_me.php` and `auth_logout.php`

### Dashboard

| Endpoint | Status | Notes |
| --- | --- | --- |
| `dashboard_overview.php` | used, needs normalization | Main dashboard payload; suitable for app shell hydration |

Auth requirement:
- logged-in user

### Attendance

| Endpoint | Status | Notes |
| --- | --- | --- |
| `attendance_list.php` | used, ready | Main attendance list with filters |
| `attendance_status.php` | used, ready | Next action / live status |
| `attendance_event.php` | used, needs normalization | Check-in / check-out action |
| `attendance_upsert.php` | used, needs normalization | Correction / edit save |
| `attendance_report_list.php` | used, ready | Daily admin/report listing |
| `attendance_daily_update.php` | used, admin-only | Admin daily edit |
| `attendance_report_export.php` | used, special-case | CSV/export trigger from Laravel frontend |
| `attendance_export.php` | used, special-case | Export by filter range |
| `attendance_report_export_month.php` | ready, special-case | Monthly export |
| `attendance_report_export_month_daily.php` | ready, special-case | Daily monthly export |
| `attendance_report_send.php` | special-case | Mail/send flow |
| `attendance_day.php` | ready | Single day helper |
| `attendance_user_month.php` | ready | Per-user monthly helper |
| `attendance_webhook.php` | special-case | External webhook intake |
| `attendance_sensepass.php` | special-case | External integration |
| `attendance_lib.php` | legacy helper | Internal attendance helper |

Auth requirement:
- logged-in user for personal endpoints
- admin/session permission for daily report update and admin reporting

### Leave

| Endpoint | Status | Notes |
| --- | --- | --- |
| `leave_balance.php` | used, ready | Leave balances by year |
| `leave_list.php` | used, ready | History/inbox list |
| `leave_create.php` | used, needs normalization | Leave apply |
| `leave_update_status.php` | used, needs normalization | Approval action |
| `leave_alloc_list.php` | used, admin-only | Allocation management |
| `leave_alloc_save.php` | used, admin-only | Allocation save |
| `leave_alloc_defaults.php` | used, admin-only | Seed default allocations |
| `leave_form.php` | used, special-case | Print form URL |

Auth requirement:
- logged-in user for personal flows
- manager/admin for approval/allocation flows

### Claims

| Endpoint | Status | Notes |
| --- | --- | --- |
| `claims_list.php` | used, ready | Claims listing/filter |
| `claims_detail.php` | used, ready | Claim detail including items/activity |
| `claims_save.php` | used, needs normalization | Draft save / edit |
| `claims_submit.php` | used, needs normalization | Submission action |
| `claims_action.php` | used, needs normalization | Manager/finance actions |
| `claim_attachment_upload.php` | used, needs normalization | Multipart upload |
| `claim_attachment_delete.php` | used, needs normalization | Attachment removal |
| `claims_lib.php` | legacy helper | Internal claims helper/bootstrap |

Auth requirement:
- logged-in user
- role-based access for manager/finance/admin actions

Print/export note:
- the Laravel frontend currently prints from the hydrated claim detail payload
- there is no dedicated claims print/export endpoint in the legacy API at this stage

### Profile

| Endpoint | Status | Notes |
| --- | --- | --- |
| `profile_update.php` | used, needs normalization | Profile save |
| `profile_password.php` | used, needs normalization | Password change |
| `profile_upload.php` | used, needs normalization | Avatar upload |
| `get_user.php` | ready, needs normalization | Older helper; avoid for new Laravel work where `auth_me.php` is enough |

Auth requirement:
- logged-in user

### Tasks

| Endpoint | Status | Notes |
| --- | --- | --- |
| `tasks_board.php` | used, ready | Board payload |
| `tasks_create.php` | used, needs normalization | Task creation |
| `tasks_move.php` | used, needs normalization | Status/column move |
| `tasks_detail.php` | ready | Detail payload, not yet wired in Laravel |
| `tasks_activity.php` | ready | Activity stream, not yet wired in Laravel |
| `tasks_update_status.php` | ready | May overlap with move/update flows |
| `tasks_delete.php` | ready | Destructive action; not yet wired |
| `tasks_permissions.php` | ready | Permission helper |
| `tasks_subtask_create.php` | ready | Subtask create |
| `tasks_subtask_update.php` | ready | Subtask update |
| `tasks_subtask_delete.php` | ready | Subtask delete |

Auth requirement:
- logged-in user
- team/ownership permissions vary by action

### Team

| Endpoint | Status | Notes |
| --- | --- | --- |
| `team_my.php` | used, ready | Team roster/current team |
| `team_create.php` | used, needs normalization | Create team |
| `team_link.php` | used, needs normalization | Link member to team |
| `team_members_options.php` | used, ready | Member picker options |
| `team_list.php` | used, admin-only | Full team/user listing for admin workspace |
| `team_invite.php` | ready | Invite flow, not yet wired |

Auth requirement:
- logged-in user
- admin/manager for broader team management

### Assets

| Endpoint | Status | Notes |
| --- | --- | --- |
| `assets_list.php` | used, ready | Main asset list |
| `asset_create.php` | used, needs normalization | Create asset |
| `asset_update_status.php` | used, needs normalization | Status update |
| `assets_admin_list.php` | used, admin-only | Oversight list |
| `asset_availability.php` | ready | Availability helper |
| `asset_book.php` | ready | Booking create |
| `asset_bookings_my.php` | ready | My bookings |
| `asset_bookings_pending.php` | ready, admin-only | Pending approvals |
| `asset_booking_cancel.php` | ready | Cancel booking |
| `asset_booking_update.php` | ready | Update booking |
| `office_assets_list.php` | ready | Office-asset list variant |
| `office_assets_create.php` | ready | Office-asset create variant |
| `office_assets_export.php` | used, special-case | Export trigger from Laravel frontend |

Auth requirement:
- logged-in user
- admin for oversight and some status flows

### Reports

| Endpoint | Status | Notes |
| --- | --- | --- |
| `report_summary.php` | used, ready | Summary cards |
| `report_series.php` | ready | Trend/series data, not yet wired |
| `attendance_report_list.php` | used, ready | Shared attendance reporting dataset |
| `attendance_report_export.php` | used, special-case | Export action |
| `food_allowance_report.php` | ready | Specialized report |
| `food_allowance_export.php` | ready, special-case | Specialized export |

Auth requirement:
- logged-in user
- some reports may be restricted by role/scope

### Admin / Settings / Approvals

| Endpoint | Status | Notes |
| --- | --- | --- |
| `approvals_list.php` | used, admin-only | Approval mapping list |
| `approvals_set.php` | used, admin-only | Approval mapping save |
| `settings_get.php` | used, admin-only | App settings load |
| `settings_update.php` | used, admin-only | App settings save |
| `admin_link_person.php` | used, admin-only | Manual person mapping |
| `api_keys_list.php` | ready, admin-only | API key management |
| `api_keys_create.php` | ready, admin-only | API key create |
| `api_keys_revoke.php` | ready, admin-only | API key revoke |

Auth requirement:
- admin/session with elevated permission

### Resume / Misc Feature Endpoints

| Endpoint | Status | Notes |
| --- | --- | --- |
| `resume_get.php` | ready | Resume/profile sub-feature |
| `resume_save.php` | ready | Resume save |
| `resume_default.php` | ready | Resume defaults |

Auth requirement:
- logged-in user

### Mail / Config / Infra / Helpers

| Endpoint | Status | Notes |
| --- | --- | --- |
| `send_mail.php` | special-case | Mail trigger helper |
| `mail_test.php` | special-case | Manual mail test |
| `mailer.php` | legacy helper | Internal mail helper |
| `mailer_phpmailer.php` | legacy helper | Internal mail helper |
| `config_mail.php` | legacy helper | Mail config |
| `cron_notify.php` | special-case | Scheduled task |
| `cron_food_allowance.php` | special-case | Scheduled task |
| `db.php` | legacy helper | Database bootstrap |
| `openapi.yaml` | special-case | API documentation source |
| `postman_collection.json` | special-case | API collection |
| `error_log` | special-case | Runtime artifact, not an endpoint |

## Laravel Frontend Coverage Summary

Endpoints already used by the Laravel frontend:

- Auth:
  - `auth_login.php`
  - `auth_logout.php`
  - `auth_me.php`
  - `auth_password_reset_request.php`
  - `auth_password_reset_check.php`
  - `auth_password_reset_complete.php`
- Dashboard:
  - `dashboard_overview.php`
- Attendance:
  - `attendance_list.php`
  - `attendance_status.php`
  - `attendance_event.php`
  - `attendance_upsert.php`
  - `attendance_report_list.php`
  - `attendance_daily_update.php`
  - `attendance_report_export.php`
  - `attendance_export.php`
- Leave:
  - `leave_balance.php`
  - `leave_list.php`
  - `leave_create.php`
  - `leave_update_status.php`
  - `leave_alloc_list.php`
  - `leave_alloc_save.php`
  - `leave_alloc_defaults.php`
  - `leave_form.php`
- Claims:
  - `claims_list.php`
  - `claims_detail.php`
  - `claims_save.php`
  - `claims_submit.php`
  - `claims_action.php`
  - `claim_attachment_upload.php`
  - `claim_attachment_delete.php`
- Profile:
  - `profile_update.php`
  - `profile_password.php`
  - `profile_upload.php`
- Tasks:
  - `tasks_board.php`
  - `tasks_create.php`
  - `tasks_move.php`
- Team:
  - `team_my.php`
  - `team_create.php`
  - `team_link.php`
  - `team_members_options.php`
  - `team_list.php`
- Assets:
  - `assets_list.php`
  - `asset_create.php`
  - `asset_update_status.php`
  - `assets_admin_list.php`
  - `office_assets_export.php`
- Reports:
  - `report_summary.php`
  - `attendance_report_list.php`
  - `attendance_report_export.php`
- Admin:
  - `approvals_list.php`
  - `approvals_set.php`
  - `settings_get.php`
  - `settings_update.php`
  - `admin_link_person.php`

## Normalization Priorities

The highest-value endpoints to normalize next are:

1. `claims_save.php`, `claims_submit.php`, `claims_action.php`
2. `attendance_event.php`, `attendance_upsert.php`, `attendance_daily_update.php`
3. `leave_create.php`, `leave_update_status.php`, `leave_alloc_save.php`
4. `profile_update.php`, `profile_password.php`, `profile_upload.php`
5. `asset_create.php`, `asset_update_status.php`, `team_create.php`, `team_link.php`, `tasks_create.php`, `tasks_move.php`

## Recommended Contract Targets

The legacy APIs should gradually converge toward:

- success:
  - `{ success: true, data: ..., message?: ... }`
- validation failure:
  - `{ success: false, message: 'Validation failed', errors: { field: ['...'] } }`
- general failure:
  - `{ success: false, message: '...' }`
- pagination:
  - `{ success: true, data: [...], meta: { page, per_page, total } }`
- dates:
  - ISO-friendly fields for APIs, UI formatting in frontend
- money:
  - numeric values in API, currency formatting in frontend

## Result

The API surface is now documented well enough to:

- close the API audit portion of the frontend checklist
- separate real frontend endpoints from helpers and special-case scripts
- identify which endpoints are already part of the Laravel migration
- highlight normalization work that still belongs to backend/API cleanup
