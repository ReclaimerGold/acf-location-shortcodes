# Copilot Instructions - ACF Location Shortcodes Plugin

## Project Context
You are assisting in the development of a WordPress plugin called **ACF Location Shortcodes**. This plugin provides shortcodes and Elementor integration for displaying and filtering location data from Advanced Custom Fields (ACF) custom post types.

## Project Objectives

### Primary Goals
1. **Community List Shortcode:** Create a shortcode that displays communities from the `surrounding_community_list` ACF field as a horizontal bullet list with house emoji (🏠) bullets
2. **Elementor Query Filters:** Enable filtering of Elementor Loop Grids and Loop Carousels by assigned service location

### Secondary Goals
- Maintain clean, documented, WordPress-standard code
- Ensure performance and scalability
- Provide flexibility through shortcode attributes
- Support both physical locations and service areas

## Technical Stack

### Required Technologies
- **WordPress:** 5.8+
- **PHP:** 7.4+
- **ACF (Advanced Custom Fields):** 5.0+ (Free or Pro)
- **Elementor:** 3.0+ (optional, for query filter features)

### Development Standards
- Follow WordPress Coding Standards (WPCS)
- Use PSR-4 autoloading for classes
- Implement proper sanitization and escaping
- Use WordPress i18n functions for all user-facing strings
- Write PHPDoc comments for all functions and classes

## ACF Schema Reference

### Custom Post Type: `location`
**Post Type Key:** `location`
**Label:** Service Locations

### Key ACF Fields
| Field Name | Field Key | Type | Usage |
|------------|-----------|------|-------|
| `surrounding_community_list` | `field_68ffbda5fdb3c` | Text | Comma-separated list of surrounding communities |
| `service_area_shorthand` | `field_68ffc4ed7c72e` | Text | Short name for service area |
| `address` | `field_68f6b00fd62cb` | Text | Physical address (empty for service areas) |
| `phone_number` | `field_68f6b01cd62cc` | Text | Location phone number |
| `servicing_physical_location` | `field_68ffbb50ffe66` | Post Object | Parent physical location for service areas |
| `full_service_area_name` | `field_68ffbc41d8abc` | Text | Full regional name |
| `located_near` | `field_68ffbf0992dc2` | Text | Nearby landmark |
| `metro_area_name` | `field_68ffc0f0d35d1` | Text | Metro area name |
| `county_name` | `field_68ffc21e953c0` | Text | County name |
| `team_members_assigned` | `field_68ffc32b9e238` | Relationship | Team members at location |

### Location Type Logic
- **Physical Location:** `address` field is NOT empty
- **Service Area:** `address` field IS empty, references a physical location via `servicing_physical_location`

## Plugin Architecture

### File Structure
```
acf-location-shortcodes/
├── acf-location-shortcodes.php          # Main plugin file
├── includes/
│   ├── class-shortcodes.php             # Shortcode handlers
│   ├── class-elementor-integration.php  # Elementor query filters
│   └── class-acf-helpers.php            # ACF data retrieval
├── assets/
│   ├── css/
│   │   └── shortcodes.css               # Frontend styles
│   └── js/
│       └── elementor-controls.js        # Elementor editor JS
├── copilot-instructions.md              # This file
├── PLAN.md                              # Development plan
└── README.md                            # Documentation
```

### Class Naming Conventions
- Main plugin class: `ACF_Location_Shortcodes`
- Shortcodes class: `ACF_Location_Shortcodes_Shortcodes`
- Elementor integration: `ACF_Location_Shortcodes_Elementor`
- ACF helpers: `ACF_Location_Shortcodes_ACF_Helpers`

## Coding Guidelines

### WordPress Best Practices
1. **Prefix Everything:** Use `acf_ls_` prefix for functions, `acf-ls-` for CSS classes
2. **Sanitization:** Always sanitize user input and ACF data
3. **Escaping:** Escape all output (`esc_html()`, `esc_attr()`, `esc_url()`)
4. **Nonces:** Use nonces for any form submissions (if applicable)
5. **Capabilities:** Check user capabilities before allowing actions
6. **Hooks:** Use appropriate WordPress hooks (actions/filters)

### Code Examples

#### Retrieving ACF Field Data
```php
// Always check if ACF exists
if ( ! function_exists( 'get_field' ) ) {
    return;
}

// Get field with fallback
$communities = get_field( 'surrounding_community_list', $post_id );
if ( empty( $communities ) ) {
    return '';
}
```

#### Shortcode Registration
```php
add_shortcode( 'location_communities', array( $this, 'render_communities_list' ) );
```

#### Shortcode Handler Pattern
```php
public function render_communities_list( $atts ) {
    // Parse attributes with defaults
    $atts = shortcode_atts( array(
        'location_id' => get_the_ID(),
        'limit'       => 0,
        'class'       => '',
    ), $atts, 'location_communities' );
    
    // Sanitize
    $location_id = absint( $atts['location_id'] );
    $limit       = absint( $atts['limit'] );
    $class       = sanitize_html_class( $atts['class'] );
    
    // Get data
    $communities = $this->get_communities( $location_id );
    
    // Build output
    ob_start();
    include plugin_dir_path( __FILE__ ) . '../templates/communities-list.php';
    return ob_get_clean();
}
```

#### Elementor Query Filter Pattern
```php
add_filter( 'elementor/query/query_args', array( $this, 'filter_by_location' ), 10, 2 );

public function filter_by_location( $query_args, $widget ) {
    $settings = $widget->get_settings();
    
    if ( ! empty( $settings['filter_by_location'] ) && $settings['filter_by_location'] === 'yes' ) {
        $location_id = ! empty( $settings['location_id'] ) ? $settings['location_id'] : 0;
        
        if ( $location_id ) {
            // Add meta query to filter by location
            $query_args['meta_query'][] = array(
                'key'     => 'assigned_location',
                'value'   => $location_id,
                'compare' => '=',
            );
        }
    }
    
    return $query_args;
}
```

### CSS Guidelines
- Use BEM-style naming: `.acf-ls-communities`, `.acf-ls-communities__item`
- Mobile-first responsive design
- Minimal specificity (avoid `!important`)
- Include vendor prefixes for better browser support

### Example CSS for Horizontal List
```css
.acf-ls-communities {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    list-style: none;
    padding: 0;
    margin: 0;
}

.acf-ls-communities__item {
    display: inline-flex;
    align-items: center;
    font-size: 1rem;
}

.acf-ls-communities__item::before {
    content: '🏠';
    margin-right: 0.5rem;
}
```

## Feature Implementation Details

### 1. Community List Shortcode

#### Shortcode Syntax
```
[location_communities]
[location_communities location_id="123"]
[location_communities limit="5" class="custom-class"]
```

#### Attributes
- `location_id` (int): Post ID of location (default: current post)
- `limit` (int): Max communities to display (default: 0 = all)
- `class` (string): Additional CSS class
- `separator` (string): Custom separator (advanced, default: internal list format)

#### Expected Output
```html
<ul class="acf-ls-communities custom-class">
    <li class="acf-ls-communities__item">🏠 Brooks Harbor</li>
    <li class="acf-ls-communities__item">🏠 Eagle Run</li>
    <li class="acf-ls-communities__item">🏠 Shadow Wood</li>
</ul>
```

#### Data Processing
1. Retrieve `surrounding_community_list` from ACF
2. Split by comma
3. Trim whitespace from each item
4. Remove empty items
5. Apply limit if specified
6. Build HTML output

### 2. Elementor Query Filter

#### Integration Points
- Hook into `elementor/query/query_args` filter
- Add controls via `elementor/element/before_section_end`
- Support Elementor Pro's Loop Grid and Loop Carousel

#### Controls to Add
1. **Toggle Control:** "Filter by Service Location"
2. **Select Control:** Location dropdown (populated dynamically)
3. **Multi-Select Control:** "Select Locations" (for OR filtering)

#### Query Modification Logic
```php
// If filtering by location is enabled
if ( $filter_enabled && ! empty( $location_ids ) ) {
    // For ACF relationship fields
    $query_args['meta_query'][] = array(
        'key'     => 'assigned_location', // Adjust field name
        'value'   => '"' . $location_id . '"',
        'compare' => 'LIKE',
    );
}
```

## Common Tasks for Copilot

### When Creating Functions
- Always check dependencies (ACF, Elementor)
- Add PHPDoc comments with `@param`, `@return`, `@since` tags
- Include error handling and early returns
- Use WordPress coding standards

### When Creating Shortcodes
- Use `shortcode_atts()` for attribute parsing
- Sanitize all input
- Escape all output
- Return string (never echo directly)
- Use output buffering for complex HTML

### When Adding Elementor Integration
- Check if Elementor is active
- Use Elementor's control system properly
- Test with both Free and Pro versions
- Add controls in appropriate sections

### When Writing CSS
- Enqueue only when needed
- Use plugin version for cache busting
- Include responsive breakpoints
- Test on multiple themes

## Testing Checklist

### Before Committing Code
- [ ] No PHP errors or warnings
- [ ] No JavaScript console errors
- [ ] Code follows WordPress standards
- [ ] All strings are translatable
- [ ] Functions are documented
- [ ] Edge cases handled (empty data, missing fields)
- [ ] Sanitized input, escaped output
- [ ] Tested with ACF Free and Pro
- [ ] Tested with Elementor Free and Pro
- [ ] Mobile responsive

## Common Pitfalls to Avoid

1. **Don't assume ACF is active:** Always check `function_exists('get_field')`
2. **Don't echo in shortcodes:** Always return output
3. **Don't forget i18n:** Wrap strings in `__()` or `_e()`
4. **Don't use generic names:** Always prefix functions/classes
5. **Don't hardcode paths:** Use `plugin_dir_path()` and `plugin_dir_url()`
6. **Don't forget mobile:** Test responsive layouts
7. **Don't skip sanitization:** Never trust user input or database content

## Debug Mode

### Enable Debug Output
```php
// In wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

// In plugin code
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'ACF Location Shortcodes: ' . print_r( $data, true ) );
}
```

## Version Control

### Commit Message Format
```
feat: Add community list shortcode
fix: Resolve Elementor query filter issue
docs: Update README with examples
style: Format code per WPCS
refactor: Optimize ACF data retrieval
test: Add unit tests for shortcode handler
```

## Resources

### Documentation Links
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [ACF Documentation](https://www.advancedcustomfields.com/resources/)
- [Elementor Developers](https://developers.elementor.com/)
- [Shortcode API](https://developer.wordpress.org/apis/shortcode/)

### ACF Functions to Use
- `get_field()` - Get field value
- `have_rows()` - Check repeater/group fields
- `get_field_object()` - Get field metadata
- `acf_add_options_page()` - Add settings page (if needed)

### WordPress Functions to Use
- `get_posts()` - Query posts
- `get_post_meta()` - Get post metadata
- `wp_enqueue_style()` - Enqueue CSS
- `wp_enqueue_script()` - Enqueue JavaScript
- `add_shortcode()` - Register shortcode
- `add_filter()` - Register filter
- `add_action()` - Register action

## AI Assistant Behavior Guidelines

### When I Ask You To...

#### "Create a shortcode"
1. Create the handler function
2. Register with `add_shortcode()`
3. Parse attributes with defaults
4. Sanitize all input
5. Get ACF data with fallbacks
6. Build HTML output
7. Return (not echo)
8. Add CSS if needed

#### "Add Elementor integration"
1. Check Elementor is active
2. Hook into query filters
3. Add controls to widgets
4. Modify query args
5. Test with Loop Grid/Carousel
6. Handle edge cases

#### "Fix a bug"
1. Ask for error details
2. Review relevant code
3. Identify root cause
4. Propose fix
5. Implement fix
6. Test thoroughly

#### "Optimize performance"
1. Identify bottlenecks
2. Add caching where appropriate
3. Minimize database queries
4. Use transients for expensive operations
5. Lazy load assets

## Current Development Phase
**Phase:** v1.1.0 Implementation In Progress
**Current Version:** 1.1.0
**Next Steps:** Complete debugging improvements and one-click ACF install implementation

### Active Planning Documents
- `DEBUGGING-IMPROVEMENT-PLAN.md` - Comprehensive debugging and UX improvements for v1.1.0
- `PLAN.md` - Overall project roadmap and technical architecture

### Version Management
**Current Plugin Version:** 1.1.0

When making changes:
- Update version in `acf-location-shortcodes.php` header comment
- Update `ACF_LS_VERSION` constant in main plugin file
- Update version in `copilot-instructions.md` (this file)
- Update CHANGELOG.md with changes
- Update "Last Updated" dates in documentation

**Version Scheme:**
- **Major (X.0.0)**: Breaking changes, major architecture changes
- **Minor (1.X.0)**: New features, enhancements, no breaking changes
- **Patch (1.1.X)**: Bug fixes, minor improvements only

## Questions to Ask Before Coding

1. Does this require ACF to function?
2. Does this require Elementor to function?
3. What happens if the data is empty?
4. What happens if the post doesn't exist?
5. Is this properly escaped for output?
6. Is this properly sanitized for input?
7. Is this translatable?
8. Is this performant with 100+ locations?
9. **NEW:** What error message will the user see if this fails?
10. **NEW:** Is debug information provided for troubleshooting?
11. **NEW:** Can the user fix this issue themselves based on the error?

---

## Debugging & Error Handling Guidelines (v1.1.0+)

### Error Message Standards

#### Always Include:
1. **What went wrong** - Clear description of the error
2. **Why it happened** - Context about the cause
3. **How to fix it** - Actionable next steps
4. **What was provided** - Show user input that caused the error

#### Error Message Template
```php
// Good Error Message
return $this->render_error(
    sprintf(
        __( 'Post ID %d is not a location post. Found post type: %s. Please use a valid location post ID.', 'acf-location-shortcodes' ),
        $location_id,
        get_post_type( $location_id )
    ),
    array(
        'location_id' => $location_id,
        'post_type' => get_post_type( $location_id ),
        'post_title' => get_the_title( $location_id ),
        'edit_link' => get_edit_post_link( $location_id ),
    )
);

// Bad Error Message (Don't do this)
return $this->render_error( __( 'Invalid location ID.', 'acf-location-shortcodes' ) );
```

### Debug Mode Implementation

#### When to Add Debug Output
- Field validation failures
- Empty or missing data
- Query modifications (Elementor)
- Cache operations
- ACF field existence checks
- Post type validation

#### Debug Output Pattern
```php
// Check if debug mode is enabled
if ( defined( 'ACF_LS_DEBUG' ) && ACF_LS_DEBUG ) {
    // Collect debug data
    $debug_data = array(
        'field_name' => $field_name,
        'post_id' => $post_id,
        'available_fields' => $this->get_field_names( $post_id ),
    );
    
    // Include in error
    return $this->render_error( $message, $debug_data );
}
```

### Logging Standards

#### When to Log
- Cache hits and misses
- Field validation results
- Query filter applications
- Plugin installation attempts
- Configuration validation

#### Logging Pattern
```php
// Use static log method
ACF_Location_Shortcodes::log(
    'Communities retrieved from cache',
    array(
        'post_id' => $post_id,
        'count' => count( $cached ),
    ),
    'info' // or 'warning', 'error'
);
```

#### Log Levels
- **info** - Normal operations, cache hits, successful validations
- **warning** - Potential issues, missing optional data, fallbacks used
- **error** - Failures, missing required data, invalid configurations

### Field Validation Pattern

#### Always Validate Fields Before Use
```php
// Check if field exists first
if ( ! $this->acf_helpers->field_exists( 'surrounding_community_list', $location_id ) ) {
    return $this->render_error(
        sprintf(
            __( 'ACF field "surrounding_community_list" not found on location "%s" (ID: %d).', 'acf-location-shortcodes' ),
            get_the_title( $location_id ),
            $location_id
        ),
        array(
            'location_id' => $location_id,
            'field_name' => 'surrounding_community_list',
            'available_fields' => $this->acf_helpers->get_field_names( $location_id ),
        )
    );
}

// Then get the value
$communities = get_field( 'surrounding_community_list', $location_id );
```

### Admin Notice Guidelines

#### Notice Types
1. **Error** (red) - Critical issues preventing functionality
2. **Warning** (yellow) - Configuration issues or missing optional features
3. **Success** (green) - Successful operations (ACF installed, setup complete)
4. **Info** (blue) - General information, tips, guidance

#### Notice Display Logic
```php
// Only show to users who can fix the issue
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

// Use transients to avoid repeated checks
$notice_dismissed = get_transient( 'acf_ls_notice_dismissed_field_check' );
if ( $notice_dismissed ) {
    return;
}

// Make dismissible for non-critical notices
// Store dismissal in user meta or transient
```

### One-Click Install Implementation

#### Security Requirements
```php
// Verify nonce
check_ajax_referer( 'acf_ls_install_acf', 'nonce' );

// Check capabilities
if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
    wp_send_json_error( __( 'Insufficient permissions', 'acf-location-shortcodes' ) );
}

// Sanitize input (if any)
$action = sanitize_key( $_POST['action'] );
```

#### Installation Flow
```php
// Include WordPress plugin installation libraries
if ( ! function_exists( 'plugins_api' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
}
if ( ! class_exists( 'WP_Upgrader' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
}

// Use Plugin_Upgrader to install
// Activate plugin
// Verify installation
// Provide feedback
```

### Error Recovery Suggestions

#### Suggest Similar Field Names
```php
// If field doesn't exist, suggest alternatives
$available_fields = $this->get_field_names( $post_id );
$suggestions = $this->find_similar_fields( $field_name, $available_fields );

if ( ! empty( $suggestions ) ) {
    $error .= ' ' . sprintf(
        __( 'Did you mean: %s?', 'acf-location-shortcodes' ),
        implode( ', ', $suggestions )
    );
}
```

#### Provide Edit Links
```php
// Include links to fix the issue
$debug_data['actions'] = array(
    'edit_post' => get_edit_post_link( $post_id ),
    'acf_fields' => admin_url( 'edit.php?post_type=acf-field-group' ),
    'create_location' => admin_url( 'post-new.php?post_type=location' ),
);
```

---

**Last Updated:** October 28, 2025
**Current Version:** 1.1.0 (in development)
**Primary Developer:** Ryan Reiffenberger
