<?php
/**
 * Paginated custom PSEO sitemap integration.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders cached sitemap indexes and URL pages.
 */
class CH_PSEO_Sitemap {

	/**
	 * Legacy single-file transient key.
	 *
	 * @var string
	 */
	const CACHE_KEY = 'ch_pseo_sitemap_xml_v1';

	/**
	 * Cache generation option.
	 *
	 * @var string
	 */
	const CACHE_VERSION_OPTION = 'ch_pseo_sitemap_cache_version';

	/**
	 * Database service.
	 *
	 * @var CH_PSEO_Database
	 */
	private $database;

	/**
	 * Per-request setting cache.
	 *
	 * @var array<string, mixed>
	 */
	private $settings = array();

	/**
	 * Constructor.
	 *
	 * @param CH_PSEO_Database $database Database service.
	 */
	public function __construct( CH_PSEO_Database $database ) {
		$this->database = $database;
	}

	/**
	 * Registers sitemap hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'init', array( $this, 'register_rewrite_rules' ), 0 );
		add_filter( 'rewrite_rules_array', array( $this, 'prepend_rewrite_rules' ), PHP_INT_MAX );
		add_filter( 'option_rewrite_rules', array( $this, 'prioritize_loaded_rewrite_rules' ), PHP_INT_MAX );
		add_action( 'template_redirect', array( $this, 'render_sitemap' ), -10 );
		add_filter( 'wpseo_sitemap_index', array( $this, 'add_to_yoast_sitemap_index' ) );
	}

	/**
	 * Registers internal sitemap query variables.
	 *
	 * @param string[] $query_vars Public query variables.
	 * @return string[]
	 */
	public function register_query_vars( $query_vars ) {
		$query_vars[] = 'ch_pseo_sitemap';
		$query_vars[] = 'ch_pseo_sitemap_page';
		return $query_vars;
	}

	/**
	 * Registers the sitemap index and numbered child endpoints.
	 *
	 * @return void
	 */
	public function register_rewrite_rules() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		foreach ( $this->get_rewrite_rules() as $regex => $query ) {
			add_rewrite_rule( $regex, $query, 'top' );
		}
	}

	/**
	 * Prepends exact sitemap rules ahead of broad third-party sitemap rules.
	 *
	 * @param array<string, string> $rules Existing rewrite rules.
	 * @return array<string, string>
	 */
	public function prepend_rewrite_rules( $rules ) {
		return $this->is_enabled() ? $this->get_rewrite_rules() + $rules : $rules;
	}

	/**
	 * Prioritizes exact PSEO sitemap rules when WordPress loads stored rules.
	 *
	 * @param mixed $rules Stored rewrite rules.
	 * @return mixed
	 */
	public function prioritize_loaded_rewrite_rules( $rules ) {
		if ( ! $this->is_enabled() || ! is_array( $rules ) ) {
			return $rules;
		}

		return $this->get_rewrite_rules() + $rules;
	}

	/**
	 * Adds the main PSEO sitemap URL to Yoast's sitemap index.
	 *
	 * @param string $sitemap_index Existing sitemap index fragments.
	 * @return string
	 */
	public function add_to_yoast_sitemap_index( $sitemap_index ) {
		if ( ! $this->is_enabled() || false !== strpos( $sitemap_index, $this->get_sitemap_url() ) ) {
			return $sitemap_index;
		}

		$last_modified  = $this->get_sitemap_last_modified();
		$sitemap_index .= "\n<sitemap>\n";
		$sitemap_index .= "\t<loc>" . esc_xml( $this->get_sitemap_url() ) . "</loc>\n";
		if ( $last_modified ) {
			$sitemap_index .= "\t<lastmod>" . esc_xml( $last_modified ) . "</lastmod>\n";
		}
		$sitemap_index .= "</sitemap>\n";
		return $sitemap_index;
	}

	/**
	 * Gets the newest effective modification date for eligible PSEO URLs.
	 *
	 * A generated page changes when its mapping, service configuration, or
	 * reusable WordPress template changes.
	 *
	 * @return string ISO 8601 date or an empty string.
	 */
	public function get_sitemap_last_modified() {
		global $wpdb;

		$query  = $this->get_eligible_query(
			"GREATEST(
				COALESCE(sl.updated_at, '1970-01-01 00:00:00'),
				COALESCE(s.updated_at, '1970-01-01 00:00:00'),
				COALESCE(p.post_modified_gmt, '1970-01-01 00:00:00')
			) AS effective_updated_at"
		) . ' ORDER BY effective_updated_at DESC LIMIT 1';
		$date   = $wpdb->get_var(
			$wpdb->prepare( $query, $this->get_eligible_query_parameters() )
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $this->format_last_modified( $date );
	}

	/**
	 * Outputs a sitemap index or URL page.
	 *
	 * @return void
	 */
	public function render_sitemap() {
		if ( ! get_query_var( 'ch_pseo_sitemap' ) ) {
			return;
		}
		if ( ! $this->is_enabled() ) {
			status_header( 404 );
			exit;
		}

		$page       = absint( get_query_var( 'ch_pseo_sitemap_page' ) );
		$page_count = $this->get_sitemap_page_count();

		if ( $page > $page_count ) {
			status_header( 404 );
			exit;
		}

		if ( $page > 0 ) {
			$xml = $this->get_cached_xml( 'page-' . $page, array( $this, 'generate_sitemap_xml' ), array( $page ) );
		} elseif ( $page_count > 1 ) {
			$xml = $this->get_cached_xml( 'index', array( $this, 'generate_sitemap_index_xml' ) );
		} else {
			$xml = $this->get_cached_xml( 'page-1', array( $this, 'generate_sitemap_xml' ), array( 1 ) );
		}

		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML is escaped during generation.
		exit;
	}

	/**
	 * Generates one URL-set page.
	 *
	 * @param int $page One-based sitemap page number.
	 * @return string
	 */
	public function generate_sitemap_xml( $page = 1 ) {
		$page           = max( 1, absint( $page ) );
		$per_page       = $this->get_urls_per_page();
		$urls           = $this->get_generated_urls( $per_page, ( $page - 1 ) * $per_page );
		$stylesheet_url = apply_filters( 'ch_pseo_sitemap_stylesheet_url', $this->get_stylesheet_url() );
		$xml            = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

		if ( $stylesheet_url ) {
			$xml .= '<?xml-stylesheet type="text/xsl" href="' . esc_xml( $stylesheet_url ) . '"?>' . "\n";
		}
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ( $urls as $item ) {
			$xml .= "\t<url>\n\t\t<loc>" . esc_xml( $item['url'] ) . "</loc>\n";
			if ( ! empty( $item['lastmod'] ) ) {
				$xml .= "\t\t<lastmod>" . esc_xml( $item['lastmod'] ) . "</lastmod>\n";
			}
			$xml .= "\t</url>\n";
		}
		$xml .= '</urlset>';

		return apply_filters( 'ch_pseo_sitemap_xml', $xml, $urls, $page );
	}

	/**
	 * Generates the numbered sitemap index.
	 *
	 * @return string
	 */
	public function generate_sitemap_index_xml() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		for ( $page = 1; $page <= $this->get_sitemap_page_count(); $page++ ) {
			$xml .= "\t<sitemap>\n\t\t<loc>" . esc_xml( $this->get_sitemap_page_url( $page ) ) . "</loc>\n\t</sitemap>\n";
		}
		$xml .= '</sitemapindex>';
		return apply_filters( 'ch_pseo_sitemap_index_xml', $xml, $this->get_sitemap_page_count() );
	}

	/**
	 * Returns generated URLs for sitemap pages and CSV export.
	 *
	 * @param int $limit  Maximum rows. Use 0 for all rows.
	 * @param int $offset Starting row offset.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_generated_urls( $limit = 0, $offset = 0 ) {
		$rows = $this->get_eligible_rows( absint( $limit ), absint( $offset ) );
		$urls = array();

		foreach ( $rows as $row ) {
			$segments = $this->get_location_segments( $row );
			if ( false === $segments ) {
				continue;
			}
			$urls[] = array(
				'service_location_id' => (int) $row['service_location_id'],
				'url'                 => ch_pseo_get_generated_url( $row['url_base'], $row['service_slug'], $segments ),
				'lastmod'             => $this->format_last_modified( $row['effective_updated_at'] ),
				'service_name'        => $row['service_name'],
				'service_slug'        => $row['service_slug'],
				'location_structure'  => $row['location_structure'],
				'country_name'        => $row['country_name'],
				'state_name'          => $row['state_name'],
				'city_name'           => $row['city_name'],
				'robots'              => $row['effective_robots'],
			);
		}

		return apply_filters( 'ch_pseo_generated_sitemap_urls', $urls );
	}

	/**
	 * Gets the total number of eligible URL rows.
	 *
	 * @return int
	 */
	public function get_generated_url_count() {
		global $wpdb;
		$query = $this->get_eligible_query( 'COUNT(*)' );
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $this->get_eligible_query_parameters() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Gets the number of sitemap URL pages.
	 *
	 * @return int
	 */
	public function get_sitemap_page_count() {
		return max( 1, (int) ceil( $this->get_generated_url_count() / $this->get_urls_per_page() ) );
	}

	/**
	 * Clears all logical sitemap caches by rotating their cache generation.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		delete_transient( self::CACHE_KEY );
		update_option( self::CACHE_VERSION_OPTION, (int) get_option( self::CACHE_VERSION_OPTION, 1 ) + 1, false );

		if ( class_exists( 'WPSEO_Sitemaps_Cache' ) ) {
			WPSEO_Sitemaps_Cache::clear();
		}
	}

	/**
	 * Gets the main public sitemap URL.
	 *
	 * @return string
	 */
	public function get_sitemap_url() {
		return home_url( '/' . $this->get_sitemap_slug() );
	}

	/**
	 * Gets a numbered child sitemap URL.
	 *
	 * @param int $page One-based page number.
	 * @return string
	 */
	public function get_sitemap_page_url( $page ) {
		return home_url( '/' . $this->get_sitemap_stem() . '-' . max( 1, absint( $page ) ) . '.xml' );
	}

	/**
	 * Gets or generates one cache-generation-specific XML document.
	 *
	 * @param string   $suffix   Cache key suffix.
	 * @param callable $callback XML generator.
	 * @param array    $args     Generator arguments.
	 * @return string
	 */
	private function get_cached_xml( $suffix, $callback, $args = array() ) {
		$version = (int) get_option( self::CACHE_VERSION_OPTION, 1 );
		$key     = 'ch_pseo_sitemap_v2_' . $version . '_' . sanitize_key( $suffix );
		$xml     = get_transient( $key );
		if ( ! is_string( $xml ) || '' === $xml ) {
			$xml = (string) call_user_func_array( $callback, $args );
			set_transient( $key, $xml, 12 * HOUR_IN_SECONDS );
		}
		return $xml;
	}

	/**
	 * Gets eligible rows using a bounded SQL page.
	 *
	 * @param int $limit  Row limit.
	 * @param int $offset Row offset.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_eligible_rows( $limit, $offset ) {
		global $wpdb;
		$fields = "sl.id AS service_location_id,
			GREATEST(
				COALESCE(sl.updated_at, '1970-01-01 00:00:00'),
				COALESCE(s.updated_at, '1970-01-01 00:00:00'),
				COALESCE(p.post_modified_gmt, '1970-01-01 00:00:00')
			) AS effective_updated_at,
			s.service_name, s.service_slug, s.url_base, s.location_structure,
			COALESCE(NULLIF(sl.robots, ''), s.robots_default) AS effective_robots,
			sl.country_id, sl.state_id, sl.city_id,
			co.name AS country_name, co.slug AS country_slug,
			st.name AS state_name, st.slug AS state_slug,
			ci.name AS city_name, ci.slug AS city_slug";
		$query  = $this->get_eligible_query( $fields ) . ' ORDER BY sl.id ASC';
		if ( $limit ) {
			$query .= ' LIMIT ' . absint( $limit ) . ' OFFSET ' . absint( $offset );
		}
		return $wpdb->get_results( $wpdb->prepare( $query, $this->get_eligible_query_parameters() ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Builds the shared eligible-row SQL query.
	 *
	 * @param string $fields SELECT fields.
	 * @return string
	 */
	private function get_eligible_query( $fields ) {
		global $wpdb;
		$tables = $this->database->get_table_names();
		return "SELECT {$fields}
			FROM {$tables['service_locations']} sl
			INNER JOIN {$tables['services']} s ON s.id = sl.service_id AND s.status = %s
			INNER JOIN {$wpdb->posts} p ON p.ID = s.template_page_id AND p.post_type = %s AND p.post_status = %s
			LEFT JOIN {$tables['countries']} co ON co.id = sl.country_id AND co.status = %s
			LEFT JOIN {$tables['states']} st ON st.id = sl.state_id AND st.status = %s
			LEFT JOIN {$tables['cities']} ci ON ci.id = sl.city_id AND ci.status = %s
			WHERE sl.status = %s
				AND COALESCE(sl.sitemap_include, s.sitemap_include_default) = 1
				AND COALESCE(NULLIF(sl.robots, ''), s.robots_default) IN (%s, %s)
				AND (
					(s.location_structure = 'country' AND co.slug IS NOT NULL)
					OR (s.location_structure = 'state' AND st.slug IS NOT NULL)
					OR (s.location_structure = 'country_state' AND co.slug IS NOT NULL AND (sl.state_id = 0 OR st.slug IS NOT NULL))
					OR (s.location_structure = 'state_city' AND st.slug IS NOT NULL AND (sl.city_id = 0 OR ci.slug IS NOT NULL))
					OR (s.location_structure = 'country_state_city' AND co.slug IS NOT NULL
						AND (sl.state_id = 0 OR st.slug IS NOT NULL)
						AND (sl.city_id = 0 OR (st.slug IS NOT NULL AND ci.slug IS NOT NULL)))
				)";
	}

	/**
	 * Gets shared prepared-query parameters.
	 *
	 * @return string[]
	 */
	private function get_eligible_query_parameters() {
		return array( 'active', 'page', 'publish', 'active', 'active', 'active', 'active', 'index_follow', 'index_nofollow' );
	}

	/**
	 * Resolves URL segments for one eligible row.
	 *
	 * @param array $row Mapping and location data.
	 * @return string[]|false
	 */
	private function get_location_segments( $row ) {
		switch ( $row['location_structure'] ) {
			case 'country':
				return array( $row['country_slug'] );
			case 'state':
				return array( $row['state_slug'] );
			case 'country_state':
				return $row['state_slug'] ? array( $row['country_slug'], $row['state_slug'] ) : array( $row['country_slug'] );
			case 'state_city':
				return $row['city_slug'] ? array( $row['state_slug'], $row['city_slug'] ) : array( $row['state_slug'] );
			case 'country_state_city':
				$segments = array( $row['country_slug'] );
				if ( $row['state_slug'] ) {
					$segments[] = $row['state_slug'];
				}
				if ( $row['city_slug'] ) {
					$segments[] = $row['city_slug'];
				}
				return $segments;
			default:
				return false;
		}
	}

	/**
	 * Gets the optional Yoast sitemap stylesheet URL.
	 *
	 * @return string
	 */
	private function get_stylesheet_url() {
		return defined( 'WPSEO_VERSION' ) ? home_url( '/main-sitemap.xsl' ) : '';
	}

	/**
	 * Formats a MySQL date for sitemap output.
	 *
	 * @param string $date MySQL date.
	 * @return string
	 */
	private function format_last_modified( $date ) {
		$timestamp = $date ? strtotime( $date ) : false;
		return $timestamp ? gmdate( 'c', $timestamp ) : '';
	}

	/**
	 * Determines whether sitemap output is enabled.
	 *
	 * @return bool
	 */
	private function is_enabled() {
		return '1' === (string) $this->get_setting( 'sitemap_enabled', '1' );
	}

	/**
	 * Gets the configured URL count per child sitemap.
	 *
	 * @return int
	 */
	private function get_urls_per_page() {
		return max( 1, min( 50000, absint( $this->get_setting( 'sitemap_max_urls', '50000' ) ) ) );
	}

	/**
	 * Gets the configured sitemap filename.
	 *
	 * @return string
	 */
	private function get_sitemap_slug() {
		$slug = sanitize_file_name( $this->get_setting( 'sitemap_slug', 'ch-pseo-pages-sitemap.xml' ) );
		return $slug ? $slug : 'ch-pseo-pages-sitemap.xml';
	}

	/**
	 * Gets the filename stem used by child sitemap pages.
	 *
	 * @return string
	 */
	private function get_sitemap_stem() {
		return preg_replace( '/\.xml$/i', '', $this->get_sitemap_slug() );
	}

	/**
	 * Gets exact index and child rewrite rules.
	 *
	 * @return array<string, string>
	 */
	private function get_rewrite_rules() {
		return array(
			'^' . preg_quote( $this->get_sitemap_slug(), '#' ) . '$' => 'index.php?ch_pseo_sitemap=1',
			'^' . preg_quote( $this->get_sitemap_stem(), '#' ) . '-([0-9]+)\\.xml$' => 'index.php?ch_pseo_sitemap=1&ch_pseo_sitemap_page=$matches[1]',
		);
	}

	/**
	 * Gets a custom-table setting.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private function get_setting( $key, $default = '' ) {
		global $wpdb;
		if ( array_key_exists( $key, $this->settings ) ) {
			return $this->settings[ $key ];
		}
		$table = $this->database->get_settings_table();
		$value = $wpdb->get_var(
			$wpdb->prepare( "SELECT setting_value FROM {$table} WHERE setting_key = %s ORDER BY id DESC LIMIT 1", $key )
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->settings[ $key ] = null === $value ? $default : $value;
		return $this->settings[ $key ];
	}
}
