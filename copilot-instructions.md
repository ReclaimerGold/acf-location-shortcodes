# Copilot Instructions - ACF Service Management Suite

## Project Identity

**Plugin Name:** ACF Service Management Suite  
**Purpose:** Complete service business management for WordPress - locations, service areas, and team members  
**Author:** Falls Technology Group, LLC  
**Developer:** Ryan T. M. Reiffenberger (github.com/ReclaimerGold)  
**License:** GPL v2 or later  
**Support:** https://github.com/ReclaimerGold/acf-service-management-suite  
**Current Version:** 2.0.0

## Core Principles

### 1. NO FLUFF DOCUMENTATION
- Every doc file must serve a specific, essential purpose
- Combine related content - no fragmentation across multiple files
- Use inline PHPDoc comments for code documentation
- README.md is the primary user-facing doc (setup, usage, examples)
- DEVELOP.md for extension/contribution only
- CHANGELOG.md for version history only
- No PLAN.md, USAGE.md, ROADMAP.md, or other redundant docs

### 2. MODERN PHP WORDPRESS PLUGIN STANDARDS
- WordPress 5.8+ / PHP 7.4+ minimum
- Follow WordPress Coding Standards (WPCS) strictly
- Singleton pattern for main plugin class
- Namespacing not required but class prefixing mandatory
- PSR-4-style autoloading structure (manual requires acceptable)
- Proper sanitization, escaping, and nonce verification
- i18n ready with proper text domain
- Object caching with reasonable expiration (1 hour default)

### 3. SECURITY FIRST
```php
// ALWAYS check capabilities before sensitive operations
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'Insufficient permissions', 'acf-sms' ) );
}

// ALWAYS sanitize input
$location_id = absint( $_POST['location_id'] );
$field_name = sanitize_text_field( $_POST['field'] );

// ALWAYS escape output
echo esc_html( $title );
echo esc_attr( $class );
echo esc_url( $link );

// ALWAYS verify nonces for forms/AJAX
check_ajax_referer( 'acf_sms_action', 'nonce' );
```

### 4. PERFORMANCE OPTIMIZATION
```php
// Use WordPress object cache
$cached = wp_cache_get( $key, 'acf_sms_locations' );
if ( false === $cached ) {
    $cached = expensive_operation();
    wp_cache_set( $key, $cached, 'acf_sms_locations', HOUR_IN_SECONDS );
}

// Lazy load integrations
if ( did_action( 'elementor/loaded' ) ) {
    // Only load Elementor integration if needed
}

// Minimize database queries
// Cache ACF field checks
// Use transients for expensive admin checks
```

### 5. ERROR HANDLING PHILOSOPHY
- Errors must be **actionable** - tell user how to fix it
- Include **context** - what was attempted, what was found
- Provide **debug data** when ACF_LS_DEBUG enabled
- Only show debug to users with `edit_posts` capability
- Log important events to error_log when debug enabled

## Technical Stack

**Required:**
- WordPress 5.8+
- PHP 7.4+
- ACF 5.0+ (Free or Pro)

**Optional:**
- Elementor 3.0+ (for query filtering)

**Included:**
- Complete post type structure (`location`, `team-member`)
- Pre-configured ACF field groups (15 total fields)
- Team Member Type taxonomy

## File Structure

```
acf-service-management-suite/
├── acf-service-management-suite.php     # Bootstrap (constants, singleton, hooks)
├── includes/
│   ├── class-acf-helpers.php            # ACF data retrieval + caching
│   ├── class-shortcodes.php             # All shortcode handlers
│   └── class-elementor-integration.php  # Elementor query filters
├── assets/
│   ├── css/shortcodes.css               # Minimal frontend styles
│   └── js/elementor-controls.js         # Elementor editor controls
├── acf-export-2025-10-28.json           # Field structure (import ready)
├── README.md                            # PRIMARY documentation (setup + usage)
├── DEVELOP.md                           # Extension/contribution guide
├── CHANGELOG.md                         # Version history
└── copilot-instructions.md              # This file
```

**NO OTHER .md FILES ALLOWED** - Consolidate or delete.

## Class Architecture

### Main Plugin Class: `ACF_Location_Shortcodes`
```php
class ACF_Location_Shortcodes {
    private static $instance = null;
    public $shortcodes;
    public $acf_helpers;
    public $elementor;
    
    public static function instance() { }
    private function __construct() { }
    private function init_hooks() { }
    public function check_dependencies() { }
    public function init() { }
    private function includes() { }
    public static function log( $message, $data = array(), $level = 'info' ) { }
}
```

### ACF Helpers: `ACF_Location_Shortcodes_ACF_Helpers`
- `get_surrounding_communities( $post_id )` - Get + cache communities
- `parse_comma_list( $string )` - Parse CSV to array
- `is_physical_location( $post_id )` - Check if has address
- `get_servicing_location( $post_id )` - Get parent location
- `get_location_field( $field, $post_id, $default )` - Generic getter
- `field_exists( $field_name, $post_id )` - Validation (v1.1.0+)
- `get_field_names( $post_id )` - List all fields (v1.1.0+)
- `clear_cache( $post_id )` - Manual cache clear

### Shortcodes: `ACF_Location_Shortcodes_Shortcodes`
- `[location_communities]` - Display surrounding communities
- `[location_info field="..."]` - Display any ACF field
- `[location_list]` - Location directory (two modes)
- `[location_address]` - Physical address (auto parent lookup)
- `render_error( $message, $debug_data = array() )` - Error display

### Elementor: `ACF_Location_Shortcodes_Elementor`
- Hooks: `elementor/element/before_section_end` (add controls)
- Hooks: `elementor/query/query_args` (filter queries)
- Controls: Filter toggle, location selector, AND/OR mode
- Query modification for relationship field filtering

## ACF Field Reference

### Location Post Type Fields (11)
- `service_area_shorthand` (Text) - "Sioux Falls, SD"
- `address` (Text) - Physical address (empty = service area)
- `phone_number` (Text) - Contact number
- `location_site_url` (URL) - Dedicated site
- `servicing_physical_location` (Post Object) - Parent location
- `full_service_area_name` (Text) - "Greater Sioux Falls"
- `located_near` (Text) - "Near Falls Park"
- `metro_area_name` (Text) - "Sioux Falls Metro"
- `county_name` (Text) - "Minnehaha County"
- `surrounding_community_list` (Text) - CSV communities
- `team_members_assigned` (Relationship) - Team members

### Team Member Post Type Fields (4)
- `profile_picture` (Image) - Photo
- `title` (Text) - Job title
- `location` (Select Multiple) - Assigned locations
- `full_profile_url` (URL) - Full bio link

**Location Type Logic:**
- WITH `address` = Physical Location
- WITHOUT `address` = Service Area

## Coding Patterns

### Shortcode Handler Template
```php
public function render_shortcode_name( $atts ) {
    // Parse attributes with defaults
    $atts = shortcode_atts( array(
        'location_id' => get_the_ID(),
        'field'       => '',
        'default'     => '',
        'class'       => '',
    ), $atts, 'shortcode_name' );
    
    // Sanitize inputs
    $location_id = absint( $atts['location_id'] );
    $field       = sanitize_text_field( $atts['field'] );
    $class       = sanitize_html_class( $atts['class'] );
    
    // Validate
    if ( empty( $location_id ) || 'location' !== get_post_type( $location_id ) ) {
        return $this->render_error(
            sprintf( __( 'Invalid location ID: %d', 'acf-sms' ), $location_id )
        );
    }
    
    // Get data with error handling
    $result = $this->acf_helpers->get_location_field_validated( $field, $location_id );
    if ( ! $result['success'] ) {
        return $this->render_error( $result['error'], $result['debug'] );
    }
    
    // Build output
    ob_start();
    ?>
    <div class="acf-sms-<?php echo esc_attr( $class ); ?>">
        <?php echo esc_html( $result['data'] ); ?>
    </div>
    <?php
    return ob_get_clean();
}
```

### Error Rendering Pattern (v1.1.0+)
```php
private function render_error( $message, $debug_data = array() ) {
    // Basic error for all users
    $output = sprintf(
        '<div class="acf-sms-error"><p>%s</p></div>',
        esc_html( $message )
    );
    
    // Debug data only for editors when debug enabled
    if ( defined( 'ACF_LS_DEBUG' ) && ACF_LS_DEBUG && current_user_can( 'edit_posts' ) && ! empty( $debug_data ) ) {
        $output .= sprintf(
            '<details class="acf-sms-debug"><summary>Debug Info</summary><pre>%s</pre></details>',
            esc_html( print_r( $debug_data, true ) )
        );
    }
    
    return $output;
}
```

### Logging Pattern (v1.1.0+)
```php
ACF_Location_Shortcodes::log(
    'Field validation failed',
    array(
        'field' => $field_name,
        'post_id' => $post_id,
        'available_fields' => $available,
    ),
    'warning' // or 'info', 'error'
);
```

### Caching Pattern
```php
$cache_key = 'communities_' . $post_id;
$cached = wp_cache_get( $cache_key, 'acf_sms_locations' );

if ( false !== $cached ) {
    ACF_Location_Shortcodes::log( 'Cache hit', array( 'key' => $cache_key ), 'info' );
    return $cached;
}

// Expensive operation
$data = $this->get_expensive_data( $post_id );

wp_cache_set( $cache_key, $data, 'acf_sms_locations', HOUR_IN_SECONDS );
ACF_Location_Shortcodes::log( 'Cache miss', array( 'key' => $cache_key ), 'info' );

return $data;
```

## Version Management

### CRITICAL: Version Synchronization Rules

When bumping versions, update in this exact order:

1. **CHANGELOG.md** - Add new version section FIRST
   ```markdown
   ## [2.0.0] - 2025-10-28
   ### Added
   - Complete post type structure included
   ### Changed
   - Rebranded to ACF Service Management Suite
   ```

2. **acf-service-management-suite.php** - Plugin header + constant
   ```php
   * Version: 2.0.0
   define( 'ACF_LS_VERSION', '2.0.0' );
   ```

3. **README.md** - Badge + Credits section
   ```markdown
   [![Version](https://img.shields.io/badge/Version-2.0.0-green)]
   **Version:** 2.0.0
   **Last Updated:** October 28, 2025
   ```

4. **copilot-instructions.md** - Current Version field
   ```markdown
   **Current Version:** 2.0.0
   ```

**Pre-Release Checklist:**
- [ ] All 4 files have matching version numbers
- [ ] CHANGELOG has complete list of changes
- [ ] All dates are current date
- [ ] Git tag created: `git tag -a v2.0.0 -m "Version 2.0.0"`
- [ ] README credits updated with correct version

**Semantic Versioning:**
- **MAJOR** (X.0.0) - Breaking changes, renamed plugin, architecture overhaul
- **MINOR** (2.X.0) - New features, new shortcodes, backwards compatible
- **PATCH** (2.0.X) - Bug fixes, security patches, performance tweaks

## Development Workflow

### Before Writing Code
1. Check if ACF function exists: `if ( ! function_exists( 'get_field' ) )`
2. Validate post ID and post type
3. Check field exists before calling `get_field()`
4. Plan error messages - what will user see?
5. Consider debug output - what helps troubleshooting?

### During Development
1. Follow WPCS (WordPress Coding Standards)
2. Add PHPDoc comments to all methods
3. Use i18n functions: `__()`, `_e()`, `esc_html__()`, etc.
4. Test with ACF Free AND Pro
5. Test with Elementor Free AND Pro
6. Test on mobile devices

### After Code Complete
1. Run PHP syntax check: `php -l filename.php`
2. Check for errors: WP_DEBUG enabled
3. Test all shortcodes with various attributes
4. Test Elementor integration with real data
5. Verify caching works correctly
6. Update CHANGELOG.md
7. Bump version numbers (all 4 files)

## Documentation Standards

### README.md Structure (PRIMARY DOCS)
```markdown
# Plugin Name
Badges, brief description

## Why Use This Plugin?
Bullet points only

## Features
Concise feature list with code examples

## Requirements
Min/recommended versions

## Installation
3 methods max (WordPress Admin, FTP, Git)

## Quick Start Guide
5 steps to first working shortcode

## Documentation
Shortcode reference tables inline (no separate files)

## Usage Examples
Real-world scenarios with code

## ACF Field Schema
Complete field reference + import instructions

## Troubleshooting
Common issues with solutions (no fluff)

## Support
GitHub issues link only

## License
Full GPL v2 text

## Credits
Author, company, version, date
```

### What NOT to Include in Docs
- ❌ Redundant "About" or "Overview" sections
- ❌ Separate USAGE.md file (integrate into README)
- ❌ Separate PLAN.md file (architecture goes in DEVELOP.md)
- ❌ ROADMAP.md (future features go in GitHub Issues/Projects)
- ❌ Multiple "Getting Started" sections
- ❌ Excessive screenshots (code examples better)
- ❌ Marketing copy or sales language
- ❌ Repeated content across multiple files

### DEVELOP.md Structure (DEVELOPERS ONLY)
```markdown
# Development Guide
- Local environment setup
- Plugin architecture
- Coding standards
- Extension patterns (how to add shortcodes, fields, integrations)
- Testing procedures
- Contribution workflow
- Release process
```

### CHANGELOG.md Structure (VERSION HISTORY ONLY)
```markdown
# Changelog
## [Unreleased]
## [2.0.0] - 2025-10-28
### Added
### Changed
### Fixed
### Security
```

## CSS Standards

### BEM Methodology
```css
/* Block */
.acf-sms-communities { }

/* Element */
.acf-sms-communities__item { }
.acf-sms-communities__emoji { }
.acf-sms-communities__text { }

/* Modifier */
.acf-sms-communities--vertical { }
.acf-sms-communities--grid { }
```

### Minimal CSS Philosophy
- Ship only essential styles
- Use flexbox/grid for layouts
- Mobile-first responsive
- No `!important` unless absolutely necessary
- Theme compatibility - use specificity wisely

## Security Checklist

### Input Validation
- [ ] All `$_GET`, `$_POST`, `$_REQUEST` sanitized
- [ ] ACF field data sanitized (it comes from DB, could be tampered)
- [ ] Shortcode attributes parsed with `shortcode_atts()`
- [ ] Post IDs validated with `absint()` and post type check

### Output Escaping
- [ ] All echo/print statements escaped
- [ ] Use `esc_html()` for text
- [ ] Use `esc_attr()` for attributes
- [ ] Use `esc_url()` for URLs
- [ ] Use `wp_kses_post()` for HTML content

### Capability Checks
- [ ] Admin actions check `manage_options` or similar
- [ ] Plugin install checks `install_plugins` + `activate_plugins`
- [ ] Debug output checks `edit_posts` minimum

### Nonce Verification
- [ ] All forms include nonce field
- [ ] AJAX requests verify nonce
- [ ] Use `wp_create_nonce()` and `check_ajax_referer()`

## Common Mistakes to Avoid

1. **Don't echo in shortcodes** - Always return string
2. **Don't assume ACF exists** - Check `function_exists('get_field')`
3. **Don't skip validation** - Verify post type before using post ID
4. **Don't hardcode IDs** - Make configurable via attributes
5. **Don't create separate doc files** - Consolidate into README/DEVELOP
6. **Don't write vague errors** - Be specific and actionable
7. **Don't forget mobile** - Test responsive layouts
8. **Don't skip version sync** - Update all 4 files

## Testing Checklist

### Manual Testing
- [ ] Install on fresh WordPress install
- [ ] Import ACF fields from JSON
- [ ] Create test locations (physical + service areas)
- [ ] Test all shortcodes with various attributes
- [ ] Test Elementor query filtering
- [ ] Test with WP_DEBUG enabled (no errors)
- [ ] Test on mobile device/responsive view
- [ ] Test with ACF Free and Pro
- [ ] Test with Elementor Free and Pro

### Error Testing
- [ ] Invalid location ID shows clear error
- [ ] Missing ACF field shows helpful error
- [ ] Empty data shows appropriate message
- [ ] Debug mode shows collapsible debug info
- [ ] Non-editors don't see debug info

## AI Assistant Behavior

### When Asked to "Add a Feature"
1. Determine if it fits plugin scope
2. Plan class/method structure
3. Write code following all standards above
4. Add error handling with actionable messages
5. Add logging if debug-worthy
6. Update CHANGELOG.md
7. Provide usage example
8. NO separate planning doc - explain inline

### When Asked to "Fix a Bug"
1. Ask for error details and context
2. Review relevant code section
3. Identify root cause
4. Implement fix with tests
5. Add entry to CHANGELOG.md (### Fixed)
6. Explain what was wrong and how it's fixed

### When Asked to "Update Documentation"
1. Determine which file (README, DEVELOP, or CHANGELOG)
2. Update inline - no new files
3. Keep language concise and technical
4. Remove fluff/marketing language
5. Ensure version numbers match across files

### When Asked to "Create Documentation"
1. **STOP** - Ask which existing file to update
2. Consolidate into README.md or DEVELOP.md
3. NO new .md files without explicit approval
4. Inline code documentation via PHPDoc only

---

**Last Updated:** October 28, 2025  
**Current Version:** 2.0.0  
**Primary Developer:** Ryan T. M. Reiffenberger  
**Company:** Falls Technology Group, LLC
