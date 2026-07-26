# FoxDesk contributor instructions

Follow `AGENTS.md` for the complete repository rules.

## RTL / i18n

- Add new `t('key')` strings to all supported language catalogs.
- Prefer logical CSS properties; document any intentionally physical
  `left`/`right` rule. `float` does not mirror automatically.
- Mirror directional icons explicitly in RTL, but never mirror content such as
  logos, media, charts, code, URLs, phone numbers, email addresses, or IDs.
- Use `bidi_isolate()` for dynamic values in plain text and `<bdi dir="auto">`
  in HTML.
- Edit `theme.css` and regenerate CSS with `npm run build:css`; never edit the
  minified file directly.
- Run `npm run test:i18n` before committing.
