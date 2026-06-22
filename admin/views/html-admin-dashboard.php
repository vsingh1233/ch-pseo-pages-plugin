<?php
/**
 * Admin dashboard view.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

$cards = array(
	'services'  => __( 'Services', 'ch-pseo-pages-plugin' ),
	'countries' => __( 'Countries', 'ch-pseo-pages-plugin' ),
	'states'    => __( 'States', 'ch-pseo-pages-plugin' ),
	'cities'    => __( 'Cities', 'ch-pseo-pages-plugin' ),
	'mappings'  => __( 'Active Mappings', 'ch-pseo-pages-plugin' ),
	'indexable' => __( 'Indexable Pages', 'ch-pseo-pages-plugin' ),
	'noindex'   => __( 'Noindex Pages', 'ch-pseo-pages-plugin' ),
);
?>
<div class="wrap ch-pseo-admin">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="ch-pseo-panel">
		<h2><?php esc_html_e( 'Dynamic PSEO Pages', 'ch-pseo-pages-plugin' ); ?></h2>
		<p>
			<?php esc_html_e( 'The plugin builds virtual service-and-location URLs from active mappings and renders them through each service’s published template page.', 'ch-pseo-pages-plugin' ); ?>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=ch-pseo-services' ) ); ?>"><?php esc_html_e( 'Manage Services', 'ch-pseo-pages-plugin' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ch-pseo-locations' ) ); ?>"><?php esc_html_e( 'Manage Locations', 'ch-pseo-pages-plugin' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ch-pseo-mappings' ) ); ?>"><?php esc_html_e( 'Manage Mappings', 'ch-pseo-pages-plugin' ); ?></a>
		</p>
	</div>

	<div class="ch-pseo-stat-grid">
		<?php foreach ( $cards as $key => $label ) : ?>
			<div class="ch-pseo-stat-card">
				<span class="ch-pseo-stat-value"><?php echo esc_html( number_format_i18n( $counts[ $key ] ) ); ?></span>
				<span class="ch-pseo-stat-label"><?php echo esc_html( $label ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="ch-pseo-panel">
		<h2><?php esc_html_e( 'PSEO Sitemap', 'ch-pseo-pages-plugin' ); ?></h2>
		<p>
			<a href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $sitemap_url ); ?>
			</a>
		</p>
		<p class="description">
			<?php esc_html_e( 'The sitemap contains eligible active mappings whose effective robots directive permits indexing and whose template page is published. Relevant changes clear its 12-hour cache automatically.', 'ch-pseo-pages-plugin' ); ?>
		</p>
		<p>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ch-pseo-sitemap' ) ); ?>"><?php esc_html_e( 'Sitemap Settings', 'ch-pseo-pages-plugin' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ch-pseo-tools' ) ); ?>"><?php esc_html_e( 'Cache and Export Tools', 'ch-pseo-pages-plugin' ); ?></a>
		</p>
	</div>
</div>
