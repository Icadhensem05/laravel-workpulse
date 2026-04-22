# Phase 1: Frontend Audit

Project: `WorkPulse`

Target rebuild: `Laravel + Tailwind`

Audit date: `2026-03-26`

## 1. Current Pages In Scope

Primary user-facing pages:

- `dashboard.php`
- `attendance.php`
- `leave.php`
- `claims.php`
- `profile.php`
- `tasks.php`
- `team.php`
- `report.php`

Admin-facing pages:

- `admin_daily.php`
- `admin_leaves.php`
- `admin_leave_alloc.php`
- `admin_assets.php`
- `admin_approvals.php`
- `admin_mapping.php`

## 2. Reusable UI Patterns Found

Global patterns already repeated across the app:

- Sidebar navigation
- Topbar with profile block and logout
- Hero/banner section
- Summary/stat cards
- Filter toolbars
- Tables with rounded container wrappers
- Form grids with mixed inline widths
- Modal/dialog overlays
- Tab navigation inside claim detail
- Date range actions and toolbar buttons
- Empty state blocks

## 3. Styling Audit Summary

Global styling source:

- `assets/app.css`

Page-level styling issues:

- Several pages still depend on inline `style=""` attributes for layout and spacing.
- Some pages embed their own `<style>` blocks, which will fight a shared design system.
- Layout decisions are spread between page markup and global CSS instead of reusable components.

Files with notable inline/page CSS usage:

- `claims.php`
- `profile.php`
- `attendance.php`
- `leave.php`
- `team.php`
- `report.php`
- `tasks.php`

## 4. UI Behavior Audit Summary

Shared runtime scripts:

- `assets/app-web.js`

Page-specific UI scripts:

- `assets/pages/dashboard.js.php`
- `assets/pages/attendance.js.php`
- `assets/pages/leave.js.php`
- `assets/pages/claims.js.php`
- `assets/pages/profile.js.php`
- `assets/pages/tasks.js.php`
- `assets/pages/team.js.php`
- `assets/pages/report.js.php`
- `assets/pages/admin_daily.js.php`
- `assets/pages/admin_approvals.js.php`

Observations:

- UI state is handled page by page instead of through shared components.
- Claims has the heaviest front-end logic because it includes tabs, modal detail, summary calculation, attachment workflow, preview, and print rendering.
- Report depends on external chart rendering.
- Several pages rely on direct DOM selection and imperative updates, which will need a cleaner component boundary in Laravel.

## 5. API Coverage Snapshot

The current PHP app already exposes usable APIs for major modules:

- Dashboard: `api/dashboard_overview.php`
- Attendance: `api/attendance_list.php`, `api/attendance_day.php`, `api/attendance_status.php`
- Leave: `api/leave_balance.php`, `api/leave_list.php`, `api/leave_create.php`, `api/leave_update_status.php`
- Claims: `api/claims_list.php`, `api/claims_detail.php`, `api/claims_save.php`, `api/claims_submit.php`, `api/claims_action.php`
- Profile: `api/profile_update.php`, `api/profile_upload.php`, `api/profile_password.php`
- Tasks: `api/tasks_board.php`, `api/tasks_detail.php`, `api/tasks_create.php`, `api/tasks_update_status.php`
- Team: `api/team_list.php`, `api/team_create.php`, `api/team_invite.php`
- Reports: `api/report_summary.php`, `api/report_series.php`

Implication:

- The Laravel frontend can be migrated screen by screen without blocking on a full backend rewrite.
- API normalization is still needed because response shape and error handling are not yet standardized.

## 6. Highest-Risk Areas For Migration

Areas most likely to slow down or break a direct UI migration:

- `claims.php`
  Reason: heavy inline styling, complex modal workflow, print layout, item calculations, approval actions

- `tasks.php`
  Reason: custom layout and denser interaction model

- `attendance.php` and `admin_daily.php`
  Reason: table-heavy layout with date filters and mobile overflow behavior

- `report.php`
  Reason: chart dependency and export-oriented UI

## 7. Most Frequent UI Building Blocks To Rebuild First

These should become reusable Blade/Tailwind components before page migration starts:

1. App shell
2. Sidebar
3. Topbar
4. Page header
5. Stat card
6. Filter toolbar
7. Button variants
8. Input/select/textarea
9. Table wrapper
10. Modal
11. Tabs
12. Empty state

## 8. Recommended Migration Order

Recommended page order based on value and complexity:

1. Dashboard
2. Attendance
3. Leave
4. Profile
5. Claims
6. Team
7. Tasks
8. Reports
9. Admin pages

Reasoning:

- Dashboard, attendance, leave, and profile give quick visible progress.
- Claims should come after the component system is stable.
- Admin and reports should migrate after the shared data-heavy patterns are proven.

## 9. Output Of Phase 1

This phase confirms:

- the pages in scope
- repeated UI patterns
- styling debt from inline CSS
- front-end behavior hotspots
- usable API surface
- migration order

Next phase:

- confirm the frontend stack choice and define the Tailwind implementation strategy
