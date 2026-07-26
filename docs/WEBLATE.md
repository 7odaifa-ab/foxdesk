# Weblate setup

FoxDesk uses Hosted Weblate with translation changes delivered only through
GitHub pull requests. The bot must not push directly to `main`.

The import reference is `locales/weblate-components.json`. For this repository
the component uses:

- file format: i18next JSON v4;
- file mask: `locales/catalogs/*.json`;
- monolingual base: `locales/catalogs/en.json`;
- language filter: the 24 public BCP-47 locales only;
- protected target branch: `main`;
- empty push branch when using the GitHub pull-request backend, so Weblate
  works through its fork/app workflow.

Hosted Weblate's GitHub App is preferred. Grant only repository contents and
pull-request access needed to create translation PRs. Store no token, private
key, or Weblate credential in this repository.

The public self-hosted component can be visible to community translators.
SaaS and iOS components live in their private repositories. All components
share the project glossary and translation memory, but their runtime catalogs
remain separate.

Machine translation is configured as suggestions or “needs editing”, never as
reviewed translation. Weblate PRs must pass `npm run test:i18n`; merge commits
are retained so Weblate can recognize imported history.

Current references:

- https://docs.weblate.org/en/latest/formats/i18next.html
- https://docs.weblate.org/en/latest/admin/code-hosting.html
- https://docs.weblate.org/en/latest/admin/continuous.html
