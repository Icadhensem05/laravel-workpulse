# Module 03: Claims Core

Project: `WorkPulse`

Date: `2026-03-27`

## Scope

The third backend module migrated into Laravel is the first native `Claims` slice.

This phase covers the core claim lifecycle only:

- list
- detail
- save draft
- update draft
- submit draft

Manager approval, finance verification, attachments, and print/export can be layered on top in the next slice of the same module.

## Implemented

- claim categories table
- claims table
- claim items table
- claim status logs table
- native Laravel claims API endpoints
- automatic claim number generation
- summary and total calculation
- ownership/admin visibility rules
- feature tests for create/list/detail/update/submit/authorization

## Routes Added

- `GET /app-api/claims`
- `POST /app-api/claims`
- `GET /app-api/claims/{claim}`
- `PUT /app-api/claims/{claim}`
- `POST /app-api/claims/{claim}/submit`

## Files Added

- `database/migrations/2026_03_27_000006_create_claims_core_tables.php`
- `app/Models/ClaimCategory.php`
- `app/Models/Claim.php`
- `app/Models/ClaimItem.php`
- `app/Models/ClaimStatusLog.php`
- `app/Http/Controllers/AppApi/ClaimsController.php`
- `tests/Feature/ClaimsModuleTest.php`
- `docs/backend/03_claims_module.md`

## Result

Laravel now owns the claim core domain for local development and future frontend rewiring.

The module currently supports:

- employee draft creation
- employee draft editing
- employee submission
- admin visibility
- calculated totals by category

## Next Slice Inside Claims

The next slice for this module should add:

1. attachments
2. manager actions
3. finance actions
4. payment state
5. print/export payload shaping
