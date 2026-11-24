# Validation

## 2026-11-24 — WordPress Playground smoke test

Environment:

- WordPress Playground CLI.
- PHP 8.3.
- WordPress `latest` at the time of the test.
- Plugin mounted from the repository; no ZIP install.

Tested:

- Activation and `GET /wp-json/headless/v1/capabilities`.
- `core` profile: preview, redirects, audit and CMS noindex.
- `example` profile: authors, sections, topics, formats, meta and agent blocks.
- Meta registration and REST updates for `hwk_direction`, `hwk_review`,
  `hwk_execution`, `hwk_audience`, `hwk_agents`, `hwk_level`,
  `hwk_requirements`.
- Signature guard: publishing a guide without `hwk_level` returned `403`;
  adding the level and publishing returned `200`.
- Preview token, preview payload, author resolution, terms, and autosave:
  a new autosave was returned with `autosave: true`.
- Redirect and retired manifests.
- `robots.txt` with `Disallow: /` and `X-Robots-Tag: noindex, nofollow`.

Not tested:

- Real hosting, Plesk, PHP-FPM or the application password bridge.
- The block editor UI and the Gutenberg preview button.
- WP-CLI installation, multisite, or a real remote WordPress.
- PHP 8.1 and 8.2 runtime; CI only lints those versions.

Reproduce:

```bash
npx @wp-playground/cli@latest start \
  --path=. \
  --skip-browser \
  --port=9400
```

Playground's auto-login may redirect the first request. Use a cookie jar and
follow redirects when testing REST endpoints.
