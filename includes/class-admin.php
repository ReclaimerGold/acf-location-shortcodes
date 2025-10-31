<?php
/**
 * Admin Interface
 *
 * Provides admin menu, ACF template management, and system checks.
 *
 * @package ACF_Location_Shortcodes
 * @since 2.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 *
 * @since 2.2.0
 */
class ACF_Location_Shortcodes_Admin {

	/**
	 * ACF helpers instance.
	 *
	 * @var ACF_Location_Shortcodes_ACF_Helpers
	 */
	private $acf_helpers;

	/**
	 * Template file path.
	 *
	 * @var string
	 */
	private $template_file;

	/**
	 * Current active tab.
	 *
	 * @var string
	 */
	private $active_tab;

	/**
	 * Constructor.
	 *
	 * @since 2.2.0
	 * @param ACF_Location_Shortcodes_ACF_Helpers $acf_helpers ACF helpers instance.
	 */
	public function __construct( $acf_helpers ) {
		$this->acf_helpers   = $acf_helpers;
		$this->template_file = ACF_LS_PLUGIN_DIR . 'acf-import-templates/acf-template.json';
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.2.0
	 */
	private function init_hooks() {
		// Admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'show_acf_notices' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_acf_sms_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );
		add_action( 'wp_ajax_acf_sms_download_template', array( $this, 'ajax_download_template' ) );

		// Admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add admin menu.
	 *
	 * @since 2.2.0
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'ACF Service Management Suite', 'acf-sms' ),
			__( 'ACF SMS', 'acf-sms' ),
			'manage_options',
			'acf-sms',
			array( $this, 'render_admin_page' ),
			'dashicons-location-alt',
			30
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 2.2.0
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our admin page.
		if ( 'toplevel_page_acf-sms' !== $hook ) {
			return;
		}

		// Enqueue admin CSS.
		wp_enqueue_style(
			'acf-sms-admin',
			ACF_LS_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ACF_LS_VERSION
		);

		// Enqueue admin JS.
		wp_enqueue_script(
			'acf-sms-admin',
			ACF_LS_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			ACF_LS_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'acf-sms-admin',
			'acfSmsAdmin',
			array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'acf_sms_admin' ),
				'downloading'  => __( 'Downloading...', 'acf-sms' ),
				'downloadUrl'  => admin_url( 'admin-ajax.php?action=acf_sms_download_template&nonce=' . wp_create_nonce( 'acf_sms_download' ) ),
			)
		);
	}

	/**
	 * Render admin page.
	 *
	 * @since 2.2.0
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'acf-sms' ) );
		}

		// Get active tab.
		$this->active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';

		?>
		<div class="wrap acf-sms-admin">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a href="?page=acf-sms&tab=dashboard" class="nav-tab <?php echo 'dashboard' === $this->active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Dashboard', 'acf-sms' ); ?>
				</a>
				<a href="?page=acf-sms&tab=readme" class="nav-tab <?php echo 'readme' === $this->active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'README', 'acf-sms' ); ?>
				</a>
			</nav>

			<div class="acf-sms-tab-content">
				<?php
				switch ( $this->active_tab ) {
					case 'readme':
						$this->render_readme_tab();
						break;
					case 'dashboard':
					default:
						$this->render_dashboard_tab();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render dashboard tab.
	 *
	 * @since 2.2.0
	 */
	private function render_dashboard_tab() {
		$acf_active     = $this->is_acf_active();
		$template_match = $this->check_template_match();

		?>
		<div class="acf-sms-dashboard">
			<div class="acf-sms-cards">
				
				<!-- ACF Status Card -->
				<div class="acf-sms-card">
					<h2><?php esc_html_e( 'ACF Plugin Status', 'acf-sms' ); ?></h2>
					<?php if ( $acf_active ) : ?>
						<p class="acf-sms-status acf-sms-status-success">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php esc_html_e( 'Advanced Custom Fields is installed and active.', 'acf-sms' ); ?>
						</p>
					<?php else : ?>
						<p class="acf-sms-status acf-sms-status-error">
							<span class="dashicons dashicons-warning"></span>
							<?php esc_html_e( 'Advanced Custom Fields is not installed or not active.', 'acf-sms' ); ?>
						</p>
						<p>
							<a href="<?php echo esc_url( $this->get_acf_install_url() ); ?>" class="button button-primary">
								<?php esc_html_e( 'Install ACF Now', 'acf-sms' ); ?>
							</a>
						</p>
					<?php endif; ?>
				</div>

				<!-- Template Download Card -->
				<div class="acf-sms-card">
					<h2><?php esc_html_e( 'ACF Template', 'acf-sms' ); ?></h2>
					<p><?php esc_html_e( 'Download the pre-configured ACF field groups for locations and team members.', 'acf-sms' ); ?></p>
					
					<?php if ( file_exists( $this->template_file ) ) : ?>
						<p>
							<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=acf_sms_download_template&nonce=' . wp_create_nonce( 'acf_sms_download' ) ) ); ?>" 
							   class="button button-primary acf-sms-download-btn" 
							   download>
								<span class="dashicons dashicons-download"></span>
								<?php esc_html_e( 'Download ACF Template', 'acf-sms' ); ?>
							</a>
						</p>
						
						<?php if ( $acf_active ) : ?>
							<p class="description">
								<?php
								printf(
									/* translators: %s: ACF Tools URL */
									__( 'After downloading, go to <a href="%s">ACF → Tools → Import Field Groups</a> to import the template.', 'acf-sms' ),
									esc_url( admin_url( 'edit.php?post_type=acf-field-group&page=acf-tools' ) )
								);
								?>
							</p>
						<?php endif; ?>

						<?php if ( $acf_active && ! $template_match ) : ?>
							<div class="acf-sms-notice acf-sms-notice-warning">
								<p>
									<span class="dashicons dashicons-info"></span>
									<?php esc_html_e( 'The installed ACF field groups may not match the latest template. Consider re-importing to get the latest fields.', 'acf-sms' ); ?>
								</p>
							</div>
						<?php elseif ( $acf_active && $template_match ) : ?>
							<p class="acf-sms-status acf-sms-status-success">
								<span class="dashicons dashicons-yes-alt"></span>
								<?php esc_html_e( 'Your ACF field groups appear to be up to date.', 'acf-sms' ); ?>
							</p>
						<?php endif; ?>
					<?php else : ?>
						<p class="acf-sms-status acf-sms-status-error">
							<span class="dashicons dashicons-warning"></span>
							<?php esc_html_e( 'Template file not found. Please reinstall the plugin.', 'acf-sms' ); ?>
						</p>
					<?php endif; ?>
				</div>

				<!-- Quick Links Card -->
				<div class="acf-sms-card">
					<h2><?php esc_html_e( 'Quick Links', 'acf-sms' ); ?></h2>
					<ul class="acf-sms-links">
						<li>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=location' ) ); ?>">
								<span class="dashicons dashicons-location-alt"></span>
								<?php esc_html_e( 'Manage Locations', 'acf-sms' ); ?>
							</a>
						</li>
						<li>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=team-member' ) ); ?>">
								<span class="dashicons dashicons-admin-users"></span>
								<?php esc_html_e( 'Manage Team Members', 'acf-sms' ); ?>
							</a>
						</li>
						<?php if ( $acf_active ) : ?>
							<li>
								<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=acf-field-group' ) ); ?>">
									<span class="dashicons dashicons-admin-generic"></span>
									<?php esc_html_e( 'ACF Field Groups', 'acf-sms' ); ?>
								</a>
							</li>
						<?php endif; ?>
						<li>
							<a href="https://github.com/ReclaimerGold/acf-location-shortcodes" target="_blank" rel="noopener noreferrer">
								<span class="dashicons dashicons-admin-links"></span>
								<?php esc_html_e( 'GitHub Repository', 'acf-sms' ); ?>
							</a>
						</li>
					</ul>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Render README tab.
	 *
	 * @since 2.2.0
	 */
	private function render_readme_tab() {
		$readme_file = ACF_LS_PLUGIN_DIR . 'README.md';

		if ( ! file_exists( $readme_file ) ) {
			echo '<p>' . esc_html__( 'README.md file not found.', 'acf-sms' ) . '</p>';
			return;
		}

		$readme_content = file_get_contents( $readme_file );

		// Basic markdown to HTML conversion.
		$html = $this->convert_markdown_to_html( $readme_content );

		?>
		<div class="acf-sms-readme">
			<?php echo wp_kses_post( $html ); ?>
		</div>
		<?php
	}

	/**
	 * Convert markdown to HTML (basic implementation).
	 *
	 * @since 2.2.0
	 * @param string $markdown Markdown content.
	 * @return string HTML content.
	 */
	private function convert_markdown_to_html( $markdown ) {
		// Remove badge images (they don't work well in admin).
		$markdown = preg_replace( '/\[!\[.*?\]\(.*?\)\]\(.*?\)/', '', $markdown );

		// Headers.
		$markdown = preg_replace( '/^### (.*?)$/m', '<h3>$1</h3>', $markdown );
		$markdown = preg_replace( '/^## (.*?)$/m', '<h2>$1</h2>', $markdown );
		$markdown = preg_replace( '/^# (.*?)$/m', '<h1>$1</h1>', $markdown );

		// Bold.
		$markdown = preg_replace( '/\*\*(.*?)\*\*/', '<strong>$1</strong>', $markdown );

		// Italic.
		$markdown = preg_replace( '/\*(.*?)\*/', '<em>$1</em>', $markdown );

		// Code blocks.
		$markdown = preg_replace( '/```(.*?)```/s', '<pre><code>$1</code></pre>', $markdown );

		// Inline code.
		$markdown = preg_replace( '/`(.*?)`/', '<code>$1</code>', $markdown );

		// Links.
		$markdown = preg_replace( '/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" target="_blank">$1</a>', $markdown );

		// Lists.
		$markdown = preg_replace( '/^\* (.*)$/m', '<li>$1</li>', $markdown );
		$markdown = preg_replace( '/(<li>.*<\/li>\n?)+/', '<ul>$0</ul>', $markdown );

		// Paragraphs.
		$lines     = explode( "\n", $markdown );
		$in_list   = false;
		$in_pre    = false;
		$processed = array();

		foreach ( $lines as $line ) {
			$trimmed = trim( $line );

			if ( strpos( $trimmed, '<pre>' ) !== false ) {
				$in_pre = true;
			}
			if ( strpos( $trimmed, '</pre>' ) !== false ) {
				$in_pre = false;
			}

			if ( strpos( $trimmed, '<ul>' ) !== false ) {
				$in_list = true;
			}
			if ( strpos( $trimmed, '</ul>' ) !== false ) {
				$in_list = false;
			}

			if ( ! empty( $trimmed ) && ! $in_list && ! $in_pre && strpos( $trimmed, '<h' ) !== 0 && strpos( $trimmed, '<ul>' ) === false ) {
				$processed[] = '<p>' . $line . '</p>';
			} else {
				$processed[] = $line;
			}
		}

		return implode( "\n", $processed );
	}

	/**
	 * Show ACF-related admin notices.
	 *
	 * @since 2.2.0
	 */
	public function show_acf_notices() {
		// Don't show on our own admin page.
		if ( isset( $_GET['page'] ) && 'acf-sms' === $_GET['page'] ) {
			return;
		}

		// Check if notice was dismissed.
		if ( get_user_meta( get_current_user_id(), 'acf_sms_dismiss_acf_notice', true ) ) {
			return;
		}

		// Check if ACF is active.
		if ( $this->is_acf_active() ) {
			// Check template match.
			if ( ! $this->check_template_match() ) {
				$this->show_template_mismatch_notice();
			}
		} else {
			$this->show_acf_missing_notice();
		}
	}

	/**
	 * Show ACF missing notice.
	 *
	 * @since 2.2.0
	 */
	private function show_acf_missing_notice() {
		?>
		<div class="notice notice-error is-dismissible acf-sms-notice" data-notice="acf_missing">
			<p>
				<strong><?php esc_html_e( 'ACF Service Management Suite:', 'acf-sms' ); ?></strong>
				<?php esc_html_e( 'Advanced Custom Fields plugin is required but not installed or activated.', 'acf-sms' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $this->get_acf_install_url() ); ?>" class="button button-primary">
					<?php esc_html_e( 'Install ACF Now', 'acf-sms' ); ?>
				</a>
				<button type="button" class="button acf-sms-dismiss-notice" data-nonce="<?php echo esc_attr( wp_create_nonce( 'acf_sms_dismiss' ) ); ?>">
					<?php esc_html_e( 'Dismiss', 'acf-sms' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Show template mismatch notice.
	 *
	 * @since 2.2.0
	 */
	private function show_template_mismatch_notice() {
		?>
		<div class="notice notice-warning is-dismissible acf-sms-notice" data-notice="template_mismatch">
			<p>
				<strong><?php esc_html_e( 'ACF Service Management Suite:', 'acf-sms' ); ?></strong>
				<?php esc_html_e( 'Your ACF field groups may not match the latest template. Some features may not work correctly.', 'acf-sms' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=acf-sms' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Download Latest Template', 'acf-sms' ); ?>
				</a>
				<button type="button" class="button acf-sms-dismiss-notice" data-nonce="<?php echo esc_attr( wp_create_nonce( 'acf_sms_dismiss' ) ); ?>">
					<?php esc_html_e( 'Dismiss', 'acf-sms' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * AJAX handler for dismissing notices.
	 *
	 * @since 2.2.0
	 */
	public function ajax_dismiss_notice() {
		check_ajax_referer( 'acf_sms_dismiss', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		update_user_meta( get_current_user_id(), 'acf_sms_dismiss_acf_notice', true );

		wp_send_json_success();
	}

	/**
	 * AJAX handler for downloading template.
	 *
	 * @since 2.2.0
	 */
	public function ajax_download_template() {
		check_ajax_referer( 'acf_sms_download', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'acf-sms' ) );
		}

		if ( ! file_exists( $this->template_file ) ) {
			wp_die( esc_html__( 'Template file not found', 'acf-sms' ) );
		}

		// Set headers for download.
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="acf-template-' . ACF_LS_VERSION . '.json"' );
		header( 'Content-Length: ' . filesize( $this->template_file ) );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );

		// Output file.
		readfile( $this->template_file );
		exit;
	}

	/**
	 * Check if ACF is active.
	 *
	 * @since 2.2.0
	 * @return bool True if ACF is active.
	 */
	private function is_acf_active() {
		return class_exists( 'ACF' ) || function_exists( 'acf' );
	}

	/**
	 * Check if installed ACF templates match plugin template.
	 *
	 * @since 2.2.0
	 * @return bool True if templates match.
	 */
	private function check_template_match() {
		if ( ! $this->is_acf_active() ) {
			return false;
		}

		// Get expected field groups from template.
		if ( ! file_exists( $this->template_file ) ) {
			return false;
		}

		$template_data = json_decode( file_get_contents( $this->template_file ), true );
		if ( ! $template_data ) {
			return false;
		}

		// Check for key field groups.
		$required_groups = array(
			'group_68f6b00f02090', // Location Details
			'group_68f68537a1e89', // Team Member Details
		);

		foreach ( $required_groups as $group_key ) {
			$group = acf_get_field_group( $group_key );
			if ( ! $group ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get URL to install ACF plugin.
	 *
	 * @since 2.2.0
	 * @return string Install URL.
	 */
	private function get_acf_install_url() {
		return admin_url( 'plugin-install.php?s=advanced+custom+fields&tab=search&type=term' );
	}
}
