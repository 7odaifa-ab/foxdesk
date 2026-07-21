# Claude Agent Instructions

Use this when connecting Claude Desktop, Claude Code, or a Claude Project to
FoxDesk through a scoped API key.

## Secret Setup

Store the key outside the prompt:

```bash
cp examples/agent-api/.env.example examples/agent-api/.env
```

Then edit `.env` and paste the key from **Settings -> API & agents**.

## Project Prompt

```text
You can operate FoxDesk only through the scripts in examples/agent-api.
Never print FOXDESK_API_TOKEN.
Treat FOXDESK_BASE_URL as an API host, not a browser page. Do not open
/index.php?page=login and do not wait for cookies. Use Authorization: Bearer
FOXDESK_API_TOKEN on every request.
At the start of every session call agent-docs, follow operating_instructions,
then call agent-me. Before changing a ticket call agent-get-ticket.
Keep the main ticket concise. Use agent-add-update for comments without tracked
time. Use agent-add-work-entry for tracked work and send the formatted comment
and duration together so FoxDesk creates one linked record.
Before write actions, confirm the target ticket or client when it is ambiguous.
Use agent-create-ticket for new work, agent-add-update for comments without
time, agent-add-work-entry for tracked work, and app-reporting-review for drafts.
For retries, reuse the same Idempotency-Key with an unchanged body. If FoxDesk
returns 409 with Retry-After, wait and retry; do not generate a second key.
Standalone time entries are only for work without a matching comment.
If the API returns 401 or 403, ask for a token with the required scope.
```

## Smoke Commands

```bash
sh examples/agent-api/create-ticket.sh
FOXDESK_TICKET_ID=123 FOXDESK_DURATION_MINUTES=30 FOXDESK_COMMENT='<p>Completed the requested update.</p>' sh examples/agent-api/comment-with-time.sh
FOXDESK_TICKET_ID=123 sh examples/agent-api/log-time.sh
FOXDESK_ORGANIZATION_ID=1 sh examples/agent-api/prepare-report.sh
```
