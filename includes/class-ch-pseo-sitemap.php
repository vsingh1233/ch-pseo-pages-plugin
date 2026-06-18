<?php
/**
 * Custom PSEO sitemap integration.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders a cached sitemap for dynamic PSEO URLs.
 */
class CH_PSEO_Sitemap {

	/**
	 * Cached XML transient key.
	 *
	 * @var string
	 */
	const CACHE_KEY = 'ch_pseo_sitemap_xml_v1';

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
		add_action( 'init', array( $this, 'register_rewrite_rules' ) );
		add_action( 'template_redirect', array( $this, 'render_sitemap' ), -10 );
		add_filter( 'wpseo_sitemap_index', array( $this, 'add_to_yoast_sitemap_index' ) );
	}

	/**
	 * Registers the internal sitemap query variable.
	 *
	 * @param string[] $query_vars Public query variables.
	 * @return string[]
	 */
	public function register_query_vars( $query_vars ) {
		$query_vars[] = 'ch_pseo_sitemap';
		return $query_vars;
	}

	/**
	 * Registers the configured sitemap endpoint.
	 *
	 * @return void
	 */
	public function register_rewrite_rules() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$slug = $this->get_sitemap_slug();
		if ( $slug ) {
			add_rewrite_rule( '^' . preg_quote( $slug, '#' ) . '$', 'index.php?ch_pseo_sitemap=1', 'top' );
		}
	}

	/**
	 * Adds one custom sitemap entry to Yoast's sitemap index.
	 *
	 * @param string $sitemap_index Existing sitemap index XML fragments.
	 * @return string
	 */
	public function add_to_yoast_sitemap_index( $sitemap_index ) {
		if ( ! $this->is_enabled() ) {
			return $sitemap_index;
		}

		if ( false !== strpos( $sitemap_index, $this->get_sitemap_url() ) ) {
			return $sitemap_index;
		}

		$sitemap_index .= "\n<sitemap>\n";
		$sitemap_index .= "\t<loc>" . esc_xml( $this->get_sitemap_url() ) . "</loc>\n";
		$sitemap_index .= "</sitemap>\n";

		return $sitemap_index;
	}

	/**
	 * Outputs the dynamic sitemap XML endpoint.
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

		$xml = get_transient( self::CACHE_KEY );
		if ( ! is_string( $xml ) || '' === $xml ) {
			$xml = $this->generate_sitemap_xml();
			set_transient( self::CACHE_KEY, $xml, 12 * HOUR_IN_SECONDS );
		}

		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: application/xml; charset=UTF-8' );
		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML is escaped during generation.
		exit;
	}

	/**
	 * Generates sitemap XML from eligible service-location mappings.
	 *
	 * @return string
	 */
	public function generate_sitemap_xml() {
		$limit          = max( 1, min( 50000, absint( $this->get_setting( 'sitemap_max_urls', '50000' ) ) ) );
		$urls           = $this->get_generated_urls( $limit );
		$stylesheet_url = apply_filters( 'ch_pseo_sitemap_stylesheet_url', $this->get_stylesheet_url() );
		$xml            = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

		if ( $stylesheet_url ) {
			$xml .= '<?xml-stylesheet type="text/xsl" href="' . esc_xml( $stylesheet_url ) . '"?>' . "\n";
		}

		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $urls as $item ) {
			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . esc_xml( $item['url'] ) . "</loc>\n";
			if ( ! empty( $item['lastmod'] ) ) {
				$xml .= "\t\t<lastmod>" . esc_xml( $item['lastmod'] ) . "</lastmod>\n";
			}
			$xml .= "\t</url>\n";
		}

		$xml .= '</urlset>';

		return apply_filters( 'ch_pseo_sitemap_xml', $xml, $urls );
	}

	/**
	 * Returns all generated URLs eligible for the sitemap and CSV export.
	 *
	 * @param int $limit Maximum rows. Use 0 for all eligible URLs.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_generated_urls( $limit = 0 ) {
		global $wpdb;

		$tables = $this->database->get_table_names();
		$limit  = absint( $limit );
		$limit_clause = $limit ? ' LIMIT ' . $limit : '';
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					sl.id AS service_location_id,
					sl.updated_at AS mapping_updated_at,
					s.service_name,
					s.service_slug,
					s.url_base,
					s.location_structure,
					COALESCE(NULLIF(sl.robots, ''), s.robots_default) AS effective_robots,
					sl.country_id,
					sl.state_id,
					sl.city_id,
					co.name AS country_name,
					co.slug AS country_slug,
					st.name AS state_name,
					st.slug AS state_slug,
					ci.name AS city_name,
					ci.slug AS city_slug
				FROM {$tables['service_locations']} sl
				INNER JOIN {$tables['services']} s
					ON s.id = sl.service_id AND s.status = %s
				INNER JOIN {$wpdb->posts} p
					ON p.ID = s.template_page_id AND p.post_type = %s AND p.post_status = %s
				LEFT JOIN {$tables['countries']} co
					ON co.id = sl.country_id AND co.status = %s
				LEFT JOIN {$tables['states']} st
					ON st.id = sl.state_id AND st.status = %s
				LEFT JOIN {$tables['cities']} ci
					ON ci.id = sl.city_id AND ci.status = %s
				WHERE sl.status = %s
					AND COALESCE(sl.sitemap_include, s.sitemap_include_default) = 1
					AND COALESCE(NULLIF(sl.robots, ''), s.robots_default) IN (%s, %s)
				ORDER BY s.service_name, co.name, st.name, ci.name" . $limit_clause,
				'active',
				'page',
				'publish',
				'active',
				'active',
				'active',
				'active',
				'index_follow',
				'index_nofollow'
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$urls = array();
		$seen = array();

		foreach ( $rows as $row ) {
			$segments = $this->get_location_segments( $row );
			if ( false === $segments ) {
				continue;
			}

			$path = trim( $row['url_base'], '/' ) . '/' . implode( '/', $segments );
			$url  = home_url( user_trailingslashit( $path ) );
			if ( isset( $seen[ $url ] ) ) {
				continue;
			}
			$seen[ $url ] = true;

			$urls[] = array(
				'service_location_id' => (int) $row['service_location_id'],
				'url'                 => $url,
				'lastmod'             => $this->format_last_modified( $row['mapping_updated_at'] ),
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
	 * Clears the cached sitemap XML.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Gets the public sitemap URL.
	 *
	 * @return string
	 */
	public function get_sitemap_url() {
		return home_url( '/' . $this->get_sitemap_slug() );
	}

	/**
	 * Resolves URL segments for one mapping row.
	 *
	 * @param array $row Mapping and location data.
	 * @return string[]|false
	 */
	private function get_location_segments( $row ) {
		if (
			( $row['country_id'] && ! $row['country_slug'] )
			|| ( $row['state_id'] && ! $row['state_slug'] )
			|| ( $row['city_id'] && ! $row['city_slug'] )
		) {
			return false;
		}

		switch ( $row['location_structure'] ) {
			case 'country':
				return $row['country_slug'] ? array( $row['country_slug'] ) : false;
			case 'country_state':
				if ( ! $row['country_slug'] ) {
					return false;
				}
				return $row['state_slug']
					? array( $row['country_slug'], $row['state_slug'] )
					: array( $row['country_slug'] );
			case 'country_state_city':
				if ( ! $row['country_slug'] ) {
					return false;
				}
				$segments = array( $row['country_slug'] );
				if ( $row['state_slug'] ) {
					$segments[] = $row['state_slug'];
				}
				if ( $row['city_slug'] ) {
					if ( ! $row['state_slug'] ) {
						return false;
					}
					$segments[] = $row['city_slug'];
				}
				return $segments;
			case 'state':
				return $row['state_slug'] ? array( $row['state_slug'] ) : false;
			case 'state_city':
				if ( ! $row['state_slug'] ) {
					return false;
				}
				return $row['city_slug']
					? array( $row['state_slug'], $row['city_slug'] )
					: array( $row['state_slug'] );
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
		if ( defined( 'WPSEO_VERSION' ) ) {
			return home_url( '/main-sitemap.xsl' );
		}

		return '';
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
	 * Gets the configured, sanitized sitemap filename.
	 *
	 * @return string
	 */
	private function get_sitemap_slug() {
		$slug = sanitize_file_name( $this->get_setting( 'sitemap_slug', 'ch-pseo-pages-sitemap.xml' ) );
		return $slug ? $slug : 'ch-pseo-pages-sitemap.xml';
	}

	/**
	 * Gets a value from the custom settings table.
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
			$wpdb->prepare(
				"SELECT setting_value FROM {$table} WHERE setting_key = %s ORDER BY id DESC LIMIT 1",
				$key
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->settings[ $key ] = null === $value ? $default : $value;
		return $this->settings[ $key ];
	}
}
