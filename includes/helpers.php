<?php
/**
 * Shared helper functions.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gets a plugin option with a default value.
 *
 * @param string $key     Option key without the plugin prefix.
 * @param mixed  $default Default value.
 * @return mixed
 */
function ch_pseo_get_option( $key, $default = false ) {
	$key = sanitize_key( $key );

	if ( '' === $key ) {
		return $default;
	}

	return get_option( 'ch_pseo_' . $key, $default );
}

/**
 * Normalizes a URL path segment for use in dynamic routes.
 *
 * @param string $value Raw path segment.
 * @return string
 */
function ch_pseo_sanitize_path_segment( $value ) {
	return sanitize_title( wp_unslash( $value ) );
}

/**
 * Normalizes a slash-separated URL path.
 *
 * @param string $path Raw URL path.
 * @return string
 */
function ch_pseo_normalize_url_path( $path ) {
	$segments = array_filter( explode( '/', trim( $path, '/' ) ) );
	$segments = array_map( 'sanitize_title', $segments );

	return implode( '/', array_filter( $segments ) );
}

/**
 * Builds the route root shared by one service's generated pages.
 *
 * @param string $url_base     Optional base prefix.
 * @param string $service_slug Service slug.
 * @return string
 */
function ch_pseo_get_service_route( $url_base, $service_slug ) {
	$parts = array_filter(
		array(
			ch_pseo_normalize_url_path( $url_base ),
			sanitize_title( $service_slug ),
		)
	);

	return implode( '/', $parts );
}

/**
 * Builds a generated service/location URL.
 *
 * @param string   $url_base     Optional base prefix.
 * @param string   $service_slug Service slug.
 * @param string[] $segments     Location slugs.
 * @return string
 */
function ch_pseo_get_generated_url( $url_base, $service_slug, $segments ) {
	$path = ch_pseo_get_service_route( $url_base, $service_slug );
	if ( $segments ) {
		$path .= '/' . implode( '/', array_map( 'sanitize_title', $segments ) );
	}

	return home_url( user_trailingslashit( $path ) );
}
