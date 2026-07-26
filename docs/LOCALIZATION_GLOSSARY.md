# FoxDesk localization glossary

These rules apply to Weblate, AI draft generation, native review, screenshots,
documentation, e-mail, and store metadata.

## Never translate

- `FoxDesk`
- API field names, endpoint paths, scopes, event codes, database enum values
- plan identifiers and internal SKU values
- ticket codes such as `FOX-123`
- ISO dates and machine values in integrations
- source code, commands, URLs, e-mail addresses, and filenames

Plan display names may be localized only after Product provides an approved
market-specific name. Until then, preserve the canonical display name.

## Product terms

| Source concept | Meaning | Avoid |
|---|---|---|
| ticket | A customer request or work item | translating as an event admission ticket |
| client | The customer organization | mixing with an individual contact |
| agent | A human or AI worker handling tickets | translating the API identifier |
| workspace | A tenant-scoped FoxDesk environment | implying a local folder |
| report | A saved customer-facing work/billing report | translating report IDs |
| time entry | One minute-level work record | inventing fixed 60/120-minute blocks |
| internal note | Staff-only comment | language implying the customer can see it |
| public link | Revocable shared view | language implying unrestricted publication |

## Voice

- Be clear, calm, and direct. Avoid marketing language in operational UI.
- Buttons use short actions. Errors explain what failed and the next safe
  action.
- Preserve the source level of formality consistently within a locale.
- Czech uses polite, neutral product language. German defaults to formal
  `Sie`. Spanish and Italian use a neutral professional register.
- Arabic, Hebrew, Persian, and Urdu reviewers must check mixed-direction
  names, IDs, dates, and e-mail addresses in context.
- Japanese, Chinese, and Korean translations should avoid unnecessary spaces
  and must be tested with real IME input, not pasted text alone.

## Critical review

Billing, destructive actions, data retention/deletion, authentication,
permissions, privacy, terms, and legal copy require specialist approval in
addition to a native language review. AI output is always a suggestion and
must never be marked reviewed automatically.
