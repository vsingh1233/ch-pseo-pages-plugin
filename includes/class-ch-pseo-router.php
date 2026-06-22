<?php
/**
 * Dynamic request routing.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and resolves dynamic PSEO URLs without creating WordPress pages.
 */
class CH_PSEO_Router {

	/**
	 * Cached rewrite-definition transient key.
	 *
	 * @var string
	 */
	const REWRITE_DEFINITIONS_CACHE_KEY = 'ch_pseo_rewrite_definitions_v1';

	/**
	 * Database service.
	 *
	 * @var CH_PSEO_Database
	 */
	private $database;

	/**
	 * Current request context.
	 *
	 * @var CH_PSEO_Context
	 */
	private $context;

	/**
	 * Whether the current request matched a PSEO rewrite rule.
	 *
	 * @var bool
	 */
	private $attempted_request = false;

	/**
	 * Constructor.
	 *
	 * @param CH_PSEO_Database $database Database service.
	 * @param CH_PSEO_Context  $context  Request context.
	 */
	public function __construct( CH_PSEO_Database $database, CH_PSEO_Context $context ) {
		$this->database = $database;
		$this->context  = $context;
	}

	/**
	 * Registers routing hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'init', array( $this, 'register_rewrite_rules' ) );
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 20 );
		add_action( 'parse_request', array( $this, 'resolve_request' ) );
		add_action( 'template_redirect', array( $this, 'force_404' ), 0 );
		add_filter( 'redirect_canonical', array( $this, 'disable_canonical_redirect' ) );
		add_filter( 'template_include', array( $this, 'select_template' ) );
	}

	/**
	 * Registers internal query variables used by dynamic routes.
	 *
	 * @param string[] $query_vars Public query variables.
	 * @return string[]
	 */
	public function register_query_vars( $query_vars ) {
		$query_vars[] = 'ch_pseo_service';
		$query_vars[] = 'ch_pseo_path_1';
		$query_vars[] = 'ch_pseo_path_2';
		$query_vars[] = 'ch_pseo_path_3';

		return $query_vars;
	}

	/**
	 * Registers active service rewrite rules.
	 *
	 * The service base itself is intentionally never registered. Only paths
	 * containing at least one location segment are captured.
	 *
	 * @return void
	 */
	public function register_rewrite_rules() {
		foreach ( $this->get_rewrite_definitions() as $definition ) {
			add_rewrite_rule( $definition['regex'], $definition['query'], 'top' );
		}
	}

	/**
	 * Clears cached rewrite definitions.
	 *
	 * @return void
	 */
	public static function clear_rewrite_definitions_cache() {
		delete_transient( self::REWRITE_DEFINITIONS_CACHE_KEY );
	}

	/**
	 * Gets cached rewrite definitions or builds them from active services.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function get_rewrite_definitions() {
		$definitions = get_transient( self::REWRITE_DEFINITIONS_CACHE_KEY );
		if ( is_array( $definitions ) ) {
			return $definitions;
		}

		global $wpdb;
		$tables     = $this->database->get_table_names();
		$services   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, service_slug, url_base, location_structure FROM {$tables['services']} WHERE status = %s",
				'active'
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exclusions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT service_id, excluded_slug FROM {$tables['url_exclusions']} WHERE status = %s",
				'active'
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$by_service = array( 0 => array() );

		foreach ( $exclusions as $exclusion ) {
			$key = $exclusion['service_id'] ? (int) $exclusion['service_id'] : 0;
			$by_service[ $key ][] = $exclusion['excluded_slug'];
		}

		$definitions = array();
		foreach ( $services as $service ) {
			$route = ch_pseo_get_service_route( $service['url_base'], $service['service_slug'] );
			if ( '' === $route ) {
				continue;
			}
			$service_exclusions = array_merge(
				$by_service[0],
				isset( $by_service[ (int) $service['id'] ] ) ? $by_service[ (int) $service['id'] ] : array()
			);
			$negative_lookahead = $this->build_exclusion_pattern( $service_exclusions );
			$route_pattern      = preg_quote( $route, '#' );
			$depth              = $this->get_structure_depth( $service['location_structure'] );

			for ( $segment_count = $depth; $segment_count >= 1; $segment_count-- ) {
				$captures = array();
				$query    = 'index.php?ch_pseo_service=' . absint( $service['id'] );
				for ( $index = 1; $index <= $segment_count; $index++ ) {
					$captures[] = '([^/]+)';
					$query     .= '&ch_pseo_path_' . $index . '=$matches[' . $index . ']';
				}
				$definitions[] = array(
					'regex' => '^' . $route_pattern . '/' . $negative_lookahead . implode( '/', $captures ) . '/?$',
					'query' => $query,
				);
			}
		}

		set_transient( self::REWRITE_DEFINITIONS_CACHE_KEY, $definitions, DAY_IN_SECONDS );
		return $definitions;
	}

	/**
	 * Flushes rules after an admin change once fresh rules are registered.
	 *
	 * @return void
	 */
	public function maybe_flush_rewrite_rules() {
		if ( ! get_option( 'ch_pseo_flush_rewrite_rules', false ) ) {
			return;
		}

		flush_rewrite_rules( false );
		delete_option( 'ch_pseo_flush_rewrite_rules' );
	}

	/**
	 * Resolves rewrite variables to active location rows and an active mapping.
	 *
	 * @param WP $wp Current WordPress environment.
	 * @return void
	 */
	public function resolve_request( $wp ) {
		global $wpdb;

		$service_id = isset( $wp->query_vars['ch_pseo_service'] ) ? absint( $wp->query_vars['ch_pseo_service'] ) : 0;

		if ( ! $service_id ) {
			return;
		}

		$this->attempted_request = true;
		$tables                  = $this->database->get_table_names();
		$service                 = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$tables['services']} WHERE id = %d AND status = %s LIMIT 1",
				$service_id,
				'active'
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $service || ! $service['template_page_id'] ) {
			$this->mark_request_as_404( $wp );
			return;
		}

		$template_page = get_post( (int) $service['template_page_id'] );
		if ( ! $template_page || 'page' !== $template_page->post_type || 'publish' !== $template_page->post_status ) {
			$this->mark_request_as_404( $wp );
			return;
		}

		$segments = array();
		for ( $index = 1; $index <= 3; $index++ ) {
			$key = 'ch_pseo_path_' . $index;
			if ( ! empty( $wp->query_vars[ $key ] ) ) {
				$segments[] = sanitize_title( wp_unslash( $wp->query_vars[ $key ] ) );
			}
		}

		if ( ! $this->is_valid_depth( $service['location_structure'], count( $segments ) ) ) {
			$this->mark_request_as_404( $wp );
			return;
		}

		$locations = $this->resolve_locations( $service['location_structure'], $segments );
		if ( false === $locations ) {
			$this->mark_request_as_404( $wp );
			return;
		}

		$country_id = ! empty( $locations['country']['id'] ) ? (int) $locations['country']['id'] : 0;
		$state_id   = ! empty( $locations['state']['id'] ) ? (int) $locations['state']['id'] : 0;
		$city_id    = ! empty( $locations['city']['id'] ) ? (int) $locations['city']['id'] : 0;
		$mapping    = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$tables['service_locations']}
				WHERE service_id = %d
				AND COALESCE(country_id, 0) = %d
				AND COALESCE(state_id, 0) = %d
				AND COALESCE(city_id, 0) = %d
				AND status = %s
				LIMIT 1",
				$service_id,
				$country_id,
				$state_id,
				$city_id,
				'active'
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $mapping ) {
			$this->mark_request_as_404( $wp );
			return;
		}

		$canonical = ch_pseo_get_generated_url( $service['url_base'], $service['service_slug'], $segments );
		$this->context->set_context( $service, $mapping, $locations, $canonical );

		unset( $wp->query_vars['error'], $wp->query_vars['name'], $wp->query_vars['pagename'] );
		$wp->query_vars['page_id']   = (int) $service['template_page_id'];
		$wp->query_vars['post_type'] = 'page';
	}

	/**
	 * Turns an unresolved captured route into a real WordPress 404.
	 *
	 * @return void
	 */
	public function force_404() {
		global $wp_query;

		if ( ! $this->attempted_request || $this->context->is_pseo_request() ) {
			return;
		}

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	/**
	 * Prevents WordPress from redirecting a virtual URL to the template page.
	 *
	 * @param string|false $redirect_url Proposed canonical redirect.
	 * @return string|false
	 */
	public function disable_canonical_redirect( $redirect_url ) {
		return $this->context->is_pseo_request() ? false : $redirect_url;
	}

	/**
	 * Uses the configured template page's page template for virtual requests.
	 *
	 * @param string $template Absolute template file path.
	 * @return string
	 */
	public function select_template( $template ) {
		if ( ! $this->context->is_pseo_request() ) {
			return $template;
		}

		$template_page_id = (int) $this->context->get( 'template_page_id' );
		$page_template    = get_page_template_slug( $template_page_id );

		if ( $page_template ) {
			$located_template = locate_template( $page_template );
			if ( $located_template ) {
				return $located_template;
			}
		}

		$default_template = get_page_template();
		return $default_template ? $default_template : $template;
	}

	/**
	 * Resolves location rows according to a service structure.
	 *
	 * @param string   $structure Service location structure.
	 * @param string[] $segments  Sanitized URL segments.
	 * @return array|false
	 */
	private function resolve_locations( $structure, $segments ) {
		global $wpdb;

		$tables    = $this->database->get_table_names();
		$locations = array(
			'country' => array(),
			'state'   => array(),
			'city'    => array(),
		);

		if ( 0 === strpos( $structure, 'country' ) ) {
			$locations['country'] = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$tables['countries']} WHERE slug = %s AND status = %s LIMIT 1", $segments[0], 'active' ),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( ! $locations['country'] ) {
				return false;
			}

			if ( isset( $segments[1] ) ) {
				$locations['state'] = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM {$tables['states']} WHERE country_id = %d AND slug = %s AND status = %s LIMIT 1",
						$locations['country']['id'],
						$segments[1],
						'active'
					),
					ARRAY_A
				); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				if ( ! $locations['state'] ) {
					return false;
				}
			}

			if ( isset( $segments[2] ) ) {
				$locations['city'] = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM {$tables['cities']}
						WHERE (country_id = %d OR country_id = 0) AND state_id = %d AND slug = %s AND status = %s LIMIT 1",
						$locations['country']['id'],
						$locations['state']['id'],
						$segments[2],
						'active'
					),
					ARRAY_A
				); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				if ( ! $locations['city'] ) {
					return false;
				}
			}
		} else {
			$locations['state'] = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$tables['states']} WHERE slug = %s AND status = %s LIMIT 1", $segments[0], 'active' ),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( ! $locations['state'] ) {
				return false;
			}

			if ( ! empty( $locations['state']['country_id'] ) ) {
				$locations['country'] = $wpdb->get_row(
					$wpdb->prepare( "SELECT * FROM {$tables['countries']} WHERE id = %d AND status = %s LIMIT 1", $locations['state']['country_id'], 'active' ),
					ARRAY_A
				); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				if ( ! $locations['country'] ) {
					return false;
				}
			}

			if ( isset( $segments[1] ) ) {
				$locations['city'] = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM {$tables['cities']} WHERE state_id = %d AND slug = %s AND status = %s LIMIT 1",
						$locations['state']['id'],
						$segments[1],
						'active'
					),
					ARRAY_A
				); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				if ( ! $locations['city'] ) {
					return false;
				}

				if ( ! empty( $locations['city']['country_id'] ) && ! empty( $locations['state']['country_id'] ) && (int) $locations['city']['country_id'] !== (int) $locations['state']['country_id'] ) {
					return false;
				}

				if ( empty( $locations['country'] ) && ! empty( $locations['city']['country_id'] ) ) {
					$locations['country'] = $wpdb->get_row(
						$wpdb->prepare( "SELECT * FROM {$tables['countries']} WHERE id = %d AND status = %s LIMIT 1", $locations['city']['country_id'], 'active' ),
						ARRAY_A
					); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

					if ( ! $locations['country'] ) {
						return false;
					}
				}
			}
		}

		return $locations;
	}

	/**
	 * Gets the maximum URL segment count for a structure.
	 *
	 * @param string $structure Location structure.
	 * @return int
	 */
	private function get_structure_depth( $structure ) {
		$depths = array(
			'country'            => 1,
			'country_state'      => 2,
			'country_state_city' => 3,
			'state'              => 1,
			'state_city'         => 2,
		);

		return isset( $depths[ $structure ] ) ? $depths[ $structure ] : 0;
	}

	/**
	 * Checks whether a captured depth is supported by the structure.
	 *
	 * @param string $structure Location structure.
	 * @param int    $depth     Captured segment count.
	 * @return bool
	 */
	private function is_valid_depth( $structure, $depth ) {
		$maximum = $this->get_structure_depth( $structure );
		return $maximum > 0 && $depth >= 1 && $depth <= $maximum;
	}

	/**
	 * Builds a first-segment negative lookahead for excluded child slugs.
	 *
	 * @param string[] $slugs Excluded slugs.
	 * @return string
	 */
	private function build_exclusion_pattern( $slugs ) {
		$patterns = array();

		foreach ( $slugs as $slug ) {
			$slug = sanitize_title( $slug );
			if ( $slug ) {
				$patterns[] = preg_quote( $slug, '#' );
			}
		}

		return $patterns ? '(?!(?:' . implode( '|', array_unique( $patterns ) ) . ')(?:/|$))' : '';
	}

	/**
	 * Marks a captured request as unresolved.
	 *
	 * @param WP $wp Current WordPress environment.
	 * @return void
	 */
	private function mark_request_as_404( $wp ) {
		$this->context->reset();
		$wp->query_vars['error'] = '404';
	}
}
