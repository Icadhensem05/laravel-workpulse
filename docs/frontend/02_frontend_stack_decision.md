# Phase 2: Frontend Stack Decision

Project: `WorkPulse`

Decision date: `2026-03-26`

## Final Stack Decision

WorkPulse frontend migration will use:

- `Laravel Blade`
- `Tailwind CSS v4`
- `Vite`
- `Vanilla JavaScript` for the initial rebuild
- `Axios` for API integration

## Chosen Option

Chosen approach:

- `Laravel Blade + Tailwind`

Not chosen for phase 1 of the rebuild:

- `Laravel + Inertia + React + Tailwind`

## Why Blade + Tailwind Was Chosen

This is the better fit for the current project because:

- the current application is already page-based PHP
- the migration goal is to replace frontend UI progressively, not rebuild the whole app architecture at once
- Blade keeps the move from legacy PHP to Laravel lower-risk
- Tailwind provides a faster design-system rebuild than maintaining large custom CSS from day one
- the team can migrate module by module without blocking on a full SPA rewrite

## Why Inertia + React Is Deferred

React is not rejected permanently, but it is deferred because:

- it adds a second migration at the same time: UI migration and app architecture migration
- current pages are still tightly coupled to PHP APIs and Blade-style rendering is the fastest bridge
- the immediate need is consistency, maintainability, and delivery speed

Future option:

- after the Blade/Tailwind frontend is stable, selected high-interaction modules such as claims or tasks can be evaluated for a React or Livewire rewrite if needed

## Frontend Implementation Strategy

The first migration wave will use:

- Blade layouts
- Blade partials
- Tailwind utility classes
- a small internal component layer using Blade components and shared class patterns

This means:

- no third-party CSS framework on top of Tailwind
- no dependency on shadcn/ui
- no JS-heavy UI framework in the first phase

## Component Strategy

Component strategy selected:

- `plain Tailwind utilities` plus a small reusable component layer

Use Tailwind directly for:

- spacing
- layout
- typography
- color
- borders
- responsive behavior

Create shared Blade components for:

- app shell
- sidebar
- topbar
- section header
- stat card
- button
- input
- select
- table shell
- modal shell
- tabs
- empty state

## Responsive Strategy

Responsive strategy selected:

- `desktop first for admin/data-heavy screens`
- `mobile-safe for all screens`

Rationale:

- WorkPulse currently behaves primarily like an internal desktop productivity tool
- several modules depend on dense tables, filters, and approval workflows
- mobile support is still required, but should be handled through adaptive layouts rather than forcing a mobile-first design on every screen

## Theme Strategy

Theme strategy selected:

- `dark corporate default`
- `light print-friendly surfaces where needed`

Rules:

- app shell, navigation, cards, and forms follow the dark corporate palette
- print layouts such as claim forms remain white-background and printer-safe
- theme tokens will be centralized before page migration starts

## JavaScript Strategy

The frontend will use:

- light DOM-driven JavaScript for simple interactive states
- Axios for API requests
- modular scripts per feature where needed

Avoid in early migration:

- large inline scripts inside Blade pages
- duplicated request logic across modules
- page-specific style and behavior mixed directly in the same file without separation

## API Strategy

Short-term API strategy:

- keep using existing PHP APIs where practical
- wrap access through a shared frontend API layer in Laravel assets

Medium-term API strategy:

- standardize response shape
- standardize auth/session handling
- move toward Laravel-managed routes or a normalized API gateway when backend migration starts

## Phase 2 Output

This phase locks the following decisions:

- Blade is the rendering layer
- Tailwind is the UI system
- Vite is the asset pipeline
- Axios is the request client
- dark corporate is the default application theme
- print surfaces stay light
- component-first Blade migration is the implementation path

Next phase:

- harden the Tailwind setup and define theme tokens
