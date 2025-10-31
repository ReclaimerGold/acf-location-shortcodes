# ACF Service Management Suite

> A comprehensive WordPress plugin for service-based businesses managing multiple locations, team members, and service areas.

Built on Advanced Custom Fields (ACF), this plugin provides powerful shortcodes, Elementor integration, and a complete post type structure for location and team management.

[![WordPress Version](https://img.shields.io/badge/WordPress-5.8%2B-blue?logo=wordpress)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple?logo=php)](https://php.net)
[![Version](https://img.shields.io/badge/Version-2.1.0-green)](CHANGELOG.md)
[![License](https://img.shields.io/badge/License-GPLv2-red)](LICENSE)
[![Tests](https://github.com/ReclaimerGold/acf-location-shortcodes/actions/workflows/test.yml/badge.svg)](https://github.com/ReclaimerGold/acf-location-shortcodes/actions/workflows/test.yml)
[![GitHub Issues](https://img.shields.io/github/issues/ReclaimerGold/acf-location-shortcodes)](https://github.com/ReclaimerGold/acf-location-shortcodes/issues)

---

## Table of Contents

- [Why Use This Plugin?](#why-use-this-plugin)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start Guide](#quick-start-guide)
- [Shortcode Reference](#shortcode-reference)
- [Usage Examples](#usage-examples)
- [ACF Field Schema](#acf-field-schema)
- [Troubleshooting](#troubleshooting)
- [Performance](#performance)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)

---

## Why Use This Plugin?

- **🏢 Complete Service Business Solution** - Manage locations, service areas, and team members from one plugin
- **📍 Physical & Service Area Support** - Distinguish between physical offices and service-only areas
- **🚀 Easy to Use** - Simple shortcodes that work anywhere in WordPress
- **🎯 Flexible** - Display communities, locations, team members, or any ACF field data
- **🎨 Elementor Ready** - Built-in query filters for Loop Grids and Carousels
- **🔍 Developer Friendly** - Debug mode, comprehensive logging, and extensible architecture
- **⚡ Performance Optimized** - Intelligent caching and minimal resource usage
- **🛡️ Secure** - Follows WordPress coding standards with proper sanitization and escaping
- **📦 Complete Post Type Structure** - Includes ACF field configurations for immediate deployment

## Features

### � Complete Post Type Structure

Includes pre-configured ACF field groups for:

- **Service Locations** (`location` post type)
  - Physical locations with addresses
  - Service areas without physical offices
  - Parent/child location relationships
  - Community and service area management

- **Team Members** (`team-member` post type)
  - Team member profiles with photos
  - Location assignments
  - Profile management

See `acf-export-2025-10-28.json` for the complete field structure ready to import.

### �🏠 Community List Shortcode
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

**Two display modes:**
- **All Locations Mode:** Show all locations with type filtering (physical/service/all)
- **Location-Specific Mode:** Context-aware display showing only parent and related children

**Perfect for:** Location directories, office listings, service area pages

### 📋 Location Info Shortcode
Display any ACF field from a location post with fallback support.

```
[location_info field="service_area_shorthand"]
[location_info field="phone_number" default="Contact us"]
[location_info field="metro_area_name" location_id="123"]
```

**Perfect for:** Dynamic content, location details, custom field display

### 🏢 Location Address Shortcode
Get physical addresses for locations or their servicing parent location.

```
[location_address]
[location_address location_id="456"]
```

**Perfect for:** Maps integration, address display, automatic parent location lookup

### 🎨 Elementor Query Filters
Advanced filtering for Elementor Loop Grids, Loop Carousels, and Posts widgets.

**Features:**
- Multi-location selection with AND/OR logic
- Custom relationship field support
- Dynamic location detection
- Visual controls in Elementor editor
- Filter team members, posts, or any CPT by assigned location

**Perfect for:** Team member grids, service listings, location-based blog posts

## Requirements

### Minimum Requirements
- **WordPress:** 5.8 or higher
- **PHP:** 7.4 or higher
- **ACF (Advanced Custom Fields):** 5.0+ (Free or Pro)

### Optional Requirements
- **Elementor:** 3.0+ (for query filtering features)

### Included
- **Complete ACF Field Export** - Pre-configured field groups in `acf-export-2025-10-28.json`
- **Location Post Type** - Custom post type for service locations
- **Team Member Post Type** - Custom post type for team management
- **Team Member Type Taxonomy** - Taxonomy for categorizing team members

### Recommended Environment
- WordPress 6.4+
- PHP 8.1+
- ACF Pro (for enhanced field types)
- Elementor Pro (for Loop Grid/Carousel widgets)

## Installation

<details>
<summary><strong>📦 Method 1: WordPress Admin (Recommended)</strong></summary>

<br>

1. Download the latest release as a ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

</details>

<details>
<summary><strong>📤 Method 2: Manual FTP Upload</strong></summary>

<br>

1. Download and extract the plugin ZIP file
2. Upload the `acf-service-management-suite` folder to `/wp-content/plugins/`
3. Go to **Plugins** in WordPress admin
4. Find and activate "ACF Service Management Suite"

</details>

<details>
<summary><strong>💻 Method 3: Git Clone (For Developers)</strong></summary>

<br>

```bash
cd wp-content/plugins/
git clone https://github.com/ReclaimerGold/acf-service-management-suite.git
```

Then activate via WordPress admin or WP-CLI:
```bash
wp plugin activate acf-service-management-suite
```

</details>

---

## Quick Start Guide

### 🔌 Step 1: Install Dependencies

The plugin requires **Advanced Custom Fields** to function. If ACF is not installed:

1. Go to **Plugins > Add New**
2. Search for "Advanced Custom Fields"
3. Install and activate ACF Free (or ACF Pro if you have it)

### 📥 Step 2: Import ACF Field Groups

This plugin includes pre-configured field groups ready to import:

1. Go to **ACF > Tools**
2. Click on **Import Field Groups** tab
3. Choose File: Select `acf-export-2025-10-28.json` from the plugin directory
4. Click **Import JSON**

This will create:
- ✅ **Location Details** field group (11 fields for service locations)
- ✅ **Team Member Details** field group (4 fields for team members)
- ✅ **Service Locations** post type
- ✅ **Team Members** post type
- ✅ **Team Member Types** taxonomy

### 📍 Step 3: Create Your First Location

1. Go to **Service Locations > Add New**
2. **Title:** Enter city name (e.g., "Sioux Falls")
3. **Service Area Shorthand:** `Sioux Falls, SD`
4. **Surrounding Community List:** `Brooks Harbor, Eagle Run, Shadow Wood, Charleswood`
5. **Physical Address:** (Enter if physical location, leave empty if service area only)
6. Add other fields as needed
7. **Publish**

### 🚀 Step 4: Use Shortcodes

Add to any page or post:

```shortcode
[location_communities location_id="123"]
```

Or on the location post itself:

```shortcode
[location_communities]
[location_info field="service_area_shorthand"]
[location_list location_specific="true"]
```

**Expected Output:**
> 🏠 Brooks Harbor  🏠 Eagle Run  🏠 Shadow Wood  🏠 Charleswood

### 🐛 Step 5: Enable Debug Mode (Optional)

For troubleshooting, add to your `wp-config.php`:

```php
define( 'ACF_LS_DEBUG', true );
```

This enables verbose error messages with helpful debugging information (only visible to users with edit permissions).

---

## Shortcode Reference

### `[location_communities]`

Display surrounding communities as a horizontal list.

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `location_id` | int | Current post ID | Location post to display |
| `limit` | int | 0 (all) | Maximum communities to show |
| `class` | string | '' | Additional CSS class |
| `show_emoji` | string | 'yes' | Show house emoji ('yes'/'no') |

**Example:**
```shortcode
[location_communities location_id="123" limit="5" show_emoji="yes"]
```

---

### `[location_info]`

Display any ACF field from a location post.

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `location_id` | int | Current post ID | Location post to display |
| `field` | string | (required) | ACF field name |
| `default` | string | '' | Fallback if field is empty |

**Example:**
```shortcode
[location_info field="phone_number" default="Contact us"]
```

---

### `[location_list]`

Display a directory of locations with filtering.

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `location_specific` | bool | false | Enable context-aware mode (parent + children only) |
| `location_id` | int | Current post ID | Location post to use |
| `type` | string | 'all' | Filter: 'all', 'physical', 'service' |
| `orderby` | string | 'title' | Sort by: 'title', 'date', 'ID' |
| `order` | string | 'ASC' | Sort order: 'ASC' or 'DESC' |
| `limit` | int | 0 (all) | Maximum locations to show |
| `class` | string | '' | Additional CSS class |
| `show_emoji` | string | 'yes' | Show map pin emoji |

**Example:**
```shortcode
[location_list type="physical" orderby="title" order="ASC"]
```

---

### `[location_address]`

Get the physical address for a location (or its parent location).

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `location_id` | int | Current post ID | Location post to display |

**Returns format:** `Site Title, Address`

**Example:**
```shortcode
[location_address location_id="456"]
```

> 📖 **For more documentation:** See [DEVELOP.md](DEVELOP.md) for extension guide and [CHANGELOG.md](CHANGELOG.md) for version history

---

## Usage Examples

### Example 1: Location Landing Page

```html
<h1>[location_info field="full_service_area_name"]</h1>
<p>Serving [location_info field="county_name"] and the [location_info field="metro_area_name"]</p>

<h2>Communities We Serve</h2>
[location_communities limit="10"]

<h2>Our Service Locations</h2>
[location_list location_specific="true"]

<h3>Contact Us</h3>
<p>Phone: [location_info field="phone_number"]</p>
<p>Located near: [location_info field="located_near"]</p>
```

### Example 2: Location Directory Page

```
<h2>Our Physical Office Locations</h2>
[location_list type="physical" orderby="title"]

<h2>Service Areas</h2>
[location_list type="service" orderby="title"]
```

### Example 3: Team Members by Location (Elementor)

1. Add **Loop Grid** widget
2. **Query > Post Type:** `team-member`
3. **Query > Filter by Service Location:** Yes
4. **Query > Select Locations:** Choose location(s)
5. **Query > Relationship Field Name:** `assigned_location`

### Example 4: Google Maps Integration

In Elementor Google Map widget Address field:
```
[location_address]
```

This automatically shows the correct physical address, even for service areas (uses parent location).

### Example 5: Custom Styled Communities

```
[location_communities class="badge-style" show_emoji="no"]
```

Add CSS:
```css
.badge-style .acf-ls-communities__item {
    background: #0073aa;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
}
```

## Project Structure

```
acf-service-management-suite/
├── acf-service-management-suite.php     # Main plugin (bootstrap)
├── includes/                            # Core plugin classes
│   ├── class-acf-helpers.php            # ACF data retrieval
│   ├── class-shortcodes.php             # Shortcode handlers
│   └── class-elementor-integration.php  # Elementor integration
├── assets/                              # Frontend assets
│   ├── css/shortcodes.css               # Shortcode styles
│   └── js/elementor-controls.js         # Elementor editor JS
├── acf-export-2025-10-28.json           # ACF field structure (ready to import)
├── DEVELOP.md                           # Development guide
├── CHANGELOG.md                         # Version history
├── copilot-instructions.md              # AI development guidelines
└── README.md                            # This file
```

**For detailed architecture, see [DEVELOP.md](DEVELOP.md)**

## ACF Field Schema

This plugin includes a complete ACF export file (`acf-export-2025-10-28.json`) ready to import. It creates:

### Post Types

#### Service Locations (`location`)
- **Purpose:** Physical locations and service areas
- **Permalink:** `/locations/{post-name}`
- **Features:** Title, editor, author, revisions, thumbnail
- **Icon:** 📍 Dashicons Store

#### Team Members (`team-member`)
- **Purpose:** Staff and team member profiles
- **Permalink:** `/people/{post-name}`
- **Features:** Title, revisions
- **Icon:** 👥 Dashicons Admin Users

### Taxonomies

#### Team Member Types (`team-member-type`)
- **Purpose:** Categorize team members (e.g., Doctor, Nurse, Admin)
- **Hierarchical:** Yes
- **Assigned To:** `team-member` post type

### Location Field Group (11 Fields)

| Field Name | Field Type | Required | Description | Example |
|------------|------------|----------|-------------|---------|
| `service_area_shorthand` | Text | No | Short service area name | `Sioux Falls, SD` |
| `address` | Text | No | Physical address (determines location type) | `123 Main St, Sioux Falls, SD 57104` |
| `phone_number` | Text | No | Contact phone (shown if address exists) | `(605) 555-1234` |
| `location_site_url` | URL | No | Dedicated location website | `https://siouxfalls.example.com` |
| `servicing_physical_location` | Post Object | No | Parent physical location (shown if no address) | (relationship to location post) |
| `full_service_area_name` | Text | No | Full regional name | `Sioux Falls & The Sioux Empire` |
| `located_near` | Text | No | Nearby landmark | `Near Falls Park` |
| `metro_area_name` | Text | No | Metropolitan area | `Sioux Falls Metro` |
| `county_name` | Text | No | County name | `Minnehaha County` |
| `surrounding_community_list` | Text | No | Comma-separated communities | `Brooks Harbor, Eagle Run, Shadow Wood` |
| `team_members_assigned` | Relationship | No | Assigned team members | (relationship to team-member posts) |

### Team Member Field Group (4 Fields)

| Field Name | Field Type | Required | Description | Example |
|------------|------------|----------|-------------|---------|
| `profile_picture` | Image | No | Team member photo | (image file) |
| `title` | Text | No | Job title/position | `Lead Physician` |
| `location` | Select (Multiple) | No | Assigned locations | `Sioux Falls, SD` |
| `full_profile_url` | URL | No | Link to full bio page | `https://example.com/people/john-doe` |

**Physical Location Logic:**
- Location WITH `address` field value = **Physical Location**
- Location WITHOUT `address` field = **Service Area** (must set `servicing_physical_location`)

**Import Instructions:**
1. Go to **ACF > Tools**
2. Select **Import Field Groups** tab
3. Choose `acf-export-2025-10-28.json` from plugin directory
4. Click **Import JSON**

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

**See [DEVELOP.md](DEVELOP.md) for complete troubleshooting guide.**

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

- **Documentation:** This README + [DEVELOP.md](DEVELOP.md)
- **Issues:** https://github.com/ReclaimerGold/acf-service-management-suite/issues
- **Source Code:** https://github.com/ReclaimerGold/acf-service-management-suite

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for complete version history.

## License

This plugin is licensed under the GNU General Public License v2 or later.

```
ACF Service Management Suite - Complete service business location & team management
Copyright (C) 2025 Falls Technology Group, LLC

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
```

For the full license text, see [LICENSE](LICENSE) or visit:
https://www.gnu.org/licenses/gpl-2.0.html

## Credits

**Developed by:** Falls Technology Group, LLC  
**Author:** Ryan T. M. Reiffenberger  
**GitHub:** https://github.com/ReclaimerGold  
**Version:** 2.0.0  
**Last Updated:** October 28, 2025
