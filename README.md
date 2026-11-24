# Headless WP Kit

A small WordPress plugin for a headless stack. WordPress stays as the editorial
backend; a separate frontend renders the public site.

## What it provides

- Signed preview tokens with autosave support.
- A capabilities endpoint for an external control panel, CLI or agent.
- Optional editorial profiles: authors, sections, topics, formats, signature,
  guide fields and agent-aware block styles.
- CMS noindex headers, `robots.txt` rules and sitemap hardening.
- Redirect and retired-path manifests.
- A minimal audit table.
- Application password support behind PHP-FPM and some hosting panels.

It does not include a frontend. The frontend can be Astro, Next, a static site or
any application that consumes the WordPress REST API.

## Requirements

- Self-hosted WordPress 6.5+.
- PHP 8.1+.
- A frontend base URL for preview links.

WordPress.com is not supported: the plugin needs plugin installation and a
self-hosted WordPress.

## Installation

1. Copy this directory to `wp-content/plugins/headless-wp-kit` or install the ZIP.
2. Activate the plugin.
3. Open **Settings → Headless WP Kit**.
4. Set the **Frontend URL**.
5. Choose a profile:
   - `core`: preview, redirects, audit, CMS noindex.
   - `example`: the same plus the editorial model and agent block styles.
6. If you use `example`, create the example terms from the settings screen.
7. Create a read-only user and an application password for the frontend.

## Profiles

The active profile controls the features and content model.

### Core

- Preview tokens.
- Redirects and retired paths.
- Audit.
- CMS noindex.

### Example editorial site

- Everything in core.
- `hwk_author` post type.
- `hwk_section`, `hwk_topic` and `hwk_format` taxonomies.
- `hwk_direction`, `hwk_review`, `hwk_execution`, `hwk_audience`,
  `hwk_agents`, `hwk_level` and `hwk_requirements` meta.
- Signature guard for publishing.
- Agent block styles for `core/group`: `hwk-agent-public`,
  `hwk-agent-labeled`, `hwk-agent-restricted`.

The example profile is a starting point. Site-specific profiles can be added
under `profiles/` and selected with the `profile` setting.

## REST endpoints

| Endpoint | Auth | Purpose |
|---|---|---|
| `GET /wp-json/headless/v1/capabilities` | Public | Kit version, profile, features and content model. |
| `GET /wp-json/headless/v1/preview-token/<id>` | Editor | Mint a preview token. |
| `GET /wp-json/headless/v1/preview/<id>?token=...` | Read | Preview payload, including the editor's autosave. |
| `GET /wp-json/headless/v1/redirects` | Public | Redirect manifest. |
| `GET /wp-json/headless/v1/retired` | Public | Retired paths manifest. |

Preview tokens are HMAC-signed with `wp_salt('auth')` and expire after the
configured TTL. They are not stored in the database.

## Preview flow

1. The editor clicks Preview or opens the meta box preview button.
2. WordPress signs a short-lived token and redirects to
   `<frontend>/preview/<id>/?token=...`.
3. The frontend calls `/headless/v1/preview/<id>?token=...` with a read-only
   application password.
4. The plugin returns the current post plus the editor's autosave.

The WordPress session does not travel to the frontend.

## Editorial model

The example profile uses namespaced keys to avoid collisions:

- Post type: `hwk_author`.
- Taxonomies: `hwk_section`, `hwk_topic`, `hwk_format`.
- Meta: `hwk_direction`, `hwk_review`, `hwk_execution`, `hwk_audience`,
  `hwk_agents`, `hwk_level`, `hwk_requirements`.

The signature guard blocks publishing a post without direction, review and
execution. Guides also require a level.

## Security notes

- The CMS is expected to be `noindex`; the frontend is the public site.
- The plugin does not set `blog_public`; keep it at `1` so Yoast does not mark
  content as `noindex`. The kit handles CMS noindex through headers and
  `robots.txt`.
- Do not reuse production credentials in a lab or a preview.
- Do not expose the CMS admin or REST to the public beyond what the frontend
  needs.
- Review the generated settings and terms before publishing.

## Development

```bash
bash bin/build.sh
```

This creates `dist/headless-wp-kit.zip` with the plugin files.

## Status

Smoke-tested in WordPress Playground on 2026-11-24 (PHP 8.3, latest WordPress):
activation, capabilities, example profile, meta, signature guard, preview and
autosave, redirects, retired paths, robots and noindex headers.

Not yet tested on real hosting, Plesk, PHP-FPM, the block editor UI or a real
remote WordPress. See `VALIDATION.md`.

## License

GPL-2.0-or-later. See `LICENSE`.
