<?php
/**
 * Versioned database migrations.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Applies ordered schema migrations and tracks the installed database version.
 */
class CH_PSEO_Migrator {

	/**
	 * Database schema version option.
	 *
	 * @var string
	 */
	const VERSION_OPTION = 'ch_pseo_db_version';

	/**
	 * Migration lock option.
	 *
	 * @var string
	 */
	const LOCK_OPTION = 'ch_pseo_db_migration_lock';

	/**
	 * Maximum migration lock age in seconds.
	 *
	 * @var int
	 */
	const LOCK_TTL = 300;

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
	 * Applies all migrations newer than the installed schema version.
	 *
	 * @return bool True when the schema is ready, false when another request is migrating.
	 */
	public function migrate() {
		$installed_version = (string) get_option( self::VERSION_OPTION, '0.0.0' );

		if ( ! version_compare( $installed_version, CH_PSEO_DB_VERSION, '<' ) ) {
			$this->record_plugin_version();
			return true;
		}

		if ( ! $this->acquire_lock() ) {
			return false;
		}

		try {
			// Re-read after locking in case another request completed first.
			$installed_version = (string) get_option( self::VERSION_OPTION, '0.0.0' );

			foreach ( $this->get_migrations() as $version => $callback ) {
				if (
					version_compare( $version, $installed_version, '>' )
					&& version_compare( $version, CH_PSEO_DB_VERSION, '<=' )
				) {
					call_user_func( $callback );
					update_option( self::VERSION_OPTION, $version, false );
					$installed_version = $version;
				}
			}

			$this->record_plugin_version();
		} finally {
			$this->release_lock();
		}

		return ! version_compare( $installed_version, CH_PSEO_DB_VERSION, '<' );
	}

	/**
	 * Reconciles all tables with the current schema and records current versions.
	 *
	 * @return void
	 */
	public function repair() {
		$this->database->create_tables();
		$this->database->remove_legacy_fields();
		$this->database->enforce_relational_constraints();
		$this->database->make_url_base_optional();
		update_option( self::VERSION_OPTION, CH_PSEO_DB_VERSION, false );
		$this->record_plugin_version();
	}

	/**
	 * Returns migrations in ascending schema-version order.
	 *
	 * Future schema changes should add a new version and migration method here.
	 *
	 * @return array<string, callable>
	 */
	private function get_migrations() {
		return array(
			'0.1.0' => array( $this, 'migrate_to_0_1_0' ),
			'0.2.0' => array( $this, 'migrate_to_0_2_0' ),
			'0.3.0' => array( $this, 'migrate_to_0_3_0' ),
			'0.4.0' => array( $this, 'migrate_to_0_4_0' ),
		);
	}

	/**
	 * Creates or reconciles the initial plugin tables.
	 *
	 * DbDelta() makes this safe for both fresh and existing installations.
	 *
	 * @return void
	 */
	private function migrate_to_0_1_0() {
		$this->database->create_tables();
	}

	/**
	 * Removes unused settings and legacy custom-table columns.
	 *
	 * @return void
	 */
	private function migrate_to_0_2_0() {
		$this->database->remove_legacy_fields();
	}

	/**
	 * Normalizes relationships and enforces database uniqueness.
	 *
	 * @return void
	 */
	private function migrate_to_0_3_0() {
		$this->database->enforce_relational_constraints();
	}

	/**
	 * Makes URL bases optional shared prefixes.
	 *
	 * @return void
	 */
	private function migrate_to_0_4_0() {
		$this->database->make_url_base_optional();
		update_option( 'ch_pseo_flush_rewrite_rules', 1, false );
		delete_transient( 'ch_pseo_rewrite_definitions_v1' );
		delete_transient( 'ch_pseo_location_tree_v1' );
		delete_transient( 'ch_pseo_location_tree_v2' );
		delete_transient( 'ch_pseo_sitemap_xml_v1' );
		update_option( 'ch_pseo_sitemap_cache_version', (int) get_option( 'ch_pseo_sitemap_cache_version', 1 ) + 1, false );
	}

	/**
	 * Acquires a short-lived option-based migration lock.
	 *
	 * @return bool
	 */
	private function acquire_lock() {
		$now = time();

		if ( add_option( self::LOCK_OPTION, $now, '', false ) ) {
			return true;
		}

		$lock_time = (int) get_option( self::LOCK_OPTION, 0 );
		if ( $lock_time > 0 && ( $now - $lock_time ) < self::LOCK_TTL ) {
			return false;
		}

		delete_option( self::LOCK_OPTION );
		return add_option( self::LOCK_OPTION, $now, '', false );
	}

	/**
	 * Releases the migration lock.
	 *
	 * @return void
	 */
	private function release_lock() {
		delete_option( self::LOCK_OPTION );
	}

	/**
	 * Records the running plugin release independently of the schema version.
	 *
	 * @return void
	 */
	private function record_plugin_version() {
		update_option( 'ch_pseo_version', CH_PSEO_VERSION, false );
	}
}
