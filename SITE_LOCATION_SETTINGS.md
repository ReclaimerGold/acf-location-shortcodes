# Site Location Settings - Feature Documentation

## Overview

Version 2.3.0 introduces site-specific location settings that allow each site in a multisite network to define its primary location information (city, state, state abbreviation). This is particularly useful for multi-location businesses where each site represents a different location.

## Features

### 1. Site Location Settings Page

Each site now has its own location settings page accessible via:
**Settings > Site Location**

Configure the following:
- **City**: e.g., "Los Angeles"
- **State**: e.g., "California"
- **State Abbreviation**: e.g., "CA" (automatically converted to uppercase)

### 2. Shortcodes

The following shortcodes are available to display site location information anywhere on your site:

#### `[site_location_city]`
Displays the city name.

**Example:**
```
[site_location_city]
```
**Output:** Los Angeles

---

#### `[site_location_state]`
Displays the full state name.

**Example:**
```
[site_location_state]
```
**Output:** California

---

#### `[site_location_state_abbrev]`
Displays the 2-letter state abbreviation.

**Example:**
```
[site_location_state_abbrev]
```
**Output:** CA

---

#### `[site_location_city_state]`
Displays the formatted city and state.

**Attributes:**
- `format` - Choose between 'abbrev' (default) or 'full'

**Examples:**
```
[site_location_city_state]
```
**Output:** Los Angeles, CA

```
[site_location_city_state format="full"]
```
**Output:** Los Angeles, California

### 3. Elementor Dynamic Tags

When using Elementor, site location settings are available as dynamic tags in the **Site Location** group:

#### Available Tags:
1. **Site Location: City** - Displays the city name
2. **Site Location: State** - Displays the full state name
3. **Site Location: State Abbreviation** - Displays the state abbreviation
4. **Site Location: City, State** - Displays formatted city and state with format options

#### How to Use in Elementor:

1. Add a text/heading widget to your page
2. Click the dynamic tag icon next to the text field
3. Navigate to **Site Location** group
4. Select the desired location component
5. For "City, State" tag, you can choose format (abbreviated or full)

### 4. Use Cases

#### Multi-Site Network with Different Locations
If you manage a network where each site represents a different physical location (e.g., franchise locations, office branches), you can:

1. Set location info for each site via **Settings > Site Location**
2. Use shortcodes or Elementor tags in your template
3. The content automatically displays the correct location for each site

#### Example Template Usage:

**Page Header:**
```html
<h1>Welcome to Our [site_location_city] Location</h1>
<p>Serving the [site_location_city], [site_location_state] area since 2020</p>
```

**Footer:**
```html
<address>
  Our Office<br>
  [site_location_city], [site_location_state_abbrev]
</address>
```

**Contact Form:**
```html
<p>Contact our [site_location_city] office for more information.</p>
```

### 5. Theme Integration

Site location settings can be accessed programmatically:

```php
// Get the site settings instance
$plugin = ACF_Location_Shortcodes::instance();
$settings = $plugin->site_settings;

// Get individual values
$city = $settings->get_setting( 'location_city' );
$state = $settings->get_setting( 'location_state' );
$state_abbrev = $settings->get_setting( 'location_state_abbrev' );

// Get formatted location
$location_abbrev = $settings->get_formatted_location( 'abbrev' ); // "City, ST"
$location_full = $settings->get_formatted_location( 'full' );     // "City, State"
```

## Media Sync Improvements (v2.3.0)

The media synchronization feature has been enhanced with the following improvements:

### 1. Smart Duplicate Detection
- Checks file size before re-copying identical files
- Avoids unnecessary file transfers when attachment hasn't changed
- Reduces server load and storage usage

### 2. Better Error Handling
- Validates source files exist before copying
- Creates target directories automatically if missing
- Cleans up failed copies to prevent orphaned files
- Comprehensive debug logging for troubleshooting

### 3. Improved Update Logic
- Updates existing attachments instead of creating duplicates
- Preserves file paths when updating
- Only regenerates metadata when file actually changes

### 4. Enhanced Reliability
- Verifies file existence at each step
- Handles permission errors gracefully
- Maintains sync metadata for tracking

## Testing the Features

### Test Site Location Settings:

1. Navigate to **Settings > Site Location**
2. Enter test values:
   - City: "Los Angeles"
   - State: "California"  
   - State Abbreviation: "CA"
3. Save settings

### Test Shortcodes:

Create a test page with the following content:
```
City: [site_location_city]
State: [site_location_state]
Abbreviation: [site_location_state_abbrev]
Full Format: [site_location_city_state format="full"]
Short Format: [site_location_city_state]
```

Expected output:
```
City: Los Angeles
State: California
Abbreviation: CA
Full Format: Los Angeles, California
Short Format: Los Angeles, CA
```

### Test Elementor Integration:

1. Edit a page with Elementor
2. Add a Heading widget
3. Click the dynamic tag icon
4. Navigate to **Site Location** group
5. Select **Site Location: City, State**
6. Verify the location displays correctly

### Test Media Sync (Multisite):

1. Navigate to Network Admin > ACF SMS
2. Enable multisite sync
3. Select target sites
4. Create a new location on the master site with an image
5. Verify the image syncs to slave sites
6. Update the image on master site
7. Verify the update syncs properly without creating duplicates

## Troubleshooting

### Shortcodes Display Empty
- Check that you've configured location settings in **Settings > Site Location**
- Verify shortcode syntax is correct

### Elementor Tags Not Appearing
- Ensure Elementor is active and up to date
- Clear Elementor cache: **Elementor > Tools > Regenerate CSS & Data**
- Check that ACF plugin is active

### Media Not Syncing
- Enable debug mode: `define( 'ACF_LS_DEBUG', true );` in wp-config.php
- Check error logs for sync issues
- Verify source files exist and are accessible
- Ensure target sites have proper file permissions

## Changelog

### Version 2.3.0
- Added site-level location settings (city, state, state abbreviation)
- Added 4 new shortcodes for site location data
- Added 4 new Elementor dynamic tags for site location
- Improved media sync duplicate detection
- Enhanced media sync error handling
- Better media sync update logic
- Added comprehensive debug logging for media sync

## Support

For issues or questions:
1. Check debug logs when `ACF_LS_DEBUG` is enabled
2. Review the troubleshooting section above
3. Submit an issue on the GitHub repository

---

**Plugin Version:** 2.3.0  
**Author:** Ryan T. M. Reiffenberger  
**Last Updated:** November 7, 2025
