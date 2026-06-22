<?php
/**
 * Live routing smoke tests for a local WordPress installation.
 *
 * Run with:
 * wp eval-file tests/integration/routing-smoke.php
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	wp_die( esc_html__( 'Run this test through WP-CLI.', 'ch-pseo-pages-plugin' ) );
}

/**
 * Creates, tests, and removes isolated routing fixtures.
 */
class CH_PSEO_Routing_Smoke_Test {

	/**
	 * Unique URL-base prefix used by every fixture.
	 *
	 * @var string
	 */
	const BASE_PREFIX = 'ch-pseo-routing-test';

	/**
	 * Database service.
	 *
	 * @var CH_PSEO_Database
	 */
	private $database;

	/**
	 * Inserted IDs grouped by table.
	 *
	 * @var array<string, int[]>
	 */
	private $ids = array(
		'services'          => array(),
		'countries'         => array(),
		'states'            => array(),
		'cities'            => array(),
		'service_locations' => array(),
	);

	/**
	 * Temporary template page ID.
	 *
	 * @var int
	 */
	private $template_page_id = 0;

	/**
	 * Original WordPress search-engine visibility setting.
	 *
	 * @var int
	 */
	private $original_blog_public = 0;

	/**
	 * HTTP test cases.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $cases = array();

	/**
	 * Original sitemap settings keyed by setting name.
	 *
	 * @var array<string, string|null>
	 */
	private $original_sitemap_settings = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->database = new CH_PSEO_Database();
	}

	/**
	 * Runs the complete fixture lifecycle.
	 *
	 * @return void
	 */
	public function run() {
		$error = null;

		try {
			$this->create_fixtures();
			$this->configure_paginated_sitemap();
			$this->refresh_rewrite_rules();
			$this->run_cases();
			$this->run_sitemap_cases();
		} catch ( Throwable $throwable ) {
			$error = $throwable;
		} finally {
			$this->delete_fixtures();
			$this->remove_fixture_rewrite_rules();
		}

		if ( $error ) {
			WP_CLI::error( $error->getMessage() );
		}
	}

	/**
	 * Creates the template page, locations, services, and mappings.
	 *
	 * @throws RuntimeException When a fixture cannot be created.
	 * @return void
	 */
	private function create_fixtures() {
		global $wpdb;

		$this->original_blog_public = (int) get_option( 'blog_public', 1 );
		update_option( 'blog_public', 1 );

		$this->template_page_id = wp_insert_post(
			array(
				'post_title'   => 'CH PSEO Routing Test Template',
				'post_name'    => 'ch-pseo-routing-test-template',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<div id="ch-pseo-routing-test-marker">ROUTING-TEMPLATE-MARKER|[ch_pseo_service_name]|[ch_pseo_location_full]|[ch_pseo_location_type]</div>',
			),
			true
		);

		if ( is_wp_error( $this->template_page_id ) ) {
			throw new RuntimeException( $this->template_page_id->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Consumed by WP-CLI.
		}

		$tables     = $this->database->get_table_names();
		$country_id = $this->insert_row(
			'countries',
			array(
				'name'   => 'Testland',
				'slug'   => 'testland',
				'status' => 'active',
			)
		);
		$unmapped_country_id = $this->insert_row(
			'countries',
			array(
				'name'   => 'Nowhere',
				'slug'   => 'nowhere',
				'status' => 'active',
			)
		);
		$state_id   = $this->insert_row(
			'states',
			array(
				'country_id' => $country_id,
				'name'       => 'Test State',
				'slug'       => 'test-state',
				'status'     => 'active',
			)
		);
		$city_id    = $this->insert_row(
			'cities',
			array(
				'country_id' => $country_id,
				'state_id'   => $state_id,
				'name'       => 'Test City',
				'slug'       => 'test-city',
				'status'     => 'active',
			)
		);

		unset( $tables, $wpdb, $unmapped_country_id );

		$this->add_structure_case( 'country', 'Country Service', array( $country_id ), 'Testland', 'country' );
		$this->add_structure_case( 'state', 'State Service', array( $country_id, $state_id ), 'Test State, Testland', 'state' );
		$this->add_structure_case( 'country_state', 'Country State Service', array( $country_id, $state_id ), 'Test State, Testland', 'state' );
		$this->add_structure_case( 'state_city', 'State City Service', array( $country_id, $state_id, $city_id ), 'Test City, Test State, Testland', 'city' );
		$this->add_structure_case(
			'country_state_city',
			'Country State City Service',
			array( $country_id, $state_id, $city_id ),
			'Test City, Test State, Testland',
			'city',
			home_url( '/ch-pseo-routing-test-canonical-target/' )
		);

		$this->cases[] = array(
			'label'  => 'service base without location',
			'url'    => home_url( '/' . self::BASE_PREFIX . '/country-service/' ),
			'status' => 404,
		);
		$this->cases[] = array(
			'label'  => 'unknown location',
			'url'    => home_url( '/' . self::BASE_PREFIX . '/country-service/unknown/' ),
			'status' => 404,
		);
		$this->cases[] = array(
			'label'  => 'valid location without mapping',
			'url'    => home_url( '/' . self::BASE_PREFIX . '/country-service/nowhere/' ),
			'status' => 404,
		);
		$this->cases[] = array(
			'label'  => 'path deeper than service structure',
			'url'    => home_url( '/' . self::BASE_PREFIX . '/country-service/testland/extra/' ),
			'status' => 404,
		);
		$this->cases[] = array(
			'label'  => 'invalid hierarchy',
			'url'    => home_url( '/' . self::BASE_PREFIX . '/country-state-city-service/testland/test-city/' ),
			'status' => 404,
		);
	}

	/**
	 * Adds one service, mapping, and valid-route assertion.
	 *
	 * @param string $structure         Location structure.
	 * @param string $service_name      Service display name.
	 * @param int[]  $location_ids      Country, state, and city IDs.
	 * @param string $location_full     Expected full location label.
	 * @param string $location_type     Expected location type.
	 * @param string $canonical_override Optional canonical override.
	 * @return void
	 */
	private function add_structure_case( $structure, $service_name, $location_ids, $location_full, $location_type, $canonical_override = '' ) {
		$url_base    = 'state' === $structure ? '' : self::BASE_PREFIX;
		$service_slug = sanitize_title( $service_name );
		$service_id = $this->insert_row(
			'services',
			array(
				'service_name'           => $service_name,
				'service_slug'           => $service_slug,
				'url_base'               => $url_base,
				'template_page_id'       => $this->template_page_id,
				'location_structure'     => $structure,
				'status'                 => 'active',
				'robots_default'         => 'index_follow',
				'sitemap_include_default' => 1,
				'meta_title_template'    => '{service_name} in {location_full}',
				'meta_description_template' => 'Routing test for {service_name} in {location_full}.',
				'h1_template'            => '{service_name} in {location}',
				'schema_type'            => 'Service',
			)
		);

		$country_id = isset( $location_ids[0] ) ? (int) $location_ids[0] : 0;
		$state_id   = isset( $location_ids[1] ) ? (int) $location_ids[1] : 0;
		$city_id    = isset( $location_ids[2] ) ? (int) $location_ids[2] : 0;

		$this->insert_row(
			'service_locations',
			array(
				'service_id'        => $service_id,
				'country_id'        => $country_id,
				'state_id'          => $state_id,
				'city_id'           => $city_id,
				'status'            => 'active',
				'canonical_override' => $canonical_override,
			)
		);

		$segments = array();
		if ( 0 === strpos( $structure, 'country' ) ) {
			$segments[] = 'testland';
		}
		if ( false !== strpos( $structure, 'state' ) ) {
			$segments[] = 'test-state';
		}
		if ( false !== strpos( $structure, 'city' ) ) {
			$segments[] = 'test-city';
		}

		$url = ch_pseo_get_generated_url( $url_base, $service_slug, $segments );

		$this->cases[] = array(
			'label'              => $structure . ' valid route',
			'url'                => $url,
			'status'             => 200,
			'body_contains'      => array(
				'ROUTING-TEMPLATE-MARKER',
				$service_name,
				$location_full,
				$location_type,
				'Routing test for ' . $service_name . ' in ' . $location_full . '.',
				'#service',
				'index, follow',
			),
			'canonical'          => $canonical_override ? $canonical_override : $url,
			'title'              => $service_name . ' in ' . $location_full,
		);
	}

	/**
	 * Inserts one fixture row and records its ID for cleanup.
	 *
	 * @param string $table_key Logical table key.
	 * @param array  $data      Row data.
	 * @throws RuntimeException When the row cannot be inserted.
	 * @return int
	 */
	private function insert_row( $table_key, $data ) {
		global $wpdb;

		$tables = $this->database->get_table_names();
		$result = $wpdb->insert( $tables[ $table_key ], $data );

		if ( false === $result ) {
			throw new RuntimeException( 'Fixture insert failed for ' . $table_key . ': ' . $wpdb->last_error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Consumed by WP-CLI.
		}

		$id                         = (int) $wpdb->insert_id;
		$this->ids[ $table_key ][] = $id;
		return $id;
	}

	/**
	 * Registers fixture rules and writes the complete rewrite set.
	 *
	 * @return void
	 */
	private function refresh_rewrite_rules() {
		$router = new CH_PSEO_Router( $this->database, new CH_PSEO_Context() );
		CH_PSEO_Router::clear_rewrite_definitions_cache();
		$router->register_rewrite_rules();
		$sitemap = new CH_PSEO_Sitemap( $this->database );
		add_filter( 'rewrite_rules_array', array( $sitemap, 'prepend_rewrite_rules' ), PHP_INT_MAX );
		$sitemap->register_rewrite_rules();
		flush_rewrite_rules( false );
	}

	/**
	 * Configures a small sitemap page size for pagination tests.
	 *
	 * @return void
	 */
	private function configure_paginated_sitemap() {
		foreach (
			array(
				'sitemap_enabled'  => '1',
				'sitemap_slug'     => 'ch-pseo-routing-test-sitemap.xml',
				'sitemap_max_urls' => '2',
			) as $key => $value
		) {
			$this->original_sitemap_settings[ $key ] = $this->get_setting_value( $key );
			$this->set_setting_value( $key, $value );
		}
		CH_PSEO_Sitemap::clear_cache();
	}

	/**
	 * Verifies sitemap index and numbered page behavior.
	 *
	 * @throws RuntimeException When sitemap assertions fail.
	 * @return void
	 */
	private function run_sitemap_cases() {
		$sitemap = new CH_PSEO_Sitemap( $this->database );
		$index   = wp_remote_get(
			$sitemap->get_sitemap_url(),
			array(
				'redirection' => 0,
				'timeout' => 20,
			)
		);
		$this->assert_http_response( $index, 200, 'sitemap index' );
		$index_body = wp_remote_retrieve_body( $index );
		if ( false === strpos( $index_body, '<sitemapindex' ) || 3 !== substr_count( $index_body, '<sitemap>' ) ) {
			throw new RuntimeException( 'Sitemap index did not contain three child sitemaps.' );
		}

		foreach ( array(
			1 => 2,
			2 => 2,
			3 => 1,
		) as $page => $expected_urls ) {
			$response = wp_remote_get(
				$sitemap->get_sitemap_page_url( $page ),
				array(
					'redirection' => 0,
					'timeout' => 20,
				)
			);
			$this->assert_http_response( $response, 200, 'sitemap page ' . $page );
			if ( substr_count( wp_remote_retrieve_body( $response ), '<url>' ) !== $expected_urls ) {
				throw new RuntimeException( 'Sitemap page ' . $page . ' contained an unexpected URL count.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Consumed by WP-CLI.
			}
		}

		$response = wp_remote_get(
			$sitemap->get_sitemap_page_url( 4 ),
			array(
				'redirection' => 0,
				'timeout' => 20,
			)
		);
		$this->assert_http_response( $response, 404, 'out-of-range sitemap page' );
		WP_CLI::log( 'PASS paginated sitemap index rendered 2/2/1 URL pages' );
	}

	/**
	 * Asserts an HTTP response code.
	 *
	 * @param array|WP_Error $response Response.
	 * @param int            $expected Expected status.
	 * @param string         $label    Assertion label.
	 * @throws RuntimeException When the response is invalid.
	 * @return void
	 */
	private function assert_http_response( $response, $expected, $label ) {
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $label . ': ' . $response->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		$status = wp_remote_retrieve_response_code( $response );
		if ( $expected !== $status ) {
			throw new RuntimeException( $label . ': expected HTTP ' . $expected . ', received ' . $status ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}

	/**
	 * Executes all HTTP assertions.
	 *
	 * @throws RuntimeException When one or more assertions fail.
	 * @return void
	 */
	private function run_cases() {
		$failures = array();

		foreach ( $this->cases as $case ) {
			$response = wp_remote_get(
				$case['url'],
				array(
					'redirection' => 0,
					'timeout'     => 20,
				)
			);

			if ( is_wp_error( $response ) ) {
				$failures[] = $case['label'] . ': ' . $response->get_error_message();
				continue;
			}

			$status = wp_remote_retrieve_response_code( $response );
			$body   = wp_remote_retrieve_body( $response );

			if ( (int) $case['status'] !== $status ) {
				$failures[] = sprintf( '%s: expected HTTP %d, received %d', $case['label'], $case['status'], $status );
				continue;
			}

			if ( 200 !== $status ) {
				WP_CLI::log( 'PASS ' . $case['label'] . ' returned HTTP ' . $status );
				continue;
			}

			foreach ( $case['body_contains'] as $expected ) {
				if ( false === strpos( $body, $expected ) ) {
					$failures[] = $case['label'] . ': response did not contain ' . $expected;
				}
			}

			$canonical = $this->extract_canonical( $body );
			if ( untrailingslashit( (string) $case['canonical'] ) !== untrailingslashit( $canonical ) ) {
				$failures[] = sprintf(
					'%s: expected canonical %s, received %s',
					$case['label'],
					$case['canonical'],
					$canonical ? $canonical : '(missing)'
				);
			}

			$canonical_count   = preg_match_all( '/<link\b[^>]*\brel=["\']canonical["\'][^>]*>/i', $body );
			$description_count = preg_match_all( '/<meta\b[^>]*\bname=["\']description["\'][^>]*>/i', $body );
			$schema_node_count = substr_count( $body, '#service' );
			$title_count       = preg_match_all( '/<title\b[^>]*>(.*?)<\/title>/is', $body, $title_matches );

			if ( 1 !== $canonical_count ) {
				$failures[] = sprintf( '%s: expected one canonical tag, received %d', $case['label'], $canonical_count );
			}
			if ( 1 !== $description_count ) {
				$failures[] = sprintf( '%s: expected one description tag, received %d', $case['label'], $description_count );
			}
			if ( 1 !== $schema_node_count ) {
				$failures[] = sprintf( '%s: expected one CH-PSEO schema node, received %d', $case['label'], $schema_node_count );
			}
			if ( false !== strpos( $body, '#ch-pseo-service' ) ) {
				$failures[] = $case['label'] . ': schema still contained the legacy #ch-pseo-service identifier';
			}
			if ( 1 !== $title_count ) {
				$failures[] = sprintf( '%s: expected one title tag, received %d', $case['label'], $title_count );
			} else {
				$title = html_entity_decode( wp_strip_all_tags( $title_matches[1][0] ), ENT_QUOTES, get_bloginfo( 'charset' ) );
				if ( false === strpos( $title, $case['title'] ) ) {
					$failures[] = sprintf( '%s: expected title to contain %s, received %s', $case['label'], $case['title'], $title );
				}
			}

			WP_CLI::log( 'PASS ' . $case['label'] . ' rendered the template with the expected canonical' );
		}

		if ( $failures ) {
			throw new RuntimeException( implode( PHP_EOL, $failures ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Consumed by WP-CLI.
		}

		WP_CLI::success( count( $this->cases ) . ' routing smoke tests passed.' );
	}

	/**
	 * Extracts the canonical URL from an HTML document.
	 *
	 * @param string $html Response HTML.
	 * @return string
	 */
	private function extract_canonical( $html ) {
		if ( preg_match_all( '/<link\b[^>]*>/i', $html, $links ) ) {
			foreach ( $links[0] as $link ) {
				if (
					preg_match( '/\brel=["\']canonical["\']/i', $link )
					&& preg_match( '/\bhref=["\']([^"\']+)/i', $link, $matches )
				) {
					return html_entity_decode( $matches[1], ENT_QUOTES, get_bloginfo( 'charset' ) );
				}
			}
		}

		return '';
	}

	/**
	 * Removes all temporary rows and the template page.
	 *
	 * @return void
	 */
	private function delete_fixtures() {
		global $wpdb;

		$tables = $this->database->get_table_names();
		foreach ( array( 'service_locations', 'services', 'cities', 'states', 'countries' ) as $table_key ) {
			foreach ( array_reverse( $this->ids[ $table_key ] ) as $id ) {
				$wpdb->delete( $tables[ $table_key ], array( 'id' => $id ), array( '%d' ) );
			}
		}

		if ( $this->template_page_id ) {
			wp_delete_post( $this->template_page_id, true );
		}

		update_option( 'blog_public', $this->original_blog_public );
		foreach ( $this->original_sitemap_settings as $key => $value ) {
			if ( null === $value ) {
				$this->delete_setting_value( $key );
			} else {
				$this->set_setting_value( $key, $value );
			}
		}
		CH_PSEO_Router::clear_rewrite_definitions_cache();
		CH_PSEO_Shortcodes::clear_location_tree_cache();
		CH_PSEO_Sitemap::clear_cache();
	}

	/**
	 * Gets one custom-table setting.
	 *
	 * @param string $key Setting key.
	 * @return string|null
	 */
	private function get_setting_value( $key ) {
		global $wpdb;
		$table = $this->database->get_settings_table();
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT setting_value FROM {$table} WHERE setting_key = %s ORDER BY id DESC LIMIT 1", $key ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return null === $value ? null : (string) $value;
	}

	/**
	 * Upserts one custom-table setting.
	 *
	 * @param string $key   Setting key.
	 * @param string $value Setting value.
	 * @return void
	 */
	private function set_setting_value( $key, $value ) {
		global $wpdb;
		$table = $this->database->get_settings_table();
		$id    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE setting_key = %s ORDER BY id DESC LIMIT 1", $key ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $id ) {
			$wpdb->update( $table, array( 'setting_value' => $value ), array( 'id' => (int) $id ), array( '%s' ), array( '%d' ) );
		} else {
			$wpdb->insert(
				$table,
				array(
					'setting_key' => $key,
					'setting_value' => $value,
				),
				array( '%s', '%s' )
			);
		}
	}

	/**
	 * Deletes one custom-table setting.
	 *
	 * @param string $key Setting key.
	 * @return void
	 */
	private function delete_setting_value( $key ) {
		global $wpdb;
		$wpdb->delete( $this->database->get_settings_table(), array( 'setting_key' => $key ), array( '%s' ) );
	}

	/**
	 * Removes fixture rules from the in-memory set and flushes clean rules.
	 *
	 * @return void
	 */
	private function remove_fixture_rewrite_rules() {
		global $wp_rewrite;

		foreach ( $wp_rewrite->extra_rules_top as $regex => $query ) {
			if (
				false !== strpos( $regex, preg_quote( self::BASE_PREFIX, '#' ) )
				|| false !== strpos( $regex, 'state\\-service' )
				|| false !== strpos( $regex, 'ch\\-pseo\\-routing\\-test\\-sitemap' )
			) {
				unset( $wp_rewrite->extra_rules_top[ $regex ] );
			}
		}

		$sitemap = new CH_PSEO_Sitemap( $this->database );
		$sitemap->register_rewrite_rules();
		flush_rewrite_rules( false );
		CH_PSEO_Router::clear_rewrite_definitions_cache();
	}
}

$ch_pseo_routing_smoke_test = new CH_PSEO_Routing_Smoke_Test();
$ch_pseo_routing_smoke_test->run();
