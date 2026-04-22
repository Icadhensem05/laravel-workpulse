# Phase 4: Design System Foundation

Project: `WorkPulse`

Decision date: `2026-03-26`

## Objective

Lock the visual rules that every Laravel + Tailwind screen will share before page migration starts.

## Design System Decisions

### Color System

Brand palette:

- `brand-50` to `brand-950`
- used for primary actions, focus states, active navigation, and key accents

Neutral palette:

- `ink-50` to `ink-950`
- used for backgrounds, cards, borders, text hierarchy, and overlays

Semantic palette:

- `success`
- `warning`
- `danger`

These are used for validation, alerts, and status badges.

### Typography Hierarchy

Typography levels now formalized:

- `wp-page-title`
- `wp-section-title`
- `wp-section-copy`
- `wp-label`
- `wp-helper`
- `wp-table-head`

This gives the migration a consistent type scale for page headers, section blocks, forms, and table-heavy screens.

### Spacing Strategy

Spacing is based on Tailwind spacing with a few explicit additions:

- `spacing-18`
- `spacing-22`

This keeps the system mostly standard while allowing wider enterprise layouts.

### Radius Strategy

Radius tokens locked:

- `radius-panel`
- `radius-control`
- `radius-pill`

Usage:

- panels and cards use `radius-panel`
- inputs/selects use `radius-control`
- buttons and badges use `radius-pill`

### Shadow Strategy

Shadow tokens locked:

- `shadow-panel`
- `shadow-soft`
- `shadow-button`

Usage:

- major content surfaces use `shadow-panel`
- secondary surfaces use `shadow-soft`
- primary call-to-action buttons use `shadow-button`

## Variant Rules Added

### Buttons

Button variants defined:

- `wp-btn`
- `wp-btn-secondary`
- `wp-btn-ghost`
- `wp-btn-danger`

### Inputs

Input variants defined:

- `wp-input`
- `wp-input-error`
- `wp-input-disabled`
- `wp-select`

### Badges

Badge variants defined:

- `wp-badge`
- `wp-badge-neutral`
- `wp-badge-info`
- `wp-badge-success`
- `wp-badge-warning`
- `wp-badge-danger`

## Output Of Phase 4

This phase gives the project:

- a locked UI token direction
- a usable typography scale
- formal button/input/badge variants
- a design foundation stable enough for layout and component implementation

Next phase:

- build the global layout components: app shell, sidebar, topbar, page wrapper, cards, and common structural blocks
