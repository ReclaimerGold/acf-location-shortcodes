<?php
/**
 * ACF Helpers Unit Tests
 *
 * Tests for the ACF_Location_Shortcodes_ACF_Helpers class.
 *
 * @package ACF_Service_Management_Suite
 */

namespace ACF_SMS\Tests\Unit;

use ACF_SMS\Tests\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * ACF Helpers test case.
 */
class ACFHelpersTest extends TestCase {

	/**
	 * ACF Helpers instance.
	 *
	 * @var \ACF_Location_Shortcodes_ACF_Helpers
	 */
	protected $helpers;

	/**
	 * Set up before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		
		// Load the class file.
		require_once ACF_LS_PLUGIN_DIR . 'includes/class-acf-helpers.php';
		
		$this->helpers = new \ACF_Location_Shortcodes_ACF_Helpers();
	}

	/**
	 * Test parse_comma_list with valid input.
	 */
	public function test_parse_comma_list_with_valid_input() {
		$input    = 'Brandon, Dell Rapids, Hartford, Tea';
		$expected = array( 'Brandon', 'Dell Rapids', 'Hartford', 'Tea' );
		
		$result = $this->helpers->parse_comma_list( $input );
		
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test parse_comma_list with extra spaces.
	 */
	public function test_parse_comma_list_with_extra_spaces() {
		$input    = '  Brandon  ,  Dell Rapids  ,  Hartford  ';
		$expected = array( 'Brandon', 'Dell Rapids', 'Hartford' );
		
		$result = $this->helpers->parse_comma_list( $input );
		
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test parse_comma_list with empty string.
	 */
	public function test_parse_comma_list_with_empty_string() {
		$result = $this->helpers->parse_comma_list( '' );
		
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test parse_comma_list with single item.
	 */
	public function test_parse_comma_list_with_single_item() {
		$input    = 'Sioux Falls';
		$expected = array( 'Sioux Falls' );
		
		$result = $this->helpers->parse_comma_list( $input );
		
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test parse_comma_list filters empty values.
	 */
	public function test_parse_comma_list_filters_empty_values() {
		$input    = 'Brandon,,Dell Rapids,  ,Hartford';
		$expected = array( 'Brandon', 'Dell Rapids', 'Hartford' );
		
		$result = $this->helpers->parse_comma_list( $input );
		
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test is_physical_location with address (top-level location).
	 */
	public function test_is_physical_location_with_address() {
		$post_id = 123;
		
		Functions\expect( 'get_post' )
			->once()
			->with( $post_id )
			->andReturn( (object) array(
				'ID'          => $post_id,
				'post_type'   => 'location',
				'post_parent' => 0, // Top-level location (no parent).
			) );

		$result = $this->helpers->is_physical_location( $post_id );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_physical_location without address (child location).
	 */
	public function test_is_physical_location_without_address() {
		$post_id = 456;
		
		Functions\expect( 'get_post' )
			->once()
			->with( $post_id )
			->andReturn( (object) array(
				'ID'          => $post_id,
				'post_type'   => 'location',
				'post_parent' => 123, // Has a parent (service area).
			) );

		$result = $this->helpers->is_physical_location( 456 );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_physical_location with null address.
	 */
	public function test_is_physical_location_with_null_address() {
		Functions\expect( 'get_field' )
			->once()
			->with( 'address', 789 )
			->andReturn( null );

		$result = $this->helpers->is_physical_location( 789 );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_location_field with valid field.
	 */
	public function test_get_location_field_with_valid_field() {
		Functions\expect( 'get_field' )
			->once()
			->with( 'phone_number', 123 )
			->andReturn( '(605) 555-1234' );

		$result = $this->helpers->get_location_field( 'phone_number', 123 );

		$this->assertEquals( '(605) 555-1234', $result );
	}

	/**
	 * Test get_location_field with default value.
	 */
	public function test_get_location_field_with_default_value() {
		Functions\expect( 'get_field' )
			->once()
			->with( 'phone_number', 123 )
			->andReturn( '' );

		$result = $this->helpers->get_location_field( 'phone_number', 123, 'N/A' );

		$this->assertEquals( 'N/A', $result );
	}

	/**
	 * Test get_location_field with null returns default.
	 */
	public function test_get_location_field_with_null_returns_default() {
		Functions\expect( 'get_field' )
			->once()
			->with( 'missing_field', 123 )
			->andReturn( null );

		$result = $this->helpers->get_location_field( 'missing_field', 123, 'Default' );

		$this->assertEquals( 'Default', $result );
	}

	/**
	 * Test get_servicing_location with parent location.
	 */
	public function test_get_servicing_location_with_parent() {
		$parent_post = $this->createMockPost( array( 'ID' => 999 ) );

		Functions\expect( 'get_field' )
			->once()
			->with( 'servicing_physical_location', 123 )
			->andReturn( $parent_post );

		$result = $this->helpers->get_servicing_location( 123 );

		$this->assertEquals( 999, $result );
	}

	/**
	 * Test get_servicing_location without parent.
	 */
	public function test_get_servicing_location_without_parent() {
		Functions\expect( 'get_field' )
			->once()
			->with( 'servicing_physical_location', 123 )
			->andReturn( null );

		$result = $this->helpers->get_servicing_location( 123 );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_surrounding_communities with valid data.
	 */
	public function test_get_surrounding_communities_with_valid_data() {
		Functions\expect( 'get_field' )
			->once()
			->with( 'surrounding_community_list', 123 )
			->andReturn( 'Brandon, Dell Rapids, Hartford' );

		$result = $this->helpers->get_surrounding_communities( 123 );

		$expected = array( 'Brandon', 'Dell Rapids', 'Hartford' );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test get_surrounding_communities with empty data.
	 */
	public function test_get_surrounding_communities_with_empty_data() {
		Functions\expect( 'get_field' )
			->once()
			->with( 'surrounding_community_list', 123 )
			->andReturn( '' );

		$result = $this->helpers->get_surrounding_communities( 123 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test field_exists when ACF function exists.
	 */
	public function test_field_exists_when_acf_available() {
		Functions\expect( 'get_field_object' )
			->once()
			->with( 'phone_number', 123 )
			->andReturn( array( 'name' => 'phone_number' ) );

		$result = $this->helpers->field_exists( 'phone_number', 123 );

		$this->assertTrue( $result );
	}

	/**
	 * Test field_exists when field does not exist.
	 */
	public function test_field_exists_when_field_missing() {
		Functions\expect( 'get_field_object' )
			->once()
			->with( 'invalid_field', 123 )
			->andReturn( false );

		$result = $this->helpers->field_exists( 'invalid_field', 123 );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_field_names returns array of field names.
	 */
	public function test_get_field_names_returns_array() {
		Functions\expect( 'get_field_objects' )
			->once()
			->with( 123 )
			->andReturn( array(
				'phone_number' => array( 'name' => 'phone_number' ),
				'address'      => array( 'name' => 'address' ),
			) );

		$result = $this->helpers->get_field_names( 123 );

		$expected = array( 'phone_number', 'address' );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test get_field_names with no fields.
	 */
	public function test_get_field_names_with_no_fields() {
		Functions\expect( 'get_field_objects' )
			->once()
			->with( 123 )
			->andReturn( false );

		$result = $this->helpers->get_field_names( 123 );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test clear_cache functionality.
	 */
	public function test_clear_cache() {
		Functions\expect( 'wp_cache_delete' )
			->once()
			->with( 'communities_123', 'acf_sms_locations' )
			->andReturn( true );

		Functions\expect( 'do_action' )
			->once()
			->with( 'acf_sms_cache_cleared', 123 );

		$this->helpers->clear_cache( 123 );

		// If we get here without errors, the test passes.
		$this->assertTrue( true );
	}
}
