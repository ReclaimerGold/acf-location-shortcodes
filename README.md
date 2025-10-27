# ACF Location Shortcodes

WordPress plugin providing shortcodes and Elementor integration for displaying and filtering location data from Advanced Custom Fields (ACF) custom post types.

## Features

### 🏠 Community List Shortcode
Display surrounding communities as a horizontal list with house emoji bullets.

```
[location_communities]
[location_communities location_id="123" limit="5"]
```

### 📍 Location List Shortcode
Display all service locations with map marker pins.

```
[location_list]
[location_list type="physical" orderby="title"]
```

### 📋 Location Info Shortcode
Display any ACF field from a location post.

```
[location_info field="service_area_shorthand"]
[location_info field="phone_number" default="Contact us"]
```

### 🎨 Elementor Query Filters
Filter Elementor Loop Grids and Loop Carousels by assigned service location.
- Multi-location selection
- AND/OR filter modes
- Custom relationship field support

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Advanced Custom Fields (ACF) 5.0+
- Elementor 3.0+ (optional, for query filters)

## Quick Start

1. **Install & Activate**
   - Upload to `/wp-content/plugins/acf-location-shortcodes/`
   - Activate in WordPress admin

2. **Install ACF** (if not already installed)
   - Plugins > Add New > Search "Advanced Custom Fields"

3. **Create a Location**
   - Service Area > Add New
   - Fill in "Surrounding Community List": `Brooks Harbor, Eagle Run, Shadow Wood`
   - Publish

4. **Use the Shortcode**
   - Add to any page: `[location_communities location_id="123"]`
   - View the result!

## Documentation

- **[USAGE.md](USAGE.md)** - Complete usage guide with examples
- **[PLAN.md](PLAN.md)** - Technical architecture
- **[CHANGELOG.md](CHANGELOG.md)** - Version history

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
├── acf-location-shortcodes.php          # Main plugin
├── includes/
│   ├── class-acf-helpers.php            # Data helpers
│   ├── class-shortcodes.php             # Shortcodes
│   └── class-elementor-integration.php  # Elementor
└── assets/
    ├── css/shortcodes.css               # Styles
    └── js/elementor-controls.js         # Editor JS
```

## ACF Schema

This plugin works with the `location` custom post type and expects:

**Required:**
- `surrounding_community_list` (Text) - Comma-separated communities

**Optional:**
- `service_area_shorthand` (Text)
- `address` (Text)
- `phone_number` (Text)
- Other location fields

See USAGE.md for complete field reference.

## Contributing

Development guidelines available in `copilot-instructions.md`.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

GPL v2 or later

## Credits

Developed by Ryan Reiffenberger  
**Version:** 1.0.0  
**Last Updated:** October 27, 2025
