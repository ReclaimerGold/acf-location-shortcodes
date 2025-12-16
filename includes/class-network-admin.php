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

		// AJAX handlers for sync.
		add_action( 'wp_ajax_acf_sms_network_sync_all', array( $this, 'ajax_sync_all' ) );
		add_action( 'wp_ajax_acf_sms_get_sync_counts', array( $this, 'ajax_get_sync_counts' ) );
		add_action( 'wp_ajax_acf_sms_sync_batch', array( $this, 'ajax_sync_batch' ) );
		add_action( 'wp_ajax_acf_sms_test_profile_pic', array( $this, 'ajax_test_profile_pic' ) );
		add_action( 'wp_ajax_acf_sms_export_diagnostics', array( $this, 'ajax_export_diagnostics' ) );
		add_action( 'wp_ajax_acf_sms_force_sync_profile_pic', array( $this, 'ajax_force_sync_profile_pic' ) );
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
				<a href="?page=acf-sms-network&tab=diagnostics" class="nav-tab <?php echo 'diagnostics' === $this->active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Diagnostics', 'acf-sms' ); ?>
				</a>
			</nav>

			<div class="acf-sms-tab-content">
				<?php
				switch ( $this->active_tab ) {
					case 'status':
						$this->render_status_tab();
						break;
					case 'diagnostics':
						$this->render_diagnostics_tab();
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
							<?php esc_html_e( 'Automatically sync content across network sites', 'acf-sms' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'When enabled, creating or updating the following content types on the master site will sync to selected sites:', 'acf-sms' ); ?>
						</p>
						<ul style="margin-top: 10px; margin-left: 20px; list-style: disc;">
							<li><strong><?php esc_html_e( 'Service Locations', 'acf-sms' ); ?></strong> - <?php esc_html_e( 'Physical locations and service areas', 'acf-sms' ); ?></li>
							<li><strong><?php esc_html_e( 'Team Members', 'acf-sms' ); ?></strong> - <?php esc_html_e( 'Staff profiles with location assignments', 'acf-sms' ); ?></li>
							<li><strong><?php esc_html_e( 'Services', 'acf-sms' ); ?></strong> - <?php esc_html_e( 'Service offerings with categories and tags', 'acf-sms' ); ?></li>
							<li><strong><?php esc_html_e( 'Conditions', 'acf-sms' ); ?></strong> - <?php esc_html_e( 'Medical/service conditions', 'acf-sms' ); ?></li>
						</ul>
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
						<button type="button" class="button button-primary acf-sms-sync-all-btn" data-nonce="<?php echo esc_attr( wp_create_nonce( 'acf_sms_network' ) ); ?>">
							<span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>
							<?php esc_html_e( 'Sync All Content Now', 'acf-sms' ); ?>
						</button>
						<p class="description">
							<?php esc_html_e( 'Manually trigger a full sync of all locations, team members, services, and conditions to selected sites.', 'acf-sms' ); ?>
						</p>
						
						<!-- Advanced Progress Container -->
						<div class="acf-sms-sync-progress-container" style="display: none; margin-top: 20px;">
							<!-- Post Type Progress Bars -->
							<div class="acf-sms-progress-section">
								<h4 style="margin-bottom: 15px; color: #1d2327;">
									<span class="dashicons dashicons-database" style="vertical-align: middle; margin-right: 5px;"></span>
									<?php esc_html_e( 'Post Types', 'acf-sms' ); ?>
								</h4>
								
								<!-- Location Progress -->
								<div class="acf-sms-progress-item" data-type="location">
									<div class="acf-sms-progress-header">
										<span class="acf-sms-progress-label">
											<span class="dashicons dashicons-location" style="color: #2271b1;"></span>
											<?php esc_html_e( 'Service Locations', 'acf-sms' ); ?>
										</span>
										<span class="acf-sms-progress-count">
											<span class="current">0</span> / <span class="total">0</span>
										</span>
									</div>
									<div class="acf-sms-progress-bar">
										<div class="acf-sms-progress-fill" style="width: 0%; background: linear-gradient(90deg, #2271b1, #135e96);"></div>
									</div>
								</div>
								
								<!-- Service Progress -->
								<div class="acf-sms-progress-item" data-type="service">
									<div class="acf-sms-progress-header">
										<span class="acf-sms-progress-label">
											<span class="dashicons dashicons-clipboard" style="color: #00a32a;"></span>
											<?php esc_html_e( 'Services', 'acf-sms' ); ?>
										</span>
										<span class="acf-sms-progress-count">
											<span class="current">0</span> / <span class="total">0</span>
										</span>
									</div>
									<div class="acf-sms-progress-bar">
										<div class="acf-sms-progress-fill" style="width: 0%; background: linear-gradient(90deg, #00a32a, #007017);"></div>
									</div>
								</div>
								
								<!-- Condition Progress -->
								<div class="acf-sms-progress-item" data-type="condition">
									<div class="acf-sms-progress-header">
										<span class="acf-sms-progress-label">
											<span class="dashicons dashicons-heart" style="color: #d63638;"></span>
											<?php esc_html_e( 'Conditions', 'acf-sms' ); ?>
										</span>
										<span class="acf-sms-progress-count">
											<span class="current">0</span> / <span class="total">0</span>
										</span>
									</div>
									<div class="acf-sms-progress-bar">
										<div class="acf-sms-progress-fill" style="width: 0%; background: linear-gradient(90deg, #d63638, #a91d22);"></div>
									</div>
								</div>
								
								<!-- Team Member Progress -->
								<div class="acf-sms-progress-item" data-type="team-member">
									<div class="acf-sms-progress-header">
										<span class="acf-sms-progress-label">
											<span class="dashicons dashicons-groups" style="color: #8c5fc4;"></span>
											<?php esc_html_e( 'Team Members', 'acf-sms' ); ?>
										</span>
										<span class="acf-sms-progress-count">
											<span class="current">0</span> / <span class="total">0</span>
										</span>
									</div>
									<div class="acf-sms-progress-bar">
										<div class="acf-sms-progress-fill" style="width: 0%; background: linear-gradient(90deg, #8c5fc4, #6b4699);"></div>
									</div>
								</div>
							</div>
							
							<!-- Relationships & Media Stats (count only, no estimates) -->
							<div class="acf-sms-progress-section" style="margin-top: 25px;">
								<h4 style="margin-bottom: 15px; color: #1d2327;">
									<span class="dashicons dashicons-networking" style="vertical-align: middle; margin-right: 5px;"></span>
									<?php esc_html_e( 'Synced Data', 'acf-sms' ); ?>
								</h4>
								
								<div class="acf-sms-sync-stats" style="display: flex; gap: 20px; flex-wrap: wrap;">
									<!-- Relationship Count -->
									<div class="acf-sms-stat-item" data-type="relationships" style="background: #fff8e5; padding: 12px 20px; border-radius: 6px; border-left: 4px solid #f0b849;">
										<span class="dashicons dashicons-randomize" style="color: #f0b849; vertical-align: middle;"></span>
										<span class="acf-sms-stat-label"><?php esc_html_e( 'Relationships:', 'acf-sms' ); ?></span>
										<strong class="acf-sms-stat-count current" style="font-size: 16px;">0</strong>
									</div>
									
									<!-- Taxonomy Count -->
									<div class="acf-sms-stat-item" data-type="taxonomies" style="background: #e8f4fc; padding: 12px 20px; border-radius: 6px; border-left: 4px solid #3582c4;">
										<span class="dashicons dashicons-category" style="color: #3582c4; vertical-align: middle;"></span>
										<span class="acf-sms-stat-label"><?php esc_html_e( 'Taxonomies:', 'acf-sms' ); ?></span>
										<strong class="acf-sms-stat-count current" style="font-size: 16px;">0</strong>
									</div>
									
									<!-- Media Count -->
									<div class="acf-sms-stat-item" data-type="media" style="background: #f0f0f1; padding: 12px 20px; border-radius: 6px; border-left: 4px solid #50575e;">
										<span class="dashicons dashicons-format-image" style="color: #50575e; vertical-align: middle;"></span>
										<span class="acf-sms-stat-label"><?php esc_html_e( 'Media Files:', 'acf-sms' ); ?></span>
										<strong class="acf-sms-stat-count current" style="font-size: 16px;">0</strong>
									</div>
								</div>
							</div>
							
							<!-- Master Progress Bar -->
							<div class="acf-sms-master-progress" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #c3c4c7;">
								<h4 style="margin-bottom: 15px; color: #1d2327; font-size: 14px;">
									<span class="dashicons dashicons-yes-alt" style="vertical-align: middle; margin-right: 5px; color: #00a32a;"></span>
									<?php esc_html_e( 'Total Posts Synced', 'acf-sms' ); ?>
								</h4>
								<div class="acf-sms-progress-header">
									<span class="acf-sms-progress-label" style="font-weight: 600; font-size: 13px;">
										<?php esc_html_e( 'Posts Progress', 'acf-sms' ); ?>
									</span>
									<span class="acf-sms-progress-count" style="font-weight: 600;">
										<span class="current">0</span> / <span class="total">0</span>
										<span class="acf-sms-percentage-display">(<span class="percentage">0</span>%)</span>
									</span>
								</div>
								<div class="acf-sms-progress-bar acf-sms-master-bar" style="height: 30px; border-radius: 6px;">
									<div class="acf-sms-progress-fill" style="width: 0%; background: linear-gradient(90deg, #2271b1, #00a32a);"></div>
								</div>
								<p class="acf-sms-status-message" style="margin-top: 10px; font-style: italic; color: #50575e;">
									<?php esc_html_e( 'Ready to sync...', 'acf-sms' ); ?>
								</p>
							</div>
							
							<!-- Sync Log -->
							<div class="acf-sms-sync-log" style="margin-top: 20px;">
								<details>
									<summary style="cursor: pointer; font-weight: 600; color: #1d2327;">
										<span class="dashicons dashicons-list-view" style="vertical-align: middle;"></span>
										<?php esc_html_e( 'Sync Log', 'acf-sms' ); ?>
									</summary>
									<div class="acf-sms-log-content" style="max-height: 200px; overflow-y: auto; background: #f6f7f7; padding: 10px; margin-top: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;">
										<!-- Log entries will be added here -->
									</div>
								</details>
							</div>
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

			// Sync state management
			var syncState = {
				isRunning: false,
				nonce: '',
				postTypes: ['location', 'service', 'condition', 'team-member'],
				currentTypeIndex: 0,
				counts: {},
				progress: {},
				totalItems: 0,
				totalSynced: 0,
				relationshipsSynced: 0,
				taxonomiesSynced: 0,
				mediaSynced: 0
			};

			// Add log entry
			function addLogEntry(message, type) {
				var $log = $('.acf-sms-log-content');
				var timestamp = new Date().toLocaleTimeString();
				var colorClass = type === 'error' ? '#dc3545' : (type === 'success' ? '#00a32a' : '#50575e');
				$log.append('<div style="color: ' + colorClass + '">[' + timestamp + '] ' + message + '</div>');
				$log.scrollTop($log[0].scrollHeight);
			}

			// Update progress bar for a specific post type
			function updateTypeProgress(type, current, total) {
				var $item = $('.acf-sms-progress-item[data-type="' + type + '"]');
				$item.find('.current').text(current);
				$item.find('.total').text(total);
				var percent = total > 0 ? Math.round((current / total) * 100) : 0;
				$item.find('.acf-sms-progress-fill').css('width', percent + '%');
			}
			
			// Update stat counter (relationships, taxonomies, media - no totals)
			function updateStatCount(type, count) {
				var $item = $('.acf-sms-stat-item[data-type="' + type + '"]');
				$item.find('.current').text(count);
			}

			// Update master progress (only counts posts, not estimates)
			function updateMasterProgress() {
				var $master = $('.acf-sms-master-progress');
				$master.find('.current').text(syncState.totalSynced);
				$master.find('.total').text(syncState.totalItems);
				
				var percent = syncState.totalItems > 0 ? Math.round((syncState.totalSynced / syncState.totalItems) * 100) : 0;
				$master.find('.percentage').text(percent);
				$master.find('.acf-sms-progress-fill').css('width', percent + '%');
			}

			// Update status message
			function updateStatus(message) {
				$('.acf-sms-status-message').text(message);
			}

			// Get sync counts first
			function getSyncCounts(callback) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'acf_sms_get_sync_counts',
						nonce: syncState.nonce
					},
					success: function(response) {
						if (response.success) {
							syncState.counts = response.data.counts;
							syncState.totalItems = response.data.total_items;
							
							// Initialize progress bars with totals (posts only)
							$.each(syncState.counts, function(type, count) {
								updateTypeProgress(type, 0, count);
								syncState.progress[type] = 0;
							});
							
							// Initialize stat counters at 0
							updateStatCount('relationships', 0);
							updateStatCount('taxonomies', 0);
							updateStatCount('media', 0);
							
							updateMasterProgress();
							
							addLogEntry('<?php echo esc_js( __( 'Found', 'acf-sms' ) ); ?> ' + syncState.totalItems + ' <?php echo esc_js( __( 'posts to sync', 'acf-sms' ) ); ?>', 'info');
							callback();
						} else {
							addLogEntry('<?php echo esc_js( __( 'Error getting sync counts:', 'acf-sms' ) ); ?> ' + response.data.message, 'error');
						}
					},
					error: function() {
						addLogEntry('<?php echo esc_js( __( 'Failed to get sync counts', 'acf-sms' ) ); ?>', 'error');
					}
				});
			}

			// Sync a batch of posts
			function syncBatch(postType, offset, batchSize, callback) {
				updateStatus('<?php echo esc_js( __( 'Syncing', 'acf-sms' ) ); ?> ' + postType + '... (' + (offset + 1) + '-' + Math.min(offset + batchSize, syncState.counts[postType]) + ')');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'acf_sms_sync_batch',
						nonce: syncState.nonce,
						post_type: postType,
						offset: offset,
						batch_size: batchSize
					},
					success: function(response) {
						if (response.success) {
							var synced = response.data.synced;
							var hasMore = response.data.has_more;
							
							// Update progress
							syncState.progress[postType] += synced;
							syncState.totalSynced += synced;
							
							// Update relationship/taxonomy/media counts from response
							if (response.data.relationships_synced) {
								syncState.relationshipsSynced += response.data.relationships_synced;
								updateStatCount('relationships', syncState.relationshipsSynced);
							}
							if (response.data.taxonomies_synced) {
								syncState.taxonomiesSynced += response.data.taxonomies_synced;
								updateStatCount('taxonomies', syncState.taxonomiesSynced);
							}
							if (response.data.media_synced) {
								syncState.mediaSynced += response.data.media_synced;
								updateStatCount('media', syncState.mediaSynced);
							}
							
							updateTypeProgress(postType, syncState.progress[postType], syncState.counts[postType]);
							updateMasterProgress();
							
							// Log synced items
							if (response.data.items && response.data.items.length > 0) {
								$.each(response.data.items, function(i, item) {
									addLogEntry('✓ ' + item, 'success');
								});
							}
							
							if (hasMore) {
								// Continue with next batch
								setTimeout(function() {
									syncBatch(postType, offset + batchSize, batchSize, callback);
								}, 100);
							} else {
								// Done with this post type
								addLogEntry('<?php echo esc_js( __( 'Completed syncing', 'acf-sms' ) ); ?> ' + postType, 'success');
								callback();
							}
						} else {
							addLogEntry('<?php echo esc_js( __( 'Error syncing', 'acf-sms' ) ); ?> ' + postType + ': ' + response.data.message, 'error');
							callback();
						}
					},
					error: function() {
						addLogEntry('<?php echo esc_js( __( 'Failed to sync batch for', 'acf-sms' ) ); ?> ' + postType, 'error');
						callback();
					}
				});
			}

			// Sync next post type
			function syncNextType() {
				if (syncState.currentTypeIndex >= syncState.postTypes.length) {
					// All done!
					finishSync();
					return;
				}
				
				var postType = syncState.postTypes[syncState.currentTypeIndex];
				
				// Skip if no items to sync
				if (!syncState.counts[postType] || syncState.counts[postType] === 0) {
					syncState.currentTypeIndex++;
					syncNextType();
					return;
				}
				
				addLogEntry('<?php echo esc_js( __( 'Starting sync for', 'acf-sms' ) ); ?> ' + postType + ' (' + syncState.counts[postType] + ' <?php echo esc_js( __( 'items', 'acf-sms' ) ); ?>)', 'info');
				
				syncBatch(postType, 0, 5, function() {
					syncState.currentTypeIndex++;
					syncNextType();
				});
			}

			// Finish sync
			function finishSync() {
				syncState.isRunning = false;
				
				// Build completion summary
				var summary = syncState.totalSynced + ' <?php echo esc_js( __( 'posts', 'acf-sms' ) ); ?>';
				if (syncState.relationshipsSynced > 0) {
					summary += ', ' + syncState.relationshipsSynced + ' <?php echo esc_js( __( 'relationships', 'acf-sms' ) ); ?>';
				}
				if (syncState.taxonomiesSynced > 0) {
					summary += ', ' + syncState.taxonomiesSynced + ' <?php echo esc_js( __( 'taxonomies', 'acf-sms' ) ); ?>';
				}
				if (syncState.mediaSynced > 0) {
					summary += ', ' + syncState.mediaSynced + ' <?php echo esc_js( __( 'media files', 'acf-sms' ) ); ?>';
				}
				
				updateStatus('✅ <?php echo esc_js( __( 'Sync completed!', 'acf-sms' ) ); ?> ' + summary);
				addLogEntry('<?php echo esc_js( __( 'Sync completed!', 'acf-sms' ) ); ?> ' + summary, 'success');
				
				// Re-enable button
				$('.acf-sms-sync-all-btn').prop('disabled', false).html(
					'<span class="dashicons dashicons-yes" style="vertical-align: middle; margin-right: 5px;"></span>' +
					'<?php echo esc_js( __( 'Sync Complete!', 'acf-sms' ) ); ?>'
				);
				
				setTimeout(function() {
					$('.acf-sms-sync-all-btn').html(
						'<span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>' +
						'<?php echo esc_js( __( 'Sync All Content Now', 'acf-sms' ) ); ?>'
					);
				}, 3000);
			}

			// Handle sync all button
			$('.acf-sms-sync-all-btn').on('click', function(e) {
				e.preventDefault();
				
				if (syncState.isRunning) {
					return;
				}
				
				if (!confirm('<?php echo esc_js( __( 'This will sync all content to selected sites. Continue?', 'acf-sms' ) ); ?>')) {
					return;
				}

				var $button = $(this);
				syncState.nonce = $button.data('nonce');
				syncState.isRunning = true;
				syncState.currentTypeIndex = 0;
				syncState.totalSynced = 0;
				syncState.relationshipsSynced = 0;
				syncState.taxonomiesSynced = 0;
				syncState.mediaSynced = 0;
				syncState.progress = {};

				$button.prop('disabled', true).html(
					'<span class="dashicons dashicons-update spin" style="vertical-align: middle; margin-right: 5px;"></span>' +
					'<?php echo esc_js( __( 'Syncing...', 'acf-sms' ) ); ?>'
				);
				
				// Show progress container
				$('.acf-sms-sync-progress-container').slideDown();
				$('.acf-sms-log-content').empty();
				
				addLogEntry('<?php echo esc_js( __( 'Starting network sync...', 'acf-sms' ) ); ?>', 'info');
				updateStatus('<?php echo esc_js( __( 'Calculating items to sync...', 'acf-sms' ) ); ?>');
				
				// Get counts first, then start syncing
				getSyncCounts(function() {
					syncNextType();
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
		
		/* Progress Container */
		.acf-sms-sync-progress-container {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 8px;
			padding: 20px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}
		
		/* Progress Section */
		.acf-sms-progress-section {
			margin-bottom: 10px;
		}
		
		/* Progress Item */
		.acf-sms-progress-item {
			margin-bottom: 15px;
		}
		
		/* Progress Header */
		.acf-sms-progress-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 5px;
		}
		
		.acf-sms-progress-label {
			font-size: 13px;
			color: #1d2327;
		}
		
		.acf-sms-progress-label .dashicons {
			vertical-align: middle;
			margin-right: 5px;
		}
		
		.acf-sms-progress-count {
			font-size: 12px;
			color: #50575e;
			font-family: monospace;
		}
		
		/* Progress Bar */
		.acf-sms-progress-bar {
			width: 100%;
			height: 20px;
			background: #f0f0f1;
			border-radius: 4px;
			overflow: hidden;
			box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
		}
		
		.acf-sms-progress-fill {
			height: 100%;
			transition: width 0.3s ease;
			border-radius: 4px;
		}
		
		/* Master Progress Bar */
		.acf-sms-master-bar {
			height: 30px;
			border-radius: 6px;
		}
		
		/* Spinning animation for sync button */
		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		
		.dashicons.spin {
			animation: spin 1s linear infinite;
		}
		
		/* Sync Log */
		.acf-sms-sync-log details summary {
			padding: 10px;
			background: #f6f7f7;
			border-radius: 4px;
		}
		
		.acf-sms-sync-log details summary:hover {
			background: #eee;
		}
		
		.acf-sms-sync-log details[open] summary {
			border-radius: 4px 4px 0 0;
		}
		
		.acf-sms-log-content {
			border: 1px solid #ddd;
			border-top: none;
			border-radius: 0 0 4px 4px;
		}
		
		.acf-sms-log-content div {
			padding: 3px 0;
			border-bottom: 1px solid #f0f0f1;
		}
		
		.acf-sms-log-content div:last-child {
			border-bottom: none;
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
						<th><?php esc_html_e( 'Services', 'acf-sms' ); ?></th>
						<th><?php esc_html_e( 'Conditions', 'acf-sms' ); ?></th>
						<th><?php esc_html_e( 'Last Sync', 'acf-sms' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'acf-sms' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all_sites as $site ) : ?>
						<?php
						$site_details = get_blog_details( $site->blog_id );
						
						switch_to_blog( $site->blog_id );
						$acf_active       = class_exists( 'ACF' ) || function_exists( 'acf' );
						$location_count   = wp_count_posts( 'location' );
						$member_count     = wp_count_posts( 'team-member' );
						$service_count    = post_type_exists( 'service' ) ? wp_count_posts( 'service' ) : null;
						$condition_count  = post_type_exists( 'condition' ) ? wp_count_posts( 'condition' ) : null;
						$last_sync        = get_option( 'acf_sms_last_sync_time', 0 );
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
								<?php if ( $service_count ) : ?>
									<?php echo esc_html( $service_count->publish ?? 0 ); ?>
									<?php if ( isset( $service_count->draft ) && $service_count->draft > 0 ) : ?>
										<span class="description">(+<?php echo esc_html( $service_count->draft ); ?> draft)</span>
									<?php endif; ?>
								<?php else : ?>
									<span class="description"><?php esc_html_e( 'N/A', 'acf-sms' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $condition_count ) : ?>
									<?php echo esc_html( $condition_count->publish ?? 0 ); ?>
									<?php if ( isset( $condition_count->draft ) && $condition_count->draft > 0 ) : ?>
										<span class="description">(+<?php echo esc_html( $condition_count->draft ); ?> draft)</span>
									<?php endif; ?>
								<?php else : ?>
									<span class="description"><?php esc_html_e( 'N/A', 'acf-sms' ); ?></span>
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
	 * Render diagnostics tab with comprehensive sync information.
	 *
	 * @since 2.4.0
	 */
	private function render_diagnostics_tab() {
		$master_site_id = (int) get_site_option( 'acf_sms_master_site', get_main_site_id() );
		$sync_sites = get_site_option( 'acf_sms_sync_sites', array() );
		$all_sites = get_sites( array( 'number' => 100 ) );
		$sync_enabled = get_site_option( 'acf_sms_sync_enabled', false );
		
		// Get master site details.
		$master_details = get_blog_details( $master_site_id );
		
		?>
		<div class="acf-sms-diagnostics">
			<h2><?php esc_html_e( 'Sync Diagnostics & Status', 'acf-sms' ); ?></h2>
			
			<!-- Sync Configuration Overview -->
			<div class="acf-sms-diag-section">
				<h3><?php esc_html_e( 'Configuration Overview', 'acf-sms' ); ?></h3>
				<div class="acf-sms-diag-config">
					<div class="config-item">
						<span class="config-label"><?php esc_html_e( 'Sync Status:', 'acf-sms' ); ?></span>
						<span class="config-value <?php echo $sync_enabled ? 'status-active' : 'status-inactive'; ?>">
							<?php echo $sync_enabled ? '✓ Enabled' : '✗ Disabled'; ?>
						</span>
					</div>
					<div class="config-item">
						<span class="config-label"><?php esc_html_e( 'Master Site:', 'acf-sms' ); ?></span>
						<span class="config-value"><?php echo esc_html( $master_details->blogname ); ?> (ID: <?php echo $master_site_id; ?>)</span>
					</div>
					<div class="config-item">
						<span class="config-label"><?php esc_html_e( 'Target Sites:', 'acf-sms' ); ?></span>
						<span class="config-value"><?php echo count( $sync_sites ); ?> sites configured</span>
					</div>
					<div class="config-item">
						<span class="config-label"><?php esc_html_e( 'Debug Mode:', 'acf-sms' ); ?></span>
						<span class="config-value <?php echo ( defined( 'ACF_LS_DEBUG' ) && ACF_LS_DEBUG ) ? 'status-active' : 'status-inactive'; ?>">
							<?php echo ( defined( 'ACF_LS_DEBUG' ) && ACF_LS_DEBUG ) ? '✓ Enabled' : '✗ Disabled'; ?>
						</span>
					</div>
				</div>
			</div>
			
			<!-- Visual Sync Map -->
			<div class="acf-sms-diag-section">
				<h3><?php esc_html_e( 'Sync Relationship Map', 'acf-sms' ); ?></h3>
				<div class="acf-sms-sync-map">
					<div class="sync-map-master">
						<div class="site-node master-node">
							<span class="node-icon">👑</span>
							<span class="node-name"><?php echo esc_html( $master_details->blogname ); ?></span>
							<span class="node-url"><?php echo esc_html( $master_details->siteurl ); ?></span>
						</div>
						<div class="sync-arrows">
							<?php foreach ( $sync_sites as $site_id ) : ?>
								<?php if ( (int) $site_id !== $master_site_id ) : ?>
									<div class="arrow-line"></div>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="sync-map-targets">
						<?php foreach ( $all_sites as $site ) : ?>
							<?php if ( (int) $site->blog_id === $master_site_id ) continue; ?>
							<?php 
							$site_details = get_blog_details( $site->blog_id );
							$is_sync_target = in_array( (int) $site->blog_id, array_map( 'intval', $sync_sites ), true );
							
							switch_to_blog( $site->blog_id );
							$last_sync = get_option( 'acf_sms_last_sync_time', 0 );
							$acf_active = class_exists( 'ACF' ) || function_exists( 'acf' );
							restore_current_blog();
							?>
							<div class="site-node target-node <?php echo $is_sync_target ? 'active-target' : 'inactive-target'; ?>">
								<span class="node-status"><?php echo $is_sync_target ? '🔄' : '⏸️'; ?></span>
								<span class="node-name"><?php echo esc_html( $site_details->blogname ); ?></span>
								<span class="node-acf <?php echo $acf_active ? 'acf-ok' : 'acf-missing'; ?>">
									ACF: <?php echo $acf_active ? '✓' : '✗'; ?>
								</span>
								<span class="node-sync-time">
									<?php 
									if ( $last_sync ) {
										echo 'Last: ' . human_time_diff( $last_sync, current_time( 'timestamp' ) ) . ' ago';
									} else {
										echo 'Never synced';
									}
									?>
								</span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			
			<!-- Detailed Content Status Per Site -->
			<div class="acf-sms-diag-section">
				<h3><?php esc_html_e( 'Content Sync Status by Site', 'acf-sms' ); ?></h3>
				
				<?php foreach ( $all_sites as $site ) : ?>
					<?php 
					$site_details = get_blog_details( $site->blog_id );
					$is_master = (int) $site->blog_id === $master_site_id;
					
					switch_to_blog( $site->blog_id );
					
					// Get counts for each post type.
					$locations = wp_count_posts( 'location' );
					$team_members = wp_count_posts( 'team-member' );
					$services = post_type_exists( 'service' ) ? wp_count_posts( 'service' ) : null;
					$conditions = post_type_exists( 'condition' ) ? wp_count_posts( 'condition' ) : null;
					
					// Get synced post counts.
					global $wpdb;
					$synced_locations = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_acf_sms_source_site'" );
					
					// Get team members with profile pictures.
					$team_posts = get_posts( array(
						'post_type' => 'team-member',
						'posts_per_page' => -1,
						'post_status' => 'any',
					) );
					
					$with_profile_pic = 0;
					$without_profile_pic = 0;
					$profile_pic_issues = array();
					
					foreach ( $team_posts as $tm ) {
						$pic = get_post_meta( $tm->ID, 'profile_picture', true );
						if ( ! empty( $pic ) ) {
							$with_profile_pic++;
							// Verify attachment exists.
							if ( is_numeric( $pic ) ) {
								$attachment = get_post( $pic );
								if ( ! $attachment ) {
									$profile_pic_issues[] = array(
										'post_id' => $tm->ID,
										'post_title' => $tm->post_title,
										'issue' => 'Attachment ID ' . $pic . ' does not exist',
									);
								} else {
									$file = get_attached_file( $pic );
									if ( ! file_exists( $file ) ) {
										$profile_pic_issues[] = array(
											'post_id' => $tm->ID,
											'post_title' => $tm->post_title,
											'issue' => 'File missing: ' . basename( $file ),
										);
									}
								}
							}
						} else {
							$without_profile_pic++;
						}
					}
					
					// Get media counts.
					$total_media = wp_count_posts( 'attachment' );
					$synced_media = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_acf_sms_source_site' AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment')" );
					
					restore_current_blog();
					?>
					
					<div class="site-status-card <?php echo $is_master ? 'master-card' : ''; ?>">
						<div class="site-header">
							<h4>
								<?php if ( $is_master ) : ?>
									<span class="master-badge">MASTER</span>
								<?php endif; ?>
								<?php echo esc_html( $site_details->blogname ); ?>
							</h4>
							<span class="site-url"><?php echo esc_html( $site_details->siteurl ); ?></span>
						</div>
						
						<div class="site-content-grid">
							<div class="content-stat">
								<span class="stat-icon">📍</span>
								<span class="stat-label">Locations</span>
								<span class="stat-value"><?php echo ( $locations->publish ?? 0 ) + ( $locations->draft ?? 0 ); ?></span>
							</div>
							<div class="content-stat">
								<span class="stat-icon">👥</span>
								<span class="stat-label">Team Members</span>
								<span class="stat-value"><?php echo ( $team_members->publish ?? 0 ) + ( $team_members->draft ?? 0 ); ?></span>
							</div>
							<div class="content-stat">
								<span class="stat-icon">🔧</span>
								<span class="stat-label">Services</span>
								<span class="stat-value"><?php echo $services ? ( ( $services->publish ?? 0 ) + ( $services->draft ?? 0 ) ) : 'N/A'; ?></span>
							</div>
							<div class="content-stat">
								<span class="stat-icon">🏥</span>
								<span class="stat-label">Conditions</span>
								<span class="stat-value"><?php echo $conditions ? ( ( $conditions->publish ?? 0 ) + ( $conditions->draft ?? 0 ) ) : 'N/A'; ?></span>
							</div>
							<div class="content-stat">
								<span class="stat-icon">🖼️</span>
								<span class="stat-label">Total Media</span>
								<span class="stat-value"><?php echo $total_media->inherit ?? 0; ?></span>
							</div>
							<?php if ( ! $is_master ) : ?>
							<div class="content-stat">
								<span class="stat-icon">🔗</span>
								<span class="stat-label">Synced Media</span>
								<span class="stat-value"><?php echo $synced_media; ?></span>
							</div>
							<?php endif; ?>
						</div>
						
						<!-- Profile Picture Status -->
						<div class="profile-pic-status">
							<h5>👤 Profile Picture Status</h5>
							<div class="pic-stats">
								<span class="pic-stat good">✓ With Picture: <?php echo $with_profile_pic; ?></span>
								<span class="pic-stat neutral">○ Without: <?php echo $without_profile_pic; ?></span>
								<?php if ( ! empty( $profile_pic_issues ) ) : ?>
									<span class="pic-stat bad">⚠ Issues: <?php echo count( $profile_pic_issues ); ?></span>
								<?php endif; ?>
							</div>
							
							<?php if ( ! empty( $profile_pic_issues ) ) : ?>
								<div class="pic-issues">
									<strong>Issues Found:</strong>
									<ul>
										<?php foreach ( array_slice( $profile_pic_issues, 0, 5 ) as $issue ) : ?>
											<li>
												<strong><?php echo esc_html( $issue['post_title'] ); ?></strong> (ID: <?php echo $issue['post_id']; ?>): 
												<?php echo esc_html( $issue['issue'] ); ?>
											</li>
										<?php endforeach; ?>
										<?php if ( count( $profile_pic_issues ) > 5 ) : ?>
											<li><em>... and <?php echo count( $profile_pic_issues ) - 5; ?> more</em></li>
										<?php endif; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			
			<!-- Test Profile Picture Sync -->
			<div class="acf-sms-diag-section">
				<h3><?php esc_html_e( 'Test & Force Sync Profile Picture', 'acf-sms' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Enter a Team Member post ID from the master site to test or force sync the profile picture.', 'acf-sms' ); ?></p>
				
				<div class="test-form">
					<input type="number" id="test-post-id" placeholder="Team Member Post ID" class="regular-text" />
					<button type="button" id="test-profile-pic-btn" class="button button-secondary">
						<?php esc_html_e( 'Test Sync Status', 'acf-sms' ); ?>
					</button>
					<button type="button" id="force-sync-btn" class="button button-primary">
						<?php esc_html_e( '⚡ Force Sync Profile Picture', 'acf-sms' ); ?>
					</button>
				</div>
				
				<p class="description" style="margin-top: 10px; color: #d63638;">
					<strong>Force Sync</strong> will bypass all caching and create fresh copies of the profile picture on all target sites.
				</p>
				
				<div id="test-results" class="test-results" style="display: none;">
					<h4>Results</h4>
					<pre id="test-output"></pre>
				</div>
			</div>
			
			<!-- JSON Export for AI/Cursor Integration -->
			<div class="acf-sms-diag-section">
				<h3>📋 <?php esc_html_e( 'Export Diagnostic Data (JSON)', 'acf-sms' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Generate comprehensive JSON data for debugging. Copy and paste this to your AI assistant (Cursor, ChatGPT, Claude) for help troubleshooting sync issues.', 'acf-sms' ); ?>
				</p>
				
				<div class="export-controls">
					<button type="button" id="export-json-btn" class="button button-primary">
						<?php esc_html_e( 'Generate Diagnostic JSON', 'acf-sms' ); ?>
					</button>
					<button type="button" id="copy-json-btn" class="button button-secondary" style="display: none;">
						<?php esc_html_e( 'Copy to Clipboard', 'acf-sms' ); ?>
					</button>
					<span id="copy-status" class="copy-status" style="display: none;">✓ Copied!</span>
				</div>
				
				<div id="json-export-container" class="json-export-container" style="display: none;">
					<div class="json-header">
						<span class="json-title">Diagnostic Export</span>
						<span class="json-size" id="json-size"></span>
					</div>
					<textarea id="json-output" class="json-output" readonly></textarea>
					<div class="json-instructions">
						<strong>How to use:</strong>
						<ol>
							<li>Click "Copy to Clipboard" above</li>
							<li>Paste in your AI assistant chat</li>
							<li>Ask: "Here's my ACF SMS diagnostic export. The profile pictures aren't syncing to subsites. What's wrong?"</li>
						</ol>
					</div>
				</div>
			</div>
			
			<!-- Recent Sync Log -->
			<div class="acf-sms-diag-section">
				<h3><?php esc_html_e( 'Debug Log', 'acf-sms' ); ?></h3>
				<?php if ( defined( 'ACF_LS_DEBUG' ) && ACF_LS_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) : ?>
					<?php 
					$log_file = WP_CONTENT_DIR . '/debug.log';
					$log_entries = array();
					
					if ( file_exists( $log_file ) ) {
						$lines = file( $log_file );
						$lines = array_reverse( $lines );
						$count = 0;
						foreach ( $lines as $line ) {
							if ( strpos( $line, 'ACF SMS:' ) !== false ) {
								$log_entries[] = $line;
								$count++;
								if ( $count >= 50 ) break;
							}
						}
					}
					?>
					<?php if ( ! empty( $log_entries ) ) : ?>
						<div class="log-viewer">
							<pre><?php echo esc_html( implode( '', $log_entries ) ); ?></pre>
						</div>
						<button type="button" id="refresh-log-btn" class="button button-secondary">
							<?php esc_html_e( 'Refresh Log', 'acf-sms' ); ?>
						</button>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'No ACF SMS log entries found. Run a sync to generate log entries.', 'acf-sms' ); ?></p>
					<?php endif; ?>
				<?php else : ?>
					<div class="notice notice-info inline">
						<p>
							<?php esc_html_e( 'Debug logging is not enabled. Add the following to wp-config.php to enable:', 'acf-sms' ); ?>
						</p>
						<pre>define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'ACF_LS_DEBUG', true );</pre>
					</div>
				<?php endif; ?>
			</div>
		</div>
		
		<style>
		.acf-sms-diagnostics {
			max-width: 1200px;
		}
		.acf-sms-diag-section {
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			padding: 20px;
			margin: 20px 0;
		}
		.acf-sms-diag-section h3 {
			margin-top: 0;
			padding-bottom: 10px;
			border-bottom: 1px solid #eee;
		}
		.acf-sms-diag-config {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 15px;
		}
		.config-item {
			display: flex;
			gap: 10px;
			align-items: center;
		}
		.config-label {
			font-weight: 600;
			color: #23282d;
		}
		.config-value.status-active {
			color: #00a32a;
			font-weight: 600;
		}
		.config-value.status-inactive {
			color: #d63638;
		}
		
		/* Sync Map */
		.acf-sms-sync-map {
			display: flex;
			flex-direction: column;
			align-items: center;
			padding: 20px;
			background: #f6f7f7;
			border-radius: 8px;
		}
		.sync-map-master {
			text-align: center;
			margin-bottom: 20px;
		}
		.site-node {
			display: inline-flex;
			flex-direction: column;
			align-items: center;
			padding: 15px 20px;
			background: #fff;
			border: 2px solid #2271b1;
			border-radius: 8px;
			margin: 5px;
		}
		.master-node {
			border-color: #dba617;
			background: linear-gradient(135deg, #fff9e6 0%, #fff 100%);
		}
		.node-icon {
			font-size: 24px;
		}
		.node-name {
			font-weight: 600;
			margin: 5px 0;
		}
		.node-url {
			font-size: 11px;
			color: #666;
		}
		.sync-arrows {
			display: flex;
			justify-content: center;
			gap: 30px;
			margin: 15px 0;
		}
		.arrow-line {
			width: 2px;
			height: 30px;
			background: #2271b1;
			position: relative;
		}
		.arrow-line::after {
			content: '▼';
			position: absolute;
			bottom: -12px;
			left: -6px;
			color: #2271b1;
		}
		.sync-map-targets {
			display: flex;
			flex-wrap: wrap;
			justify-content: center;
			gap: 15px;
		}
		.target-node {
			min-width: 180px;
		}
		.active-target {
			border-color: #00a32a;
		}
		.inactive-target {
			border-color: #c3c4c7;
			opacity: 0.7;
		}
		.node-acf {
			font-size: 11px;
			padding: 2px 8px;
			border-radius: 3px;
			margin-top: 5px;
		}
		.node-acf.acf-ok {
			background: #d4edda;
			color: #155724;
		}
		.node-acf.acf-missing {
			background: #f8d7da;
			color: #721c24;
		}
		.node-sync-time {
			font-size: 10px;
			color: #666;
			margin-top: 5px;
		}
		
		/* Site Status Cards */
		.site-status-card {
			background: #fff;
			border: 1px solid #ddd;
			border-radius: 8px;
			padding: 20px;
			margin: 15px 0;
		}
		.site-status-card.master-card {
			border-color: #dba617;
			background: linear-gradient(135deg, #fffef7 0%, #fff 100%);
		}
		.site-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 15px;
			padding-bottom: 10px;
			border-bottom: 1px solid #eee;
		}
		.site-header h4 {
			margin: 0;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.master-badge {
			background: #dba617;
			color: #fff;
			padding: 2px 8px;
			border-radius: 3px;
			font-size: 10px;
			font-weight: 600;
		}
		.site-url {
			color: #666;
			font-size: 12px;
		}
		.site-content-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
			gap: 15px;
			margin-bottom: 15px;
		}
		.content-stat {
			text-align: center;
			padding: 10px;
			background: #f6f7f7;
			border-radius: 6px;
		}
		.stat-icon {
			display: block;
			font-size: 20px;
			margin-bottom: 5px;
		}
		.stat-label {
			display: block;
			font-size: 11px;
			color: #666;
			margin-bottom: 3px;
		}
		.stat-value {
			display: block;
			font-size: 18px;
			font-weight: 600;
			color: #23282d;
		}
		
		/* Profile Picture Status */
		.profile-pic-status {
			background: #f9f9f9;
			padding: 15px;
			border-radius: 6px;
			margin-top: 10px;
		}
		.profile-pic-status h5 {
			margin: 0 0 10px 0;
		}
		.pic-stats {
			display: flex;
			gap: 15px;
			flex-wrap: wrap;
		}
		.pic-stat {
			padding: 5px 12px;
			border-radius: 4px;
			font-size: 13px;
		}
		.pic-stat.good {
			background: #d4edda;
			color: #155724;
		}
		.pic-stat.neutral {
			background: #e9ecef;
			color: #495057;
		}
		.pic-stat.bad {
			background: #f8d7da;
			color: #721c24;
		}
		.pic-issues {
			margin-top: 10px;
			padding: 10px;
			background: #fff3cd;
			border-radius: 4px;
		}
		.pic-issues ul {
			margin: 5px 0 0 20px;
			font-size: 12px;
		}
		
		/* Test Form */
		.test-form {
			display: flex;
			gap: 10px;
			align-items: center;
			margin: 15px 0;
		}
		.test-results {
			background: #1e1e1e;
			padding: 15px;
			border-radius: 6px;
			margin-top: 15px;
		}
		.test-results h4 {
			color: #fff;
			margin: 0 0 10px 0;
		}
		#test-output {
			color: #98c379;
			font-size: 12px;
			line-height: 1.6;
			max-height: 400px;
			overflow-y: auto;
			white-space: pre-wrap;
			margin: 0;
		}
		
		/* Log Viewer */
		.log-viewer {
			background: #1e1e1e;
			padding: 15px;
			border-radius: 6px;
			max-height: 400px;
			overflow-y: auto;
		}
		.log-viewer pre {
			color: #abb2bf;
			font-size: 11px;
			line-height: 1.5;
			margin: 0;
			white-space: pre-wrap;
		}
		
		/* JSON Export */
		.export-controls {
			display: flex;
			gap: 10px;
			align-items: center;
			margin: 15px 0;
		}
		.copy-status {
			color: #00a32a;
			font-weight: 600;
			animation: fadeIn 0.3s ease;
		}
		@keyframes fadeIn {
			from { opacity: 0; }
			to { opacity: 1; }
		}
		.json-export-container {
			margin-top: 15px;
			border: 1px solid #ddd;
			border-radius: 8px;
			overflow: hidden;
		}
		.json-header {
			background: #23282d;
			color: #fff;
			padding: 10px 15px;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.json-title {
			font-weight: 600;
		}
		.json-size {
			font-size: 12px;
			color: #aaa;
		}
		.json-output {
			width: 100%;
			height: 400px;
			font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
			font-size: 11px;
			line-height: 1.5;
			padding: 15px;
			border: none;
			background: #1e1e1e;
			color: #98c379;
			resize: vertical;
			box-sizing: border-box;
		}
		.json-output:focus {
			outline: none;
		}
		.json-instructions {
			background: #f6f7f7;
			padding: 15px;
			border-top: 1px solid #ddd;
		}
		.json-instructions ol {
			margin: 10px 0 0 20px;
		}
		.json-instructions li {
			margin: 5px 0;
		}
		</style>
		
		<script>
		jQuery(document).ready(function($) {
			// Test profile picture sync.
			$('#test-profile-pic-btn').on('click', function() {
				var postId = $('#test-post-id').val();
				if (!postId) {
					alert('Please enter a post ID');
					return;
				}
				
				var $btn = $(this);
				$btn.prop('disabled', true).text('Testing...');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'acf_sms_test_profile_pic',
						nonce: '<?php echo wp_create_nonce( 'acf_sms_network' ); ?>',
						post_id: postId
					},
					success: function(response) {
						$('#test-results').show();
						if (response.success) {
							$('#test-output').text(JSON.stringify(response.data, null, 2));
						} else {
							$('#test-output').text('Error: ' + response.data.message);
						}
					},
					error: function() {
						$('#test-results').show();
						$('#test-output').text('AJAX request failed');
					},
					complete: function() {
						$btn.prop('disabled', false).text('Test Sync Status');
					}
				});
			});
			
			// Force sync profile picture.
			$('#force-sync-btn').on('click', function() {
				var postId = $('#test-post-id').val();
				if (!postId) {
					alert('Please enter a post ID');
					return;
				}
				
				if (!confirm('This will force sync the profile picture to all target sites. Continue?')) {
					return;
				}
				
				var $btn = $(this);
				$btn.prop('disabled', true).text('⚡ Syncing...');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'acf_sms_force_sync_profile_pic',
						nonce: '<?php echo wp_create_nonce( 'acf_sms_network' ); ?>',
						post_id: postId
					},
					success: function(response) {
						$('#test-results').show();
						if (response.success) {
							$('#test-output').text('FORCE SYNC RESULTS:\n\n' + JSON.stringify(response.data, null, 2));
						} else {
							$('#test-output').text('Error: ' + response.data.message);
						}
					},
					error: function(xhr, status, error) {
						$('#test-results').show();
						$('#test-output').text('AJAX request failed: ' + error);
					},
					complete: function() {
						$btn.prop('disabled', false).text('⚡ Force Sync Profile Picture');
					}
				});
			});
			
			// Refresh log.
			$('#refresh-log-btn').on('click', function() {
				location.reload();
			});
			
			// Export JSON diagnostics.
			$('#export-json-btn').on('click', function() {
				var $btn = $(this);
				$btn.prop('disabled', true).text('Generating...');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'acf_sms_export_diagnostics',
						nonce: '<?php echo wp_create_nonce( 'acf_sms_network' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							var jsonStr = JSON.stringify(response.data, null, 2);
							$('#json-output').val(jsonStr);
							$('#json-size').text(formatBytes(jsonStr.length));
							$('#json-export-container').show();
							$('#copy-json-btn').show();
						} else {
							alert('Error: ' + response.data.message);
						}
					},
					error: function() {
						alert('AJAX request failed');
					},
					complete: function() {
						$btn.prop('disabled', false).text('Generate Diagnostic JSON');
					}
				});
			});
			
			// Copy to clipboard.
			$('#copy-json-btn').on('click', function() {
				var $textarea = $('#json-output');
				$textarea.select();
				document.execCommand('copy');
				
				// Try modern clipboard API as fallback.
				if (navigator.clipboard) {
					navigator.clipboard.writeText($textarea.val());
				}
				
				$('#copy-status').show().delay(2000).fadeOut();
			});
			
			// Helper function to format bytes.
			function formatBytes(bytes) {
				if (bytes < 1024) return bytes + ' bytes';
				if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
				return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
			}
		});
		</script>
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
		$synced_by_type = array();

		// Get all post types to sync.
		$sync_post_types = $sync->get_sync_post_types();

		// Sync each post type in order (locations first, then services, then conditions, then team members).
		// This order ensures relationship fields have their targets available.
		$ordered_types = array( 'location', 'service', 'condition', 'team-member' );
		
		foreach ( $ordered_types as $post_type ) {
			if ( ! in_array( $post_type, $sync_post_types, true ) ) {
				continue;
			}

			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}

			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => -1,
					'post_status'    => 'any',
				)
			);

			$synced_by_type[ $post_type ] = 0;

			foreach ( $posts as $post ) {
				$sync->sync_post_on_save( $post->ID, $post, true );
				$synced_total++;
				$synced_by_type[ $post_type ]++;
			}
		}

		// Update last sync time for current site.
		update_option( 'acf_sms_last_sync_time', current_time( 'timestamp' ) );

		// Build detailed message.
		$type_messages = array();
		foreach ( $synced_by_type as $type => $count ) {
			if ( $count > 0 ) {
				$type_obj = get_post_type_object( $type );
				$label = $type_obj ? $type_obj->labels->name : $type;
				$type_messages[] = sprintf( '%d %s', $count, $label );
			}
		}

		$detailed_message = ! empty( $type_messages ) 
			? implode( ', ', $type_messages )
			: __( 'No posts to sync', 'acf-sms' );

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: 1: number of posts synced, 2: breakdown by type */
					__( 'Successfully synced %1$d posts across network (%2$s)', 'acf-sms' ),
					$synced_total,
					$detailed_message
				),
				'count'         => $synced_total,
				'by_type'       => $synced_by_type,
			)
		);
	}

	/**
	 * AJAX handler for getting sync counts.
	 *
	 * @since 2.4.0
	 */
	public function ajax_get_sync_counts() {
		check_ajax_referer( 'acf_sms_network', 'nonce' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'acf-sms' ) ) );
		}

		// Get the multisite sync instance.
		if ( ! class_exists( 'ACF_Location_Shortcodes' ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin not initialized', 'acf-sms' ) ) );
		}
		
		$plugin = ACF_Location_Shortcodes::instance();
		if ( ! isset( $plugin->multisite_sync ) || ! is_object( $plugin->multisite_sync ) ) {
			wp_send_json_error( array( 'message' => __( 'Multisite sync not initialized. Is ACF active?', 'acf-sms' ) ) );
		}

		$sync = $plugin->multisite_sync;
		
		if ( ! method_exists( $sync, 'get_sync_post_types' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sync method not available', 'acf-sms' ) ) );
		}
		
		$sync_post_types = $sync->get_sync_post_types();
		
		$counts = array();
		$total_items = 0;
		$total_relationships = 0;
		$total_taxonomies = 0;
		$total_media = 0;
		
		// Ordered sync types.
		$ordered_types = array( 'location', 'service', 'condition', 'team-member' );
		
		// Relationship fields per post type.
		$relationship_fields = array(
			'location'    => 1, // team_members_assigned.
			'team-member' => 2, // location, specialties.
			'service'     => 0,
			'condition'   => 0,
		);
		
		// Taxonomies per post type.
		$taxonomy_fields = array(
			'service'     => 2, // service-category, service-tag.
			'team-member' => 1, // team-member-type.
			'location'    => 0,
			'condition'   => 0,
		);
		
		// Media per post type (estimate featured images + ACF image fields).
		$media_fields = array(
			'location'    => 1, // Featured image.
			'team-member' => 2, // Featured image + profile_picture.
			'service'     => 1, // Featured image.
			'condition'   => 1, // Featured image.
		);

		foreach ( $ordered_types as $post_type ) {
			if ( ! in_array( $post_type, $sync_post_types, true ) || ! post_type_exists( $post_type ) ) {
				$counts[ $post_type ] = 0;
				continue;
			}
			
			$count = wp_count_posts( $post_type );
			$type_total = 0;
			$type_total += isset( $count->publish ) ? intval( $count->publish ) : 0;
			$type_total += isset( $count->draft ) ? intval( $count->draft ) : 0;
			$type_total += isset( $count->pending ) ? intval( $count->pending ) : 0;
			$type_total += isset( $count->private ) ? intval( $count->private ) : 0;
			
			$counts[ $post_type ] = $type_total;
			$total_items += $type_total;
			
			// Calculate relationships, taxonomies, and media based on defined arrays.
			$rel_count = isset( $relationship_fields[ $post_type ] ) ? $relationship_fields[ $post_type ] : 0;
			$total_relationships += $type_total * $rel_count;
			
			$tax_count = isset( $taxonomy_fields[ $post_type ] ) ? $taxonomy_fields[ $post_type ] : 0;
			$total_taxonomies += $type_total * $tax_count;
			
			$media_count = isset( $media_fields[ $post_type ] ) ? $media_fields[ $post_type ] : 0;
			$total_media += $type_total * $media_count;
		}
		
		wp_send_json_success( array(
			'counts'        => $counts,
			'total_items'   => $total_items,
			'relationships' => $total_relationships,
			'taxonomies'    => $total_taxonomies,
			'media'         => $total_media,
		) );
	}

	/**
	 * AJAX handler for syncing a batch of posts.
	 *
	 * @since 2.4.0
	 */
	public function ajax_sync_batch() {
		check_ajax_referer( 'acf_sms_network', 'nonce' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'acf-sms' ) ) );
		}

		$post_type  = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : '';
		$offset     = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
		$batch_size = isset( $_POST['batch_size'] ) ? intval( $_POST['batch_size'] ) : 5;

		// Validate post type.
		if ( ! class_exists( 'ACF_Location_Shortcodes' ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin not initialized', 'acf-sms' ) ) );
		}
		
		$plugin = ACF_Location_Shortcodes::instance();
		if ( ! isset( $plugin->multisite_sync ) || ! is_object( $plugin->multisite_sync ) ) {
			wp_send_json_error( array( 'message' => __( 'Multisite sync not initialized. Is ACF active?', 'acf-sms' ) ) );
		}

		$sync = $plugin->multisite_sync;
		
		if ( ! method_exists( $sync, 'get_sync_post_types' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sync method not available', 'acf-sms' ) ) );
		}
		
		$sync_post_types = $sync->get_sync_post_types();
		
		if ( ! in_array( $post_type, $sync_post_types, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post type', 'acf-sms' ) ) );
		}

		// Get batch of posts.
		$posts = get_posts( array(
			'post_type'      => $post_type,
			'posts_per_page' => $batch_size,
			'offset'         => $offset,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		$synced_count = 0;
		$synced_items = array();
		$relationships_synced = 0;
		$taxonomies_synced = 0;
		$media_synced = 0;

		foreach ( $posts as $post ) {
			// Sync the post.
			$sync->sync_post_on_save( $post->ID, $post, true );
			$synced_count++;
			$synced_items[] = $post->post_title;
			
			// Track relationship syncs based on post type.
			if ( function_exists( 'get_field' ) ) {
				switch ( $post_type ) {
					case 'location':
						// team_members_assigned is a relationship field.
						$team_members = get_field( 'team_members_assigned', $post->ID );
						if ( ! empty( $team_members ) ) {
							$relationships_synced++;
						}
						break;
					case 'team-member':
						// location and specialties are relationship fields.
						$locations = get_field( 'location', $post->ID );
						if ( ! empty( $locations ) ) {
							$relationships_synced++;
						}
						$specialties = get_field( 'specialties', $post->ID );
						if ( ! empty( $specialties ) ) {
							$relationships_synced++;
						}
						break;
				}
				
				// Check for media fields.
				if ( has_post_thumbnail( $post->ID ) ) {
					$media_synced++;
				}
				
				// Check for profile_picture on team members.
				if ( $post_type === 'team-member' ) {
					$profile_pic = get_field( 'profile_picture', $post->ID );
					if ( ! empty( $profile_pic ) ) {
						$media_synced++;
					}
				}
			}
			
			// Track taxonomy syncs.
			$taxonomies = get_object_taxonomies( $post_type );
			foreach ( $taxonomies as $taxonomy ) {
				$terms = wp_get_object_terms( $post->ID, $taxonomy );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$taxonomies_synced++;
				}
			}
		}

		// Check if there are more posts.
		$total_count = wp_count_posts( $post_type );
		$total = 0;
		$total += isset( $total_count->publish ) ? $total_count->publish : 0;
		$total += isset( $total_count->draft ) ? $total_count->draft : 0;
		$total += isset( $total_count->pending ) ? $total_count->pending : 0;
		$total += isset( $total_count->private ) ? $total_count->private : 0;
		
		$has_more = ( $offset + $batch_size ) < $total;

		// Update last sync time.
		if ( ! $has_more ) {
			update_option( 'acf_sms_last_sync_time', current_time( 'timestamp' ) );
		}

		wp_send_json_success( array(
			'synced'               => $synced_count,
			'items'                => $synced_items,
			'has_more'             => $has_more,
			'relationships_synced' => $relationships_synced,
			'taxonomies_synced'    => $taxonomies_synced,
			'media_synced'         => $media_synced,
		) );
	}

	/**
	 * AJAX handler for testing profile picture sync.
	 * This is a diagnostic tool to help debug sync issues.
	 *
	 * @since 2.4.0
	 */
	public function ajax_test_profile_pic() {
		check_ajax_referer( 'acf_sms_network', 'nonce' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'acf-sms' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'No post ID provided', 'acf-sms' ) ) );
		}

		$debug_info = array();
		$master_site_id = get_main_site_id();
		
		// Get info from master site.
		switch_to_blog( $master_site_id );
		
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'team-member' ) {
			restore_current_blog();
			wp_send_json_error( array( 'message' => __( 'Post not found or not a team member', 'acf-sms' ) ) );
		}
		
		$debug_info['master_site'] = array(
			'site_id' => $master_site_id,
			'post_id' => $post_id,
			'post_title' => $post->post_title,
		);
		
		// Get all meta for this post.
		$all_meta = get_post_meta( $post_id );
		$profile_meta = array();
		foreach ( $all_meta as $key => $value ) {
			if ( stripos( $key, 'profile' ) !== false || stripos( $key, 'picture' ) !== false ) {
				$profile_meta[ $key ] = $value;
			}
		}
		$debug_info['master_site']['profile_meta'] = $profile_meta;
		
		// Get profile_picture value.
		$raw_value = get_post_meta( $post_id, 'profile_picture', true );
		$debug_info['master_site']['profile_picture_raw'] = $raw_value;
		$debug_info['master_site']['profile_picture_raw_type'] = gettype( $raw_value );
		
		// Get ACF field key.
		$field_key = get_post_meta( $post_id, '_profile_picture', true );
		$debug_info['master_site']['field_key'] = $field_key;
		
		// Get via ACF.
		if ( function_exists( 'get_field' ) ) {
			$acf_raw = get_field( 'profile_picture', $post_id, false );
			$acf_formatted = get_field( 'profile_picture', $post_id, true );
			$debug_info['master_site']['acf_raw'] = is_array( $acf_raw ) ? 'array:' . implode( ',', array_keys( $acf_raw ) ) : $acf_raw;
			$debug_info['master_site']['acf_formatted'] = is_array( $acf_formatted ) ? 'array:' . implode( ',', array_keys( $acf_formatted ) ) : $acf_formatted;
			
			if ( is_array( $acf_formatted ) && isset( $acf_formatted['ID'] ) ) {
				$debug_info['master_site']['attachment_id'] = $acf_formatted['ID'];
				$debug_info['master_site']['attachment_url'] = $acf_formatted['url'];
				
				// Check if attachment file exists.
				$file_path = get_attached_file( $acf_formatted['ID'] );
				$debug_info['master_site']['attachment_file'] = $file_path;
				$debug_info['master_site']['file_exists'] = file_exists( $file_path ) ? 'yes' : 'no';
			}
		}
		
		restore_current_blog();
		
		// Check subsites.
		$sites = get_sites( array( 'number' => 100 ) );
		$debug_info['subsites'] = array();
		
		foreach ( $sites as $site ) {
			if ( (int) $site->blog_id === $master_site_id ) {
				continue;
			}
			
			switch_to_blog( $site->blog_id );
			
			$site_info = array(
				'site_id' => $site->blog_id,
				'domain' => $site->domain,
				'path' => $site->path,
			);
			
			// Find synced post.
			global $wpdb;
			$synced_post_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} 
				WHERE meta_key = '_acf_sms_source_post' AND meta_value = %d
				LIMIT 1",
				$post_id
			) );
			
			if ( $synced_post_id ) {
				$site_info['synced_post_id'] = $synced_post_id;
				$site_info['profile_picture_raw'] = get_post_meta( $synced_post_id, 'profile_picture', true );
				$site_info['field_key'] = get_post_meta( $synced_post_id, '_profile_picture', true );
				
				if ( function_exists( 'get_field' ) ) {
					$acf_val = get_field( 'profile_picture', $synced_post_id, true );
					$site_info['acf_value'] = is_array( $acf_val ) ? 'array with ID: ' . ( $acf_val['ID'] ?? 'none' ) : $acf_val;
				}
				
				// Check if attachment exists.
				$attachment_id = get_post_meta( $synced_post_id, 'profile_picture', true );
				if ( $attachment_id && is_numeric( $attachment_id ) ) {
					$attachment = get_post( $attachment_id );
					$site_info['attachment_exists'] = $attachment ? 'yes' : 'no';
					if ( $attachment ) {
						$file_path = get_attached_file( $attachment_id );
						$site_info['attachment_file'] = $file_path;
						$site_info['file_exists'] = file_exists( $file_path ) ? 'yes' : 'no';
					}
				}
			} else {
				$site_info['synced_post_id'] = 'not found';
			}
			
			$debug_info['subsites'][] = $site_info;
			
			restore_current_blog();
		}
		
		wp_send_json_success( $debug_info );
	}

	/**
	 * AJAX handler for exporting comprehensive diagnostic data as JSON.
	 * Designed to be copy-pasted into AI assistants for debugging.
	 *
	 * @since 2.4.0
	 */
	public function ajax_export_diagnostics() {
		check_ajax_referer( 'acf_sms_network', 'nonce' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'acf-sms' ) ) );
		}

		$export = array(
			'_export_info' => array(
				'generated_at' => current_time( 'mysql' ),
				'plugin_version' => ACF_LS_VERSION,
				'wp_version' => get_bloginfo( 'version' ),
				'php_version' => phpversion(),
				'multisite' => is_multisite(),
			),
			'configuration' => array(),
			'sites' => array(),
			'team_members' => array(),
			'sync_relationships' => array(),
			'issues' => array(),
		);

		// Configuration.
		$master_site_id = (int) get_site_option( 'acf_sms_master_site', get_main_site_id() );
		$sync_sites = get_site_option( 'acf_sms_sync_sites', array() );
		$sync_enabled = get_site_option( 'acf_sms_sync_enabled', false );
		
		$export['configuration'] = array(
			'sync_enabled' => $sync_enabled,
			'master_site_id' => $master_site_id,
			'target_site_ids' => array_map( 'intval', $sync_sites ),
			'debug_mode' => defined( 'ACF_LS_DEBUG' ) && ACF_LS_DEBUG,
		);

		// Get all sites info.
		$all_sites = get_sites( array( 'number' => 100 ) );
		
		foreach ( $all_sites as $site ) {
			$site_id = (int) $site->blog_id;
			$site_details = get_blog_details( $site_id );
			$is_master = $site_id === $master_site_id;
			$is_target = in_array( $site_id, array_map( 'intval', $sync_sites ), true );
			
			switch_to_blog( $site_id );
			
			$site_data = array(
				'site_id' => $site_id,
				'name' => $site_details->blogname,
				'url' => $site_details->siteurl,
				'is_master' => $is_master,
				'is_sync_target' => $is_target,
				'acf_active' => class_exists( 'ACF' ) || function_exists( 'acf' ),
				'last_sync' => get_option( 'acf_sms_last_sync_time', null ),
				'last_sync_human' => null,
				'post_counts' => array(),
				'upload_path' => wp_upload_dir()['basedir'],
			);
			
			if ( $site_data['last_sync'] ) {
				$site_data['last_sync_human'] = human_time_diff( $site_data['last_sync'], current_time( 'timestamp' ) ) . ' ago';
			}
			
			// Post type counts.
			$post_types = array( 'location', 'team-member', 'service', 'condition' );
			foreach ( $post_types as $pt ) {
				if ( post_type_exists( $pt ) ) {
					$counts = wp_count_posts( $pt );
					$site_data['post_counts'][ $pt ] = array(
						'publish' => (int) ( $counts->publish ?? 0 ),
						'draft' => (int) ( $counts->draft ?? 0 ),
						'total' => (int) ( ( $counts->publish ?? 0 ) + ( $counts->draft ?? 0 ) ),
					);
				}
			}
			
			// Media counts.
			$media_counts = wp_count_posts( 'attachment' );
			$site_data['post_counts']['attachment'] = array(
				'total' => (int) ( $media_counts->inherit ?? 0 ),
			);
			
			// Synced items count.
			global $wpdb;
			$site_data['synced_posts_count'] = (int) $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_acf_sms_source_site'" 
			);
			$site_data['synced_media_count'] = (int) $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
				JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
				WHERE pm.meta_key = '_acf_sms_source_site' AND p.post_type = 'attachment'" 
			);
			
			$export['sites'][] = $site_data;
			
			restore_current_blog();
		}

		// Get all team members from master site with profile picture details.
		switch_to_blog( $master_site_id );
		
		$team_members = get_posts( array(
			'post_type' => 'team-member',
			'posts_per_page' => -1,
			'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
		) );
		
		foreach ( $team_members as $tm ) {
			$tm_data = array(
				'post_id' => $tm->ID,
				'title' => $tm->post_title,
				'status' => $tm->post_status,
				'profile_picture' => array(
					'meta_value' => get_post_meta( $tm->ID, 'profile_picture', true ),
					'field_key' => get_post_meta( $tm->ID, '_profile_picture', true ),
				),
				'synced_to' => array(),
			);
			
			// Check ACF value.
			if ( function_exists( 'get_field' ) ) {
				$acf_val = get_field( 'profile_picture', $tm->ID, false );
				$tm_data['profile_picture']['acf_raw'] = $acf_val;
				
				if ( is_numeric( $acf_val ) || ( is_array( $acf_val ) && isset( $acf_val['ID'] ) ) ) {
					$att_id = is_numeric( $acf_val ) ? $acf_val : $acf_val['ID'];
					$tm_data['profile_picture']['attachment_id'] = $att_id;
					$tm_data['profile_picture']['attachment_exists'] = get_post( $att_id ) ? true : false;
					
					if ( $tm_data['profile_picture']['attachment_exists'] ) {
						$file = get_attached_file( $att_id );
						$tm_data['profile_picture']['file_path'] = $file;
						$tm_data['profile_picture']['file_exists'] = file_exists( $file );
						$tm_data['profile_picture']['file_size'] = file_exists( $file ) ? filesize( $file ) : 0;
					}
				}
			}
			
			$export['team_members'][] = $tm_data;
		}
		
		restore_current_blog();

		// Check sync status on each target site for each team member.
		foreach ( $export['team_members'] as &$tm_data ) {
			foreach ( $sync_sites as $target_site_id ) {
				$target_site_id = (int) $target_site_id;
				if ( $target_site_id === $master_site_id ) continue;
				
				switch_to_blog( $target_site_id );
				
				global $wpdb;
				$synced_post_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} 
					WHERE meta_key = '_acf_sms_source_post' AND meta_value = %d
					LIMIT 1",
					$tm_data['post_id']
				) );
				
				$sync_info = array(
					'site_id' => $target_site_id,
					'synced_post_id' => $synced_post_id ? (int) $synced_post_id : null,
					'profile_picture' => null,
				);
				
				if ( $synced_post_id ) {
					$pic_meta = get_post_meta( $synced_post_id, 'profile_picture', true );
					$sync_info['profile_picture'] = array(
						'meta_value' => $pic_meta,
						'field_key' => get_post_meta( $synced_post_id, '_profile_picture', true ),
					);
					
					if ( is_numeric( $pic_meta ) && $pic_meta > 0 ) {
						$sync_info['profile_picture']['attachment_id'] = (int) $pic_meta;
						$sync_info['profile_picture']['attachment_exists'] = get_post( $pic_meta ) ? true : false;
						
						if ( $sync_info['profile_picture']['attachment_exists'] ) {
							$file = get_attached_file( $pic_meta );
							$sync_info['profile_picture']['file_path'] = $file;
							$sync_info['profile_picture']['file_exists'] = file_exists( $file );
						}
					}
				}
				
				$tm_data['synced_to'][] = $sync_info;
				
				restore_current_blog();
			}
		}
		unset( $tm_data );

		// Identify issues.
		foreach ( $export['team_members'] as $tm ) {
			// Master site issues.
			if ( ! empty( $tm['profile_picture']['attachment_id'] ) ) {
				if ( ! $tm['profile_picture']['attachment_exists'] ) {
					$export['issues'][] = array(
						'type' => 'master_attachment_missing',
						'severity' => 'error',
						'post_id' => $tm['post_id'],
						'title' => $tm['title'],
						'message' => 'Attachment ID ' . $tm['profile_picture']['attachment_id'] . ' does not exist on master site',
					);
				} elseif ( isset( $tm['profile_picture']['file_exists'] ) && ! $tm['profile_picture']['file_exists'] ) {
					$export['issues'][] = array(
						'type' => 'master_file_missing',
						'severity' => 'error',
						'post_id' => $tm['post_id'],
						'title' => $tm['title'],
						'message' => 'File missing on master: ' . basename( $tm['profile_picture']['file_path'] ?? 'unknown' ),
					);
				}
			}
			
			// Target site issues.
			foreach ( $tm['synced_to'] as $sync ) {
				if ( ! $sync['synced_post_id'] ) {
					$export['issues'][] = array(
						'type' => 'post_not_synced',
						'severity' => 'warning',
						'post_id' => $tm['post_id'],
						'title' => $tm['title'],
						'site_id' => $sync['site_id'],
						'message' => 'Team member not synced to site ' . $sync['site_id'],
					);
				} elseif ( ! empty( $tm['profile_picture']['attachment_id'] ) ) {
					// Master has profile pic, check if target has it.
					if ( empty( $sync['profile_picture']['meta_value'] ) ) {
						$export['issues'][] = array(
							'type' => 'profile_pic_not_synced',
							'severity' => 'error',
							'post_id' => $tm['post_id'],
							'title' => $tm['title'],
							'site_id' => $sync['site_id'],
							'synced_post_id' => $sync['synced_post_id'],
							'message' => 'Profile picture meta empty on target site ' . $sync['site_id'],
						);
					} elseif ( isset( $sync['profile_picture']['attachment_exists'] ) && ! $sync['profile_picture']['attachment_exists'] ) {
						$export['issues'][] = array(
							'type' => 'target_attachment_missing',
							'severity' => 'error',
							'post_id' => $tm['post_id'],
							'title' => $tm['title'],
							'site_id' => $sync['site_id'],
							'message' => 'Attachment ID ' . $sync['profile_picture']['attachment_id'] . ' does not exist on site ' . $sync['site_id'],
						);
					} elseif ( isset( $sync['profile_picture']['file_exists'] ) && ! $sync['profile_picture']['file_exists'] ) {
						$export['issues'][] = array(
							'type' => 'target_file_missing',
							'severity' => 'error',
							'post_id' => $tm['post_id'],
							'title' => $tm['title'],
							'site_id' => $sync['site_id'],
							'message' => 'File missing on site ' . $sync['site_id'],
						);
					}
				}
			}
		}

		// Summary.
		$export['summary'] = array(
			'total_team_members' => count( $export['team_members'] ),
			'with_profile_picture' => count( array_filter( $export['team_members'], function( $tm ) {
				return ! empty( $tm['profile_picture']['attachment_id'] );
			} ) ),
			'total_issues' => count( $export['issues'] ),
			'issues_by_type' => array_count_values( array_column( $export['issues'], 'type' ) ),
		);

		wp_send_json_success( $export );
	}

	/**
	 * AJAX handler for force syncing a profile picture.
	 *
	 * @since 2.4.0
	 */
	public function ajax_force_sync_profile_pic() {
		check_ajax_referer( 'acf_sms_network', 'nonce' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'acf-sms' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'No post ID provided', 'acf-sms' ) ) );
		}

		// Get the multisite sync instance.
		if ( ! class_exists( 'ACF_Location_Shortcodes' ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin not initialized', 'acf-sms' ) ) );
		}
		
		$plugin = ACF_Location_Shortcodes::instance();
		if ( ! isset( $plugin->multisite_sync ) || ! is_object( $plugin->multisite_sync ) ) {
			wp_send_json_error( array( 'message' => __( 'Multisite sync not initialized', 'acf-sms' ) ) );
		}

		$sync = $plugin->multisite_sync;
		
		if ( ! method_exists( $sync, 'force_sync_profile_picture' ) ) {
			wp_send_json_error( array( 'message' => __( 'Force sync method not available - update plugin', 'acf-sms' ) ) );
		}

		$results = $sync->force_sync_profile_picture( $post_id );
		
		wp_send_json_success( $results );
	}
}
