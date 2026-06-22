<?php
/**
 * Structured data integration.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds Yoast schema pieces and standalone JSON-LD for PSEO requests.
 */
class CH_PSEO_Schema {

	/**
	 * Current request context.
	 *
	 * @var CH_PSEO_Context
	 */
	private $context;

	/**
	 * Database service.
	 *
	 * @var CH_PSEO_Database
	 */
	private $database;

	/**
	 * SEO metadata service.
	 *
	 * @var CH_PSEO_SEO
	 */
	private $seo;

	/**
	 * Per-request plugin setting cache.
	 *
	 * @var array<string, mixed>
	 */
	private $settings = array();

	/**
	 * Constructor.
	 *
	 * @param CH_PSEO_Context  $context  Request context.
	 * @param CH_PSEO_Database $database Database service.
	 * @param CH_PSEO_SEO      $seo      SEO metadata service.
	 */
	public function __construct( CH_PSEO_Context $context, CH_PSEO_Database $database, CH_PSEO_SEO $seo ) {
		$this->context  = $context;
		$this->database = $database;
		$this->seo      = $seo;
	}

	/**
	 * Registers Yoast schema and standalone JSON-LD hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'wpseo_schema_graph', array( $this, 'filter_yoast_graph' ), 20, 2 );
		add_action( 'wp_head', array( $this, 'render_standalone_schema' ), 20 );
	}

	/**
	 * Adds the PSEO piece to Yoast's schema graph.
	 *
	 * @param array $graph         Existing schema graph.
	 * @param mixed $yoast_context Yoast schema context.
	 * @return array
	 */
	public function filter_yoast_graph( $graph, $yoast_context = null ) {
		if ( ! $this->seo->should_filter_yoast() || ! $this->is_enabled() || ! is_array( $graph ) ) {
			return $graph;
		}

		$graph       = $this->normalize_yoast_graph( $graph );
		$schema_type = $this->get_schema_type();
		if ( 'Organization' === $schema_type && $this->graph_contains_type( $graph, 'Organization' ) ) {
			return $graph;
		}

		$piece = $this->build_piece( $graph, $yoast_context );
		if ( $piece ) {
			$graph[] = $piece;
		}

		return apply_filters( 'ch_pseo_schema_graph', $graph, $piece, $this->context->get_all(), $yoast_context );
	}

	/**
	 * Outputs standalone JSON-LD when Yoast is unavailable.
	 *
	 * @return void
	 */
	public function render_standalone_schema() {
		if ( ! $this->seo->should_render_standalone() || ! $this->is_enabled() ) {
			return;
		}

		$graph       = array();
		$schema_type = $this->get_schema_type();

		if ( in_array( $schema_type, array( 'Service', 'ProfessionalService', 'LegalService' ), true ) ) {
			$organization = $this->build_organization_piece();
			if ( $organization ) {
				$graph[] = $organization;
			}
		}

		$piece = $this->build_piece( $graph );
		if ( $piece ) {
			$graph[] = $piece;
		}

		$graph = apply_filters( 'ch_pseo_schema_graph', $graph, $piece, $this->context->get_all(), null );
		if ( ! is_array( $graph ) || empty( $graph ) ) {
			return;
		}

		$json_ld = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);

		echo '<script type="application/ld+json" class="ch-pseo-schema">' . wp_json_encode( $json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/**
	 * Builds the primary PSEO schema piece.
	 *
	 * @param array $graph         Existing schema graph.
	 * @param mixed $yoast_context Optional Yoast schema context.
	 * @return array<string, mixed>
	 */
	public function build_piece( $graph = array(), $yoast_context = null ) {
		$schema_type = $this->get_schema_type();
		$canonical   = $this->seo->get_canonical_url();
		$piece       = array(
			'@type'       => $schema_type,
			'@id'         => trailingslashit( $canonical ) . '#' . strtolower( $schema_type ),
			'url'         => $canonical,
			'name'        => $this->seo->get_title(),
			'description' => $this->seo->get_meta_description(),
		);

		if ( in_array( $schema_type, array( 'Service', 'ProfessionalService', 'LegalService' ), true ) ) {
			$piece['areaServed'] = array(
				'@type' => $this->schema_place_type(),
				'name'  => $this->context->get( 'location_full' ),
			);

			$organization_id = $this->find_graph_id_by_type( $graph, 'Organization' );
			if ( $organization_id ) {
				$piece['provider'] = array( '@id' => $organization_id );
			}
		}

		if ( 'Organization' === $schema_type ) {
			$piece['name'] = $this->get_setting( 'schema_organization_name', get_bloginfo( 'name' ) );
		}

		if ( 'WebPage' === $schema_type ) {
			$piece['isPartOf'] = array( '@id' => home_url( '/#website' ) );
		}

		$piece = apply_filters( 'ch_pseo_schema_piece', $piece, $schema_type, $this->context->get_all(), $yoast_context );
		return is_array( $piece ) ? $piece : array();
	}

	/**
	 * Updates Yoast's page-specific graph values for the current virtual URL.
	 *
	 * Yoast builds its graph from the reusable WordPress template page. The
	 * graph types and site-wide values remain Yoast-controlled; only values
	 * identifying the current page are replaced here.
	 *
	 * @param array $graph Existing Yoast schema graph.
	 * @return array
	 */
	private function normalize_yoast_graph( $graph ) {
		$canonical     = $this->seo->get_canonical_url();
		$webpage_id    = trailingslashit( $canonical ) . '#webpage';
		$image_id      = trailingslashit( $canonical ) . '#primaryimage';
		$breadcrumb_id = trailingslashit( $canonical ) . '#breadcrumb';

		foreach ( $graph as &$piece ) {
			if ( ! is_array( $piece ) ) {
				continue;
			}

			if ( $this->piece_has_type( $piece, 'WebPage' ) ) {
				$piece['@id']         = $webpage_id;
				$piece['url']         = $canonical;
				$piece['name']        = $this->seo->get_title();
				$piece['description'] = $this->seo->get_meta_description();
				$piece['breadcrumb']  = array( '@id' => $breadcrumb_id );

				if ( isset( $piece['primaryImageOfPage'] ) ) {
					$piece['primaryImageOfPage'] = array( '@id' => $image_id );
				}
				if ( isset( $piece['image'] ) ) {
					$piece['image'] = array( '@id' => $image_id );
				}
				if ( ! empty( $piece['potentialAction'] ) && is_array( $piece['potentialAction'] ) ) {
					foreach ( $piece['potentialAction'] as &$action ) {
						if ( is_array( $action ) && $this->piece_has_type( $action, 'ReadAction' ) ) {
							$action['target'] = array( $canonical );
						}
					}
					unset( $action );
				}
			} elseif ( $this->piece_has_type( $piece, 'ImageObject' ) ) {
				$piece['@id'] = $image_id;
			} elseif ( $this->piece_has_type( $piece, 'BreadcrumbList' ) ) {
				$piece['@id']             = $breadcrumb_id;
				$piece['itemListElement'] = $this->build_breadcrumb_items();
			}
		}
		unset( $piece );

		return $graph;
	}

	/**
	 * Builds a fully linked breadcrumb hierarchy for the generated page.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function build_breadcrumb_items() {
		$service  = $this->context->get_service();
		$position = 1;
		$items    = array(
			array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => __( 'Home', 'ch-pseo-pages-plugin' ),
				'item'     => home_url( '/' ),
			),
		);
		$path     = '';

		foreach ( array_filter( explode( '/', ch_pseo_normalize_url_path( $service['url_base'] ) ) ) as $segment ) {
			$path    = ltrim( $path . '/' . $segment, '/' );
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => ucwords( str_replace( '-', ' ', $segment ) ),
				'item'     => home_url( user_trailingslashit( $path ) ),
			);
		}

		$path    = ch_pseo_get_service_route( $service['url_base'], $service['service_slug'] );
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => $service['service_name'],
			'item'     => home_url( user_trailingslashit( $path ) ),
		);

		foreach ( $this->get_breadcrumb_locations( $service['location_structure'] ) as $location ) {
			$path    .= '/' . $location['slug'];
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => $location['name'],
				'item'     => home_url( user_trailingslashit( $path ) ),
			);
		}

		return $items;
	}

	/**
	 * Gets resolved locations in route order.
	 *
	 * @param string $structure Service location structure.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_breadcrumb_locations( $structure ) {
		$country = $this->context->get( 'country', array() );
		$state   = $this->context->get( 'state', array() );
		$city    = $this->context->get( 'city', array() );
		$sets    = array(
			'country'            => array( $country ),
			'country_state'      => array( $country, $state ),
			'country_state_city' => array( $country, $state, $city ),
			'state'              => array( $state ),
			'state_city'         => array( $state, $city ),
		);

		return isset( $sets[ $structure ] )
			? array_values(
				array_filter(
					$sets[ $structure ],
					static function ( $location ) {
						return ! empty( $location['name'] ) && ! empty( $location['slug'] );
					}
				)
			)
			: array();
	}

	/**
	 * Checks whether one schema piece declares a type.
	 *
	 * @param array  $piece Schema piece.
	 * @param string $type  Schema type.
	 * @return bool
	 */
	private function piece_has_type( $piece, $type ) {
		if ( empty( $piece['@type'] ) ) {
			return false;
		}

		$types = is_array( $piece['@type'] ) ? $piece['@type'] : array( $piece['@type'] );
		return in_array( $type, $types, true );
	}

	/**
	 * Builds a standalone organization provider piece.
	 *
	 * @return array<string, string>
	 */
	private function build_organization_piece() {
		$name = trim( $this->get_setting( 'schema_organization_name', get_bloginfo( 'name' ) ) );
		if ( '' === $name ) {
			return array();
		}

		return array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => $name,
			'url'   => home_url( '/' ),
		);
	}

	/**
	 * Gets the validated schema type.
	 *
	 * @return string
	 */
	private function get_schema_type() {
		$allowed_types = array( 'Service', 'ProfessionalService', 'LegalService', 'Organization', 'WebPage' );
		$schema_type   = $this->context->get( 'schema_type' );

		if ( ! in_array( $schema_type, $allowed_types, true ) ) {
			$schema_type = $this->get_setting( 'schema_default_type', 'Service' );
		}

		return in_array( $schema_type, $allowed_types, true ) ? $schema_type : 'Service';
	}

	/**
	 * Determines whether schema output is enabled.
	 *
	 * @return bool
	 */
	private function is_enabled() {
		return $this->context->is_pseo_request()
			&& '1' === (string) $this->get_setting( 'schema_enabled', '1' );
	}

	/**
	 * Checks whether the graph already contains a schema type.
	 *
	 * @param array  $graph Schema graph.
	 * @param string $type  Schema type.
	 * @return bool
	 */
	private function graph_contains_type( $graph, $type ) {
		return (bool) $this->find_graph_id_by_type( $graph, $type, true );
	}

	/**
	 * Finds a graph piece ID by schema type.
	 *
	 * @param array  $graph             Schema graph.
	 * @param string $type              Schema type.
	 * @param bool   $return_boolean_id Return a truthy placeholder without an ID.
	 * @return string|false
	 */
	private function find_graph_id_by_type( $graph, $type, $return_boolean_id = false ) {
		foreach ( $graph as $piece ) {
			if ( ! is_array( $piece ) || empty( $piece['@type'] ) ) {
				continue;
			}

			$types = is_array( $piece['@type'] ) ? $piece['@type'] : array( $piece['@type'] );
			if ( in_array( $type, $types, true ) ) {
				if ( ! empty( $piece['@id'] ) ) {
					return $piece['@id'];
				}
				return $return_boolean_id ? 'present' : false;
			}
		}

		return false;
	}

	/**
	 * Maps the current location level to a Schema.org place type.
	 *
	 * @return string
	 */
	private function schema_place_type() {
		$types = array(
			'country' => 'Country',
			'state'   => 'AdministrativeArea',
			'city'    => 'City',
		);
		$type  = $this->context->get( 'location_type' );

		return isset( $types[ $type ] ) ? $types[ $type ] : 'Place';
	}

	/**
	 * Gets a value from the plugin's custom settings table.
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
