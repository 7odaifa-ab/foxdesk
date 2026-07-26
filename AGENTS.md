# FoxDesk contributor instructions

## RTL / i18n

- `locales/catalogs/*.json` is the translation source. Files in
  `includes/lang/` are generated; never edit them by hand.
- Add source copy to `locales/catalogs/en.json`, add reviewed copy to every
  stable or beta locale, run `npm run i18n:sync-drafts`, then
  `npm run i18n:build`. New product code should use semantic keys. Use `tn()`
  and CLDR plural suffixes for counted copy.
- Locale selectors, API validation, e-mail routing, reports, and agent
  instructions must derive from `locales/registry.json`; do not add a second
  hard-coded language list.
- Use CSS logical properties such as `margin-inline-*`, `padding-inline-*`,
  `inset-inline-*`, and `border-inline-*` for layout. A physical
  `left`/`right` rule is allowed only when the direction is intentionally
  physical, for example in a chart or media control. `float` does not mirror
  automatically.
- Mark directional icons such as back arrows and chevrons explicitly and
  mirror them in RTL. Do not mirror logos, media, charts, code, URLs, phone
  numbers, email addresses, or ticket identifiers.
- Wrap untranslated dynamic values in `bidi_isolate()` in plain-text contexts
  such as `<option>`. In HTML, prefer `<bdi dir="auto">`.
- Render user-provided text with `dir="auto"` unless the component has a
  stronger semantic direction.
- Network search, autosave, and command actions must wait for
  `compositionend`; never submit an unfinished CJK IME composition.
- Edit `theme.css`, then run `npm run build:css`. Never hand-edit
  `assets/css/theme.min.css`.
- Run `npm run test:i18n` before committing localization or layout changes.
