<?php
/**
 * Tests for the dynamic request context.
 *
 * @package CH_PSEO
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies context resolution and template token replacement.
 */
final class ContextTest extends TestCase {

	/**
	 * Verifies city-level context values and metadata templates.
	 *
	 * @return void
	 */
	public function test_sets_city_context_and_replaces_tokens() {
		$context = new CH_PSEO_Context();
		$context->set_context(
			$this->service(),
			$this->mapping(),
			array(
				'country' => array(
					'id'   => 1,
					'name' => 'United States',
					'slug' => 'united-states',
				),
				'state'   => array(
					'id'   => 2,
					'name' => 'California',
					'slug' => 'california',
				),
				'city'    => array(
					'id'   => 3,
					'name' => 'Los Angeles',
					'slug' => 'los-angeles',
				),
			),
			'https://example.test/services/united-states/california/los-angeles/'
		);

		$this->assertTrue( $context->is_pseo_request() );
		$this->assertSame( 'Los Angeles', $context->get_location() );
		$this->assertSame( 'city', $context->get( 'location_type' ) );
		$this->assertSame( 'Los Angeles, California, United States', $context->get( 'location_full' ) );
		$this->assertSame( 'California', $context->get( 'location_parent' ) );
		$this->assertSame( 'Assignment Help in Los Angeles', $context->get( 'meta_title' ) );
		$this->assertSame(
			'Assignment Help — Los Angeles, California, United States',
			$context->replace_tokens( '{service_name} — {location_full}' )
		);
	}

	/**
	 * Verifies mapping overrides and reset behavior.
	 *
	 * @return void
	 */
	public function test_mapping_overrides_defaults_and_context_can_reset() {
		$context = new CH_PSEO_Context();
		$mapping = $this->mapping();

		$mapping['robots']             = 'noindex_follow';
		$mapping['custom_schema_type'] = 'ProfessionalService';
		$mapping['canonical_override'] = 'https://example.test/preferred/';
		$mapping['custom_meta_title']  = 'Custom {state} title';

		$context->set_context(
			$this->service(),
			$mapping,
			array(
				'country' => array(
					'id'   => 1,
					'name' => 'United States',
					'slug' => 'united-states',
				),
				'state'   => array(
					'id'   => 2,
					'name' => 'California',
					'slug' => 'california',
				),
			),
			'https://example.test/generated/'
		);

		$this->assertSame( 'noindex_follow', $context->get( 'robots' ) );
		$this->assertSame( 'ProfessionalService', $context->get( 'schema_type' ) );
		$this->assertSame( 'https://example.test/preferred/', $context->get( 'canonical_url' ) );
		$this->assertSame( 'Custom California title', $context->get( 'meta_title' ) );

		$context->reset();

		$this->assertFalse( $context->is_pseo_request() );
		$this->assertSame( '', $context->get_location() );
	}

	/**
	 * Supplies a representative service row.
	 *
	 * @return array<string, mixed>
	 */
	private function service() {
		return array(
			'id'                        => 10,
			'service_name'              => 'Assignment Help',
			'service_slug'              => 'assignment-help',
			'url_base'                  => 'services',
			'template_page_id'          => 20,
			'location_structure'        => 'country_state_city',
			'robots_default'            => 'index_follow',
			'meta_title_template'       => '{service_name} in {location}',
			'meta_description_template' => 'Find {service_name} in {location_full}.',
			'schema_type'               => 'Service',
		);
	}

	/**
	 * Supplies a representative mapping row.
	 *
	 * @return array<string, mixed>
	 */
	private function mapping() {
		return array(
			'id'                      => 30,
			'robots'                  => '',
			'custom_schema_type'      => '',
			'canonical_override'      => '',
			'custom_meta_title'       => '',
			'custom_meta_description' => '',
		);
	}
}
