# Changelog

All notable changes to the **ACF Service Management Suite** plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) and [Conventional Commits](https://www.conventionalcommits.org/).

---

## [Unreleased]

### Planned Features
- Additional Elementor editor warnings
- Advanced template sync features

---

## [2.2.0] - 2025-10-31

### Added
- **Admin Interface** - Comprehensive WordPress admin menu at **ACF SMS**
  - Dashboard tab with ACF status check, template download, and quick links
  - README tab displaying plugin documentation in admin
  - One-click download of ACF field group template (`acf-template.json`)
  - Automatic detection of ACF installation status
  - Template mismatch detection comparing installed fields to plugin template
- **Admin Notices** - Global dismissible notifications
  - Warning when ACF is not installed with one-click install link
  - Alert when ACF field groups don't match plugin template
  - Pre-search query link for ACF plugin installation
  - Per-user dismissal with user meta storage
- **Admin Assets** - Custom styling and JavaScript
  - Tabbed interface with WordPress-native design
  - Status indicators (success, warning, error)
  - Responsive card-based dashboard layout
  - Markdown-to-HTML converter for README display

### Changed
- **Plugin Initialization** - Admin interface always loads, frontend features require ACF
- **ACF Helpers** - Now instantiated even when ACF is not active (supports admin checks)

---

## [2.1.1] - 2025-10-31

### Fixed
- **GitHub Workflows** - Multiple CI/CD improvements
  - Added composer plugin allowance for PHPCS installer
  - Fixed version extraction regex to prevent capturing extra characters
  - Updated deprecated artifact actions from v3 to v4
  - Replaced YAML heredoc with printf statements for readme.txt generation

---

## [2.1.0] - 2025-10-30

### Changed
- **Elementor Location Filter** - Improved hierarchical location support
  - Filter dropdown now shows only physical locations (top-level or with address)
  - Service areas automatically use parent physical location for filtering
  - Updated default relationship field from `assigned_location` to `location` for team members
- **ACF Helpers** - Enhanced location detection logic
  - `is_physical_location()` now checks WordPress hierarchy (`post_parent`) first
  - Physical locations are top-level posts OR posts with an address field
  - `get_servicing_location()` now uses native `post_parent` instead of ACF field

### Fixed
- Team member filtering on service area pages now correctly uses parent location
- Physical location detection works even without ACF active (checks hierarchy)

---

## [2.0.0] - 2025-10-28

### Added
- **Complete Post Type Structure** - Plugin now includes pre-configured ACF field groups
  - Service Locations post type (`location`) with 11 custom fields
  - Team Members post type (`team-member`) with 4 custom fields
  - Team Member Types taxonomy for categorization
  - Ready-to-import ACF JSON export file (`acf-export-2025-10-28.json`)
- **`[location_address]` Shortcode** - Display physical address with automatic parent location lookup
- **Enhanced Documentation** - Consolidated all usage documentation into README.md
  - Complete shortcode reference with attribute tables
  - Real-world usage examples
  - ACF field schema documentation
  - Troubleshooting guide
  - Removed separate USAGE.md file

### Changed
- **Plugin Rebranded** - "ACF Location Shortcodes" → "ACF Service Management Suite"
  - Reflects expanded scope as complete service business solution
  - Better describes location + team member management capabilities
- **Text Domain** - Changed from `acf-location-shortcodes` to `acf-sms`
- **Plugin URI** - Updated to `https://github.com/ReclaimerGold/acf-service-management-suite`
- **Author Credits** - Falls Technology Group, LLC (Ryan T. M. Reiffenberger)
- **Documentation Cleanup** - Removed redundant markdown files
  - ❌ Deleted: `USAGE.md` (merged into README.md)
  - ❌ Deleted: `PLAN.md` (architecture in DEVELOP.md)
  - ❌ Deleted: `DEBUGGING-IMPROVEMENT-PLAN.md` (completed features)
  - ❌ Deleted: `PLANNING-SUMMARY.md` (no longer needed)
  - ✅ Retained: `README.md` (primary docs), `DEVELOP.md` (developer guide), `CHANGELOG.md` (this file)
- **Copilot Instructions** - Complete rewrite focusing on modern WordPress plugin development standards
  - No fluff documentation policy
  - Security-first coding patterns
  - Performance optimization guidelines
  - Clear version management rules
  - Conventional Commits enforcement

### Improved
- **README.md** - Now comprehensive primary documentation source
  - Installation methods (WordPress Admin, FTP, Git)
  - Quick start guide with ACF field import instructions
  - Complete shortcode reference (all 4 shortcodes documented)
  - Real-world usage examples
  - Complete ACF field schema with import guide
  - Troubleshooting section
- **License Information** - Full GPL v2 license text in plugin description
- **Support Links** - GitHub repository as official support channel

---

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
