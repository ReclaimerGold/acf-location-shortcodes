# ACF Location Shortcodes - Technical Documentation

## Overview
WordPress plugin providing shortcodes and Elementor integration for ACF location data.

## Architecture

### File Structure
```
acf-location-shortcodes/
├── acf-location-shortcodes.php          # Main plugin (222 lines)
├── includes/
│   ├── class-acf-helpers.php            # Data helpers (244 lines)
│   ├── class-shortcodes.php             # Shortcodes (202 lines)
│   └── class-elementor-integration.php  # Elementor (272 lines)
└── assets/
    ├── css/shortcodes.css               # Styles (163 lines)
    └── js/elementor-controls.js         # Editor JS (51 lines)
```

### Classes

**`ACF_Location_Shortcodes`** - Main plugin class
- Singleton pattern
- Dependency checking (ACF required)
- Component initialization
- Asset enqueuing

**`ACF_Location_Shortcodes_ACF_Helpers`** - Data retrieval
- `get_surrounding_communities($post_id)` - Parse CSV communities
- `parse_comma_list($string)` - Generic CSV parser
- `is_physical_location($post_id)` - Check location type
- `get_servicing_location($post_id)` - Get parent location
- `get_location_field($field, $post_id, $default)` - Generic getter
- Caching with 1-hour expiration

**`ACF_Location_Shortcodes_Shortcodes`** - Shortcode handlers
- `[location_communities]` - Community list
- `[location_info]` - Any ACF field display
- Error handling for missing data
- Security: sanitized input, escaped output

**`ACF_Location_Shortcodes_Elementor`** - Elementor integration
- Adds controls to Loop Grid/Carousel widgets
- Filters `elementor/query/query_args`
- Multi-location AND/OR filtering
- Custom relationship field support

## Requirements
```
acf-location-shortcodes/
├── acf-location-shortcodes.php          # Main plugin file
├── includes/
│   ├── class-shortcodes.php             # Shortcode handlers
│   ├── class-elementor-integration.php  # Elementor query filters
│   └── class-acf-helpers.php            # ACF data retrieval helpers
├── assets/
│   ├── css/
│   │   └── shortcodes.css               # Styling for shortcodes
│   └── js/
│       └── elementor-controls.js        # Elementor editor enhancements
├── copilot-instructions.md              # AI assistant instructions
├── PLAN.md                              # This file
└── README.md                            # Plugin documentation
```

## Implementation Phases

### Phase 1: Core Plugin Setup
**Goal:** Establish plugin foundation and ACF dependency checking

**Tasks:**
1. Create main plugin file with proper WordPress headers
2. Implement ACF dependency check
3. Set up plugin activation/deactivation hooks
4. Create autoloader for plugin classes
5. Establish proper enqueue system for assets

**Deliverables:**
- `acf-location-shortcodes.php` - Main plugin file
- Basic plugin structure with error handling

---

### Phase 2: Community List Shortcode
**Goal:** Implement horizontal community list with house emoji bullets

**Tasks:**
1. Create `class-shortcodes.php` with shortcode registration
2. Implement `[location_communities]` shortcode
3. Add support for attributes:
   - `location_id` - Specific location post ID (default: current post)
   - `separator` - Custom separator (default: bullet with house emoji)
   - `limit` - Maximum number of communities to display
   - `class` - Custom CSS class for the list container
4. Create responsive CSS for horizontal layout
5. Handle edge cases (empty data, invalid post ID)

**Shortcode Examples:**
```
[location_communities]
[location_communities location_id="123"]
[location_communities limit="5"]
[location_communities class="custom-communities"]
```

**Output Structure:**
```html
<ul class="location-communities-list">
  <li>🏠 Community Name 1</li>
  <li>🏠 Community Name 2</li>
  <li>🏠 Community Name 3</li>
</ul>
```

**Deliverables:**
- Shortcode handler class
- CSS for horizontal bullet list layout
- Unit tests for data parsing

---

### Phase 3: ACF Helper Functions
**Goal:** Create reusable functions for ACF data access

**Tasks:**
1. Create `class-acf-helpers.php`
2. Implement methods:
   - `get_surrounding_communities($post_id)` - Parse comma-separated list
   - `get_service_location($post_id)` - Get assigned service location
   - `get_physical_location($post_id)` - Determine if physical or service area
   - `get_servicing_location($post_id)` - Get parent physical location
3. Add caching layer for performance
4. Implement data sanitization and validation

**Deliverables:**
- ACF helper class with documented methods
- Caching implementation using WordPress Transients API

---

### Phase 4: Elementor Query Filter Integration
**Goal:** Enable filtering Elementor loops by service location

**Tasks:**
1. Create `class-elementor-integration.php`
2. Register custom Elementor query controls:
   - "Filter by Service Location" toggle
   - Service Location dropdown (populated from location post type)
   - Multiple location selection support
3. Hook into Elementor query builder:
   - `elementor/query/query_args` filter
   - Modify WP_Query to filter by location relationship
4. Support filtering for:
   - Posts assigned to specific location (via ACF relationship field)
   - Team members assigned to location
   - Any custom post type with location relationship field
5. Add dynamic tags support for current location context

**Elementor Integration Points:**
- Loop Grid widget
- Loop Carousel widget
- Posts widget
- Custom query loops

**Deliverables:**
- Elementor integration class
- Query filter implementation
- Control registration for Elementor editor
- JavaScript for dynamic control interactions

---

### Phase 5: Advanced Shortcodes (Future Enhancement)
**Goal:** Additional location-related shortcodes

**Potential Shortcodes:**
- `[location_info field="service_area_shorthand"]` - Display any ACF field
- `[location_address]` - Formatted address display
- `[location_phone]` - Click-to-call phone number
- `[location_team_members]` - Display assigned team members
- `[location_map]` - Embedded Google Maps

**Tasks:**
1. Implement flexible field display shortcode
2. Create specialized formatting shortcodes
3. Add schema.org markup for local business data

---

### Phase 6: Testing & Documentation
**Goal:** Ensure reliability and ease of use

**Tasks:**
1. Test with multiple WordPress themes
2. Test Elementor compatibility (Free & Pro)
3. Verify ACF compatibility (Free & Pro)
4. Performance testing with large datasets
5. Write comprehensive README.md
6. Create inline code documentation
7. Add admin notices for configuration guidance

**Deliverables:**
- Test results documentation
- Complete README with usage examples
- Inline PHPDoc comments
- Admin UI improvements

---

## Technical Specifications

- WordPress 5.8+
- PHP 7.4+
- ACF 5.0+ (Free or Pro)
- Elementor 3.0+ (optional)

## ACF Schema

### Post Type: `location`

### Key Fields
| Field Name | Field Key | Type | Usage |
|------------|-----------|------|-------|
| `surrounding_community_list` | `field_68ffbda5fdb3c` | Text | CSV communities |
| `service_area_shorthand` | `field_68ffc4ed7c72e` | Text | Short name |
| `address` | `field_68f6b00fd62cb` | Text | Physical address (empty = service area) |
| `servicing_physical_location` | `field_68ffbb50ffe66` | Post Object | Parent location |
| `full_service_area_name` | `field_68ffbc41d8abc` | Text | Full regional name |
| `metro_area_name` | `field_68ffc0f0d35d1` | Text | Metro area |
| `county_name` | `field_68ffc21e953c0` | Text | County |
| `phone_number` | `field_68f6b01cd62cc` | Text | Phone |
| `team_members_assigned` | `field_68ffc32b9e238` | Relationship | Team members |

## Standards
- WordPress Coding Standards (WPCS)
- PSR-4 autoloading
- Proper escaping/sanitization
- i18n ready (text domain: `acf-location-shortcodes`)
- PHPDoc comments

## Performance

- WordPress object caching (1-hour expiration)
- Lazy-load Elementor integration
- Minimal asset loading
- Efficient database queries

## Security

- Input sanitization (`absint()`, `sanitize_text_field()`)
- Output escaping (`esc_html()`, `esc_attr()`, `esc_url()`)
- Capability checks for error display
- ABSPATH check on all files
- Secure ACF field access

## Future Enhancements

### v1.1.0 - Debugging & User Experience (In Planning)
**Priority:** High  
**Status:** Planning Phase

#### Enhanced Debugging
- Debug mode with `ACF_LS_DEBUG` constant
- Contextual error messages with actionable solutions
- Collapsible debug data in error output
- Comprehensive logging system
- Field validation and existence checking

#### Error Handling Improvements
- Structured error returns from helper methods
- Field name suggestions when fields don't exist
- Post type validation with alternatives
- Detailed shortcode parameter validation
- Elementor query debugging and validation

#### One-Click ACF Installation
- Admin notice with install button when ACF is missing
- Automated plugin installation from WordPress.org
- Automatic activation after installation
- Success/failure feedback with fallback options
- Security: nonce verification and capability checks

#### Admin Improvements
- Configuration validation notices
- Missing field warnings with edit links
- Setup guidance for first-time users
- Dismissible notices with transient storage
- Success notifications for key actions

#### Documentation Updates
- Comprehensive troubleshooting guide in USAGE.md
- Debug mode usage instructions
- Common error solutions reference
- Installation problem resolution guide

**See:** `DEBUGGING-IMPROVEMENT-PLAN.md` for complete details

### v1.2.0 - Additional Features
- `[location_address]` - Formatted address shortcode
- `[location_phone]` - Click-to-call links
- `[location_map]` - Embedded maps
- Schema.org markup for local business
- AJAX-enhanced ACF installation
- Gutenberg blocks for location data
- REST API endpoints
- Admin dashboard widget
- Setup wizard

### v2.0.0 - Advanced Features
- Import/export tools for locations
- Analytics integration
- Multi-location comparison tools
- Advanced caching system
- WP-CLI commands
- Query Monitor integration

---

**Version:** 1.1.0  
**Status:** Complete (v1.0.0) | In Development (v1.1.0)  
**Last Updated:** October 28, 2025
