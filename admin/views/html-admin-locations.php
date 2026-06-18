<?php
/**
 * Admin locations CRUD view.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

$item = wp_parse_args(
	is_array( $item ) ? $item : array(),
	array(
		'id'         => 0,
		'name'       => '',
		'slug'       => '',
		'status'     => 'active',
		'country_id' => 0,
		'state_id'   => 0,
	)
);
$tab_labels = array(
	'countries' => __( 'Countries', 'ch-pseo-pages-plugin' ),
	'states'    => __( 'States', 'ch-pseo-pages-plugin' ),
	'cities'    => __( 'Cities', 'ch-pseo-pages-plugin' ),
);
$singular_labels = array(
	'countries' => __( 'Country', 'ch-pseo-pages-plugin' ),
	'states'    => __( 'State', 'ch-pseo-pages-plugin' ),
	'cities'    => __( 'City', 'ch-pseo-pages-plugin' ),
);
$current_items = 'countries' === $tab ? $countries : ( 'states' === $tab ? $states : $cities );
?>
<div class="wrap ch-pseo-admin">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $tab_labels as $tab_key => $tab_label ) : ?>
			<a class="nav-tab <?php echo $tab === $tab_key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ch-pseo-locations', 'tab' => $tab_key ), admin_url( 'admin.php' ) ) ); ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="ch-pseo-layout ch-pseo-layout-top">
		<div class="ch-pseo-panel">
			<h2>
				<?php
				printf(
					/* translators: %s: singular location type. */
					esc_html( $item['id'] ? __( 'Edit %s', 'ch-pseo-pages-plugin' ) : __( 'Add %s', 'ch-pseo-pages-plugin' ) ),
					esc_html( $singular_labels[ $tab ] )
				);
				?>
			</h2>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="ch_pseo_save_location">
				<input type="hidden" name="location_type" value="<?php echo esc_attr( $tab ); ?>">
				<input type="hidden" name="location_id" value="<?php echo esc_attr( $item['id'] ); ?>">
				<?php wp_nonce_field( 'ch_pseo_save_location' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="location-name"><?php esc_html_e( 'Name', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td><input class="regular-text" id="location-name" name="name" type="text" value="<?php echo esc_attr( $item['name'] ); ?>" required data-ch-pseo-slug-source="#location-slug"></td>
					</tr>
					<tr>
						<th scope="row"><label for="location-slug"><?php esc_html_e( 'Slug', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<input class="regular-text" id="location-slug" name="slug" type="text" value="<?php echo esc_attr( $item['slug'] ); ?>">
							<p class="description"><?php esc_html_e( 'Generated from the name when left blank. You can edit it manually.', 'ch-pseo-pages-plugin' ); ?></p>
						</td>
					</tr>

					<?php if ( 'states' === $tab || 'cities' === $tab ) : ?>
						<tr>
							<th scope="row"><label for="country-id"><?php esc_html_e( 'Country', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td>
								<select id="country-id" name="country_id">
									<option value=""><?php esc_html_e( 'No country', 'ch-pseo-pages-plugin' ); ?></option>
									<?php foreach ( $countries as $country ) : ?>
										<option value="<?php echo esc_attr( $country['id'] ); ?>" <?php selected( (int) $item['country_id'], (int) $country['id'] ); ?>><?php echo esc_html( $country['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Optional for state/city-only websites.', 'ch-pseo-pages-plugin' ); ?></p>
							</td>
						</tr>
					<?php endif; ?>

					<?php if ( 'cities' === $tab ) : ?>
						<tr>
							<th scope="row"><label for="state-id"><?php esc_html_e( 'State', 'ch-pseo-pages-plugin' ); ?></label></th>
							<td>
								<select id="state-id" name="state_id">
									<option value=""><?php esc_html_e( 'No state', 'ch-pseo-pages-plugin' ); ?></option>
									<?php foreach ( $states as $state ) : ?>
										<option value="<?php echo esc_attr( $state['id'] ); ?>" <?php selected( (int) $item['state_id'], (int) $state['id'] ); ?>><?php echo esc_html( $state['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					<?php endif; ?>

					<tr>
						<th scope="row"><label for="location-status"><?php esc_html_e( 'Status', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<select id="location-status" name="status">
								<option value="active" <?php selected( $item['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'ch-pseo-pages-plugin' ); ?></option>
								<option value="inactive" <?php selected( $item['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'ch-pseo-pages-plugin' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<?php submit_button( $item['id'] ? __( 'Update Location', 'ch-pseo-pages-plugin' ) : __( 'Add Location', 'ch-pseo-pages-plugin' ) ); ?>
				<?php if ( $item['id'] ) : ?>
					<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'ch-pseo-locations', 'tab' => $tab ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Cancel', 'ch-pseo-pages-plugin' ); ?></a>
				<?php endif; ?>
			</form>
		</div>

		<div class="ch-pseo-panel ch-pseo-panel-wide">
			<h2><?php echo esc_html( $tab_labels[ $tab ] ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Slug', 'ch-pseo-pages-plugin' ); ?></th>
						<?php if ( 'countries' !== $tab ) : ?><th><?php esc_html_e( 'Country', 'ch-pseo-pages-plugin' ); ?></th><?php endif; ?>
						<?php if ( 'cities' === $tab ) : ?><th><?php esc_html_e( 'State', 'ch-pseo-pages-plugin' ); ?></th><?php endif; ?>
						<th><?php esc_html_e( 'Status', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'ch-pseo-pages-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $current_items ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No locations found.', 'ch-pseo-pages-plugin' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $current_items as $row ) : ?>
							<?php
							$edit_url = add_query_arg(
								array(
									'page'        => 'ch-pseo-locations',
									'tab'         => $tab,
									'location_id' => $row['id'],
								),
								admin_url( 'admin.php' )
							);
							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'action'        => 'ch_pseo_delete_location',
										'location_type' => $tab,
										'location_id'   => $row['id'],
									),
									admin_url( 'admin-post.php' )
								),
								'ch_pseo_delete_location_' . $tab . '_' . $row['id']
							);
							?>
							<tr>
								<td><strong><?php echo esc_html( $row['name'] ); ?></strong></td>
								<td><code><?php echo esc_html( $row['slug'] ); ?></code></td>
								<?php if ( 'countries' !== $tab ) : ?><td><?php echo esc_html( ! empty( $row['country_name'] ) ? $row['country_name'] : '—' ); ?></td><?php endif; ?>
								<?php if ( 'cities' === $tab ) : ?><td><?php echo esc_html( ! empty( $row['state_name'] ) ? $row['state_name'] : '—' ); ?></td><?php endif; ?>
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
