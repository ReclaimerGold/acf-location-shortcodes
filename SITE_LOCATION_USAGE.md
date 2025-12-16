# Site Location Settings - Usage Guide

## Overview

The Site Location feature allows you to associate each site in your network with a Location post. Once associated, all ACF fields from that location are automatically available sitewide via shortcodes and Elementor dynamic tags.

This is perfect for multi-location businesses where each site represents a different location (e.g., franchise locations, regional offices, service areas).

## Quick Start

### Step 1: Add Required ACF Fields to Location Post Type

Add these fields to your Location post type field group in ACF:

| Field Name | Field Type | Description |
|------------|------------|-------------|
| `location_city` | Text | City name (e.g., "Sioux Falls") |
| `location_state` | Text | Full state name (e.g., "South Dakota") |
| `location_state_abbrev` | Text | 2-letter state code (e.g., "SD") |

**How to add:**
1. Go to **ACF > Field Groups**
2. Find your "Location Details" field group
3. Click "Add Field"
4. Add each of the three fields above
5. Save the field group

### Step 2: Populate Location Data

Edit each of your Location posts and fill in ALL relevant fields:

**Required for site location shortcodes:**
- `location_city`
- `location_state`
- `location_state_abbrev`

**Commonly used fields (already in default export):**
- `service_area_shorthand` (e.g., "Sioux Falls, SD")
- `full_service_area_name` (e.g., "Sioux Falls & The Sioux Empire")
- `metro_area_name` (e.g., "Sioux Falls Metro")
- `county_name` (e.g., "Minnehaha County")
- `address` (physical address for physical locations)
- `phone_number`
- `located_near` (nearby landmark)

### Step 3: Associate Site with Location

On each site in your network:

1. Go to **Settings > Site Location**
2. Select the Location post that represents this site from the dropdown
3. Click **Save Changes**
4. (Optional) Click "Edit Location Post" to update the location's ACF fields

### Step 4: Use Shortcodes

Now you can use location data anywhere on that site!

## Available Shortcodes

### Location Title

Display the title of the associated Location post (typically the city name):

```shortcode
[site_location_title]
<!-- Output: Sioux Falls -->
```

This is the simplest way to display the location name, as it uses the post title directly without needing a separate ACF field.

### Basic Location Fields

```shortcode
[site_location_city]
<!-- Output: Sioux Falls -->

[site_location_state]
<!-- Output: South Dakota -->

[site_location_state_abbrev]
<!-- Output: SD -->

[site_location_city_state]
<!-- Output: Sioux Falls, SD -->

[site_location_city_state format="full"]
<!-- Output: Sioux Falls, South Dakota -->
```

### Any ACF Field

Use `[location_info]` to display ANY ACF field from the associated location:

```shortcode
[location_info field="county_name"]
<!-- Output: Minnehaha County -->

[location_info field="metro_area_name"]
<!-- Output: Sioux Falls Metro -->

[location_info field="full_service_area_name"]
<!-- Output: Sioux Falls & The Sioux Empire -->

[location_info field="phone_number"]
<!-- Output: (605) 555-1234 -->

[location_info field="located_near"]
<!-- Output: Near Falls Park -->
```

### Service Areas

Display child service area locations:

```shortcode
[site_service_areas]
<!-- Output: Brandon, Harrisburg, and Tea -->

[site_service_areas format="comma"]
<!-- Output: Brandon, Harrisburg, Tea -->

[site_service_areas format="comma_and_surrounding"]
<!-- Output: Brandon, Harrisburg, and surrounding locations -->

[site_service_areas format="list"]
<!-- Output: 
<ul class="acf-sms-service-areas-list">
  <li>Brandon</li>
  <li>Harrisburg</li>
  <li>Tea</li>
</ul>
-->
```

## Elementor Integration

All location data is available as Elementor Dynamic Tags in the **"Site Location"** group:

1. Add any Elementor widget that accepts dynamic content
2. Click the **{@}** dynamic tags icon
3. Select **"Site Location"** group
4. Choose your field:
   - Site Location: Title
   - Site Location: City
   - Site Location: State
   - Site Location: State Abbreviation
   - Site Location: City, State
   - Site Location: Service Areas

## Common Use Cases

### Page Title

```html
<h1>Welcome to Our [site_location_title] Location</h1>
<!-- Output: Welcome to Our Sioux Falls Location -->
```

Or with ACF field:

```html
<h1>Welcome to Our [site_location_city] Location</h1>
<!-- Output: Welcome to Our Sioux Falls Location -->
```

### Service Area Description

```html
<p>Serving [location_info field="county_name"] and the [location_info field="metro_area_name"] area.</p>
<!-- Output: Serving Minnehaha County and the Sioux Falls Metro area. -->
```

### Footer Contact Info

```html
<div class="footer-contact">
  <p><strong>[site_location_city_state]</strong></p>
  <p>[location_info field="phone_number"]</p>
  <p>[location_info field="address"]</p>
</div>
```

### Service Areas List

```html
<h2>Communities We Serve</h2>
<p>Including [site_service_areas] and surrounding areas.</p>
<!-- Output: Including Brandon, Harrisburg, and Tea and surrounding areas. -->
```

## Troubleshooting

### Shortcode shows empty/nothing

**Problem:** The shortcode is displaying nothing.

**Solutions:**
1. Verify you've selected a Location in **Settings > Site Location**
2. Make sure the ACF field exists on your Location post type
3. Edit the Location post and populate the field with data
4. Check that ACF is activated

### Wrong location data showing

**Problem:** The shortcode shows data from the wrong location.

**Solution:** Go to **Settings > Site Location** and verify you've selected the correct Location post.

### "Edit Location Post" button doesn't work

**Problem:** Clicking the edit button doesn't open the location.

**Solution:** Make sure you have permission to edit Location posts. Network administrators and site administrators should have this capability.

### Service areas shortcode shows nothing

**Problem:** `[site_service_areas]` displays nothing.

**Solutions:**
1. Verify the selected Location has child service area posts
2. Make sure child locations have the `servicing_physical_location` field set to the parent location
3. Check that child locations are published (not draft)

## Advanced: Custom Fields

You can add ANY custom ACF field to your Location post type and access it via `[location_info field="YOUR_FIELD_NAME"]`:

**Example: Add a "Manager Name" field**

1. Go to **ACF > Field Groups** > "Location Details"
2. Add Field: `manager_name` (Text field)
3. Edit your Location posts and fill in the manager name
4. Use: `[location_info field="manager_name"]`

This makes the site location system extremely flexible for your specific business needs!

## Need Help?

- Check the main [README.md](README.md) for general plugin documentation
- See [SITE_LOCATION_SETTINGS.md](SITE_LOCATION_SETTINGS.md) for technical details
- Report issues on GitHub: https://github.com/ReclaimerGold/acf-location-shortcodes/issues
