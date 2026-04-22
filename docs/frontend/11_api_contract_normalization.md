# Phase 11: Frontend API Contract Normalization

Project: `WorkPulse`

Date: `2026-03-27`

## Purpose

The legacy PHP APIs still return mixed response shapes. This phase standardizes the contract at the Laravel frontend API layer so page-level code no longer depends on raw endpoint inconsistencies.

## What Was Added

The shared API client now includes:

- `normalizeValidationErrors(...)`
- `normalizePaginationMeta(...)`
- `normalizeReadPayload(...)`
- `normalizeMutationPayload(...)`
- `getNormalized(...)`
- `postNormalized(...)`
- `postMultipartNormalized(...)`

File:

- `resources/js/api/client.js`

## Normalization Rules

### Read responses

Every read endpoint now passes through a shared normalizer that yields:

- `success`
- `message`
- `errors`
- `meta`
- `data`
- `raw`

Module API files then return `result.data` so page-layer code can keep consuming stable domain payloads such as:

- dashboard overview object
- attendance rows
- leave balances and lists
- claims detail payload
- profile bootstrap payload

### Mutation responses

Every create/update/action/upload flow now passes through a shared mutation normalizer that yields:

- `success`
- `message`
- `errors`
- `meta`
- `data`
- `raw`

Module API files expose the normalized metadata while preserving the object keys already expected by the page layer.

This means pages can still access fields like:

- `claim.id`
- `url`
- `options`
- `team_id`

while also getting consistent:

- `success`
- `message`
- `errors`
- `meta`

## Modules Updated

- `resources/js/api/auth.js`
- `resources/js/api/dashboard.js`
- `resources/js/api/attendance.js`
- `resources/js/api/leave.js`
- `resources/js/api/claims.js`
- `resources/js/api/profile.js`
- `resources/js/api/tasks.js`
- `resources/js/api/team.js`
- `resources/js/api/assets.js`
- `resources/js/api/reports.js`
- `resources/js/api/admin.js`

## Contract Conventions Now Used By Frontend

### Success shape

The frontend adapter now treats success responses as:

- `{ success, message, data, meta, errors }`

even if the legacy endpoint originally returned:

- raw objects
- `{ message, ... }`
- `{ error: ... }`
- `{ data: ... }`

### Error shape

The shared mapper still resolves:

- `message`
- `error`
- status-based fallbacks

and now validation payloads are flattened more consistently.

### Validation shape

The adapter now normalizes:

- `errors: []`
- `errors: { field: [...] }`

into a frontend-safe list while preserving the original payload in `raw`.

### Pagination shape

The adapter now reads pagination from:

- `meta`
- `pagination`
- `current_page`
- `per_page`
- `total`

and converts it into:

- `{ page, per_page, total }`

### Date/time and money handling

Dates and money are intentionally left as raw API values in the adapter layer.

Current frontend rule:

- API layer transports raw scalar values
- page/UI layer formats dates and currency for display

This avoids accidental timezone or currency formatting drift while the backend remains legacy and mixed.

## Result

The Laravel frontend now consumes a stable API adapter contract even though the backend PHP endpoints are still heterogeneous.

This closes the main frontend-facing contract standardization work without requiring a full backend rewrite first.
