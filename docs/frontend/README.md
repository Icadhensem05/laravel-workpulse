# WorkPulse Frontend Migration

This folder tracks the Laravel + Tailwind frontend migration phase by phase.

## Phase Status

1. `01_frontend_audit.md` - completed
2. `02_frontend_stack_decision.md` - completed
3. `03_tailwind_setup_hardening.md` - completed
4. `04_design_system_foundation.md` - completed
5. `05_global_layout_components.md` - completed
6. `06_reusable_ui_components.md` - completed
7. `07_page_level_migration_dashboard.md` - completed
   - `07_page_level_migration_attendance.md` - completed
   - `07_page_level_migration_remaining_modules.md` - completed
8. Module and feature migration - completed
9. `09_api_frontend_integration_foundation.md` - in progress
   - auth pages - integrated (login / forgot password / reset password / logout)
   - admin workspace - integrated (users / approvals / assets / settings / mapping)
   - dashboard - integrated
   - attendance - integrated including admin daily view
   - leave - integrated including apply / approval / allocation
   - claims - integrated including draft / submit / actions / attachments
   - profile - integrated
   - tasks - integrated
   - team - integrated
   - assets - integrated
   - reports - integrated
   - global feedback / normalized error handling - implemented
   - retry/fallback for GET hydration - implemented
   - upload progress for core file flows - implemented
   - auth/app-shell accessibility and interaction polish - implemented
   - mobile stacked-table fallback for data-heavy pages - implemented
   - contrast, hover/active state, and landscape tablet polish - implemented
   - field-level auth validation states and reusable form error styling - implemented
   - long-form section grouping and table numeric/action alignment helpers - implemented
   - slow-network feedback handling and shared field spacing cleanup - implemented
   - dashboard leave summary, register page UI, asset detail modal, and team detail toggle - implemented
   - task drag/drop visual state - implemented
10. `10_api_audit_inventory.md` - completed
   - legacy PHP endpoints inventoried by module
   - frontend-consumed endpoints identified
   - auth requirement and response-shape observations documented
   - normalization priorities recorded
11. `11_api_contract_normalization.md` - completed
   - shared frontend API contract adapter implemented
   - read/mutation response normalization documented
   - pagination and validation normalization rules recorded
12. `12_frontend_smoke_testing.md` - completed
   - Playwright smoke suite added
   - desktop / tablet / mobile route rendering verified
   - claims print preview smoke test verified

## Notes

- The audit is based on the current PHP WorkPulse application in the parent project.
- The Laravel app in this folder will be the target frontend rebuild workspace.
- Start implementation after each phase is documented and signed off.
- API audit and contract notes now live in `10_api_audit_inventory.md`.
- Frontend contract adapter rules now live in `11_api_contract_normalization.md`.
- Smoke testing notes now live in `12_frontend_smoke_testing.md`.
