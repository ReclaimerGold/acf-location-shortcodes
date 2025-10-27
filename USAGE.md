# ACF Location Shortcodes - Complete Usage Guide

A comprehensive guide for installing, configuring, and using the ACF Location Shortcodes plugin.

## Table of Contents
- [Quick Start](#quick-start)
- [Installation](#installation)
- [Configuration](#configuration)
- [Shortcode Reference](#shortcode-reference)
- [Elementor Integration](#elementor-integration)
- [Common Use Cases](#common-use-cases)
- [Styling Customization](#styling-customization)
- [Development](#development)
- [Troubleshooting](#troubleshooting)

---

## Quick Start

### Prerequisites
- WordPress 5.8+
- PHP 7.4+
- Advanced Custom Fields (ACF) 5.0+
- Elementor 3.0+ (optional, for query filters)

### 5-Minute Setup

1. **Upload & Activate**
   - Upload plugin to `/wp-content/plugins/acf-location-shortcodes/`
   - Activate via WordPress admin

2. **Install ACF** (if not already installed)
   - Go to Plugins > Add New
   - Search "Advanced Custom Fields"
   - Install and activate

3. **Create a Location**
   - Go to Service Area > Add New
   - Title: "Sioux Falls"
   - Surrounding Community List: `Brooks Harbor, Eagle Run, Shadow Wood`
   - Publish

4. **Test the Shortcode**
   - Add to any page: `[location_communities location_id="123"]`
   - Replace `123` with your location ID
   - Preview the page

**Done!** You should see: 🏠 Brooks Harbor 🏠 Eagle Run 🏠 Shadow Wood

---

## Installation

### Method 1: WordPress Admin Upload

1. Download plugin as ZIP
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Method 2: Manual FTP Upload

1. Upload `acf-location-shortcodes` folder to `/wp-content/plugins/`
2. Go to **Plugins** in WordPress admin
3. Find and activate "ACF Location Shortcodes"

### Method 3: Git Clone (Development)

```bash
cd wp-content/plugins/
git clone [repository-url] acf-location-shortcodes
```

Then activate via WordPress admin.

---

## Configuration

### Required ACF Fields

The plugin expects these fields on the `location` post type:

**Primary Field:**
- `surrounding_community_list` (Text) - Comma-separated communities

**Optional Fields:**
- `service_area_shorthand` (Text) - Short name (e.g., "Sioux Falls, SD")
- `full_service_area_name` (Text) - Full regional name
- `address` (Text) - Physical address (determines if physical location)
- `phone_number` (Text) - Contact number
- `metro_area_name` (Text) - Metro area
- `county_name` (Text) - County name
- `located_near` (Text) - Nearby landmark
- `servicing_physical_location` (Post Object) - Parent physical location
- `team_members_assigned` (Relationship) - Assigned team members

### Import ACF Field Group

Use the ACF export code you have to import fields:
1. Go to **ACF > Tools > Import Field Groups**
2. Paste your PHP export code
3. Import

---

## Shortcode Reference

### `[location_communities]`

Display surrounding communities as a horizontal list with house emoji bullets.

**Attributes:**
| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `location_id` | int | Current post ID | Location post to display |
| `limit` | int | 0 (all) | Maximum communities to show |
| `class` | string | '' | Additional CSS class |
| `show_emoji` | string | 'yes' | Show house emoji ('yes'/'no') |

**Examples:**

```
[location_communities]
```
Output: 🏠 Brooks Harbor  🏠 Eagle Run  🏠 Shadow Wood

```
[location_communities location_id="123" limit="5"]
```
Show 5 communities from location #123

```
[location_communities class="my-style" show_emoji="no"]
```
Custom styling, no emojis

---

### `[location_info]`

Display any ACF field from a location post.

**Attributes:**
| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `location_id` | int | Current post ID | Location post to display |
| `field` | string | (required) | ACF field name |
| `default` | string | '' | Fallback if field is empty |

**Examples:**

```
[location_info field="service_area_shorthand"]
```
Output: Sioux Falls, SD

```
[location_info field="phone_number" default="Contact us"]
```
Output: (605) 555-1234 or "Contact us" if empty

```
[location_info location_id="123" field="metro_area_name"]
```
Metro area from specific location

---

### `[location_list]`

Display physical locations and their child service areas with map marker pins (📍).

Each physical location is listed, followed by any service areas that have that physical location set in their `servicing_physical_location` field.

**Attributes:**
| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `orderby` | string | 'title' | Sort by: 'title', 'date', 'ID' |
| `order` | string | 'ASC' | Sort order: 'ASC' or 'DESC' |
| `limit` | int | 0 (all) | Maximum physical locations to show |
| `class` | string | '' | Additional CSS class |
| `show_emoji` | string | 'yes' | Show map pin emoji ('yes'/'no') |

**Examples:**

```
[location_list]
```
Output: 
- 📍 Sioux Falls (physical location)
- 📍 Brandon (service area under Sioux Falls)
- 📍 Harrisburg (service area under Sioux Falls)
- 📍 West Fargo (physical location)
- 📍 Fargo (service area under West Fargo)

```
[location_list orderby="title" order="DESC"]
```
Show physical locations in reverse alphabetical order with their service areas

```
[location_list limit="3"]
```
Show first 3 physical locations and all their service areas

```
[location_list class="location-directory" show_emoji="no"]
```
Custom styling without emojis

---

## Elementor Integration

### Setup Query Filters

Filter Elementor Loop Grids and Loop Carousels by service location.

**Steps:**
1. Add **Loop Grid** or **Loop Carousel** widget
2. Go to **Query** section
3. Enable "Filter by Service Location"
4. Select location(s) from dropdown
5. Set "Filter Mode" (AND/OR)
6. Set "Location Relationship Field Name" (default: `assigned_location`)

**Controls:**
- **Filter by Service Location** - Toggle on/off
- **Select Locations** - Multi-select dropdown
- **Filter Mode** - Match Any (OR) or Match All (AND)
- **Relationship Field Name** - ACF field connecting posts to locations

---

### Example Scenarios

#### 1. Team Members by Location

Display team members assigned to a specific location.

**Setup:**
- Widget: Loop Grid
- Post Type: `team-member`
- Filter by Service Location: Yes
- Relationship Field: `assigned_location`

#### 2. Services by Multiple Locations

Show services available at any selected location.

**Setup:**
- Widget: Loop Carousel
- Post Type: `service`
- Filter by Service Location: Yes
- Select multiple locations
- Filter Mode: Match Any (OR)

#### 3. Dynamic Location Page

Auto-filter content on single location template.

**Setup:**
- Add Loop Grid to single location template
- Filter by Service Location: Yes
- Leave location selection empty (uses current page)
- Result: Auto-displays content for current location

#### 4. Posts for All Selected Locations

Show posts assigned to ALL selected locations.

**Setup:**
- Widget: Loop Grid
- Filter by Service Location: Yes
- Select multiple locations
- Filter Mode: Match All (AND)

---

## Common Use Cases

### 1. Location Landing Page Template

Create a comprehensive location page:

```html
<h1>[location_info field="full_service_area_name"]</h1>
<p class="subtitle">Serving [location_info field="county_name"] and the [location_info field="metro_area_name"]</p>

<h2>Communities We Serve</h2>
[location_communities limit="10"]

<h3>Our Team in [location_info field="service_area_shorthand"]</h3>
<!-- Add Elementor Loop Grid filtered by current location -->

<h3>Contact Us</h3>
<p>Phone: [location_info field="phone_number"]</p>
<p>Located near: [location_info field="located_near"]</p>
```

### 2. Service Area Overview

Display all service areas with their communities:

```php
<!-- Display all locations -->
<h2>Our Service Locations</h2>
[location_list type="physical"]

<!-- In a WordPress loop of location posts -->
<?php while ( have_posts() ) : the_post(); ?>
<div class="location-card">
    <h3><?php the_title(); ?></h3>
    <p><strong>Serving:</strong></p>
    <?php echo do_shortcode('[location_communities]'); ?>
    <p><strong>Metro:</strong> <?php echo do_shortcode('[location_info field="metro_area_name"]'); ?></p>
</div>
<?php endwhile; ?>
```

### 3. Location Directory

Create a complete hierarchical location directory:

```html
<h2>All Service Locations</h2>
<p>Physical locations are listed with their service areas below them.</p>
[location_list orderby="title"]
```

This will display all physical locations, each followed by their child service areas.

### 4. Location-Specific Testimonials

**Setup:**
1. Add ACF relationship field to testimonial CPT: `testimonial_location`
2. Use Elementor Loop Grid:
   - Post Type: `testimonial`
   - Filter by Service Location: Yes
   - Relationship Field: `testimonial_location`

### 5. Location-Filtered Blog Posts

**Setup:**
1. Add ACF relationship field to posts: `related_locations`
2. Use Elementor Loop Carousel:
   - Post Type: `post`
   - Filter by Service Location: Yes
   - Relationship Field: `related_locations`

---

## Styling Customization

### Default CSS Classes

| Class | Purpose |
|-------|---------|
| `.acf-ls-communities` | Communities container |
| `.acf-ls-communities__item` | Individual community |
| `.acf-ls-communities__emoji` | Community emoji (🏠) |
| `.acf-ls-communities__text` | Community text |
| `.acf-ls-locations` | Locations container |
| `.acf-ls-locations__item` | Individual location |
| `.acf-ls-locations__emoji` | Location emoji (📍) |
| `.acf-ls-locations__text` | Location text |

### Layout Modifiers

Add these classes via the `class` attribute:

**Vertical Layout:**
```
[location_communities class="acf-ls-communities--vertical"]
[location_list class="acf-ls-locations--vertical"]
```

**Grid Layout:**
```
[location_communities class="acf-ls-communities--grid"]
[location_list class="acf-ls-locations--grid"]
```

**Compact Spacing:**
```
[location_communities class="acf-ls-communities--compact"]
[location_list class="acf-ls-locations--compact"]
```

### Custom Styling Examples

**Styled Badges:**
```css
.community-badges .acf-ls-communities__item {
    background: #0073aa;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
}

.location-badges .acf-ls-locations__item {
    background: #0073aa;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
}

.location-badges .acf-ls-locations__link {
    color: white;
    text-decoration: none;
}

.location-badges .acf-ls-locations__link:hover {
    color: #f0f0f0;
}
```

Use: 
```
[location_communities class="community-badges"]
[location_list class="location-badges"]
```

**Grid with 3 Columns:**
```css
.communities-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

@media (max-width: 768px) {
    .communities-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
```

Use: `[location_communities class="communities-grid"]`

**With Separators:**
```css
.with-separators .acf-ls-communities__item:not(:last-child)::after {
    content: '•';
    margin-left: 1.5rem;
    color: #999;
}
```

Use: `[location_communities class="with-separators"]`

---

## Development

### Plugin Architecture

**File Structure:**
```
acf-location-shortcodes/
├── acf-location-shortcodes.php          # Main plugin file
├── includes/
│   ├── class-acf-helpers.php            # ACF data helpers
│   ├── class-shortcodes.php             # Shortcode handlers
│   └── class-elementor-integration.php  # Elementor filters
└── assets/
    ├── css/shortcodes.css               # Frontend styles
    └── js/elementor-controls.js         # Editor JS
```

### Key Functions

**ACF Helpers:**
```php
// Get communities
$communities = $acf_helpers->get_surrounding_communities( $post_id );

// Parse comma list
$items = $acf_helpers->parse_comma_list( $string );

// Check location type
$is_physical = $acf_helpers->is_physical_location( $post_id );

// Get any field
$value = $acf_helpers->get_location_field( $field_name, $post_id, $default );
```

### Extending the Plugin

**Add Custom Shortcode:**
```php
add_action( 'init', function() {
    add_shortcode( 'my_location_shortcode', 'my_callback' );
});

function my_callback( $atts ) {
    $atts = shortcode_atts( array(
        'location_id' => get_the_ID(),
    ), $atts );
    
    // Your code here
    return 'Output';
}
```

**Filter Elementor Query:**
```php
add_filter( 'elementor/query/query_args', function( $query_args, $widget ) {
    // Modify $query_args
    return $query_args;
}, 10, 2 );
```

### Performance Notes

- Communities are cached using WordPress object cache
- Cache expires after 1 hour
- Clear cache: `$acf_helpers->clear_cache( $post_id )`
- Elementor integration lazy-loads only when needed

---

## Troubleshooting

### Communities Not Displaying

**Issue:** Shortcode shows "No communities found"

**Solutions:**
- Verify `surrounding_community_list` field has data
- Check you're using correct location ID
- Ensure ACF plugin is active
- Verify field exists in ACF

### Elementor Filter Not Working

**Issue:** Posts not filtering by location

**Solutions:**
- Verify relationship field name matches your ACF field
- Check posts have location relationship set
- Ensure field is Relationship or Post Object type
- Try changing filter mode (AND/OR)
- Check that location posts are published

### Shortcode Shows Raw Text

**Issue:** You see `[location_communities]` on page

**Solutions:**
- Ensure plugin is activated
- Check ACF is installed and active
- Verify you're on a location post or specify `location_id`

### Styling Not Applied

**Issue:** Custom CSS not showing

**Solutions:**
- Clear browser cache
- Check CSS selector specificity
- Ensure custom CSS loads after plugin CSS
- Verify classes are applied (browser inspector)

### Empty Location Dropdown

**Issue:** No locations in Elementor dropdown

**Solutions:**
- Create at least one location post
- Publish location posts (drafts don't appear)
- Clear Elementor cache: Elementor > Tools > Regenerate CSS
- Refresh the Elementor editor

### PHP Errors

**Issue:** White screen or errors

**Solutions:**
- Check WordPress and PHP version requirements
- Deactivate and reactivate plugin
- Check error logs in wp-content/debug.log
- Verify all plugin files uploaded correctly

---

## FAQ

**Q: Can I use this without Elementor?**  
A: Yes! The shortcodes work independently. Elementor is only needed for query filtering.

**Q: Does this work with ACF Free?**  
A: Yes, works with both ACF Free and Pro.

**Q: How do I customize the emoji?**  
A: Modify `assets/css/shortcodes.css` or use CSS to change the emoji content.

**Q: Can I filter other post types?**  
A: Yes! Add an ACF relationship field connecting that post type to locations, then use Elementor filters.

**Q: Where is the data cached?**  
A: WordPress object cache with 1-hour expiration. Cleared automatically on post updates.

**Q: How do I add more shortcodes?**  
A: See the Development section for extending the plugin.

---

## Support & Resources

- **Documentation:** See this guide and PLAN.md for technical details
- **Issues:** Check Troubleshooting section above
- **Development:** See copilot-instructions.md for dev guidelines
- **Updates:** See CHANGELOG.md for version history

---

**Last Updated:** October 27, 2025
