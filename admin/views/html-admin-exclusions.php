<?php
/**
 * Admin URL exclusions CRUD view.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

$exclusion = wp_parse_args(
	is_array( $exclusion ) ? $exclusion : array(),
	array(
		'id'            => 0,
		'service_id'    => 0,
		'excluded_slug' => '',
		'reason'        => '',
		'status'        => 'active',
	)
);
?>
<div class="wrap ch-pseo-admin">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="ch-pseo-layout">
		<div class="ch-pseo-panel">
			<h2>
				<?php echo $exclusion['id'] ? esc_html__( 'Edit URL Exclusion', 'ch-pseo-pages-plugin' ) : esc_html__( 'Add URL Exclusion', 'ch-pseo-pages-plugin' ); ?>
			</h2>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="ch_pseo_save_exclusion">
				<input type="hidden" name="exclusion_id" value="<?php echo esc_attr( $exclusion['id'] ); ?>">
				<?php wp_nonce_field( 'ch_pseo_save_exclusion' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="exclusion-service"><?php esc_html_e( 'Service', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<select id="exclusion-service" name="service_id">
								<option value=""><?php esc_html_e( 'All services (global)', 'ch-pseo-pages-plugin' ); ?></option>
								<?php foreach ( $services as $service ) : ?>
									<option value="<?php echo esc_attr( $service['id'] ); ?>" <?php selected( (int) $exclusion['service_id'], (int) $service['id'] ); ?>>
										<?php echo esc_html( $service['service_name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Leave empty to exclude this first child slug from every service.', 'ch-pseo-pages-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="excluded-slug"><?php esc_html_e( 'Excluded slug', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<input class="regular-text" id="excluded-slug" name="excluded_slug" type="text" value="<?php echo esc_attr( $exclusion['excluded_slug'] ); ?>" required>
							<p class="description"><?php esc_html_e( 'Example: demand-letter-preparation. The value is normalized as a WordPress slug.', 'ch-pseo-pages-plugin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="exclusion-reason"><?php esc_html_e( 'Reason', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td><textarea class="large-text" id="exclusion-reason" name="reason" rows="3"><?php echo esc_textarea( $exclusion['reason'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="exclusion-status"><?php esc_html_e( 'Status', 'ch-pseo-pages-plugin' ); ?></label></th>
						<td>
							<select id="exclusion-status" name="status">
								<option value="active" <?php selected( $exclusion['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'ch-pseo-pages-plugin' ); ?></option>
								<option value="inactive" <?php selected( $exclusion['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'ch-pseo-pages-plugin' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<?php submit_button( $exclusion['id'] ? __( 'Update Exclusion', 'ch-pseo-pages-plugin' ) : __( 'Add Exclusion', 'ch-pseo-pages-plugin' ) ); ?>
				<?php if ( $exclusion['id'] ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ch-pseo-exclusions' ) ); ?>"><?php esc_html_e( 'Cancel', 'ch-pseo-pages-plugin' ); ?></a>
				<?php endif; ?>
			</form>
		</div>

		<div class="ch-pseo-panel ch-pseo-panel-wide">
			<h2><?php esc_html_e( 'Existing URL Exclusions', 'ch-pseo-pages-plugin' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Slug', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Scope', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ch-pseo-pages-plugin' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'ch-pseo-pages-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $exclusions ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No URL exclusions have been added.', 'ch-pseo-pages-plugin' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $exclusions as $row ) : ?>
							<?php
							$edit_url = add_query_arg(
								array(
									'page'         => 'ch-pseo-exclusions',
									'exclusion_id' => $row['id'],
								),
								admin_url( 'admin.php' )
							);
							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'action'       => 'ch_pseo_delete_exclusion',
										'exclusion_id' => $row['id'],
									),
									admin_url( 'admin-post.php' )
								),
								'ch_pseo_delete_exclusion_' . $row['id']
							);
							?>
							<tr>
								<td><code><?php echo esc_html( $row['excluded_slug'] ); ?></code></td>
								<td><?php echo esc_html( $row['service_id'] ? $row['service_name'] : __( 'Global', 'ch-pseo-pages-plugin' ) ); ?></td>
								<td><?php echo esc_html( $row['reason'] ? $row['reason'] : '—' ); ?></td>
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

