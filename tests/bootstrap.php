<?php
/**
 * PHPUnit Bootstrap File
 *
 * Sets up the testing environment for ACF Service Management Suite.
 *
 * @package ACF_Service_Management_Suite
 */

// Define test constants.
define( 'ACF_LS_TESTS', true );
define( 'ACF_LS_DEBUG', false );

// Load Composer autoloader.
if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __DIR__ ) . '/vendor/autoload.php';
}

// Load Brain Monkey for WordPress function mocking.
if ( class_exists( 'Brain\Monkey' ) ) {
	require_once __DIR__ . '/mocks/wordpress-functions.php';
}

// Define WordPress constants if not running in WordPress environment.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

// Define plugin constants.
define( 'ACF_LS_VERSION', '2.2.0' );
define( 'ACF_LS_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'ACF_LS_PLUGIN_URL', 'http://example.com/wp-content/plugins/acf-service-management-suite/' );
define( 'ACF_LS_PLUGIN_FILE', dirname( __DIR__ ) . '/acf-location-shortcodes.php' );

// Load test helpers.
require_once __DIR__ . '/TestCase.php';

echo "ACF Service Management Suite Test Suite Bootstrap Complete\n";
echo "Plugin Version: " . ACF_LS_VERSION . "\n";
echo "Test Mode: Enabled\n\n";
