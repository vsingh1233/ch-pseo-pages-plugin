<?php
/**
 * PHPUnit bootstrap for isolated plugin unit tests.
 *
 * @package CH_PSEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'CH_PSEO_VERSION' ) ) {
	define( 'CH_PSEO_VERSION', '0.1.0' );
}

if ( ! defined( 'CH_PSEO_DB_VERSION' ) ) {
	define( 'CH_PSEO_DB_VERSION', '0.4.0' );
}

$GLOBALS['ch_pseo_test_options'] = array();
$GLOBALS['ch_pseo_test_transients'] = array();

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Test replacement for wp_unslash().
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		return $value;
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	/**
	 * Test replacement for sanitize_title().
	 *
	 * @param string $value Value.
	 * @return string
	 */
	function sanitize_title( $value ) {
		$value = strtolower( trim( (string) $value ) );
		$value = preg_replace( '/[^a-z0-9]+/', '-', $value );
		return trim( $value, '-' );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Test replacement for get_option().
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function get_option( $option, $default = false ) {
		return array_key_exists( $option, $GLOBALS['ch_pseo_test_options'] )
			? $GLOBALS['ch_pseo_test_options'][ $option ]
			: $default;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	/**
	 * Test replacement for add_option().
	 *
	 * @param string $option     Option name.
	 * @param mixed  $value      Option value.
	 * @param string $deprecated Deprecated parameter.
	 * @param bool   $autoload   Autoload flag.
	 * @return bool
	 */
	function add_option( $option, $value = '', $deprecated = '', $autoload = true ) {
		unset( $deprecated, $autoload );

		if ( array_key_exists( $option, $GLOBALS['ch_pseo_test_options'] ) ) {
			return false;
		}

		$GLOBALS['ch_pseo_test_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Test replacement for update_option().
	 *
	 * @param string    $option   Option name.
	 * @param mixed     $value    Option value.
	 * @param bool|null $autoload Autoload flag.
	 * @return bool
	 */
	function update_option( $option, $value, $autoload = null ) {
		unset( $autoload );
		$GLOBALS['ch_pseo_test_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * Test replacement for delete_option().
	 *
	 * @param string $option Option name.
	 * @return bool
	 */
	function delete_option( $option ) {
		if ( ! array_key_exists( $option, $GLOBALS['ch_pseo_test_options'] ) ) {
			return false;
		}

		unset( $GLOBALS['ch_pseo_test_options'][ $option ] );
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	/**
	 * Test replacement for delete_transient().
	 *
	 * @param string $key Transient key.
	 * @return bool
	 */
	function delete_transient( $key ) {
		unset( $GLOBALS['ch_pseo_test_transients'][ $key ] );
		return true;
	}
}

require_once dirname( __DIR__ ) . '/includes/helpers.php';
require_once dirname( __DIR__ ) . '/includes/class-ch-pseo-database.php';
require_once dirname( __DIR__ ) . '/includes/class-ch-pseo-migrator.php';
require_once dirname( __DIR__ ) . '/includes/class-ch-pseo-context.php';
require_once dirname( __DIR__ ) . '/includes/class-ch-pseo-importer.php';
require_once __DIR__ . '/support/class-ch-pseo-test-database.php';
