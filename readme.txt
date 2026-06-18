=== CH-PSEO Pages Plugin ===
Contributors: ch
Tags: programmatic seo, dynamic pages, locations, sitemap, schema
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A reusable foundation for dynamic programmatic SEO pages in WordPress.

== Description ==

CH-PSEO Pages Plugin is intended to generate dynamic, service-and-location-based
programmatic SEO URLs without creating a physical WordPress page for every URL.

This initial release contains only the modular plugin boilerplate:

* Activation and deactivation handlers.
* Initial custom database tables.
* Admin dashboard and settings pages.
* Routing, context, shortcode, SEO, sitemap, and schema class foundations.
* Optional data cleanup on uninstall.

Dynamic route generation and content integrations are not implemented yet.

== Installation ==

1. Upload the `ch-pseo-pages-plugin` folder to `/wp-content/plugins/`.
2. Activate "CH-PSEO Pages Plugin" through the WordPress Plugins screen.
3. Open CH-PSEO Pages in WordPress Admin.

== Frequently Asked Questions ==

= Does this version create dynamic PSEO pages? =

Not yet. Version 0.1.0 establishes the plugin architecture only.

= Does deactivation delete plugin data? =

No. Data is retained on deactivation. It is deleted on uninstall only when the
data-removal setting has been enabled.

== Changelog ==

= 0.1.0 =

* Added the initial modular plugin boilerplate.

