# Phase 3: Tailwind Setup Hardening

Project: `WorkPulse`

Decision date: `2026-03-26`

## What Was Implemented

The Laravel rebuild workspace now has a stronger Tailwind foundation:

- `resources/css/app.css` upgraded from the default starter file into a WorkPulse-specific theme foundation
- `tailwind.config.js` added for explicit content scanning and future extension
- dark corporate base styling defined in the shared CSS layer
- theme tokens defined for colors, spacing, radius, shadows, breakpoints, and container width

## Theme Tokens Added

The following token groups are now defined:

- brand palette
- ink/neutral palette
- semantic colors for success, warning, and danger
- radius scale for panels, controls, and pills
- shadow presets for panels, soft surfaces, and buttons
- extra spacing utilities
- extended breakpoint and container width

## CSS Architecture Chosen

The CSS foundation now follows this structure:

1. `@theme`
2. `@layer base`
3. `@layer components`
4. `@layer utilities`

This is the baseline that later phases will build on.

## Shared Foundation Classes Added

The setup now includes shared classes for:

- page wrapper
- app container
- primary panel
- secondary panel
- input
- select
- primary button
- secondary button
- badge
- section title
- section copy

These are not the final component library yet, but they remove the need to start page migration from raw utility noise on every screen.

## Important Implementation Note

Tailwind v4 supports CSS-first configuration, so:

- `app.css` is now the main theme source
- `tailwind.config.js` exists mainly for explicit scanning and future extension

## Output Of Phase 3

This phase gives the project:

- a stable Tailwind base
- WorkPulse-specific theme tokens
- reusable foundation classes
- a clean starting point for the design-system phase

Next phase:

- define the design system foundation in a more formal way and start locking button, input, badge, spacing, and typography rules
