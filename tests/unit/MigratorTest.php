<?php
/**
 * Tests for versioned database migrations.
 *
 * @package CH_PSEO
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies migration version tracking and locking.
 */
final class MigratorTest extends TestCase {

	/**
	 * Clears the in-memory option store before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['ch_pseo_test_options'] = array();
	}

	/**
	 * Verifies a legacy installation receives the initial schema migration.
	 *
	 * @return void
	 */
	public function test_migrates_legacy_installation_and_records_versions() {
		$database = new CH_PSEO_Test_Database();
		$migrator = new CH_PSEO_Migrator( $database );

		$this->assertTrue( $migrator->migrate() );
		$this->assertSame( 1, $database->create_tables_calls );
		$this->assertSame( 1, $database->remove_legacy_fields_calls );
		$this->assertSame( 1, $database->enforce_relational_constraints_calls );
		$this->assertSame( 1, $database->make_url_base_optional_calls );
		$this->assertSame( CH_PSEO_DB_VERSION, get_option( CH_PSEO_Migrator::VERSION_OPTION ) );
		$this->assertSame( CH_PSEO_VERSION, get_option( 'ch_pseo_version' ) );
		$this->assertFalse( get_option( CH_PSEO_Migrator::LOCK_OPTION, false ) );
	}

	/**
	 * Verifies an up-to-date installation does not run dbDelta again.
	 *
	 * @return void
	 */
	public function test_skips_schema_work_when_database_is_current() {
		$GLOBALS['ch_pseo_test_options'][ CH_PSEO_Migrator::VERSION_OPTION ] = CH_PSEO_DB_VERSION;
		$database = new CH_PSEO_Test_Database();
		$migrator = new CH_PSEO_Migrator( $database );

		$this->assertTrue( $migrator->migrate() );
		$this->assertSame( 0, $database->create_tables_calls );
		$this->assertSame( 0, $database->remove_legacy_fields_calls );
		$this->assertSame( 0, $database->enforce_relational_constraints_calls );
		$this->assertSame( 0, $database->make_url_base_optional_calls );
		$this->assertSame( CH_PSEO_VERSION, get_option( 'ch_pseo_version' ) );
	}

	/**
	 * Verifies a recent lock prevents concurrent schema work.
	 *
	 * @return void
	 */
	public function test_recent_lock_prevents_concurrent_migration() {
		$GLOBALS['ch_pseo_test_options'][ CH_PSEO_Migrator::LOCK_OPTION ] = time();
		$database = new CH_PSEO_Test_Database();
		$migrator = new CH_PSEO_Migrator( $database );

		$this->assertFalse( $migrator->migrate() );
		$this->assertSame( 0, $database->create_tables_calls );
		$this->assertSame( 0, $database->remove_legacy_fields_calls );
		$this->assertSame( 0, $database->enforce_relational_constraints_calls );
		$this->assertSame( 0, $database->make_url_base_optional_calls );
		$this->assertSame( '0.0.0', get_option( CH_PSEO_Migrator::VERSION_OPTION, '0.0.0' ) );
	}

	/**
	 * Verifies repair reconciles tables and records the current schema.
	 *
	 * @return void
	 */
	public function test_repair_records_current_schema_version() {
		$database = new CH_PSEO_Test_Database();
		$migrator = new CH_PSEO_Migrator( $database );

		$migrator->repair();

		$this->assertSame( 1, $database->create_tables_calls );
		$this->assertSame( 1, $database->remove_legacy_fields_calls );
		$this->assertSame( 1, $database->enforce_relational_constraints_calls );
		$this->assertSame( 1, $database->make_url_base_optional_calls );
		$this->assertSame( CH_PSEO_DB_VERSION, get_option( CH_PSEO_Migrator::VERSION_OPTION ) );
		$this->assertSame( CH_PSEO_VERSION, get_option( 'ch_pseo_version' ) );
	}

	/**
	 * Verifies a 0.1.0 installation runs the later ordered migrations.
	 *
	 * @return void
	 */
	public function test_migrates_0_1_0_installation_without_recreating_tables() {
		$GLOBALS['ch_pseo_test_options'][ CH_PSEO_Migrator::VERSION_OPTION ] = '0.1.0';
		$database = new CH_PSEO_Test_Database();
		$migrator = new CH_PSEO_Migrator( $database );

		$this->assertTrue( $migrator->migrate() );
		$this->assertSame( 0, $database->create_tables_calls );
		$this->assertSame( 1, $database->remove_legacy_fields_calls );
		$this->assertSame( 1, $database->enforce_relational_constraints_calls );
		$this->assertSame( 1, $database->make_url_base_optional_calls );
		$this->assertSame( CH_PSEO_DB_VERSION, get_option( CH_PSEO_Migrator::VERSION_OPTION ) );
	}

	/**
	 * Verifies a 0.2.0 installation runs the newer schema migrations.
	 *
	 * @return void
	 */
	public function test_migrates_0_2_0_installation_without_older_schema_work() {
		$GLOBALS['ch_pseo_test_options'][ CH_PSEO_Migrator::VERSION_OPTION ] = '0.2.0';
		$database = new CH_PSEO_Test_Database();
		$migrator = new CH_PSEO_Migrator( $database );

		$this->assertTrue( $migrator->migrate() );
		$this->assertSame( 0, $database->create_tables_calls );
		$this->assertSame( 0, $database->remove_legacy_fields_calls );
		$this->assertSame( 1, $database->enforce_relational_constraints_calls );
		$this->assertSame( 1, $database->make_url_base_optional_calls );
		$this->assertSame( CH_PSEO_DB_VERSION, get_option( CH_PSEO_Migrator::VERSION_OPTION ) );
	}

	/**
	 * Verifies a 0.3.0 installation only relaxes the URL-base schema.
	 *
	 * @return void
	 */
	public function test_migrates_0_3_0_installation_without_older_schema_work() {
		$GLOBALS['ch_pseo_test_options'][ CH_PSEO_Migrator::VERSION_OPTION ] = '0.3.0';
		$database = new CH_PSEO_Test_Database();
		$migrator = new CH_PSEO_Migrator( $database );

		$this->assertTrue( $migrator->migrate() );
		$this->assertSame( 0, $database->create_tables_calls );
		$this->assertSame( 0, $database->remove_legacy_fields_calls );
		$this->assertSame( 0, $database->enforce_relational_constraints_calls );
		$this->assertSame( 1, $database->make_url_base_optional_calls );
		$this->assertSame( CH_PSEO_DB_VERSION, get_option( CH_PSEO_Migrator::VERSION_OPTION ) );
	}
}
