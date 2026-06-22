<?php
/**
 * Recursively checks project PHP files for syntax errors.
 *
 * @package CH_PSEO
 */

$root      = dirname( __DIR__ );
$iterator  = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
);
$has_error = false;

foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}

	$path = $file->getPathname();
	if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}

	$command = escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $path );
	exec( $command, $output, $exit_code );

	if ( 0 !== $exit_code ) {
		$has_error = true;
		echo implode( PHP_EOL, $output ) . PHP_EOL;
	}

	$output = array();
}

if ( $has_error ) {
	exit( 1 );
}

echo "All project PHP files passed syntax checks." . PHP_EOL;

