<?php
/**
 * Autoloader for the SMS-connect plugin.
 *
 * @package SmsConnect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

spl_autoload_register( 'sms_connect_autoloader' );

/**
 * Dynamically loads the class attempting to be instantiated.
 *
 * @param string $class The class name.
 */
function sms_connect_autoloader( $class ) {
	// Project-specific namespace prefix.
	$prefix = 'SmsConnect\\';

	// Base directory for the namespace prefix.
	$base_dir = __DIR__ . '/';

	// Does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		// No, move to the next registered autoloader.
		return;
	}

	// Get the relative class name.
	$relative_class = substr( $class, $len );

	// Replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	// If the file exists, require it.
	if ( file_exists( $file ) ) {
		require $file;
	}
} 