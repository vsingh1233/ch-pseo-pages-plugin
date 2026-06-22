<?php
/**
 * Plugin uninstall handler.
 *
 * @package CH_PSEO
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! get_option( 'ch_pseo_remove_data_on_uninstall', false ) ) {
	return;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'ch_pseo_service_locations',
	$wpdb->prefix . 'ch_pseo_url_exclusions',
	$wpdb->prefix . 'ch_pseo_cities',
	$wpdb->prefix . 'ch_pseo_states',
	$wpdb->prefix . 'ch_pseo_countries',
	$wpdb->prefix . 'ch_pseo_services',
	$wpdb->prefix . 'ch_pseo_settings',
);

foreach ( $tables as $table ) {
	// Table names are built exclusively from WordPress's trusted database prefix.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

delete_option( 'ch_pseo_version' );
delete_option( 'ch_pseo_db_version' );
delete_option( 'ch_pseo_db_migration_lock' );
delete_option( 'ch_pseo_sitemap_cache_version' );
delete_option( 'ch_pseo_remove_data_on_uninstall' );
delete_transient( 'ch_pseo_rewrite_definitions_v1' );
delete_transient( 'ch_pseo_sitemap_xml_v1' );
