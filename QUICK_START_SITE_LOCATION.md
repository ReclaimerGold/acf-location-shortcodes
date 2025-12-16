# Quick Start: Site Location Settings

## Setup (5 minutes)

### Step 1: Configure Your Site Location
1. Go to **Settings > Site Location** in WordPress admin
2. Fill in your location information:
   - City: e.g., "Los Angeles"
   - State: e.g., "California"
   - State Abbreviation: e.g., "CA"
3. Click **Save Changes**

### Step 2: Use in Your Content

#### In Posts/Pages (Shortcodes)
Simply add shortcodes where you want location info to appear:

```
Welcome to our [site_location_city] office!

Contact us at:
[site_location_city], [site_location_state_abbrev] [site_location_state_abbrev]

Full address format:
[site_location_city_state format="full"]
```

#### In Elementor (Dynamic Tags)
1. Add a **Heading** or **Text Editor** widget
2. Click the **dynamic tag** icon (🔖) next to the text field
3. Navigate to **Site Location** group
4. Select your desired tag:
   - Site Location: City
   - Site Location: State
   - Site Location: State Abbreviation
   - Site Location: City, State

#### In Theme Templates (PHP)
```php
<?php
// Get the site settings
$plugin = ACF_Location_Shortcodes::instance();
$settings = $plugin->site_settings;

// Display individual fields
echo $settings->get_setting( 'location_city' );
echo $settings->get_setting( 'location_state' );
echo $settings->get_setting( 'location_state_abbrev' );

// Display formatted location
echo $settings->get_formatted_location( 'abbrev' ); // "City, ST"
echo $settings->get_formatted_location( 'full' );   // "City, State"
?>
```

## Common Use Cases

### 1. Page Header
```html
<h1>Welcome to Our [site_location_city] Location</h1>
<p class="subtitle">Proudly serving [site_location_city], [site_location_state]</p>
```

### 2. Footer Contact Info
```html
<footer>
  <h3>Contact Us</h3>
  <p>
    Our [site_location_city] Office<br>
    [site_location_city], [site_location_state_abbrev]
  </p>
</footer>
```

### 3. Hero Section (Elementor)
1. Add Heading widget
2. Use dynamic tag: **Site Location: City, State**
3. Style as needed
4. Works across all sites in your network!

### 4. Schema Markup
```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "[site_location_city]",
    "addressRegion": "[site_location_state_abbrev]"
  }
}
</script>
```

### 5. Multi-Site Network Template
Create one Elementor template and use site location tags throughout. Each site will automatically display its own location information!

## Multisite Benefits

If you manage multiple sites where each represents a different location:

1. **Set up once:** Configure location for each site
2. **Build once:** Create templates with site location tags
3. **Use everywhere:** Same template works across all sites
4. **Update easily:** Change location info without touching templates

Perfect for:
- Franchise locations
- Regional offices
- Multi-location service businesses
- Real estate agencies with multiple branches

## Tips

- State abbreviations are automatically converted to uppercase
- Empty values display nothing (no placeholder text)
- Settings are per-site in multisite networks
- Compatible with all major page builders
- Works in widgets, sidebars, and theme templates

## Need Help?

See full documentation in [SITE_LOCATION_SETTINGS.md](SITE_LOCATION_SETTINGS.md)
