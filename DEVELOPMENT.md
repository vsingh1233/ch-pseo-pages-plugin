# Development

## Requirements

- PHP 7.4 or newer.
- Composer 2.

The dependency lock is resolved for PHP 7.4 so development dependencies remain
installable across every supported PHP version.

## Install development dependencies

```bash
composer install
```

Dependencies are installed in `vendor/`, which is intentionally excluded from
Git and from production plugin packages.

## Quality checks

Run the complete local quality gate:

```bash
composer check
```

The command runs:

1. PHP syntax checks.
2. PHPCS with WordPress Coding Standards and PHPCompatibilityWP.
3. PHPStan static analysis.
4. PHPUnit tests.

Individual commands are also available:

```bash
composer lint
composer phpcs
composer phpcbf
composer phpstan
composer test
```

`composer phpcbf` applies only the formatting fixes that PHP_CodeSniffer
considers safe. Review its diff before committing.

## Tests

Fast isolated unit tests live in `tests/unit`. They do not load WordPress or
connect to the local site's database.

Future WordPress integration tests must use a dedicated disposable test
database. Never point the WordPress test suite at the `assignment-today`
development database.

### Local routing smoke test

The routing smoke test creates uniquely named temporary services, locations,
mappings, and a published template page in the selected local WordPress site.
It tests all five location structures through real HTTP requests and removes the
fixtures afterward.

Run it only against a local development site:

```bash
wp eval-file tests/integration/class-ch-pseo-routing-smoke-test.php
```

The test verifies successful template rendering, dynamic shortcode context,
canonical output, missing mappings, unknown locations, invalid hierarchy,
unsupported depth, and service-base 404 behavior.

Run the smoke test once with Yoast active and once with Yoast inactive when
changing SEO or schema output. Both modes must produce exactly one title,
description, canonical, robots directive, and CH-PSEO schema node.

When Yoast is active, CH-PSEO preserves Yoast's graph types and site-wide
values but rewrites page-specific `WebPage`, `ImageObject`, and
`BreadcrumbList` identifiers for the resolved PSEO URL. The full base-prefix,
service, and location breadcrumb hierarchy is linked. The added service entity
uses the standard `#service` identifier.

## Database migrations

The plugin release version and database schema version are tracked separately:

- `CH_PSEO_VERSION` identifies the plugin release.
- `CH_PSEO_DB_VERSION` identifies the required database schema.

`CH_PSEO_Migrator` runs automatically before the rest of the plugin boots. To
add a schema change:

1. Increase `CH_PSEO_DB_VERSION`.
2. Add the new version and callback to `CH_PSEO_Migrator::get_migrations()`.
3. Add a migration method for the schema or data change.
4. Add or update migration tests.

Migrations must be safe to run once and should not assume that activation has
occurred. A short-lived option lock prevents concurrent requests from applying
the same migration.

Schema version `0.2.0` removes the unused `custom_intro` mapping column, the
custom settings table's inapplicable `autoload` column, and the unused
`seo_default_robots` setting row.

Schema version `0.3.0` normalizes optional relationship IDs to `0`, repairs
orphaned optional references, merges legacy duplicates while preserving child
references, and adds unique indexes for:

- service slugs;
- country slugs, state slugs per country, and city slugs per state;
- service/location mapping tuples;
- global or service-specific exclusion slugs; and
- setting keys.

The plugin deliberately uses application-level relationship validation rather
than SQL foreign keys, following WordPress custom-table conventions. Admin and
CSV write paths must verify that referenced records exist and that each city,
state, and country combination is internally consistent.

Verify the live constraints locally with:

```bash
wp eval-file tests/integration/class-ch-pseo-relational-constraints-smoke-test.php
```

Schema version `0.4.0` makes `url_base` an optional, non-unique prefix. The
service slug is always included in generated routes. For example, a prefix of
`services` and slug of `assignment-writing` produces
`/services/assignment-writing/{locations}/`; a blank prefix produces
`/assignment-writing/{locations}/`.

## Continuous integration

GitHub Actions runs `composer check` on PHP 7.4, 8.1, and 8.3 for every push and
pull request.

Sitemap URL and index `lastmod` values use the newest timestamp from the
mapping, service configuration, or reusable WordPress template page. They must
not be replaced with the current request time because `lastmod` should represent
a meaningful content change.

## CSV imports

CSV imports are transactional and all-or-nothing. The Tools screen provides the
authoritative location and mapping templates. A dry run executes the complete
validation/import path and rolls back before reporting counts.

Run the local CSV integration test with:

```bash
wp eval-file tests/integration/class-ch-pseo-csv-import-smoke-test.php
```

The test verifies dry-run rollback, hierarchy creation, location upserts,
mapping creation, override persistence, and fixture cleanup.

## Rewrite and sitemap caching

Active service rewrite definitions and URL exclusions are combined and cached
for 24 hours. URL-structure changes clear this cache before rewrite rules are
flushed.

The configured sitemap URL returns a normal URL set when all eligible URLs fit
in one page. When the configured per-file limit is exceeded, it returns a
sitemap index linking to numbered `-1.xml`, `-2.xml`, and later pages. The index
and every child page use generation-versioned 12-hour caches.
