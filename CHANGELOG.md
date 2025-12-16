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

## [2.4.0] - 2025-12-16

### Added
- **Extended Multisite Sync** - Now syncs all service-related content types
  - **Services** (`service`) - Service offerings with full ACF field support
  - **Conditions** (`condition`) - Medical/service conditions
  - Previously synced: Locations and Team Members
  
- **Taxonomy Synchronization** - Full taxonomy support across network
  - **Service Categories** (`service-category`) - Hierarchical categories with parent-child sync
  - **Service Tags** (`service-tag`) - Non-hierarchical tags
  - **Team Member Types** (`team-member-type`) - Staff categorization
  - Automatic term creation on target sites when terms don't exist
  - Preserves hierarchical relationships for category structures
  
- **Relationship Field Remapping** - Intelligent cross-site ID mapping
  - `servicing_physical_location` - Links service areas to physical locations
  - `team_members_assigned` - Team member assignments on locations
  - `location` - Team member location assignments
  - `specialties` - Team member service specializations
  - Automatic fallback to slug-based matching when ID mapping unavailable
  - Supports both `relationship` and `post_object` ACF field types

- **Network Admin Enhancements**
  - Status tab now shows Services and Conditions counts per site
  - Sync Settings displays all synced content types with descriptions
  - Improved sync progress reporting with per-type breakdown
  
- **Comprehensive Diagnostics Tab** - New tab for troubleshooting sync issues
  - **Configuration Overview** - Shows sync status, master site, target sites, debug mode
  - **Visual Sync Relationship Map** - Graphical representation of master → target site flow
  - **Content Sync Status by Site** - Detailed counts for each post type per site
  - **Profile Picture Status** - Shows which team members have/don't have pictures, identifies issues
  - **Profile Picture Issue Detection** - Finds missing attachments and missing files
  - **Test Profile Picture Sync Tool** - Enter a post ID to see detailed sync status across all sites
  - **Debug Log Viewer** - View recent ACF SMS log entries directly in admin
  - Real-time JSON output for sync testing
  
- **JSON Diagnostic Export for AI Integration**
  - One-click export of comprehensive diagnostic data
  - Designed for copy-paste into AI assistants (Cursor, ChatGPT, Claude)
  - Includes:
    - Export metadata (timestamp, versions)
    - Full site configuration
    - All sites with post counts, sync status, upload paths
    - Complete team member list with profile picture details
    - Cross-site sync mapping for each team member
    - Automated issue detection with severity levels
    - Summary statistics
  - Copy to clipboard functionality
  - Instructions for AI-assisted debugging
  
- **Advanced Real-Time Progress Tracking** - Complete overhaul of sync UI
  - Individual progress bars for each post type (Locations, Services, Conditions, Team Members)
  - Relationship fields progress tracking
  - Taxonomy sync progress tracking  
  - Media files sync progress tracking
  - Master progress bar showing total quantified progress with percentage
  - Color-coded progress bars for each content type
  - Real-time sync log with timestamped entries
  - Batch processing (5 posts at a time) for better performance
  - Non-blocking UI with spinning animation during sync
  
### Improved
- **Media Synchronization** - Robust attachment syncing for image fields
  - Team member profile pictures (`profile_picture` field) synced to all network sites
  - Featured images synced for all post types
  - Error handling prevents data corruption on sync failures
  - Debug logging for media sync operations
  - File integrity checking to avoid unnecessary re-copying
  - Multiple fallback detection methods:
    - ACF field type detection
    - Known image field names (`profile_picture`, `servcat_featured_image`)
    - Value structure analysis (detects image arrays by keys)
  - Field update fallbacks:
    - Primary: `update_field()` by field name
    - Secondary: `update_field()` by field key
    - Tertiary: Direct `update_post_meta()` with field key reference

- **Sync Order Optimization** - Posts now sync in dependency order
  - Locations sync first (required by other relationships)
  - Services sync second (required by team member specialties)
  - Conditions sync third
  - Team Members sync last (depend on locations and services)
  
- **Taxonomy Sync** - Enhanced hierarchical term handling
  - Creates parent terms before child terms
  - Properly links terms to parents on target sites
  - Preserves term descriptions and slugs

### Fixed
- **Duplicate Post Linking** - Fixed issue where existing posts on target sites were skipped
  - Previously, sync would skip posts if a matching post (by title+slug) already existed
  - Now properly links existing posts to the master source, enabling ACF field sync
  - Resolves issue where team members created before sync was enabled wouldn't sync
  - Profile pictures and other ACF fields now sync to pre-existing posts
  
- **Attachment Cache Cross-Site Bug** - Fixed attachment ID caching issue
  - Cache key now includes target site ID to prevent cross-site ID confusion
  - Each target site gets unique attachment copies instead of shared IDs
  - Resolves "file missing" errors on sites 7, 9, 10 when site 4 synced first

- **Force Sync Feature** - Added ability to force-sync profile pictures
  - New "Force Sync Profile Picture" button in Diagnostics tab
  - Bypasses all caching for debugging purposes
  - Returns detailed step-by-step results for troubleshooting

### Technical
- Added `get_sync_post_types()` public method for extensibility
- Added `$relationship_fields` mapping array for relationship handling
- Added `$sync_taxonomies` configuration for post type taxonomy assignments
- New `remap_relationship_field()` method for cross-site ID mapping
- New `sync_hierarchical_terms()` method for proper taxonomy hierarchy sync
- New AJAX endpoint `acf_sms_get_sync_counts` for pre-sync item counting
- New AJAX endpoint `acf_sms_sync_batch` for batch processing with progress tracking
- Batch size of 5 posts per request for optimal performance

---

## [2.3.0] - 2025-11-07

### Added
- **Site Location Settings** - Per-site location configuration
  - New settings page: **Settings > Site Location**
  - Configure city, state, and state abbreviation for each site
  - Ideal for multisite networks where each site represents a different location
  - Settings accessible via admin options page
  
- **Site Location Shortcodes** - 4 new shortcodes for dynamic location data
  - `[site_location_city]` - Display site city name
  - `[site_location_state]` - Display full state name
  - `[site_location_state_abbrev]` - Display 2-letter state abbreviation
  - `[site_location_city_state]` - Display formatted city and state (supports 'abbrev' or 'full' format)
  - All shortcodes work in page content, widgets, and Elementor
  
- **Elementor Dynamic Tags** - Site location tags for Elementor editor
  - New "Site Location" dynamic tag group
  - 4 dynamic tags matching shortcode functionality
  - Tags work in any text field supporting dynamic content
  - Format options for city/state combination tag
  - Perfect for reusable Elementor templates across network sites

### Improved
- **Media Sync** - Enhanced attachment synchronization
  - Smart duplicate detection checks file size before re-copying
  - Avoids unnecessary file transfers when content unchanged
  - Better error handling with comprehensive validation
  - Automatic directory creation when missing
  - Cleanup of failed copies prevents orphaned files
  - Only regenerates metadata when file actually changes
  - Improved debug logging for troubleshooting

### Documentation
- Added comprehensive `SITE_LOCATION_SETTINGS.md` documentation
- Updated README with site location features
- Added usage examples for all new shortcodes and dynamic tags

---

## [2.2.0] - 2025-10-31

### Added
- **Admin Interface** - Comprehensive WordPress admin menu at **ACF SMS**
  - Dashboard tab with ACF status check, template management, and quick links
  - README tab displaying plugin documentation in admin
  - One-click download of ACF field group template (`acf-template.json`)
  - Automatic detection of ACF installation status
  - Template mismatch detection comparing installed fields to plugin template
- **Admin Notices** - Global dismissible notifications
  - Warning when ACF is not installed with one-click install link
  - **Activate ACF** button when ACF is installed but not activated
  - Alert when ACF field groups don't match plugin template
  - Pre-search query link for ACF plugin installation
  - Per-user dismissal with user meta storage
- **Admin Assets** - Custom styling and JavaScript
  - Tabbed interface with WordPress-native design
  - Status indicators (success, warning, error)
  - Responsive card-based dashboard layout
  - Markdown-to-HTML converter for README display
- **Auto-Install ACF Template** - One-click template installation
  - Auto-Install button in admin dashboard when ACF is active
  - Automatically detects if template is installed or outdated
  - Automatically imports template when ACF is first activated
  - Uses ACF's native import functions for reliability
- **Multisite Synchronization** - Automatic post sync across network sites
  - Syncs locations and team members when created, updated, deleted, trashed, or untrashed
  - Maintains source site and post ID references
  - Syncs ACF field data, taxonomies, and post status
  - Network-wide enable/disable setting
  - Per-site filtering via hooks
  - Displays sync status in dashboard for multisite installations
  - Prevents infinite sync loops with intelligent flagging
- **Network Admin Support** - Multisite-aware features
  - Admin notices display in network admin dashboard
  - Install/activate ACF links use network admin URLs when in network context
  - Sync status card visible only on multisite installations

### Changed
- **Plugin Initialization** - Admin interface always loads, frontend features require ACF
- **ACF Helpers** - Now instantiated even when ACF is not active (supports admin checks)
- **Template Management** - Renamed "Download" to emphasize auto-install option
- **Button Styling** - Added success state for auto-install completion

### Fixed
- **Multisite Compatibility** - ACF missing notice now shows in network admin
- **Template Detection** - More accurate checking of installed vs. required field groups

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

**Last Updated:** December 16, 2025
