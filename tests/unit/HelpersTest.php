<?php
/**
 * Tests for shared URL helpers.
 *
 * @package CH_PSEO
 */

use PHPUnit\Framework\TestCase;

/**
 * Verifies service route construction.
 */
final class HelpersTest extends TestCase {

	/**
	 * Includes an optional normalized prefix before the service slug.
	 *
	 * @return void
	 */
	public function test_builds_route_with_base_prefix() {
		$this->assertSame(
			'services/assignment-writing',
			ch_pseo_get_service_route( '/Services/', 'Assignment Writing' )
		);
	}

	/**
	 * Starts at the service slug when the prefix is blank.
	 *
	 * @return void
	 */
	public function test_builds_route_without_base_prefix() {
		$this->assertSame(
			'assignment-writing',
			ch_pseo_get_service_route( '', 'Assignment Writing' )
		);
	}
}
