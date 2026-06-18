<?php
/**
 * PSEO shortcodes.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers shortcodes that expose dynamic request data.
 */
class CH_PSEO_Shortcodes {

	/**
	 * Location finder cache key.
	 *
	 * @var string
	 */
	const LOCATION_TREE_CACHE_KEY = 'ch_pseo_location_tree_v1';

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
	 * Whether finder data has been attached to the frontend script.
	 *
	 * @var bool
	 */
	private $finder_data_added = false;

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
	 * Registers shortcode hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		$shortcodes = array(
			'ch_pseo_title'           => 'render_title',
			'ch_pseo_service_name'    => 'render_service_name',
			'ch_pseo_location'        => 'render_location',
			'ch_pseo_location_full'   => 'render_location_full',
			'ch_pseo_location_parent' => 'render_location_parent',
			'ch_pseo_country'         => 'render_country',
			'ch_pseo_state'           => 'render_state',
			'ch_pseo_city'            => 'render_city',
			'ch_pseo_location_type'   => 'render_location_type',
			'ch_pseo_breadcrumbs'     => 'render_breadcrumbs',
			'ch_pseo_location_finder' => 'render_location_finder',
		);

		foreach ( $shortcodes as $tag => $method ) {
			add_shortcode( $tag, array( $this, $method ) );
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_shortcode_styles' ) );

		// WordPress core handles normal post content. These filters cover Divi's
		// rendered layout/module output when it bypasses the standard content path.
		add_filter( 'et_builder_render_layout', array( $this, 'process_divi_shortcodes' ), 20 );
		add_filter( 'et_builder_module_content', array( $this, 'process_divi_shortcodes' ), 20 );
	}

	/**
	 * Processes CH PSEO shortcodes in Divi output.
	 *
	 * @param string $content Rendered Divi content.
	 * @return string
	 */
	public function process_divi_shortcodes( $content ) {
		if ( is_string( $content ) && false !== strpos( $content, '[ch_pseo_' ) ) {
			return do_shortcode( $content );
		}

		return $content;
	}

	/**
	 * Enqueues the lightweight shared shortcode stylesheet.
	 *
	 * @return void
	 */
	public function enqueue_shortcode_styles() {
		$this->enqueue_public_style();
	}

	/**
	 * Renders the effective page H1.
	 *
	 * @return string
	 */
	public function render_title() {
		if ( ! $this->context->is_pseo_request() ) {
			return '';
		}

		$mapping = $this->context->get_service_location();
		$service = $this->context->get_service();
		$title   = ! empty( $mapping['custom_h1'] ) ? $mapping['custom_h1'] : $service['h1_template'];

		if ( $title ) {
			$title = $this->context->replace_tokens( $title );
		} else {
			$title = trim( $this->context->get( 'service_name' ) . ' in ' . $this->context->get( 'location_full' ) );
		}

		return esc_html( $title );
	}

	/**
	 * Renders the service name.
	 *
	 * @return string
	 */
	public function render_service_name() {
		return $this->render_context_value( 'service_name' );
	}

	/**
	 * Renders the deepest location name.
	 *
	 * @return string
	 */
	public function render_location() {
		return $this->render_context_value( 'location' );
	}

	/**
	 * Renders the full location label.
	 *
	 * @return string
	 */
	public function render_location_full() {
		return $this->render_context_value( 'location_full' );
	}

	/**
	 * Renders the parent location name.
	 *
	 * @return string
	 */
	public function render_location_parent() {
		return $this->render_context_value( 'location_parent' );
	}

	/**
	 * Renders the country name.
	 *
	 * @return string
	 */
	public function render_country() {
		return $this->render_context_value( 'country_name' );
	}

	/**
	 * Renders the state name.
	 *
	 * @return string
	 */
	public function render_state() {
		return $this->render_context_value( 'state_name' );
	}

	/**
	 * Renders the city name.
	 *
	 * @return string
	 */
	public function render_city() {
		return $this->render_context_value( 'city_name' );
	}

	/**
	 * Renders the current location type.
	 *
	 * @return string
	 */
	public function render_location_type() {
		return $this->render_context_value( 'location_type' );
	}

	/**
	 * Renders hierarchical breadcrumbs for the current PSEO URL.
	 *
	 * @return string
	 */
	public function render_breadcrumbs() {
		if ( ! $this->context->is_pseo_request() ) {
			return '';
		}

		$this->enqueue_public_style();

		$service = $this->context->get_service();
		$country = $this->context->get( 'country', array() );
		$state   = $this->context->get( 'state', array() );
		$city    = $this->context->get( 'city', array() );
		$base    = trim( $service['url_base'], '/' );
		$items   = array(
			array(
				'label' => __( 'Home', 'ch-pseo-pages-plugin' ),
				'url'   => home_url( '/' ),
			),
			array(
				'label' => $service['service_name'],
				'url'   => home_url( user_trailingslashit( $base ) ),
			),
		);
		$path = $base;

		foreach ( array( $country, $state, $city ) as $location ) {
			if ( empty( $location['name'] ) || empty( $location['slug'] ) ) {
				continue;
			}

			$path   .= '/' . $location['slug'];
			$items[] = array(
				'label' => $location['name'],
				'url'   => home_url( user_trailingslashit( $path ) ),
			);
		}

		$output = '<nav class="ch-pseo-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumbs', 'ch-pseo-pages-plugin' ) . '"><ol>';
		$last   = count( $items ) - 1;

		foreach ( $items as $index => $item ) {
			$output .= '<li>';
			if ( $index === $last ) {
				$output .= '<span aria-current="page">' . esc_html( $item['label'] ) . '</span>';
			} else {
				$output .= '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
			}
			$output .= '</li>';
		}

		$output .= '</ol></nav>';
		return $output;
	}

	/**
	 * Renders a mapping-aware dynamic location finder.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_location_finder( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'button_text' => __( 'Find Location', 'ch-pseo-pages-plugin' ),
			),
			$atts,
			'ch_pseo_location_finder'
		);

		$tree = $this->get_location_tree();
		if ( empty( $tree['services'] ) ) {
			return '';
		}

		$this->enqueue_finder_assets( $tree );

		$instance_id = wp_unique_id( 'ch-pseo-location-finder-' );
		ob_start();
		?>
		<form class="ch-pseo-location-finder" id="<?php echo esc_attr( $instance_id ); ?>">
			<div class="ch-pseo-finder-field">
				<label for="<?php echo esc_attr( $instance_id ); ?>-service"><?php esc_html_e( 'Service', 'ch-pseo-pages-plugin' ); ?></label>
				<select id="<?php echo esc_attr( $instance_id ); ?>-service" data-ch-pseo-finder="service" required>
					<option value=""><?php esc_html_e( 'Select a service', 'ch-pseo-pages-plugin' ); ?></option>
					<?php foreach ( $tree['services'] as $service_id => $service ) : ?>
						<option value="<?php echo esc_attr( $service_id ); ?>"><?php echo esc_html( $service['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php foreach ( array( 'country', 'state', 'city' ) as $type ) : ?>
				<div class="ch-pseo-finder-field" data-ch-pseo-finder-field="<?php echo esc_attr( $type ); ?>" hidden>
					<label for="<?php echo esc_attr( $instance_id . '-' . $type ); ?>"><?php echo esc_html( ucfirst( $type ) ); ?></label>
					<select id="<?php echo esc_attr( $instance_id . '-' . $type ); ?>" data-ch-pseo-finder="<?php echo esc_attr( $type ); ?>">
						<option value=""><?php echo esc_html( sprintf( __( 'Select %s', 'ch-pseo-pages-plugin' ), $type ) ); ?></option>
					</select>
				</div>
			<?php endforeach; ?>

			<button class="ch-pseo-finder-submit" type="submit" disabled><?php echo esc_html( $atts['button_text'] ); ?></button>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Clears the cached location finder tree.
	 *
	 * @return void
	 */
	public static function clear_location_tree_cache() {
		delete_transient( self::LOCATION_TREE_CACHE_KEY );
	}

	/**
	 * Renders an escaped context value or a safe blank.
	 *
	 * @param string $key Context key.
	 * @return string
	 */
	private function render_context_value( $key ) {
		if ( ! $this->context->is_pseo_request() ) {
			return '';
		}

		return esc_html( $this->context->get( $key, '' ) );
	}

	/**
	 * Gets or builds the active service/location mapping tree.
	 *
	 * @return array<string, mixed>
	 */
	private function get_location_tree() {
		$tree = get_transient( self::LOCATION_TREE_CACHE_KEY );

		if ( is_array( $tree ) ) {
			return $tree;
		}

		global $wpdb;

		$tables = $this->database->get_table_names();
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					sl.id AS mapping_id,
					s.id AS service_id,
					s.service_name,
					s.url_base,
					s.location_structure,
					co.id AS country_id,
					co.name AS country_name,
					co.slug AS country_slug,
					st.id AS state_id,
					st.name AS state_name,
					st.slug AS state_slug,
					ci.id AS city_id,
					ci.name AS city_name,
					ci.slug AS city_slug
				FROM {$tables['service_locations']} sl
				INNER JOIN {$tables['services']} s ON s.id = sl.service_id AND s.status = %s
				LEFT JOIN {$tables['countries']} co ON co.id = sl.country_id AND co.status = %s
				LEFT JOIN {$tables['states']} st ON st.id = sl.state_id AND st.status = %s
				LEFT JOIN {$tables['cities']} ci ON ci.id = sl.city_id AND ci.status = %s
				WHERE sl.status = %s
				ORDER BY s.service_name, co.name, st.name, ci.name",
				'active',
				'active',
				'active',
				'active',
				'active'
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$tree = array( 'services' => array() );

		foreach ( $rows as $row ) {
			$service_id = (string) $row['service_id'];
			$segments   = array();

			if ( 0 === strpos( $row['location_structure'], 'country' ) ) {
				if ( empty( $row['country_slug'] ) ) {
					continue;
				}
				$segments[] = $row['country_slug'];
				if ( ! empty( $row['state_slug'] ) ) {
					$segments[] = $row['state_slug'];
				}
				if ( ! empty( $row['city_slug'] ) ) {
					$segments[] = $row['city_slug'];
				}
			} else {
				if ( empty( $row['state_slug'] ) ) {
					continue;
				}
				$segments[] = $row['state_slug'];
				if ( ! empty( $row['city_slug'] ) ) {
					$segments[] = $row['city_slug'];
				}
			}

			if ( ! isset( $tree['services'][ $service_id ] ) ) {
				$tree['services'][ $service_id ] = array(
					'name'      => $row['service_name'],
					'structure' => $row['location_structure'],
					'mappings'  => array(),
				);
			}

			$tree['services'][ $service_id ]['mappings'][] = array(
				'id'      => (int) $row['mapping_id'],
				'url'     => home_url( user_trailingslashit( trim( $row['url_base'], '/' ) . '/' . implode( '/', $segments ) ) ),
				'country' => $this->location_tree_item( $row['country_id'], $row['country_name'] ),
				'state'   => $this->location_tree_item( $row['state_id'], $row['state_name'] ),
				'city'    => $this->location_tree_item( $row['city_id'], $row['city_name'] ),
			);
		}

		set_transient( self::LOCATION_TREE_CACHE_KEY, $tree, DAY_IN_SECONDS );
		return $tree;
	}

	/**
	 * Normalizes a finder location option.
	 *
	 * @param mixed  $id   Location ID.
	 * @param string $name Location name.
	 * @return array|null
	 */
	private function location_tree_item( $id, $name ) {
		if ( ! $id || ! $name ) {
			return null;
		}

		return array(
			'id'   => (int) $id,
			'name' => $name,
		);
	}

	/**
	 * Enqueues finder CSS/JS and attaches cached data once.
	 *
	 * @param array $tree Location tree.
	 * @return void
	 */
	private function enqueue_finder_assets( $tree ) {
		$this->enqueue_public_style();
		wp_enqueue_script(
			'ch-pseo-location-finder',
			CH_PSEO_PLUGIN_URL . 'public/assets/location-finder.js',
			array(),
			CH_PSEO_VERSION,
			true
		);

		if ( ! $this->finder_data_added ) {
			wp_add_inline_script(
				'ch-pseo-location-finder',
				'window.CHPSEOFinderData = ' . wp_json_encode( $tree ) . ';',
				'before'
			);
			$this->finder_data_added = true;
		}
	}

	/**
	 * Enqueues shared shortcode presentation styles.
	 *
	 * @return void
	 */
	private function enqueue_public_style() {
		wp_enqueue_style(
			'ch-pseo-shortcodes',
			CH_PSEO_PLUGIN_URL . 'public/assets/location-finder.css',
			array(),
			CH_PSEO_VERSION
		);
	}
}
