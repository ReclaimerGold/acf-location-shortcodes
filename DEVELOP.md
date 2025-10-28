# Development Guide

> Complete guide for extending, customizing, and contributing to **ACF Service Management Suite**

[![WordPress Coding Standards](https://img.shields.io/badge/WPCS-Compliant-green?logo=wordpress)](https://developer.wordpress.org/coding-standards/)
[![Conventional Commits](https://img.shields.io/badge/Conventional%20Commits-1.0.0-yellow?logo=git)](https://conventionalcommits.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple?logo=php)](https://php.net)

**Version:** 2.0.0  
**Last Updated:** October 28, 2025

---

## Table of Contents

- [Getting Started](#getting-started)
- [Development Environment](#development-environment)
- [Plugin Architecture](#plugin-architecture)
- [Coding Standards](#coding-standards)
- [Extending the Plugin](#extending-the-plugin)
- [Creating Custom Shortcodes](#creating-custom-shortcodes)
- [Adding Elementor Features](#adding-elementor-features)
- [Working with ACF Data](#working-with-acf-data)
- [Debugging](#debugging)
- [Testing](#testing)
- [Contributing](#contributing)
- [Release Process](#release-process)

---

## Getting Started

### Prerequisites

Before you begin developing, ensure you have:

- **WordPress Development Environment**
  - Local WordPress installation (Local by Flywheel, XAMPP, Docker, etc.)
  - WordPress 5.8+ (6.4+ recommended)
  - PHP 7.4+ (8.1+ recommended)
  - MySQL 5.7+ or MariaDB 10.3+

- **Required Plugins**
  - Advanced Custom Fields (Free or Pro)
  - Elementor (Free or Pro) for testing Elementor features

- **Development Tools**
  - Code editor (VS Code, PHPStorm, etc.)
  - Git
  - Composer (optional, for future dependency management)
  - WP-CLI (optional, for testing)

- **Recommended Tools**
  - Query Monitor plugin (for debugging)
  - Debug Bar plugin (for WordPress debugging)
  - PHP CodeSniffer with WordPress Coding Standards
  - Node.js and npm (for asset building, if needed)

### Clone the Repository

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/rreiffenberger/acf-location-shortcodes.git
cd acf-location-shortcodes
```

### Install Dependencies

Currently, this plugin has no npm or Composer dependencies. If you add any:

```bash
# For PHP dependencies (if added)
composer install

# For JavaScript dependencies (if added)
npm install
```

---

## Development Environment

### Recommended Local Setup

#### Option 1: Local by Flywheel (Easiest)

1. Download [Local by Flywheel](https://localwp.com/)
2. Create a new site with PHP 8.1+ and WordPress 6.4+
3. Clone plugin into `app/public/wp-content/plugins/`
4. Install ACF and Elementor via WordPress admin

#### Option 2: Docker (Most Flexible)

```bash
# Using official WordPress Docker image
docker-compose up -d
```

Create a `docker-compose.yml`:

```yaml
version: '3.8'
services:
  wordpress:
    image: wordpress:latest
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DB_NAME: wordpress
      WORDPRESS_DEBUG: 1
    volumes:
      - ./:/var/www/html/wp-content/plugins/acf-location-shortcodes
  
  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
      MYSQL_ROOT_PASSWORD: somewordpress
```

### Enable WordPress Debug Mode

Add to `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
define( 'SAVEQUERIES', true );

// Enable ACF Location Shortcodes debug mode
define( 'ACF_LS_DEBUG', true );
```

### Create Test Data

Use the included test data script (create one):

```bash
wp acf-ls create-test-data --locations=10
```

Or manually create:
- 2-3 physical locations (with address field)
- 5-10 service areas (without address field)
- Assign communities to each

---

## Plugin Architecture

### File Structure

```
acf-location-shortcodes/
├── acf-location-shortcodes.php          # Main plugin file (bootstrap)
├── includes/                            # Core plugin classes
│   ├── class-acf-helpers.php            # ACF data retrieval and caching
│   ├── class-shortcodes.php             # Shortcode handlers
│   └── class-elementor-integration.php  # Elementor query filters
├── assets/                              # Frontend assets
│   ├── css/
│   │   └── shortcodes.css               # Shortcode styles
│   └── js/
│       └── elementor-controls.js        # Elementor editor scripts
├── languages/                           # Translation files (future)
├── tests/                               # Unit tests (future)
├── docs/                                # Additional documentation (future)
├── DEVELOP.md                           # This file
├── USAGE.md                             # User documentation
├── PLAN.md                              # Technical roadmap
├── CHANGELOG.md                         # Version history
├── README.md                            # Project overview
└── copilot-instructions.md              # AI assistant guidelines
```

### Class Overview

#### `ACF_Location_Shortcodes` (Main Plugin Class)

**File:** `acf-location-shortcodes.php`

**Responsibilities:**
- Plugin initialization
- Dependency checking (ACF)
- Component loading
- Asset enqueuing
- Admin notices
- Logging system

**Key Methods:**
- `instance()` - Singleton instance
- `init()` - Initialize plugin components
- `check_dependencies()` - Verify ACF is active
- `log($message, $data, $level)` - Static logging method

**Hooks:**
- `plugins_loaded` - Check dependencies, load text domain
- `init` - Initialize plugin
- `admin_notices` - Display admin warnings

#### `ACF_Location_Shortcodes_ACF_Helpers` (Data Layer)

**File:** `includes/class-acf-helpers.php`

**Responsibilities:**
- ACF field retrieval
- Data parsing and validation
- Caching management
- Location type detection

**Key Methods:**
- `get_surrounding_communities($post_id)` - Parse community list
- `field_exists($field_name, $post_id)` - Validate field existence
- `get_field_names($post_id)` - List available fields
- `is_physical_location($post_id)` - Check location type
- `get_servicing_location($post_id)` - Get parent location
- `clear_cache($post_id)` - Clear cached data

**Caching:**
- Uses WordPress object cache
- 1-hour expiration (3600 seconds)
- Cache group: `acf_ls_locations`

#### `ACF_Location_Shortcodes_Shortcodes` (Presentation Layer)

**File:** `includes/class-shortcodes.php`

**Responsibilities:**
- Shortcode registration
- HTML output generation
- Error handling and display
- Input sanitization

**Registered Shortcodes:**
- `[location_communities]`
- `[location_info]`
- `[location_list]`
- `[location_address]`

**Key Methods:**
- `render_communities_list($atts)` - Community list shortcode
- `render_location_info($atts)` - Generic field display
- `render_location_list($atts)` - Location directory
- `render_error($message, $debug_data)` - Error display

#### `ACF_Location_Shortcodes_Elementor` (Elementor Integration)

**File:** `includes/class-elementor-integration.php`

**Responsibilities:**
- Elementor control registration
- Query filtering
- Editor script enqueuing

**Key Methods:**
- `add_location_controls($element, $args)` - Add controls to widgets
- `filter_query_by_location($query_args, $widget)` - Modify WP_Query
- `get_locations_for_control()` - Get location options

**Hooks:**
- `elementor/element/*/section_query/before_section_end` - Add controls
- `elementor/query/query_args` - Filter queries
- `elementor/editor/after_enqueue_scripts` - Enqueue editor JS

---

## Coding Standards

### WordPress Coding Standards

This plugin follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).

**Install PHP CodeSniffer:**

```bash
composer global require "squizlabs/php_codesniffer=*"
composer global require wp-coding-standards/wpcs
phpcs --config-set installed_paths /path/to/wpcs
```

**Check Code:**

```bash
phpcs --standard=WordPress includes/
```

**Auto-fix Issues:**

```bash
phpcbf --standard=WordPress includes/
```

### Code Style Guidelines

#### PHP

```php
<?php
/**
 * File-level docblock
 *
 * @package ACF_Location_Shortcodes
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class docblock
 *
 * @since 1.0.0
 */
class My_Class {

    /**
     * Method docblock
     *
     * @since 1.0.0
     * @param int    $post_id The post ID.
     * @param string $field   The field name.
     * @return mixed Field value or false.
     */
    public function get_field_value( $post_id, $field ) {
        // Validate input.
        $post_id = absint( $post_id );
        $field   = sanitize_key( $field );
        
        // Early return for invalid data.
        if ( ! $post_id || empty( $field ) ) {
            return false;
        }
        
        // Get and return value.
        $value = get_field( $field, $post_id );
        
        return $value;
    }
}
```

#### Naming Conventions

- **Classes:** `ACF_Location_Shortcodes_Class_Name`
- **Functions:** `acf_ls_function_name()`
- **Variables:** `$variable_name`
- **Constants:** `ACF_LS_CONSTANT_NAME`
- **CSS Classes:** `.acf-ls-class-name`
- **JS Variables:** `acfLsVariableName` (camelCase)

#### File Naming

- **Classes:** `class-class-name.php`
- **Functions:** `functions-purpose.php`
- **Templates:** `template-name.php`

### Security Best Practices

#### Always Sanitize Input

```php
// Sanitize integers
$location_id = absint( $_POST['location_id'] );

// Sanitize text
$field_name = sanitize_text_field( $_POST['field_name'] );

// Sanitize keys
$field_key = sanitize_key( $_POST['field_key'] );

// Sanitize HTML class
$class = sanitize_html_class( $_POST['class'] );

// Sanitize URLs
$url = esc_url_raw( $_POST['url'] );
```

#### Always Escape Output

```php
// Escape HTML
echo esc_html( $text );

// Escape attributes
echo '<div class="' . esc_attr( $class ) . '">';

// Escape URLs
echo '<a href="' . esc_url( $url ) . '">';

// Escape translation
echo esc_html__( 'Text', 'acf-location-shortcodes' );
```

#### Use Nonces for Forms

```php
// Create nonce
wp_nonce_field( 'acf_ls_action_name', 'acf_ls_nonce' );

// Verify nonce
if ( ! isset( $_POST['acf_ls_nonce'] ) || ! wp_verify_nonce( $_POST['acf_ls_nonce'], 'acf_ls_action_name' ) ) {
    wp_die( __( 'Security check failed', 'acf-location-shortcodes' ) );
}
```

#### Check Capabilities

```php
// Check if user can edit posts
if ( ! current_user_can( 'edit_posts' ) ) {
    return;
}

// Check if user can manage options
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have permission', 'acf-location-shortcodes' ) );
}
```

---

## Extending the Plugin

### Adding a New Shortcode

**Step 1: Register the Shortcode**

In `includes/class-shortcodes.php`, add to `register_shortcodes()`:

```php
public function register_shortcodes() {
    add_shortcode( 'location_communities', array( $this, 'render_communities_list' ) );
    add_shortcode( 'location_info', array( $this, 'render_location_info' ) );
    add_shortcode( 'location_list', array( $this, 'render_location_list' ) );
    add_shortcode( 'location_address', array( $this, 'render_location_address' ) );
    
    // Add your new shortcode
    add_shortcode( 'location_custom', array( $this, 'render_custom' ) );
}
```

**Step 2: Create the Handler Method**

```php
/**
 * Render custom shortcode.
 *
 * @since 1.1.0
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
public function render_custom( $atts ) {
    // Parse attributes with defaults
    $atts = shortcode_atts(
        array(
            'location_id' => get_the_ID(),
            'custom_attr' => '',
        ),
        $atts,
        'location_custom'
    );
    
    // Sanitize attributes
    $location_id = absint( $atts['location_id'] );
    $custom_attr = sanitize_text_field( $atts['custom_attr'] );
    
    // Validate location ID
    if ( ! $location_id || 'location' !== get_post_type( $location_id ) ) {
        return $this->render_error(
            sprintf(
                __( 'Invalid location ID: %d', 'acf-location-shortcodes' ),
                $location_id
            ),
            array(
                'location_id' => $location_id,
                'post_type' => get_post_type( $location_id ),
            )
        );
    }
    
    // Get data
    $data = $this->get_custom_data( $location_id, $custom_attr );
    
    // Handle empty data
    if ( empty( $data ) ) {
        return $this->render_error(
            __( 'No data found', 'acf-location-shortcodes' ),
            array(
                'location_id' => $location_id,
                'custom_attr' => $custom_attr,
            )
        );
    }
    
    // Build HTML output
    ob_start();
    ?>
    <div class="acf-ls-custom">
        <?php echo esc_html( $data ); ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Get custom data helper.
 *
 * @since 1.1.0
 * @param int    $location_id Location post ID.
 * @param string $custom_attr Custom attribute.
 * @return string Custom data.
 */
private function get_custom_data( $location_id, $custom_attr ) {
    // Use ACF helpers to get data
    return $this->acf_helpers->get_location_field( 'custom_field', $location_id );
}
```

**Step 3: Add CSS (if needed)**

In `assets/css/shortcodes.css`:

```css
.acf-ls-custom {
    padding: 1rem;
    background: #f5f5f5;
    border-radius: 4px;
}
```

**Step 4: Document the Shortcode**

Update `USAGE.md` with examples and documentation.

### Adding a New ACF Helper Method

In `includes/class-acf-helpers.php`:

```php
/**
 * Get custom location data.
 *
 * @since 1.1.0
 * @param int $post_id Location post ID.
 * @return array Custom data.
 */
public function get_custom_data( $post_id ) {
    if ( ! function_exists( 'get_field' ) ) {
        ACF_Location_Shortcodes::log(
            'ACF not available',
            array( 'post_id' => $post_id ),
            'error'
        );
        return array();
    }
    
    $post_id = absint( $post_id );
    if ( ! $post_id ) {
        return array();
    }
    
    // Check cache first
    $cache_key = 'custom_data_' . $post_id;
    $cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
    
    if ( false !== $cached ) {
        ACF_Location_Shortcodes::log(
            'Custom data retrieved from cache',
            array( 'post_id' => $post_id ),
            'info'
        );
        return $cached;
    }
    
    // Get field value
    $data = get_field( 'custom_field', $post_id );
    
    // Process data
    $processed = $this->process_custom_data( $data );
    
    // Cache the result
    wp_cache_set( $cache_key, $processed, self::CACHE_GROUP, self::CACHE_EXPIRATION );
    
    ACF_Location_Shortcodes::log(
        'Custom data fetched and cached',
        array( 'post_id' => $post_id, 'count' => count( $processed ) ),
        'info'
    );
    
    return $processed;
}
```

### Adding Elementor Controls

In `includes/class-elementor-integration.php`:

```php
public function add_location_controls( $element, $args ) {
    // ... existing controls ...
    
    // Add your custom control
    $element->add_control(
        'acf_ls_custom_control',
        array(
            'label'       => __( 'Custom Control', 'acf-location-shortcodes' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'description' => __( 'Custom control description', 'acf-location-shortcodes' ),
            'condition'   => array(
                'acf_ls_filter_by_location' => 'yes',
            ),
        )
    );
}
```

Then use it in the filter:

```php
public function filter_query_by_location( $query_args, $widget ) {
    $settings = $widget->get_settings();
    
    // Get your custom control value
    $custom_value = ! empty( $settings['acf_ls_custom_control'] ) 
        ? sanitize_text_field( $settings['acf_ls_custom_control'] ) 
        : '';
    
    // Use it in your query
    if ( ! empty( $custom_value ) ) {
        // Modify query args
    }
    
    return $query_args;
}
```

---

## Creating Custom Shortcodes

### Example: Team Members Shortcode

Create a new file or add to an existing class:

```php
/**
 * Display team members assigned to a location.
 *
 * Usage: [location_team_members location_id="123" limit="5"]
 *
 * @since 1.2.0
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
public function render_team_members( $atts ) {
    $atts = shortcode_atts(
        array(
            'location_id' => get_the_ID(),
            'limit'       => 0,
            'show_photo'  => 'yes',
            'show_title'  => 'yes',
            'class'       => '',
        ),
        $atts,
        'location_team_members'
    );
    
    // Sanitize
    $location_id = absint( $atts['location_id'] );
    $limit       = absint( $atts['limit'] );
    $show_photo  = filter_var( $atts['show_photo'], FILTER_VALIDATE_BOOLEAN );
    $show_title  = filter_var( $atts['show_title'], FILTER_VALIDATE_BOOLEAN );
    $class       = sanitize_html_class( $atts['class'] );
    
    // Validate
    if ( ! $location_id || 'location' !== get_post_type( $location_id ) ) {
        return $this->render_error(
            __( 'Invalid location ID', 'acf-location-shortcodes' )
        );
    }
    
    // Get team members
    $team_members = $this->acf_helpers->get_location_field( 'team_members_assigned', $location_id );
    
    if ( empty( $team_members ) ) {
        return $this->render_error(
            __( 'No team members assigned to this location', 'acf-location-shortcodes' )
        );
    }
    
    // Apply limit
    if ( $limit > 0 ) {
        $team_members = array_slice( $team_members, 0, $limit );
    }
    
    // Build CSS classes
    $css_classes = array( 'acf-ls-team-members' );
    if ( ! empty( $class ) ) {
        $css_classes[] = $class;
    }
    
    // Build output
    ob_start();
    ?>
    <div class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>">
        <?php foreach ( $team_members as $member ) : ?>
            <div class="acf-ls-team-member">
                <?php if ( $show_photo && has_post_thumbnail( $member->ID ) ) : ?>
                    <div class="acf-ls-team-member__photo">
                        <?php echo get_the_post_thumbnail( $member->ID, 'thumbnail' ); ?>
                    </div>
                <?php endif; ?>
                
                <div class="acf-ls-team-member__info">
                    <h3 class="acf-ls-team-member__name">
                        <?php echo esc_html( $member->post_title ); ?>
                    </h3>
                    
                    <?php if ( $show_title ) : ?>
                        <?php $job_title = get_field( 'job_title', $member->ID ); ?>
                        <?php if ( $job_title ) : ?>
                            <p class="acf-ls-team-member__title">
                                <?php echo esc_html( $job_title ); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
```

Register it:

```php
add_shortcode( 'location_team_members', array( $this, 'render_team_members' ) );
```

---

## Adding Elementor Features

### Custom Query Filter Example

```php
/**
 * Add custom meta query for team experience level.
 *
 * @param array                  $query_args The query args.
 * @param \Elementor\Widget_Base $widget     The widget instance.
 * @return array Modified query args.
 */
public function filter_by_experience_level( $query_args, $widget ) {
    $settings = $widget->get_settings();
    
    // Check if custom filter is enabled
    if ( empty( $settings['filter_by_experience'] ) ) {
        return $query_args;
    }
    
    $experience_level = sanitize_text_field( $settings['experience_level'] );
    
    if ( ! empty( $experience_level ) ) {
        if ( ! isset( $query_args['meta_query'] ) ) {
            $query_args['meta_query'] = array();
        }
        
        $query_args['meta_query'][] = array(
            'key'     => 'experience_level',
            'value'   => $experience_level,
            'compare' => '=',
        );
        
        // Log the modification
        ACF_Location_Shortcodes::log(
            'Elementor query filtered by experience level',
            array(
                'widget' => $widget->get_name(),
                'level'  => $experience_level,
            ),
            'info'
        );
    }
    
    return $query_args;
}
```

---

## Working with ACF Data

### Getting Field Values

```php
// Simple field value
$value = get_field( 'field_name', $post_id );

// With existence check
if ( $this->acf_helpers->field_exists( 'field_name', $post_id ) ) {
    $value = get_field( 'field_name', $post_id );
}

// With validation
$result = $this->acf_helpers->get_location_field_validated( 'field_name', $post_id );
if ( $result['success'] ) {
    $value = $result['value'];
} else {
    // Handle error: $result['error']
}
```

### Caching ACF Data

```php
// Check cache
$cache_key = 'my_data_' . $post_id;
$cached = wp_cache_get( $cache_key, 'acf_ls_locations' );

if ( false !== $cached ) {
    return $cached;
}

// Get fresh data
$data = get_field( 'field_name', $post_id );

// Process data
$processed = $this->process_data( $data );

// Cache it
wp_cache_set( $cache_key, $processed, 'acf_ls_locations', 3600 );

return $processed;
```

### Clearing Cache

```php
// Clear specific post cache
$this->acf_helpers->clear_cache( $post_id );

// Clear all location caches
wp_cache_delete( 'physical_locations', 'acf_ls_locations' );
```

---

## Debugging

### Enable Debug Mode

In `wp-config.php`:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'ACF_LS_DEBUG', true );
```

### Using the Logging System

```php
// Info level - normal operations
ACF_Location_Shortcodes::log(
    'Field retrieved successfully',
    array(
        'post_id' => $post_id,
        'field' => $field_name,
    ),
    'info'
);

// Warning level - potential issues
ACF_Location_Shortcodes::log(
    'Field is empty, using default',
    array(
        'post_id' => $post_id,
        'field' => $field_name,
        'default' => $default_value,
    ),
    'warning'
);

// Error level - failures
ACF_Location_Shortcodes::log(
    'Failed to retrieve field',
    array(
        'post_id' => $post_id,
        'field' => $field_name,
        'error' => $error_message,
    ),
    'error'
);
```

### Viewing Logs

Check the WordPress debug log:

```bash
tail -f wp-content/debug.log
```

Look for entries starting with `[ACF Location Shortcodes]`.

### Using Query Monitor

Install [Query Monitor](https://wordpress.org/plugins/query-monitor/) plugin to see:

- Database queries
- PHP errors
- Hook execution
- HTTP requests
- Environment info

---

## Testing

### Manual Testing Checklist

Before committing code, test:

- [ ] All shortcodes render correctly
- [ ] Error messages display properly
- [ ] Debug mode shows helpful information
- [ ] Caching works as expected
- [ ] Elementor filters function correctly
- [ ] No PHP errors or warnings
- [ ] No JavaScript console errors
- [ ] Responsive design works on mobile
- [ ] Works with ACF Free and Pro
- [ ] Works with Elementor Free and Pro

### Testing Environments

Test on:

- [ ] WordPress 5.8 (minimum version)
- [ ] WordPress latest (currently 6.7)
- [ ] PHP 7.4 (minimum version)
- [ ] PHP 8.1+ (recommended version)
- [ ] Different themes (Twenty Twenty-Four, Astra, etc.)
- [ ] Multisite installation

### Creating Test Data

Use WP-CLI to create test locations:

```bash
# Create physical location
wp post create --post_type=location --post_title="Sioux Falls" --post_status=publish --meta_input='{"address":"123 Main St"}'

# Create service area
wp post create --post_type=location --post_title="Brookings" --post_status=publish
```

### Unit Testing (Future)

When adding PHPUnit tests:

```php
<?php
/**
 * Test ACF Helpers
 *
 * @package ACF_Location_Shortcodes\Tests
 */

class Test_ACF_Helpers extends WP_UnitTestCase {
    
    public function test_parse_comma_list() {
        $helpers = new ACF_Location_Shortcodes_ACF_Helpers();
        
        $input = 'Brooks Harbor, Eagle Run, Shadow Wood';
        $expected = array( 'Brooks Harbor', 'Eagle Run', 'Shadow Wood' );
        
        $result = $helpers->parse_comma_list( $input );
        
        $this->assertEquals( $expected, $result );
    }
}
```

Run tests:

```bash
phpunit
```

---

## Contributing

### Code Contribution Workflow

1. **Fork the Repository**
   ```bash
   # Fork on GitHub, then clone your fork
   git clone https://github.com/your-username/acf-location-shortcodes.git
   cd acf-location-shortcodes
   ```

2. **Create a Feature Branch**
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/bug-description
   ```

3. **Make Your Changes**
   - Follow coding standards
   - Add comments and documentation
   - Test thoroughly
   - **Use workspace/ for drafts** (never create .old or .bak files)

4. **Commit Your Changes**
   
   **IMPORTANT: Use Conventional Commits Format**
   
   Format: `<type>[optional scope]: <description>`
   
   ```bash
   git add .
   git commit -m "feat(shortcodes): add location_distance shortcode"
   ```
   
   **Commit Types (determines versioning):**
   - `feat:` New feature → Minor version bump (1.0.0 → 1.1.0)
   - `fix:` Bug fix → Patch version bump (1.0.0 → 1.0.1)
   - `feat!:` Breaking feature → Major version bump (1.0.0 → 2.0.0)
   - `fix!:` Breaking fix → Major version bump
   - `docs:` Documentation only → No version bump
   - `style:` Code formatting → No version bump
   - `refactor:` Code refactoring → No version bump
   - `perf:` Performance improvement → No version bump
   - `test:` Adding tests → No version bump
   - `chore:` Maintenance → No version bump
   
   **Examples:**
   ```bash
   # Feature (bumps minor version)
   git commit -m "feat: add location_distance shortcode"
   git commit -m "feat(elementor): add distance filter control"
   
   # Bug fix (bumps patch version)
   git commit -m "fix: resolve cache invalidation on post update"
   git commit -m "fix(shortcodes): correct empty field handling"
   
   # Breaking change (bumps major version)
   git commit -m "feat!: change shortcode attribute names for consistency"
   git commit -m "refactor!: rename text domain from acf-location-shortcodes to acf-sms"
   
   # No version bump
   git commit -m "docs: update README with installation instructions"
   git commit -m "style: format code per WPCS"
   git commit -m "chore: update .gitignore"
   ```
   
   **Multi-line Commits:**
   ```bash
   git commit -m "feat(shortcodes): add location_distance shortcode

   - Calculate distance between two locations
   - Support miles and kilometers
   - Add caching for performance
   - Include comprehensive error handling"
   ```

5. **Push to Your Fork**
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Create Pull Request**
   - Go to GitHub and create a PR
   - Describe your changes clearly
   - Reference any related issues

### Pull Request Guidelines

Your PR should:

- [ ] Follow WordPress Coding Standards
- [ ] Include PHPDoc comments
- [ ] Have no PHP errors or warnings
- [ ] Be tested on multiple environments
- [ ] Update documentation if needed
- [ ] Add to CHANGELOG.md (under Unreleased)
- [ ] Use Conventional Commits format for all commits
- [ ] Have a clear, descriptive title (using conventional format)
- [ ] Include a detailed description of changes
- [ ] **Never include .old, .bak, or backup files**

**PR Title Examples:**
```
feat: add location_distance shortcode
fix: resolve cache invalidation bug
docs: update installation instructions
refactor!: change text domain to acf-sms
```

### Code Review Process

1. Automated checks run (coding standards, syntax)
2. Maintainer reviews code
3. Feedback provided or approval given
4. Changes requested if needed
5. Final approval and merge

---

## Release Process

### Version Numbering

Follow semantic versioning: `MAJOR.MINOR.PATCH`

- **MAJOR** (1.0.0 → 2.0.0): Breaking changes
- **MINOR** (1.0.0 → 1.1.0): New features, backwards compatible
- **PATCH** (1.0.0 → 1.0.1): Bug fixes only

### Pre-Release Checklist

- [ ] All tests pass
- [ ] No PHP errors or warnings
- [ ] All commits follow Conventional Commits format
- [ ] Update CHANGELOG.md with all changes (categorized by type)
- [ ] Update version in `acf-service-management-suite.php` header
- [ ] Update `ACF_LS_VERSION` constant
- [ ] Update version in README.md (badge + credits)
- [ ] Update version in copilot-instructions.md
- [ ] Update "Last Updated" dates in all docs
- [ ] Verify version numbers match across all 4 files
- [ ] Test on multiple environments
- [ ] No .old, .bak, or backup files in repository
- [ ] Clean workspace/ directory (or verify it's gitignored)

### Release Steps

1. **Determine Version Bump**
   
   Review commits since last release to determine version:
   ```bash
   git log v1.1.0..HEAD --oneline
   ```
   
   - Any `feat!:` or `BREAKING CHANGE:` → **MAJOR** version
   - Any `feat:` commits → **MINOR** version
   - Only `fix:` commits → **PATCH** version
   - Only `docs:`, `chore:`, `style:` → **No version bump** (bundle with next release)

2. **Update Version Numbers**
   ```bash
   # Update all version references (see Pre-Release Checklist)
   ```

3. **Update CHANGELOG.md**
   
   Organize changes by conventional commit type:
   ```markdown
   ## [2.1.0] - 2025-11-15
   
   ### Added (from feat: commits)
   - New location_distance shortcode
   
   ### Fixed (from fix: commits)
   - Cache clearing on post update
   ```

4. **Commit Version Bump**
   ```bash
   git add CHANGELOG.md acf-service-management-suite.php README.md copilot-instructions.md
   git commit -m "chore(release): bump version to 2.1.0"
   ```

5. **Create Git Tag**
   ```bash
   git tag -a v2.1.0 -m "chore(release): version 2.1.0"
   git push origin main --tags
   ```

5. **Create GitHub Release**
   - Go to GitHub Releases
   - Create new release from tag
   - Copy CHANGELOG content
   - Upload ZIP file (if applicable)

6. **WordPress.org Release** (if applicable)
   ```bash
   # Deploy to WordPress.org SVN
   svn co https://plugins.svn.wordpress.org/acf-location-shortcodes
   # Copy files, commit
   ```

---

## Advanced Topics

### Performance Optimization

#### Database Query Optimization

```php
// Bad - Multiple queries in loop
foreach ( $locations as $location ) {
    $address = get_field( 'address', $location->ID );
}

// Good - One query with array
$location_ids = wp_list_pluck( $locations, 'ID' );
$addresses = acf_get_field_values( 'address', $location_ids );
```

#### Object Caching

Use persistent object cache (Redis/Memcached) for production:

```php
// Check if object cache is persistent
if ( wp_using_ext_object_cache() ) {
    // Use longer cache times
    $expiration = DAY_IN_SECONDS;
} else {
    // Use shorter cache for transients
    $expiration = HOUR_IN_SECONDS;
}
```

### Internationalization (i18n)

Prepare plugin for translation:

```php
// Mark strings for translation
__( 'Text to translate', 'acf-location-shortcodes' );
_e( 'Text to echo', 'acf-location-shortcodes' );
_n( 'Singular', 'Plural', $count, 'acf-location-shortcodes' );
esc_html__( 'Text to escape and translate', 'acf-location-shortcodes' );

// With placeholders
sprintf(
    __( 'Found %d locations in %s', 'acf-location-shortcodes' ),
    $count,
    $city
);
```

Generate POT file:

```bash
wp i18n make-pot . languages/acf-location-shortcodes.pot
```

### Hooks and Filters

Create custom hooks for extensibility:

```php
// Action hook
do_action( 'acf_ls_before_render_communities', $location_id, $communities );

// Filter hook
$communities = apply_filters( 'acf_ls_communities_list', $communities, $location_id );
```

Allow others to extend:

```php
// Allow filtering the output
$output = apply_filters( 'acf_ls_communities_output', $output, $atts );

// Allow modifying query args
$args = apply_filters( 'acf_ls_locations_query_args', $args );
```

---

## Resources

### WordPress Development
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Developer Resources](https://developer.wordpress.org/)

### ACF Development
- [ACF Documentation](https://www.advancedcustomfields.com/resources/)
- [ACF Functions](https://www.advancedcustomfields.com/resources/get_field/)

### Elementor Development
- [Elementor Developers](https://developers.elementor.com/)
- [Elementor GitHub](https://github.com/elementor/elementor)

### Tools
- [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
- [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards)
- [Query Monitor](https://querymonitor.com/)
- [WP-CLI](https://wp-cli.org/)

---

## FAQ

**Q: Can I create custom shortcodes in my theme?**  
A: Yes! Use the plugin's helper classes:

```php
function my_custom_location_shortcode( $atts ) {
    $plugin = acf_location_shortcodes();
    $helpers = $plugin->acf_helpers;
    
    // Use plugin functionality
    $data = $helpers->get_location_field( 'field_name', $post_id );
    
    return '<div>' . esc_html( $data ) . '</div>';
}
add_shortcode( 'my_location_custom', 'my_custom_location_shortcode' );
```

**Q: How do I add my own ACF field to the schema?**  
A: Just create the field in ACF and use it with `[location_info field="your_field_name"]`

**Q: Can I modify the HTML output?**  
A: Yes, use WordPress filters:

```php
add_filter( 'acf_ls_communities_output', function( $output, $atts ) {
    // Modify $output
    return $output;
}, 10, 2 );
```

**Q: How do I contribute?**  
A: See the [Contributing](#contributing) section above!

---

## Support

For development questions:

1. Check this documentation
2. Review the code comments
3. Check existing GitHub issues
4. Open a new issue with detailed information

For general usage questions, see [USAGE.md](USAGE.md).

---

**Last Updated:** October 28, 2025  
**Version:** 1.1.0  
**Maintained by:** Ryan Reiffenberger

Happy coding! 🚀
