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
			return $cached;
		}

		// Get field value.
		$communities_string = get_field( 'surrounding_community_list', $post_id );

		if ( empty( $communities_string ) ) {
			wp_cache_set( $cache_key, array(), self::CACHE_GROUP, self::CACHE_EXPIRATION );
			return array();
		}

		// Parse comma-separated list.
		$communities = $this->parse_comma_list( $communities_string );

		// Cache the result.
		wp_cache_set( $cache_key, $communities, self::CACHE_GROUP, self::CACHE_EXPIRATION );

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
	 * @since 1.0.0
	 * @param int $post_id Location post ID.
	 * @return bool True if physical location, false if service area.
	 */
	public function is_physical_location( $post_id ) {
		if ( ! function_exists( 'get_field' ) ) {
			return false;
		}

		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return false;
		}

		$address = get_field( 'address', $post_id );

		return ! empty( $address );
	}

	/**
	 * Get the servicing physical location for a service area.
	 *
	 * @since 1.0.0
	 * @param int $post_id Location post ID.
	 * @return WP_Post|null Physical location post object or null.
	 */
	public function get_servicing_location( $post_id ) {
		if ( ! function_exists( 'get_field' ) ) {
			return null;
		}

		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return null;
		}

		// Only service areas have a servicing location.
		if ( $this->is_physical_location( $post_id ) ) {
			return null;
		}

		$servicing_location = get_field( 'servicing_physical_location', $post_id );

		if ( $servicing_location && is_object( $servicing_location ) ) {
			return $servicing_location;
		}

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
}
