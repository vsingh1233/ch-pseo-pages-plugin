<?php
/**
 * Tests for CSV import contracts.
 *
 * @package CH_PSEO
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies stable CSV template headers.
 */
final class ImporterTest extends TestCase {

	/**
	 * Verifies the location CSV contract.
	 *
	 * @return void
	 */
	public function test_location_headers() {
		$importer = new CH_PSEO_Importer( new CH_PSEO_Test_Database() );

		$this->assertSame(
			array( 'country_name', 'country_slug', 'state_name', 'state_slug', 'city_name', 'city_slug', 'status' ),
			$importer->location_headers()
		);
	}

	/**
	 * Verifies the mapping CSV contract.
	 *
	 * @return void
	 */
	public function test_mapping_headers() {
		$importer = new CH_PSEO_Importer( new CH_PSEO_Test_Database() );

		$this->assertSame(
			array(
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
			),
			$importer->mapping_headers()
		);
	}
}
