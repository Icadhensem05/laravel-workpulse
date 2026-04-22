# Module 07: Team

Status: completed

## Scope

- team list for current user
- create team
- link member into team
- member option lookup for team lead

## Routes Added

- `GET /app-api/team/my`
- `POST /app-api/team`
- `POST /app-api/team/link`
- `GET /app-api/team/member-options`

## Tables Added

- `teams`
- `team_user`

## Verification

- migration refreshed successfully
- feature tests cover create, link, and list flows
