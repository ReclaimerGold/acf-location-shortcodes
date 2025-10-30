<?php
/**
 * ACF Helper Functions
 *
 * Handles all ACF data retrieval and parsing for location posts.
 *
 * @package ACF_Location_Shortcodes
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF Helper class.
 *
 * @since 1.0.0
 */
class ACF_Location_Shortcodes_ACF_Helpers {

	/**
	 * Cache group name.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'acf_ls_locations';

	/**
	 * Cache expiration time (1 hour).
	 *
	 * @var int
	 */
	const CACHE_EXPIRATION = 3600;

	/**
	 * Get surrounding communities from a location post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Location post ID.
	 * @return array Array of community names.
	 */
	public function get_surrounding_communities( $post_id ) {
		if ( ! function_exists( 'get_field' ) ) {
			return array();
		}

		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return array();
		}

		// Check cache first.
		$cache_key = 'communities_' . $post_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			ACF_Location_Shortcodes::log(
				'Communities retrieved from cache',
				array(
					'post_id' => $post_id,
					'count'   => count( $cached ),
				),
				'info'
			);
			return $cached;
		}

		// Get field value.
		$communities_string = get_field( 'surrounding_community_list', $post_id );

		if ( empty( $communities_string ) ) {
			ACF_Location_Shortcodes::log(
				'Surrounding community list is empty',
				array( 'post_id' => $post_id ),
				'info'
			);
			wp_cache_set( $cache_key, array(), self::CACHE_GROUP, self::CACHE_EXPIRATION );
			return array();
		}

		// Parse comma-separated list.
		$communities = $this->parse_comma_list( $communities_string );

		// Cache the result.
		wp_cache_set( $cache_key, $communities, self::CACHE_GROUP, self::CACHE_EXPIRATION );

		ACF_Location_Shortcodes::log(
			'Communities parsed and cached',
			array(
				'post_id' => $post_id,
				'count'   => count( $communities ),
			),
			'info'
		);

		return $communities;
	}

	/**
	 * Parse a comma-separated list into an array.
	 *
	 * @since 1.0.0
	 * @param string $list Comma-separated list.
	 * @return array Array of trimmed, non-empty items.
	 */
	public function parse_comma_list( $list ) {
		if ( empty( $list ) || ! is_string( $list ) ) {
			return array();
		}

		// Split by comma.
		$items = explode( ',', $list );

		// Trim and filter empty items.
		$items = array_map( 'trim', $items );
		$items = array_filter( $items );

		return array_values( $items );
	}

	/**
	 * Check if a location is a physical location or service area.
	 *
	 * A location is physical if it's top-level (no parent) OR has a physical address.
	 *
	 * @since 1.0.0
	 * @param int $post_id Location post ID.
	 * @return bool True if physical location, false if service area.
	 */
	public function is_physical_location( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return false;
		}

		// Get the post object.
		$post = get_post( $post_id );
		if ( ! $post || 'location' !== $post->post_type ) {
			return false;
		}

		// Top-level locations (no parent) are physical locations.
		if ( ! $post->post_parent ) {
			ACF_Location_Shortcodes::log(
				'Location is physical (top-level)',
				array( 'post_id' => $post_id ),
				'info'
			);
			return true;
		}

		// Child locations can be physical if they have an address.
		if ( function_exists( 'get_field' ) ) {
			$address = get_field( 'address', $post_id );
			$is_physical = ! empty( $address );

			ACF_Location_Shortcodes::log(
				'Location checked for physical address',
				array(
					'post_id'     => $post_id,
					'has_address' => $is_physical,
					'is_physical' => $is_physical,
				),
				'info'
			);

			return $is_physical;
		}

		// Fallback: child locations without ACF are service areas.
		return false;
	}

	/**
	 * Get the parent physical location for a service area.
	 *
	 * Uses WordPress hierarchical structure (post_parent).
	 *
	 * @since 1.0.0
	 * @param int $post_id Location post ID.
	 * @return WP_Post|null Parent physical location post object or null.
	 */
	public function get_servicing_location( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return null;
		}

		// Only service areas have a servicing location.
		if ( $this->is_physical_location( $post_id ) ) {
			ACF_Location_Shortcodes::log(
				'Post is a physical location, no parent needed',
				array( 'post_id' => $post_id ),
				'info'
			);
			return null;
		}

		// Get the parent location using WordPress hierarchy.
		$post = get_post( $post_id );
		if ( ! $post || ! $post->post_parent ) {
			ACF_Location_Shortcodes::log(
				'No parent location found for service area',
				array( 'post_id' => $post_id ),
				'warning'
			);
			return null;
		}

		$parent = get_post( $post->post_parent );
		if ( $parent && 'location' === $parent->post_type ) {
			ACF_Location_Shortcodes::log(
				'Parent location found',
				array(
					'post_id'         => $post_id,
					'parent_id'       => $parent->ID,
					'parent_title'    => $parent->post_title,
				),
				'info'
			);
			return $parent;
		}

		ACF_Location_Shortcodes::log(
			'Parent post is not a valid location',
			array(
				'post_id'    => $post_id,
				'parent_id'  => $post->post_parent,
			),
			'warning'
		);

		return null;
	}

	/**
	 * Get any location field value.
	 *
	 * @since 1.0.0
	 * @param string $field_name ACF field name.
	 * @param int    $post_id    Location post ID.
	 * @param mixed  $default    Default value if field is empty.
	 * @return mixed Field value or default.
	 */
	public function get_location_field( $field_name, $post_id, $default = '' ) {
		if ( ! function_exists( 'get_field' ) ) {
			return $default;
		}

		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return $default;
		}

		$value = get_field( $field_name, $post_id );

		return ! empty( $value ) ? $value : $default;
	}

	/**
	 * Get all location posts.
	 *
	 * @since 1.0.0
	 * @param array $args Additional WP_Query arguments.
	 * @return WP_Post[] Array of location posts.
	 */
	public function get_all_locations( $args = array() ) {
		$defaults = array(
			'post_type'      => 'location',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		$query = new WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Get physical locations only.
	 *
	 * @since 1.0.0
	 * @return WP_Post[] Array of physical location posts.
	 */
	public function get_physical_locations() {
		$cache_key = 'physical_locations';
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$all_locations      = $this->get_all_locations();
		$physical_locations = array();

		foreach ( $all_locations as $location ) {
			if ( $this->is_physical_location( $location->ID ) ) {
				$physical_locations[] = $location;
			}
		}

		wp_cache_set( $cache_key, $physical_locations, self::CACHE_GROUP, self::CACHE_EXPIRATION );

		return $physical_locations;
	}

	/**
	 * Get service areas that belong to a physical location.
	 *
	 * @since 1.0.0
	 * @param int $physical_location_id Physical location post ID.
	 * @return WP_Post[] Array of service area posts.
	 */
	public function get_service_areas_by_physical_location( $physical_location_id ) {
		if ( ! function_exists( 'get_field' ) ) {
			return array();
		}

		$physical_location_id = absint( $physical_location_id );
		if ( ! $physical_location_id ) {
			return array();
		}

		$cache_key = 'service_areas_' . $physical_location_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$all_locations = $this->get_all_locations();
		$service_areas = array();

		foreach ( $all_locations as $location ) {
			// Skip physical locations.
			if ( $this->is_physical_location( $location->ID ) ) {
				continue;
			}

			$servicing_location = get_field( 'servicing_physical_location', $location->ID );

			// Check if this service area belongs to the physical location.
			if ( $servicing_location && is_object( $servicing_location ) && $servicing_location->ID === $physical_location_id ) {
				$service_areas[] = $location;
			}
		}

		wp_cache_set( $cache_key, $service_areas, self::CACHE_GROUP, self::CACHE_EXPIRATION );

		return $service_areas;
	}

	/**
	 * Clear cached location data.
	 *
	 * @since 1.0.0
	 * @param int $post_id Optional. Specific post ID to clear cache for.
	 */
	public function clear_cache( $post_id = 0 ) {
		if ( $post_id ) {
			$cache_key = 'communities_' . $post_id;
			wp_cache_delete( $cache_key, self::CACHE_GROUP );
			// Also clear service areas cache if this is a physical location.
			wp_cache_delete( 'service_areas_' . $post_id, self::CACHE_GROUP );
		} else {
			// Clear all location caches.
			wp_cache_delete( 'physical_locations', self::CACHE_GROUP );
		}
	}

	/**
	 * Check if an ACF field exists.
	 *
	 * @since 1.1.0
	 * @param string $field_name ACF field name.
	 * @param int    $post_id    Optional. Post ID to check field on.
	 * @return bool True if field exists, false otherwise.
	 */
	public function field_exists( $field_name, $post_id = null ) {
		if ( ! function_exists( 'get_field_object' ) ) {
			ACF_Location_Shortcodes::log( 'ACF not available for field_exists check', array( 'field_name' => $field_name ), 'warning' );
			return false;
		}

		$field = get_field_object( $field_name, $post_id );
		$exists = ! empty( $field );

		ACF_Location_Shortcodes::log(
			'Field existence check',
			array(
				'field_name' => $field_name,
				'post_id' => $post_id,
				'exists' => $exists,
			),
			'info'
		);

		return $exists;
	}

	/**
	 * Get all ACF field names for a post.
	 *
	 * @since 1.1.0
	 * @param int $post_id Post ID.
	 * @return array Array of field names.
	 */
	public function get_field_names( $post_id ) {
		if ( ! function_exists( 'get_field_objects' ) ) {
			return array();
		}

		$fields = get_field_objects( $post_id );
		
		if ( empty( $fields ) ) {
			return array();
		}

		return array_keys( $fields );
	}

	/**
	 * Get location field with validation.
	 *
	 * Returns structured data with success/error information.
	 *
	 * @since 1.1.0
	 * @param string $field_name ACF field name.
	 * @param int    $post_id    Post ID.
	 * @param mixed  $default    Default value if field is empty.
	 * @return array {
	 *     Structured result.
	 *
	 *     @type bool   $success Whether the field was retrieved successfully.
	 *     @type mixed  $value   Field value or default.
	 *     @type string $error   Error message if unsuccessful.
	 *     @type array  $debug   Debug information.
	 * }
	 */
	public function get_location_field_validated( $field_name, $post_id, $default = '' ) {
		$result = array(
			'success' => false,
			'value'   => $default,
			'error'   => '',
			'debug'   => array(),
		);

		// Check if ACF is available.
		if ( ! function_exists( 'get_field' ) ) {
			$result['error'] = __( 'ACF plugin not active', 'acf-location-shortcodes' );
			ACF_Location_Shortcodes::log( 'ACF not available', array( 'field_name' => $field_name ), 'error' );
			return $result;
		}

		// Check if field exists.
		if ( ! $this->field_exists( $field_name, $post_id ) ) {
			$available_fields = $this->get_field_names( $post_id );
			$result['error'] = sprintf(
				__( 'Field "%s" does not exist', 'acf-location-shortcodes' ),
				$field_name
			);
			$result['debug'] = array(
				'field_name'       => $field_name,
				'post_id'          => $post_id,
				'available_fields' => $available_fields,
			);

			ACF_Location_Shortcodes::log(
				'Field does not exist',
				$result['debug'],
				'warning'
			);

			return $result;
		}

		// Get field value.
		$value = get_field( $field_name, $post_id );

		// Check if value is empty and no default provided.
		if ( empty( $value ) && empty( $default ) ) {
			$result['error'] = sprintf(
				__( 'Field "%s" is empty and no default provided', 'acf-location-shortcodes' ),
				$field_name
			);
			$result['debug'] = array(
				'field_name' => $field_name,
				'post_id'    => $post_id,
			);

			ACF_Location_Shortcodes::log(
				'Field is empty',
				$result['debug'],
				'info'
			);

			return $result;
		}

		// Success.
		$result['success'] = true;
		$result['value']   = ! empty( $value ) ? $value : $default;

		ACF_Location_Shortcodes::log(
			'Field retrieved successfully',
			array(
				'field_name' => $field_name,
				'post_id'    => $post_id,
				'has_value'  => ! empty( $value ),
			),
			'info'
		);

		return $result;
	}

	/**
	 * Find similar field names using fuzzy matching.
	 *
	 * @since 1.1.0
	 * @param string $field_name     Field name to match.
	 * @param array  $available_fields Array of available field names.
	 * @return array Array of similar field names.
	 */
	public function find_similar_fields( $field_name, $available_fields ) {
		if ( empty( $available_fields ) ) {
			return array();
		}

		$suggestions = array();
		$field_name_lower = strtolower( $field_name );

		foreach ( $available_fields as $available_field ) {
			$available_lower = strtolower( $available_field );

			// Exact match (shouldn't happen, but just in case).
			if ( $field_name_lower === $available_lower ) {
				continue;
			}

			// Check if one contains the other.
			if ( strpos( $available_lower, $field_name_lower ) !== false ||
			     strpos( $field_name_lower, $available_lower ) !== false ) {
				$suggestions[] = $available_field;
				continue;
			}

			// Check for similar words.
			similar_text( $field_name_lower, $available_lower, $percent );
			if ( $percent > 50 ) {
				$suggestions[] = $available_field;
			}
		}

		// Limit to top 3 suggestions.
		return array_slice( $suggestions, 0, 3 );
	}
}
