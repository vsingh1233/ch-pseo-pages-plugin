<?php
/**
 * Admin service/location mappings view.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

$mapping = wp_parse_args(
	is_array( $mapping ) ? $mapping : array(),
	array(
		'id'                      => 0,
		'service_id'              => 0,
		'country_id'              => 0,
		'state_id'                => 0,
		'city_id'                 => 0,
		'status'                  => 'active',
		'robots'                  => '',
		'sitemap_include'         => '',
		'custom_h1'               => '',
		'custom_meta_title'       => '',
		'custom_meta_description' => '',
		'custom_schema_type'      => '',
		'canonical_override'      => '',
	)
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
			<h2><?php echo $mapping['id'] ? esc_html__( 'Edit Mapping', 'ch-pseo-pages-plugin' ) : esc_html__( 'Add Mapping', 'ch-pseo-pages-plugin' ); ?></h2>

			<?php if ( empty( $services ) ) : ?>
				<p><?php esc_html_e( 'Add a service before creating location mappings.', 'ch-pseo-pages-plugin' ); ?></p>
			<?php else : ?>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="ch-pseo-mapping-form">
					<input type="hidden" name="action" value="ch_pseo_save_mapping">
					<input type="hidden" name="mapping_id" value="<?php echo esc_attr( $mapping['id'] ); ?>">
					<?php wp_nonce_field( 'ch_pseo_save_mapping' ); ?>

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="mapping-service"><?php esc_html_e( 'Service', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td>
								<select id="mapping-service" name="service_id" required>
									<option value=""><?php esc_html_e( 'Select a service', 'ch-pseo-pages-plugin' ); ?></option>
									<?php foreach ( $services as $service ) : ?>
										<option value="<?php echo esc_attr( $service['id'] ); ?>" data-structure="<?php echo esc_attr( $service['location_structure'] ); ?>" <?php selected( (int) $mapping['service_id'], (int) $service['id'] ); ?>><?php echo esc_html( $service['service_name'] ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description" id="ch-pseo-structure-help"></p>
							</td>
						</tr>
						<tr data-location-field="country">
							<th scope="row"><label for="mapping-country"><?php esc_html_e( 'Country', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td>
								<select id="mapping-country" name="country_id">
									<option value=""><?php esc_html_e( 'No country', 'ch-pseo-pages-plugin' ); ?></option>
									<?php foreach ( $countries as $country ) : ?>
										<option value="<?php echo esc_attr( $country['id'] ); ?>" <?php selected( (int) $mapping['country_id'], (int) $country['id'] ); ?>><?php echo esc_html( $country['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr data-location-field="state">
							<th scope="row"><label for="mapping-state"><?php esc_html_e( 'State', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td>
								<select id="mapping-state" name="state_id">
									<option value=""><?php esc_html_e( 'No state', 'ch-pseo-pages-plugin' ); ?></option>
									<?php foreach ( $states as $state ) : ?>
										<option value="<?php echo esc_attr( $state['id'] ); ?>" data-country="<?php echo esc_attr( $state['country_id'] ); ?>" <?php selected( (int) $mapping['state_id'], (int) $state['id'] ); ?>><?php echo esc_html( $state['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr data-location-field="city">
							<th scope="row"><label for="mapping-city"><?php esc_html_e( 'City', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td>
								<select id="mapping-city" name="city_id">
									<option value=""><?php esc_html_e( 'State-level mapping (no city)', 'ch-pseo-pages-plugin' ); ?></option>
									<?php foreach ( $cities as $city ) : ?>
										<option value="<?php echo esc_attr( $city['id'] ); ?>" data-country="<?php echo esc_attr( $city['country_id'] ); ?>" data-state="<?php echo esc_attr( $city['state_id'] ); ?>" <?php selected( (int) $mapping['city_id'], (int) $city['id'] ); ?>><?php echo esc_html( $city['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mapping-status"><?php esc_html_e( 'Status', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td>
								<select id="mapping-status" name="status">
									<option value="active" <?php selected( $mapping['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'ch-pseo-pages-plugin' ); ?></option>
									<option value="inactive" <?php selected( $mapping['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'ch-pseo-pages-plugin' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mapping-robots"><?php esc_html_e( 'Robots override', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td>
								<select id="mapping-robots" name="robots">
									<option value=""><?php esc_html_e( 'Use service default', 'ch-pseo-pages-plugin' ); ?></option>
									<?php foreach ( $robots_options as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $mapping['robots'], $value ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mapping-sitemap"><?php esc_html_e( 'Sitemap override', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td>
								<select id="mapping-sitemap" name="sitemap_include">
									<option value="" <?php selected( $mapping['sitemap_include'], '' ); ?>><?php esc_html_e( 'Use service default', 'ch-pseo-pages-plugin' ); ?></option>
									<option value="1" <?php selected( (string) $mapping['sitemap_include'], '1' ); ?>><?php esc_html_e( 'Include', 'ch-pseo-pages-plugin' ); ?></option>
									<option value="0" <?php selected( (string) $mapping['sitemap_include'], '0' ); ?>><?php esc_html_e( 'Exclude', 'ch-pseo-pages-plugin' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="custom-h1"><?php esc_html_e( 'Custom H1', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td><textarea class="large-text" id="custom-h1" name="custom_h1" rows="2"><?php echo esc_textarea( $mapping['custom_h1'] ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><label for="custom-meta-title"><?php esc_html_e( 'Custom meta title', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td><textarea class="large-text" id="custom-meta-title" name="custom_meta_title" rows="2"><?php echo esc_textarea( $mapping['custom_meta_title'] ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><label for="custom-meta-description"><?php esc_html_e( 'Custom meta description', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td><textarea class="large-text" id="custom-meta-description" name="custom_meta_description" rows="3"><?php echo esc_textarea( $mapping['custom_meta_description'] ); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><label for="custom-schema-type"><?php esc_html_e( 'Schema override', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td><input class="regular-text" id="custom-schema-type" name="custom_schema_type" type="text" value="<?php echo esc_attr( $mapping['custom_schema_type'] ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="canonical-override"><?php esc_html_e( 'Canonical override', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td><input class="large-text" id="canonical-override" name="canonical_override" type="url" value="<?php echo esc_attr( $mapping['canonical_override'] ); ?>"></td>
						</tr>
					</table>

					<?php submit_button( $mapping['id'] ? __( 'Update Mapping', 'ch-pseo-pages-plugin' ) : __( 'Add Mapping', 'ch-pseo-pages-plugin' ) ); ?>
					<?php if ( $mapping['id'] ) : ?>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ch-pseo-mappings' ) ); ?>"><?php esc_html_e( 'Cancel', 'ch-pseo-pages-plugin' ); ?></a>
					<?php endif; ?>
				</form>
			<?php endif; ?>
		</div>

		<div class="ch-pseo-panel ch-pseo-panel-wide">
			<h2><?php esc_html_e( 'Existing Mappings', 'ch-pseo-pages-plugin' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Service', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Location', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Robots', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Sitemap', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'ch-pseo-pages-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $mappings ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No mappings have been created.', 'ch-pseo-pages-plugin' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $mappings as $row ) : ?>
							<?php
							$location_parts = array_filter( array( $row['country_name'], $row['state_name'], $row['city_name'] ) );
							$edit_url = add_query_arg( array( 'page' => 'ch-pseo-mappings', 'mapping_id' => $row['id'] ), admin_url( 'admin.php' ) );
							$delete_url = wp_nonce_url(
								add_query_arg( array( 'action' => 'ch_pseo_delete_mapping', 'mapping_id' => $row['id'] ), admin_url( 'admin-post.php' ) ),
								'ch_pseo_delete_mapping_' . $row['id']
							);
							?>
							<tr>
								<td><?php echo esc_html( $row['service_name'] ); ?></td>
								<td><?php echo esc_html( $location_parts ? implode( ' / ', $location_parts ) : '—' ); ?></td>
								<td><?php echo esc_html( ucfirst( $row['status'] ) ); ?></td>
								<td>
									<?php
									echo esc_html(
										$row['robots'] && isset( $robots_options[ $row['robots'] ] )
											? $robots_options[ $row['robots'] ]
											: __( 'Service default', 'ch-pseo-pages-plugin' )
									);
									?>
								</td>
								<td>
									<?php
									if ( null === $row['sitemap_include'] ) {
										esc_html_e( 'Service default', 'ch-pseo-pages-plugin' );
									} else {
										echo $row['sitemap_include'] ? esc_html__( 'Include', 'ch-pseo-pages-plugin' ) : esc_html__( 'Exclude', 'ch-pseo-pages-plugin' );
									}
									?>
								</td>
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
