=== Headless WP Kit ===
Contributors: headlesswpkit
Tags: headless, rest-api, preview, decoupled, editorial
Requires at least: 6.5
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A small headless WordPress kit: signed previews, editorial profiles, REST capabilities and CMS hardening.

== Description ==

Headless WP Kit keeps WordPress as the editorial backend and lets a separate frontend render the public site.

It provides:

* Signed preview tokens for drafts and published posts, including the editor's autosave.
* A capabilities endpoint for an external control panel or CLI.
* An optional editorial profile with authors, sections, topics, formats, signature fields and agent-aware blocks.
* CMS noindex headers, robots.txt rules and sitemap hardening.
* Redirect and retired-path manifests.
* A minimal audit log.
* Application password support behind PHP-FPM and some hosting panels.

The plugin does not include a frontend. You can use Astro, Next, a static site or any application that consumes the WordPress REST API.

This plugin is for self-hosted WordPress. It is not a WordPress.com plugin.

== Installation ==

1. Upload the plugin ZIP and activate it.
2. Go to Settings → Headless WP Kit.
3. Set the Frontend URL, for example `https://example.com`.
4. Choose the `core` or `example` profile.
5. If you use the example profile, create its example terms from the settings screen.
6. Create a read-only WordPress user and an application password for the frontend.
7. Point the frontend at the REST API and the preview route.

The frontend must implement `/preview/<id>/` and consume the preview data from `/wp-json/headless/v1/preview/<id>?token=...`.

== Frequently Asked Questions ==

= Does this replace WordPress themes? =

No. WordPress stops rendering the public site. A separate frontend renders it.

= Does it work with WordPress.com? =

No. It targets self-hosted WordPress installations where plugins can be installed.

= Does it require the editorial profile? =

No. The `core` profile provides preview, redirects, audit and CMS hardening. The `example` profile adds the editorial model.

= Where is the frontend URL configured? =

In Settings → Headless WP Kit. You can also filter it with `hwk_frontend_url`.

= Are credentials stored by the plugin? =

No. Application passwords and other secrets belong to the users and the hosting environment. The plugin stores only settings and audit rows.

== Changelog ==

= 0.1.0 =
* Initial release: preview tokens, capabilities, core and example profiles, editorial meta, agent block styles, SEO hardening, redirects and audit.
