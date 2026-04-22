# WorkPulse Backend Migration

This folder tracks backend/domain migration work inside the Laravel workspace.

Checklist:

- `module_migration_checklist.md`

## Module Status

1. `01_auth_module.md` - completed
   - native Laravel auth API added
   - local user/profile schema extended
   - session and password reset support confirmed in base schema
   - feature tests added
2. `02_profile_module.md` - completed
   - authenticated profile API added
   - password and photo update flows added
   - feature tests added
3. `03_claims_module.md` - completed
   - native claims core schema added
   - draft/detail/submit endpoints added
   - feature tests added
4. `03b_claims_workflow_module.md` - completed
   - manager/finance workflow actions added
   - attachment upload/delete added
   - payment recording added
   - feature tests added
5. `04_leave_module.md` - completed
   - leave balances and request endpoints added
   - admin approval endpoints added
   - allocation maintenance and seed defaults added
   - feature tests added
6. `05_attendance_module.md` - completed
   - attendance status and event endpoints added
   - admin daily attendance endpoints added
   - feature tests added
7. `06_tasks_module.md` - completed
   - task board and move endpoints added
   - feature tests added
8. `07_team_module.md` - completed
   - team create, link, and listing endpoints added
   - feature tests added
9. `08_assets_module.md` - completed
   - asset listing and assignment endpoints added
   - feature tests added
10. `09_reports_module.md` - completed
   - report summary and module datasets added
   - feature tests added
11. `10_admin_module.md` - completed
   - admin users, settings, approvals, and overview endpoints added
   - feature tests added
