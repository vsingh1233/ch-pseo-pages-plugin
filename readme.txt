=== CH-PSEO Pages Plugin ===
Contributors: ch
Tags: programmatic seo, dynamic pages, locations, yoast, sitemap
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create dynamic service-and-location SEO pages from reusable WordPress page templates.

== Description ==

CH-PSEO Pages Plugin creates virtual, service-and-location-based URLs without
creating a separate WordPress page for every generated URL.

Each service uses a published WordPress page as its reusable content template.
The plugin resolves an active service/location mapping, loads the selected page,
and exposes the resolved service and location through context tokens and
shortcodes.

= Dynamic routing =

Every generated URL includes the service slug. Services can optionally use a
shared or nested base prefix:

* With base prefix `services`: `/services/assignment-writing/india/`
* Without a base prefix: `/assignment-writing/india/`

Services support these location structures:

* Country
* State
* Country / State
* State / City
* Country / State / City

Only active services, active locations, active mappings, and published template
pages resolve. Captured URLs without a valid mapping return a WordPress 404.

= Service and location management =

The WordPress administration screens provide:

* Service management with optional base prefix, service slug, template page, location structure, status,
  robots, sitemap, metadata, H1, and schema defaults.
* Country, state, and city management.
* Service/location mappings with per-location overrides.
* Global and service-specific first-path-segment exclusions.
* Dashboard counts for generated, indexable, and noindex mappings.

= Dynamic content =

The following shortcodes are available:

* `[ch_pseo_title]`
* `[ch_pseo_service_name]`
* `[ch_pseo_location]`
* `[ch_pseo_location_full]`
* `[ch_pseo_location_parent]`
* `[ch_pseo_country]`
* `[ch_pseo_state]`
* `[ch_pseo_city]`
* `[ch_pseo_location_type]`
* `[ch_pseo_breadcrumbs]`
* `[ch_pseo_location_finder]`

The plugin also processes CH-PSEO shortcodes in supported Divi rendered output.

Supported template tokens include:

* `{service_name}`
* `{service_slug}`
* `{url_base}`
* `{country}` and `{country_name}`
* `{state}` and `{state_name}`
* `{city}` and `{city_name}`
* `{location}`
* `{location_full}`
* `{location_parent}`
* `{location_type}`

= SEO, schema, and sitemap =

When Yoast SEO is active and integration is enabled, valid PSEO requests
override:

* SEO title
* Meta description
* Canonical URL
* Robots directives
* Yoast schema graph

When Yoast is unavailable, the plugin outputs standalone document titles, meta
descriptions, canonical URLs, robots directives, and JSON-LD schema. Schema
generation is owned by the dedicated schema component in both modes, avoiding
duplicate or divergent structured data.

The plugin provides configurable, paginated XML sitemaps for eligible generated
URLs and adds the main sitemap to the Yoast sitemap index. When the configured
per-file limit is exceeded, the main URL becomes a sitemap index linking to
numbered child files. Each XML document is cached independently for 12 hours.

= Tools =

The Tools screen can:

* Check and update custom database tables.
* Clear the location finder and sitemap caches.
* Export eligible generated URLs as CSV.
* Import location hierarchies and service/location mappings from CSV.
* Bulk activate, deactivate, or delete locations and mappings.
* Configure optional data removal during uninstall.

== Installation ==

1. Upload the `ch-pseo-pages-plugin` folder to `/wp-content/plugins/`.
2. Activate "CH-PSEO Pages Plugin" through the WordPress Plugins screen.
3. Open CH PSEO Pages in WordPress Admin.
4. Add a service and select a published WordPress template page.
5. Add the required countries, states, or cities.
6. Create an active service/location mapping.
7. Save WordPress permalinks if the site does not resolve the generated URL.

== Frequently Asked Questions ==

= Does the plugin create a WordPress page for every generated URL? =

No. Generated URLs are virtual. They reuse the published template page selected
for the service.

= What happens when a generated-looking URL has no active mapping? =

The request returns a normal WordPress 404.

= Is Yoast SEO required? =

No. When Yoast is active, the plugin integrates with its metadata and schema
filters. When Yoast is unavailable, CH-PSEO outputs standalone SEO metadata and
JSON-LD.

= Can metadata be customized for one location? =

Yes. A mapping can override the service-level H1, title, description, schema
type, robots directive, sitemap inclusion, and canonical URL.

= Does deactivation delete plugin data? =

No. Data is retained on deactivation. It is deleted on uninstall only when the
data-removal setting has been enabled on the Tools screen.

= Does the plugin support bulk import? =

Yes. The Tools screen provides downloadable CSV templates, dry-run validation,
transactional location and mapping imports, and generated-URL export.

== Changelog ==

= 0.1.0 =

* Added virtual service-and-location routing with reusable template pages.
* Added service, location, mapping, exclusion, and settings administration.
* Added context tokens, dynamic shortcodes, breadcrumbs, and a location finder.
* Added Yoast metadata, robots, canonical, and schema graph integration.
* Added a cached XML sitemap, Yoast sitemap index entry, and CSV URL export.
* Added optional uninstall cleanup and maintenance tools.
* Added versioned database migrations and removed unused legacy settings fields.
* Added transactional CSV imports and bulk location/mapping actions.
* Added strict service/location relationship validation and database uniqueness constraints.
* Made the base prefix optional and included the service slug in every generated URL.
* Normalized Yoast page schema and linked breadcrumbs for each generated URL.
* Added a Tools-screen reference for configuration tokens and template shortcodes.
* Added import-format examples and editable CSV exports for locations and mappings.
* Added effective last-modified dates to PSEO sitemap URLs and the Yoast sitemap index.
