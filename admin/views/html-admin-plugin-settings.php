<?php
/**
 * Shared SEO, schema, and sitemap settings view.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

$titles = array(
	'seo'     => __( 'SEO Settings', 'ch-pseo-pages-plugin' ),
	'schema'  => __( 'Schema Settings', 'ch-pseo-pages-plugin' ),
	'sitemap' => __( 'Sitemap Settings', 'ch-pseo-pages-plugin' ),
);
?>
<div class="wrap ch-pseo-admin">
	<h1><?php echo esc_html( $titles[ $section ] ); ?></h1>
	<div class="ch-pseo-panel ch-pseo-settings-panel">
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="ch_pseo_save_plugin_settings">
			<input type="hidden" name="settings_section" value="<?php echo esc_attr( $section ); ?>">
			<?php wp_nonce_field( 'ch_pseo_save_plugin_settings' ); ?>

			<table class="form-table" role="presentation">
				<?php if ( 'seo' === $section ) : ?>
					<tr>
						<th scope="row"><label for="seo-global-title-template"><?php esc_html_e( 'Global title template', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<textarea class="large-text" id="seo-global-title-template" name="seo_global_title_template" rows="2"><?php echo esc_textarea( $settings['seo_global_title_template'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Used when neither the mapping nor service supplies a title. Context tokens and CH PSEO shortcodes are supported.', 'ch-pseo-pages-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="seo-global-description-template"><?php esc_html_e( 'Global description template', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<textarea class="large-text" id="seo-global-description-template" name="seo_global_description_template" rows="3"><?php echo esc_textarea( $settings['seo_global_description_template'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Used when neither the mapping nor service supplies a description.', 'ch-pseo-pages-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="seo-title-suffix"><?php esc_html_e( 'Default title suffix', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td><input class="regular-text" id="seo-title-suffix" name="seo_default_title_suffix" type="text" value="<?php echo esc_attr( $settings['seo_default_title_suffix'] ); ?>" placeholder="| Site Name"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Yoast integration', 'ch-pseo-pages-plugin' ); ?></th>
						<td>
							<label><input name="seo_enable_yoast" type="checkbox" value="1" <?php checked( '1', $settings['seo_enable_yoast'] ); ?>> <?php esc_html_e( 'Enable Yoast SEO filters for PSEO requests', 'ch-pseo-pages-plugin' ); ?></label>
							<p class="description"><?php esc_html_e( 'When Yoast is unavailable, CH-PSEO outputs standalone titles, descriptions, canonicals, robots directives, and JSON-LD. When Yoast is active but this option is disabled, Yoast retains its normal template-page output.', 'ch-pseo-pages-plugin' ); ?></p>
						</td>
					</tr>
				<?php elseif ( 'schema' === $section ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Schema output', 'ch-pseo-pages-plugin' ); ?></th>
						<td><label><input name="schema_enabled" type="checkbox" value="1" <?php checked( '1', $settings['schema_enabled'] ); ?>> <?php esc_html_e( 'Enable schema for PSEO requests', 'ch-pseo-pages-plugin' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="schema-default-type"><?php esc_html_e( 'Default schema type', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td><input class="regular-text" id="schema-default-type" name="schema_default_type" type="text" value="<?php echo esc_attr( $settings['schema_default_type'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="schema-organization-name"><?php esc_html_e( 'Organization name', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td><input class="regular-text" id="schema-organization-name" name="schema_organization_name" type="text" value="<?php echo esc_attr( $settings['schema_organization_name'] ); ?>"></td>
					</tr>
				<?php else : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Sitemap output', 'ch-pseo-pages-plugin' ); ?></th>
						<td><label><input name="sitemap_enabled" type="checkbox" value="1" <?php checked( '1', $settings['sitemap_enabled'] ); ?>> <?php esc_html_e( 'Enable the PSEO sitemap endpoint', 'ch-pseo-pages-plugin' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="sitemap-slug"><?php esc_html_e( 'Sitemap filename', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td><input class="regular-text" id="sitemap-slug" name="sitemap_slug" type="text" value="<?php echo esc_attr( $settings['sitemap_slug'] ); ?>" placeholder="ch-pseo-pages-sitemap.xml"></td>
					</tr>
					<tr>
						<th scope="row"><label for="sitemap-max-urls"><?php esc_html_e( 'Maximum URLs per sitemap', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<input id="sitemap-max-urls" name="sitemap_max_urls" type="number" min="1" max="50000" value="<?php echo esc_attr( $settings['sitemap_max_urls'] ); ?>">
							<p class="description"><?php esc_html_e( 'When eligible URLs exceed this value, the configured sitemap filename becomes an index linking to numbered child sitemaps.', 'ch-pseo-pages-plugin' ); ?></p>
						</td>
					</tr>
				<?php endif; ?>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
</div>
