<?php
/*
 * Plugin Name: BP REST API
 * Plugin URI:  https://buddypress.org
 * Description: BuddyPress integration with the WP REST API.
 * Author:      The BuddyPress Community
 * Author URI:  https://buddypress.org/
 * Version:     0.1-alpha
 * License:     GPLv2 or later (license.txt)
 */

defined( 'ABSPATH' ) or die();

// Autoloader.
add_action( 'bp_include', function() {
	// Bail if not on the root blog.
	if ( ! bp_is_root_blog() ) {
		return;
	}

	spl_autoload_register( function( $class ) {
		$prefix = 'BP_REST\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		// Get the relative class name.
		$relative_class = substr( $class, strlen( $prefix ) );

		// Check if component is active. If not, bail.
		$parts = explode( '\\', $relative_class );

		if ( isset( $parts[1] ) && ! bp_is_active( strtolower( $parts[0] ) ) ) {
			return;
		}

		$base_dir = dirname( __FILE__ ) . '/classes/';
		$file = $base_dir . str_replace( '\\', '/', $relative_class . '.php' );
		if ( file_exists( $file ) ) {
			require $file;
		}
	} );
} );

// Time to REST!
add_action( 'bp_rest_api_init', function() {
	// Bail if not on the root blog.
	if ( ! bp_is_root_blog() ) {
		return;
	}

	foreach ( bp_core_get_packaged_component_ids() as $component ) {
		// 'xprofile' needs to use the correct letter case for the class name.
		if ( 'xprofile' === $component ) {
			$component = 'XProfile';
		} else {
			$component = ucfirst( $component );
		}

		// Components must have a Config class.
		if ( ! class_exists( "BP_REST\\{$component}\\Config" ) ) {
			continue;
		}

		// Get REST config for component.
		$config = "BP_REST\\{$component}\\Config";

		// Register REST fields.
		$config::register_rest_fields();

		// Register meta fields.
		$config::register_meta();

		// Load each component's controller depending on the supported API version.
		foreach ( (array) $config::get_versions() as $v ) {
			$controller = "BP_REST\\{$component}\\{$v}\\Controller";
			$controller::init();
		}
	}
} );