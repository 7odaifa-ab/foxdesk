# FoxDesk contributor instructions

## RTL / i18n

- Add every new `t('key')` string to every catalog returned by
  `get_supported_languages()`, not only `includes/lang/en.php`.
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
- Edit `theme.css`, then run `npm run build:css`. Never hand-edit
  `assets/css/theme.min.css`.
- Run `npm run test:i18n` before committing localization or layout changes.
