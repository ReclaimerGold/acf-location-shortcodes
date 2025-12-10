<?php
/**
 * Network Admin Settings
 *
 * Provides network-wide configuration for multisite sync.
 *
 * @package ACF_Location_Shortcodes
 * @since 2.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Network Admin class.
 *
 * @since 2.2.0
 */
class ACF_Location_Shortcodes_Network_Admin {

	/**
	 * Active tab.
	 *
	 * @var string
	 */
	private $active_tab;

	/**
	 * Constructor.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.2.0
	 */
	private function init_hooks() {
		// Only run on multisite.
		if ( ! is_multisite() ) {
			return;
		}

		// Network admin menu.
		add_action( 'network_admin_menu', array( $this, 'add_network_menu' ) );

		// Save settings - use network_admin_edit action for network admin forms.
		add_action( 'network_admin_edit_acf_sms_save_settings', array( $this, 'save_settings' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_acf_sms_network_sync_all', array( $this, 'ajax_sync_all' ) );
	}

	/**
	 * Add network admin menu.
	 *
	 * @since 2.2.0
	 */
	public function add_network_menu() {
		add_menu_page(
			__( 'ACF SMS Network', 'acf-sms' ),
			__( 'ACF SMS', 'acf-sms' ),
			'manage_network_options',
			'acf-sms-network',
			array( $this, 'render_network_page' ),
			'dashicons-location-alt',
			30
		);
	}

	/**
	 * Render network admin page.
	 *
	 * @since 2.2.0
	 */
	public function render_network_page() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'acf-sms' ) );
		}

		// Get active tab.
		$this->active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'sync';

		?>
		<div class="wrap acf-sms-network">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Network settings saved successfully.', 'acf-sms' ); ?></p>
				</div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper">
				<a href="?page=acf-sms-network&tab=sync" class="nav-tab <?php echo 'sync' === $this->active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Sync Settings', 'acf-sms' ); ?>
				</a>
				<a href="?page=acf-sms-network&tab=status" class="nav-tab <?php echo 'status' === $this->active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Site Status', 'acf-sms' ); ?>
				</a>
			</nav>

			<div class="acf-sms-tab-content">
				<?php
				switch ( $this->active_tab ) {
					case 'status':
						$this->render_status_tab();
						break;
					case 'sync':
					default:
						$this->render_sync_tab();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render sync settings tab.
	 *
	 * @since 2.2.0
	 */
	private function render_sync_tab() {
		$sync_enabled = get_site_option( 'acf_sms_sync_enabled', false );
		$sync_sites   = get_site_option( 'acf_sms_sync_sites', array() );
		$master_site  = get_site_option( 'acf_sms_master_site', get_main_site_id() );
		$all_sites    = get_sites( array( 'number' => 1000 ) );

		// Debug logging.
		if ( defined( 'ACF_LS_DEBUG' ) && ACF_LS_DEBUG ) {
			error_log( 'ACF SMS: Rendering sync settings tab' );
			error_log( 'ACF SMS: sync_enabled = ' . ( $sync_enabled ? 'true' : 'false' ) );
			error_log( 'ACF SMS: sync_sites = ' . print_r( $sync_sites, true ) );
			error_log( 'ACF SMS: master_site = ' . $master_site );
		}

		?>
		<form method="post" action="edit.php?action=acf_sms_save_settings">
			<?php wp_nonce_field( 'acf_sms_network_settings', 'acf_sms_network_nonce' ); ?>

			<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
				<div class="notice notice-info inline">
					<p><strong>Debug Info:</strong></p>
					<p>Sync Enabled: <code><?php echo $sync_enabled ? 'true' : 'false'; ?></code></p>
					<p>Master Site: <code><?php echo $master_site; ?></code></p>
					<p>Sync Sites Array: <code><?php echo esc_html( print_r( $sync_sites, true ) ); ?></code></p>
					<p>Sync Sites Count: <code><?php echo count( $sync_sites ); ?></code></p>
					<p>Sync Sites Types: <code><?php 
						foreach ( $sync_sites as $id ) {
							echo $id . ' (' . gettype( $id ) . '), ';
						}
					?></code></p>
					<p>First Site Blog ID: <code><?php 
						if ( ! empty( $all_sites ) ) {
							echo $all_sites[0]->blog_id . ' (' . gettype( $all_sites[0]->blog_id ) . ')';
						}
					?></code></p>
				</div>
			<?php endif; ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="acf_sms_master_site"><?php esc_html_e( 'Master Site', 'acf-sms' ); ?></label>
					</th>
					<td>
						<select id="acf_sms_master_site" name="acf_sms_master_site" class="regular-text">
							<?php foreach ( $all_sites as $site ) : ?>
								<?php
								$site_details = get_blog_details( $site->blog_id );
								$blog_id      = intval( $site->blog_id );
								?>
								<option value="<?php echo esc_attr( $blog_id ); ?>" <?php selected( $master_site, $blog_id ); ?>>
									<?php echo esc_html( $site_details->blogname ); ?> (<?php echo esc_html( $site_details->siteurl ); ?>)
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'The master site is the central database. Changes here automatically sync to slave sites. Slave sites can manually push updates to the master.', 'acf-sms' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="acf_sms_sync_enabled"><?php esc_html_e( 'Enable Multisite Sync', 'acf-sms' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" 
								   id="acf_sms_sync_enabled" 
								   name="acf_sms_sync_enabled" 
								   value="1" 
								   <?php checked( $sync_enabled, true ); ?> />
							<?php esc_html_e( 'Automatically sync locations and team members across network sites', 'acf-sms' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'When enabled, creating or updating locations and team members on any site will sync to selected sites below.', 'acf-sms' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<?php esc_html_e( 'Sync to Sites', 'acf-sms' ); ?>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php esc_html_e( 'Select sites to sync to', 'acf-sms' ); ?></span>
							</legend>

							<label>
								<input type="checkbox" id="acf_sms_sync_all_sites" class="acf-sms-sync-all-toggle" />
								<strong><?php esc_html_e( 'All Sites', 'acf-sms' ); ?></strong>
							</label>
							<br /><br />


						<?php foreach ( $all_sites as $site ) : ?>
							<?php
							$site_details = get_blog_details( $site->blog_id );
							// Convert blog_id to integer for proper comparison
							$blog_id      = intval( $site->blog_id );
							$checked      = in_array( $blog_id, $sync_sites, true );
							
							// Debug logging
							if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								error_log( sprintf( 
									'ACF SMS: Site %d (%s) - in_array check: %s, sync_sites: %s',
									$blog_id,
									$site_details->blogname,
									$checked ? 'true' : 'false',
									print_r( $sync_sites, true )
								) );
							}
							?>
							<label style="display: block; margin-bottom: 8px;">
								<input type="checkbox" 
									   name="acf_sms_sync_sites[]" 
									   value="<?php echo esc_attr( $blog_id ); ?>"
									   class="acf-sms-site-checkbox"
									   <?php checked( $checked, true ); ?> />
								<strong><?php echo esc_html( $site_details->blogname ); ?></strong>
								<span class="description">(<?php echo esc_html( $site_details->siteurl ); ?>)</span>
								<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
									<code>ID: <?php echo $blog_id; ?> - <?php echo $checked ? 'CHECKED' : 'unchecked'; ?></code>
								<?php endif; ?>
								
								<?php
								// Show ACF status.
								switch_to_blog( $site->blog_id );
								$acf_active = class_exists( 'ACF' ) || function_exists( 'acf' );
								restore_current_blog();
								?>
								
								<?php if ( $acf_active ) : ?>
									<span class="acf-sms-badge acf-sms-badge-success"><?php esc_html_e( 'ACF Active', 'acf-sms' ); ?></span>
								<?php else : ?>
									<span class="acf-sms-badge acf-sms-badge-warning"><?php esc_html_e( 'ACF Not Active', 'acf-sms' ); ?></span>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
							<p class="description">
								<?php esc_html_e( 'Select which sites should receive synced content. Sites without ACF active will be skipped automatically.', 'acf-sms' ); ?>
							</p>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<?php esc_html_e( 'Sync Actions', 'acf-sms' ); ?>
					</th>
					<td>
						<button type="button" class="button acf-sms-sync-all-btn" data-nonce="<?php echo esc_attr( wp_create_nonce( 'acf_sms_network' ) ); ?>">
							<?php esc_html_e( 'Sync All Content Now', 'acf-sms' ); ?>
						</button>
						<p class="description">
							<?php esc_html_e( 'Manually trigger a full sync of all locations and team members to selected sites.', 'acf-sms' ); ?>
						</p>
						<div class="acf-sms-sync-progress" style="display: none; margin-top: 10px;">
							<div class="acf-sms-progress-bar">
								<div class="acf-sms-progress-fill" style="width: 0%;"></div>
							</div>
							<p class="acf-sms-progress-text"></p>
						</div>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Network Settings', 'acf-sms' ) ); ?>
		</form>

		<script>
		jQuery(document).ready(function($) {
			// Handle "All Sites" toggle
			$('#acf_sms_sync_all_sites').on('change', function() {
				$('.acf-sms-site-checkbox').prop('checked', $(this).is(':checked'));
			});

			// Update "All Sites" checkbox based on individual selections
			$('.acf-sms-site-checkbox').on('change', function() {
				var total = $('.acf-sms-site-checkbox').length;
				var checked = $('.acf-sms-site-checkbox:checked').length;
				$('#acf_sms_sync_all_sites').prop('checked', total === checked);
			});

			// Set initial state of "All Sites" checkbox
			var total = $('.acf-sms-site-checkbox').length;
			var checked = $('.acf-sms-site-checkbox:checked').length;
			$('#acf_sms_sync_all_sites').prop('checked', total === checked);

			// Handle sync all button
			$('.acf-sms-sync-all-btn').on('click', function(e) {
				e.preventDefault();
				
				if (!confirm('<?php echo esc_js( __( 'This will sync all locations and team members to selected sites. This may take a while. Continue?', 'acf-sms' ) ); ?>')) {
					return;
				}

				var $button = $(this);
				var $progress = $('.acf-sms-sync-progress');
				var $progressBar = $('.acf-sms-progress-fill');
				var $progressText = $('.acf-sms-progress-text');
				var nonce = $button.data('nonce');

				$button.prop('disabled', true);
				$progress.show();
				$progressText.text('<?php echo esc_js( __( 'Starting sync...', 'acf-sms' ) ); ?>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'acf_sms_network_sync_all',
						nonce: nonce
					},
					success: function(response) {
						if (response.success) {
							$progressBar.css('width', '100%');
							$progressText.html('<span style="color: #28a745;">✓ ' + response.data.message + '</span>');
							setTimeout(function() {
								$progress.fadeOut();
								$button.prop('disabled', false);
							}, 3000);
						} else {
							$progressText.html('<span style="color: #dc3545;">✗ ' + response.data.message + '</span>');
							$button.prop('disabled', false);
						}
					},
					error: function() {
						$progressText.html('<span style="color: #dc3545;">✗ <?php echo esc_js( __( 'An error occurred', 'acf-sms' ) ); ?></span>');
						$button.prop('disabled', false);
					}
				});
			});
		});
		</script>

		<style>
		.acf-sms-badge {
			display: inline-block;
			padding: 2px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
			margin-left: 8px;
		}
		.acf-sms-badge-success {
			background: #d4edda;
			color: #155724;
		}
		.acf-sms-badge-warning {
			background: #fff3cd;
			color: #856404;
		}
		.acf-sms-progress-bar {
			width: 100%;
			height: 30px;
			background: #f0f0f0;
			border-radius: 4px;
			overflow: hidden;
		}
		.acf-sms-progress-fill {
			height: 100%;
			background: linear-gradient(90deg, #2271b1, #135e96);
			transition: width 0.3s ease;
		}
		.acf-sms-progress-text {
			margin-top: 8px;
			font-weight: 600;
		}
		</style>
		<?php
	}

	/**
	 * Render site status tab.
	 *
	 * @since 2.2.0
	 */
	private function render_status_tab() {
		$all_sites    = get_sites( array( 'number' => 1000 ) );
		$sync_enabled = get_site_option( 'acf_sms_sync_enabled', false );

		?>
		<div class="acf-sms-status-overview">
			<h2><?php esc_html_e( 'Network Sites Overview', 'acf-sms' ); ?></h2>
			
			<?php if ( ! $sync_enabled ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<strong><?php esc_html_e( 'Sync Disabled', 'acf-sms' ); ?></strong><br />
						<?php esc_html_e( 'Multisite synchronization is currently disabled. Enable it in the Sync Settings tab.', 'acf-sms' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Site', 'acf-sms' ); ?></th>
						<th><?php esc_html_e( 'ACF Status', 'acf-sms' ); ?></th>
						<th><?php esc_html_e( 'Locations', 'acf-sms' ); ?></th>
						<th><?php esc_html_e( 'Team Members', 'acf-sms' ); ?></th>
						<th><?php esc_html_e( 'Last Sync', 'acf-sms' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'acf-sms' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all_sites as $site ) : ?>
						<?php
						$site_details = get_blog_details( $site->blog_id );
						
						switch_to_blog( $site->blog_id );
						$acf_active      = class_exists( 'ACF' ) || function_exists( 'acf' );
						$location_count  = wp_count_posts( 'location' );
						$member_count    = wp_count_posts( 'team-member' );
						$last_sync       = get_option( 'acf_sms_last_sync_time', 0 );
						restore_current_blog();
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $site_details->blogname ); ?></strong><br />
								<span class="description"><?php echo esc_html( $site_details->siteurl ); ?></span>
							</td>
							<td>
								<?php if ( $acf_active ) : ?>
									<span class="acf-sms-badge acf-sms-badge-success"><?php esc_html_e( 'Active', 'acf-sms' ); ?></span>
								<?php else : ?>
									<span class="acf-sms-badge acf-sms-badge-warning"><?php esc_html_e( 'Not Active', 'acf-sms' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<?php echo esc_html( $location_count->publish ?? 0 ); ?>
								<?php if ( isset( $location_count->draft ) && $location_count->draft > 0 ) : ?>
									<span class="description">(+<?php echo esc_html( $location_count->draft ); ?> draft)</span>
								<?php endif; ?>
							</td>
							<td>
								<?php echo esc_html( $member_count->publish ?? 0 ); ?>
								<?php if ( isset( $member_count->draft ) && $member_count->draft > 0 ) : ?>
									<span class="description">(+<?php echo esc_html( $member_count->draft ); ?> draft)</span>
								<?php endif; ?>
							</td>
							<td>
								<?php
								if ( $last_sync ) {
									echo esc_html( human_time_diff( $last_sync, current_time( 'timestamp' ) ) . ' ago' );
								} else {
									esc_html_e( 'Never', 'acf-sms' );
								}
								?>
							</td>
							<td>
								<a href="<?php echo esc_url( get_admin_url( $site->blog_id, 'admin.php?page=acf-sms' ) ); ?>" class="button button-small">
									<?php esc_html_e( 'View Dashboard', 'acf-sms' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<style>
		.acf-sms-status-overview {
			margin-top: 20px;
		}
		.acf-sms-status-overview table {
			margin-top: 20px;
		}
		</style>
		<?php
	}

	/**
	 * Save network settings.
	 *
	 * @since 2.2.0
	 */
	public function save_settings() {
		// Debug: Log entire POST data.
		if ( defined( 'ACF_LS_DEBUG' ) && ACF_LS_DEBUG ) {
			error_log( 'ACF SMS: save_settings() called' );
			error_log( 'ACF SMS: POST data = ' . print_r( $_POST, true ) );
		}

		// Verify nonce.
		if ( ! isset( $_POST['acf_sms_network_nonce'] ) || ! wp_verify_nonce( $_POST['acf_sms_network_nonce'], 'acf_sms_network_settings' ) ) {
			wp_die( esc_html__( 'Security check failed', 'acf-sms' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'acf-sms' ) );
		}

		// Save master site setting.
		$master_site = isset( $_POST['acf_sms_master_site'] ) ? intval( $_POST['acf_sms_master_site'] ) : get_main_site_id();
		update_site_option( 'acf_sms_master_site', $master_site );

		// Save sync enabled setting.
		$sync_enabled = isset( $_POST['acf_sms_sync_enabled'] ) ? true : false;
		update_site_option( 'acf_sms_sync_enabled', $sync_enabled );

		// Save sync sites.
		$sync_sites = isset( $_POST['acf_sms_sync_sites'] ) ? array_map( 'intval', $_POST['acf_sms_sync_sites'] ) : array();
		update_site_option( 'acf_sms_sync_sites', $sync_sites );

		// Debug logging.
		if ( defined( 'ACF_LS_DEBUG' ) && ACF_LS_DEBUG ) {
			error_log( 'ACF SMS: Saving network settings' );
			error_log( 'ACF SMS: sync_enabled = ' . ( $sync_enabled ? 'true' : 'false' ) );
			error_log( 'ACF SMS: sync_sites (from POST) = ' . print_r( $sync_sites, true ) );
			error_log( 'ACF SMS: sync_sites (after save, from DB) = ' . print_r( get_site_option( 'acf_sms_sync_sites' ), true ) );
		}

		// Redirect with success message.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'acf-sms-network',
					'tab'     => 'sync',
					'updated' => 'true',
				),
				network_admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * AJAX handler for syncing all content.
	 *
	 * @since 2.2.0
	 */
	public function ajax_sync_all() {
		check_ajax_referer( 'acf_sms_network', 'nonce' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'acf-sms' ) ) );
		}

		// Get the multisite sync instance.
		$plugin = ACF_Location_Shortcodes::instance();
		if ( ! isset( $plugin->multisite_sync ) ) {
			wp_send_json_error( array( 'message' => __( 'Multisite sync not initialized', 'acf-sms' ) ) );
		}

		$sync = $plugin->multisite_sync;
		$synced_total = 0;

		// Sync locations.
		$locations = get_posts(
			array(
				'post_type'      => 'location',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		foreach ( $locations as $post ) {
			$sync->sync_post_on_save( $post->ID, $post, true );
			$synced_total++;
		}

		// Sync team members.
		$members = get_posts(
			array(
				'post_type'      => 'team-member',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		foreach ( $members as $post ) {
			$sync->sync_post_on_save( $post->ID, $post, true );
			$synced_total++;
		}

		// Update last sync time for current site.
		update_option( 'acf_sms_last_sync_time', current_time( 'timestamp' ) );

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of posts synced */
					__( 'Successfully synced %d posts across network', 'acf-sms' ),
					$synced_total
				),
				'count'   => $synced_total,
			)
		);
	}
}
