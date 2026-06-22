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
		<p><?php esc_html_e( 'The sitemap index and each numbered URL page are cached independently for 12 hours. Relevant service, location, mapping, and sitemap-setting changes rotate the complete cache generation automatically.', 'ch-pseo-pages-plugin' ); ?></p>
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
		<h2><?php esc_html_e( 'Dynamic Values Reference', 'ch-pseo-pages-plugin' ); ?></h2>
		<p>
			<?php esc_html_e( 'Use tokens in CH-PSEO configuration fields such as meta title, meta description, and H1 templates. Use shortcodes inside the reusable WordPress template page or page-builder content.', 'ch-pseo-pages-plugin' ); ?>
		</p>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Dynamic value', 'ch-pseo-pages-plugin' ); ?></th>
					<th><?php esc_html_e( 'Configuration token', 'ch-pseo-pages-plugin' ); ?></th>
					<th><?php esc_html_e( 'Template-page shortcode', 'ch-pseo-pages-plugin' ); ?></th>
					<th><?php esc_html_e( 'Example output', 'ch-pseo-pages-plugin' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Service name', 'ch-pseo-pages-plugin' ); ?></td>
					<td><code>{service_name}</code></td>
					<td><code>[ch_pseo_service_name]</code></td>
					<td><?php esc_html_e( 'Assignment Writing', 'ch-pseo-pages-plugin' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Service slug', 'ch-pseo-pages-plugin' ); ?></td>
					<td><code>{service_slug}</code></td>
					<td>&mdash;</td>
					<td><code>assignment-writing</code></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Base prefix', 'ch-pseo-pages-plugin' ); ?></td>
					<td><code>{url_base}</code></td>
					<td>&mdash;</td>
					<td><code>services</code></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Country', 'ch-pseo-pages-plugin' ); ?></td>
					<td><code>{country}</code> / <code>{country_name}</code></td>
					<td><code>[ch_pseo_country]</code></td>
					<td><?php esc_html_e( 'India', 'ch-pseo-pages-plugin' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'State', 'ch-pseo-pages-plugin' ); ?></td>
					<td><code>{state}</code> / <code>{state_name}</code></td>
					<td><code>[ch_pseo_state]</code></td>
					<td><?php esc_html_e( 'Tamil Nadu', 'ch-pseo-pages-plugin' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'City', 'ch-pseo-pages-plugin' ); ?></td>
					<td><code>{city}</code> / <code>{city_name}</code></td>
					<td><code>[ch_pseo_city]</code></td>
					<td><?php esc_html_e( 'Chennai', 'ch-pseo-pages-plugin' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Current location', 'ch-pseo-pages-plugin' ); ?></td>
					<td><code>{location}</code></td>
					<td><code>[ch_pseo_location]</code></td>
					<td><?php esc_html_e( 'Chennai', 'ch-pseo-pages-plugin' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Full location', 'ch-pseo-pages-plugin' ); ?></td>
					<td><code>{location_full}</code></td>
					<td><code>[ch_pseo_location_full]</code></td>
					<td><?php esc_html_e( 'Chennai, Tamil Nadu, India', 'ch-pseo-pages-plugin' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Parent location', 'ch-pseo-pages-plugin' ); ?></td>
					<td><code>{location_parent}</code></td>
					<td><code>[ch_pseo_location_parent]</code></td>
					<td><?php esc_html_e( 'Tamil Nadu', 'ch-pseo-pages-plugin' ); ?></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Location type', 'ch-pseo-pages-plugin' ); ?></td>
					<td><code>{location_type}</code></td>
					<td><code>[ch_pseo_location_type]</code></td>
					<td><code>city</code></td>
				</tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Template components', 'ch-pseo-pages-plugin' ); ?></h3>
		<ul class="ch-pseo-table-list">
			<li><code>[ch_pseo_title]</code> — <?php esc_html_e( 'outputs the mapping H1 override, service H1 template, or automatic service/location heading.', 'ch-pseo-pages-plugin' ); ?></li>
			<li><code>[ch_pseo_breadcrumbs]</code> — <?php esc_html_e( 'outputs the visible dynamic breadcrumb navigation.', 'ch-pseo-pages-plugin' ); ?></li>
			<li><code>[ch_pseo_location_finder]</code> — <?php esc_html_e( 'outputs the service and location finder form.', 'ch-pseo-pages-plugin' ); ?></li>
		</ul>

		<p>
			<strong><?php esc_html_e( 'Example meta title:', 'ch-pseo-pages-plugin' ); ?></strong>
			<code>{service_name} Services in {location} | Expert Help</code>
		</p>
		<p>
			<strong><?php esc_html_e( 'Example template content:', 'ch-pseo-pages-plugin' ); ?></strong>
			<code><?php echo esc_html( 'Get professional [ch_pseo_service_name] help in [ch_pseo_city], [ch_pseo_state].' ); ?></code>
		</p>
		<p class="description">
			<?php esc_html_e( 'Short forms such as [country] or [city] are not registered. Use the complete CH-PSEO shortcode names shown above.', 'ch-pseo-pages-plugin' ); ?>
		</p>
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
		<p><?php esc_html_e( 'Imports are transactional: if any row is invalid, no rows from that file are saved. Existing rows with matching slugs are updated.', 'ch-pseo-pages-plugin' ); ?></p>

		<?php if ( is_array( $import_result ) ) : ?>
			<div class="notice notice-<?php echo empty( $import_result['errors'] ) ? 'success' : 'error'; ?> inline">
				<p>
					<?php
					printf(
						/* translators: 1: processed rows, 2: created rows, 3: updated rows. */
						esc_html__( 'Processed: %1$d. Created: %2$d. Updated: %3$d.', 'ch-pseo-pages-plugin' ),
						(int) $import_result['processed'],
						(int) $import_result['created'],
						(int) $import_result['updated']
					);
					if ( ! empty( $import_result['dry_run'] ) ) {
						echo ' ' . esc_html__( 'Dry run only; no changes were saved.', 'ch-pseo-pages-plugin' );
					}
					?>
				</p>
				<?php if ( ! empty( $import_result['errors'] ) ) : ?>
					<ul>
						<?php foreach ( $import_result['errors'] as $import_error ) : ?>
							<li><?php echo esc_html( $import_error ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
			<input type="hidden" name="action" value="ch_pseo_import_csv">
			<?php wp_nonce_field( 'ch_pseo_import_csv' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ch-pseo-import-type"><?php esc_html_e( 'Import type', 'ch-pseo-pages-plugin' ); ?></label></th>
					<td>
						<select id="ch-pseo-import-type" name="import_type">
							<option value="locations"><?php esc_html_e( 'Locations', 'ch-pseo-pages-plugin' ); ?></option>
							<option value="mappings"><?php esc_html_e( 'Service/location mappings', 'ch-pseo-pages-plugin' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ch-pseo-csv-file"><?php esc_html_e( 'CSV file', 'ch-pseo-pages-plugin' ); ?></label></th>
					<td><input id="ch-pseo-csv-file" name="csv_file" type="file" accept=".csv,text/csv" required></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Validation', 'ch-pseo-pages-plugin' ); ?></th>
					<td><label><input name="dry_run" type="checkbox" value="1"> <?php esc_html_e( 'Dry run: validate and report without saving', 'ch-pseo-pages-plugin' ); ?></label></td>
				</tr>
			</table>
			<?php submit_button( __( 'Import CSV', 'ch-pseo-pages-plugin' ), 'primary' ); ?>
		</form>

		<p>
			<?php
			$location_template_url = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'ch_pseo_download_csv_template',
						'import_type' => 'locations',
					),
					admin_url( 'admin-post.php' )
				),
				'ch_pseo_download_csv_template_locations'
			);
			$mapping_template_url  = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'ch_pseo_download_csv_template',
						'import_type' => 'mappings',
					),
					admin_url( 'admin-post.php' )
				),
				'ch_pseo_download_csv_template_mappings'
			);
			?>
			<a class="button" href="<?php echo esc_url( $location_template_url ); ?>"><?php esc_html_e( 'Download Location Template', 'ch-pseo-pages-plugin' ); ?></a>
			<a class="button" href="<?php echo esc_url( $mapping_template_url ); ?>"><?php esc_html_e( 'Download Mapping Template', 'ch-pseo-pages-plugin' ); ?></a>
		</p>

		<?php
		$location_export_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'      => 'ch_pseo_export_data_csv',
					'export_type' => 'locations',
				),
				admin_url( 'admin-post.php' )
			),
			'ch_pseo_export_data_csv_locations'
		);
		$mapping_export_url  = wp_nonce_url(
			add_query_arg(
				array(
					'action'      => 'ch_pseo_export_data_csv',
					'export_type' => 'mappings',
				),
				admin_url( 'admin-post.php' )
			),
			'ch_pseo_export_data_csv_mappings'
		);
		?>

		<h3><?php esc_html_e( 'Export Editable Data', 'ch-pseo-pages-plugin' ); ?></h3>
		<p><?php esc_html_e( 'These exports use the exact import headers. You can keep them as backups, edit them in a spreadsheet, and import them again.', 'ch-pseo-pages-plugin' ); ?></p>
		<p>
			<a class="button" href="<?php echo esc_url( $location_export_url ); ?>"><?php esc_html_e( 'Export Locations CSV', 'ch-pseo-pages-plugin' ); ?></a>
			<a class="button" href="<?php echo esc_url( $mapping_export_url ); ?>"><?php esc_html_e( 'Export Mappings CSV', 'ch-pseo-pages-plugin' ); ?></a>
		</p>

		<h3><?php esc_html_e( 'Location CSV Format', 'ch-pseo-pages-plugin' ); ?></h3>
		<p><?php esc_html_e( 'Use one row for a country, country/state, or country/state/city hierarchy. Slugs may be blank and will be generated from names. A city requires a state.', 'ch-pseo-pages-plugin' ); ?></p>
		<div class="ch-pseo-code-scroll">
			<code>country_name,country_slug,state_name,state_slug,city_name,city_slug,status</code><br>
			<code>India,india,Tamil Nadu,tamil-nadu,Chennai,chennai,active</code><br>
			<code>India,india,Karnataka,karnataka,,,active</code>
		</div>
		<p class="description"><?php esc_html_e( 'Status must be active or inactive. Exported files include hierarchy rows in a safe order so re-importing preserves country, state, and city statuses.', 'ch-pseo-pages-plugin' ); ?></p>

		<h3><?php esc_html_e( 'Mapping CSV Format', 'ch-pseo-pages-plugin' ); ?></h3>
		<p><?php esc_html_e( 'The service and locations must already exist. Location columns must match the service location structure. Existing matching mappings are updated.', 'ch-pseo-pages-plugin' ); ?></p>
		<div class="ch-pseo-code-scroll">
			<code>service_slug,country_slug,state_slug,city_slug,status,robots,sitemap_include,custom_h1,custom_meta_title,custom_meta_description,custom_schema_type,canonical_override</code><br>
			<code>assignment-writing,india,tamil-nadu,chennai,active,index_follow,1,Assignment Writing in Chennai,Assignment Writing Services in Chennai,Get expert assignment help in Chennai,Service,</code>
		</div>
		<ul class="ch-pseo-table-list">
			<li><strong><?php esc_html_e( 'robots:', 'ch-pseo-pages-plugin' ); ?></strong> <code>index_follow</code>, <code>index_nofollow</code>, <code>noindex_follow</code>, <code>noindex_nofollow</code>, <?php esc_html_e( 'or blank to inherit the service default.', 'ch-pseo-pages-plugin' ); ?></li>
			<li><strong><?php esc_html_e( 'sitemap_include:', 'ch-pseo-pages-plugin' ); ?></strong> <code>1</code>, <code>0</code>, <?php esc_html_e( 'or blank to inherit the service default.', 'ch-pseo-pages-plugin' ); ?></li>
			<li><?php esc_html_e( 'Custom content, metadata, schema type, and canonical columns may be left blank.', 'ch-pseo-pages-plugin' ); ?></li>
		</ul>
	</div>
</div>
