<?php
/**
 * Plugin deactivation handler.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Performs non-destructive plugin deactivation tasks.
 */
class CH_PSEO_Deactivator {

	/**
	 * Refreshes rewrite rules without deleting plugin data.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
