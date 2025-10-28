# Changelog

All notable changes to the ACF Location Shortcodes plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-10-28 (In Development)

### Added
- **Debug Mode:** New `ACF_LS_DEBUG` constant for verbose error output
- **Enhanced Error Messages:** Contextual, actionable error messages with debug data
- **Field Validation:** New helper methods to check ACF field existence
- **Logging System:** Static `log()` method for debugging operations
- **One-Click ACF Install:** Admin notice with button to install ACF from WordPress.org
- **Admin Notices:** Configuration validation and setup guidance
- **Field Suggestions:** Suggest similar field names when fields don't exist
- **Elementor Debugging:** Query modification logging and validation

### Enhanced
- `render_error()` method now supports collapsible debug data
- All shortcode error messages now include context and fix suggestions
- ACF helper methods return structured data with success/error states
- Admin notices are dismissible and use transient storage

### Security
- One-click install includes nonce verification and capability checks
- All error output respects user capabilities
- Debug mode is opt-in and respects WP_DEBUG

### Documentation
- Added `DEBUGGING-IMPROVEMENT-PLAN.md` with complete technical specs
- Updated `copilot-instructions.md` with debugging guidelines
- Enhanced `PLAN.md` with v1.1.0 features
- Added troubleshooting section to documentation (planned)

# Changelog

All notable changes to the ACF Location Shortcodes plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-10-28

### Added
- **Debug Mode** - New `ACF_LS_DEBUG` constant for verbose error output
  - Collapsible debug data sections in error messages
  - Shows post IDs, field names, available fields, and edit links
  - Only visible to users with `edit_posts` capability
- **Enhanced Error Messages** - Contextual, actionable error messages
  - Specific errors for invalid location IDs vs wrong post types
  - Field name suggestions when fields don't exist
  - Edit links to fix issues directly
  - Shows what was provided vs what was expected
- **Field Validation** - New ACF helper methods for validation
  - `field_exists()` - Check if ACF field is registered
  - `get_field_names()` - List all available fields on a post
  - `get_location_field_validated()` - Structured error returns
- **Logging System** - Comprehensive logging for debugging
  - Static `ACF_Location_Shortcodes::log()` method
  - Log levels: info, warning, error
  - Logs cache operations, field validations, query modifications
  - Only logs when `ACF_LS_DEBUG` is enabled
- **Elementor Query Debugging** - Better visibility into query filtering
  - Logs when queries are modified by location filter
  - Shows widget name and selected locations
  - Field validation warnings in debug mode

### Changed
- **Error Rendering** - `render_error()` now accepts debug data parameter
  - Displays collapsible debug information when debug mode enabled
  - Shows available fields when field not found
  - Provides edit links and actionable solutions
- **Communities Shortcode** - Enhanced error messages
  - Distinguishes between missing ID, wrong post type, and empty field
  - Shows current post type when validation fails
  - Suggests available fields if field doesn't exist
- **Location Info Shortcode** - Better field validation
  - Lists available fields when requested field doesn't exist
  - Shows example usage in error messages
  - Validates field existence before attempting to get value
- **Location List Shortcode** - Improved error context
  - Explains parent/child relationship issues
  - Shows which physical location was found/not found
  - Clarifies service area vs physical location logic
- **ACF Helpers** - Added validation and logging throughout
  - Field existence checks before retrieval
  - Cache hit/miss logging
  - Empty value logging with context
  - Structured error returns with debug data

### Security
- Debug output only shown to authorized users
- Debug mode opt-in via constant
- All logging respects debug flag
- No sensitive data exposed in debug output

### Performance
- Debug data only collected when debug mode enabled
- Logging only when `ACF_LS_DEBUG` is true
- Field validation cached where appropriate
- Minimal overhead when debug mode disabled

## [1.0.0] - 2025-10-27

### Added
- Initial release of ACF Location Shortcodes plugin
- `[location_communities]` shortcode to display surrounding communities
  - Support for house emoji bullets (🏠)
  - Horizontal list layout by default
  - Attributes: `location_id`, `limit`, `class`, `show_emoji`
- `[location_list]` shortcode to display all service locations
  - Support for map marker pin emoji (📍)
  - Filter by location type (all/physical/service)
  - Automatic linking to location pages
  - Attributes: `type`, `orderby`, `order`, `limit`, `class`, `show_emoji`
- `[location_info]` shortcode to display any ACF field from location posts
  - Support for text fields, relationship fields, and post objects
  - Attributes: `location_id`, `field`, `default`
- Elementor integration for Loop Grid and Loop Carousel widgets
  - "Filter by Service Location" control
  - Multi-location selection support
  - AND/OR filter modes
  - Custom relationship field name configuration
- ACF helper functions for data retrieval
  - Community list parsing
  - Physical location vs service area detection
  - Location data caching for performance
- Responsive CSS for community lists
  - Mobile-first design
  - Multiple layout options (horizontal, vertical, grid)
  - Accessibility features
  - Print-friendly styles
- WordPress best practices implementation
  - Proper sanitization and escaping
  - i18n ready with text domain
  - ACF dependency checking
  - Admin notices for missing dependencies
- Comprehensive documentation
  - README.md with installation and basic usage
  - PLAN.md with development roadmap
  - USAGE.md with detailed examples
  - copilot-instructions.md for development guidance

### Security
- All user input properly sanitized
- All output properly escaped
- Capability checks for error messages
- Nonce verification ready for future forms

### Performance
- WordPress object caching implementation
- Efficient ACF field queries
- Lazy loading of Elementor integration
- Minimal CSS and JavaScript footprint

---

## [Unreleased]

### Planned for Future Releases

#### v1.1.0
- Additional shortcodes:
  - `[location_address]` - Formatted address display
  - `[location_phone]` - Click-to-call phone links
  - `[location_team_members]` - Display assigned team members
  - `[location_map]` - Embedded Google Maps
- Schema.org markup for local business data
- Enhanced styling options via shortcode attributes

#### v1.2.0
- Gutenberg blocks for location data
- REST API endpoints for location data
- Location search/filter functionality
- Admin dashboard widget

#### v2.0.0
- Import/export tools for locations
- Analytics integration
- Multi-location comparison features
- Advanced caching system

---

## Version History

### Version Numbering
- **Major version (X.0.0)**: Breaking changes, major new features
- **Minor version (0.X.0)**: New features, no breaking changes
- **Patch version (0.0.X)**: Bug fixes, minor improvements

---

**Last Updated:** October 27, 2025
