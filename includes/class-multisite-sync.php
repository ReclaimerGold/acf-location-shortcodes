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
	private $sync_post_types = array( 'location', 'team-member', 'service', 'condition' );

	/**
	 * Relationship fields that need ID remapping across sites.
	 * Format: 'field_name' => 'target_post_type'
	 *
	 * @var array
	 */
	private $relationship_fields = array(
		'servicing_physical_location' => 'location',
		'team_members_assigned'       => 'team-member',
		'location'                    => 'location',
		'specialties'                 => 'service',
	);

	/**
	 * Taxonomies to sync for each post type.
	 * Format: 'post_type' => array( 'taxonomy1', 'taxonomy2' )
	 *
	 * @var array
	 */
	private $sync_taxonomies = array(
		'service'     => array( 'service-category', 'service-tag' ),
		'team-member' => array( 'team-member-type' ),
	);

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

		// Clear attachment cache before syncing to ensure fresh copies for each site.
		$this->attachment_id_cache = array();

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

			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: === SYNCING TO SITE %d ===', $site_id ) );
			}

			// Switch to target site.
			switch_to_blog( $site_id );

			// Sync the post.
			$this->sync_post_to_current_site( $post_id, $post, $current_site_id );

			// Update last sync time for this site.
			update_option( 'acf_sms_last_sync_time', current_time( 'timestamp' ) );

			// Restore original site.
			restore_current_blog();
			
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: === COMPLETED SYNC TO SITE %d ===', $site_id ) );
			}
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
						'ACF SMS: Duplicate found for "%s" (slug: %s) on target site - linking existing post %d.', 
						$source_post->post_title, 
						$source_post->post_name,
						$duplicate_id
					) );
				}
				// LINK the duplicate instead of skipping - treat it as an existing synced post.
				$existing_id = $duplicate_id;
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

		// Sync profile picture specifically for team members (ensures it works).
		if ( $source_post->post_type === 'team-member' ) {
			$this->sync_profile_picture( $source_post_id, $source_site_id, $synced_id );
		}

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
		// Known image field names - fallback for when field type detection fails.
		$known_image_fields = array( 'profile_picture', 'servcat_featured_image' );
		
		// Switch to source site to get ACF data.
		switch_to_blog( $source_site_id );
		$acf_fields = get_fields( $source_post_id );
		$field_objects = array();
		
		// Get field objects to check field types.
		if ( function_exists( 'get_field_objects' ) ) {
			$field_objects = get_field_objects( $source_post_id );
			if ( ! is_array( $field_objects ) ) {
				$field_objects = array();
			}
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
			
			// Fallback: Check if this is a known image field by name.
			$is_known_image_field = in_array( $field_name, $known_image_fields, true );
			
			// Also detect image fields by value structure (array with 'ID', 'url', 'sizes' keys).
			$looks_like_image = false;
			if ( is_array( $field_value ) && isset( $field_value['ID'] ) && isset( $field_value['url'] ) ) {
				// Check for image-specific keys.
				if ( isset( $field_value['sizes'] ) || isset( $field_value['width'] ) || isset( $field_value['height'] ) ) {
					$looks_like_image = true;
				}
			}

			// Handle image/file fields - need to sync the attachment.
			// Use field type, known field names, or value structure detection.
			$is_image_field = in_array( $field_type, array( 'image', 'file' ), true ) 
				|| $is_known_image_field 
				|| $looks_like_image;
			
			if ( $is_image_field ) {
				$original_attachment_id = null;
				
				if ( is_numeric( $field_value ) ) {
					$original_attachment_id = (int) $field_value;
				} elseif ( is_array( $field_value ) && isset( $field_value['ID'] ) ) {
					$original_attachment_id = (int) $field_value['ID'];
				}
				
				if ( $original_attachment_id ) {
					if ( ACF_LS_DEBUG ) {
						error_log( sprintf( 
							'ACF SMS: Attempting to sync image field "%s" (type: %s, known: %s, looks_like: %s) - attachment ID %d', 
							$field_name,
							$field_type ?: 'unknown',
							$is_known_image_field ? 'yes' : 'no',
							$looks_like_image ? 'yes' : 'no',
							$original_attachment_id 
						) );
					}
					
					$synced_id = $this->sync_attachment( $original_attachment_id, $source_site_id );
					
					if ( $synced_id ) {
						$field_value = $synced_id;
						
						if ( ACF_LS_DEBUG ) {
							error_log( sprintf( 
								'ACF SMS: Successfully synced media field "%s" - source attachment %d to target attachment %d', 
								$field_name, 
								$original_attachment_id, 
								$synced_id 
							) );
						}
					} else {
						// Attachment sync failed - skip updating this field to preserve existing data.
						if ( ACF_LS_DEBUG ) {
							error_log( sprintf( 
								'ACF SMS: Failed to sync media field "%s" - attachment %d', 
								$field_name, 
								$original_attachment_id 
							) );
						}
						continue;
					}
				} elseif ( empty( $field_value ) ) {
					// Field is empty - clear it on target.
					$field_value = '';
				} else {
					// Unknown format - skip to avoid data corruption.
					if ( ACF_LS_DEBUG ) {
						error_log( sprintf( 
							'ACF SMS: Unknown format for media field "%s" - value type: %s', 
							$field_name,
							gettype( $field_value )
						) );
					}
					continue;
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
			} elseif ( in_array( $field_type, array( 'relationship', 'post_object' ), true ) ) {
				// Handle relationship and post_object fields - need to remap IDs across sites.
				$field_value = $this->remap_relationship_field( $field_name, $field_value, $source_site_id );
				
				// Skip if remapping returned null (unable to remap).
				if ( $field_value === null ) {
					continue;
				}
			}

			// Update field value.
			// Get field key from field objects if available.
			$field_key = isset( $field_objects[ $field_name ]['key'] ) ? $field_objects[ $field_name ]['key'] : '';
			
			// Try update_field first (uses ACF's internal logic).
			$update_result = update_field( $field_name, $field_value, $target_post_id );
			
			// If update_field returned false and we have the field key, try with the key directly.
			if ( ! $update_result && $field_key ) {
				$update_result = update_field( $field_key, $field_value, $target_post_id );
				
				if ( ACF_LS_DEBUG ) {
					error_log( sprintf( 
						'ACF SMS: Retry with field key "%s" returned: %s', 
						$field_key,
						$update_result ? 'true' : 'false'
					) );
				}
			}
			
			// Final fallback for image fields: update post meta directly.
			if ( ! $update_result && $is_image_field && is_numeric( $field_value ) ) {
				update_post_meta( $target_post_id, $field_name, $field_value );
				
				// Also store the field key reference if we have it.
				if ( $field_key ) {
					update_post_meta( $target_post_id, '_' . $field_name, $field_key );
				}
				
				if ( ACF_LS_DEBUG ) {
					error_log( sprintf( 
						'ACF SMS: Fallback update_post_meta for "%s" with value %d (key: %s)', 
						$field_name, 
						$field_value,
						$field_key ?: 'none'
					) );
				}
			}
			
			if ( ACF_LS_DEBUG && $is_image_field ) {
				error_log( sprintf( 
					'ACF SMS: update_field("%s", %s, %d) returned: %s', 
					$field_name, 
					is_scalar( $field_value ) ? $field_value : gettype( $field_value ),
					$target_post_id,
					$update_result ? 'true' : 'false'
				) );
			}
		}
	}

	/**
	 * Remap relationship/post_object field IDs from source site to target site.
	 *
	 * @since 2.4.0
	 * @param string $field_name  ACF field name.
	 * @param mixed  $field_value Field value (post object(s) or ID(s)).
	 * @param int    $source_site_id Source site ID.
	 * @return mixed Remapped field value (array of IDs) or null if unable to remap.
	 */
	private function remap_relationship_field( $field_name, $field_value, $source_site_id ) {
		if ( empty( $field_value ) ) {
			return array();
		}

		// Handle single post object (post_object field with multiple=0).
		$is_single = false;
		if ( ! is_array( $field_value ) || ( is_object( $field_value ) ) ) {
			$field_value = array( $field_value );
			$is_single = true;
		}

		// Check if we have an array of objects.
		$first_item = reset( $field_value );
		if ( ! is_object( $first_item ) && ! is_numeric( $first_item ) ) {
			// Not a valid relationship field format.
			return null;
		}

		$remapped_ids = array();

		foreach ( $field_value as $item ) {
			// Get source post ID.
			$source_related_id = 0;
			if ( is_object( $item ) && isset( $item->ID ) ) {
				$source_related_id = $item->ID;
			} elseif ( is_numeric( $item ) ) {
				$source_related_id = absint( $item );
			}

			if ( ! $source_related_id ) {
				continue;
			}

			// Find the corresponding post on target site.
			$target_related_id = $this->get_synced_post_id( $source_related_id, $source_site_id );

			if ( $target_related_id ) {
				$remapped_ids[] = $target_related_id;
				
				if ( ACF_LS_DEBUG ) {
					error_log( sprintf(
						'ACF SMS: Remapped relationship %s: source ID %d -> target ID %d',
						$field_name,
						$source_related_id,
						$target_related_id
					) );
				}
			} else {
				// Related post doesn't exist on target site yet.
				// Try to find by title/slug as fallback.
				switch_to_blog( $source_site_id );
				$source_post = get_post( $source_related_id );
				restore_current_blog();

				if ( $source_post ) {
					// Check if this post type should be synced.
					if ( in_array( $source_post->post_type, $this->sync_post_types, true ) ) {
						// Look for matching post by slug on target site.
						$target_post = get_page_by_path( $source_post->post_name, OBJECT, $source_post->post_type );
						
						if ( $target_post ) {
							$remapped_ids[] = $target_post->ID;
							
							if ( ACF_LS_DEBUG ) {
								error_log( sprintf(
									'ACF SMS: Remapped relationship %s by slug: source ID %d -> target ID %d',
									$field_name,
									$source_related_id,
									$target_post->ID
								) );
							}
						} else {
							if ( ACF_LS_DEBUG ) {
								error_log( sprintf(
									'ACF SMS: Unable to remap relationship %s: source ID %d not found on target site',
									$field_name,
									$source_related_id
								) );
							}
						}
					}
				}
			}
		}

		// Return single ID for post_object fields, array for relationship fields.
		if ( $is_single ) {
			return ! empty( $remapped_ids ) ? $remapped_ids[0] : null;
		}

		return $remapped_ids;
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
				
				// Get full term data including hierarchy for hierarchical taxonomies.
				$is_hierarchical = is_taxonomy_hierarchical( $taxonomy );
				$terms = wp_get_object_terms( $source_post_id, $taxonomy, array( 
					'fields' => 'all',
					'orderby' => 'parent', // Parents first for hierarchical.
				) );
				
				restore_current_blog();

				if ( is_wp_error( $terms ) || empty( $terms ) ) {
					// Clear terms on target if source has none.
					wp_set_object_terms( $target_post_id, array(), $taxonomy, false );
					continue;
				}

				if ( $is_hierarchical ) {
					// For hierarchical taxonomies, sync with parent relationships.
					$this->sync_hierarchical_terms( $terms, $taxonomy, $target_post_id, $source_site_id );
				} else {
					// For non-hierarchical taxonomies, just sync term names.
					$term_names = wp_list_pluck( $terms, 'name' );
					wp_set_object_terms( $target_post_id, $term_names, $taxonomy, false );
				}

				if ( ACF_LS_DEBUG ) {
					error_log( sprintf(
						'ACF SMS: Synced %d terms for taxonomy %s on post %d',
						count( $terms ),
						$taxonomy,
						$target_post_id
					) );
				}
			}
		}
	}

	/**
	 * Sync hierarchical terms maintaining parent-child relationships.
	 *
	 * @since 2.4.0
	 * @param array  $source_terms   Array of source term objects.
	 * @param string $taxonomy       Taxonomy slug.
	 * @param int    $target_post_id Target post ID.
	 * @param int    $source_site_id Source site ID.
	 */
	private function sync_hierarchical_terms( $source_terms, $taxonomy, $target_post_id, $source_site_id ) {
		$term_ids_to_set = array();
		$term_id_map = array(); // Maps source term ID to target term ID.

		// First pass: Create/find all terms and build the mapping.
		foreach ( $source_terms as $source_term ) {
			// Check if term exists on target by slug.
			$target_term = get_term_by( 'slug', $source_term->slug, $taxonomy );

			if ( $target_term ) {
				$term_ids_to_set[] = $target_term->term_id;
				$term_id_map[ $source_term->term_id ] = $target_term->term_id;
			} else {
				// Term doesn't exist - create it.
				$parent_id = 0;
				
				// If this term has a parent, try to find the mapped parent ID.
				if ( $source_term->parent > 0 ) {
					if ( isset( $term_id_map[ $source_term->parent ] ) ) {
						$parent_id = $term_id_map[ $source_term->parent ];
					} else {
						// Try to find parent by getting it from source site.
						switch_to_blog( $source_site_id );
						$source_parent = get_term( $source_term->parent, $taxonomy );
						restore_current_blog();

						if ( $source_parent && ! is_wp_error( $source_parent ) ) {
							$target_parent = get_term_by( 'slug', $source_parent->slug, $taxonomy );
							if ( $target_parent ) {
								$parent_id = $target_parent->term_id;
							}
						}
					}
				}

				$new_term = wp_insert_term(
					$source_term->name,
					$taxonomy,
					array(
						'slug'        => $source_term->slug,
						'description' => $source_term->description,
						'parent'      => $parent_id,
					)
				);

				if ( ! is_wp_error( $new_term ) ) {
					$term_ids_to_set[] = $new_term['term_id'];
					$term_id_map[ $source_term->term_id ] = $new_term['term_id'];

					if ( ACF_LS_DEBUG ) {
						error_log( sprintf(
							'ACF SMS: Created term "%s" (ID: %d) in taxonomy %s',
							$source_term->name,
							$new_term['term_id'],
							$taxonomy
						) );
					}
				} else {
					if ( ACF_LS_DEBUG ) {
						error_log( sprintf(
							'ACF SMS: Failed to create term "%s": %s',
							$source_term->name,
							$new_term->get_error_message()
						) );
					}
				}
			}
		}

		// Set all terms on the post.
		if ( ! empty( $term_ids_to_set ) ) {
			wp_set_object_terms( $target_post_id, $term_ids_to_set, $taxonomy, false );
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
	 * Sync profile picture for team members.
	 * This is a dedicated method to ensure profile pictures are properly synced.
	 *
	 * @since 2.4.0
	 * @param int $source_post_id Source post ID.
	 * @param int $source_site_id Source site ID.
	 * @param int $target_post_id Target post ID.
	 */
	private function sync_profile_picture( $source_post_id, $source_site_id, $target_post_id ) {
		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: === sync_profile_picture START === source_post: %d, source_site: %d, target_post: %d', 
				$source_post_id, $source_site_id, $target_post_id ) );
		}
		
		// Get the profile_picture attachment ID from the source site.
		switch_to_blog( $source_site_id );
		
		// Debug: Show all post meta for this post to see what's stored.
		if ( ACF_LS_DEBUG ) {
			$all_meta = get_post_meta( $source_post_id );
			$profile_keys = array();
			foreach ( $all_meta as $key => $value ) {
				if ( stripos( $key, 'profile' ) !== false || stripos( $key, 'picture' ) !== false || stripos( $key, 'image' ) !== false ) {
					$profile_keys[ $key ] = $value;
				}
			}
			error_log( sprintf( 'ACF SMS: Source post %d meta keys containing profile/picture/image: %s', 
				$source_post_id, print_r( $profile_keys, true ) ) );
		}
		
		// Try to get the raw meta value first (attachment ID).
		$source_attachment_id = get_post_meta( $source_post_id, 'profile_picture', true );
		
		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Raw meta "profile_picture" value: %s (type: %s)', 
				is_array( $source_attachment_id ) ? print_r( $source_attachment_id, true ) : $source_attachment_id,
				gettype( $source_attachment_id ) ) );
		}
		
		// If it's an array, extract the ID.
		if ( is_array( $source_attachment_id ) && isset( $source_attachment_id['ID'] ) ) {
			$source_attachment_id = (int) $source_attachment_id['ID'];
		} elseif ( is_array( $source_attachment_id ) && isset( $source_attachment_id['id'] ) ) {
			$source_attachment_id = (int) $source_attachment_id['id'];
		}
		
		// If still not numeric, try get_field which handles ACF's format.
		if ( ! is_numeric( $source_attachment_id ) || empty( $source_attachment_id ) ) {
			if ( function_exists( 'get_field' ) ) {
				// Try with raw value (false).
				$profile_pic = get_field( 'profile_picture', $source_post_id, false );
				
				if ( ACF_LS_DEBUG ) {
					error_log( sprintf( 'ACF SMS: get_field raw value: %s (type: %s)', 
						is_array( $profile_pic ) ? print_r( $profile_pic, true ) : $profile_pic,
						gettype( $profile_pic ) ) );
				}
				
				if ( is_numeric( $profile_pic ) ) {
					$source_attachment_id = (int) $profile_pic;
				} elseif ( is_array( $profile_pic ) && isset( $profile_pic['ID'] ) ) {
					$source_attachment_id = (int) $profile_pic['ID'];
				} elseif ( is_array( $profile_pic ) && isset( $profile_pic['id'] ) ) {
					$source_attachment_id = (int) $profile_pic['id'];
				}
				
				// Also try with formatted value (true).
				if ( empty( $source_attachment_id ) ) {
					$profile_pic_formatted = get_field( 'profile_picture', $source_post_id, true );
					
					if ( ACF_LS_DEBUG ) {
						error_log( sprintf( 'ACF SMS: get_field formatted value: %s (type: %s)', 
							is_array( $profile_pic_formatted ) ? 'array with keys: ' . implode( ', ', array_keys( $profile_pic_formatted ) ) : $profile_pic_formatted,
							gettype( $profile_pic_formatted ) ) );
					}
					
					if ( is_array( $profile_pic_formatted ) && isset( $profile_pic_formatted['ID'] ) ) {
						$source_attachment_id = (int) $profile_pic_formatted['ID'];
					} elseif ( is_array( $profile_pic_formatted ) && isset( $profile_pic_formatted['id'] ) ) {
						$source_attachment_id = (int) $profile_pic_formatted['id'];
					} elseif ( is_numeric( $profile_pic_formatted ) ) {
						$source_attachment_id = (int) $profile_pic_formatted;
					}
				}
			}
		}
		
		// Also try to get the ACF field key for later use.
		$field_key = get_post_meta( $source_post_id, '_profile_picture', true );
		
		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Final source_attachment_id: %s, field_key: %s', 
				$source_attachment_id ?: 'empty', $field_key ?: 'empty' ) );
		}
		
		restore_current_blog();

		if ( empty( $source_attachment_id ) ) {
			// No profile picture on source - clear it on target.
			delete_post_meta( $target_post_id, 'profile_picture' );
			delete_post_meta( $target_post_id, '_profile_picture' );
			
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: No profile_picture on source post %d, cleared on target %d', $source_post_id, $target_post_id ) );
			}
			return;
		}

		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Syncing profile_picture - source attachment ID: %d, field key: %s', $source_attachment_id, $field_key ?: 'none' ) );
		}

		// Sync the attachment to get the target site's attachment ID.
		$target_attachment_id = $this->sync_attachment( $source_attachment_id, $source_site_id );

		if ( ! $target_attachment_id ) {
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Failed to sync profile_picture attachment %d', $source_attachment_id ) );
			}
			return;
		}

		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Profile picture attachment synced: source %d -> target %d', $source_attachment_id, $target_attachment_id ) );
		}

		// Method 1: Try ACF's update_field with field name.
		$updated = false;
		if ( function_exists( 'update_field' ) ) {
			$updated = update_field( 'profile_picture', $target_attachment_id, $target_post_id );
			
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: update_field("profile_picture", %d, %d) = %s', $target_attachment_id, $target_post_id, $updated ? 'success' : 'failed' ) );
			}
		}

		// Method 2: Try ACF's update_field with field key.
		if ( ! $updated && function_exists( 'update_field' ) && $field_key ) {
			$updated = update_field( $field_key, $target_attachment_id, $target_post_id );
			
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: update_field("%s", %d, %d) = %s', $field_key, $target_attachment_id, $target_post_id, $updated ? 'success' : 'failed' ) );
			}
		}

		// Method 3: Direct post meta update (always do this as a backup).
		update_post_meta( $target_post_id, 'profile_picture', $target_attachment_id );
		
		// Store the field key reference so ACF recognizes this as an ACF field.
		if ( $field_key ) {
			update_post_meta( $target_post_id, '_profile_picture', $field_key );
		} else {
			// Use the known field key for profile_picture from the ACF export.
			update_post_meta( $target_post_id, '_profile_picture', 'field_68f69441b39f5' );
		}

		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 
				'ACF SMS: Profile picture sync complete for post %d - attachment ID %d stored in meta', 
				$target_post_id, 
				$target_attachment_id 
			) );
			
			// Verify the value was saved.
			$verify = get_post_meta( $target_post_id, 'profile_picture', true );
			error_log( sprintf( 'ACF SMS: Verification - profile_picture meta value: %s', $verify ) );
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
	private function sync_attachment( $source_attachment_id, $source_site_id, $force = false ) {
		if ( ! $source_attachment_id ) {
			if ( ACF_LS_DEBUG ) {
				error_log( 'ACF SMS: sync_attachment called with empty source_attachment_id' );
			}
			return false;
		}

		// Get the current (target) site ID - this is the site we're syncing TO.
		$target_site_id = get_current_blog_id();

		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: sync_attachment START - source_att: %d, source_site: %d, target_site: %d, force: %s', 
				$source_attachment_id, $source_site_id, $target_site_id, $force ? 'yes' : 'no' ) );
		}

		// Check cache first - MUST include target site ID to avoid cross-site confusion!
		$cache_key = $target_site_id . '_' . $source_site_id . '_' . $source_attachment_id;
		
		if ( ! $force && isset( $this->attachment_id_cache[ $cache_key ] ) ) {
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Cache hit - returning %d', $this->attachment_id_cache[ $cache_key ] ) );
			}
			return $this->attachment_id_cache[ $cache_key ];
		}

		// Check if attachment already exists on current site.
		$existing_id = $this->get_synced_attachment_id( $source_attachment_id, $source_site_id );
		
		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Existing synced attachment on target site: %s', $existing_id ?: 'none' ) );
		}

		$target_id = false;

		if ( $existing_id && ! $force ) {
			// Check if we need to update it.
			switch_to_blog( $source_site_id );
			$source_modified = get_post_field( 'post_modified', $source_attachment_id );
			restore_current_blog();

			$target_last_sync = get_post_meta( $existing_id, '_acf_sms_last_sync', true );
			$source_modified_time = strtotime( $source_modified );

			if ( ! $target_last_sync || $source_modified_time > $target_last_sync ) {
				if ( ACF_LS_DEBUG ) {
					error_log( 'ACF SMS: Existing attachment needs update' );
				}
				$target_id = $this->copy_attachment_to_current_site( $source_attachment_id, $source_site_id, $existing_id );
			} else {
				if ( ACF_LS_DEBUG ) {
					error_log( 'ACF SMS: Existing attachment is up to date' );
				}
				$target_id = $existing_id;
			}
		} else {
			// Create new attachment (or force re-create).
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Creating new attachment (force=%s, existing=%s)', 
					$force ? 'yes' : 'no', $existing_id ?: 'none' ) );
			}
			$target_id = $this->copy_attachment_to_current_site( $source_attachment_id, $source_site_id, $force ? null : $existing_id );
		}

		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: sync_attachment END - result: %s', $target_id ?: 'FAILED' ) );
		}

		// Cache the result.
		if ( $target_id ) {
			$this->attachment_id_cache[ $cache_key ] = $target_id;
		}

		return $target_id;
	}

	/**
	 * Force sync a profile picture to all target sites.
	 * This bypasses all caching and existing attachment checks.
	 *
	 * @since 2.4.0
	 * @param int $team_member_id Team member post ID on master site.
	 * @return array Results for each target site.
	 */
	public function force_sync_profile_picture( $team_member_id ) {
		$results = array();
		$master_site_id = $this->get_master_site();
		$sync_sites = $this->get_sync_sites();
		
		// Get source data from master site.
		switch_to_blog( $master_site_id );
		
		$team_member = get_post( $team_member_id );
		if ( ! $team_member || $team_member->post_type !== 'team-member' ) {
			restore_current_blog();
			return array( 'error' => 'Invalid team member ID' );
		}
		
		$source_attachment_id = get_post_meta( $team_member_id, 'profile_picture', true );
		$field_key = get_post_meta( $team_member_id, '_profile_picture', true );
		
		if ( empty( $source_attachment_id ) ) {
			restore_current_blog();
			return array( 'error' => 'No profile picture on master site' );
		}
		
		// Get source file info.
		$source_file = get_attached_file( $source_attachment_id );
		$source_file_exists = $source_file && file_exists( $source_file );
		
		$results['master'] = array(
			'site_id' => $master_site_id,
			'team_member_id' => $team_member_id,
			'team_member_title' => $team_member->post_title,
			'attachment_id' => $source_attachment_id,
			'file_path' => $source_file,
			'file_exists' => $source_file_exists,
		);
		
		if ( ! $source_file_exists ) {
			restore_current_blog();
			return array_merge( $results, array( 'error' => 'Source file does not exist: ' . $source_file ) );
		}
		
		restore_current_blog();
		
		// Clear cache completely.
		$this->attachment_id_cache = array();
		
		// Sync to each target site.
		foreach ( $sync_sites as $site_id ) {
			$site_id = (int) $site_id;
			if ( $site_id === $master_site_id ) {
				continue;
			}
			
			$site_result = array(
				'site_id' => $site_id,
				'steps' => array(),
			);
			
			switch_to_blog( $site_id );
			
			$site_result['steps'][] = 'Switched to site ' . $site_id;
			
			// Find synced team member post.
			global $wpdb;
			$synced_post_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} 
				WHERE meta_key = '_acf_sms_source_post' AND meta_value = %d
				AND post_id IN (
					SELECT post_id FROM {$wpdb->postmeta}
					WHERE meta_key = '_acf_sms_source_site' AND meta_value = %d
				)
				LIMIT 1",
				$team_member_id,
				$master_site_id
			) );
			
			if ( ! $synced_post_id ) {
				$site_result['error'] = 'Team member not synced to this site';
				$results['sites'][] = $site_result;
				restore_current_blog();
				continue;
			}
			
			$site_result['synced_post_id'] = $synced_post_id;
			$site_result['steps'][] = 'Found synced post: ' . $synced_post_id;
			
			// Force copy the attachment.
			$target_attachment_id = $this->copy_attachment_to_current_site( $source_attachment_id, $master_site_id, null );
			
			if ( ! $target_attachment_id ) {
				$site_result['error'] = 'Failed to copy attachment';
				$results['sites'][] = $site_result;
				restore_current_blog();
				continue;
			}
			
			$site_result['new_attachment_id'] = $target_attachment_id;
			$site_result['steps'][] = 'Created new attachment: ' . $target_attachment_id;
			
			// Verify the file was copied.
			$target_file = get_attached_file( $target_attachment_id );
			$site_result['target_file'] = $target_file;
			$site_result['target_file_exists'] = $target_file && file_exists( $target_file );
			$site_result['steps'][] = 'Target file: ' . $target_file . ' (exists: ' . ( $site_result['target_file_exists'] ? 'yes' : 'no' ) . ')';
			
			// Update the profile_picture field.
			update_post_meta( $synced_post_id, 'profile_picture', $target_attachment_id );
			update_post_meta( $synced_post_id, '_profile_picture', $field_key ?: 'field_68f69441b39f5' );
			
			$site_result['steps'][] = 'Updated profile_picture meta to ' . $target_attachment_id;
			
			// Verify.
			$verify = get_post_meta( $synced_post_id, 'profile_picture', true );
			$site_result['verification'] = $verify;
			$site_result['success'] = ( (int) $verify === (int) $target_attachment_id );
			
			$results['sites'][] = $site_result;
			
			restore_current_blog();
		}
		
		return $results;
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
		// Store the current (target) site ID.
		$target_site_id = get_current_blog_id();
		
		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: copy_attachment_to_current_site - source attachment: %d, source site: %d, target site: %d', 
				$source_attachment_id, $source_site_id, $target_site_id ) );
		}
		
		// Get source attachment data while on source site.
		switch_to_blog( $source_site_id );
		
		$source_file = get_attached_file( $source_attachment_id );
		$source_post = get_post( $source_attachment_id );
		$source_metadata = wp_get_attachment_metadata( $source_attachment_id );
		
		// Store the source file path before restoring - this is the absolute filesystem path.
		$source_file_path = $source_file;
		$source_file_exists = $source_file && file_exists( $source_file );
		$source_file_size = $source_file_exists ? filesize( $source_file ) : 0;
		
		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Source file path: %s, exists: %s, size: %d', 
				$source_file_path, $source_file_exists ? 'yes' : 'no', $source_file_size ) );
		}
		
		if ( ! $source_file_path || ! $source_file_exists || ! $source_post ) {
			restore_current_blog();
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Source attachment invalid - file: %s, exists: %s, post: %s', 
					$source_file_path ?: 'null', 
					$source_file_exists ? 'yes' : 'no',
					$source_post ? 'yes' : 'no' ) );
			}
			return false;
		}

		// Get the file name.
		$filename = basename( $source_file_path );
		
		// Store source post data for later use.
		$source_post_title = $source_post->post_title;
		$source_post_content = $source_post->post_content;
		$source_post_excerpt = $source_post->post_excerpt;
		$source_mime_type = $source_post->post_mime_type;
		$source_alt_text = get_post_meta( $source_attachment_id, '_wp_attachment_image_alt', true );
		
		restore_current_blog();
		
		// Now we're back on the target site - get upload directory.
		$upload_dir = wp_upload_dir();
		
		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Target upload dir: %s (error: %s)', 
				$upload_dir['path'], $upload_dir['error'] ?: 'none' ) );
		}
		
		if ( $upload_dir['error'] ) {
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Upload directory error: %s', $upload_dir['error'] ) );
			}
			return false;
		}

		// Check if we're updating an existing attachment.
		if ( $existing_id ) {
			// Get existing file path.
			$existing_file = get_attached_file( $existing_id );
			$target_size = $existing_file && file_exists( $existing_file ) ? filesize( $existing_file ) : 0;
			
			if ( $existing_file && file_exists( $existing_file ) && $source_file_size === $target_size && $source_file_size > 0 ) {
				// File is identical, just update metadata.
				if ( ACF_LS_DEBUG ) {
					error_log( sprintf( 'ACF SMS: Attachment %d already synced with identical file (%d bytes), updating metadata only', 
						$source_attachment_id, $source_file_size ) );
				}
				
				// Update sync timestamp.
				update_post_meta( $existing_id, '_acf_sms_last_sync', current_time( 'timestamp' ) );
				
				return $existing_id;
			}
			
			// Use existing file path for update.
			$target_file_path = $existing_file;
			
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Updating existing attachment at: %s', $target_file_path ) );
			}
		} else {
			// Generate unique filename if needed.
			$target_file = wp_unique_filename( $upload_dir['path'], $filename );
			$target_file_path = $upload_dir['path'] . '/' . $target_file;
			
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Creating new attachment at: %s', $target_file_path ) );
			}
		}

		// Create target directory if it doesn't exist.
		$target_dir = dirname( $target_file_path );
		if ( ! file_exists( $target_dir ) ) {
			$mkdir_result = wp_mkdir_p( $target_dir );
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Created directory %s: %s', $target_dir, $mkdir_result ? 'success' : 'failed' ) );
			}
		}
		
		// Copy the file using the stored source path (absolute filesystem path).
		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Copying file from %s to %s', $source_file_path, $target_file_path ) );
		}
		
		$copied = @copy( $source_file_path, $target_file_path );
		
		if ( ! $copied ) {
			// Try alternative method using file_get_contents/file_put_contents.
			$file_contents = @file_get_contents( $source_file_path );
			if ( $file_contents !== false ) {
				$bytes_written = @file_put_contents( $target_file_path, $file_contents );
				$copied = ( $bytes_written !== false && $bytes_written > 0 );
				
				if ( ACF_LS_DEBUG ) {
					error_log( sprintf( 'ACF SMS: Alternative copy method - bytes written: %s', 
						$bytes_written !== false ? $bytes_written : 'failed' ) );
				}
			}
		}

		if ( ! $copied ) {
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Failed to copy file from %s to %s - check file permissions', 
					$source_file_path, $target_file_path ) );
				error_log( sprintf( 'ACF SMS: Source readable: %s, Target dir writable: %s', 
					is_readable( $source_file_path ) ? 'yes' : 'no',
					is_writable( $target_dir ) ? 'yes' : 'no' ) );
			}
			return false;
		}

		// Verify the copied file exists.
		if ( ! file_exists( $target_file_path ) ) {
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Target file does not exist after copy: %s', $target_file_path ) );
			}
			return false;
		}
		
		$copied_size = filesize( $target_file_path );
		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: File copied successfully - size: %d bytes (source was %d bytes)', 
				$copied_size, $source_file_size ) );
		}

		// Get file type.
		$file_type = wp_check_filetype( $target_file_path );
		$mime_type = $file_type['type'] ?: $source_mime_type;

		// Prepare attachment data using stored source data.
		$attachment_data = array(
			'post_title'     => $source_post_title ?: $filename,
			'post_content'   => $source_post_content,
			'post_excerpt'   => $source_post_excerpt,
			'post_status'    => 'inherit',
			'post_mime_type' => $mime_type,
		);

		if ( $existing_id ) {
			// Update existing attachment.
			$attachment_data['ID'] = $existing_id;
			$attachment_id = wp_update_post( $attachment_data );
			
			// Update the attached file path.
			update_attached_file( $existing_id, $target_file_path );
			
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Updated existing attachment %d', $existing_id ) );
			}
		} else {
			// Insert new attachment.
			$attachment_id = wp_insert_attachment( $attachment_data, $target_file_path );
			
			if ( ACF_LS_DEBUG ) {
				error_log( sprintf( 'ACF SMS: Inserted new attachment - result: %s', 
					is_wp_error( $attachment_id ) ? $attachment_id->get_error_message() : $attachment_id ) );
			}
		}

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			// Clean up copied file on error.
			if ( file_exists( $target_file_path ) ) {
				@unlink( $target_file_path );
			}
			if ( ACF_LS_DEBUG ) {
				$error_msg = is_wp_error( $attachment_id ) ? $attachment_id->get_error_message() : 'Unknown error';
				error_log( sprintf( 'ACF SMS: Failed to insert attachment: %s', $error_msg ) );
			}
			return false;
		}

		// Generate attachment metadata (thumbnails, etc.).
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $target_file_path );
		wp_update_attachment_metadata( $attachment_id, $attach_data );
		
		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Generated metadata for attachment %d', $attachment_id ) );
		}

		// Store sync metadata.
		update_post_meta( $attachment_id, '_acf_sms_source_site', $source_site_id );
		update_post_meta( $attachment_id, '_acf_sms_source_post', $source_attachment_id );
		update_post_meta( $attachment_id, '_acf_sms_last_sync', current_time( 'timestamp' ) );

		// Copy alt text using the value we stored earlier.
		if ( $source_alt_text ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $source_alt_text );
		}

		if ( ACF_LS_DEBUG ) {
			error_log( sprintf( 'ACF SMS: Successfully synced attachment %d to %d on site %d (file: %s)', 
				$source_attachment_id, $attachment_id, get_current_blog_id(), basename( $target_file_path ) ) );
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
			'enabled'    => $this->is_sync_enabled(),
			'sites'      => count( $this->get_sync_sites() ),
			'locations'  => wp_count_posts( 'location' )->publish,
			'members'    => wp_count_posts( 'team-member' )->publish,
			'services'   => post_type_exists( 'service' ) ? wp_count_posts( 'service' )->publish : 0,
			'conditions' => post_type_exists( 'condition' ) ? wp_count_posts( 'condition' )->publish : 0,
		);

		wp_send_json_success( $stats );
	}

	/**
	 * Get the post types that are synced.
	 *
	 * @since 2.4.0
	 * @return array Array of post type slugs.
	 */
	public function get_sync_post_types() {
		return apply_filters( 'acf_sms_sync_post_types', $this->sync_post_types );
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