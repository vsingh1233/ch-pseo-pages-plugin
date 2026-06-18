<?php
/**
 * SEO and Yoast SEO integration.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Supplies dynamic Yoast metadata and schema for valid PSEO requests.
 */
class CH_PSEO_SEO {

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
	 * Guards against nested metadata template processing.
	 *
	 * @var bool
	 */
	private $processing_template = false;

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
	 */
	public function __construct( CH_PSEO_Context $context, CH_PSEO_Database $database ) {
		$this->context  = $context;
		$this->database = $database;
	}

	/**
	 * Registers Yoast filters.
	 *
	 * Registering these filters is safe when Yoast is inactive because WordPress
	 * simply never applies them.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'wpseo_title', array( $this, 'filter_title' ) );
		add_filter( 'wpseo_metadesc', array( $this, 'filter_meta_description' ) );
		add_filter( 'wpseo_canonical', array( $this, 'filter_canonical' ) );
		add_filter( 'wpseo_robots', array( $this, 'filter_robots' ) );
		add_filter( 'wpseo_schema_graph', array( $this, 'filter_schema_graph' ), 20, 2 );
	}

	/**
	 * Filters the Yoast title for a valid PSEO request.
	 *
	 * @param string $title Existing title.
	 * @return string
	 */
	public function filter_title( $title ) {
		if ( ! $this->should_filter_yoast() ) {
			return $title;
		}

		$mapping  = $this->context->get_service_location();
		$service  = $this->context->get_service();
		$template = $this->first_non_empty(
			array(
				$mapping['custom_meta_title'],
				$service['meta_title_template'],
				$this->get_setting( 'seo_global_title_template', '' ),
			)
		);

		if ( $template ) {
			$title = $this->process_template( $template );
		} else {
			$title = sprintf(
				/* translators: 1: service name, 2: location. */
				__( '%1$s in %2$s', 'ch-pseo-pages-plugin' ),
				$this->context->get( 'service_name' ),
				$this->context->get( 'location_full' )
			);
		}

		$suffix = trim( $this->get_setting( 'seo_default_title_suffix', '' ) );
		if ( $suffix ) {
			$title = trim( $title . ' ' . $this->process_template( $suffix ) );
		}

		return apply_filters( 'ch_pseo_meta_title', $title, $this->context->get_all() );
	}

	/**
	 * Filters the Yoast meta description for a valid PSEO request.
	 *
	 * @param string $description Existing description.
	 * @return string
	 */
	public function filter_meta_description( $description ) {
		if ( ! $this->should_filter_yoast() ) {
			return $description;
		}

		$mapping  = $this->context->get_service_location();
		$service  = $this->context->get_service();
		$template = $this->first_non_empty(
			array(
				$mapping['custom_meta_description'],
				$service['meta_description_template'],
				$this->get_setting( 'seo_global_description_template', '' ),
			)
		);

		if ( $template ) {
			$description = $this->process_template( $template );
		} else {
			$description = sprintf(
				/* translators: 1: service name, 2: location. */
				__( 'Learn more about %1$s in %2$s. Get local information, service details, and assistance for your needs.', 'ch-pseo-pages-plugin' ),
				$this->context->get( 'service_name' ),
				$this->context->get( 'location_full' )
			);
		}

		return apply_filters( 'ch_pseo_meta_description', $description, $this->context->get_all() );
	}

	/**
	 * Filters the Yoast canonical URL.
	 *
	 * @param string|false $canonical Existing canonical URL.
	 * @return string|false
	 */
	public function filter_canonical( $canonical ) {
		if ( ! $this->should_filter_yoast() ) {
			return $canonical;
		}

		$url = $this->context->get( 'canonical_url' );
		return $url ? esc_url_raw( $url ) : $canonical;
	}

	/**
	 * Filters the Yoast robots directive.
	 *
	 * @param string $robots Existing robots directive.
	 * @return string
	 */
	public function filter_robots( $robots ) {
		if ( ! $this->should_filter_yoast() ) {
			return $robots;
		}

		$directives = array(
			'index_follow'     => 'index, follow',
			'noindex_follow'   => 'noindex, follow',
			'noindex_nofollow' => 'noindex, nofollow',
			'index_nofollow'   => 'index, nofollow',
		);
		$value = $this->context->get( 'robots', 'index_follow' );

		return isset( $directives[ $value ] ) ? $directives[ $value ] : $robots;
	}

	/**
	 * Adds a basic, filterable schema piece to Yoast's graph.
	 *
	 * @param array $graph   Existing schema graph.
	 * @param mixed $context Yoast schema context.
	 * @return array
	 */
	public function filter_schema_graph( $graph, $context = null ) {
		if (
			! $this->should_filter_yoast()
			|| '1' !== (string) $this->get_setting( 'schema_enabled', '1' )
			|| ! is_array( $graph )
		) {
			return $graph;
		}

		$allowed_types = array( 'Service', 'ProfessionalService', 'LegalService', 'Organization', 'WebPage' );
		$schema_type   = $this->context->get( 'schema_type' );

		if ( ! in_array( $schema_type, $allowed_types, true ) ) {
			$schema_type = $this->get_setting( 'schema_default_type', 'Service' );
		}
		if ( ! in_array( $schema_type, $allowed_types, true ) ) {
			$schema_type = 'Service';
		}

		if ( 'Organization' === $schema_type && $this->graph_contains_type( $graph, 'Organization' ) ) {
			return $graph;
		}

		$canonical  = $this->context->get( 'canonical_url' );
		$title      = $this->filter_title( '' );
		$description = $this->filter_meta_description( '' );
		$piece      = array(
			'@type'       => $schema_type,
			'@id'         => trailingslashit( $canonical ) . '#ch-pseo-' . strtolower( $schema_type ),
			'url'         => $canonical,
			'name'        => $title,
			'description' => $description,
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

		$piece = apply_filters( 'ch_pseo_schema_piece', $piece, $schema_type, $this->context->get_all(), $context );
		if ( is_array( $piece ) && ! empty( $piece ) ) {
			$graph[] = $piece;
		}

		return apply_filters( 'ch_pseo_schema_graph', $graph, $piece, $this->context->get_all(), $context );
	}

	/**
	 * Determines whether dynamic Yoast output should be applied.
	 *
	 * @return bool
	 */
	private function should_filter_yoast() {
		return $this->context->is_pseo_request()
			&& '1' === (string) $this->get_setting( 'seo_enable_yoast', '1' );
	}

	/**
	 * Processes context tokens and registered CH PSEO shortcodes.
	 *
	 * @param string $template Metadata template.
	 * @return string
	 */
	private function process_template( $template ) {
		if ( $this->processing_template ) {
			return '';
		}

		$this->processing_template = true;
		$value                     = $this->context->replace_tokens( $template );
		$value                     = do_shortcode( $value );
		$value                     = html_entity_decode( wp_strip_all_tags( $value, true ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		$value                     = preg_replace( '/\s+/', ' ', $value );
		$this->processing_template = false;

		return trim( $value );
	}

	/**
	 * Returns the first non-empty string.
	 *
	 * @param array $values Candidate values in priority order.
	 * @return string
	 */
	private function first_non_empty( $values ) {
		foreach ( $values as $value ) {
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return $value;
			}
		}

		return '';
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
	 * @param array  $graph            Schema graph.
	 * @param string $type             Schema type.
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
	 * Maps the current location level to a basic Schema.org place type.
	 *
	 * @return string
	 */
	private function schema_place_type() {
		$types = array(
			'country' => 'Country',
			'state'   => 'AdministrativeArea',
			'city'    => 'City',
		);
		$type = $this->context->get( 'location_type' );

		return isset( $types[ $type ] ) ? $types[ $type ] : 'Place';
	}
}
