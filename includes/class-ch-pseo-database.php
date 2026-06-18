<?php
/**
 * Database access and schema management.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides plugin table names and manages the database schema.
 */
class CH_PSEO_Database {

	/**
	 * Gets the fully prefixed services table name.
	 *
	 * @return string
	 */
	public function get_services_table() {
		return $this->get_table_name( 'services' );
	}

	/**
	 * Gets the fully prefixed countries table name.
	 *
	 * @return string
	 */
	public function get_countries_table() {
		return $this->get_table_name( 'countries' );
	}

	/**
	 * Gets the fully prefixed states table name.
	 *
	 * @return string
	 */
	public function get_states_table() {
		return $this->get_table_name( 'states' );
	}

	/**
	 * Gets the fully prefixed cities table name.
	 *
	 * @return string
	 */
	public function get_cities_table() {
		return $this->get_table_name( 'cities' );
	}

	/**
	 * Gets the fully prefixed service locations table name.
	 *
	 * @return string
	 */
	public function get_service_locations_table() {
		return $this->get_table_name( 'service_locations' );
	}

	/**
	 * Gets the fully prefixed URL exclusions table name.
	 *
	 * @return string
	 */
	public function get_url_exclusions_table() {
		return $this->get_table_name( 'url_exclusions' );
	}

	/**
	 * Gets the fully prefixed settings table name.
	 *
	 * @return string
	 */
	public function get_settings_table() {
		return $this->get_table_name( 'settings' );
	}

	/**
	 * Gets all plugin table names keyed by their logical names.
	 *
	 * @return string[]
	 */
	public function get_table_names() {
		return array(
			'services'          => $this->get_services_table(),
			'countries'         => $this->get_countries_table(),
			'states'            => $this->get_states_table(),
			'cities'            => $this->get_cities_table(),
			'service_locations' => $this->get_service_locations_table(),
			'url_exclusions'    => $this->get_url_exclusions_table(),
			'settings'          => $this->get_settings_table(),
		);
	}

	/**
	 * Creates or updates all plugin tables with dbDelta().
	 *
	 * @return void
	 */
	public function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$tables          = $this->get_table_names();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		$sql[] = "CREATE TABLE {$tables['services']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			service_name varchar(191) NOT NULL,
			service_slug varchar(191) NOT NULL,
			url_base varchar(191) NOT NULL,
			template_page_id bigint(20) unsigned NULL,
			location_structure varchar(50) NOT NULL DEFAULT 'country_state_city',
			status varchar(20) NOT NULL DEFAULT 'active',
			robots_default varchar(50) NOT NULL DEFAULT 'index_follow',
			sitemap_include_default tinyint(1) NOT NULL DEFAULT 1,
			meta_title_template text NULL,
			meta_description_template text NULL,
			h1_template text NULL,
			schema_type varchar(100) NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY service_slug (service_slug),
			KEY url_base (url_base),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['countries']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			PRIMARY KEY  (id),
			KEY slug (slug),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['states']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			country_id bigint(20) unsigned NULL,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			PRIMARY KEY  (id),
			KEY country_slug (country_id, slug),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['cities']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			country_id bigint(20) unsigned NULL,
			state_id bigint(20) unsigned NULL,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			PRIMARY KEY  (id),
			KEY state_slug (state_id, slug),
			KEY country_id (country_id),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['service_locations']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			service_id bigint(20) unsigned NOT NULL,
			country_id bigint(20) unsigned NULL,
			state_id bigint(20) unsigned NULL,
			city_id bigint(20) unsigned NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			robots varchar(50) NULL,
			sitemap_include tinyint(1) NULL,
			custom_h1 text NULL,
			custom_meta_title text NULL,
			custom_meta_description text NULL,
			custom_schema_type varchar(100) NULL,
			custom_intro longtext NULL,
			canonical_override text NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY service_location (service_id, country_id, state_id, city_id),
			KEY country_id (country_id),
			KEY state_id (state_id),
			KEY city_id (city_id),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['url_exclusions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			service_id bigint(20) unsigned NULL,
			excluded_slug varchar(191) NOT NULL,
			reason text NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			PRIMARY KEY  (id),
			KEY service_slug (service_id, excluded_slug),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['settings']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			setting_key varchar(191) NOT NULL,
			setting_value longtext NULL,
			autoload varchar(20) NOT NULL DEFAULT 'yes',
			PRIMARY KEY  (id),
			KEY setting_key (setting_key),
			KEY autoload (autoload)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Builds a fully prefixed plugin table name.
	 *
	 * @param string $table Logical table name.
	 * @return string
	 */
	private function get_table_name( $table ) {
		global $wpdb;

		return $wpdb->prefix . 'ch_pseo_' . $table;
	}
}
