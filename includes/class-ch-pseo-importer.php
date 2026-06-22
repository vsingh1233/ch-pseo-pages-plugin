<?php
/**
 * CSV import service.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Imports location hierarchies and service/location mappings from CSV files.
 */
class CH_PSEO_Importer {

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
	 * Imports a CSV file.
	 *
	 * @param string $type    Import type: locations or mappings.
	 * @param string $path    Uploaded CSV path.
	 * @param bool   $dry_run Whether to roll back after validation.
	 * @return array<string, mixed>
	 */
	public function import( $type, $path, $dry_run = false ) {
		$headers = 'locations' === $type ? $this->location_headers() : $this->mapping_headers();
		$rows    = $this->read_csv( $path, $headers );

		if ( ! empty( $rows['errors'] ) ) {
			return $rows;
		}

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		$result = array(
			'processed' => 0,
			'created'   => 0,
			'updated'   => 0,
			'errors'    => array(),
			'dry_run'   => (bool) $dry_run,
		);

		foreach ( $rows['rows'] as $index => $row ) {
			$row_result = 'locations' === $type
				? $this->import_location_row( $row )
				: $this->import_mapping_row( $row );

			if ( is_wp_error( $row_result ) ) {
				$result['errors'][] = sprintf(
					/* translators: 1: CSV row number, 2: error message. */
					__( 'Row %1$d: %2$s', 'ch-pseo-pages-plugin' ),
					$index + 2,
					$row_result->get_error_message()
				);
				continue;
			}

			++$result['processed'];
			$result[ $row_result ]++;
		}

		if ( $result['errors'] || $dry_run ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}

		return $result;
	}

	/**
	 * Gets the location template headers.
	 *
	 * @return string[]
	 */
	public function location_headers() {
		return array( 'country_name', 'country_slug', 'state_name', 'state_slug', 'city_name', 'city_slug', 'status' );
	}

	/**
	 * Gets the mapping template headers.
	 *
	 * @return string[]
	 */
	public function mapping_headers() {
		return array(
			'service_slug',
			'country_slug',
			'state_slug',
			'city_slug',
			'status',
			'robots',
			'sitemap_include',
			'custom_h1',
			'custom_meta_title',
			'custom_meta_description',
			'custom_schema_type',
			'canonical_override',
		);
	}

	/**
	 * Gets import-compatible rows for a CSV backup/export.
	 *
	 * @param string $type Export type: locations or mappings.
	 * @return array<int, array<string, mixed>>
	 */
	public function export_rows( $type ) {
		return 'locations' === $type
			? $this->get_location_export_rows()
			: $this->get_mapping_export_rows();
	}

	/**
	 * Gets location rows in an order that preserves parent statuses on import.
	 *
	 * City rows are followed by state rows and then country rows. Because the
	 * importer upserts every hierarchy level supplied in a row, this ordering
	 * ensures each parent entity's own row is the final status written to it.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_location_export_rows() {
		global $wpdb;

		$tables = $this->database->get_table_names();
		$rows   = $wpdb->get_results(
			"SELECT
				co.name AS country_name, co.slug AS country_slug,
				st.name AS state_name, st.slug AS state_slug,
				ci.name AS city_name, ci.slug AS city_slug,
				ci.status
			FROM {$tables['cities']} ci
			LEFT JOIN {$tables['states']} st ON st.id = ci.state_id
			LEFT JOIN {$tables['countries']} co ON co.id = COALESCE(NULLIF(ci.country_id, 0), st.country_id)
			ORDER BY co.name, st.name, ci.name",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$state_rows = $wpdb->get_results(
			"SELECT
				co.name AS country_name, co.slug AS country_slug,
				st.name AS state_name, st.slug AS state_slug,
				'' AS city_name, '' AS city_slug,
				st.status
			FROM {$tables['states']} st
			LEFT JOIN {$tables['countries']} co ON co.id = st.country_id
			ORDER BY co.name, st.name",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$country_rows = $wpdb->get_results(
			"SELECT
				co.name AS country_name, co.slug AS country_slug,
				'' AS state_name, '' AS state_slug,
				'' AS city_name, '' AS city_slug,
				co.status
			FROM {$tables['countries']} co
			ORDER BY co.name",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_merge( $rows, $state_rows, $country_rows );
	}

	/**
	 * Gets service/location mapping rows accepted by the mapping importer.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_mapping_export_rows() {
		global $wpdb;

		$tables = $this->database->get_table_names();
		return $wpdb->get_results(
			"SELECT
				s.service_slug,
				COALESCE(co.slug, '') AS country_slug,
				COALESCE(st.slug, '') AS state_slug,
				COALESCE(ci.slug, '') AS city_slug,
				sl.status,
				COALESCE(sl.robots, '') AS robots,
				COALESCE(sl.sitemap_include, '') AS sitemap_include,
				COALESCE(sl.custom_h1, '') AS custom_h1,
				COALESCE(sl.custom_meta_title, '') AS custom_meta_title,
				COALESCE(sl.custom_meta_description, '') AS custom_meta_description,
				COALESCE(sl.custom_schema_type, '') AS custom_schema_type,
				COALESCE(sl.canonical_override, '') AS canonical_override
			FROM {$tables['service_locations']} sl
			INNER JOIN {$tables['services']} s ON s.id = sl.service_id
			LEFT JOIN {$tables['countries']} co ON co.id = sl.country_id
			LEFT JOIN {$tables['states']} st ON st.id = sl.state_id
			LEFT JOIN {$tables['cities']} ci ON ci.id = sl.city_id
			ORDER BY s.service_slug, co.slug, st.slug, ci.slug",
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Reads and validates CSV headers.
	 *
	 * @param string   $path             CSV file path.
	 * @param string[] $expected_headers Expected headers.
	 * @return array<string, mixed>
	 */
	private function read_csv( $path, $expected_headers ) {
		$handle = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $handle ) {
			return array(
				'rows' => array(),
				'errors' => array( __( 'The uploaded CSV could not be opened.', 'ch-pseo-pages-plugin' ) ),
			);
		}

		$headers = fgetcsv( $handle );
		if ( isset( $headers[0] ) ) {
			$headers[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $headers[0] );
		}
		$headers = array_map( 'sanitize_key', is_array( $headers ) ? $headers : array() );

		if ( $headers !== $expected_headers ) {
			fclose( $handle );
			return array(
				'rows'   => array(),
				'errors' => array( __( 'CSV headers do not match the selected import type. Download and use the provided template.', 'ch-pseo-pages-plugin' ) ),
			);
		}

		$rows = array();
		while ( false !== ( $values = fgetcsv( $handle ) ) ) {
			$non_empty_values = array_filter(
				$values,
				static function ( $value ) {
					return null !== $value && '' !== $value;
				}
			);
			if ( array( '' ) === $values || empty( $non_empty_values ) ) {
				continue;
			}
			$values = array_pad( $values, count( $headers ), '' );
			$rows[] = array_combine( $headers, array_slice( $values, 0, count( $headers ) ) );
		}
		fclose( $handle );

		return array(
			'rows' => $rows,
			'errors' => array(),
		);
	}

	/**
	 * Imports one location hierarchy row.
	 *
	 * @param array<string, string> $row CSV row.
	 * @return string|WP_Error
	 */
	private function import_location_row( $row ) {
		$status       = $this->sanitize_status( $row['status'] );
		$country_name = sanitize_text_field( $row['country_name'] );
		$country_slug = sanitize_title( $row['country_slug'] ? $row['country_slug'] : $country_name );
		$state_name   = sanitize_text_field( $row['state_name'] );
		$state_slug   = sanitize_title( $row['state_slug'] ? $row['state_slug'] : $state_name );
		$city_name    = sanitize_text_field( $row['city_name'] );
		$city_slug    = sanitize_title( $row['city_slug'] ? $row['city_slug'] : $city_name );

		if ( ! $country_slug && ! $state_slug && ! $city_slug ) {
			return new WP_Error( 'empty_location', __( 'At least one location name or slug is required.', 'ch-pseo-pages-plugin' ) );
		}
		if ( $city_slug && ! $state_slug ) {
			return new WP_Error( 'city_without_state', __( 'A city requires a state in the same row.', 'ch-pseo-pages-plugin' ) );
		}

		$created        = false;
		$country_result = $country_slug ? $this->upsert_location( 'countries', $country_name, $country_slug, $status ) : array(
			'id' => 0,
			'created' => false,
		);
		if ( is_wp_error( $country_result ) ) {
			return $country_result;
		}
		$country_id  = (int) $country_result['id'];
		$state_result = $state_slug ? $this->upsert_location( 'states', $state_name, $state_slug, $status, $country_id ) : array(
			'id' => 0,
			'created' => false,
		);
		if ( is_wp_error( $state_result ) ) {
			return $state_result;
		}
		$state_id   = (int) $state_result['id'];
		$city_result = $city_slug ? $this->upsert_location( 'cities', $city_name, $city_slug, $status, $country_id, $state_id ) : array(
			'id' => 0,
			'created' => false,
		);
		if ( is_wp_error( $city_result ) ) {
			return $city_result;
		}

		foreach ( array( $country_result, $state_result, $city_result ) as $location_result ) {
			if ( ! empty( $location_result['created'] ) ) {
				$created = true;
			}
		}

		return $created ? 'created' : 'updated';
	}

	/**
	 * Creates or updates one location.
	 *
	 * @param string $type       Logical table key.
	 * @param string $name       Location name.
	 * @param string $slug       Location slug.
	 * @param string $status     Status.
	 * @param int    $country_id Parent country ID.
	 * @param int    $state_id   Parent state ID.
	 * @return array<string, mixed>|WP_Error
	 */
	private function upsert_location( $type, $name, $slug, $status, $country_id = 0, $state_id = 0 ) {
		global $wpdb;

		if ( '' === $name ) {
			return new WP_Error( 'missing_name', __( 'Every supplied location slug requires a name.', 'ch-pseo-pages-plugin' ) );
		}

		$table = $this->database->get_table_names()[ $type ];
		if ( 'countries' === $type ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s LIMIT 1", $slug ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} elseif ( 'states' === $type ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s AND COALESCE(country_id, 0) = %d LIMIT 1", $slug, $country_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s AND COALESCE(state_id, 0) = %d LIMIT 1", $slug, $state_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$data = array(
			'name' => $name,
			'slug' => $slug,
			'status' => $status,
		);
		if ( 'states' === $type || 'cities' === $type ) {
			$data['country_id'] = $country_id;
		}
		if ( 'cities' === $type ) {
			$data['state_id'] = $state_id;
		}

		$result = $id ? $wpdb->update( $table, $data, array( 'id' => (int) $id ) ) : $wpdb->insert( $table, $data );
		if ( false === $result ) {
			return new WP_Error( 'database_error', $wpdb->last_error );
		}

		return array(
			'id' => $id ? (int) $id : (int) $wpdb->insert_id,
			'created' => ! $id,
		);
	}

	/**
	 * Imports one mapping row.
	 *
	 * @param array<string, string> $row CSV row.
	 * @return string|WP_Error
	 */
	private function import_mapping_row( $row ) {
		global $wpdb;

		$tables       = $this->database->get_table_names();
		$service_slug = sanitize_title( $row['service_slug'] );
		$service      = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['services']} WHERE service_slug = %s LIMIT 1", $service_slug ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $service ) {
			return new WP_Error( 'missing_service', __( 'The service slug does not exist.', 'ch-pseo-pages-plugin' ) );
		}

		$country = $this->find_location( 'countries', sanitize_title( $row['country_slug'] ) );
		$state   = $this->find_location( 'states', sanitize_title( $row['state_slug'] ), $country ? (int) $country['id'] : 0 );
		$city    = $this->find_location( 'cities', sanitize_title( $row['city_slug'] ), $state ? (int) $state['id'] : 0 );
		$ids     = array(
			'country' => $country ? (int) $country['id'] : 0,
			'state'   => $state ? (int) $state['id'] : 0,
			'city'    => $city ? (int) $city['id'] : 0,
		);

		if ( $row['country_slug'] && ! $country ) {
			return new WP_Error( 'missing_country', __( 'The country slug does not exist.', 'ch-pseo-pages-plugin' ) );
		}
		if ( $row['state_slug'] && ! $state ) {
			return new WP_Error( 'missing_state', __( 'The state slug does not exist under the selected country.', 'ch-pseo-pages-plugin' ) );
		}
		if ( $row['city_slug'] && ! $city ) {
			return new WP_Error( 'missing_city', __( 'The city slug does not exist under the selected state.', 'ch-pseo-pages-plugin' ) );
		}
		if ( ! $this->mapping_locations_valid( $service['location_structure'], $ids ) ) {
			return new WP_Error( 'invalid_structure', __( 'The location columns do not satisfy the service location structure.', 'ch-pseo-pages-plugin' ) );
		}

		if ( 'country' === $service['location_structure'] ) {
			$ids['state'] = 0;
			$ids['city']  = 0;
		} elseif ( in_array( $service['location_structure'], array( 'state', 'country_state' ), true ) ) {
			$ids['city'] = 0;
		}

		$mapping_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$tables['service_locations']}
				WHERE service_id = %d AND COALESCE(country_id, 0) = %d
				AND COALESCE(state_id, 0) = %d AND COALESCE(city_id, 0) = %d LIMIT 1",
				$service['id'],
				$ids['country'],
				$ids['state'],
				$ids['city']
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$robots  = sanitize_key( $row['robots'] );
		$allowed = array( '', 'index_follow', 'index_nofollow', 'noindex_follow', 'noindex_nofollow' );
		if ( ! in_array( $robots, $allowed, true ) ) {
			return new WP_Error( 'invalid_robots', __( 'The robots value is invalid.', 'ch-pseo-pages-plugin' ) );
		}
		$sitemap = trim( $row['sitemap_include'] );
		if ( ! in_array( $sitemap, array( '', '0', '1' ), true ) ) {
			return new WP_Error( 'invalid_sitemap', __( 'Sitemap include must be blank, 0, or 1.', 'ch-pseo-pages-plugin' ) );
		}

		$data = array(
			'service_id'              => (int) $service['id'],
			'country_id'              => $ids['country'],
			'state_id'                => $ids['state'],
			'city_id'                 => $ids['city'],
			'status'                  => $this->sanitize_status( $row['status'] ),
			'robots'                  => $robots ? $robots : null,
			'sitemap_include'         => '' === $sitemap ? null : (int) $sitemap,
			'custom_h1'               => sanitize_textarea_field( $row['custom_h1'] ),
			'custom_meta_title'       => sanitize_textarea_field( $row['custom_meta_title'] ),
			'custom_meta_description' => sanitize_textarea_field( $row['custom_meta_description'] ),
			'custom_schema_type'      => sanitize_text_field( $row['custom_schema_type'] ),
			'canonical_override'      => esc_url_raw( $row['canonical_override'] ),
		);

		$result = $mapping_id
			? $wpdb->update( $tables['service_locations'], $data, array( 'id' => (int) $mapping_id ) )
			: $wpdb->insert( $tables['service_locations'], $data );

		return false === $result ? new WP_Error( 'database_error', $wpdb->last_error ) : ( $mapping_id ? 'updated' : 'created' );
	}

	/**
	 * Finds a location by slug and parent ID.
	 *
	 * @param string $type      Logical table key.
	 * @param string $slug      Location slug.
	 * @param int    $parent_id Parent country or state ID.
	 * @return array|null
	 */
	private function find_location( $type, $slug, $parent_id = 0 ) {
		global $wpdb;
		if ( ! $slug ) {
			return null;
		}
		$table = $this->database->get_table_names()[ $type ];
		if ( 'countries' === $type ) {
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s LIMIT 1", $slug ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		$parent = 'states' === $type ? 'country_id' : 'state_id';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s AND COALESCE({$parent}, 0) = %d LIMIT 1", $slug, $parent_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Validates mapping IDs against a location structure.
	 *
	 * @param string             $structure Service structure.
	 * @param array<string, int> $ids       Location IDs.
	 * @return bool
	 */
	private function mapping_locations_valid( $structure, $ids ) {
		switch ( $structure ) {
			case 'country':
				return $ids['country'] > 0;
			case 'state':
			case 'state_city':
				return $ids['state'] > 0;
			case 'country_state':
			case 'country_state_city':
				return $ids['country'] > 0 && ( ! $ids['city'] || $ids['state'] > 0 );
			default:
				return false;
		}
	}

	/**
	 * Sanitizes active/inactive status.
	 *
	 * @param string $status Raw status.
	 * @return string
	 */
	private function sanitize_status( $status ) {
		$status = sanitize_key( $status );
		return in_array( $status, array( 'active', 'inactive' ), true ) ? $status : 'active';
	}
}
