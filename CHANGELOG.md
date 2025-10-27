# Changelog

All notable changes to the ACF Location Shortcodes plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
