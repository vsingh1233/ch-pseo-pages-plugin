<?php
/**
 * Plugin Name:       CH-PSEO Pages Plugin
 * Plugin URI:        https://example.com/
 * Description:       A reusable foundation for dynamic programmatic SEO pages in WordPress.
 * Version:           0.1.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            CH
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ch-pseo-pages-plugin
 * Domain Path:       /languages
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

define( 'CH_PSEO_VERSION', '0.1.0' );
define( 'CH_PSEO_PLUGIN_FILE', __FILE__ );
define( 'CH_PSEO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CH_PSEO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once CH_PSEO_PLUGIN_DIR . 'includes/helpers.php';
require_once CH_PSEO_PLUGIN_DIR . 'includes/class-ch-pseo-database.php';
require_once CH_PSEO_PLUGIN_DIR . 'includes/class-ch-pseo-activator.php';
require_once CH_PSEO_PLUGIN_DIR . 'includes/class-ch-pseo-deactivator.php';
require_once CH_PSEO_PLUGIN_DIR . 'includes/class-ch-pseo-context.php';
require_once CH_PSEO_PLUGIN_DIR . 'includes/class-ch-pseo-router.php';
require_once CH_PSEO_PLUGIN_DIR . 'includes/class-ch-pseo-shortcodes.php';
require_once CH_PSEO_PLUGIN_DIR . 'includes/class-ch-pseo-seo.php';
require_once CH_PSEO_PLUGIN_DIR . 'includes/class-ch-pseo-sitemap.php';
require_once CH_PSEO_PLUGIN_DIR . 'includes/class-ch-pseo-schema.php';
require_once CH_PSEO_PLUGIN_DIR . 'admin/class-ch-pseo-admin.php';

register_activation_hook( __FILE__, array( 'CH_PSEO_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CH_PSEO_Deactivator', 'deactivate' ) );

/**
 * Boots the plugin after WordPress has loaded all active plugins.
 *
 * @return void
 */
function ch_pseo_run() {
	$database   = new CH_PSEO_Database();
	$context    = new CH_PSEO_Context();
	$router     = new CH_PSEO_Router( $database, $context );
	$shortcodes = new CH_PSEO_Shortcodes( $context, $database );
	$seo        = new CH_PSEO_SEO( $context, $database );
	$sitemap    = new CH_PSEO_Sitemap( $database );
	$schema     = new CH_PSEO_Schema( $context );

	$router->register_hooks();
	$shortcodes->register_hooks();
	$seo->register_hooks();
	$sitemap->register_hooks();
	$schema->register_hooks();

	if ( is_admin() ) {
		$admin = new CH_PSEO_Admin( $database );
		$admin->register_hooks();
	}
}
add_action( 'plugins_loaded', 'ch_pseo_run' );
