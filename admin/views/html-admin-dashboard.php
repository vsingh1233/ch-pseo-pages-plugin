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
			<?php esc_html_e( 'The sitemap endpoint will begin returning dynamic URLs when sitemap generation is implemented.', 'ch-pseo-pages-plugin' ); ?>
		</p>
	</div>
</div>

