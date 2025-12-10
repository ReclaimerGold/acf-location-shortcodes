<?php
/**
 * Multisite Synchronization
 *
 * Synchronizes locations and team members across network sites.
 *
 * @package ACF_Location_Shortcodes
 * @since 2.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Multisite Sync class.
 *
 * @since 2.2.0
 */
class ACF_Location_Shortcodes_Multisite_Sync {

	/**
	 * ACF helpers instance.
	 *
	 * @var ACF_Location_Shortcodes_ACF_Helpers
	 */
	private $acf_helpers;

	/**
	 * Post types to sync.
	 *
	 * @var array
	 */
	private $sync_post_types = array( 'location', 'team-member' );

	/**
	 * Cache for synced attachment IDs.
	 *
	 * @var array
	 */
	private $attachment_id_cache = array();

	/**
	 * Whether sync is currently running (prevents infinite loops).
	 *
	 * @var bool
	 */
	private $sync_in_progress = false;

	/**
	 * Constructor.
	 *
	 * @since 2.2.0
	 * @param ACF_Location_Shortcodes_ACF_Helpers $acf_helpers ACF helpers instance.
	 */
	public function __construct( $acf_helpers ) {
		$this->acf_helpers = $acf_helpers;
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

		// Post save/update hooks.
		add_action( 'save_post', array( $this, 'sync_post_on_save' ), 20, 3 );
		add_action( 'before_delete_post', array( $this, 'sync_post_on_delete' ), 10, 2 );
		add_action( 'trashed_post', array( $this, 'sync_post_on_trash' ), 10, 2 );
		add_action( 'untrashed_post', array( $this, 'sync_post_on_untrash' ), 10, 2 );

		// Attachment hooks for media syncing.
		add_action( 'add_attachment', array( $this, 'sync_attachment_on_add' ), 10 );
		add_action( 'edit_attachment', array( $this, 'sync_attachment_on_edit' ), 10 );
		add_action( 'delete_attachment', array( $this, 'sync_attachment_on_delete' ), 10 );

		// AJAX handlers for manual sync.
		add_action( 'wp_ajax_acf_sms_manual_sync', array( $this, 'ajax_manual_sync' ) );
		add_action( 'wp_ajax_acf_sms_sync_status', array( $this, 'ajax_sync_status' ) );
		add_action( 'wp_ajax_acf_sms_push_to_master', array( $this, 'ajax_push_to_master' ) );
		add_action( 'wp_ajax_acf_sms_push_all_to_master', array( $this, 'ajax_push_all_to_master' ) );

		// Admin notices for slave sites.
		add_action( 'admin_notices', array( $this, 'show_sync_notices' ) );
		add_action( 'admin_notices', array( $this, 'show_dashboard_sync_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_sync_scripts' ) );
	}

	/**
	 * Sync post on save/update.
	 *
	 * @since 2.2.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 */
	public function sync_post_on_save( $post_id, $post, $update ) {
		// Check if sync is enabled.
		if ( ! $this->is_sync_enabled() ) {
			return;
		}

		// Prevent infinite loops.
		if ( $this->sync_in_progress ) {
			return;
		}

		// Only sync our post types.
		if ( ! in_array( $post->post_type, $this->sync_post_types, true ) ) {
			return;
		}

		// Don't sync revisions or autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Get current site and master site.
		$current_site_id = get_current_blog_id();
		$master_site_id  = $this->get_master_site();

		// Only auto-sync FROM master site.
		if ( $current_site_id !== $master_site_id ) {
			// Slave site - don't auto-sync (user must manually push).
			return;
		}

		// Get sync settings.
		$sync_sites = $this->get_sync_sites();
		if ( empty( $sync_sites ) ) {
			return;
		}

		// Log sync start.
		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Starting sync of %s (ID: %d) from MASTER to %d slave sites', $post->post_type, $post_id, count( $sync_sites ) ) );
		}

		// Set flag to prevent loops.
		$this->sync_in_progress = true;

		// Sync to each site.
		foreach ( $sync_sites as $site_id ) {
			// Skip current site.
			if ( (int) $site_id === $current_site_id ) {
				continue;
			}

			// Check if ACF is active on target site.
			if ( ! $this->is_acf_active_on_site( $site_id ) ) {
				if ( ACF_LS_DEBUG ) {
					error_log( sprintf( 'ACF SMS: Skipping site %d - ACF not active', $site_id ) );
				}
				continue;
			}

			// Switch to target site.
			switch_to_blog( $site_id );

			// Sync the post.
			$this->sync_post_to_current_site( $post_id, $post, $current_site_id );

			// Update last sync time for this site.
			update_option( 'acf_sms_last_sync_time', current_time( 'timestamp' ) );

			// Restore original site.
			restore_current_blog();
		}

		// Reset flag.
		$this->sync_in_progress = false;

		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Sync completed for %s (ID: %d)', $post->post_type, $post_id ) );
		}
	}

	/**
	 * Sync post to current site.
	 *
	 * @since 2.2.0
	 * @param int     $source_post_id Source post ID.
	 * @param WP_Post $source_post    Source post object.
	 * @param int     $source_site_id Source site ID.
	 */
	private function sync_post_to_current_site( $source_post_id, $source_post, $source_site_id ) {
		// Check if post already exists (by source meta).
		$existing_id = $this->get_synced_post_id( $source_post_id, $source_site_id );

		// If not found by meta, check for duplicate based on title AND post_name (slug).
		if ( ! $existing_id ) {
			$duplicate_id = $this->find_duplicate_post( $source_post->post_title, $source_post->post_name, $source_post->post_type );
			
			if ( $duplicate_id ) {
				if ( ACF_LS_DEBUG ) {
					error_log( sprintf( 
						'ACF SMS: Duplicate found for "%s" (slug: %s). Skipping sync to avoid duplicate.', 
						$source_post->post_title, 
						$source_post->post_name 
					) );
				}
				return; // Skip syncing this post - it's a duplicate.
			}
		}

		// Prepare post data.
		$post_data = array(
			'post_title'   => $source_post->post_title,
			'post_name'    => $source_post->post_name,
			'post_content' => $source_post->post_content,
			'post_excerpt' => $source_post->post_excerpt,
			'post_status'  => $source_post->post_status,
			'post_type'    => $source_post->post_type,
			'post_author'  => $source_post->post_author,
			'post_parent'  => 0, // Handle parent separately.
			'menu_order'   => $source_post->menu_order,
		);

		// Update or insert.
		if ( $existing_id ) {
			$post_data['ID'] = $existing_id;
			$synced_id       = wp_update_post( $post_data, true );
		} else {
			$synced_id = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $synced_id ) ) {
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Error syncing post %d: %s', $source_post_id, $synced_id->get_error_message() ) );
			}
			return;
		}

		// Store sync metadata.
		update_post_meta( $synced_id, '_acf_sms_source_site', $source_site_id );
		update_post_meta( $synced_id, '_acf_sms_source_post', $source_post_id );
		update_post_meta( $synced_id, '_acf_sms_last_sync', current_time( 'timestamp' ) );

		// Sync ACF fields.
		$this->sync_acf_fields( $source_post_id, $source_site_id, $synced_id );

		// Sync taxonomies.
		$this->sync_taxonomies( $source_post, $synced_id );

		// Sync featured image.
		$this->sync_featured_image( $source_post_id, $source_site_id, $synced_id );

		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Synced post %d to site %d as post %d', $source_post_id, get_current_blog_id(), $synced_id ) );
		}
	}

	/**
	 * Sync ACF fields from source to target.
	 *
	 * @since 2.2.0
	 * @param int $source_post_id Source post ID.
	 * @param int $source_site_id Source site ID.
	 * @param int $target_post_id Target post ID.
	 */
	private function sync_acf_fields( $source_post_id, $source_site_id, $target_post_id ) {
		// Switch to source site to get ACF data.
		switch_to_blog( $source_site_id );
		$acf_fields = get_fields( $source_post_id );
		$field_objects = array();
		
		// Get field objects to check field types.
		if ( function_exists( 'get_field_objects' ) ) {
			$field_objects = get_field_objects( $source_post_id );
		}
		
		restore_current_blog();

		if ( empty( $acf_fields ) ) {
			return;
		}

		// Update each field on target site.
		foreach ( $acf_fields as $field_name => $field_value ) {
			// Get field type if available.
			$field_type = '';
			if ( isset( $field_objects[ $field_name ]['type'] ) ) {
				$field_type = $field_objects[ $field_name ]['type'];
			}

			// Handle image/file fields - need to sync the attachment.
			if ( in_array( $field_type, array( 'image', 'file' ), true ) ) {
				if ( is_numeric( $field_value ) ) {
					// Single attachment ID.
					$field_value = $this->sync_attachment( $field_value, $source_site_id );
				} elseif ( is_array( $field_value ) && isset( $field_value['ID'] ) ) {
					// Attachment array format.
					$synced_id = $this->sync_attachment( $field_value['ID'], $source_site_id );
					if ( $synced_id ) {
						$field_value = $synced_id;
					}
				}
			} elseif ( $field_type === 'gallery' ) {
				// Gallery field - array of attachment IDs.
				if ( is_array( $field_value ) && ! empty( $field_value ) ) {
					$synced_ids = array();
					foreach ( $field_value as $attachment ) {
						if ( is_numeric( $attachment ) ) {
							$synced_id = $this->sync_attachment( $attachment, $source_site_id );
						} elseif ( is_array( $attachment ) && isset( $attachment['ID'] ) ) {
							$synced_id = $this->sync_attachment( $attachment['ID'], $source_site_id );
						} else {
							continue;
						}
						
						if ( $synced_id ) {
							$synced_ids[] = $synced_id;
						}
					}
					$field_value = $synced_ids;
				}
			} elseif ( is_array( $field_value ) && ! empty( $field_value ) ) {
				// Handle relationship fields (need to remap IDs).
				$first_item = reset( $field_value );
				if ( is_object( $first_item ) && isset( $first_item->ID ) ) {
					// This might be a relationship field - for now, skip remapping.
					// In future, could remap related post IDs across sites.
					continue;
				}
			}

			// Update field value.
			update_field( $field_name, $field_value, $target_post_id );
		}
	}

	/**
	 * Sync taxonomies from source to target.
	 *
	 * @since 2.2.0
	 * @param WP_Post $source_post    Source post object.
	 * @param int     $target_post_id Target post ID.
	 */
	private function sync_taxonomies( $source_post, $target_post_id ) {
		$taxonomies = get_object_taxonomies( $source_post->post_type );

		foreach ( $taxonomies as $taxonomy ) {
			// Get terms from source (we're already on source site context when this is called from sync_post_to_current_site).
			$source_site_id = get_post_meta( $target_post_id, '_acf_sms_source_site', true );
			$source_post_id = get_post_meta( $target_post_id, '_acf_sms_source_post', true );

			if ( $source_site_id ) {
				switch_to_blog( $source_site_id );
				$terms = wp_get_object_terms( $source_post_id, $taxonomy, array( 'fields' => 'names' ) );
				restore_current_blog();

				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					wp_set_object_terms( $target_post_id, $terms, $taxonomy, false );
				}
			}
		}
	}

	/**
	 * Sync featured image from source to target.
	 *
	 * @since 2.2.0
	 * @param int $source_post_id Source post ID.
	 * @param int $source_site_id Source site ID.
	 * @param int $target_post_id Target post ID.
	 */
	private function sync_featured_image( $source_post_id, $source_site_id, $target_post_id ) {
		// Get source thumbnail ID.
		switch_to_blog( $source_site_id );
		$source_thumb_id = get_post_thumbnail_id( $source_post_id );
		restore_current_blog();

		if ( ! $source_thumb_id ) {
			delete_post_thumbnail( $target_post_id );
			return;
		}

		// Sync the attachment and get the target site's attachment ID.
		$target_thumb_id = $this->sync_attachment( $source_thumb_id, $source_site_id );

		if ( $target_thumb_id ) {
			set_post_thumbnail( $target_post_id, $target_thumb_id );
		}
	}

	/**
	 * Sync post deletion.
	 *
	 * @since 2.2.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function sync_post_on_delete( $post_id, $post ) {
		if ( ! $this->is_sync_enabled() || $this->sync_in_progress ) {
			return;
		}

		if ( ! in_array( $post->post_type, $this->sync_post_types, true ) ) {
			return;
		}

		$this->sync_in_progress = true;
		$current_site_id        = get_current_blog_id();
		$sync_sites             = $this->get_sync_sites();

		foreach ( $sync_sites as $site_id ) {
			if ( (int) $site_id === $current_site_id ) {
				continue;
			}

			switch_to_blog( $site_id );
			$synced_id = $this->get_synced_post_id( $post_id, $current_site_id );
			if ( $synced_id ) {
				wp_delete_post( $synced_id, true );
			}
			restore_current_blog();
		}

		$this->sync_in_progress = false;
	}

	/**
	 * Sync post trash.
	 *
	 * @since 2.2.0
	 * @param int    $post_id Post ID.
	 * @param string $previous_status Previous post status.
	 */
	public function sync_post_on_trash( $post_id, $previous_status ) {
		$post = get_post( $post_id );
		if ( ! $post || ! $this->is_sync_enabled() || $this->sync_in_progress ) {
			return;
		}

		if ( ! in_array( $post->post_type, $this->sync_post_types, true ) ) {
			return;
		}

		$this->sync_in_progress = true;
		$current_site_id        = get_current_blog_id();
		$sync_sites             = $this->get_sync_sites();

		foreach ( $sync_sites as $site_id ) {
			if ( (int) $site_id === $current_site_id ) {
				continue;
			}

			switch_to_blog( $site_id );
			$synced_id = $this->get_synced_post_id( $post_id, $current_site_id );
			if ( $synced_id ) {
				wp_trash_post( $synced_id );
			}
			restore_current_blog();
		}

		$this->sync_in_progress = false;
	}

	/**
	 * Sync post untrash.
	 *
	 * @since 2.2.0
	 * @param int    $post_id Post ID.
	 * @param string $previous_status Previous post status.
	 */
	public function sync_post_on_untrash( $post_id, $previous_status ) {
		$post = get_post( $post_id );
		if ( ! $post || ! $this->is_sync_enabled() || $this->sync_in_progress ) {
			return;
		}

		if ( ! in_array( $post->post_type, $this->sync_post_types, true ) ) {
			return;
		}

		$this->sync_in_progress = true;
		$current_site_id        = get_current_blog_id();
		$sync_sites             = $this->get_sync_sites();

		foreach ( $sync_sites as $site_id ) {
			if ( (int) $site_id === $current_site_id ) {
				continue;
			}

			switch_to_blog( $site_id );
			$synced_id = $this->get_synced_post_id( $post_id, $current_site_id );
			if ( $synced_id ) {
				wp_untrash_post( $synced_id );
			}
			restore_current_blog();
		}

		$this->sync_in_progress = false;
	}

	/**
	 * Sync attachment when added.
	 *
	 * @since 2.2.0
	 * @param int $attachment_id Attachment ID.
	 */
	public function sync_attachment_on_add( $attachment_id ) {
		if ( ! $this->is_sync_enabled() || $this->sync_in_progress ) {
			return;
		}

		$current_site_id = get_current_blog_id();
		$master_site_id  = $this->get_master_site();

		// Only auto-sync FROM master site.
		if ( $current_site_id !== $master_site_id ) {
			return;
		}

		$sync_sites = $this->get_sync_sites();
		if ( empty( $sync_sites ) ) {
			return;
		}

		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Starting sync of attachment (ID: %d) from MASTER', $attachment_id ) );
		}

		$this->sync_in_progress = true;

		foreach ( $sync_sites as $site_id ) {
			if ( (int) $site_id === $current_site_id ) {
				continue;
			}

			if ( ! $this->is_acf_active_on_site( $site_id ) ) {
				continue;
			}

			switch_to_blog( $site_id );
			$this->sync_attachment( $attachment_id, $current_site_id );
			restore_current_blog();
		}

		$this->sync_in_progress = false;

		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Completed sync of attachment (ID: %d)', $attachment_id ) );
		}
	}

	/**
	 * Sync attachment when edited.
	 *
	 * @since 2.2.0
	 * @param int $attachment_id Attachment ID.
	 */
	public function sync_attachment_on_edit( $attachment_id ) {
		// Use same logic as add.
		$this->sync_attachment_on_add( $attachment_id );
	}

	/**
	 * Sync attachment deletion.
	 *
	 * @since 2.2.0
	 * @param int $attachment_id Attachment ID.
	 */
	public function sync_attachment_on_delete( $attachment_id ) {
		if ( ! $this->is_sync_enabled() || $this->sync_in_progress ) {
			return;
		}

		$current_site_id = get_current_blog_id();
		$master_site_id  = $this->get_master_site();

		// Only auto-sync FROM master site.
		if ( $current_site_id !== $master_site_id ) {
			return;
		}

		$sync_sites = $this->get_sync_sites();
		if ( empty( $sync_sites ) ) {
			return;
		}

		$this->sync_in_progress = true;

		foreach ( $sync_sites as $site_id ) {
			if ( (int) $site_id === $current_site_id ) {
				continue;
			}

			switch_to_blog( $site_id );
			$synced_id = $this->get_synced_attachment_id( $attachment_id, $current_site_id );
			if ( $synced_id ) {
				wp_delete_attachment( $synced_id, true );
			}
			restore_current_blog();
		}

		$this->sync_in_progress = false;
	}

	/**
	 * Sync an attachment (media file) from source site to current site.
	 *
	 * @since 2.2.0
	 * @param int $source_attachment_id Source attachment ID.
	 * @param int $source_site_id       Source site ID.
	 * @return int|false Target attachment ID or false on failure.
	 */
	private function sync_attachment( $source_attachment_id, $source_site_id ) {
		if ( ! $source_attachment_id ) {
			return false;
		}

		// Check cache first.
		$cache_key = $source_site_id . '_' . $source_attachment_id;
		if ( isset( $this->attachment_id_cache[ $cache_key ] ) ) {
			return $this->attachment_id_cache[ $cache_key ];
		}

		// Check if attachment already exists on current site.
		$existing_id = $this->get_synced_attachment_id( $source_attachment_id, $source_site_id );

		if ( $existing_id ) {
			// Check if we need to update it.
			switch_to_blog( $source_site_id );
			$source_modified = get_post_field( 'post_modified', $source_attachment_id );
			restore_current_blog();

			$target_last_sync = get_post_meta( $existing_id, '_acf_sms_last_sync', true );
			$source_modified_time = strtotime( $source_modified );

			// If source was modified after last sync, re-sync.
			if ( ! $target_last_sync || $source_modified_time > $target_last_sync ) {
				$target_id = $this->copy_attachment_to_current_site( $source_attachment_id, $source_site_id, $existing_id );
			} else {
				$target_id = $existing_id;
			}
		} else {
			// Create new attachment.
			$target_id = $this->copy_attachment_to_current_site( $source_attachment_id, $source_site_id );
		}

		// Cache the result.
		if ( $target_id ) {
			$this->attachment_id_cache[ $cache_key ] = $target_id;
		}

		return $target_id;
	}

	/**
	 * Copy attachment file and metadata to current site.
	 *
	 * @since 2.2.0
	 * @param int      $source_attachment_id Source attachment ID.
	 * @param int      $source_site_id       Source site ID.
	 * @param int|null $existing_id          Existing attachment ID to update (optional).
	 * @return int|false Target attachment ID or false on failure.
	 */
	private function copy_attachment_to_current_site( $source_attachment_id, $source_site_id, $existing_id = null ) {
		// Get source attachment data.
		switch_to_blog( $source_site_id );
		
		$source_file = get_attached_file( $source_attachment_id );
		$source_post = get_post( $source_attachment_id );
		$source_metadata = wp_get_attachment_metadata( $source_attachment_id );
		
		if ( ! $source_file || ! file_exists( $source_file ) || ! $source_post ) {
			restore_current_blog();
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Source attachment file not found: %d', $source_attachment_id ) );
			}
			return false;
		}

		// Get the file name.
		$filename = basename( $source_file );
		
		restore_current_blog();

		// Get upload directory info for target site.
		$upload_dir = wp_upload_dir();
		
		if ( $upload_dir['error'] ) {
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Upload directory error: %s', $upload_dir['error'] ) );
			}
			return false;
		}

		// Generate unique filename if needed.
		$target_file = $upload_dir['path'] . '/' . $filename;
		$target_file = wp_unique_filename( $upload_dir['path'], $filename );
		$target_file_path = $upload_dir['path'] . '/' . $target_file;

		// Copy the file.
		switch_to_blog( $source_site_id );
		$source_file = get_attached_file( $source_attachment_id );
		$copied = copy( $source_file, $target_file_path );
		restore_current_blog();

		if ( ! $copied ) {
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Failed to copy file from %s to %s', $source_file, $target_file_path ) );
			}
			return false;
		}

		// Get file type.
		$file_type = wp_check_filetype( $target_file_path );

		// Prepare attachment data.
		$attachment_data = array(
			'post_title'     => $source_post->post_title,
			'post_content'   => $source_post->post_content,
			'post_excerpt'   => $source_post->post_excerpt,
			'post_status'    => 'inherit',
			'post_mime_type' => $file_type['type'],
		);

		if ( $existing_id ) {
			// Update existing attachment.
			$attachment_data['ID'] = $existing_id;
			$attachment_id = wp_update_post( $attachment_data );
			
			// Update the attached file path.
			update_attached_file( $existing_id, $target_file_path );
		} else {
			// Insert new attachment.
			$attachment_id = wp_insert_attachment( $attachment_data, $target_file_path );
		}

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			// Clean up copied file.
			unlink( $target_file_path );
			if ( ACF_LS_DEBUG ) {
				$error_msg = is_wp_error( $attachment_id ) ? $attachment_id->get_error_message() : 'Unknown error';
				error_log( sprintf( 'ACF SMS: Failed to insert attachment: %s', $error_msg ) );
			}
			return false;
		}

		// Generate attachment metadata.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $target_file_path );
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		// Store sync metadata.
		update_post_meta( $attachment_id, '_acf_sms_source_site', $source_site_id );
		update_post_meta( $attachment_id, '_acf_sms_source_post', $source_attachment_id );
		update_post_meta( $attachment_id, '_acf_sms_last_sync', current_time( 'timestamp' ) );

		// Copy alt text and other meta.
		switch_to_blog( $source_site_id );
		$alt_text = get_post_meta( $source_attachment_id, '_wp_attachment_image_alt', true );
		restore_current_blog();
		
		if ( $alt_text ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		}

		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Synced attachment %d to %d on site %d', $source_attachment_id, $attachment_id, get_current_blog_id() ) );
		}

		return $attachment_id;
	}

	/**
	 * Get synced attachment ID on current site.
	 *
	 * @since 2.2.0
	 * @param int $source_attachment_id Source attachment ID.
	 * @param int $source_site_id       Source site ID.
	 * @return int|false Synced attachment ID or false if not found.
	 */
	private function get_synced_attachment_id( $source_attachment_id, $source_site_id ) {
		global $wpdb;

		$attachment_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} 
				WHERE meta_key = '_acf_sms_source_post' 
				AND meta_value = %d
				AND post_id IN (
					SELECT post_id FROM {$wpdb->postmeta}
					WHERE meta_key = '_acf_sms_source_site'
					AND meta_value = %d
				)
				AND post_id IN (
					SELECT ID FROM {$wpdb->posts}
					WHERE post_type = 'attachment'
				)
				LIMIT 1",
				$source_attachment_id,
				$source_site_id
			)
		);

		return $attachment_id ? (int) $attachment_id : false;
	}

	/**
	 * Get synced post ID on current site.
	 *
	 * @since 2.2.0
	 * @param int $source_post_id Source post ID.
	 * @param int $source_site_id Source site ID.
	 * @return int|false Synced post ID or false if not found.
	 */
	private function get_synced_post_id( $source_post_id, $source_site_id ) {
		global $wpdb;

		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} 
				WHERE meta_key = '_acf_sms_source_post' 
				AND meta_value = %d
				AND post_id IN (
					SELECT post_id FROM {$wpdb->postmeta}
					WHERE meta_key = '_acf_sms_source_site'
					AND meta_value = %d
				)
				LIMIT 1",
				$source_post_id,
				$source_site_id
			)
		);

		return $post_id ? (int) $post_id : false;
	}

	/**
	 * Find duplicate post based on title AND post_name (slug).
	 * Both must match for it to be considered a duplicate.
	 *
	 * @since 2.2.0
	 * @param string $post_title Post title.
	 * @param string $post_name  Post slug/name.
	 * @param string $post_type  Post type.
	 * @return int|false Post ID if duplicate found, false otherwise.
	 */
	private function find_duplicate_post( $post_title, $post_name, $post_type ) {
		global $wpdb;

		// Both title AND post_name must match for it to be a duplicate.
		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} 
				WHERE post_title = %s 
				AND post_name = %s 
				AND post_type = %s 
				AND post_status != 'trash'
				LIMIT 1",
				$post_title,
				$post_name,
				$post_type
			)
		);

		return $post_id ? (int) $post_id : false;
	}

	/**
	 * Check if sync is enabled.
	 *
	 * @since 2.2.0
	 * @return bool True if sync is enabled.
	 */
	private function is_sync_enabled() {
		// Check network-wide setting.
		$enabled = get_site_option( 'acf_sms_sync_enabled', false );
		
		// Allow filtering per site.
		return apply_filters( 'acf_sms_sync_enabled', $enabled );
	}

	/**
	 * Get sites to sync to.
	 *
	 * @since 2.2.0
	 * @return array Array of site IDs.
	 */
	private function get_sync_sites() {
		$sync_sites = get_site_option( 'acf_sms_sync_sites', array() );

		// If empty, sync to all sites.
		if ( empty( $sync_sites ) ) {
			$sites      = get_sites( array( 'number' => 1000 ) );
			$sync_sites = wp_list_pluck( $sites, 'blog_id' );
		}

		return apply_filters( 'acf_sms_sync_sites', $sync_sites );
	}

	/**
	 * Get master site ID.
	 *
	 * @since 2.2.0
	 * @return int Master site ID.
	 */
	private function get_master_site() {
		$master_site = get_site_option( 'acf_sms_master_site', get_main_site_id() );
		return (int) $master_site;
	}

	/**
	 * Check if current site is the master site.
	 *
	 * @since 2.2.0
	 * @return bool True if current site is master.
	 */
	public function is_master_site() {
		return get_current_blog_id() === $this->get_master_site();
	}

	/**
	 * Check if ACF is active on a site.
	 *
	 * @since 2.2.0
	 * @param int $site_id Site ID.
	 * @return bool True if ACF is active.
	 */
	private function is_acf_active_on_site( $site_id ) {
		switch_to_blog( $site_id );
		$is_active = class_exists( 'ACF' ) || function_exists( 'acf' );
		restore_current_blog();

		return $is_active;
	}

	/**
	 * AJAX handler for manual sync.
	 *
	 * @since 2.2.0
	 */
	public function ajax_manual_sync() {
		check_ajax_referer( 'acf_sms_admin', 'nonce' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'acf-sms' ) ) );
		}

		$post_type = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : '';
		
		if ( ! in_array( $post_type, $this->sync_post_types, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post type', 'acf-sms' ) ) );
		}

		// Get all posts of this type.
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		$synced = 0;
		foreach ( $posts as $post ) {
			$this->sync_post_on_save( $post->ID, $post, true );
			$synced++;
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of posts synced */
					__( 'Successfully synced %d posts', 'acf-sms' ),
					$synced
				),
				'count'   => $synced,
			)
		);
	}

	/**
	 * AJAX handler for sync status.
	 *
	 * @since 2.2.0
	 */
	public function ajax_sync_status() {
		check_ajax_referer( 'acf_sms_admin', 'nonce' );

		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'acf-sms' ) ) );
		}

		$stats = array(
			'enabled'   => $this->is_sync_enabled(),
			'sites'     => count( $this->get_sync_sites() ),
			'locations' => wp_count_posts( 'location' )->publish,
			'members'   => wp_count_posts( 'team-member' )->publish,
		);

		wp_send_json_success( $stats );
	}

	/**
	 * AJAX handler for pushing post to master site.
	 *
	 * @since 2.2.0
	 */
	public function ajax_push_to_master() {
		check_ajax_referer( 'acf_sms_push', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'acf-sms' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID', 'acf-sms' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, $this->sync_post_types, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post', 'acf-sms' ) ) );
		}

		// Get master site.
		$master_site_id  = $this->get_master_site();
		$current_site_id = get_current_blog_id();

		if ( $current_site_id === $master_site_id ) {
			wp_send_json_error( array( 'message' => __( 'Already on master site', 'acf-sms' ) ) );
		}

		// Push to master.
		$this->sync_in_progress = true;

		switch_to_blog( $master_site_id );
		$this->sync_post_to_current_site( $post_id, $post, $current_site_id );
		restore_current_blog();

		$this->sync_in_progress = false;

		wp_send_json_success( array( 'message' => __( 'Successfully pushed to master site', 'acf-sms' ) ) );
	}

	/**
	 * AJAX handler for pushing all out-of-sync posts to master site.
	 *
	 * @since 2.2.0
	 */
	public function ajax_push_all_to_master() {
		check_ajax_referer( 'acf_sms_push_all', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'acf-sms' ) ) );
		}

		// Get master site.
		$master_site_id  = $this->get_master_site();
		$current_site_id = get_current_blog_id();

		if ( $current_site_id === $master_site_id ) {
			wp_send_json_error( array( 'message' => __( 'Already on master site', 'acf-sms' ) ) );
		}

		$this->sync_in_progress = true;
		$synced_count = 0;

		foreach ( $this->sync_post_types as $post_type ) {
			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => -1,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				)
			);

			foreach ( $posts as $post ) {
				$source_site = get_post_meta( $post->ID, '_acf_sms_source_site', true );
				$last_sync   = get_post_meta( $post->ID, '_acf_sms_last_sync', true );

				// Check if this post needs to be synced.
				$needs_sync = false;

				if ( $source_site && $source_site == $master_site_id ) {
					// Post from master - check if modified.
					$modified_time = strtotime( $post->post_modified );
					if ( $last_sync && $modified_time > $last_sync ) {
						$needs_sync = true;
					}
				} elseif ( ! $source_site ) {
					// Post created locally.
					$needs_sync = true;
				}

				if ( $needs_sync ) {
					switch_to_blog( $master_site_id );
					$this->sync_post_to_current_site( $post->ID, $post, $current_site_id );
					restore_current_blog();
					$synced_count++;
				}
			}
		}

		$this->sync_in_progress = false;

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of posts synced */
					_n( 'Successfully synced %d post to master', 'Successfully synced %d posts to master', $synced_count, 'acf-sms' ),
					$synced_count
				),
				'count'   => $synced_count,
			)
		);
	}

	/**
	 * Show sync notices on post edit screens for slave sites.
	 *
	 * @since 2.2.0
	 */
	public function show_sync_notices() {
		// Only show on post edit screens.
		$screen = get_current_screen();
		if ( ! $screen || $screen->base !== 'post' || ! in_array( $screen->post_type, $this->sync_post_types, true ) ) {
			return;
		}

		// Only show if sync is enabled.
		if ( ! $this->is_sync_enabled() ) {
			return;
		}

		// Only show on slave sites.
		if ( $this->is_master_site() ) {
			return;
		}

		// Get current post.
		global $post;
		if ( ! $post ) {
			return;
		}

		// Check if this post exists on master.
		$master_site_id  = $this->get_master_site();
		$current_site_id = get_current_blog_id();

		// Check if this post came FROM master (has sync metadata).
		$source_site = get_post_meta( $post->ID, '_acf_sms_source_site', true );
		$last_sync   = get_post_meta( $post->ID, '_acf_sms_last_sync', true );

		if ( $source_site && $source_site == $master_site_id ) {
			// This post came from master - check if it's outdated.
			$modified_time = strtotime( $post->post_modified );
			
			if ( $last_sync && $modified_time > $last_sync ) {
				// Local copy has been modified since last sync.
				?>
				<div class="notice notice-warning is-dismissible acf-sms-sync-notice">
					<p>
						<strong><?php esc_html_e( 'Sync Notice:', 'acf-sms' ); ?></strong>
						<?php esc_html_e( 'This post has local modifications that are not reflected on the master database.', 'acf-sms' ); ?>
					</p>
					<p>
						<button type="button" class="button button-primary acf-sms-push-to-master" data-post-id="<?php echo esc_attr( $post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'acf_sms_push' ) ); ?>">
							<?php esc_html_e( 'Update Master Database with Local Changes', 'acf-sms' ); ?>
						</button>
						<span class="spinner" style="float: none; margin: 0 10px;"></span>
					</p>
				</div>
				<?php
			}
		} else {
			// This post was created locally - doesn't exist on master.
			?>
			<div class="notice notice-info is-dismissible acf-sms-sync-notice">
				<p>
					<strong><?php esc_html_e( 'Sync Notice:', 'acf-sms' ); ?></strong>
					<?php esc_html_e( 'This post was created on this site and does not exist on the master database.', 'acf-sms' ); ?>
				</p>
				<p>
					<button type="button" class="button button-primary acf-sms-push-to-master" data-post-id="<?php echo esc_attr( $post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'acf_sms_push' ) ); ?>">
						<?php esc_html_e( 'Push to Master Database', 'acf-sms' ); ?>
					</button>
					<span class="spinner" style="float: none; margin: 0 10px;"></span>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Show dashboard sync status notice.
	 *
	 * @since 2.2.0
	 */
	public function show_dashboard_sync_notice() {
		// Only show if user can edit posts.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Only show if sync is enabled.
		if ( ! $this->is_sync_enabled() ) {
			return;
		}

		// Only show on slave sites.
		if ( $this->is_master_site() ) {
			return;
		}

		// Check if this site is enabled for syncing.
		$current_site_id = get_current_blog_id();
		$sync_sites      = get_site_option( 'acf_sms_sync_sites', array() );
		
		// If sync_sites is not empty and current site is not in the list, don't show notice.
		if ( ! empty( $sync_sites ) && ! in_array( $current_site_id, $sync_sites, true ) ) {
			return;
		}

		// Check current screen - show on dashboard and main admin pages.
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->base, array( 'dashboard', 'edit' ), true ) ) {
			return;
		}

		// Get sync status.
		$out_of_sync = $this->get_out_of_sync_posts();
		$total_count = $out_of_sync['modified_count'] + $out_of_sync['new_count'];

		// Get master site name for display.
		$master_site_id = $this->get_master_site();
		switch_to_blog( $master_site_id );
		$master_site_name = get_bloginfo( 'name' );
		restore_current_blog();

		if ( $total_count > 0 ) {
			// Group posts by type.
			$modified_by_type = array();
			$new_by_type      = array();

			foreach ( $out_of_sync['modified_posts'] as $post ) {
				$type_label = $this->get_post_type_label( $post['type'] );
				if ( ! isset( $modified_by_type[ $type_label ] ) ) {
					$modified_by_type[ $type_label ] = array();
				}
				$modified_by_type[ $type_label ][] = $post;
			}

			foreach ( $out_of_sync['new_posts'] as $post ) {
				$type_label = $this->get_post_type_label( $post['type'] );
				if ( ! isset( $new_by_type[ $type_label ] ) ) {
					$new_by_type[ $type_label ] = array();
				}
				$new_by_type[ $type_label ][] = $post;
			}

			// Out of sync - show warning with CSV format for post names.
			?>
			<div class="notice notice-warning is-dismissible acf-sms-dashboard-notice">
				<p>
					<strong><?php esc_html_e( 'Network Resources Out of Sync:', 'acf-sms' ); ?></strong>
					<?php
					printf(
						/* translators: 1: number of posts out of sync, 2: master site name */
						esc_html( _n( '%1$d item needs to be synced to %2$s.', '%1$d items need to be synced to %2$s.', $total_count, 'acf-sms' ) ),
						$total_count,
						'<strong>' . esc_html( $master_site_name ) . '</strong>'
					);
					?>
				</p>

				<?php if ( ! empty( $modified_by_type ) ) : ?>
					<p><strong><?php esc_html_e( 'Modified Resources (from master):', 'acf-sms' ); ?></strong></p>
					<?php foreach ( $modified_by_type as $type_label => $posts ) : ?>
						<p style="margin-left: 20px; margin-top: 5px;">
							<strong><?php echo esc_html( $type_label ); ?></strong> (<?php echo esc_html( count( $posts ) ); ?>):
							<?php
							$post_links = array();
							foreach ( $posts as $post ) {
								$post_links[] = '<a href="' . esc_url( $post['edit_url'] ) . '">' . esc_html( $post['title'] ) . '</a>';
							}
							echo implode( ', ', $post_links );
							?>
						</p>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if ( ! empty( $new_by_type ) ) : ?>
					<p><strong><?php esc_html_e( 'New Resources (created locally):', 'acf-sms' ); ?></strong></p>
					<?php foreach ( $new_by_type as $type_label => $posts ) : ?>
						<p style="margin-left: 20px; margin-top: 5px;">
							<strong><?php echo esc_html( $type_label ); ?></strong> (<?php echo esc_html( count( $posts ) ); ?>):
							<?php
							$post_links = array();
							foreach ( $posts as $post ) {
								$post_links[] = '<a href="' . esc_url( $post['edit_url'] ) . '">' . esc_html( $post['title'] ) . '</a>';
							}
							echo implode( ', ', $post_links );
							?>
						</p>
					<?php endforeach; ?>
				<?php endif; ?>

				<p>
					<button type="button" class="button button-primary acf-sms-push-all-to-master" data-nonce="<?php echo esc_attr( wp_create_nonce( 'acf_sms_push_all' ) ); ?>">
						<?php esc_html_e( 'Sync All to Master Database', 'acf-sms' ); ?>
					</button>
					<span class="spinner" style="float: none; margin: 0 10px;"></span>
				</p>
			</div>
			<?php
		} else {
			// In sync - show success.
			?>
			<div class="notice notice-success is-dismissible acf-sms-dashboard-notice">
				<p>
					<span class="dashicons dashicons-yes-alt" style="color: #46b450; vertical-align: middle;"></span>
					<strong><?php esc_html_e( 'Network Resources in Sync', 'acf-sms' ); ?></strong>
					<?php
					printf(
						/* translators: %s: master site name */
						esc_html__( '(with %s)', 'acf-sms' ),
						esc_html( $master_site_name )
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Get post type label (singular).
	 *
	 * @since 2.2.0
	 * @param string $post_type Post type slug.
	 * @return string Post type label.
	 */
	private function get_post_type_label( $post_type ) {
		$post_type_obj = get_post_type_object( $post_type );
		if ( $post_type_obj ) {
			return $post_type_obj->labels->singular_name;
		}
		return ucfirst( $post_type );
	}

	/**
	 * Get posts that are out of sync with master.
	 *
	 * @since 2.2.0
	 * @return array Array with modified_count, new_count, modified_posts, and new_posts.
	 */
	private function get_out_of_sync_posts() {
		$master_site_id = $this->get_master_site();
		$modified_count = 0;
		$new_count      = 0;
		$modified_posts = array();
		$new_posts      = array();

		foreach ( $this->sync_post_types as $post_type ) {
			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => -1,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				)
			);

			foreach ( $posts as $post ) {
				$source_site = get_post_meta( $post->ID, '_acf_sms_source_site', true );
				$last_sync   = get_post_meta( $post->ID, '_acf_sms_last_sync', true );

				if ( $source_site && $source_site == $master_site_id ) {
					// Post from master - check if modified.
					$modified_time = strtotime( $post->post_modified );
					if ( $last_sync && $modified_time > $last_sync ) {
						$modified_count++;
						$modified_posts[] = array(
							'id'        => $post->ID,
							'title'     => $post->post_title,
							'type'      => $post->post_type,
							'edit_url'  => get_edit_post_link( $post->ID ),
						);
					}
				} elseif ( ! $source_site ) {
					// Post created locally.
					$new_count++;
					$new_posts[] = array(
						'id'        => $post->ID,
						'title'     => $post->post_title,
						'type'      => $post->post_type,
						'edit_url'  => get_edit_post_link( $post->ID ),
					);
				}
			}
		}

		return array(
			'modified_count' => $modified_count,
			'new_count'      => $new_count,
			'modified_posts' => $modified_posts,
			'new_posts'      => $new_posts,
		);
	}

	/**
	 * Enqueue sync scripts for admin.
	 *
	 * @since 2.2.0
	 */
	public function enqueue_sync_scripts( $hook ) {
		// Only on post edit screens and dashboard.
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'index.php', 'edit.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		
		// Check if we need the scripts (on dashboard or on our post types).
		$needs_scripts = false;
		if ( in_array( $hook, array( 'index.php', 'edit.php' ), true ) ) {
			$needs_scripts = true;
		} elseif ( $screen && in_array( $screen->post_type, $this->sync_post_types, true ) ) {
			$needs_scripts = true;
		}
		
		if ( ! $needs_scripts ) {
			return;
		}

		wp_add_inline_script( 'jquery', "
			jQuery(document).ready(function($) {
				// Handle single post push to master
				$('.acf-sms-push-to-master').on('click', function(e) {
					e.preventDefault();
					
					var \$button = $(this);
					var \$spinner = \$button.siblings('.spinner');
					var postId = \$button.data('post-id');
					var nonce = \$button.data('nonce');
					
					if (!confirm('" . esc_js( __( 'This will update the master database with the current data from this site. Continue?', 'acf-sms' ) ) . "')) {
						return;
					}
					
					\$button.prop('disabled', true);
					\$spinner.addClass('is-active');
					
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'acf_sms_push_to_master',
							post_id: postId,
							nonce: nonce
						},
						success: function(response) {
							\$spinner.removeClass('is-active');
							if (response.success) {
								\$button.closest('.notice').removeClass('notice-warning notice-info').addClass('notice-success');
								\$button.closest('.notice').find('p:first-child').html('<strong>" . esc_js( __( 'Success!', 'acf-sms' ) ) . "</strong> ' + response.data.message + '');
								\$button.remove();
								setTimeout(function() {
									location.reload();
								}, 2000);
							} else {
								alert('" . esc_js( __( 'Error:', 'acf-sms' ) ) . " ' + response.data.message);
								\$button.prop('disabled', false);
							}
						},
						error: function() {
							\$spinner.removeClass('is-active');
							alert('" . esc_js( __( 'An error occurred', 'acf-sms' ) ) . "');
							\$button.prop('disabled', false);
						}
					});
				});

				// Handle push all to master
				$('.acf-sms-push-all-to-master').on('click', function(e) {
					e.preventDefault();
					
					var \$button = $(this);
					var \$spinner = \$button.siblings('.spinner');
					var nonce = \$button.data('nonce');
					
					if (!confirm('" . esc_js( __( 'This will sync all out-of-sync posts to the master database. This may take a while. Continue?', 'acf-sms' ) ) . "')) {
						return;
					}
					
					\$button.prop('disabled', true);
					\$spinner.addClass('is-active');
					
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'acf_sms_push_all_to_master',
							nonce: nonce
						},
						success: function(response) {
							\$spinner.removeClass('is-active');
							if (response.success) {
								\$button.closest('.notice').removeClass('notice-warning').addClass('notice-success');
								\$button.closest('.notice').html('<p><strong>" . esc_js( __( 'Success!', 'acf-sms' ) ) . "</strong> ' + response.data.message + '</p>');
								setTimeout(function() {
									location.reload();
								}, 2000);
							} else {
								alert('" . esc_js( __( 'Error:', 'acf-sms' ) ) . " ' + response.data.message);
								\$button.prop('disabled', false);
							}
						},
						error: function() {
							\$spinner.removeClass('is-active');
							alert('" . esc_js( __( 'An error occurred', 'acf-sms' ) ) . "');
							\$button.prop('disabled', false);
						}
					});
				});
			});
		" );
	}
}