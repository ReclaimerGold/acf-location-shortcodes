<?php
/**
 * Shortcodes Unit Tests
 *
 * Tests for the ACF_Location_Shortcodes_Shortcodes class.
 *
 * @package ACF_Service_Management_Suite
 */

namespace ACF_SMS\Tests\Unit;

use ACF_SMS\Tests\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Shortcodes test case.
 */
class ShortcodesTest extends TestCase {

	/**
	 * Shortcodes instance.
	 *
	 * @var \ACF_Location_Shortcodes_Shortcodes
	 */
	protected $shortcodes;

	/**
	 * Mock ACF helpers.
	 *
	 * @var Mockery\MockInterface
	 */
	protected $acf_helpers;

	/**
	 * Set up before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		
		// Load the class files.
		require_once ACF_LS_PLUGIN_DIR . 'includes/class-acf-helpers.php';
		require_once ACF_LS_PLUGIN_DIR . 'includes/class-shortcodes.php';
		
		// Mock ACF helpers.
		$this->acf_helpers = Mockery::mock( 'ACF_Location_Shortcodes_ACF_Helpers' );
		
		// Create shortcodes instance with mocked helpers.
		$this->shortcodes = new \ACF_Location_Shortcodes_Shortcodes( $this->acf_helpers );
	}

	/**
	 * Test location_communities shortcode with valid data.
	 */
	public function test_location_communities_shortcode_with_valid_data() {
		$post_id = 123;
		$communities = array( 'Brandon', 'Dell Rapids', 'Hartford' );

		Functions\expect( 'get_the_ID' )->andReturn( $post_id );
		Functions\expect( 'get_post_type' )->with( $post_id )->andReturn( 'location' );
		Functions\expect( 'shortcode_atts' )->andReturnUsing( function( $defaults, $atts ) {
			return array_merge( $defaults, $atts );
		} );

		$this->acf_helpers->shouldReceive( 'get_surrounding_communities' )
			->once()
			->with( $post_id )
			->andReturn( $communities );

		$result = $this->shortcodes->render_communities_list( array() );

		$this->assertStringContainsString( 'Brandon', $result );
		$this->assertStringContainsString( 'Dell Rapids', $result );
		$this->assertStringContainsString( 'Hartford', $result );
		$this->assertNotContainsError( $result );
	}

	/**
	 * Test location_communities shortcode with no communities found.
	 */
	public function test_location_communities_shortcode_with_no_communities() {
		$post_id = 1; // Match get_the_ID mock.

		// Mock user capability to see errors.
		Functions\expect( 'current_user_can' )->with( 'edit_posts' )->andReturn( true );
		Functions\expect( 'get_the_ID' )->andReturn( $post_id );
		Functions\expect( 'get_post_type' )->with( $post_id )->andReturn( 'location' );
		Functions\expect( 'shortcode_atts' )->andReturnUsing( function( $defaults, $atts ) {
			return array_merge( $defaults, $atts );
		} );

		$this->acf_helpers->shouldReceive( 'get_surrounding_communities' )
			->once()
			->with( $post_id )
			->andReturn( array() );

		$result = $this->shortcodes->render_communities_list( array() );

		$this->assertContainsError( $result );
		$this->assertStringContainsString( 'No surrounding communities found', $result );
	}

	/**
	 * Test location_communities shortcode with invalid post type.
	 */
	public function test_location_communities_shortcode_with_invalid_post_type() {
		$post_id = 1; // Match get_the_ID mock.

		// Mock user capability to see errors.
		Functions\expect( 'current_user_can' )->with( 'edit_posts' )->andReturn( true );
		Functions\expect( 'get_the_ID' )->andReturn( $post_id );
		Functions\expect( 'get_post_type' )->with( $post_id )->andReturn( 'post' );
		Functions\expect( 'shortcode_atts' )->andReturnUsing( function( $defaults, $atts ) {
			return array_merge( $defaults, $atts );
		} );

		$result = $this->shortcodes->render_communities_list( array() );

		$this->assertContainsError( $result );
		$this->assertStringContainsString( 'Invalid location', $result );
	}

	/**
	 * Test location_info shortcode with valid field.
	 */
	public function test_location_info_shortcode_with_valid_field() {
		$post_id = 1; // Match get_the_ID mock.

		Functions\expect( 'get_the_ID' )->andReturn( $post_id );
		Functions\expect( 'get_post_type' )->with( $post_id )->andReturn( 'location' );
		Functions\expect( 'shortcode_atts' )->andReturnUsing( function( $defaults, $atts ) {
			return array_merge( $defaults, $atts );
		} );

		$this->acf_helpers->shouldReceive( 'get_location_field' )
			->once()
			->with( 'phone_number', $post_id, '' )
			->andReturn( '(605) 555-1234' );

		$result = $this->shortcodes->render_location_info( array( 'field' => 'phone_number' ) );

		$this->assertStringContainsString( '(605) 555-1234', $result );
		$this->assertNotContainsError( $result );
	}

	/**
	 * Test location_info shortcode with missing field parameter.
	 */
	public function test_location_info_shortcode_with_missing_field() {
		// Mock user capability to see errors.
		Functions\expect( 'current_user_can' )->with( 'edit_posts' )->andReturn( true );
		Functions\expect( 'shortcode_atts' )->andReturnUsing( function( $defaults, $atts ) {
			return array_merge( $defaults, $atts );
		} );

		$result = $this->shortcodes->render_location_info( array() );

		$this->assertContainsError( $result );
		$this->assertStringContainsString( 'field', $result );
	}

	/**
	 * Test location_address shortcode for physical location.
	 */
	public function test_location_address_shortcode_for_physical_location() {
		$post_id = 1; // Match get_the_ID mock.
		$address = '123 Main St, Sioux Falls, SD 57104';

		Functions\expect( 'get_the_ID' )->andReturn( $post_id );
		Functions\expect( 'get_post_type' )->with( $post_id )->andReturn( 'location' );
		Functions\expect( 'shortcode_atts' )->andReturnUsing( function( $defaults, $atts ) {
			return array_merge( $defaults, $atts );
		} );

		$this->acf_helpers->shouldReceive( 'is_physical_location' )
			->once()
			->with( $post_id )
			->andReturn( true );

		$this->acf_helpers->shouldReceive( 'get_location_field' )
			->once()
			->with( 'address', $post_id, '' )
			->andReturn( $address );

		$result = $this->shortcodes->render_location_address( array() );

		$this->assertStringContainsString( $address, $result );
		$this->assertNotContainsError( $result );
	}

	/**
	 * Test location_address shortcode for service area with parent.
	 */
	public function test_location_address_shortcode_for_service_area() {
		$service_area_id = 1; // Match get_the_ID mock.
		$parent_id = 456;
		$parent_address = '789 Oak Ave, Sioux Falls, SD 57105';

		Functions\expect( 'get_the_ID' )->andReturn( $service_area_id );
		Functions\expect( 'get_post_type' )->with( $service_area_id )->andReturn( 'location' );
		Functions\expect( 'shortcode_atts' )->andReturnUsing( function( $defaults, $atts ) {
			return array_merge( $defaults, $atts );
		} );

		$this->acf_helpers->shouldReceive( 'is_physical_location' )
			->once()
			->with( $service_area_id )
			->andReturn( false );

		$this->acf_helpers->shouldReceive( 'get_servicing_location' )
			->once()
			->with( $service_area_id )
			->andReturn( $parent_id );

		Functions\expect( 'get_post_type' )->with( $parent_id )->andReturn( 'location' );

		$this->acf_helpers->shouldReceive( 'get_location_field' )
			->once()
			->with( 'address', $parent_id, '' )
			->andReturn( $parent_address );

		$result = $this->shortcodes->render_location_address( array() );

		$this->assertStringContainsString( $parent_address, $result );
		$this->assertNotContainsError( $result );
	}
}
