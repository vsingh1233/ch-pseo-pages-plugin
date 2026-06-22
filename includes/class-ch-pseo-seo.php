<?php
/**
 * Dynamic SEO metadata and Yoast SEO integration.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds PSEO metadata and supplies Yoast or standalone output.
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
	 * Registers Yoast and standalone metadata hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'wpseo_title', array( $this, 'filter_title' ) );
		add_filter( 'wpseo_metadesc', array( $this, 'filter_meta_description' ) );
		add_filter( 'wpseo_canonical', array( $this, 'filter_canonical' ) );
		add_filter( 'wpseo_robots', array( $this, 'filter_robots' ) );

		add_filter( 'pre_get_document_title', array( $this, 'filter_document_title' ), 20 );
		add_filter( 'wp_robots', array( $this, 'filter_wordpress_robots' ), 20 );
		add_action( 'template_redirect', array( $this, 'disable_core_canonical' ), 1 );
		add_action( 'wp_head', array( $this, 'render_standalone_meta' ), 1 );
	}

	/**
	 * Filters the Yoast title for a valid PSEO request.
	 *
	 * @param string $title Existing title.
	 * @return string
	 */
	public function filter_title( $title ) {
		return $this->should_filter_yoast() ? $this->get_title() : $title;
	}

	/**
	 * Filters the Yoast meta description for a valid PSEO request.
	 *
	 * @param string $description Existing description.
	 * @return string
	 */
	public function filter_meta_description( $description ) {
		return $this->should_filter_yoast() ? $this->get_meta_description() : $description;
	}

	/**
	 * Filters the Yoast canonical URL.
	 *
	 * @param string|false $canonical Existing canonical URL.
	 * @return string|false
	 */
	public function filter_canonical( $canonical ) {
		return $this->should_filter_yoast() ? $this->get_canonical_url() : $canonical;
	}

	/**
	 * Filters the Yoast robots directive.
	 *
	 * @param string $robots Existing robots directive.
	 * @return string
	 */
	public function filter_robots( $robots ) {
		return $this->should_filter_yoast() ? $this->get_robots_directive() : $robots;
	}

	/**
	 * Supplies a standalone WordPress document title when Yoast is unavailable.
	 *
	 * @param string $title Existing document title.
	 * @return string
	 */
	public function filter_document_title( $title ) {
		return $this->should_render_standalone() ? $this->get_title() : $title;
	}

	/**
	 * Supplies standalone WordPress robots directives when Yoast is unavailable.
	 *
	 * @param array<string, bool|string> $robots Existing robots directives.
	 * @return array<string, bool|string>
	 */
	public function filter_wordpress_robots( $robots ) {
		if ( ! $this->should_render_standalone() ) {
			return $robots;
		}

		unset( $robots['index'], $robots['noindex'], $robots['follow'], $robots['nofollow'] );

		foreach ( explode( ',', $this->get_robots_directive() ) as $directive ) {
			$directive            = trim( $directive );
			$robots[ $directive ] = true;
		}

		return $robots;
	}

	/**
	 * Removes WordPress's template-page canonical before standalone output.
	 *
	 * @return void
	 */
	public function disable_core_canonical() {
		if ( $this->should_render_standalone() ) {
			remove_action( 'wp_head', 'rel_canonical' );
		}
	}

	/**
	 * Outputs standalone description and canonical tags when Yoast is unavailable.
	 *
	 * @return void
	 */
	public function render_standalone_meta() {
		if ( ! $this->should_render_standalone() ) {
			return;
		}

		$description = $this->get_meta_description();
		$canonical   = $this->get_canonical_url();

		if ( $description ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		}

		if ( $canonical ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
		}
	}

	/**
	 * Gets the resolved metadata title.
	 *
	 * @return string
	 */
	public function get_title() {
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
	 * Gets the resolved metadata description.
	 *
	 * @return string
	 */
	public function get_meta_description() {
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
	 * Gets the resolved canonical URL.
	 *
	 * @return string
	 */
	public function get_canonical_url() {
		return esc_url_raw( $this->context->get( 'canonical_url' ) );
	}

	/**
	 * Gets the resolved robots directive.
	 *
	 * @return string
	 */
	public function get_robots_directive() {
		$directives = array(
			'index_follow'     => 'index, follow',
			'noindex_follow'   => 'noindex, follow',
			'noindex_nofollow' => 'noindex, nofollow',
			'index_nofollow'   => 'index, nofollow',
		);
		$value      = $this->context->get( 'robots', 'index_follow' );

		return isset( $directives[ $value ] ) ? $directives[ $value ] : 'index, follow';
	}

	/**
	 * Determines whether Yoast should receive dynamic values.
	 *
	 * @return bool
	 */
	public function should_filter_yoast() {
		return $this->context->is_pseo_request()
			&& $this->is_yoast_available()
			&& '1' === (string) $this->get_setting( 'seo_enable_yoast', '1' );
	}

	/**
	 * Determines whether the plugin should output standalone metadata.
	 *
	 * @return bool
	 */
	public function should_render_standalone() {
		return $this->context->is_pseo_request() && ! $this->is_yoast_available();
	}

	/**
	 * Determines whether Yoast SEO is loaded.
	 *
	 * @return bool
	 */
	public function is_yoast_available() {
		return defined( 'WPSEO_VERSION' );
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
}
