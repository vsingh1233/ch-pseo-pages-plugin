<?php
/**
 * Admin services CRUD view.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

$defaults       = array(
	'id'                        => 0,
	'service_name'              => '',
	'service_slug'              => '',
	'url_base'                  => '',
	'template_page_id'          => '',
	'location_structure'        => 'country_state_city',
	'status'                    => 'active',
	'robots_default'            => 'index_follow',
	'sitemap_include_default'   => 1,
	'meta_title_template'       => '',
	'meta_description_template' => '',
	'h1_template'               => '',
	'schema_type'               => '',
);
$service        = wp_parse_args( is_array( $service ) ? $service : array(), $defaults );
$structures     = array(
	'country_state_city' => __( 'Country / State / City', 'ch-pseo-pages-plugin' ),
	'state_city'         => __( 'State / City', 'ch-pseo-pages-plugin' ),
	'country_state'      => __( 'Country / State', 'ch-pseo-pages-plugin' ),
	'country'            => __( 'Country only', 'ch-pseo-pages-plugin' ),
	'state'              => __( 'State only', 'ch-pseo-pages-plugin' ),
);
$robots_options = array(
	'index_follow'     => __( 'Index, Follow', 'ch-pseo-pages-plugin' ),
	'index_nofollow'   => __( 'Index, No Follow', 'ch-pseo-pages-plugin' ),
	'noindex_follow'   => __( 'No Index, Follow', 'ch-pseo-pages-plugin' ),
	'noindex_nofollow' => __( 'No Index, No Follow', 'ch-pseo-pages-plugin' ),
);
?>
<div class="wrap ch-pseo-admin">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="ch-pseo-layout">
		<div class="ch-pseo-panel">
			<h2>
				<?php echo $service['id'] ? esc_html__( 'Edit Service', 'ch-pseo-pages-plugin' ) : esc_html__( 'Add Service', 'ch-pseo-pages-plugin' ); ?>
			</h2>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="ch_pseo_save_service">
				<input type="hidden" name="service_id" value="<?php echo esc_attr( $service['id'] ); ?>">
				<?php wp_nonce_field( 'ch_pseo_save_service' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="service-name"><?php esc_html_e( 'Service name', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td><input class="regular-text" id="service-name" name="service_name" type="text" value="<?php echo esc_attr( $service['service_name'] ); ?>" required data-ch-pseo-slug-source="#service-slug"></td>
					</tr>
					<tr>
						<th scope="row"><label for="service-slug"><?php esc_html_e( 'Service slug', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td><input class="regular-text" id="service-slug" name="service_slug" type="text" value="<?php echo esc_attr( $service['service_slug'] ); ?>" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="url-base"><?php esc_html_e( 'Base prefix', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<input class="regular-text" id="url-base" name="url_base" type="text" value="<?php echo esc_attr( $service['url_base'] ); ?>" placeholder="services">
							<p class="description"><?php esc_html_e( 'Optional. With “services”, URLs begin /services/service-slug/. Leave blank for /service-slug/.', 'ch-pseo-pages-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="template-page"><?php esc_html_e( 'Template page', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<select id="template-page" name="template_page_id">
								<option value=""><?php esc_html_e( 'Select a page', 'ch-pseo-pages-plugin' ); ?></option>
								<?php foreach ( $pages as $template_page ) : ?>
									<option value="<?php echo esc_attr( $template_page->ID ); ?>" <?php selected( (int) $service['template_page_id'], $template_page->ID ); ?>>
										<?php echo esc_html( $template_page->post_title ? $template_page->post_title : __( '(no title)', 'ch-pseo-pages-plugin' ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="location-structure"><?php esc_html_e( 'Location structure', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<select id="location-structure" name="location_structure">
								<?php foreach ( $structures as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $service['location_structure'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="service-status"><?php esc_html_e( 'Status', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<select id="service-status" name="status">
								<option value="active" <?php selected( $service['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'ch-pseo-pages-plugin' ); ?></option>
								<option value="inactive" <?php selected( $service['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'ch-pseo-pages-plugin' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="robots-default"><?php esc_html_e( 'Robots default', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<select id="robots-default" name="robots_default">
								<?php foreach ( $robots_options as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $service['robots_default'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Sitemap default', 'ch-pseo-pages-plugin' ); ?></th>
						<td><label><input name="sitemap_include_default" type="checkbox" value="1" <?php checked( 1, $service['sitemap_include_default'] ); ?>> <?php esc_html_e( 'Include service locations by default', 'ch-pseo-pages-plugin' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="meta-title-template"><?php esc_html_e( 'Meta title template', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td><textarea class="large-text" id="meta-title-template" name="meta_title_template" rows="2"><?php echo esc_textarea( $service['meta_title_template'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="meta-description-template"><?php esc_html_e( 'Meta description template', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td><textarea class="large-text" id="meta-description-template" name="meta_description_template" rows="3"><?php echo esc_textarea( $service['meta_description_template'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="h1-template"><?php esc_html_e( 'H1 template', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td><textarea class="large-text" id="h1-template" name="h1_template" rows="2"><?php echo esc_textarea( $service['h1_template'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="schema-type"><?php esc_html_e( 'Schema type', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td><input class="regular-text" id="schema-type" name="schema_type" type="text" value="<?php echo esc_attr( $service['schema_type'] ); ?>" placeholder="Service"></td>
					</tr>
				</table>

				<?php submit_button( $service['id'] ? __( 'Update Service', 'ch-pseo-pages-plugin' ) : __( 'Add Service', 'ch-pseo-pages-plugin' ) ); ?>
				<?php if ( $service['id'] ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ch-pseo-services' ) ); ?>"><?php esc_html_e( 'Cancel', 'ch-pseo-pages-plugin' ); ?></a>
				<?php endif; ?>
			</form>
		</div>

		<div class="ch-pseo-panel ch-pseo-panel-wide">
			<h2><?php esc_html_e( 'Services', 'ch-pseo-pages-plugin' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Service', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Route root', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Structure', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'ch-pseo-pages-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $services ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No services have been added.', 'ch-pseo-pages-plugin' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $services as $row ) : ?>
							<?php
							$edit_url   = add_query_arg(
								array(
									'page'       => 'ch-pseo-services',
									'service_id' => $row['id'],
								),
								admin_url( 'admin.php' )
							);
							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'action'     => 'ch_pseo_delete_service',
										'service_id' => $row['id'],
									),
									admin_url( 'admin-post.php' )
								),
								'ch_pseo_delete_service_' . $row['id']
							);
							?>
							<tr>
								<td><strong><?php echo esc_html( $row['service_name'] ); ?></strong><br><code><?php echo esc_html( $row['service_slug'] ); ?></code></td>
								<td><code>/<?php echo esc_html( ch_pseo_get_service_route( $row['url_base'], $row['service_slug'] ) ); ?>/</code></td>
								<td><?php echo esc_html( isset( $structures[ $row['location_structure'] ] ) ? $structures[ $row['location_structure'] ] : $row['location_structure'] ); ?></td>
								<td><?php echo esc_html( ucfirst( $row['status'] ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'ch-pseo-pages-plugin' ); ?></a>
									|
									<a class="submitdelete ch-pseo-confirm-delete" href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'ch-pseo-pages-plugin' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
