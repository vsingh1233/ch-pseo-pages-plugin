<?php
/**
 * Live relational-constraint smoke test for a local WordPress installation.
 *
 * Run with:
 * wp eval-file tests/integration/class-ch-pseo-relational-constraints-smoke-test.php
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	wp_die( esc_html__( 'Run this test through WP-CLI.', 'ch-pseo-pages-plugin' ) );
}

/**
 * Verifies normalized relationship columns and all unique identities.
 */
class CH_PSEO_Relational_Constraints_Smoke_Test {

	/**
	 * Runs assertions in a transaction and rolls all fixtures back.
	 *
	 * @return void
	 */
	public function run() {
		global $wpdb;

		$database        = new CH_PSEO_Database();
		$tables          = $database->get_table_names();
		$suffix          = strtolower( wp_generate_password( 10, false, false ) );
		$previous_errors = $wpdb->suppress_errors( true );
		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		try {
			$this->assert_normalized_columns( $tables );

			$this->insert(
				$tables['services'],
				array(
					'service_name' => 'Constraint Test',
					'service_slug' => "constraint-{$suffix}",
					'url_base' => "constraint-{$suffix}",
				)
			);
			$service_id = (int) $wpdb->insert_id;
			$this->assert_rejected(
				$tables['services'],
				array(
					'service_name' => 'Duplicate Slug',
					'service_slug' => "constraint-{$suffix}",
					'url_base' => "constraint-other-{$suffix}",
				),
				'duplicate service slug'
			);
			$this->insert(
				$tables['services'],
				array(
					'service_name' => 'Shared Prefix',
					'service_slug' => "constraint-other-{$suffix}",
					'url_base' => "constraint-{$suffix}",
				)
			);

			$this->insert(
				$tables['countries'],
				array(
					'name' => 'Constraint Country',
					'slug' => "constraint-{$suffix}",
				)
			);
			$country_id = (int) $wpdb->insert_id;
			$this->assert_rejected(
				$tables['countries'],
				array(
					'name' => 'Duplicate Country',
					'slug' => "constraint-{$suffix}",
				),
				'duplicate country slug'
			);

			$this->insert(
				$tables['states'],
				array(
					'country_id' => $country_id,
					'name' => 'Constraint State',
					'slug' => "constraint-{$suffix}",
				)
			);
			$state_id = (int) $wpdb->insert_id;
			$this->assert_rejected(
				$tables['states'],
				array(
					'country_id' => $country_id,
					'name' => 'Duplicate State',
					'slug' => "constraint-{$suffix}",
				),
				'duplicate state identity'
			);

			$this->insert(
				$tables['cities'],
				array(
					'country_id' => $country_id,
					'state_id' => $state_id,
					'name' => 'Constraint City',
					'slug' => "constraint-{$suffix}",
				)
			);
			$city_id = (int) $wpdb->insert_id;
			$this->assert_rejected(
				$tables['cities'],
				array(
					'country_id' => $country_id,
					'state_id' => $state_id,
					'name' => 'Duplicate City',
					'slug' => "constraint-{$suffix}",
				),
				'duplicate city identity'
			);

			$mapping = array(
				'service_id' => $service_id,
				'country_id' => $country_id,
				'state_id' => $state_id,
				'city_id' => $city_id,
			);
			$this->insert( $tables['service_locations'], $mapping );
			$this->assert_rejected( $tables['service_locations'], $mapping, 'duplicate service/location mapping' );

			$exclusion = array(
				'service_id' => $service_id,
				'excluded_slug' => "excluded-{$suffix}",
			);
			$this->insert( $tables['url_exclusions'], $exclusion );
			$this->assert_rejected( $tables['url_exclusions'], $exclusion, 'duplicate exclusion' );

			$setting = array(
				'setting_key' => "constraint_{$suffix}",
				'setting_value' => 'one',
			);
			$this->insert( $tables['settings'], $setting );
			$this->assert_rejected( $tables['settings'], $setting, 'duplicate setting key' );
		} catch ( Throwable $throwable ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->suppress_errors( $previous_errors );
			WP_CLI::error( $throwable->getMessage() );
		}

		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->suppress_errors( $previous_errors );
		WP_CLI::success( 'Relational constraint smoke tests passed.' );
	}

	/**
	 * Verifies optional mapping IDs use a deterministic zero default.
	 *
	 * @param array<string, string> $tables Plugin tables.
	 * @throws RuntimeException When a column is nullable or has another default.
	 * @return void
	 */
	private function assert_normalized_columns( $tables ) {
		global $wpdb;

		foreach ( array( 'country_id', 'state_id', 'city_id' ) as $column ) {
			$row = $wpdb->get_row( "SHOW COLUMNS FROM {$tables['service_locations']} LIKE '{$column}'", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( ! $row || 'NO' !== $row['Null'] || '0' !== (string) $row['Default'] ) {
				throw new RuntimeException( "Mapping column {$column} is not normalized." ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}
		}
	}

	/**
	 * Inserts one required fixture.
	 *
	 * @param string               $table Table name.
	 * @param array<string, mixed> $data  Row data.
	 * @throws RuntimeException When insertion fails.
	 * @return void
	 */
	private function insert( $table, $data ) {
		global $wpdb;

		if ( false === $wpdb->insert( $table, $data ) ) {
			throw new RuntimeException( "Fixture insert failed: {$wpdb->last_error}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}

	/**
	 * Confirms that a duplicate row is rejected by MySQL.
	 *
	 * @param string               $table   Table name.
	 * @param array<string, mixed> $data    Duplicate row.
	 * @param string               $message Assertion description.
	 * @throws RuntimeException When insertion unexpectedly succeeds.
	 * @return void
	 */
	private function assert_rejected( $table, $data, $message ) {
		global $wpdb;

		if ( false !== $wpdb->insert( $table, $data ) ) {
			throw new RuntimeException( "Database accepted {$message}." ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
	}
}

( new CH_PSEO_Relational_Constraints_Smoke_Test() )->run();
