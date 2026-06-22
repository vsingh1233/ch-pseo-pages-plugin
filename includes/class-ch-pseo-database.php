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
			url_base varchar(191) NOT NULL DEFAULT '',
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
			UNIQUE KEY service_slug (service_slug),
			KEY url_base (url_base),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['countries']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['states']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			country_id bigint(20) unsigned NOT NULL DEFAULT 0,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			PRIMARY KEY  (id),
			UNIQUE KEY country_slug (country_id, slug),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['cities']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			country_id bigint(20) unsigned NOT NULL DEFAULT 0,
			state_id bigint(20) unsigned NOT NULL DEFAULT 0,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			PRIMARY KEY  (id),
			UNIQUE KEY state_slug (state_id, slug),
			KEY country_id (country_id),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['service_locations']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			service_id bigint(20) unsigned NOT NULL,
			country_id bigint(20) unsigned NOT NULL DEFAULT 0,
			state_id bigint(20) unsigned NOT NULL DEFAULT 0,
			city_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'active',
			robots varchar(50) NULL,
			sitemap_include tinyint(1) NULL,
			custom_h1 text NULL,
			custom_meta_title text NULL,
			custom_meta_description text NULL,
			custom_schema_type varchar(100) NULL,
			canonical_override text NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY service_location (service_id, country_id, state_id, city_id),
			KEY country_id (country_id),
			KEY state_id (state_id),
			KEY city_id (city_id),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['url_exclusions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			service_id bigint(20) unsigned NOT NULL DEFAULT 0,
			excluded_slug varchar(191) NOT NULL,
			reason text NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			PRIMARY KEY  (id),
			UNIQUE KEY service_slug (service_id, excluded_slug),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$tables['settings']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			setting_key varchar(191) NOT NULL,
			setting_value longtext NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY setting_key (setting_key)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Normalizes relational IDs, merges duplicate rows, and installs unique indexes.
	 *
	 * WordPress plugins generally avoid foreign keys because custom tables may use
	 * different storage engines and because WordPress itself manages relationships
	 * in application code. This routine provides deterministic relationship values
	 * and database-level uniqueness without introducing foreign-key dependencies.
	 *
	 * @return void
	 */
	public function enforce_relational_constraints() {
		global $wpdb;

		$tables = $this->get_table_names();

		// Remove rows whose required service no longer exists.
		$wpdb->query(
			"DELETE sl FROM {$tables['service_locations']} sl
			LEFT JOIN {$tables['services']} s ON s.id = sl.service_id
			WHERE s.id IS NULL"
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			"DELETE ue FROM {$tables['url_exclusions']} ue
			LEFT JOIN {$tables['services']} s ON s.id = ue.service_id
			WHERE ue.service_id IS NOT NULL AND ue.service_id > 0 AND s.id IS NULL"
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

		// Merge duplicate services and preserve their mappings and exclusions.
		$this->merge_duplicate_services( 'service_slug' );

		// Merge duplicate countries and point all children at the retained row.
		$country_duplicates = "(SELECT slug, MAX(id) AS keep_id FROM {$tables['countries']} GROUP BY slug HAVING COUNT(*) > 1)";
		$wpdb->query( "UPDATE {$tables['states']} st INNER JOIN {$tables['countries']} old ON old.id = st.country_id INNER JOIN {$country_duplicates} d ON d.slug = old.slug SET st.country_id = d.keep_id WHERE st.country_id != d.keep_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "UPDATE {$tables['cities']} ci INNER JOIN {$tables['countries']} old ON old.id = ci.country_id INNER JOIN {$country_duplicates} d ON d.slug = old.slug SET ci.country_id = d.keep_id WHERE ci.country_id != d.keep_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "UPDATE {$tables['service_locations']} sl INNER JOIN {$tables['countries']} old ON old.id = sl.country_id INNER JOIN {$country_duplicates} d ON d.slug = old.slug SET sl.country_id = d.keep_id WHERE sl.country_id != d.keep_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "DELETE old FROM {$tables['countries']} old INNER JOIN {$country_duplicates} d ON d.slug = old.slug WHERE old.id != d.keep_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

		// Invalid optional parents become the explicit root value, zero.
		$wpdb->query( "UPDATE {$tables['states']} st LEFT JOIN {$tables['countries']} co ON co.id = st.country_id SET st.country_id = 0 WHERE st.country_id IS NULL OR (st.country_id > 0 AND co.id IS NULL)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "UPDATE {$tables['cities']} ci LEFT JOIN {$tables['countries']} co ON co.id = ci.country_id SET ci.country_id = 0 WHERE ci.country_id IS NULL OR (ci.country_id > 0 AND co.id IS NULL)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "UPDATE {$tables['cities']} ci LEFT JOIN {$tables['states']} st ON st.id = ci.state_id SET ci.state_id = 0 WHERE ci.state_id IS NULL OR (ci.state_id > 0 AND st.id IS NULL)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "UPDATE {$tables['service_locations']} sl LEFT JOIN {$tables['countries']} co ON co.id = sl.country_id SET sl.country_id = 0 WHERE sl.country_id IS NULL OR (sl.country_id > 0 AND co.id IS NULL)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "UPDATE {$tables['service_locations']} sl LEFT JOIN {$tables['states']} st ON st.id = sl.state_id SET sl.state_id = 0 WHERE sl.state_id IS NULL OR (sl.state_id > 0 AND st.id IS NULL)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "UPDATE {$tables['service_locations']} sl LEFT JOIN {$tables['cities']} ci ON ci.id = sl.city_id SET sl.city_id = 0 WHERE sl.city_id IS NULL OR (sl.city_id > 0 AND ci.id IS NULL)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "UPDATE {$tables['url_exclusions']} SET service_id = 0 WHERE service_id IS NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

		// Merge duplicate states, then cities, preserving all mapping references.
		$state_duplicates = "(SELECT country_id, slug, MAX(id) AS keep_id FROM {$tables['states']} GROUP BY country_id, slug HAVING COUNT(*) > 1)";
		$wpdb->query( "UPDATE {$tables['cities']} ci INNER JOIN {$tables['states']} old ON old.id = ci.state_id INNER JOIN {$state_duplicates} d ON d.country_id = old.country_id AND d.slug = old.slug SET ci.state_id = d.keep_id WHERE ci.state_id != d.keep_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "UPDATE {$tables['service_locations']} sl INNER JOIN {$tables['states']} old ON old.id = sl.state_id INNER JOIN {$state_duplicates} d ON d.country_id = old.country_id AND d.slug = old.slug SET sl.state_id = d.keep_id WHERE sl.state_id != d.keep_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "DELETE old FROM {$tables['states']} old INNER JOIN {$state_duplicates} d ON d.country_id = old.country_id AND d.slug = old.slug WHERE old.id != d.keep_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

		$city_duplicates = "(SELECT state_id, slug, MAX(id) AS keep_id FROM {$tables['cities']} GROUP BY state_id, slug HAVING COUNT(*) > 1)";
		$wpdb->query( "UPDATE {$tables['service_locations']} sl INNER JOIN {$tables['cities']} old ON old.id = sl.city_id INNER JOIN {$city_duplicates} d ON d.state_id = old.state_id AND d.slug = old.slug SET sl.city_id = d.keep_id WHERE sl.city_id != d.keep_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "DELETE old FROM {$tables['cities']} old INNER JOIN {$city_duplicates} d ON d.state_id = old.state_id AND d.slug = old.slug WHERE old.id != d.keep_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

		// Make existing child rows agree with their authoritative parent chain.
		$wpdb->query( "UPDATE {$tables['cities']} ci INNER JOIN {$tables['states']} st ON st.id = ci.state_id SET ci.country_id = st.country_id WHERE ci.state_id > 0 AND ci.country_id != st.country_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "UPDATE {$tables['service_locations']} sl INNER JOIN {$tables['cities']} ci ON ci.id = sl.city_id SET sl.state_id = ci.state_id, sl.country_id = ci.country_id WHERE sl.city_id > 0 AND (sl.state_id != ci.state_id OR sl.country_id != ci.country_id)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "UPDATE {$tables['service_locations']} sl INNER JOIN {$tables['states']} st ON st.id = sl.state_id SET sl.country_id = st.country_id WHERE sl.city_id = 0 AND sl.state_id > 0 AND sl.country_id != st.country_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

		// Keep the newest duplicate relationship/configuration row.
		$wpdb->query( "DELETE old FROM {$tables['service_locations']} old INNER JOIN {$tables['service_locations']} keep ON keep.service_id = old.service_id AND keep.country_id = old.country_id AND keep.state_id = old.state_id AND keep.city_id = old.city_id AND keep.id > old.id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "DELETE old FROM {$tables['url_exclusions']} old INNER JOIN {$tables['url_exclusions']} keep ON keep.service_id = old.service_id AND keep.excluded_slug = old.excluded_slug AND keep.id > old.id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "DELETE old FROM {$tables['settings']} old INNER JOIN {$tables['settings']} keep ON keep.setting_key = old.setting_key AND keep.id > old.id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery

		$wpdb->query( "ALTER TABLE {$tables['states']} MODIFY country_id bigint(20) unsigned NOT NULL DEFAULT 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "ALTER TABLE {$tables['cities']} MODIFY country_id bigint(20) unsigned NOT NULL DEFAULT 0, MODIFY state_id bigint(20) unsigned NOT NULL DEFAULT 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "ALTER TABLE {$tables['service_locations']} MODIFY country_id bigint(20) unsigned NOT NULL DEFAULT 0, MODIFY state_id bigint(20) unsigned NOT NULL DEFAULT 0, MODIFY city_id bigint(20) unsigned NOT NULL DEFAULT 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "ALTER TABLE {$tables['url_exclusions']} MODIFY service_id bigint(20) unsigned NOT NULL DEFAULT 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange

		$this->replace_with_unique_index( $tables['services'], 'service_slug', 'service_slug' );
		$this->replace_with_index( $tables['services'], 'url_base', 'url_base' );
		$this->replace_with_unique_index( $tables['countries'], 'slug', 'slug' );
		$this->replace_with_unique_index( $tables['states'], 'country_slug', 'country_id, slug' );
		$this->replace_with_unique_index( $tables['cities'], 'state_slug', 'state_id, slug' );
		$this->replace_with_unique_index( $tables['service_locations'], 'service_location', 'service_id, country_id, state_id, city_id' );
		$this->replace_with_unique_index( $tables['url_exclusions'], 'service_slug', 'service_id, excluded_slug' );
		$this->replace_with_unique_index( $tables['settings'], 'setting_key', 'setting_key' );
	}

	/**
	 * Removes schema and data that are no longer used by the plugin.
	 *
	 * @return void
	 */
	public function remove_legacy_fields() {
		global $wpdb;

		$this->drop_column_if_exists( $this->get_service_locations_table(), 'custom_intro' );
		$this->drop_column_if_exists( $this->get_settings_table(), 'autoload' );

		$settings_table = $this->get_settings_table();
		$wpdb->delete(
			$settings_table,
			array( 'setting_key' => 'seo_default_robots' ),
			array( '%s' )
		);
	}

	/**
	 * Makes the service URL base an optional, reusable route prefix.
	 *
	 * @return void
	 */
	public function make_url_base_optional() {
		global $wpdb;

		$table = $this->get_services_table();
		$wpdb->query( "UPDATE {$table} SET url_base = '' WHERE url_base IS NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "ALTER TABLE {$table} MODIFY url_base varchar(191) NOT NULL DEFAULT ''" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange
		$this->replace_with_index( $table, 'url_base', 'url_base' );
	}

	/**
	 * Merges services sharing one unique identity column.
	 *
	 * @param string $column Trusted services-table column.
	 * @return void
	 */
	private function merge_duplicate_services( $column ) {
		global $wpdb;

		$tables     = $this->get_table_names();
		$duplicates = "(SELECT {$column}, MAX(id) AS keep_id FROM {$tables['services']} GROUP BY {$column} HAVING COUNT(*) > 1)";

		$wpdb->query( "UPDATE {$tables['service_locations']} sl INNER JOIN {$tables['services']} old ON old.id = sl.service_id INNER JOIN {$duplicates} d ON d.{$column} = old.{$column} SET sl.service_id = d.keep_id WHERE sl.service_id != d.keep_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "UPDATE {$tables['url_exclusions']} ue INNER JOIN {$tables['services']} old ON old.id = ue.service_id INNER JOIN {$duplicates} d ON d.{$column} = old.{$column} SET ue.service_id = d.keep_id WHERE ue.service_id != d.keep_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "DELETE old FROM {$tables['services']} old INNER JOIN {$duplicates} d ON d.{$column} = old.{$column} WHERE old.id != d.keep_id" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Replaces a named index with a unique index.
	 *
	 * Table, index, and column names are internal trusted schema identifiers.
	 *
	 * @param string $table   Fully prefixed plugin table name.
	 * @param string $index   Trusted index name.
	 * @param string $columns Trusted comma-separated column list.
	 * @return void
	 */
	private function replace_with_unique_index( $table, $index, $columns ) {
		global $wpdb;

		$index_exists = $wpdb->get_var( "SHOW INDEX FROM {$table} WHERE Key_name = '{$index}'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( $index_exists ) {
			$wpdb->query( "ALTER TABLE {$table} DROP INDEX {$index}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange
		}

		$wpdb->query( "ALTER TABLE {$table} ADD UNIQUE KEY {$index} ({$columns})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange
	}

	/**
	 * Replaces a named index with a regular non-unique index.
	 *
	 * @param string $table   Fully prefixed plugin table name.
	 * @param string $index   Trusted index name.
	 * @param string $columns Trusted comma-separated column list.
	 * @return void
	 */
	private function replace_with_index( $table, $index, $columns ) {
		global $wpdb;

		$index_exists = $wpdb->get_var( "SHOW INDEX FROM {$table} WHERE Key_name = '{$index}'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( $index_exists ) {
			$wpdb->query( "ALTER TABLE {$table} DROP INDEX {$index}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange
		}

		$wpdb->query( "ALTER TABLE {$table} ADD KEY {$index} ({$columns})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange
	}

	/**
	 * Drops one trusted plugin-table column when it exists.
	 *
	 * @param string $table  Fully prefixed plugin table name.
	 * @param string $column Trusted column name.
	 * @return void
	 */
	private function drop_column_if_exists( $table, $column ) {
		global $wpdb;

		$column_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SHOW COLUMNS FROM {$table} LIKE %s",
				$column
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $column_exists ) {
			$wpdb->query( "ALTER TABLE {$table} DROP COLUMN {$column}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.SchemaChange
		}
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
