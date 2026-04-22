# Module 03B: Claims Workflow

Status: completed

## Scope

- manager review actions
- finance approval action
- payment recording
- claim attachment upload/delete
- workflow-aware feature coverage

## Routes Added

- `POST /app-api/claims/{claim}/action`
- `POST /app-api/claims/{claim}/attachments`
- `DELETE /app-api/claims/{claim}/attachments/{attachment}`

## Workflow States Implemented

- `draft`
- `submitted`
- `pending_finance_verification`
- `returned_for_amendment`
- `rejected`
- `approved`
- `paid`

## Actions Implemented

- `manager_approve`
- `manager_reject`
- `manager_return`
- `finance_approve`
- `mark_paid`

## Tables Added

- `claim_attachments`
- `claim_payments`

## Notes

- current workflow authority is mapped to `admin` for both manager and finance stages
- attachment upload is limited to editable claims
- payment record is created only when claim is marked paid
- claim detail response now includes attachments and payments

## Verification

- migration refreshed successfully
- feature tests cover attachment upload/delete and workflow progression through paid state
