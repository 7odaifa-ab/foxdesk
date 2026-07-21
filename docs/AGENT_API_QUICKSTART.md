# Agent API Quickstart

This quickstart connects Codex, Claude, or a CLI script to FoxDesk without
giving the assistant more access than the user who created the key.

## Steps

1. Sign in as the user whose permissions the assistant should inherit.
2. Open **Settings -> API & agents**.
3. Create a scoped key with a clear name, for example `Codex local assistant`.
4. Select only the scopes the assistant needs.
5. Copy the key once.

Use this single page for Codex, Claude, automations, and AI-agent access. The
key always inherits the permissions of the person creating it.

6. Configure the local example env:

```bash
cp examples/agent-api/.env.example examples/agent-api/.env
```

7. Edit `examples/agent-api/.env` and paste the token.
8. Run a smoke command:

```bash
sh examples/agent-api/create-ticket.sh
```

## Important: API access, not browser login

When you receive a FoxDesk URL and an API token, do not open the web app or
`/index.php?page=login`. The token is not a password and it will not create a
browser session.

Treat the URL as the API host and call FoxDesk with an `Authorization: Bearer`
header:

```bash
FOXDESK_BASE_URL=https://helpdesk.example.com
FOXDESK_API_TOKEN=fdx_replace_with_token_from_profile

curl -fsS "$FOXDESK_BASE_URL/index.php?page=api&action=agent-me" \
  -H "Authorization: Bearer $FOXDESK_API_TOKEN"
```

Every agent session starts by loading the live instructions and then verifying
the token identity:

```bash
curl -fsS "$FOXDESK_BASE_URL/index.php?page=api&action=agent-docs&instruction_language=en" \
  -H "Authorization: Bearer $FOXDESK_API_TOKEN"

curl -fsS "$FOXDESK_BASE_URL/index.php?page=api&action=agent-me" \
  -H "Authorization: Bearer $FOXDESK_API_TOKEN"
```

The canonical ticket and tracked-work workflow is documented in
`docs/AGENT_TICKET_WORKFLOW.md`. Localized API instructions are available in
English, Czech, German, Spanish, and Italian.

If a browser request redirects to login, switch back to the API endpoint. It
means the request was missing the bearer header or was sent to the web UI
instead of `/index.php?page=api&action=...`.

## Examples

Create a ticket:

```bash
FOXDESK_TICKET_TITLE="Printer issue" \
FOXDESK_TICKET_DESCRIPTION="The office printer is offline." \
sh examples/agent-api/create-ticket.sh
```

Log time only when the user explicitly requests a tracked-time workflow:

```bash
FOXDESK_TICKET_ID=123 \
FOXDESK_DURATION_MINUTES=45 \
FOXDESK_MANUAL_DATE=2026-07-20 \
FOXDESK_MANUAL_START_TIME=09:00 \
FOXDESK_MANUAL_END_TIME=09:45 \
FOXDESK_COMMENT='<p>Diagnosed printer network settings.</p>' \
sh examples/agent-api/comment-with-time.sh
```

Use `log-time.sh` only when the work genuinely has no related comment.

Prepare a report review:

```bash
FOXDESK_ORGANIZATION_ID=1 \
FOXDESK_TIME_RANGE=this_month \
sh examples/agent-api/prepare-report.sh
```

## Agent Instructions

- Codex: `examples/agent-api/codex-instructions.md`
- Claude: `examples/agent-api/claude-instructions.md`
- MCP server: `docs/AGENT_MCP_SERVER.md`

Run the local MCP wrapper when your agent supports MCP:

```bash
npm run agent:mcp
```

## Required scopes

- Create ticket: `tickets:write`
- Add comment: `tickets:read`, `comments:write`
- Add tracked work: `tickets:read`, `comments:write`, `time:write`
- Log standalone time: `tickets:read`, `time:write`
- Prepare report review: `reports:read`
- Upload attachment: `attachments:write`

Use read-only scopes first when testing. Add write scopes only after the agent's
workflow is clear.

401 means the key is missing or invalid. 403 means the key is valid but the user
or token scope cannot perform that action.
