# ACF Service Management Suite - Testing Documentation

## Overview

This document provides comprehensive testing standards and guidelines for the ACF Service Management Suite plugin. All contributors must follow these standards to maintain code quality and consistency.

## Test Framework

- **Framework:** PHPUnit 9.5+
- **Mocking:** Brain Monkey + Mockery
- **Coverage Target:** 80%+ for critical paths
- **PHP Versions Tested:** 7.4, 8.0, 8.1, 8.2, 8.3

## Directory Structure

```
tests/
├── bootstrap.php              # Test suite bootstrap
├── TestCase.php               # Base test case class
├── mocks/
│   └── wordpress-functions.php # WordPress function mocks
├── Unit/
│   ├── ACFHelpersTest.php     # ACF helpers unit tests
│   ├── ShortcodesTest.php     # Shortcodes unit tests
│   └── [ClassName]Test.php    # One test file per class
└── Integration/
    ├── MultisiteSyncTest.php  # Multisite sync tests
    └── [Feature]Test.php      # Integration tests
```

## Installation

### 1. Install Dependencies

```bash
# Install Composer dependencies
composer install

# Or update existing dependencies
composer update
```

### 2. Verify Installation

```bash
# Check PHPUnit installation
./vendor/bin/phpunit --version

# Should output: PHPUnit 9.5.x or higher
```

## Running Tests

### Run All Tests

```bash
composer test
# or
./vendor/bin/phpunit
```

### Run Unit Tests Only

```bash
composer test:unit
# or
./vendor/bin/phpunit --testsuite unit
```

### Run Integration Tests Only

```bash
composer test:integration
# or
./vendor/bin/phpunit --testsuite integration
```

### Run Specific Test File

```bash
./vendor/bin/phpunit tests/Unit/ACFHelpersTest.php
```

### Run Specific Test Method

```bash
./vendor/bin/phpunit --filter test_parse_comma_list_with_valid_input
```

### Generate Coverage Report

```bash
composer test:coverage
# or
./vendor/bin/phpunit --coverage-html coverage

# Open coverage/index.html in browser
```

## Writing Tests

### Test Naming Conventions

**File Names:**
- Format: `[ClassName]Test.php`
- Examples: `ACFHelpersTest.php`, `ShortcodesTest.php`

**Class Names:**
- Format: `[ClassName]Test`
- Namespace: `ACF_SMS\Tests\Unit` or `ACF_SMS\Tests\Integration`

**Method Names:**
- Format: `test_[method_name]_[scenario]_[expected_result]`
- Examples:
  - `test_parse_comma_list_with_valid_input()`
  - `test_get_location_field_with_default_value()`
  - `test_shortcode_renders_error_for_invalid_post_type()`

### Test Structure Template

```php
<?php
/**
 * [Class Name] Unit Tests
 *
 * Tests for the [Class Name] class.
 *
 * @package ACF_Service_Management_Suite
 */

namespace ACF_SMS\Tests\Unit;

use ACF_SMS\Tests\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * [Class Name] test case.
 */
class ClassNameTest extends TestCase {

	/**
	 * Instance under test.
	 *
	 * @var \ClassName
	 */
	protected $instance;

	/**
	 * Set up before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		
		// Load class file
		require_once ACF_LS_PLUGIN_DIR . 'includes/class-name.php';
		
		// Create instance
		$this->instance = new \ClassName();
	}

	/**
	 * Test [method name] [scenario].
	 */
	public function test_method_name_scenario() {
		// Arrange - Set up test data and expectations
		$expected = 'expected value';
		
		Functions\expect( 'wordpress_function' )
			->once()
			->with( 'param' )
			->andReturn( 'value' );
		
		// Act - Execute the method
		$result = $this->instance->method_name( 'param' );
		
		// Assert - Verify results
		$this->assertEquals( $expected, $result );
	}
}
```

### Unit Test Guidelines

**Unit tests should:**
- Test a single method or function
- Mock all external dependencies (WordPress functions, ACF functions, database)
- Be fast (< 100ms per test)
- Be isolated (no side effects, order-independent)
- Test edge cases (empty values, null, invalid types)
- Test error handling and validation

**Example: Testing a Helper Method**

```php
public function test_parse_comma_list_with_valid_input() {
	// Arrange
	$input = 'Brandon, Dell Rapids, Hartford';
	$expected = array( 'Brandon', 'Dell Rapids', 'Hartford' );
	
	// Act
	$result = $this->helpers->parse_comma_list( $input );
	
	// Assert
	$this->assertEquals( $expected, $result );
	$this->assertIsArray( $result );
	$this->assertCount( 3, $result );
}
```

### Integration Test Guidelines

**Integration tests should:**
- Test interactions between multiple classes
- Test WordPress integration (hooks, filters, actions)
- Test database operations (if applicable)
- Test full feature workflows
- Use minimal mocking (only external services)

**Example: Testing Multisite Sync**

```php
public function test_sync_post_from_master_to_slave() {
	// Arrange
	$master_post = $this->createMockPost( array(
		'ID' => 123,
		'post_type' => 'location',
	) );
	
	Functions\expect( 'get_current_blog_id' )->andReturn( 1 );
	Functions\expect( 'get_site_option' )
		->with( 'acf_sms_sync_enabled' )
		->andReturn( true );
	
	// Act
	$this->sync->sync_post_on_save( $master_post->ID, $master_post, false );
	
	// Assert
	// Verify sync occurred (check mock calls)
	$this->assertTrue( true );
}
```

### Mocking WordPress Functions

**Using Brain Monkey:**

```php
use Brain\Monkey\Functions;

// Simple return value
Functions\when( 'get_option' )->justReturn( 'value' );

// Return argument as-is
Functions\when( 'esc_html' )->returnArg();

// Expect function called once with specific params
Functions\expect( 'update_option' )
	->once()
	->with( 'key', 'value' )
	->andReturn( true );

// Expect function called twice
Functions\expect( 'get_field' )
	->twice()
	->andReturn( 'value' );

// Never called
Functions\expect( 'delete_option' )->never();

// Conditional return
Functions\when( 'get_post_type' )->alias( function( $post_id ) {
	return $post_id === 123 ? 'location' : 'post';
} );
```

### Mocking Classes

**Using Mockery:**

```php
use Mockery;

// Create mock
$mock = Mockery::mock( 'ACF_Location_Shortcodes_ACF_Helpers' );

// Set expectation
$mock->shouldReceive( 'get_location_field' )
	->once()
	->with( 'field_name', 123, 'default' )
	->andReturn( 'field_value' );

// Inject mock
$instance = new ClassName( $mock );
```

### Custom Assertions

The base `TestCase` class provides custom assertions:

```php
// Assert string contains HTML
$this->assertStringContainsHTML( '<div>', $output );

// Assert output contains error
$this->assertContainsError( $output );

// Assert output does NOT contain error
$this->assertNotContainsError( $output );
```

### Test Data Helpers

```php
// Create mock post
$post = $this->createMockPost( array(
	'ID' => 123,
	'post_title' => 'Test Location',
	'post_type' => 'location',
) );

// Create mock ACF field
$field = $this->createMockACFField( 'phone_number', 'text', '555-1234' );
```

## Test Coverage Requirements

### Critical Classes (Must Have 90%+ Coverage)

- `ACF_Location_Shortcodes_ACF_Helpers` - Core data retrieval
- `ACF_Location_Shortcodes_Shortcodes` - All shortcodes
- `ACF_Location_Shortcodes_Multisite_Sync` - Sync logic (especially media sync)

### Important Classes (Must Have 75%+ Coverage)

- `ACF_Location_Shortcodes_Elementor` - Elementor integration
- `ACF_Location_Shortcodes_Admin` - Admin interface
- `ACF_Location_Shortcodes_Network_Admin` - Network settings

### What to Test

**✅ MUST TEST:**
- All public methods
- Input validation and sanitization
- Error handling and edge cases
- Return values and types
- Integration points (hooks, filters)
- Security checks (nonces, capabilities)

**❌ DON'T TEST:**
- WordPress core functions
- Third-party library code
- Simple getters/setters
- Private methods (test through public interface)

## Continuous Integration

Tests run automatically on:

- Every push to any branch
- Every pull request
- Before releases (tag creation)

**GitHub Actions workflow runs:**

1. PHP syntax validation (PHP 7.4-8.3)
2. PHPUnit test suite
3. Code coverage report
4. WordPress Coding Standards (PHPCS)

**Required for merge:**
- All tests passing
- Code coverage ≥ 80% for changed files
- No PHPCS errors

## Test-Driven Development (TDD)

When adding new features, follow TDD:

### 1. Write Failing Test First

```php
public function test_new_feature_returns_expected_value() {
	$result = $this->instance->new_feature( 'input' );
	$this->assertEquals( 'expected', $result );
}
```

### 2. Run Test - Should Fail

```bash
./vendor/bin/phpunit --filter test_new_feature
# Expected: FAIL (method doesn't exist yet)
```

### 3. Implement Minimum Code to Pass

```php
public function new_feature( $input ) {
	return 'expected';
}
```

### 4. Run Test - Should Pass

```bash
./vendor/bin/phpunit --filter test_new_feature
# Expected: PASS
```

### 5. Refactor and Add Edge Cases

```php
public function test_new_feature_with_empty_input() {
	$result = $this->instance->new_feature( '' );
	$this->assertEquals( '', $result );
}

public function test_new_feature_with_null_input() {
	$result = $this->instance->new_feature( null );
	$this->assertNull( $result );
}
```

## Debugging Tests

### Enable Verbose Output

```bash
./vendor/bin/phpunit --verbose
```

### Stop on First Failure

```bash
./vendor/bin/phpunit --stop-on-failure
```

### Print Debug Info in Tests

```php
public function test_something() {
	$result = $this->instance->method();
	
	// Debug output
	var_dump( $result );
	print_r( $result );
	
	$this->assertEquals( 'expected', $result );
}
```

### Test Specific Method

```bash
./vendor/bin/phpunit --filter test_method_name tests/Unit/ClassTest.php
```

## Common Testing Patterns

### Testing Shortcodes

```php
public function test_shortcode_renders_correctly() {
	// Mock WordPress shortcode functions
	Functions\expect( 'shortcode_atts' )->andReturnUsing( function( $defaults, $atts ) {
		return array_merge( $defaults, $atts );
	} );
	
	// Mock data retrieval
	$this->acf_helpers->shouldReceive( 'get_location_field' )
		->andReturn( 'field value' );
	
	// Execute shortcode
	$output = $this->shortcodes->location_info( array( 'field' => 'test' ) );
	
	// Verify output
	$this->assertStringContainsString( 'field value', $output );
	$this->assertNotContainsError( $output );
}
```

### Testing Error Conditions

```php
public function test_method_throws_error_for_invalid_input() {
	// Expect error handling
	Functions\expect( 'current_user_can' )->andReturn( false );
	
	// Execute with invalid input
	$result = $this->instance->method( null );
	
	// Verify error response
	$this->assertContainsError( $result );
	$this->assertStringContainsString( 'Invalid', $result );
}
```

### Testing Cache Behavior

```php
public function test_method_uses_cache_when_available() {
	// First call - cache miss
	Functions\expect( 'wp_cache_get' )->once()->andReturn( false );
	Functions\expect( 'wp_cache_set' )->once()->andReturn( true );
	
	$result1 = $this->instance->cached_method( 123 );
	
	// Second call - cache hit
	Functions\expect( 'wp_cache_get' )->once()->andReturn( 'cached_value' );
	
	$result2 = $this->instance->cached_method( 123 );
	
	$this->assertEquals( 'cached_value', $result2 );
}
```

## Conventional Commits for Tests

When committing test code:

```bash
# Adding new tests
git commit -m "test: add unit tests for ACF helpers class"

# Updating existing tests
git commit -m "test: update shortcode tests for new validation"

# Fixing failing tests
git commit -m "test: fix multisite sync integration tests"

# Adding test coverage
git commit -m "test: improve coverage for media sync functionality"
```

**Version Impact:** `test:` commits do NOT trigger version bumps.

## Pre-Commit Checklist

Before committing code changes:

- [ ] Run full test suite: `composer test`
- [ ] All tests passing
- [ ] No test warnings or errors
- [ ] Code coverage maintained or improved
- [ ] New features have corresponding tests
- [ ] Bug fixes have regression tests
- [ ] Updated test documentation if needed

## Troubleshooting

### "Class not found" errors

**Problem:** PHPUnit can't find your class.

**Solution:**
```bash
# Regenerate autoload files
composer dump-autoload
```

### Brain Monkey function conflicts

**Problem:** Function already declared.

**Solution:**
```php
// Use namespace for WordPress functions
Functions\when( 'function_name' )->...
```

### Mock expectations not met

**Problem:** Test fails with "Method should be called X times but was called Y times."

**Solution:**
```php
// Check mock setup
$mock->shouldReceive( 'method' )
	->times( 2 )  // Adjust expected call count
	->andReturn( 'value' );
```

### Coverage too low

**Problem:** Coverage below 80% threshold.

**Solution:**
1. Identify uncovered lines: `composer test:coverage`
2. Add tests for uncovered code paths
3. Test edge cases (null, empty, invalid input)
4. Test error conditions

## Best Practices Summary

### DO ✅

- Write tests before or during feature development
- Test one thing per test method
- Use descriptive test names
- Mock external dependencies
- Test edge cases and error conditions
- Keep tests fast (< 100ms)
- Maintain 80%+ code coverage
- Follow naming conventions
- Use provided helper methods
- Document complex test setups

### DON'T ❌

- Test WordPress core functionality
- Test third-party code
- Write slow tests (database, file I/O)
- Share state between tests
- Skip test cleanup (tearDown)
- Commit failing tests
- Ignore code coverage drops
- Over-mock (test implementation, not behavior)
- Write tests without assertions
- Duplicate test code

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Brain Monkey Documentation](https://brain-wp.github.io/BrainMonkey/)
- [Mockery Documentation](http://docs.mockery.io/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

---

**Last Updated:** November 6, 2025  
**Maintained By:** Ryan T. M. Reiffenberger  
**Questions:** Open a GitHub issue
