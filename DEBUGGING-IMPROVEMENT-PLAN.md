# Debugging & Error Handling Improvement Plan

**Version:** 1.1.0  
**Date:** October 28, 2025  
**Status:** Implementation In Progress

## Overview

This document outlines planned improvements to debugging, error handling, and user experience for the ACF Location Shortcodes plugin. These improvements will make it significantly easier for users to troubleshoot issues and configure the plugin correctly.

---

## Goals

1. **Enhanced Error Messages** - Provide contextual, actionable error messages
2. **Debug Mode** - Add verbose debugging capability for developers
3. **Proactive Warnings** - Alert users to configuration issues before they cause problems
4. **One-Click ACF Install** - Streamline the ACF dependency installation process
5. **Better Developer Experience** - Add logging and validation tools

---

## Feature 1: Debug Mode

### Implementation Details

**Add Debug Constant**
- Define `ACF_LS_DEBUG` constant (respects `WP_DEBUG` by default)
- Enable verbose error output for authorized users
- Add collapsible debug data sections in error messages

**Debug Information to Include:**
- Current post ID and post type
- Provided shortcode attributes
- ACF field values and availability
- Query arguments (for Elementor)
- Available locations and fields
- Edit links to relevant admin pages

**Example Output:**
```
ACF Location Shortcodes Error: Post ID 123 is not a location post. Found post type: page

[Debug Info ▼]
  Provided location_id: 123
  Current post ID: 456
  Current post type: page
  Available location posts: [View all locations →]
```

### Code Location
- Main plugin file: Add `ACF_LS_DEBUG` constant
- `class-shortcodes.php`: Enhance `render_error()` method
- All helper methods: Add debug data collection

---

## Feature 2: Enhanced Error Messages

### Current Issues
❌ Generic messages: "Invalid location ID"  
❌ No context about what was provided  
❌ Doesn't explain how to fix the issue  
❌ Silent failures in helper methods

### Planned Improvements

#### 2.1 Shortcode Error Messages

**Communities Shortcode**
- Distinguish between "no location ID", "wrong post type", and "empty field"
- Show post title and ID in error messages
- Provide edit links to fix the issue
- Explain expected field format

**Location Info Shortcode**
- List available fields if requested field doesn't exist
- Show example usage in error message
- Indicate if ACF plugin is missing
- Warn about empty default values

**Location List Shortcode**
- Explain parent/child relationship issues
- Show which locations were found/not found
- Clarify physical vs service area logic

#### 2.2 Helper Method Validation

**Add Validation Methods:**
- `field_exists($field_name, $post_id)` - Check if ACF field exists
- `get_field_names($post_id)` - List all available fields
- `get_location_field_validated($field, $post_id, $default)` - Return structured result with errors

**Return Structured Data:**
```php
array(
    'success' => false,
    'value' => '',
    'error' => 'Field "xyz" does not exist',
    'debug' => array(
        'field_name' => 'xyz',
        'post_id' => 123,
        'available_fields' => array(...)
    )
)
```

### Code Location
- `class-shortcodes.php`: Update all render methods
- `class-acf-helpers.php`: Add validation methods
- `class-acf-helpers.php`: Return structured data instead of empty values

---

## Feature 3: Logging System

### Implementation Details

**Static Log Method:**
```php
ACF_Location_Shortcodes::log($message, $data, $level)
```

**Log Levels:**
- `info` - General information
- `warning` - Potential issues
- `error` - Failures

**What to Log:**
- Cache hits/misses
- Empty field values
- Missing servicing locations
- Elementor query modifications
- ACF field availability checks

**Storage:**
- Use `error_log()` when `ACF_LS_DEBUG` is enabled
- Prefix all logs: `[ACF Location Shortcodes][LEVEL]`
- Include timestamp and context data

### Code Location
- Main plugin file: Add static `log()` method
- All classes: Add logging calls at key decision points

---

## Feature 4: Admin Notices

### Types of Notices

#### 4.1 Missing Dependencies
✅ Already implemented: ACF missing notice  
➕ Add: Link to one-click install (see Feature 5)

#### 4.2 Configuration Warnings
**Check for:**
- Location post type doesn't exist
- No location posts created yet
- Required ACF fields missing
- Conflicting plugin settings

**Notice Examples:**
```
⚠️ ACF Location Shortcodes: No location posts found. Create your first location to get started.
[Create Location →]

⚠️ ACF Location Shortcodes: The ACF field "surrounding_community_list" is missing on location posts.
[Configure ACF Fields →]

⚠️ ACF Location Shortcodes: Your location posts don't have the required ACF fields configured.
[View Setup Guide →]
```

#### 4.3 Success Notices
- ACF successfully installed
- First location created
- Plugin activated successfully

### Display Logic
- Check conditions on `admin_notices` hook
- Use transients to avoid repeated checks
- Make notices dismissible where appropriate
- Show only to users with `manage_options` capability

### Code Location
- Main plugin file: Expand `admin_notices()` method
- Add configuration validation functions

---

## Feature 5: One-Click ACF Install

### User Experience

**When ACF is Not Installed:**

1. **Admin Notice (Primary UI)**
```
┌─────────────────────────────────────────────────────────────┐
│ ⚠️ ACF Location Shortcodes requires Advanced Custom Fields  │
│                                                              │
│ The Advanced Custom Fields plugin is required for this      │
│ plugin to function. Install it now with one click.          │
│                                                              │
│ [Install ACF Free Now] [Learn More]        [Dismiss]        │
└─────────────────────────────────────────────────────────────┘
```

2. **Click "Install ACF Free Now"**
   - Shows loading indicator
   - Installs ACF from WordPress.org repository
   - Activates ACF automatically
   - Shows success message
   - Refreshes admin notices

3. **Success Message**
```
┌─────────────────────────────────────────────────────────────┐
│ ✅ Advanced Custom Fields has been installed and activated! │
│                                                              │
│ Next step: Configure your location fields.                  │
│ [Configure ACF Fields →]                [Dismiss]           │
└─────────────────────────────────────────────────────────────┘
```

### Technical Implementation

#### 5.1 Plugin Installation Flow

**Prerequisites Check:**
- User has `install_plugins` capability
- User has `activate_plugins` capability
- WordPress.org API is accessible

**Installation Steps:**
1. Verify nonce security
2. Include WordPress plugin install libraries
3. Use `Plugin_Upgrader` class to install from WordPress.org
4. Activate plugin programmatically
5. Verify ACF is now active
6. Set transient for success message
7. Redirect to avoid form resubmission

**Code Structure:**
```php
// Handler method
public function handle_acf_install() {
    // Verify nonce
    // Check capabilities
    // Include required files
    // Install plugin
    // Activate plugin
    // Show success/error
}
```

#### 5.2 AJAX Implementation (Optional Enhancement)

**For Better UX:**
- Use AJAX to avoid page reload
- Show progress indicator
- Real-time status updates
- Error handling without page refresh

**AJAX Flow:**
1. User clicks "Install ACF Free Now"
2. JavaScript sends AJAX request
3. Server installs and activates
4. Server returns success/error
5. JavaScript updates UI dynamically

#### 5.3 Fallback Options

**If Installation Fails:**
- Provide direct link to WordPress.org
- Link to manual installation guide
- Show alternative: ACF Pro installation
- Contact support option

**Error Messages:**
```
❌ Could not install Advanced Custom Fields automatically.

Possible reasons:
• File permissions issue
• WordPress.org API temporarily unavailable
• Hosting restrictions on plugin installation

Please try:
1. Install manually from WordPress.org
2. Contact your hosting provider
3. Review our installation guide

[Download ACF Manually] [View Guide] [Retry]
```

### Security Considerations

**Required Checks:**
- Nonce verification for all actions
- Capability checks: `install_plugins`, `activate_plugins`
- Sanitize all input
- Rate limiting to prevent abuse
- Verify plugin source (WordPress.org only)

**Code Location:**
- Main plugin file: Add install handler method
- Main plugin file: Add AJAX handlers (if implementing AJAX)
- `assets/js/admin.js`: New file for AJAX functionality
- Add new notice in `acf_missing_notice()` method

---

## Feature 6: Elementor Integration Debugging

### Current Issues
- Silent failures when filtering doesn't work
- No indication of query modifications
- Difficult to debug relationship field issues

### Planned Improvements

#### 6.1 Query Debug Logging
```php
// Log query modifications when debug enabled
if ( defined( 'ACF_LS_DEBUG' ) && ACF_LS_DEBUG ) {
    error_log( sprintf(
        '[ACF Location Shortcodes] Elementor query modified - Widget: %s, Locations: %s',
        $widget->get_name(),
        implode( ', ', $location_ids )
    ) );
}
```

#### 6.2 Editor Warnings

**In Elementor Editor:**
- Show notice if relationship field doesn't exist
- Warn if no locations are selected
- Indicate if current page is not a location
- Preview query results count

#### 6.3 Validation Alerts

**Check and warn about:**
- Relationship field name mismatch
- No posts found with current filters
- ACF field not configured on post type
- Invalid location IDs

### Code Location
- `class-elementor-integration.php`: Add logging to `filter_query_by_location()`
- `assets/js/elementor-controls.js`: Add validation feedback

---

## Feature 7: Field Validation & Suggestions

### Auto-Detection Features

#### 7.1 Field Name Suggestions
When a field doesn't exist, suggest similar fields:
```
Error: Field "community_list" not found.

Did you mean one of these?
• surrounding_community_list
• service_area_shorthand
• full_service_area_name
```

#### 7.2 Post Type Validation
Verify location post type exists and suggest alternatives:
```
Warning: Post type "location" not found.

Found these similar post types:
• locations (2 posts)
• service-area (5 posts)

Using "location" post type? Create it in your theme or plugin.
```

### Code Location
- `class-acf-helpers.php`: Add fuzzy matching function
- `class-acf-helpers.php`: Add post type detection

---

## Feature 8: Enhanced Admin Dashboard

### Dashboard Widget (Future v1.2.0)

**Display:**
- Total locations count
- Physical vs service areas breakdown
- Locations missing required fields
- Recent location updates
- Quick links to common tasks

**Widget Code Location:**
- New file: `includes/class-admin-dashboard.php`

---

## Implementation Phases

### Phase 1: Core Debugging Infrastructure (v1.1.0)
**Priority: High**  
**Estimated Time: 4-6 hours**

- [ ] Add `ACF_LS_DEBUG` constant
- [ ] Enhance `render_error()` method with debug data
- [ ] Add static `log()` method
- [ ] Add field validation methods to ACF helpers

### Phase 2: Error Message Improvements (v1.1.0)
**Priority: High**  
**Estimated Time: 6-8 hours**

- [ ] Update all shortcode error messages
- [ ] Add structured error returns in helpers
- [ ] Implement field suggestion system
- [ ] Add contextual help links

### Phase 3: One-Click ACF Install (v1.1.0)
**Priority: High**  
**Estimated Time: 4-5 hours**

- [ ] Create install handler method
- [ ] Add enhanced admin notice UI
- [ ] Implement installation flow
- [ ] Add success/error handling
- [ ] Add security checks (nonce, capabilities)
- [ ] Test with various hosting environments

### Phase 4: Admin Notices & Warnings (v1.1.0)
**Priority: Medium**  
**Estimated Time: 3-4 hours**

- [ ] Add configuration validation
- [ ] Create dismissible notices
- [ ] Add setup wizard notice
- [ ] Implement transient-based checking

### Phase 5: Elementor Debug Features (v1.1.0)
**Priority: Medium**  
**Estimated Time: 3-4 hours**

- [ ] Add query logging
- [ ] Add editor warnings
- [ ] Implement validation alerts
- [ ] Add query preview feature

### Phase 6: AJAX Enhancement (v1.2.0)
**Priority: Low**  
**Estimated Time: 4-5 hours**

- [ ] Create admin JavaScript file
- [ ] Implement AJAX install flow
- [ ] Add progress indicators
- [ ] Enhance error handling

---

## Testing Checklist

### Unit Tests
- [ ] Field validation methods
- [ ] Error message generation
- [ ] Field suggestion algorithm
- [ ] Logging functionality

### Integration Tests
- [ ] ACF installation flow
- [ ] Plugin activation sequence
- [ ] Admin notices display
- [ ] Elementor query filtering

### Environment Tests
- [ ] WordPress 5.8, 6.0, 6.4, 6.7
- [ ] PHP 7.4, 8.0, 8.1, 8.2, 8.3
- [ ] ACF Free vs Pro
- [ ] Elementor Free vs Pro
- [ ] Various hosting environments (shared, VPS, managed)
- [ ] Multisite compatibility

### User Experience Tests
- [ ] First-time installation
- [ ] Missing ACF scenario
- [ ] Missing fields scenario
- [ ] Invalid shortcode usage
- [ ] Debug mode enabled/disabled

---

## Code Quality Standards

### Documentation
- All new methods must have PHPDoc comments
- Debug features must have inline code comments
- Update USAGE.md with troubleshooting section
- Add debugging guide to README.md

### Security
- All AJAX endpoints must verify nonces
- Capability checks on all install actions
- Sanitize and validate all input
- Escape all output

### Performance
- Minimize admin notice checks
- Use transients for expensive validations
- Lazy load debug data collection
- Cache field existence checks

---

## Documentation Updates Required

### USAGE.md
Add new "Debugging & Troubleshooting" section:
- How to enable debug mode
- Reading debug output
- Common error messages and solutions
- Field validation errors

### README.md
- Update with one-click install feature
- Add troubleshooting quick links
- Mention debug mode capability

### copilot-instructions.md
- Add debugging guidelines
- Document error handling patterns
- Include logging best practices
- Add one-click install workflow

### PLAN.md
- Update with v1.1.0 features
- Add debugging architecture section
- Document admin notice system

---

## Success Metrics

### User Experience
- Reduce support requests by 50%
- Decrease time to resolution for common issues
- Increase successful first-time installations

### Developer Experience
- Easier debugging with verbose output
- Faster issue identification
- Better error context

### Code Quality
- 100% of errors have contextual messages
- All field access has validation
- Comprehensive logging coverage

---

## Rollback Plan

### If Issues Arise
1. Debug mode is opt-in (safe by default)
2. Enhanced errors only for authorized users
3. One-click install can be disabled via constant
4. Admin notices are dismissible

### Disable Features
```php
// In wp-config.php
define( 'ACF_LS_DISABLE_AUTO_INSTALL', true );
define( 'ACF_LS_DISABLE_ADMIN_NOTICES', true );
define( 'ACF_LS_DEBUG', false );
```

---

## Future Enhancements (v1.2.0+)

### Advanced Features
- Health check dashboard
- Automated field configuration detection
- Setup wizard for first-time users
- Diagnostic export for support
- Integration with Query Monitor plugin
- WP-CLI commands for debugging

### Analytics
- Track common error types
- Monitor field usage patterns
- Identify configuration issues
- Generate improvement recommendations

---

**Next Steps:**
1. Review and approve this plan
2. Update PLAN.md with v1.1.0 features
3. Update copilot-instructions.md with implementation guidelines
4. Begin Phase 1 implementation
5. Create feature branch: `feature/debugging-improvements`

**Questions to Address:**
- Should AJAX install be in v1.1.0 or deferred to v1.2.0?
- What hosting environments need special testing?
- Should we add a setup wizard in addition to one-click install?
- Do we want telemetry/error reporting (opt-in)?

---

**Last Updated:** October 28, 2025  
**Status:** Implementation In Progress
