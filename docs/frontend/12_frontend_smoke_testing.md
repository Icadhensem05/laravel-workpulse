# Phase 12: Frontend Smoke Testing

Project: `WorkPulse`

Date: `2026-03-27`

## Purpose

Close the remaining frontend testing checklist with an automated smoke pass across desktop, tablet, and mobile viewports.

## Added

- Playwright config:
  - `playwright.config.cjs`
- Smoke spec:
  - `tests/e2e/frontend-smoke.spec.cjs`
- PHP built-in router helper for local testing:
  - `tests/router.php`

## Coverage

The smoke suite validates route rendering for:

- `/login`
- `/dashboard`
- `/attendance`
- `/leave`
- `/claims`
- `/profile`
- `/tasks`
- `/team`
- `/assets`
- `/report`
- `/admin`

Viewport coverage:

- desktop
- tablet
- mobile

Additional print coverage:

- claims form print preview PDF render on desktop Chromium

## Execution Notes

Because `php artisan serve` could not bind reliably on this machine, the suite was executed against the PHP built-in server with:

- `php -S 127.0.0.1:8765 tests/router.php`

Playwright browsers were installed locally and the suite was run with:

- `npx playwright test --config=playwright.config.cjs`

## Result

Latest run:

- `34 passed`
- `2 skipped`

Skipped tests:

- tablet print preview
- mobile print preview

Those skips are intentional because the print PDF smoke test is only meaningful once on desktop Chromium.

## What This Closes

This smoke pass is sufficient to close:

- visual check on desktop
- visual check on tablet
- visual check on mobile
- dark theme verification for the Laravel workspace
- claims print layout smoke test
- basic regression sweep across the migrated WorkPulse route set

## Remaining Limit

This is still a smoke/regression pass, not a pixel-perfect design review.

If needed later, a separate manual QA round can still compare:

- spacing refinements
- typography polish
- animation feel
- exact parity against the legacy PHP UI
