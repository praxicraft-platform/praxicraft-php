# Changelog

## 1.0.0

### Breaking

- Rename assessment task methods from `listCases` / `attachCases` / `replaceCases` / `removeCase` to `listTasks` / `attachTasks` / `replaceTasks` / `removeTask`.
- Wire paths use `/tasks/` instead of `/cases/`; JSON keys use `tasks`, `task_id`, and `assessment_task_id`.

## 0.1.1

- ci: auto-bump releases with GitHub Release + package publish

## [0.1.0] — 2026-08-21

### Added

- Initial Assess Public API SDK.
- `Client` with Bearer API-key auth (`PRAXICRAFT_API_KEY` / `PRAXICRAFT_API_BASE_URL`).
- Automatic retries on `429` / `5xx` / transport errors (default 2), honouring `Retry-After`.
- Typed / mapped errors from `{ error: { code, message } }`.
- Resources: org, assessments, invites, results, webhooks, pipelines.
- Webhook signature helper for `X-Praxicraft-Signature`.
