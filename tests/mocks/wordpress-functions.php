<?php
/**
 * WordPress Function Mocks
 *
 * Provides mock implementations of WordPress functions for testing.
 *
 * @package ACF_Service_Management_Suite
 */

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Mock wp_json_encode function.
	 *
	 * @param mixed $data    Data to encode.
	 * @param int   $options Optional. Options to be passed to json_encode().
	 * @param int   $depth   Optional. Maximum depth.
	 * @return string|false JSON encoded string or false on failure.
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Mock wp_kses_post function.
	 *
	 * @param string $data Data to sanitize.
	 * @return string Sanitized data.
	 */
	function wp_kses_post( $data ) {
		return $data; // Simplified for testing.
	}
}

if ( ! function_exists( 'get_post_type' ) ) {
	/**
	 * Mock get_post_type function.
	 *
	 * @param int|WP_Post $post Post ID or object.
	 * @return string|false Post type or false.
	 */
	function get_post_type( $post = null ) {
		if ( is_object( $post ) && isset( $post->post_type ) ) {
			return $post->post_type;
		}
		return false;
	}
}

if ( ! function_exists( 'get_the_ID' ) ) {
	/**
	 * Mock get_the_ID function.
	 *
	 * @return int|false Post ID or false.
	 */
	function get_the_ID() {
		global $post;
		return isset( $post->ID ) ? $post->ID : false;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	/**
	 * Mock get_post function.
	 *
	 * @param int $post_id Post ID.
	 * @return object|null Post object or null.
	 */
	function get_post( $post_id ) {
		// Return a basic mock post object
		return (object) array(
			'ID'        => $post_id,
			'post_type' => 'post',
		);
	}
}

if ( ! function_exists( 'add_shortcode' ) ) {
	/**
	 * Mock add_shortcode function.
	 *
	 * @param string   $tag  Shortcode tag.
	 * @param callable $callback Shortcode callback.
	 */
	function add_shortcode( $tag, $callback ) {
		// No-op for tests
	}
}

if ( ! class_exists( 'ACF_Location_Shortcodes' ) ) {
	/**
	 * Mock main plugin class for testing.
	 */
	class ACF_Location_Shortcodes {
		/**
		 * Mock log method.
		 *
		 * @param string $message Log message.
		 * @param array  $data    Optional data.
		 * @param string $level   Log level.
		 */
		public static function log( $message, $data = array(), $level = 'info' ) {
			// No-op for tests
		}
	}
}
