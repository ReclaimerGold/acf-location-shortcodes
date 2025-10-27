<?php
/**
 * Plugin Name: ACF Location Shortcodes
 * Plugin URI: https://github.com/rreiffenberger/acf-location-shortcodes
 * Description: Provides shortcodes and Elementor integration for displaying and filtering location data from ACF custom post types.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Ryan Reiffenberger
 * Author URI: https://github.com/rreiffenberger
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acf-location-shortcodes
 * Domain Path: /languages
 *
 * @package ACF_Location_Shortcodes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'ACF_LS_VERSION', '1.0.0' );
define( 'ACF_LS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACF_LS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ACF_LS_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class ACF_Location_Shortcodes {

	/**
	 * Single instance of the class.
	 *
	 * @var ACF_Location_Shortcodes
	 */
	private static $instance = null;

	/**
	 * Shortcodes handler.
	 *
	 * @var ACF_Location_Shortcodes_Shortcodes
	 */
	public $shortcodes;

	/**
	 * ACF helpers.
	 *
	 * @var ACF_Location_Shortcodes_ACF_Helpers
	 */
	public $acf_helpers;

	/**
	 * Elementor integration.
	 *
	 * @var ACF_Location_Shortcodes_Elementor
	 */
	public $elementor;

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 * @return ACF_Location_Shortcodes
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'check_dependencies' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );
		
		// Admin notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Check if required dependencies are active.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function check_dependencies() {
		if ( ! function_exists( 'get_field' ) ) {
			add_action( 'admin_notices', array( $this, 'acf_missing_notice' ) );
			return false;
		}
		return true;
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Don't initialize if ACF is not active.
		if ( ! function_exists( 'get_field' ) ) {
			return;
		}

		// Load plugin files.
		$this->includes();

		// Initialize components.
		$this->acf_helpers = new ACF_Location_Shortcodes_ACF_Helpers();
		$this->shortcodes  = new ACF_Location_Shortcodes_Shortcodes( $this->acf_helpers );

		// Initialize Elementor integration if Elementor is active.
		if ( did_action( 'elementor/loaded' ) ) {
			$this->elementor = new ACF_Location_Shortcodes_Elementor( $this->acf_helpers );
		}

		// Enqueue assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Include required files.
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		require_once ACF_LS_PLUGIN_DIR . 'includes/class-acf-helpers.php';
		require_once ACF_LS_PLUGIN_DIR . 'includes/class-shortcodes.php';

		// Load Elementor integration only if Elementor is active.
		if ( did_action( 'elementor/loaded' ) ) {
			require_once ACF_LS_PLUGIN_DIR . 'includes/class-elementor-integration.php';
		}
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {
		// Enqueue CSS.
		wp_enqueue_style(
			'acf-ls-shortcodes',
			ACF_LS_PLUGIN_URL . 'assets/css/shortcodes.css',
			array(),
			ACF_LS_VERSION
		);
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'acf-location-shortcodes',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Display admin notices.
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {
		// This method is here for future notices if needed.
	}

	/**
	 * Display ACF missing notice.
	 *
	 * @since 1.0.0
	 */
	public function acf_missing_notice() {
		$class   = 'notice notice-error';
		$message = sprintf(
			/* translators: %s: ACF plugin link */
			__( 'ACF Location Shortcodes requires Advanced Custom Fields to be installed and active. %s', 'acf-location-shortcodes' ),
			'<a href="' . esc_url( admin_url( 'plugin-install.php?s=advanced+custom+fields&tab=search&type=term' ) ) . '">' . __( 'Install ACF now', 'acf-location-shortcodes' ) . '</a>'
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
	}
}

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 * @return ACF_Location_Shortcodes
 */
function acf_location_shortcodes() {
	return ACF_Location_Shortcodes::instance();
}

// Kick off the plugin.
acf_location_shortcodes();
