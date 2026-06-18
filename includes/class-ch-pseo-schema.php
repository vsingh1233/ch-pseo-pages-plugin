<?php
/**
 * Structured data integration.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds schema data for dynamic PSEO requests.
 */
class CH_PSEO_Schema {

	/**
	 * Current request context.
	 *
	 * @var CH_PSEO_Context
	 */
	private $context;

	/**
	 * Constructor.
	 *
	 * @param CH_PSEO_Context $context Request context.
	 */
	public function __construct( CH_PSEO_Context $context ) {
		$this->context = $context;
	}

	/**
	 * Registers schema hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		// Yoast schema graph and standalone JSON-LD hooks come later.
	}
}

