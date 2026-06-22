<?php
/**
 * Live CSV import smoke test for a local WordPress installation.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	wp_die( esc_html__( 'Run this test through WP-CLI.', 'ch-pseo-pages-plugin' ) );
}

/**
 * Verifies transactional location and mapping imports.
 */
class CH_PSEO_CSV_Import_Smoke_Test {

	/**
	 * Database service.
	 *
	 * @var CH_PSEO_Database
	 */
	private $database;

	/**
	 * Import service.
	 *
	 * @var CH_PSEO_Importer
	 */
	private $importer;

	/**
	 * Temporary service ID.
	 *
	 * @var int
	 */
	private $service_id = 0;

	/**
	 * Temporary page ID.
	 *
	 * @var int
	 */
	private $page_id = 0;

	/**
	 * Temporary CSV files.
	 *
	 * @var string[]
	 */
	private $files = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->database = new CH_PSEO_Database();
		$this->importer = new CH_PSEO_Importer( $this->database );
	}

	/**
	 * Runs the test and always removes fixtures.
	 *
	 * @return void
	 */
	public function run() {
		$error = null;
		try {
			$this->create_service();
			$this->test_location_import();
			$this->test_mapping_import();
			$this->test_export_rows();
		} catch ( Throwable $throwable ) {
			$error = $throwable;
		} finally {
			$this->cleanup();
		}

		if ( $error ) {
			WP_CLI::error( $error->getMessage() );
		}

		WP_CLI::success( 'CSV location and mapping smoke tests passed.' );
	}

	/**
	 * Creates a service used by mapping imports.
	 *
	 * @throws RuntimeException When fixture creation fails.
	 * @return void
	 */
	private function create_service() {
		global $wpdb;

		$this->page_id = wp_insert_post(
			array(
				'post_title'   => 'CH PSEO CSV Test Template',
				'post_name'    => 'ch-pseo-csv-test-template',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => 'CSV TEST',
			),
			true
		);
		if ( is_wp_error( $this->page_id ) ) {
			throw new RuntimeException( $this->page_id->get_error_message() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$result = $wpdb->insert(
			$this->database->get_services_table(),
			array(
				'service_name'       => 'CSV Test Service',
				'service_slug'       => 'csv-test-service',
				'url_base'           => 'ch-pseo-csv-test',
				'template_page_id'   => $this->page_id,
				'location_structure' => 'country_state_city',
				'status'             => 'active',
			)
		);
		if ( false === $result ) {
			throw new RuntimeException( $wpdb->last_error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		$this->service_id = (int) $wpdb->insert_id;
	}

	/**
	 * Verifies dry-run rollback, creation, and location upsert.
	 *
	 * @throws RuntimeException When an assertion fails.
	 * @return void
	 */
	private function test_location_import() {
		global $wpdb;
		$tables = $this->database->get_table_names();
		$file   = $this->write_csv(
			$this->importer->location_headers(),
			array( array( 'CSV Land', 'csv-land', 'CSV State', 'csv-state', 'CSV City', 'csv-city', 'active' ) )
		);

		$dry_run = $this->importer->import( 'locations', $file, true );
		$this->assert_empty_errors( $dry_run );
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tables['countries']} WHERE slug = %s", 'csv-land' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assert( 0 === $count, 'Dry-run location import wrote database rows.' );

		$result = $this->importer->import( 'locations', $file, false );
		$this->assert_empty_errors( $result );
		$this->assert( 1 === (int) $result['processed'], 'Location import did not process one row.' );

		$update_file = $this->write_csv(
			$this->importer->location_headers(),
			array( array( 'CSV Land Updated', 'csv-land', 'CSV State', 'csv-state', 'CSV City', 'csv-city', 'inactive' ) )
		);
		$update = $this->importer->import( 'locations', $update_file, false );
		$this->assert_empty_errors( $update );
		$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$tables['countries']} WHERE slug = %s", 'csv-land' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assert( 'CSV Land Updated' === $name, 'Location upsert did not update the existing country.' );
	}

	/**
	 * Verifies mapping creation and override persistence.
	 *
	 * @throws RuntimeException When an assertion fails.
	 * @return void
	 */
	private function test_mapping_import() {
		global $wpdb;
		$file = $this->write_csv(
			$this->importer->mapping_headers(),
			array(
				array(
					'csv-test-service',
					'csv-land',
					'csv-state',
					'csv-city',
					'active',
					'noindex_follow',
					'0',
					'CSV Custom H1',
					'CSV Meta Title',
					'CSV Meta Description',
					'Service',
					'https://example.test/csv-canonical/',
				),
			)
		);

		$result = $this->importer->import( 'mappings', $file, false );
		$this->assert_empty_errors( $result );
		$mappings_table = $this->database->get_service_locations_table();
		$mapping = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$mappings_table} WHERE service_id = %d LIMIT 1",
				$this->service_id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assert( is_array( $mapping ), 'Mapping import did not create a row.' );
		$this->assert( 'noindex_follow' === $mapping['robots'], 'Mapping robots override was not imported.' );
		$this->assert( 0 === (int) $mapping['sitemap_include'], 'Mapping sitemap override was not imported.' );
		$this->assert( 'CSV Custom H1' === $mapping['custom_h1'], 'Mapping custom H1 was not imported.' );
	}

	/**
	 * Verifies exports use import-compatible rows and preserve overrides.
	 *
	 * @throws RuntimeException When an assertion fails.
	 * @return void
	 */
	private function test_export_rows() {
		$locations = array_values(
			array_filter(
				$this->importer->export_rows( 'locations' ),
				static function ( $row ) {
					return 'csv-city' === $row['city_slug'];
				}
			)
		);
		$this->assert( 1 === count( $locations ), 'Location export did not include the imported city.' );
		$this->assert( 'csv-land' === $locations[0]['country_slug'], 'Location export lost the country hierarchy.' );
		$this->assert( 'csv-state' === $locations[0]['state_slug'], 'Location export lost the state hierarchy.' );
		$this->assert( 'inactive' === $locations[0]['status'], 'Location export did not preserve city status.' );

		$mappings = array_values(
			array_filter(
				$this->importer->export_rows( 'mappings' ),
				static function ( $row ) {
					return 'csv-test-service' === $row['service_slug'];
				}
			)
		);
		$this->assert( 1 === count( $mappings ), 'Mapping export did not include the imported mapping.' );
		$this->assert( 'csv-city' === $mappings[0]['city_slug'], 'Mapping export lost the location slug.' );
		$this->assert( 'noindex_follow' === $mappings[0]['robots'], 'Mapping export lost the robots override.' );
		$this->assert( '0' === (string) $mappings[0]['sitemap_include'], 'Mapping export lost the sitemap override.' );
		$this->assert( 'CSV Meta Title' === $mappings[0]['custom_meta_title'], 'Mapping export lost custom metadata.' );
	}

	/**
	 * Writes a temporary CSV file.
	 *
	 * @param string[] $headers CSV headers.
	 * @param array    $rows    CSV rows.
	 * @throws RuntimeException When the file cannot be written.
	 * @return string
	 */
	private function write_csv( $headers, $rows ) {
		$file   = wp_tempnam( 'ch-pseo-import.csv' );
		$handle = fopen( $file, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $handle ) {
			throw new RuntimeException( 'Unable to create temporary CSV.' );
		}
		fputcsv( $handle, $headers );
		foreach ( $rows as $row ) {
			fputcsv( $handle, $row );
		}
		fclose( $handle );
		$this->files[] = $file;
		return $file;
	}

	/**
	 * Asserts an import has no errors.
	 *
	 * @param array<string, mixed> $result Import result.
	 * @throws RuntimeException When errors are present.
	 * @return void
	 */
	private function assert_empty_errors( $result ) {
		if ( ! empty( $result['errors'] ) ) {
			throw new RuntimeException( implode( '; ', $result['errors'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}

	/**
	 * Throws when a condition is false.
	 *
	 * @param bool   $condition Condition.
	 * @param string $message   Failure message.
	 * @throws RuntimeException When the condition is false.
	 * @return void
	 */
	private function assert( $condition, $message ) {
		if ( ! $condition ) {
			throw new RuntimeException( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Consumed by WP-CLI.
		}
	}

	/**
	 * Removes all temporary database rows and files.
	 *
	 * @return void
	 */
	private function cleanup() {
		global $wpdb;
		$tables = $this->database->get_table_names();
		if ( $this->service_id ) {
			$wpdb->delete( $tables['service_locations'], array( 'service_id' => $this->service_id ), array( '%d' ) );
			$wpdb->delete( $tables['services'], array( 'id' => $this->service_id ), array( '%d' ) );
		}
		$country_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tables['countries']} WHERE slug = %s", 'csv-land' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$state_id   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tables['states']} WHERE slug = %s AND country_id = %d", 'csv-state', $country_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->delete( $tables['cities'], array( 'state_id' => $state_id ), array( '%d' ) );
		$wpdb->delete( $tables['states'], array( 'id' => $state_id ), array( '%d' ) );
		$wpdb->delete( $tables['countries'], array( 'id' => $country_id ), array( '%d' ) );
		if ( $this->page_id ) {
			wp_delete_post( $this->page_id, true );
		}
		foreach ( $this->files as $file ) {
			wp_delete_file( $file );
		}
	}
}

$ch_pseo_csv_import_smoke_test = new CH_PSEO_CSV_Import_Smoke_Test();
$ch_pseo_csv_import_smoke_test->run();
