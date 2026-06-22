<?php
/**
 * WordPress admin integration.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers plugin admin menus, screens, and secure form handlers.
 */
class CH_PSEO_Admin {

	/**
	 * Capability required to manage the plugin.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Database service.
	 *
	 * @var CH_PSEO_Database
	 */
	private $database;

	/**
	 * Constructor.
	 *
	 * @param CH_PSEO_Database $database Database service.
	 */
	public function __construct( CH_PSEO_Database $database ) {
		$this->database = $database;
	}

	/**
	 * Registers admin hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notice' ) );

		add_action( 'admin_post_ch_pseo_save_service', array( $this, 'handle_save_service' ) );
		add_action( 'admin_post_ch_pseo_delete_service', array( $this, 'handle_delete_service' ) );
		add_action( 'admin_post_ch_pseo_save_location', array( $this, 'handle_save_location' ) );
		add_action( 'admin_post_ch_pseo_delete_location', array( $this, 'handle_delete_location' ) );
		add_action( 'admin_post_ch_pseo_save_mapping', array( $this, 'handle_save_mapping' ) );
		add_action( 'admin_post_ch_pseo_delete_mapping', array( $this, 'handle_delete_mapping' ) );
		add_action( 'admin_post_ch_pseo_save_exclusion', array( $this, 'handle_save_exclusion' ) );
		add_action( 'admin_post_ch_pseo_delete_exclusion', array( $this, 'handle_delete_exclusion' ) );
		add_action( 'admin_post_ch_pseo_save_plugin_settings', array( $this, 'handle_save_plugin_settings' ) );
		add_action( 'admin_post_ch_pseo_repair_tables', array( $this, 'handle_repair_tables' ) );
		add_action( 'admin_post_ch_pseo_clear_location_cache', array( $this, 'handle_clear_location_cache' ) );
		add_action( 'admin_post_ch_pseo_clear_sitemap_cache', array( $this, 'handle_clear_sitemap_cache' ) );
		add_action( 'admin_post_ch_pseo_export_urls_csv', array( $this, 'handle_export_urls_csv' ) );
		add_action( 'admin_post_ch_pseo_export_data_csv', array( $this, 'handle_export_data_csv' ) );
		add_action( 'admin_post_ch_pseo_import_csv', array( $this, 'handle_import_csv' ) );
		add_action( 'admin_post_ch_pseo_download_csv_template', array( $this, 'handle_download_csv_template' ) );
		add_action( 'admin_post_ch_pseo_bulk_locations', array( $this, 'handle_bulk_locations' ) );
		add_action( 'admin_post_ch_pseo_bulk_mappings', array( $this, 'handle_bulk_mappings' ) );
	}

	/**
	 * Registers the top-level plugin menu and all requested submenu pages.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'CH PSEO Pages', 'ch-pseo-pages-plugin' ),
			__( 'CH PSEO Pages', 'ch-pseo-pages-plugin' ),
			self::CAPABILITY,
			'ch-pseo',
			array( $this, 'render_dashboard_page' ),
			'dashicons-location-alt',
			58
		);

		$submenus = array(
			'ch-pseo'            => array( __( 'Dashboard', 'ch-pseo-pages-plugin' ), 'render_dashboard_page' ),
			'ch-pseo-services'   => array( __( 'Services', 'ch-pseo-pages-plugin' ), 'render_services_page' ),
			'ch-pseo-locations'  => array( __( 'Locations', 'ch-pseo-pages-plugin' ), 'render_locations_page' ),
			'ch-pseo-mappings'   => array( __( 'Service Location Mapping', 'ch-pseo-pages-plugin' ), 'render_mappings_page' ),
			'ch-pseo-seo'        => array( __( 'SEO Settings', 'ch-pseo-pages-plugin' ), 'render_seo_settings_page' ),
			'ch-pseo-schema'     => array( __( 'Schema Settings', 'ch-pseo-pages-plugin' ), 'render_schema_settings_page' ),
			'ch-pseo-sitemap'    => array( __( 'Sitemap Settings', 'ch-pseo-pages-plugin' ), 'render_sitemap_settings_page' ),
			'ch-pseo-exclusions' => array( __( 'URL Exclusions', 'ch-pseo-pages-plugin' ), 'render_exclusions_page' ),
			'ch-pseo-tools'      => array( __( 'Tools', 'ch-pseo-pages-plugin' ), 'render_tools_page' ),
		);

		foreach ( $submenus as $slug => $submenu ) {
			add_submenu_page(
				'ch-pseo',
				$submenu[0],
				$submenu[0],
				self::CAPABILITY,
				$slug,
				array( $this, $submenu[1] )
			);
		}
	}

	/**
	 * Registers the uninstall preference stored in WordPress options.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'ch_pseo_tools',
			'ch_pseo_remove_data_on_uninstall',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);
	}

	/**
	 * Loads admin-only CSS and JavaScript on plugin screens.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( false === strpos( $hook_suffix, 'ch-pseo' ) ) {
			return;
		}

		wp_enqueue_style(
			'ch-pseo-admin',
			CH_PSEO_PLUGIN_URL . 'admin/assets/admin.css',
			array(),
			CH_PSEO_VERSION
		);

		wp_enqueue_script(
			'ch-pseo-admin',
			CH_PSEO_PLUGIN_URL . 'admin/assets/admin.js',
			array(),
			CH_PSEO_VERSION,
			true
		);
	}

	/**
	 * Displays result notices after redirected form submissions.
	 *
	 * @return void
	 */
	public function render_admin_notice() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 0 !== strpos( $page, 'ch-pseo' ) || empty( $_GET['ch_pseo_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$notice  = sanitize_key( wp_unslash( $_GET['ch_pseo_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notices = array(
			'saved'                  => array( 'success', __( 'Changes saved.', 'ch-pseo-pages-plugin' ) ),
			'deleted'                => array( 'success', __( 'Item deleted.', 'ch-pseo-pages-plugin' ) ),
			'tables_repaired'        => array( 'success', __( 'Database tables checked and updated.', 'ch-pseo-pages-plugin' ) ),
			'cache_cleared'          => array( 'success', __( 'Location finder cache cleared.', 'ch-pseo-pages-plugin' ) ),
			'sitemap_cache_cleared'  => array( 'success', __( 'Sitemap cache cleared.', 'ch-pseo-pages-plugin' ) ),
			'missing_fields'         => array( 'error', __( 'Please complete all required fields.', 'ch-pseo-pages-plugin' ) ),
			'invalid_item'           => array( 'error', __( 'The requested item could not be found.', 'ch-pseo-pages-plugin' ) ),
			'duplicate_service_slug' => array( 'error', __( 'Another service already uses that service slug.', 'ch-pseo-pages-plugin' ) ),
			'duplicate_country_slug' => array( 'error', __( 'A country with that slug already exists.', 'ch-pseo-pages-plugin' ) ),
			'duplicate_state_slug'   => array( 'error', __( 'A state with that slug already exists under the selected country.', 'ch-pseo-pages-plugin' ) ),
			'duplicate_city_slug'    => array( 'error', __( 'A city with that slug already exists under the selected state.', 'ch-pseo-pages-plugin' ) ),
			'duplicate_mapping'      => array( 'error', __( 'That service-location mapping already exists.', 'ch-pseo-pages-plugin' ) ),
			'duplicate_exclusion'    => array( 'error', __( 'That exclusion already exists for the selected service scope.', 'ch-pseo-pages-plugin' ) ),
			'invalid_relationship'   => array( 'error', __( 'The selected locations do not form a valid country, state, and city relationship.', 'ch-pseo-pages-plugin' ) ),
			'invalid_template_page'  => array( 'error', __( 'The template must be a published WordPress page.', 'ch-pseo-pages-plugin' ) ),
			'database_error'         => array( 'error', __( 'The database operation failed. Please try again.', 'ch-pseo-pages-plugin' ) ),
			'bulk_updated'           => array( 'success', __( 'Bulk action completed.', 'ch-pseo-pages-plugin' ) ),
			'import_complete'        => array( 'success', __( 'CSV import completed.', 'ch-pseo-pages-plugin' ) ),
			'import_failed'          => array( 'error', __( 'CSV import failed. Review the import results.', 'ch-pseo-pages-plugin' ) ),
		);

		if ( ! isset( $notices[ $notice ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $notices[ $notice ][0] ),
			esc_html( $notices[ $notice ][1] )
		);
	}

	/**
	 * Renders the dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard_page() {
		global $wpdb;

		$this->authorize_page();
		$tables = $this->database->get_table_names();

		$counts = array(
			'services'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['services']}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'countries' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['countries']}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'states'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['states']}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'cities'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['cities']}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'mappings'  => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tables['service_locations']} WHERE status = %s", 'active' ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'indexable' => 0,
			'noindex'   => 0,
		);

		$robots_counts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COALESCE(NULLIF(sl.robots, ''), s.robots_default) AS robots, COUNT(*) AS total
				FROM {$tables['service_locations']} sl
				INNER JOIN {$tables['services']} s ON s.id = sl.service_id
				WHERE sl.status = %s AND s.status = %s
				GROUP BY COALESCE(NULLIF(sl.robots, ''), s.robots_default)",
				'active',
				'active'
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( $robots_counts as $robots_count ) {
			if ( 0 === strpos( $robots_count['robots'], 'noindex' ) ) {
				$counts['noindex'] += (int) $robots_count['total'];
			} else {
				$counts['indexable'] += (int) $robots_count['total'];
			}
		}

		$sitemap_slug = $this->get_setting( 'sitemap_slug', 'ch-pseo-pages-sitemap.xml' );
		$sitemap_url  = home_url( '/' . ltrim( $sitemap_slug, '/' ) );

		require CH_PSEO_PLUGIN_DIR . 'admin/views/html-admin-dashboard.php';
	}

	/**
	 * Renders the services CRUD page.
	 *
	 * @return void
	 */
	public function render_services_page() {
		global $wpdb;

		$this->authorize_page();
		$table      = $this->database->get_services_table();
		$service_id = isset( $_GET['service_id'] ) ? absint( $_GET['service_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$service    = $service_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $service_id ), ARRAY_A ) : null; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$services   = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY service_name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$pages      = get_pages(
			array(
				'post_status' => 'publish',
				'sort_column' => 'post_title',
			)
		);

		require CH_PSEO_PLUGIN_DIR . 'admin/views/html-admin-services.php';
	}

	/**
	 * Renders the countries, states, and cities CRUD page.
	 *
	 * @return void
	 */
	public function render_locations_page() {
		global $wpdb;

		$this->authorize_page();
		$tables  = $this->database->get_table_names();
		$tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'countries'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab     = in_array( $tab, array( 'countries', 'states', 'cities' ), true ) ? $tab : 'countries';
		$item_id = isset( $_GET['location_id'] ) ? absint( $_GET['location_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$item    = $item_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables[ $tab ]} WHERE id = %d", $item_id ), ARRAY_A ) : null; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$countries = $wpdb->get_results( "SELECT * FROM {$tables['countries']} ORDER BY name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$states    = $wpdb->get_results(
			"SELECT st.*, co.name AS country_name
			FROM {$tables['states']} st
			LEFT JOIN {$tables['countries']} co ON co.id = st.country_id
			ORDER BY st.name ASC",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cities    = $wpdb->get_results(
			"SELECT ci.*, st.name AS state_name, co.name AS country_name
			FROM {$tables['cities']} ci
			LEFT JOIN {$tables['states']} st ON st.id = ci.state_id
			LEFT JOIN {$tables['countries']} co ON co.id = ci.country_id
			ORDER BY ci.name ASC",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		require CH_PSEO_PLUGIN_DIR . 'admin/views/html-admin-locations.php';
	}

	/**
	 * Renders the service/location mapping CRUD page.
	 *
	 * @return void
	 */
	public function render_mappings_page() {
		global $wpdb;

		$this->authorize_page();
		$tables     = $this->database->get_table_names();
		$mapping_id = isset( $_GET['mapping_id'] ) ? absint( $_GET['mapping_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$mapping    = $mapping_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['service_locations']} WHERE id = %d", $mapping_id ), ARRAY_A ) : null; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$services   = $wpdb->get_results( "SELECT * FROM {$tables['services']} ORDER BY service_name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$countries  = $wpdb->get_results( "SELECT * FROM {$tables['countries']} ORDER BY name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$states     = $wpdb->get_results( "SELECT * FROM {$tables['states']} ORDER BY name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cities     = $wpdb->get_results( "SELECT * FROM {$tables['cities']} ORDER BY name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$mappings   = $wpdb->get_results(
			"SELECT sl.*, s.service_name, co.name AS country_name, st.name AS state_name, ci.name AS city_name
			FROM {$tables['service_locations']} sl
			INNER JOIN {$tables['services']} s ON s.id = sl.service_id
			LEFT JOIN {$tables['countries']} co ON co.id = sl.country_id
			LEFT JOIN {$tables['states']} st ON st.id = sl.state_id
			LEFT JOIN {$tables['cities']} ci ON ci.id = sl.city_id
			ORDER BY s.service_name ASC, co.name ASC, st.name ASC, ci.name ASC",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		require CH_PSEO_PLUGIN_DIR . 'admin/views/html-admin-mappings.php';
	}

	/**
	 * Renders the global and service-specific URL exclusions CRUD page.
	 *
	 * @return void
	 */
	public function render_exclusions_page() {
		global $wpdb;

		$this->authorize_page();
		$tables       = $this->database->get_table_names();
		$exclusion_id = isset( $_GET['exclusion_id'] ) ? absint( $_GET['exclusion_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$exclusion    = $exclusion_id
			? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['url_exclusions']} WHERE id = %d", $exclusion_id ), ARRAY_A ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			: null;
		$services     = $wpdb->get_results( "SELECT id, service_name FROM {$tables['services']} ORDER BY service_name ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exclusions   = $wpdb->get_results(
			"SELECT ex.*, s.service_name
			FROM {$tables['url_exclusions']} ex
			LEFT JOIN {$tables['services']} s ON s.id = ex.service_id
			ORDER BY ex.excluded_slug ASC, s.service_name ASC",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		require CH_PSEO_PLUGIN_DIR . 'admin/views/html-admin-exclusions.php';
	}

	/**
	 * Renders global SEO settings.
	 *
	 * @return void
	 */
	public function render_seo_settings_page() {
		$this->render_plugin_settings_page(
			'seo',
			array(
				'seo_default_title_suffix'        => $this->get_setting( 'seo_default_title_suffix', '' ),
				'seo_global_title_template'       => $this->get_setting( 'seo_global_title_template', '' ),
				'seo_global_description_template' => $this->get_setting( 'seo_global_description_template', '' ),
				'seo_enable_yoast'                => $this->get_setting( 'seo_enable_yoast', '1' ),
			)
		);
	}

	/**
	 * Renders global schema settings.
	 *
	 * @return void
	 */
	public function render_schema_settings_page() {
		$this->render_plugin_settings_page(
			'schema',
			array(
				'schema_enabled'           => $this->get_setting( 'schema_enabled', '1' ),
				'schema_default_type'      => $this->get_setting( 'schema_default_type', 'Service' ),
				'schema_organization_name' => $this->get_setting( 'schema_organization_name', get_bloginfo( 'name' ) ),
			)
		);
	}

	/**
	 * Renders global sitemap settings.
	 *
	 * @return void
	 */
	public function render_sitemap_settings_page() {
		$this->render_plugin_settings_page(
			'sitemap',
			array(
				'sitemap_enabled'  => $this->get_setting( 'sitemap_enabled', '1' ),
				'sitemap_slug'     => $this->get_setting( 'sitemap_slug', 'ch-pseo-pages-sitemap.xml' ),
				'sitemap_max_urls' => $this->get_setting( 'sitemap_max_urls', '50000' ),
			)
		);
	}

	/**
	 * Renders maintenance tools.
	 *
	 * @return void
	 */
	public function render_tools_page() {
		$this->authorize_page();
		$tables        = $this->database->get_table_names();
		$import_result = get_transient( 'ch_pseo_import_result_' . get_current_user_id() );
		delete_transient( 'ch_pseo_import_result_' . get_current_user_id() );

		require CH_PSEO_PLUGIN_DIR . 'admin/views/html-admin-tools.php';
	}

	/**
	 * Saves a service.
	 *
	 * @return void
	 */
	public function handle_save_service() {
		global $wpdb;

		$this->authorize_action( 'ch_pseo_save_service' );

		$service_id   = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$service_name = isset( $_POST['service_name'] ) ? sanitize_text_field( wp_unslash( $_POST['service_name'] ) ) : '';
		$service_slug = isset( $_POST['service_slug'] ) ? sanitize_title( wp_unslash( $_POST['service_slug'] ) ) : '';
		$url_base     = isset( $_POST['url_base'] ) ? $this->sanitize_url_base( wp_unslash( $_POST['url_base'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by sanitize_url_base().
		$table        = $this->database->get_services_table();
		$old_service  = $service_id
			? $wpdb->get_row( $wpdb->prepare( "SELECT service_slug, url_base, location_structure, status FROM {$table} WHERE id = %d", $service_id ), ARRAY_A ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			: null;

		if ( '' === $service_name || '' === $service_slug ) {
			$this->redirect( 'ch-pseo-services', 'missing_fields', $service_id ? array( 'service_id' => $service_id ) : array() );
		}

		$structures = array( 'country_state_city', 'state_city', 'country_state', 'country', 'state' );
		$statuses   = array( 'active', 'inactive' );
		$robots     = $this->get_robots_options();

		$location_structure = isset( $_POST['location_structure'] ) ? sanitize_key( wp_unslash( $_POST['location_structure'] ) ) : 'country_state_city';
		$status             = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active';
		$robots_default     = isset( $_POST['robots_default'] ) ? sanitize_key( wp_unslash( $_POST['robots_default'] ) ) : 'index_follow';
		$template_page_id   = isset( $_POST['template_page_id'] ) ? absint( $_POST['template_page_id'] ) : 0;
		$status             = in_array( $status, $statuses, true ) ? $status : 'active';

		$service_args = $service_id ? array( 'service_id' => $service_id ) : array();
		$duplicate_slug = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE service_slug = %s AND id != %d LIMIT 1",
				$service_slug,
				$service_id
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $duplicate_slug ) {
			$this->redirect( 'ch-pseo-services', 'duplicate_service_slug', $service_args );
		}

		if (
			( 'active' === $status && ! $template_page_id )
			|| ( $template_page_id && ( 'page' !== get_post_type( $template_page_id ) || 'publish' !== get_post_status( $template_page_id ) ) )
		) {
			$this->redirect( 'ch-pseo-services', 'invalid_template_page', $service_args );
		}

		$data = array(
			'service_name'              => $service_name,
			'service_slug'              => $service_slug,
			'url_base'                  => $url_base,
			'template_page_id'          => $template_page_id ? $template_page_id : null,
			'location_structure'        => in_array( $location_structure, $structures, true ) ? $location_structure : 'country_state_city',
			'status'                    => $status,
			'robots_default'            => isset( $robots[ $robots_default ] ) ? $robots_default : 'index_follow',
			'sitemap_include_default'   => empty( $_POST['sitemap_include_default'] ) ? 0 : 1,
			'meta_title_template'       => isset( $_POST['meta_title_template'] ) ? sanitize_textarea_field( wp_unslash( $_POST['meta_title_template'] ) ) : '',
			'meta_description_template' => isset( $_POST['meta_description_template'] ) ? sanitize_textarea_field( wp_unslash( $_POST['meta_description_template'] ) ) : '',
			'h1_template'               => isset( $_POST['h1_template'] ) ? sanitize_textarea_field( wp_unslash( $_POST['h1_template'] ) ) : '',
			'schema_type'               => isset( $_POST['schema_type'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_type'] ) ) : '',
		);

		$result = $service_id
			? $wpdb->update( $table, $data, array( 'id' => $service_id ) )
			: $wpdb->insert( $table, $data );

		if (
			false !== $result
			&& (
				! $old_service
				|| $old_service['service_slug'] !== $data['service_slug']
				|| $old_service['url_base'] !== $data['url_base']
				|| $old_service['location_structure'] !== $data['location_structure']
				|| $old_service['status'] !== $data['status']
			)
		) {
			update_option( 'ch_pseo_flush_rewrite_rules', 1, false );
		}
		if ( false !== $result ) {
			CH_PSEO_Router::clear_rewrite_definitions_cache();
			CH_PSEO_Shortcodes::clear_location_tree_cache();
			CH_PSEO_Sitemap::clear_cache();
		}

		$this->redirect( 'ch-pseo-services', false === $result ? 'database_error' : 'saved' );
	}

	/**
	 * Deletes a service and its dependent mappings and exclusions.
	 *
	 * @return void
	 */
	public function handle_delete_service() {
		global $wpdb;

		$service_id = isset( $_GET['service_id'] ) ? absint( $_GET['service_id'] ) : 0;
		$this->authorize_action( 'ch_pseo_delete_service_' . $service_id );

		if ( ! $service_id ) {
			$this->redirect( 'ch-pseo-services', 'invalid_item' );
		}

		$tables = $this->database->get_table_names();
		$wpdb->delete( $tables['service_locations'], array( 'service_id' => $service_id ), array( '%d' ) );
		$wpdb->delete( $tables['url_exclusions'], array( 'service_id' => $service_id ), array( '%d' ) );
		$result = $wpdb->delete( $tables['services'], array( 'id' => $service_id ), array( '%d' ) );

		if ( false !== $result ) {
			update_option( 'ch_pseo_flush_rewrite_rules', 1, false );
			CH_PSEO_Router::clear_rewrite_definitions_cache();
			CH_PSEO_Shortcodes::clear_location_tree_cache();
			CH_PSEO_Sitemap::clear_cache();
		}

		$this->redirect( 'ch-pseo-services', false === $result ? 'database_error' : 'deleted' );
	}

	/**
	 * Saves a country, state, or city.
	 *
	 * @return void
	 */
	public function handle_save_location() {
		global $wpdb;

		$this->authorize_action( 'ch_pseo_save_location' );

		$type = isset( $_POST['location_type'] ) ? sanitize_key( wp_unslash( $_POST['location_type'] ) ) : '';
		if ( ! in_array( $type, array( 'countries', 'states', 'cities' ), true ) ) {
			$this->redirect( 'ch-pseo-locations', 'invalid_item' );
		}

		$item_id = isset( $_POST['location_id'] ) ? absint( $_POST['location_id'] ) : 0;
		$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug    = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$slug    = $slug ? $slug : sanitize_title( $name );
		$status  = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active';
		$status  = in_array( $status, array( 'active', 'inactive' ), true ) ? $status : 'active';

		if ( '' === $name || '' === $slug ) {
			$this->redirect( 'ch-pseo-locations', 'missing_fields', array( 'tab' => $type ) );
		}

		$data = array(
			'name'   => $name,
			'slug'   => $slug,
			'status' => $status,
		);
		$country_id = 0;
		$state_id   = 0;

		if ( 'states' === $type || 'cities' === $type ) {
			$country_id         = isset( $_POST['country_id'] ) ? absint( $_POST['country_id'] ) : 0;
			$data['country_id'] = $country_id;
		}

		if ( 'cities' === $type ) {
			$state_id         = isset( $_POST['state_id'] ) ? absint( $_POST['state_id'] ) : 0;
			$data['state_id'] = $state_id;
		}

		$tables = $this->database->get_table_names();
		$table  = $tables[ $type ];

		if ( $country_id ) {
			$country_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tables['countries']} WHERE id = %d LIMIT 1", $country_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $country_exists ) {
				$this->redirect( 'ch-pseo-locations', 'invalid_relationship', array( 'tab' => $type ) );
			}
		}

		if ( 'cities' === $type ) {
			$state = $state_id
				? $wpdb->get_row( $wpdb->prepare( "SELECT id, country_id FROM {$tables['states']} WHERE id = %d LIMIT 1", $state_id ), ARRAY_A ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				: null;
			if ( ! $state || ( $country_id && (int) $state['country_id'] !== $country_id ) ) {
				$this->redirect( 'ch-pseo-locations', 'invalid_relationship', array( 'tab' => $type ) );
			}
			$country_id         = (int) $state['country_id'];
			$data['country_id'] = $country_id;
		}

		if ( 'countries' === $type ) {
			$duplicate_notice = 'duplicate_country_slug';
			$duplicate_sql    = $wpdb->prepare(
				"SELECT id FROM {$table} WHERE slug = %s AND id != %d LIMIT 1",
				$slug,
				$item_id
			);
		} elseif ( 'states' === $type ) {
			$duplicate_notice = 'duplicate_state_slug';
			$duplicate_sql    = $wpdb->prepare(
				"SELECT id FROM {$table}
				WHERE slug = %s
					AND COALESCE(country_id, 0) = %d
					AND id != %d
				LIMIT 1",
				$slug,
				$country_id,
				$item_id
			);
		} else {
			$duplicate_notice = 'duplicate_city_slug';
			$duplicate_sql    = $wpdb->prepare(
				"SELECT id FROM {$table}
				WHERE slug = %s
					AND COALESCE(state_id, 0) = %d
					AND id != %d
				LIMIT 1",
				$slug,
				$state_id,
				$item_id
			);
		}

		$duplicate_location = $wpdb->get_var( $duplicate_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $duplicate_location ) {
			$args = array( 'tab' => $type );
			if ( $item_id ) {
				$args['location_id'] = $item_id;
			}
			$this->redirect( 'ch-pseo-locations', $duplicate_notice, $args );
		}

		$result = $item_id
			? $wpdb->update( $table, $data, array( 'id' => $item_id ) )
			: $wpdb->insert( $table, $data );

		if ( false !== $result ) {
			CH_PSEO_Shortcodes::clear_location_tree_cache();
			CH_PSEO_Sitemap::clear_cache();
		}

		$this->redirect( 'ch-pseo-locations', false === $result ? 'database_error' : 'saved', array( 'tab' => $type ) );
	}

	/**
	 * Deletes a location and dependent mapping rows.
	 *
	 * @return void
	 */
	public function handle_delete_location() {
		global $wpdb;

		$type    = isset( $_GET['location_type'] ) ? sanitize_key( wp_unslash( $_GET['location_type'] ) ) : '';
		$item_id = isset( $_GET['location_id'] ) ? absint( $_GET['location_id'] ) : 0;
		$this->authorize_action( 'ch_pseo_delete_location_' . $type . '_' . $item_id );

		if ( ! $item_id || ! in_array( $type, array( 'countries', 'states', 'cities' ), true ) ) {
			$this->redirect( 'ch-pseo-locations', 'invalid_item' );
		}

		$tables = $this->database->get_table_names();

		if ( 'countries' === $type ) {
			$state_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$tables['states']} WHERE country_id = %d", $item_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			foreach ( $state_ids as $state_id ) {
				$city_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$tables['cities']} WHERE state_id = %d", $state_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				foreach ( $city_ids as $city_id ) {
					$wpdb->delete( $tables['service_locations'], array( 'city_id' => (int) $city_id ), array( '%d' ) );
				}
				$wpdb->delete( $tables['service_locations'], array( 'state_id' => (int) $state_id ), array( '%d' ) );
				$wpdb->delete( $tables['cities'], array( 'state_id' => (int) $state_id ), array( '%d' ) );
			}
			$wpdb->delete( $tables['service_locations'], array( 'country_id' => $item_id ), array( '%d' ) );
			$wpdb->delete( $tables['cities'], array( 'country_id' => $item_id ), array( '%d' ) );
			$wpdb->delete( $tables['states'], array( 'country_id' => $item_id ), array( '%d' ) );
		} elseif ( 'states' === $type ) {
			$city_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$tables['cities']} WHERE state_id = %d", $item_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			foreach ( $city_ids as $city_id ) {
				$wpdb->delete( $tables['service_locations'], array( 'city_id' => (int) $city_id ), array( '%d' ) );
			}
			$wpdb->delete( $tables['service_locations'], array( 'state_id' => $item_id ), array( '%d' ) );
			$wpdb->delete( $tables['cities'], array( 'state_id' => $item_id ), array( '%d' ) );
		} else {
			$wpdb->delete( $tables['service_locations'], array( 'city_id' => $item_id ), array( '%d' ) );
		}

		$result = $wpdb->delete( $tables[ $type ], array( 'id' => $item_id ), array( '%d' ) );
		if ( false !== $result ) {
			CH_PSEO_Shortcodes::clear_location_tree_cache();
			CH_PSEO_Sitemap::clear_cache();
		}
		$this->redirect( 'ch-pseo-locations', false === $result ? 'database_error' : 'deleted', array( 'tab' => $type ) );
	}

	/**
	 * Saves a service/location mapping.
	 *
	 * @return void
	 */
	public function handle_save_mapping() {
		global $wpdb;

		$this->authorize_action( 'ch_pseo_save_mapping' );

		$tables     = $this->database->get_table_names();
		$mapping_id = isset( $_POST['mapping_id'] ) ? absint( $_POST['mapping_id'] ) : 0;
		$service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$country_id = isset( $_POST['country_id'] ) ? absint( $_POST['country_id'] ) : 0;
		$state_id   = isset( $_POST['state_id'] ) ? absint( $_POST['state_id'] ) : 0;
		$city_id    = isset( $_POST['city_id'] ) ? absint( $_POST['city_id'] ) : 0;
		$service    = $service_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['services']} WHERE id = %d", $service_id ), ARRAY_A ) : null; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$city       = null;

		if ( ! $service ) {
			$this->redirect( 'ch-pseo-mappings', 'missing_fields' );
		}

		if ( $country_id ) {
			$country_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tables['countries']} WHERE id = %d LIMIT 1", $country_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $country_exists ) {
				$this->redirect( 'ch-pseo-mappings', 'invalid_relationship', $mapping_id ? array( 'mapping_id' => $mapping_id ) : array() );
			}
		}

		if ( $city_id ) {
			$city = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['cities']} WHERE id = %d", $city_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $city || ! $city['state_id'] || ( $state_id && (int) $city['state_id'] !== $state_id ) ) {
				$this->redirect( 'ch-pseo-mappings', 'invalid_relationship', $mapping_id ? array( 'mapping_id' => $mapping_id ) : array() );
			}
			$state_id = (int) $city['state_id'];
		}

		if ( $state_id ) {
			$state = $wpdb->get_row( $wpdb->prepare( "SELECT id, country_id FROM {$tables['states']} WHERE id = %d", $state_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if (
				! $state
				|| ( $country_id && (int) $state['country_id'] !== $country_id )
				|| ( $city && ! empty( $city['country_id'] ) && (int) $city['country_id'] !== (int) $state['country_id'] )
			) {
				$this->redirect( 'ch-pseo-mappings', 'invalid_relationship', $mapping_id ? array( 'mapping_id' => $mapping_id ) : array() );
			}
			$country_id = (int) $state['country_id'];
		}

		if ( ! $this->mapping_has_required_locations( $service['location_structure'], $country_id, $state_id, $city_id ) ) {
			$this->redirect( 'ch-pseo-mappings', 'missing_fields', $mapping_id ? array( 'mapping_id' => $mapping_id ) : array() );
		}

		if ( 'country_state' === $service['location_structure'] || 'state' === $service['location_structure'] ) {
			$city_id = 0;
		} elseif ( 'country' === $service['location_structure'] ) {
			$state_id = 0;
			$city_id  = 0;
		}

		$duplicate = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$tables['service_locations']}
				WHERE service_id = %d
				AND COALESCE(country_id, 0) = %d
				AND COALESCE(state_id, 0) = %d
				AND COALESCE(city_id, 0) = %d
				AND id != %d
				LIMIT 1",
				$service_id,
				$country_id,
				$state_id,
				$city_id,
				$mapping_id
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $duplicate ) {
			$this->redirect(
				'ch-pseo-mappings',
				'duplicate_mapping',
				$mapping_id ? array( 'mapping_id' => $mapping_id ) : array()
			);
		}

		$statuses = array( 'active', 'inactive' );
		$status   = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active';
		$robots   = isset( $_POST['robots'] ) ? sanitize_key( wp_unslash( $_POST['robots'] ) ) : '';
		$robots   = isset( $this->get_robots_options()[ $robots ] ) ? $robots : null;
		$sitemap  = isset( $_POST['sitemap_include'] ) ? sanitize_key( wp_unslash( $_POST['sitemap_include'] ) ) : '';

		$data = array(
			'service_id'              => $service_id,
			'country_id'              => $country_id,
			'state_id'                => $state_id,
			'city_id'                 => $city_id,
			'status'                  => in_array( $status, $statuses, true ) ? $status : 'active',
			'robots'                  => $robots,
			'sitemap_include'         => in_array( $sitemap, array( '0', '1' ), true ) ? (int) $sitemap : null,
			'custom_h1'               => isset( $_POST['custom_h1'] ) ? sanitize_textarea_field( wp_unslash( $_POST['custom_h1'] ) ) : '',
			'custom_meta_title'       => isset( $_POST['custom_meta_title'] ) ? sanitize_textarea_field( wp_unslash( $_POST['custom_meta_title'] ) ) : '',
			'custom_meta_description' => isset( $_POST['custom_meta_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['custom_meta_description'] ) ) : '',
			'custom_schema_type'      => isset( $_POST['custom_schema_type'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_schema_type'] ) ) : '',
			'canonical_override'      => isset( $_POST['canonical_override'] ) ? esc_url_raw( wp_unslash( $_POST['canonical_override'] ) ) : '',
		);

		$result = $mapping_id
			? $wpdb->update( $tables['service_locations'], $data, array( 'id' => $mapping_id ) )
			: $wpdb->insert( $tables['service_locations'], $data );

		if ( false !== $result ) {
			CH_PSEO_Shortcodes::clear_location_tree_cache();
			CH_PSEO_Sitemap::clear_cache();
		}

		$this->redirect( 'ch-pseo-mappings', false === $result ? 'database_error' : 'saved' );
	}

	/**
	 * Deletes a service/location mapping.
	 *
	 * @return void
	 */
	public function handle_delete_mapping() {
		global $wpdb;

		$mapping_id = isset( $_GET['mapping_id'] ) ? absint( $_GET['mapping_id'] ) : 0;
		$this->authorize_action( 'ch_pseo_delete_mapping_' . $mapping_id );

		if ( ! $mapping_id ) {
			$this->redirect( 'ch-pseo-mappings', 'invalid_item' );
		}

		$result = $wpdb->delete( $this->database->get_service_locations_table(), array( 'id' => $mapping_id ), array( '%d' ) );
		if ( false !== $result ) {
			CH_PSEO_Shortcodes::clear_location_tree_cache();
			CH_PSEO_Sitemap::clear_cache();
		}
		$this->redirect( 'ch-pseo-mappings', false === $result ? 'database_error' : 'deleted' );
	}

	/**
	 * Saves a global or service-specific URL exclusion.
	 *
	 * @return void
	 */
	public function handle_save_exclusion() {
		global $wpdb;

		$this->authorize_action( 'ch_pseo_save_exclusion' );

		$exclusion_id  = isset( $_POST['exclusion_id'] ) ? absint( $_POST['exclusion_id'] ) : 0;
		$service_id    = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$excluded_slug = isset( $_POST['excluded_slug'] ) ? sanitize_title( wp_unslash( $_POST['excluded_slug'] ) ) : '';
		$reason        = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
		$status        = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active';
		$status        = in_array( $status, array( 'active', 'inactive' ), true ) ? $status : 'active';
		$table         = $this->database->get_url_exclusions_table();

		if ( '' === $excluded_slug ) {
			$this->redirect(
				'ch-pseo-exclusions',
				'missing_fields',
				$exclusion_id ? array( 'exclusion_id' => $exclusion_id ) : array()
			);
		}

		if ( $exclusion_id ) {
			$exclusion_exists = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d LIMIT 1", $exclusion_id )
			); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( ! $exclusion_exists ) {
				$this->redirect( 'ch-pseo-exclusions', 'invalid_item' );
			}
		}

		if ( $service_id ) {
			$services_table = $this->database->get_services_table();
			$service_exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$services_table} WHERE id = %d LIMIT 1",
					$service_id
				)
			); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( ! $service_exists ) {
				$this->redirect( 'ch-pseo-exclusions', 'invalid_item' );
			}
		}

		$duplicate = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table}
				WHERE service_id = %d AND excluded_slug = %s AND id != %d
				LIMIT 1",
				$service_id,
				$excluded_slug,
				$exclusion_id
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $duplicate ) {
			$this->redirect(
				'ch-pseo-exclusions',
				'duplicate_exclusion',
				$exclusion_id ? array( 'exclusion_id' => $exclusion_id ) : array()
			);
		}

		$data   = array(
			'service_id'    => $service_id,
			'excluded_slug' => $excluded_slug,
			'reason'        => $reason,
			'status'        => $status,
		);
		$result = $exclusion_id
			? $wpdb->update( $table, $data, array( 'id' => $exclusion_id ) )
			: $wpdb->insert( $table, $data );

		if ( false !== $result ) {
			$this->invalidate_url_generation();
		}

		$this->redirect( 'ch-pseo-exclusions', false === $result ? 'database_error' : 'saved' );
	}

	/**
	 * Deletes a URL exclusion.
	 *
	 * @return void
	 */
	public function handle_delete_exclusion() {
		global $wpdb;

		$exclusion_id = isset( $_GET['exclusion_id'] ) ? absint( $_GET['exclusion_id'] ) : 0;
		$this->authorize_action( 'ch_pseo_delete_exclusion_' . $exclusion_id );

		if ( ! $exclusion_id ) {
			$this->redirect( 'ch-pseo-exclusions', 'invalid_item' );
		}

		$table            = $this->database->get_url_exclusions_table();
		$exclusion_exists = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d LIMIT 1", $exclusion_id )
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $exclusion_exists ) {
			$this->redirect( 'ch-pseo-exclusions', 'invalid_item' );
		}

		$result = $wpdb->delete(
			$table,
			array( 'id' => $exclusion_id ),
			array( '%d' )
		);

		if ( false !== $result ) {
			$this->invalidate_url_generation();
		}

		$this->redirect( 'ch-pseo-exclusions', false === $result ? 'database_error' : 'deleted' );
	}

	/**
	 * Saves one of the global settings forms.
	 *
	 * @return void
	 */
	public function handle_save_plugin_settings() {
		$this->authorize_action( 'ch_pseo_save_plugin_settings' );

		$section = isset( $_POST['settings_section'] ) ? sanitize_key( wp_unslash( $_POST['settings_section'] ) ) : '';
		$values  = array();
		$page    = 'ch-pseo';
		$old_slug = '';
		$old_enabled = '';

		if ( 'seo' === $section ) {
			$values = array(
				'seo_default_title_suffix'        => isset( $_POST['seo_default_title_suffix'] ) ? sanitize_text_field( wp_unslash( $_POST['seo_default_title_suffix'] ) ) : '',
				'seo_global_title_template'       => isset( $_POST['seo_global_title_template'] ) ? sanitize_textarea_field( wp_unslash( $_POST['seo_global_title_template'] ) ) : '',
				'seo_global_description_template' => isset( $_POST['seo_global_description_template'] ) ? sanitize_textarea_field( wp_unslash( $_POST['seo_global_description_template'] ) ) : '',
				'seo_enable_yoast'                => empty( $_POST['seo_enable_yoast'] ) ? '0' : '1',
			);
			$page   = 'ch-pseo-seo';
		} elseif ( 'schema' === $section ) {
			$values = array(
				'schema_enabled'           => empty( $_POST['schema_enabled'] ) ? '0' : '1',
				'schema_default_type'      => isset( $_POST['schema_default_type'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_default_type'] ) ) : 'Service',
				'schema_organization_name' => isset( $_POST['schema_organization_name'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_organization_name'] ) ) : '',
			);
			$page   = 'ch-pseo-schema';
		} elseif ( 'sitemap' === $section ) {
			$old_slug    = $this->get_setting( 'sitemap_slug', 'ch-pseo-pages-sitemap.xml' );
			$old_enabled = $this->get_setting( 'sitemap_enabled', '1' );
			$slug        = isset( $_POST['sitemap_slug'] ) ? sanitize_file_name( wp_unslash( $_POST['sitemap_slug'] ) ) : 'ch-pseo-pages-sitemap.xml';
			$values      = array(
				'sitemap_enabled'  => empty( $_POST['sitemap_enabled'] ) ? '0' : '1',
				'sitemap_slug'     => $slug ? $slug : 'ch-pseo-pages-sitemap.xml',
				'sitemap_max_urls' => (string) max( 1, min( 50000, isset( $_POST['sitemap_max_urls'] ) ? absint( $_POST['sitemap_max_urls'] ) : 50000 ) ),
			);
			$page        = 'ch-pseo-sitemap';
		} else {
			$this->redirect( 'ch-pseo', 'invalid_item' );
		}

		foreach ( $values as $key => $value ) {
			$this->save_setting( $key, $value );
		}

		if ( 'sitemap' === $section ) {
			CH_PSEO_Sitemap::clear_cache();
			if ( $old_slug !== $values['sitemap_slug'] || $old_enabled !== $values['sitemap_enabled'] ) {
				update_option( 'ch_pseo_flush_rewrite_rules', 1, false );
			}
		}

		$this->redirect( $page, 'saved' );
	}

	/**
	 * Re-runs dbDelta for safe schema repair/upgrades.
	 *
	 * @return void
	 */
	public function handle_repair_tables() {
		$this->authorize_action( 'ch_pseo_repair_tables' );
		$migrator = new CH_PSEO_Migrator( $this->database );
		$migrator->repair();
		$this->redirect( 'ch-pseo-tools', 'tables_repaired' );
	}

	/**
	 * Clears the cached location finder tree.
	 *
	 * @return void
	 */
	public function handle_clear_location_cache() {
		$this->authorize_action( 'ch_pseo_clear_location_cache' );
		CH_PSEO_Shortcodes::clear_location_tree_cache();
		$this->redirect( 'ch-pseo-tools', 'cache_cleared' );
	}

	/**
	 * Clears the cached sitemap XML.
	 *
	 * @return void
	 */
	public function handle_clear_sitemap_cache() {
		$this->authorize_action( 'ch_pseo_clear_sitemap_cache' );
		CH_PSEO_Sitemap::clear_cache();
		$this->redirect( 'ch-pseo-tools', 'sitemap_cache_cleared' );
	}

	/**
	 * Exports all eligible generated PSEO URLs as CSV.
	 *
	 * @return void
	 */
	public function handle_export_urls_csv() {
		$this->authorize_action( 'ch_pseo_export_urls_csv' );

		$sitemap = new CH_PSEO_Sitemap( $this->database );
		$urls    = $sitemap->get_generated_urls( 0 );

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename=ch-pseo-generated-urls-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'Unable to create the CSV export.', 'ch-pseo-pages-plugin' ) );
		}

		fputcsv(
			$output,
			array(
				'service_location_id',
				'url',
				'service_name',
				'service_slug',
				'location_structure',
				'country',
				'state',
				'city',
				'robots',
				'lastmod',
			)
		);

		foreach ( $urls as $item ) {
			fputcsv(
				$output,
				array(
					$item['service_location_id'],
					$item['url'],
					$item['service_name'],
					$item['service_slug'],
					$item['location_structure'],
					$item['country_name'],
					$item['state_name'],
					$item['city_name'],
					$item['robots'],
					$item['lastmod'],
				)
			);
		}

		fclose( $output );
		exit;
	}

	/**
	 * Exports locations or mappings in the same format accepted by imports.
	 *
	 * @return void
	 */
	public function handle_export_data_csv() {
		$type = isset( $_GET['export_type'] ) ? sanitize_key( wp_unslash( $_GET['export_type'] ) ) : '';
		$this->authorize_action( 'ch_pseo_export_data_csv_' . $type );

		if ( ! in_array( $type, array( 'locations', 'mappings' ), true ) ) {
			wp_die( esc_html__( 'Invalid CSV export type.', 'ch-pseo-pages-plugin' ) );
		}

		$importer = new CH_PSEO_Importer( $this->database );
		$headers  = 'locations' === $type ? $importer->location_headers() : $importer->mapping_headers();
		$rows     = $importer->export_rows( $type );

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename=ch-pseo-' . $type . '-export-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'Unable to create the CSV export.', 'ch-pseo-pages-plugin' ) );
		}

		fputcsv( $output, $headers );
		foreach ( $rows as $row ) {
			$values = array();
			foreach ( $headers as $header ) {
				$values[] = isset( $row[ $header ] ) ? $row[ $header ] : '';
			}
			fputcsv( $output, $values );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Imports locations or mappings from an uploaded CSV.
	 *
	 * @return void
	 */
	public function handle_import_csv() {
		$this->authorize_action( 'ch_pseo_import_csv' );

		$type = isset( $_POST['import_type'] ) ? sanitize_key( wp_unslash( $_POST['import_type'] ) ) : '';
		if ( ! in_array( $type, array( 'locations', 'mappings' ), true ) || empty( $_FILES['csv_file']['tmp_name'] ) ) {
			$this->redirect( 'ch-pseo-tools', 'missing_fields' );
		}

		$file = $_FILES['csv_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
			$this->redirect( 'ch-pseo-tools', 'import_failed' );
		}

		$importer = new CH_PSEO_Importer( $this->database );
		$result   = $importer->import( $type, $file['tmp_name'], ! empty( $_POST['dry_run'] ) );

		set_transient( 'ch_pseo_import_result_' . get_current_user_id(), $result, 10 * MINUTE_IN_SECONDS );
		if ( empty( $result['errors'] ) && empty( $result['dry_run'] ) ) {
			$this->invalidate_url_generation();
		}

		$this->redirect( 'ch-pseo-tools', empty( $result['errors'] ) ? 'import_complete' : 'import_failed' );
	}

	/**
	 * Downloads a CSV import template.
	 *
	 * @return void
	 */
	public function handle_download_csv_template() {
		$type = isset( $_GET['import_type'] ) ? sanitize_key( wp_unslash( $_GET['import_type'] ) ) : '';
		$this->authorize_action( 'ch_pseo_download_csv_template_' . $type );

		if ( ! in_array( $type, array( 'locations', 'mappings' ), true ) ) {
			wp_die( esc_html__( 'Invalid CSV template type.', 'ch-pseo-pages-plugin' ) );
		}

		$importer = new CH_PSEO_Importer( $this->database );
		$headers  = 'locations' === $type ? $importer->location_headers() : $importer->mapping_headers();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename=ch-pseo-' . $type . '-template.csv' );
		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'Unable to create the CSV template.', 'ch-pseo-pages-plugin' ) );
		}
		fputcsv( $output, $headers );
		fclose( $output );
		exit;
	}

	/**
	 * Applies a bulk action to location rows.
	 *
	 * @return void
	 */
	public function handle_bulk_locations() {
		global $wpdb;

		$this->authorize_action( 'ch_pseo_bulk_locations' );
		$type   = isset( $_POST['location_type'] ) ? sanitize_key( wp_unslash( $_POST['location_type'] ) ) : '';
		$action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$ids    = isset( $_POST['location_ids'] ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST['location_ids'] ) ) ) : array();

		if (
			! in_array( $type, array( 'countries', 'states', 'cities' ), true )
			|| ! in_array( $action, array( 'active', 'inactive', 'delete' ), true )
			|| ! $ids
		) {
			$this->redirect( 'ch-pseo-locations', 'missing_fields', array( 'tab' => $type ) );
		}

		$table = $this->database->get_table_names()[ $type ];
		foreach ( $ids as $id ) {
			if ( 'delete' === $action ) {
				$this->delete_location_records( $type, $id );
			} elseif ( in_array( $action, array( 'active', 'inactive' ), true ) ) {
				$wpdb->update( $table, array( 'status' => $action ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
			}
		}

		CH_PSEO_Shortcodes::clear_location_tree_cache();
		CH_PSEO_Sitemap::clear_cache();
		$this->redirect( 'ch-pseo-locations', 'bulk_updated', array( 'tab' => $type ) );
	}

	/**
	 * Applies a bulk action to mapping rows.
	 *
	 * @return void
	 */
	public function handle_bulk_mappings() {
		global $wpdb;

		$this->authorize_action( 'ch_pseo_bulk_mappings' );
		$action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$ids    = isset( $_POST['mapping_ids'] ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST['mapping_ids'] ) ) ) : array();

		if ( ! in_array( $action, array( 'active', 'inactive', 'delete' ), true ) || ! $ids ) {
			$this->redirect( 'ch-pseo-mappings', 'missing_fields' );
		}

		$table = $this->database->get_service_locations_table();
		foreach ( $ids as $id ) {
			if ( 'delete' === $action ) {
				$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
			} elseif ( in_array( $action, array( 'active', 'inactive' ), true ) ) {
				$wpdb->update( $table, array( 'status' => $action ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
			}
		}

		CH_PSEO_Shortcodes::clear_location_tree_cache();
		CH_PSEO_Sitemap::clear_cache();
		$this->redirect( 'ch-pseo-mappings', 'bulk_updated' );
	}

	/**
	 * Renders a shared global settings view.
	 *
	 * @param string $section Setting section.
	 * @param array  $settings Current setting values.
	 * @return void
	 */
	private function render_plugin_settings_page( $section, $settings ) {
		$this->authorize_page();

		require CH_PSEO_PLUGIN_DIR . 'admin/views/html-admin-plugin-settings.php';
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

		$table = $this->database->get_settings_table();
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT setting_value FROM {$table} WHERE setting_key = %s ORDER BY id DESC LIMIT 1", $key ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return null === $value ? $default : $value;
	}

	/**
	 * Inserts or updates a custom-table setting.
	 *
	 * @param string $key   Setting key.
	 * @param string $value Setting value.
	 * @return void
	 */
	private function save_setting( $key, $value ) {
		global $wpdb;

		$table = $this->database->get_settings_table();
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (setting_key, setting_value)
				VALUES (%s, %s)
				ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
				$key,
				$value
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Checks access to a rendered plugin page.
	 *
	 * @return void
	 */
	private function authorize_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ch-pseo-pages-plugin' ) );
		}
	}

	/**
	 * Checks capability and verifies an admin action nonce.
	 *
	 * @param string $nonce_action Expected nonce action.
	 * @return void
	 */
	private function authorize_action( $nonce_action ) {
		$this->authorize_page();
		check_admin_referer( $nonce_action );
	}

	/**
	 * Redirects back to a plugin page and terminates execution.
	 *
	 * @param string       $page   Admin page slug.
	 * @param string       $notice Optional notice code.
	 * @param array<mixed> $args   Additional query arguments.
	 * @return void
	 */
	private function redirect( $page, $notice = '', $args = array() ) {
		$args['page'] = $page;

		if ( $notice ) {
			$args['ch_pseo_notice'] = $notice;
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Sanitizes a URL base while preserving nested path segments.
	 *
	 * @param string $url_base Raw URL base.
	 * @return string
	 */
	private function sanitize_url_base( $url_base ) {
		$segments = array_filter( explode( '/', trim( $url_base, "/ \t\n\r\0\x0B" ) ) );
		$segments = array_map( 'sanitize_title', $segments );

		return implode( '/', array_filter( $segments ) );
	}

	/**
	 * Gets allowed robots values.
	 *
	 * @return string[]
	 */
	private function get_robots_options() {
		return array(
			'index_follow'     => __( 'Index, Follow', 'ch-pseo-pages-plugin' ),
			'index_nofollow'   => __( 'Index, No Follow', 'ch-pseo-pages-plugin' ),
			'noindex_follow'   => __( 'No Index, Follow', 'ch-pseo-pages-plugin' ),
			'noindex_nofollow' => __( 'No Index, No Follow', 'ch-pseo-pages-plugin' ),
		);
	}

	/**
	 * Invalidates rewrite rules and generated URL caches.
	 *
	 * @return void
	 */
	private function invalidate_url_generation() {
		update_option( 'ch_pseo_flush_rewrite_rules', 1, false );
		CH_PSEO_Router::clear_rewrite_definitions_cache();
		CH_PSEO_Shortcodes::clear_location_tree_cache();
		CH_PSEO_Sitemap::clear_cache();
	}

	/**
	 * Deletes a location and all dependent rows.
	 *
	 * @param string $type    Location table key.
	 * @param int    $item_id Location ID.
	 * @return int|false
	 */
	private function delete_location_records( $type, $item_id ) {
		global $wpdb;

		$tables = $this->database->get_table_names();
		if ( 'countries' === $type ) {
			$state_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$tables['states']} WHERE country_id = %d", $item_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			foreach ( $state_ids as $state_id ) {
				$city_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$tables['cities']} WHERE state_id = %d", $state_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				foreach ( $city_ids as $city_id ) {
					$wpdb->delete( $tables['service_locations'], array( 'city_id' => (int) $city_id ), array( '%d' ) );
				}
				$wpdb->delete( $tables['service_locations'], array( 'state_id' => (int) $state_id ), array( '%d' ) );
				$wpdb->delete( $tables['cities'], array( 'state_id' => (int) $state_id ), array( '%d' ) );
			}
			$wpdb->delete( $tables['service_locations'], array( 'country_id' => $item_id ), array( '%d' ) );
			$wpdb->delete( $tables['cities'], array( 'country_id' => $item_id ), array( '%d' ) );
			$wpdb->delete( $tables['states'], array( 'country_id' => $item_id ), array( '%d' ) );
		} elseif ( 'states' === $type ) {
			$city_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$tables['cities']} WHERE state_id = %d", $item_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			foreach ( $city_ids as $city_id ) {
				$wpdb->delete( $tables['service_locations'], array( 'city_id' => (int) $city_id ), array( '%d' ) );
			}
			$wpdb->delete( $tables['service_locations'], array( 'state_id' => $item_id ), array( '%d' ) );
			$wpdb->delete( $tables['cities'], array( 'state_id' => $item_id ), array( '%d' ) );
		} else {
			$wpdb->delete( $tables['service_locations'], array( 'city_id' => $item_id ), array( '%d' ) );
		}

		return $wpdb->delete( $tables[ $type ], array( 'id' => $item_id ), array( '%d' ) );
	}

	/**
	 * Validates location selections against a service structure.
	 *
	 * State-level mappings are valid for structures that end in a city.
	 *
	 * @param string $structure  Service location structure.
	 * @param int    $country_id Country ID.
	 * @param int    $state_id   State ID.
	 * @param int    $city_id    City ID.
	 * @return bool
	 */
	private function mapping_has_required_locations( $structure, $country_id, $state_id, $city_id ) {
		switch ( $structure ) {
			case 'country_state_city':
				return $country_id > 0
					&& ( 0 === $city_id || $state_id > 0 );
			case 'state_city':
				return $state_id > 0;
			case 'country_state':
				return $country_id > 0;
			case 'country':
				return $country_id > 0;
			case 'state':
				return $state_id > 0;
			default:
				return false;
		}
	}
}
