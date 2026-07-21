# FoxDesk Agent Ticket Workflow

This is the canonical workflow for agents that create tickets, add comments,
and record tracked work without disconnected or duplicate records. The live,
localized version is always available from:

```text
GET /index.php?page=api&action=agent-docs&instruction_language=en
```

Supported instruction languages are `en`, `cs`, `de`, `es`, and `it`. If the
parameter is omitted, FoxDesk uses the API-token user's language.

## Basic Rules

- Use only the FoxDesk Agent API. Never use a web browser.
- At the start of every session, call `agent-docs`, then `agent-me`.
- Read the key from `FOXDESK_API_TOKEN` and never expose it.
- Before changing a ticket, call `agent-get-ticket`.
- Every POST request must include a unique `Idempotency-Key`.

## Main Ticket

The main ticket contains only a concise title, short general description,
client, assignee, status, and priority. Do not put minutes, total time, a daily
breakdown, a detailed agenda, a timer, or a time entry in the main body.

## Comments Without Tracked Time

Use `agent-add-update` when the comment should not change worked or billable
time. Do not include any time fields in this request.

## Tracked Work Entries

Use one `agent-add-work-entry` request for each work record. Send the comment
and duration together so FoxDesk creates both linked records atomically:

```html
<p><strong>13 Jul 2026 - 27 min</strong></p>
<ul>
  <li>Adjusted campaign budgets based on performance.</li>
  <li>Reviewed the bidding strategy for the accessories campaign.</li>
</ul>
```

The request must include `content` and `duration_minutes`. Include `started_at`
and `ended_at` when exact work times are known. Set `skip_notification` to
`true` when the client should not receive an email. Never create a separate
comment and time entry for the same work.

## Verification

After finishing, call `agent-get-ticket` and verify the client, assignee,
description, comments, that each tracked-work comment has a time entry with a
non-null `comment_id`, that `total_time_minutes` equals the saved time-entry
sum, and that no duplicate active ticket exists. Cancel an incorrect ticket
only after the correct replacement has been created and verified.

## Permanent Deletion

Permanent deletion is an exceptional administrator action. Call
`agent-delete-ticket-preflight` first, show its impact, and obtain explicit
confirmation of the exact ticket code. Then call
`agent-delete-ticket-permanently` with `tickets:read`, `delete:write`, and a
unique `Idempotency-Key`. Never use permanent deletion as a status change.
