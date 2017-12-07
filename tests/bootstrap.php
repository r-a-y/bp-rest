<?php
/**
 * PHPUnit bootstrap file
 *
 * @package BP_REST
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! defined( 'BP_TESTS_DIR' ) ) {
	// BP 2.1 and higher
	if ( file_exists( realpath( dirname( __FILE__ ) . '/../../buddypress/tests/phpunit' ) ) ) {
		define( 'BP_TESTS_DIR', dirname( __FILE__ ) . '/../../buddypress/tests/phpunit' );
	}
}

if ( ! defined( 'BP_TESTS_DIR' ) || ! file_exists( BP_TESTS_DIR . '/bootstrap.php' ) ) {
	return;
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Make sure BP is installed and loaded first.
	require BP_TESTS_DIR . '/includes/loader.php';

	require dirname( dirname( __FILE__ ) ) . '/bp-rest.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Load the BP test files.
require BP_TESTS_DIR . '/includes/testcase.php';
