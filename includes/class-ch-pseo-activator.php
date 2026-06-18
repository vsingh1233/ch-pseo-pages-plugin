<?php
/**
 * Plugin activation handler.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Performs tasks required when the plugin is activated.
 */
class CH_PSEO_Activator {

	/**
	 * Creates the initial database tables and refreshes rewrite rules.
	 *
	 * @return void
	 */
	public static function activate() {
		$database = new CH_PSEO_Database();
		$database->create_tables();

		update_option( 'ch_pseo_version', CH_PSEO_VERSION );

		$router = new CH_PSEO_Router( $database, new CH_PSEO_Context() );
		$router->register_rewrite_rules();
		$sitemap = new CH_PSEO_Sitemap( $database );
		$sitemap->register_rewrite_rules();
		CH_PSEO_Sitemap::clear_cache();
		flush_rewrite_rules( false );
	}
}
