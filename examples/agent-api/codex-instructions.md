# Codex Agent Instructions

Use this when connecting Codex to FoxDesk through a scoped API key.

## Local Secret

Create `examples/agent-api/.env` from `.env.example` and store:

```bash
FOXDESK_BASE_URL=https://helpdesk.example.com
FOXDESK_API_TOKEN=fdx_replace_with_token_from_profile
```

Never paste a production token into a shared prompt. Never print FOXDESK_API_TOKEN.
Prefer the local `.env` file or your secret manager.

## API, Not Browser Login

Treat `FOXDESK_BASE_URL` as the API host. Do not open
`/index.php?page=login`, do not wait for a browser session, and do not use
cookies. A FoxDesk API token only works through HTTP requests that include:

```bash
Authorization: Bearer $FOXDESK_API_TOKEN
```

## Required Session Start

1. Call `agent-docs` and follow its `operating_instructions`.
2. Call `agent-me` and verify the token identity.
3. Before changing an existing ticket, call `agent-get-ticket`.

Keep the main ticket concise. Use `agent-add-update` for a comment without time
and `agent-add-work-entry` for tracked work, sending the formatted comment and
duration together. Use a unique `Idempotency-Key` for every POST request. Full rules:
`docs/AGENT_TICKET_WORKFLOW.md`.

## Allowed Tools

Use only these scripts unless the user explicitly asks for a lower-level API
call:

```bash
sh examples/agent-api/create-ticket.sh
sh examples/agent-api/add-comment.sh
sh examples/agent-api/comment-with-time.sh
sh examples/agent-api/log-time.sh
sh examples/agent-api/prepare-report.sh
```

## Behavior

- Read the user's intent first, then set the relevant `FOXDESK_*` variables.
- Use `FOXDESK_TICKET_ID` or `FOXDESK_TICKET_HASH` before commenting or logging
  time.
- Treat 401/403 as permission or token-scope problems, not application bugs.
- For write actions, keep the default `Idempotency-Key` header or set
  `FOXDESK_IDEMPOTENCY_KEY` when retrying the same action.
- If the API returns `409` with `Retry-After`, wait and retry the unchanged
  request with the same idempotency key. Never reuse that key for new content.
- Standalone time entries are only for work without a matching comment and
  accept 1 to 1440 minutes.
- Summarize the created ticket id, logged minutes, or report totals back to the
  user.
