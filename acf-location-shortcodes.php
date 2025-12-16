<?php
/**
 * Plugin Name: ACF Service Management Suite
 * Plugin URI: https://github.com/ReclaimerGold/acf-location-shortcodes
 * Description: Complete service business management for WordPress. Manage locations, service areas, and team members with powerful shortcodes, Elementor integration, and pre-configured ACF post type structure. Perfect for multi-location businesses, service providers, and professional practices. Licensed under GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 2.4.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Ryan T. M. Reiffenberger
 * Author URI: https://github.com/ReclaimerGold
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acf-sms
 * Domain Path: /languages
 *
 * @package ACF_Service_Management_Suite
 * @author Ryan T. M. Reiffenberger
 * @copyright 2025 Ryan T. M. Reiffenberger
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'ACF_LS_VERSION', '2.4.0' );
define( 'ACF_LS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACF_LS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ACF_LS_PLUGIN_FILE', __FILE__ );

// Define debug mode (can be overridden in wp-config.php).
if ( ! defined( 'ACF_LS_DEBUG' ) ) {
	define( 'ACF_LS_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );
}

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
	 * Admin interface.
	 *
	 * @var ACF_Location_Shortcodes_Admin
	 */
	public $admin;

	/**
	 * Multisite sync.
	 *
	 * @var ACF_Location_Shortcodes_Multisite_Sync
	 */
	public $multisite_sync;

	/**
	 * Network admin.
	 *
	 * @var ACF_Location_Shortcodes_Network_Admin
	 */
	public $network_admin;

	/**
	 * Site settings.
	 *
	 * @var ACF_Location_Shortcodes_Site_Settings
	 */
	public $site_settings;

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
		// Load plugin files.
		$this->includes();

		// Initialize ACF helpers (works without ACF).
		$this->acf_helpers = new ACF_Location_Shortcodes_ACF_Helpers();

		// Initialize site settings (always available).
		$this->site_settings = new ACF_Location_Shortcodes_Site_Settings();

		// Initialize admin interface (always available).
		if ( is_admin() ) {
			$this->admin = new ACF_Location_Shortcodes_Admin( $this->acf_helpers );
		}

		// Initialize network admin (if multisite).
		if ( is_multisite() ) {
			$this->network_admin = new ACF_Location_Shortcodes_Network_Admin();
		}

		// Initialize multisite sync (if multisite and ACF active).
		if ( is_multisite() && function_exists( 'get_field' ) ) {
			$this->multisite_sync = new ACF_Location_Shortcodes_Multisite_Sync( $this->acf_helpers );
		}

		// Don't initialize frontend features if ACF is not active.
		if ( ! function_exists( 'get_field' ) ) {
			return;
		}

		// Initialize frontend components.
		$this->shortcodes = new ACF_Location_Shortcodes_Shortcodes( $this->acf_helpers, $this->site_settings );

		// Initialize Elementor integration if Elementor is active.
		if ( did_action( 'elementor/loaded' ) ) {
			$this->elementor = new ACF_Location_Shortcodes_Elementor( $this->acf_helpers, $this->site_settings );
		}

		// Fix Elementor Pro Display Conditions compatibility.
		// This must be registered even if Elementor integration class isn't loaded.
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			add_filter( 'acf/format_value', array( $this, 'fix_elementor_display_conditions' ), 999, 3 );
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
		require_once ACF_LS_PLUGIN_DIR . 'includes/class-site-settings.php';

		// Load admin interface if in admin.
		if ( is_admin() ) {
			require_once ACF_LS_PLUGIN_DIR . 'includes/class-admin.php';
		}

		// Load network admin if multisite.
		if ( is_multisite() ) {
			require_once ACF_LS_PLUGIN_DIR . 'includes/class-network-admin.php';
		}

		// Load multisite sync if multisite.
		if ( is_multisite() && function_exists( 'get_field' ) ) {
			require_once ACF_LS_PLUGIN_DIR . 'includes/class-multisite-sync.php';
		}

		// Load frontend components if ACF is active.
		if ( function_exists( 'get_field' ) ) {
			require_once ACF_LS_PLUGIN_DIR . 'includes/class-shortcodes.php';

			// Load Elementor integration only if Elementor is active.
			if ( did_action( 'elementor/loaded' ) ) {
				require_once ACF_LS_PLUGIN_DIR . 'includes/class-elementor-integration.php';
			}
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
			'acf-sms',
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
			__( 'ACF Service Management Suite requires Advanced Custom Fields to be installed and active. %s', 'acf-sms' ),
			'<a href="' . esc_url( admin_url( 'plugin-install.php?s=advanced+custom+fields&tab=search&type=term' ) ) . '">' . __( 'Install ACF now', 'acf-sms' ) . '</a>'
		);

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
	}

	/**
	 * Log debug messages.
	 *
	 * @since 1.1.0
	 * @param string $message Log message.
	 * @param array  $data    Optional. Additional data to log.
	 * @param string $level   Optional. Log level: 'info', 'warning', 'error'. Default 'info'.
	 */
	public static function log( $message, $data = array(), $level = 'info' ) {
		// Only log when debug mode is enabled.
		if ( ! defined( 'ACF_LS_DEBUG' ) || ! ACF_LS_DEBUG ) {
			return;
		}

		$log_message = sprintf(
			'[ACF Service Management Suite][%s] %s',
			strtoupper( $level ),
			$message
		);

		if ( ! empty( $data ) ) {
			$log_message .= ' | Data: ' . wp_json_encode( $data );
		}

		error_log( $log_message );
	}

	/**
	 * Fix Elementor Pro Display Conditions compatibility.
	 *
	 * Elementor's Display Conditions expects strings, but ACF relationship/post_object
	 * fields return arrays. This filter converts arrays to comma-separated strings
	 * when Elementor Pro's display conditions module is evaluating a dynamic tag.
	 *
	 * @since 2.3.0
	 * @param mixed  $value   The field value.
	 * @param int    $post_id The post ID.
	 * @param array  $field   The field array.
	 * @return mixed The converted value.
	 */
	public function fix_elementor_display_conditions( $value, $post_id, $field ) {
		// Only process on frontend.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $value;
		}

		// Get field type.
		$field_type = isset( $field['type'] ) ? $field['type'] : '';

		// Only process relationship and post_object fields.
		if ( ! in_array( $field_type, array( 'relationship', 'post_object' ), true ) ) {
			return $value;
		}

		// Check if we're being called from Elementor's display conditions.
		// Use backtrace to detect Elementor context.
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 20 );
		$is_elementor_display_conditions = false;

		foreach ( $backtrace as $trace ) {
			if ( isset( $trace['class'] ) ) {
				// Check for Elementor Pro Display Conditions classes.
				if ( strpos( $trace['class'], 'ElementorPro\\Modules\\DisplayConditions' ) !== false ) {
					$is_elementor_display_conditions = true;
					break;
				}
				// Also check for Dynamic Tags in Elementor.
				if ( strpos( $trace['class'], 'Elementor\\Core\\DynamicTags' ) !== false ) {
					$is_elementor_display_conditions = true;
					break;
				}
			}
		}

		// Only convert if in Elementor display conditions context.
		if ( ! $is_elementor_display_conditions ) {
			return $value;
		}

		// Handle empty values.
		if ( empty( $value ) || $value === false || $value === null ) {
			return '';
		}

		// Handle arrays (multiple selections).
		if ( is_array( $value ) ) {
			$titles = array();
			foreach ( $value as $item ) {
				$title = $this->extract_acf_post_title( $item );
				if ( ! empty( $title ) ) {
					$titles[] = $title;
				}
			}
			return ! empty( $titles ) ? implode( ', ', $titles ) : '';
		}

		// Handle single post object.
		if ( is_object( $value ) ) {
			$title = $this->extract_acf_post_title( $value );
			return ! empty( $title ) ? $title : '';
		}

		// Return original value if it's already a string.
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Extract post title from ACF field value.
	 *
	 * @since 2.3.0
	 * @param mixed $item The item (post object, array, or ID).
	 * @return string The post title or empty string.
	 */
	private function extract_acf_post_title( $item ) {
		// Handle post object.
		if ( is_object( $item ) && isset( $item->post_title ) ) {
			return (string) $item->post_title;
		}

		// Handle array format.
		if ( is_array( $item ) && isset( $item['post_title'] ) ) {
			return (string) $item['post_title'];
		}

		// Handle ID.
		if ( is_numeric( $item ) ) {
			$post = get_post( absint( $item ) );
			if ( $post && isset( $post->post_title ) ) {
				return (string) $post->post_title;
			}
		}

		return '';
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
