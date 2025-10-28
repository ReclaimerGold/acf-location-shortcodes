# ACF Location Shortcodes

A powerful WordPress plugin that provides shortcodes and Elementor integration for displaying and filtering location data from Advanced Custom Fields (ACF) custom post types. Perfect for multi-location businesses, service area directories, and location-based content management.

[![WordPress Version](https://img.shields.io/badge/WordPress-5.8%2B-blue)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)](https://php.net)
[![Version](https://img.shields.io/badge/Version-1.1.0-green)](CHANGELOG.md)
[![License](https://img.shields.io/badge/License-GPLv2-red)](LICENSE)

## Why Use This Plugin?

- **🚀 Easy to Use** - Simple shortcodes that work anywhere in WordPress
- **🎯 Flexible** - Display communities, locations, or any ACF field data
- **🎨 Elementor Ready** - Built-in query filters for Loop Grids and Carousels
- **🔍 Developer Friendly** - Debug mode, comprehensive logging, and extensible architecture
- **⚡ Performance Optimized** - Intelligent caching and minimal resource usage
- **🛡️ Secure** - Follows WordPress coding standards with proper sanitization and escaping

## Features

### 🏠 Community List Shortcode
Display surrounding communities as a responsive horizontal list with customizable emoji bullets.

```
[location_communities]
[location_communities location_id="123" limit="5"]
[location_communities class="my-style" show_emoji="no"]
```

**Perfect for:** Showing service areas, neighborhoods, or nearby communities

### 📍 Location List Shortcode
Display all service locations with flexible filtering and ordering options.

```
[location_list]
[location_list type="physical" orderby="title"]
[location_list location_specific="true"]
```

**Perfect for:** Location directories, office listings, service area pages

### 📋 Location Info Shortcode
Display any ACF field from a location post with fallback support.

```
[location_info field="service_area_shorthand"]
[location_info field="phone_number" default="Contact us"]
[location_info field="metro_area_name" location_id="123"]
```

**Perfect for:** Dynamic content, location details, custom field display

### 🎨 Elementor Query Filters
Advanced filtering for Elementor Loop Grids, Loop Carousels, and Posts widgets.

**Features:**
- Multi-location selection with AND/OR logic
- Custom relationship field support
- Dynamic location detection
- Visual controls in Elementor editor

**Perfect for:** Team member grids, service listings, location-based blog posts

## Requirements

### Minimum Requirements
- **WordPress:** 5.8 or higher
- **PHP:** 7.4 or higher
- **ACF (Advanced Custom Fields):** 5.0+ (Free or Pro)

### Optional Requirements
- **Elementor:** 3.0+ (for query filtering features)
- **Location Custom Post Type:** Your theme or another plugin must register a `location` post type

### Recommended Environment
- WordPress 6.4+
- PHP 8.1+
- ACF Pro (for enhanced field types)
- Elementor Pro (for Loop Grid/Carousel widgets)

## Installation

### Method 1: WordPress Admin (Recommended)

1. Download the latest release as a ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Method 2: Manual FTP Upload

1. Download and extract the plugin ZIP file
2. Upload the `acf-location-shortcodes` folder to `/wp-content/plugins/`
3. Go to **Plugins** in WordPress admin
4. Find and activate "ACF Location Shortcodes"

### Method 3: Git Clone (For Developers)

```bash
cd wp-content/plugins/
git clone https://github.com/rreiffenberger/acf-location-shortcodes.git
```

Then activate via WordPress admin or WP-CLI:
```bash
wp plugin activate acf-location-shortcodes
```

## Quick Start Guide

### Step 1: Install Dependencies

The plugin requires **Advanced Custom Fields** to function. If ACF is not installed:

1. Go to **Plugins > Add New**
2. Search for "Advanced Custom Fields"
3. Install and activate ACF Free (or ACF Pro if you have it)

### Step 2: Configure ACF Fields

Create a field group for your `location` post type with these fields:

**Required Field:**
- `surrounding_community_list` (Text) - Comma-separated list of communities

**Recommended Optional Fields:**
- `service_area_shorthand` (Text) - e.g., "Sioux Falls, SD"
- `address` (Text) - Physical address (empty = service area)
- `phone_number` (Text) - Contact number
- `servicing_physical_location` (Post Object) - Parent physical location

See [USAGE.md](USAGE.md) for complete field schema.

### Step 3: Create Your First Location

1. Go to your location post type (e.g., **Service Area > Add New**)
2. Enter a title: "Sioux Falls"
3. Fill in "Surrounding Community List": `Brooks Harbor, Eagle Run, Shadow Wood`
4. Add other location details (address, phone, etc.)
5. Publish

### Step 4: Use the Shortcode

Add this to any page or post:

```
[location_communities location_id="123"]
```

Replace `123` with your actual location post ID. Or use on the location post itself:

```
[location_communities]
```

**Expected Output:**
> 🏠 Brooks Harbor  🏠 Eagle Run  🏠 Shadow Wood

### Step 5: Enable Debug Mode (Optional)

For troubleshooting, add to your `wp-config.php`:

```php
define( 'ACF_LS_DEBUG', true );
```

This enables verbose error messages with helpful debugging information (only visible to users with edit permissions).

## Documentation

### User Documentation
- **[USAGE.md](USAGE.md)** - Complete usage guide with detailed examples
- **[CHANGELOG.md](CHANGELOG.md)** - Version history and release notes

### Developer Documentation
- **[DEVELOP.md](DEVELOP.md)** - Development guide and extension instructions
- **[PLAN.md](PLAN.md)** - Technical architecture and roadmap
- **[copilot-instructions.md](copilot-instructions.md)** - AI assistant development guidelines
- **[DEBUGGING-IMPROVEMENT-PLAN.md](DEBUGGING-IMPROVEMENT-PLAN.md)** - Debugging features specification

## Usage Examples

### Basic Community List
```
[location_communities]
```

### Specific Location
```
[location_communities location_id="123" limit="5"]
```

### Custom Styling
```
[location_communities class="my-communities"]
```

### Display Any Field
```
[location_info field="metro_area_name"]
```

### List All Locations
```
[location_list type="physical"]
```

### Elementor Integration

1. Add Loop Grid/Carousel widget
2. Go to Query section
3. Enable "Filter by Service Location"
4. Select location(s)
5. Set relationship field name

**See [USAGE.md](USAGE.md) for detailed examples and use cases.**

## Project Structure

```
acf-location-shortcodes/
├── acf-location-shortcodes.php          # Main plugin (bootstrap)
├── includes/                            # Core plugin classes
│   ├── class-acf-helpers.php            # ACF data retrieval
│   ├── class-shortcodes.php             # Shortcode handlers
│   └── class-elementor-integration.php  # Elementor integration
├── assets/                              # Frontend assets
│   ├── css/shortcodes.css               # Shortcode styles
│   └── js/elementor-controls.js         # Elementor editor JS
├── languages/                           # Translation files
├── DEVELOP.md                           # Development guide
├── USAGE.md                             # Complete usage documentation
├── CHANGELOG.md                         # Version history
├── README.md                            # This file
└── PLAN.md                              # Technical roadmap
```

**For detailed architecture, see [DEVELOP.md](DEVELOP.md)**

## ACF Field Schema

This plugin is designed to work with a `location` custom post type. Your theme or another plugin should register this post type.

### Required Fields

| Field Name | Field Type | Description | Example |
|------------|------------|-------------|---------|
| `surrounding_community_list` | Text | Comma-separated list of communities | `Brooks Harbor, Eagle Run, Shadow Wood` |

### Optional Fields

| Field Name | Field Type | Description | Example |
|------------|------------|-------------|---------|
| `service_area_shorthand` | Text | Short display name | `Sioux Falls, SD` |
| `address` | Text | Physical address (empty = service area) | `123 Main St, Sioux Falls, SD 57104` |
| `phone_number` | Text | Contact phone number | `(605) 555-1234` |
| `servicing_physical_location` | Post Object | Parent physical location | (relationship to another location post) |
| `full_service_area_name` | Text | Full regional name | `Greater Sioux Falls Metropolitan Area` |
| `metro_area_name` | Text | Metro area | `Sioux Falls Metro` |
| `county_name` | Text | County | `Minnehaha County` |
| `located_near` | Text | Nearby landmark | `Near Falls Park` |
| `team_members_assigned` | Relationship | Assigned team members | (relationship to team member posts) |

**Physical Location Logic:**
- Posts WITH an `address` field value = Physical Location
- Posts WITHOUT an `address` field = Service Area

See [USAGE.md](USAGE.md) for complete field reference and setup instructions.

## Troubleshooting

### Common Issues

**❓ Shortcode shows raw text instead of output**
- Ensure the plugin is activated
- Verify ACF is installed and active
- Check that you're using the correct shortcode syntax

**❓ "Invalid location ID" error**
- Verify the location post ID is correct
- Ensure the post type is `location`
- Check that the post is published

**❓ "No communities found" error**
- Add content to the `surrounding_community_list` field
- Ensure it's a comma-separated list
- Check field name matches exactly

**❓ Elementor filtering not working**
- Verify the relationship field name is correct
- Ensure posts have locations assigned
- Check that ACF field is a Relationship or Post Object type

**❓ Debug Information**

Enable debug mode in `wp-config.php`:
```php
define( 'ACF_LS_DEBUG', true );
```

This will show detailed error messages with:
- Post IDs and types
- Available fields
- Edit links to fix issues
- Suggested solutions

See [USAGE.md](USAGE.md) for more troubleshooting tips.

## Performance

### Caching
- WordPress object cache with 1-hour expiration
- Efficient ACF field queries
- Lazy loading of Elementor integration
- Minimal CSS/JS footprint

### Optimization Tips
- Use specific location IDs instead of querying all locations
- Limit the number of communities displayed
- Cache full pages with a caching plugin
- Use a persistent object cache (Redis/Memcached)

## Security

- All user input is sanitized
- All output is properly escaped
- Capability checks for debug output
- Follows WordPress coding standards
- Regular security audits

## Browser Support

- Chrome (last 2 versions)
- Firefox (last 2 versions)
- Safari (last 2 versions)
- Edge (last 2 versions)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Contributing

We welcome contributions! Here's how to get started:

1. **Report Bugs:** Open an issue with detailed reproduction steps
2. **Suggest Features:** Describe your use case and proposed solution
3. **Submit Pull Requests:** Follow the guidelines in [DEVELOP.md](DEVELOP.md)
4. **Improve Documentation:** Help make our docs clearer

### Development Setup

See [DEVELOP.md](DEVELOP.md) for:
- Local development environment setup
- Coding standards and guidelines
- Testing procedures
- Extension and customization guide

## Support

- **Documentation:** Start with [USAGE.md](USAGE.md)
- **Issues:** Check existing issues or open a new one
- **Community:** WordPress.org support forums (coming soon)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

GPL v2 or later

## Credits

Developed by Ryan Reiffenberger  
**Version:** 1.1.0  
**Last Updated:** October 28, 2025
