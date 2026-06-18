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

