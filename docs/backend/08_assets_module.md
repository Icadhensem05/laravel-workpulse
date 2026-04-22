# Module 08: Assets

Status: completed

## Scope

- asset listing
- asset creation
- assign, return, maintenance status update

## Routes Added

- `GET /app-api/assets`
- `POST /app-api/assets`
- `POST /app-api/assets/{asset}/status`

## Tables Added

- `assets`

## Verification

- migration refreshed successfully
- feature tests cover create and assign flow
