<?php
/**
 * Base Test Case
 *
 * Provides common functionality for all test cases.
 *
 * @package ACF_Service_Management_Suite
 */

namespace ACF_SMS\Tests;

use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Brain\Monkey;
use Mockery;

/**
 * Base test case class.
 */
abstract class TestCase extends PHPUnit_TestCase {

	/**
	 * Set up before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		
		// Set up common WordPress constants.
		$this->setupWordPressConstants();
		
		// Set up common WordPress functions.
		$this->setupWordPressFunctions();
	}

	/**
	 * Tear down after each test.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Set up WordPress constants.
	 */
	protected function setupWordPressConstants() {
		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 3600 );
		}
		if ( ! defined( 'DAY_IN_SECONDS' ) ) {
			define( 'DAY_IN_SECONDS', 86400 );
		}
		if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
			define( 'WEEK_IN_SECONDS', 604800 );
		}
	}

	/**
	 * Set up common WordPress functions.
	 */
	protected function setupWordPressFunctions() {
		// Escaping functions.
		Monkey\Functions\when( 'esc_html' )->returnArg();
		Monkey\Functions\when( 'esc_attr' )->returnArg();
		Monkey\Functions\when( 'esc_url' )->returnArg();
		Monkey\Functions\when( 'esc_js' )->returnArg();
		Monkey\Functions\when( 'sanitize_text_field' )->returnArg();
		Monkey\Functions\when( 'sanitize_html_class' )->returnArg();
		Monkey\Functions\when( 'sanitize_key' )->returnArg();
		Monkey\Functions\when( 'absint' )->alias( 'abs' );
		
		// Translation functions.
		Monkey\Functions\when( '__' )->returnArg();
		Monkey\Functions\when( '_e' )->returnArg();
		Monkey\Functions\when( 'esc_html__' )->returnArg();
		Monkey\Functions\when( 'esc_html_e' )->returnArg();
		Monkey\Functions\when( 'esc_attr__' )->returnArg();
		
		// Cache functions.
		Monkey\Functions\when( 'wp_cache_get' )->justReturn( false );
		Monkey\Functions\when( 'wp_cache_set' )->justReturn( true );
		Monkey\Functions\when( 'wp_cache_delete' )->justReturn( true );
		
		// Current user functions.
		Monkey\Functions\when( 'current_user_can' )->justReturn( false );
		
		// Post functions.
		Monkey\Functions\when( 'get_post' )->alias( function( $post_id ) {
			return (object) array(
				'ID'         => $post_id,
				'post_type'  => 'post',
				'post_title' => 'Test Post',
			);
		} );
		
		Monkey\Functions\when( 'get_the_ID' )->justReturn( 1 );
		
		// Shortcode functions.
		Monkey\Functions\when( 'add_shortcode' )->justReturn( true );
		
		// Action/Filter hooks.
		Monkey\Functions\when( 'do_action' )->justReturn( true );
		Monkey\Functions\when( 'apply_filters' )->returnArg( 2 );
	}

	/**
	 * Create a mock WordPress post.
	 *
	 * @param array $args Post arguments.
	 * @return object Mock post object.
	 */
	protected function createMockPost( array $args = array() ) {
		$defaults = array(
			'ID'            => 1,
			'post_title'    => 'Test Post',
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_author'   => 1,
			'post_name'     => 'test-post',
			'post_content'  => 'Test content',
			'post_excerpt'  => 'Test excerpt',
			'post_modified' => '2025-11-06 12:00:00',
		);

		$post_data = wp_parse_args( $args, $defaults );

		return (object) $post_data;
	}

	/**
	 * Create a mock ACF field.
	 *
	 * @param string $name  Field name.
	 * @param string $type  Field type.
	 * @param mixed  $value Field value.
	 * @return array Mock field array.
	 */
	protected function createMockACFField( $name, $type, $value ) {
		return array(
			'key'   => 'field_' . md5( $name ),
			'name'  => $name,
			'type'  => $type,
			'value' => $value,
		);
	}

	/**
	 * Assert that a string contains HTML.
	 *
	 * @param string $needle   The substring to search for.
	 * @param string $haystack The string to search in.
	 * @param string $message  Optional failure message.
	 */
	protected function assertStringContainsHTML( $needle, $haystack, $message = '' ) {
		$this->assertStringContainsString(
			$needle,
			$haystack,
			$message ?: "Failed asserting that HTML contains '{$needle}'"
		);
	}

	/**
	 * Assert that output contains error message.
	 *
	 * @param string $output  The output to check.
	 * @param string $message Optional failure message.
	 */
	protected function assertContainsError( $output, $message = '' ) {
		$this->assertStringContainsString(
			'acf-sms-error',
			$output,
			$message ?: 'Failed asserting that output contains error class'
		);
	}

	/**
	 * Assert that output does not contain error message.
	 *
	 * @param string $output  The output to check.
	 * @param string $message Optional failure message.
	 */
	protected function assertNotContainsError( $output, $message = '' ) {
		$this->assertStringNotContainsString(
			'acf-sms-error',
			$output,
			$message ?: 'Failed asserting that output does not contain error class'
		);
	}
}

/**
 * Polyfill for wp_parse_args if not available.
 *
 * @param string|array $args     Value to merge with defaults.
 * @param array        $defaults Optional. Array that serves as the defaults.
 * @return array Merged array.
 */
if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$parsed_args = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$parsed_args =& $args;
		} else {
			parse_str( $args, $parsed_args );
		}

		if ( is_array( $defaults ) && $defaults ) {
			return array_merge( $defaults, $parsed_args );
		}
		return $parsed_args;
	}
}
