<?php
/**
 * Admin tools view.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap ch-pseo-admin">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="ch-pseo-panel ch-pseo-settings-panel">
		<h2><?php esc_html_e( 'Database Tables', 'ch-pseo-pages-plugin' ); ?></h2>
		<ul class="ch-pseo-table-list">
			<?php foreach ( $tables as $label => $table ) : ?>
				<li><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $label ) ) ); ?>:</strong> <code><?php echo esc_html( $table ); ?></code></li>
			<?php endforeach; ?>
		</ul>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="ch_pseo_repair_tables">
			<?php wp_nonce_field( 'ch_pseo_repair_tables' ); ?>
			<?php submit_button( __( 'Check and Update Tables', 'ch-pseo-pages-plugin' ), 'secondary' ); ?>
		</form>
	</div>

	<div class="ch-pseo-panel ch-pseo-settings-panel">
		<h2><?php esc_html_e( 'Sitemap Cache and URL Export', 'ch-pseo-pages-plugin' ); ?></h2>
		<p><?php esc_html_e( 'The generated sitemap XML is cached for 12 hours. Relevant service, location, mapping, and sitemap-setting changes clear it automatically.', 'ch-pseo-pages-plugin' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="ch_pseo_clear_sitemap_cache">
			<?php wp_nonce_field( 'ch_pseo_clear_sitemap_cache' ); ?>
			<?php submit_button( __( 'Clear Sitemap Cache', 'ch-pseo-pages-plugin' ), 'secondary', 'submit', false ); ?>
		</form>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="ch_pseo_export_urls_csv">
			<?php wp_nonce_field( 'ch_pseo_export_urls_csv' ); ?>
			<?php submit_button( __( 'Export Generated URLs as CSV', 'ch-pseo-pages-plugin' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>

	<div class="ch-pseo-panel ch-pseo-settings-panel">
		<h2><?php esc_html_e( 'Location Finder Cache', 'ch-pseo-pages-plugin' ); ?></h2>
		<p><?php esc_html_e( 'The location finder caches active services and mappings for 24 hours. Relevant edits clear it automatically.', 'ch-pseo-pages-plugin' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="ch_pseo_clear_location_cache">
			<?php wp_nonce_field( 'ch_pseo_clear_location_cache' ); ?>
			<?php submit_button( __( 'Clear Location Finder Cache', 'ch-pseo-pages-plugin' ), 'secondary' ); ?>
		</form>
	</div>

	<div class="ch-pseo-panel ch-pseo-settings-panel">
		<h2><?php esc_html_e( 'Uninstall Behavior', 'ch-pseo-pages-plugin' ); ?></h2>
		<form action="options.php" method="post">
			<?php settings_fields( 'ch_pseo_tools' ); ?>
			<label>
				<input name="ch_pseo_remove_data_on_uninstall" type="hidden" value="0">
				<input name="ch_pseo_remove_data_on_uninstall" type="checkbox" value="1" <?php checked( 1, get_option( 'ch_pseo_remove_data_on_uninstall', 0 ) ); ?>>
				<?php esc_html_e( 'Delete all CH PSEO custom tables and settings when the plugin is uninstalled.', 'ch-pseo-pages-plugin' ); ?>
			</label>
			<?php submit_button( __( 'Save Uninstall Setting', 'ch-pseo-pages-plugin' ) ); ?>
		</form>
	</div>

	<div class="ch-pseo-panel ch-pseo-settings-panel">
		<h2><?php esc_html_e( 'Import Tools', 'ch-pseo-pages-plugin' ); ?></h2>
		<p><?php esc_html_e( 'CSV import is intentionally deferred to a later development phase.', 'ch-pseo-pages-plugin' ); ?></p>
	</div>
</div>
