<?php
/**
 * Shortcodes Handler
 *
 * Registers and handles all plugin shortcodes.
 *
 * @package ACF_Location_Shortcodes
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes class.
 *
 * @since 1.0.0
 */
class ACF_Location_Shortcodes_Shortcodes {

	/**
	 * ACF helpers instance.
	 *
	 * @var ACF_Location_Shortcodes_ACF_Helpers
	 */
	private $acf_helpers;

	/**
	 * Site settings instance.
	 *
	 * @var ACF_Location_Shortcodes_Site_Settings
	 */
	private $site_settings;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param ACF_Location_Shortcodes_ACF_Helpers $acf_helpers ACF helpers instance.
	 * @param ACF_Location_Shortcodes_Site_Settings $site_settings Site settings instance.
	 */
	public function __construct( $acf_helpers, $site_settings = null ) {
		$this->acf_helpers = $acf_helpers;
		$this->site_settings = $site_settings;
		$this->register_shortcodes();
	}

	/**
	 * Register all shortcodes.
	 *
	 * @since 1.0.0
	 */
	public function register_shortcodes() {
		add_shortcode( 'location_communities', array( $this, 'render_communities_list' ) );
		add_shortcode( 'location_info', array( $this, 'render_location_info' ) );
		add_shortcode( 'location_list', array( $this, 'render_location_list' ) );
		add_shortcode( 'location_address', array( $this, 'render_location_address' ) );

		// Site location shortcodes.
		add_shortcode( 'site_location_title', array( $this, 'render_site_location_title' ) );
		add_shortcode( 'site_location_city', array( $this, 'render_site_location_city' ) );
		add_shortcode( 'site_location_state', array( $this, 'render_site_location_state' ) );
		add_shortcode( 'site_location_state_abbrev', array( $this, 'render_site_location_state_abbrev' ) );
		add_shortcode( 'site_location_city_state', array( $this, 'render_site_location_city_state' ) );
		add_shortcode( 'site_service_areas', array( $this, 'render_site_service_areas' ) );

		// Team member shortcodes.
		add_shortcode( 'team_member_locations', array( $this, 'render_team_member_locations' ) );
		add_shortcode( 'team_member_specialties', array( $this, 'render_team_member_specialties' ) );
	}

	/**
	 * Render communities list shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_communities_list( $atts ) {
		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'location_id' => get_the_ID(),
				'limit'       => 0,
				'class'       => '',
				'show_emoji'  => 'yes',
			),
			$atts,
			'location_communities'
		);

		// Sanitize attributes.
		$location_id = absint( $atts['location_id'] );
		$limit       = absint( $atts['limit'] );
		$class       = sanitize_html_class( $atts['class'] );
		$show_emoji  = $atts['show_emoji'] === 'yes';

		// Validate location ID.
		if ( ! $location_id || get_post_type( $location_id ) !== 'location' ) {
			return $this->render_error( __( 'Invalid location ID.', 'acf-location-shortcodes' ) );
		}

		// Get communities.
		$communities = $this->acf_helpers->get_surrounding_communities( $location_id );

		// Handle empty data.
		if ( empty( $communities ) ) {
			return $this->render_error( __( 'No communities found for this location.', 'acf-location-shortcodes' ) );
		}

		// Apply limit if specified.
		if ( $limit > 0 ) {
			$communities = array_slice( $communities, 0, $limit );
		}

		// Build CSS classes.
		$css_classes = array( 'acf-ls-communities' );
		if ( ! empty( $class ) ) {
			$css_classes[] = $class;
		}
		if ( $show_emoji ) {
			$css_classes[] = 'acf-ls-communities--with-emoji';
		}

		// Build HTML output.
		ob_start();
		?>
		<ul class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>">
			<?php foreach ( $communities as $community ) : ?>
				<li class="acf-ls-communities__item">
					<?php if ( $show_emoji ) : ?>
						<span class="acf-ls-communities__emoji" aria-hidden="true">🏠</span>
					<?php endif; ?>
					<span class="acf-ls-communities__text"><?php echo esc_html( $community ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render location info shortcode.
	 *
	 * Displays any ACF field from a location post.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Field value or error message.
	 */
	public function render_location_info( $atts ) {
		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'location_id' => get_the_ID(),
				'field'       => '',
				'default'     => '',
			),
			$atts,
			'location_info'
		);

		// Sanitize attributes.
		$location_id = absint( $atts['location_id'] );
		$field       = sanitize_key( $atts['field'] );
		$default     = sanitize_text_field( $atts['default'] );

		// Validate location ID.
		if ( ! $location_id || get_post_type( $location_id ) !== 'location' ) {
			return $this->render_error( __( 'Invalid location ID.', 'acf-location-shortcodes' ) );
		}

		// Validate field name.
		if ( empty( $field ) ) {
			return $this->render_error( __( 'Field name is required.', 'acf-location-shortcodes' ) );
		}

		// Get field value.
		$value = $this->acf_helpers->get_location_field( $field, $location_id, $default );

		// Handle different field types.
		if ( is_array( $value ) ) {
			// For arrays (like relationship fields), return comma-separated list.
			if ( isset( $value[0] ) && is_object( $value[0] ) && isset( $value[0]->post_title ) ) {
				$titles = array_map(
					function ( $item ) {
						return $item->post_title;
					},
					$value
				);
				return esc_html( implode( ', ', $titles ) );
			}
			return esc_html( implode( ', ', $value ) );
		} elseif ( is_object( $value ) && isset( $value->post_title ) ) {
			// For post objects, return the title.
			return esc_html( $value->post_title );
		}

		// Return as text.
		return esc_html( $value );
	}

	/**
	 * Render location list shortcode.
	 *
	 * Two modes:
	 * 1. All locations (default): Lists all physical locations and their service areas
	 * 2. Location-specific: Shows only the relevant parent and its children
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_location_list( $atts ) {
		// Store original attributes to check what user actually provided.
		$original_atts = $atts;
		
		// Parse attributes (don't set location_id default yet).
		$atts = shortcode_atts(
			array(
				'location_id'        => '', // Empty default, we'll handle it below
				'location_specific'  => 'false',
				'type'               => 'all', // 'all', 'physical', 'service'
				'orderby'            => 'title',
				'order'              => 'ASC',
				'limit'              => 0,
				'class'              => '',
				'show_emoji'         => 'yes',
			),
			$atts,
			'location_list'
		);

		// Determine location_id intelligently.
		$location_id = '';
		$using_site_location = false;
		
		// If user explicitly provided location_id, use it.
		if ( isset( $original_atts['location_id'] ) && ! empty( $original_atts['location_id'] ) ) {
			$location_id = absint( $original_atts['location_id'] );
			ACF_Location_Shortcodes::log( 'location_list: Using user-provided location_id', array( 'location_id' => $location_id ), 'debug' );
		}
		// If no location_id provided, try to get from context.
		else {
			$current_post_id = get_the_ID();
			
			ACF_Location_Shortcodes::log( 'location_list: No location_id provided by user', array( 
				'current_post_id' => $current_post_id,
				'current_post_type' => get_post_type( $current_post_id ),
			), 'debug' );
			
			// Check if we're on a location page.
			if ( $current_post_id && get_post_type( $current_post_id ) === 'location' ) {
				$location_id = $current_post_id;
				ACF_Location_Shortcodes::log( 'location_list: On location page, using current location', array( 'location_id' => $location_id ), 'debug' );
			}
			// Not on a location page - try to use site settings.
			elseif ( $this->site_settings ) {
				$site_location_id = $this->site_settings->get_location_id();
				
				ACF_Location_Shortcodes::log( 'location_list: Not on location page, checking site settings', array( 
					'site_location_id' => $site_location_id,
					'site_location_post_type' => $site_location_id ? get_post_type( $site_location_id ) : 'none',
				), 'debug' );
				
				if ( $site_location_id && get_post_type( $site_location_id ) === 'location' ) {
					$location_id = $site_location_id;
					$using_site_location = true;
					
					// Default to location-specific mode when using site location
					// (only if user didn't explicitly set location_specific attribute).
					if ( ! isset( $original_atts['location_specific'] ) ) {
						$atts['location_specific'] = 'true';
						ACF_Location_Shortcodes::log( 'location_list: Auto-enabling location_specific mode for site location', array( 'location_id' => $location_id ), 'debug' );
					}
				}
			}
		}

		// Sanitize remaining attributes.
		$location_id       = absint( $location_id );
		$location_specific = filter_var( $atts['location_specific'], FILTER_VALIDATE_BOOLEAN );
		$type              = sanitize_key( $atts['type'] );
		$orderby           = sanitize_key( $atts['orderby'] );
		$order             = strtoupper( sanitize_key( $atts['order'] ) ) === 'DESC' ? 'DESC' : 'ASC';
		$limit             = absint( $atts['limit'] );
		$class             = sanitize_html_class( $atts['class'] );
		$show_emoji        = filter_var( $atts['show_emoji'], FILTER_VALIDATE_BOOLEAN );

		ACF_Location_Shortcodes::log( 'location_list: Final values', array( 
			'location_id' => $location_id,
			'location_specific' => $location_specific,
			'using_site_location' => $using_site_location,
		), 'debug' );

		// Build CSS classes.
		$css_classes = array( 'acf-ls-locations' );
		if ( ! empty( $class ) ) {
			$css_classes[] = $class;
		}
		if ( $show_emoji ) {
			$css_classes[] = 'acf-ls-locations--with-emoji';
		}

		// Handle location-specific mode.
		if ( $location_specific ) {
			ACF_Location_Shortcodes::log( 'location_list: Rendering location-specific list', array( 'location_id' => $location_id ), 'debug' );
			return $this->render_location_specific_list( $location_id, $orderby, $order, $css_classes, $show_emoji );
		}

		// Handle all locations mode.
		ACF_Location_Shortcodes::log( 'location_list: Rendering all locations list', array( 'type' => $type ), 'debug' );
		return $this->render_all_locations_list( $type, $orderby, $order, $limit, $css_classes, $show_emoji );
	}

	/**
	 * Render location-specific list (parent and its children).
	 *
	 * @since 1.0.0
	 * @param int    $location_id Location post ID.
	 * @param string $orderby     Order by field.
	 * @param string $order       Sort order.
	 * @param array  $css_classes CSS classes.
	 * @param bool   $show_emoji  Show emoji flag.
	 * @return string HTML output.
	 */
	private function render_location_specific_list( $location_id, $orderby, $order, $css_classes, $show_emoji ) {
		// Validate location ID.
		if ( ! $location_id || 'location' !== get_post_type( $location_id ) ) {
			return $this->render_error( __( 'Invalid location ID.', 'acf-location-shortcodes' ) );
		}

		// Determine the physical location to display.
		$physical_location = null;
		
		if ( $this->acf_helpers->is_physical_location( $location_id ) ) {
			// Current location is a physical location - use it.
			$physical_location = get_post( $location_id );
		} else {
			// Current location is a service area - get its parent.
			$physical_location = $this->acf_helpers->get_servicing_location( $location_id );
		}

		// Handle no physical location found.
		if ( ! $physical_location ) {
			return $this->render_error( __( 'No physical location found for this service area.', 'acf-location-shortcodes' ) );
		}

		// Get all service areas for this physical location.
		$service_areas = $this->acf_helpers->get_service_areas_by_physical_location( $physical_location->ID );

		// Apply ordering to service areas.
		if ( 'title' === $orderby && ! empty( $service_areas ) ) {
			usort( $service_areas, function( $a, $b ) use ( $order ) {
				$result = strcmp( $a->post_title, $b->post_title );
				return $order === 'DESC' ? -$result : $result;
			});
		}

		// Build HTML output.
		ob_start();
		?>
		<ul class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>">
			<!-- Physical Location -->
			<li class="acf-ls-locations__item acf-ls-locations__item--physical">
				<?php if ( $show_emoji ) : ?>
					<span class="acf-ls-locations__emoji" aria-hidden="true">📍</span>
				<?php endif; ?>
				<span class="acf-ls-locations__text">
					<?php echo esc_html( $physical_location->post_title ); ?>
				</span>
			</li>
			
			<!-- Service Areas (Children) -->
			<?php if ( ! empty( $service_areas ) ) : ?>
				<?php foreach ( $service_areas as $service_area ) : ?>
					<li class="acf-ls-locations__item acf-ls-locations__item--service">
						<?php if ( $show_emoji ) : ?>
							<span class="acf-ls-locations__emoji" aria-hidden="true">📍</span>
						<?php endif; ?>
						<span class="acf-ls-locations__text">
							<?php echo esc_html( $service_area->post_title ); ?>
						</span>
					</li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render all locations list.
	 *
	 * @since 1.0.0
	 * @param string $type        Location type filter.
	 * @param string $orderby     Order by field.
	 * @param string $order       Sort order.
	 * @param int    $limit       Maximum locations to show.
	 * @param array  $css_classes CSS classes.
	 * @param bool   $show_emoji  Show emoji flag.
	 * @return string HTML output.
	 */
	private function render_all_locations_list( $type, $orderby, $order, $limit, $css_classes, $show_emoji ) {
		// Get all locations.
		$all_locations = $this->acf_helpers->get_all_locations();

		// Filter by type if specified.
		$locations = array();
		
		if ( 'physical' === $type ) {
			// Only physical locations.
			foreach ( $all_locations as $location ) {
				if ( $this->acf_helpers->is_physical_location( $location->ID ) ) {
					$locations[] = $location;
				}
			}
		} elseif ( 'service' === $type ) {
			// Only service areas.
			foreach ( $all_locations as $location ) {
				if ( ! $this->acf_helpers->is_physical_location( $location->ID ) ) {
					$locations[] = $location;
				}
			}
		} else {
			// All locations.
			$locations = $all_locations;
		}

		// Apply ordering.
		if ( 'title' === $orderby ) {
			usort( $locations, function( $a, $b ) use ( $order ) {
				$result = strcmp( $a->post_title, $b->post_title );
				return $order === 'DESC' ? -$result : $result;
			});
		} elseif ( 'date' === $orderby ) {
			usort( $locations, function( $a, $b ) use ( $order ) {
				$result = strcmp( $a->post_date, $b->post_date );
				return $order === 'DESC' ? -$result : $result;
			});
		} elseif ( 'ID' === $orderby ) {
			usort( $locations, function( $a, $b ) use ( $order ) {
				$result = $a->ID - $b->ID;
				return $order === 'DESC' ? -$result : $result;
			});
		}

		// Apply limit.
		if ( $limit > 0 ) {
			$locations = array_slice( $locations, 0, $limit );
		}

		// Handle empty data.
		if ( empty( $locations ) ) {
			return $this->render_error( __( 'No locations found.', 'acf-location-shortcodes' ) );
		}

		// Build HTML output.
		ob_start();
		?>
		<ul class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>">
			<?php foreach ( $locations as $location ) : ?>
				<?php
				$is_physical = $this->acf_helpers->is_physical_location( $location->ID );
				$item_class  = $is_physical ? 'acf-ls-locations__item--physical' : 'acf-ls-locations__item--service';
				?>
				<li class="acf-ls-locations__item <?php echo esc_attr( $item_class ); ?>">
					<?php if ( $show_emoji ) : ?>
						<span class="acf-ls-locations__emoji" aria-hidden="true">📍</span>
					<?php endif; ?>
					<span class="acf-ls-locations__text">
						<?php echo esc_html( $location->post_title ); ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render location address shortcode.
	 *
	 * Returns the physical address in format: "Site Title, Address"
	 * For service areas without an address, uses the servicing physical location's address.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Formatted address or empty string.
	 */
	public function render_location_address( $atts ) {
		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'location_id' => get_the_ID(),
			),
			$atts,
			'location_address'
		);

		// Sanitize location ID.
		$location_id = absint( $atts['location_id'] );

		// Validate location ID.
		if ( ! $location_id || 'location' !== get_post_type( $location_id ) ) {
			return '';
		}

		// Get the location post.
		$location = get_post( $location_id );
		if ( ! $location ) {
			return '';
		}

		$site_title = $location->post_title;
		$address    = '';

		// Check if this location has a physical address.
		if ( $this->acf_helpers->is_physical_location( $location_id ) ) {
			// Physical location - use its own address.
			$address = $this->acf_helpers->get_location_field( 'address', $location_id );
		} else {
			// Service area - get the servicing physical location's address.
			$servicing_location = $this->acf_helpers->get_servicing_location( $location_id );
			
			if ( $servicing_location ) {
				$site_title = $servicing_location->post_title;
				$address    = $this->acf_helpers->get_location_field( 'address', $servicing_location->ID );
			}
		}

		// Return empty if no address found.
		if ( empty( $address ) ) {
			return '';
		}

		// Format: "Site Title, Address"
		return esc_html( $site_title . ', ' . $address );
	}

	/**
	 * Render site location title shortcode.
	 *
	 * Displays the post title from the associated Location post.
	 * This is typically the city name (e.g., "Sioux Falls").
	 *
	 * @since 2.3.0
	 * @param array $atts Shortcode attributes.
	 * @return string Location post title.
	 */
	public function render_site_location_title( $atts ) {
		if ( ! $this->site_settings ) {
			return '';
		}

		$location_id = $this->site_settings->get_location_id();
		
		if ( ! $location_id || get_post_type( $location_id ) !== 'location' ) {
			return '';
		}

		return esc_html( get_the_title( $location_id ) );
	}

	/**
	 * Render site location city shortcode.
	 *
	 * Displays the city from the associated Location post's ACF fields.
	 *
	 * @since 2.3.0
	 * @param array $atts Shortcode attributes.
	 * @return string City name.
	 */
	public function render_site_location_city( $atts ) {
		if ( ! $this->site_settings ) {
			return '';
		}

		return esc_html( $this->site_settings->get_location_field( 'location_city' ) );
	}

	/**
	 * Render site location state shortcode.
	 *
	 * Displays the state from the associated Location post's ACF fields.
	 *
	 * @since 2.3.0
	 * @param array $atts Shortcode attributes.
	 * @return string State name.
	 */
	public function render_site_location_state( $atts ) {
		if ( ! $this->site_settings ) {
			return '';
		}

		return esc_html( $this->site_settings->get_location_field( 'location_state' ) );
	}

	/**
	 * Render site location state abbreviation shortcode.
	 *
	 * Displays the state abbreviation from the associated Location post's ACF fields.
	 *
	 * @since 2.3.0
	 * @param array $atts Shortcode attributes.
	 * @return string State abbreviation.
	 */
	public function render_site_location_state_abbrev( $atts ) {
		if ( ! $this->site_settings ) {
			return '';
		}

		return esc_html( $this->site_settings->get_location_field( 'location_state_abbrev' ) );
	}

	/**
	 * Render site location city and state shortcode.
	 *
	 * @since 2.3.0
	 * @param array $atts Shortcode attributes.
	 * @return string Formatted city and state.
	 */
	public function render_site_location_city_state( $atts ) {
		if ( ! $this->site_settings ) {
			return '';
		}

		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'format' => 'abbrev', // 'abbrev' or 'full'
			),
			$atts,
			'site_location_city_state'
		);

		$format = sanitize_key( $atts['format'] );

		return esc_html( $this->site_settings->get_formatted_location( $format ) );
	}

	/**
	 * Render site service areas shortcode.
	 *
	 * Displays a comma-separated list of service areas for the associated location.
	 * Format: "Area 1, Area 2, and Area 3"
	 *
	 * @since 2.3.0
	 * @param array $atts Shortcode attributes.
	 * @return string Formatted service areas list.
	 */
	public function render_site_service_areas( $atts ) {
		if ( ! $this->site_settings ) {
			return '';
		}

		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'format' => 'comma_and', // 'comma', 'comma_and', 'comma_and_surrounding', or 'list'
			),
			$atts,
			'site_service_areas'
		);

		$format = sanitize_key( $atts['format'] );

		// Validate format.
		if ( ! in_array( $format, array( 'comma', 'comma_and', 'comma_and_surrounding', 'list' ), true ) ) {
			$format = 'comma_and';
		}

		// For list format, return raw HTML (already escaped in get_service_areas).
		if ( $format === 'list' ) {
			return $this->site_settings->get_service_areas( $format );
		}

		// For text formats, escape the output.
		return esc_html( $this->site_settings->get_service_areas( $format ) );
	}

	/**
	 * Render team member locations shortcode.
	 *
	 * Displays a list of locations assigned to the team member.
	 * Should be used on a team-member post page.
	 *
	 * @since 2.3.0
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_team_member_locations( $atts ) {
		// Ensure we always return a string, even on errors.
		try {
			$result = $this->render_team_member_locations_internal( $atts );
			return is_string( $result ) ? $result : '';
		} catch ( Exception $e ) {
			// Log error but return empty string to prevent fatal errors.
			if ( class_exists( 'ACF_Location_Shortcodes' ) && method_exists( 'ACF_Location_Shortcodes', 'log' ) ) {
				ACF_Location_Shortcodes::log( 'Error in team_member_locations shortcode', array( 'error' => $e->getMessage() ), 'error' );
			}
			return '';
		}
	}

	/**
	 * Internal method to render team member locations.
	 *
	 * @since 2.3.0
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	private function render_team_member_locations_internal( $atts ) {
		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'team_member_id' => get_the_ID(),
				'format'         => 'comma', // 'comma', 'comma_and', or 'list'
				'class'          => '',
			),
			$atts,
			'team_member_locations'
		);

		// Sanitize attributes.
		$team_member_id = absint( $atts['team_member_id'] );
		$format         = sanitize_key( $atts['format'] );
		$class          = sanitize_html_class( $atts['class'] );

		// Validate format.
		if ( ! in_array( $format, array( 'comma', 'comma_and', 'list' ), true ) ) {
			$format = 'comma';
		}

		// Validate team member ID.
		if ( ! $team_member_id || get_post_type( $team_member_id ) !== 'team-member' ) {
			return $this->render_error( __( 'Invalid team member ID. This shortcode must be used on a team-member post page.', 'acf-location-shortcodes' ) );
		}

		// Check if ACF is available.
		if ( ! function_exists( 'get_field' ) ) {
			return $this->render_error( __( 'ACF plugin is required for this shortcode.', 'acf-location-shortcodes' ) );
		}

		// Get locations assigned to this team member.
		$locations = get_field( 'location', $team_member_id );

		// Handle empty data (false, null, empty array, etc.).
		if ( empty( $locations ) || $locations === false || $locations === null ) {
			return $this->render_error( __( 'No locations assigned to this team member.', 'acf-location-shortcodes' ) );
		}

		// Ensure we have an array to work with.
		if ( ! is_array( $locations ) && ! is_object( $locations ) ) {
			// If it's not an array or object, it's invalid.
			return $this->render_error( __( 'Invalid location data format.', 'acf-location-shortcodes' ) );
		}

		// Normalize to array if single object.
		if ( ! is_array( $locations ) ) {
			$locations = array( $locations );
		}

		// Extract location titles - handle all possible formats.
		$location_titles = array();
		foreach ( $locations as $location ) {
			$title = null;
			
			// Handle post object.
			if ( is_object( $location ) ) {
				if ( isset( $location->post_title ) ) {
					$title = $location->post_title;
				} elseif ( isset( $location->ID ) ) {
					// If we have an ID but no title, fetch the post.
					$location_post = get_post( $location->ID );
					if ( $location_post && isset( $location_post->post_title ) ) {
						$title = $location_post->post_title;
					}
				}
			}
			// Handle array format.
			elseif ( is_array( $location ) ) {
				if ( isset( $location['post_title'] ) ) {
					$title = $location['post_title'];
				} elseif ( isset( $location['ID'] ) ) {
					// If we have an ID but no title, fetch the post.
					$location_post = get_post( $location['ID'] );
					if ( $location_post && isset( $location_post->post_title ) ) {
						$title = $location_post->post_title;
					}
				}
			}
			// Handle numeric ID.
			elseif ( is_numeric( $location ) ) {
				$location_post = get_post( absint( $location ) );
				if ( $location_post && isset( $location_post->post_title ) ) {
					$title = $location_post->post_title;
				}
			}
			
			// Only add valid, non-empty titles.
			if ( ! empty( $title ) && is_string( $title ) ) {
				$location_titles[] = sanitize_text_field( $title );
			}
		}

		// Handle empty titles.
		if ( empty( $location_titles ) ) {
			return $this->render_error( __( 'No valid locations found for this team member.', 'acf-location-shortcodes' ) );
		}

		// Build CSS classes.
		$css_classes = array( 'acf-ls-team-member-locations' );
		if ( ! empty( $class ) ) {
			$css_classes[] = $class;
		}

		// Format output based on format attribute.
		if ( $format === 'list' ) {
			// Return as HTML list.
			ob_start();
			?>
			<ul class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>">
				<?php foreach ( $location_titles as $title ) : ?>
					<li class="acf-ls-team-member-locations__item"><?php echo esc_html( $title ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php
			$output = ob_get_clean();
			return is_string( $output ) ? $output : '';
		} elseif ( $format === 'comma_and' ) {
			// Format: "Location 1, Location 2, and Location 3"
			if ( empty( $location_titles ) ) {
				return '';
			}
			$last_location = array_pop( $location_titles );
			if ( empty( $last_location ) ) {
				return '';
			}
			$output = '';
			if ( ! empty( $location_titles ) ) {
				$output = esc_html( implode( ', ', $location_titles ) );
				$output .= ' ' . esc_html__( 'and', 'acf-location-shortcodes' ) . ' ';
			}
			$output .= esc_html( $last_location );
			return (string) '<span class="' . esc_attr( implode( ' ', $css_classes ) ) . '">' . $output . '</span>';
		} else {
			// Default: comma-separated format.
			return (string) '<span class="' . esc_attr( implode( ' ', $css_classes ) ) . '">' . esc_html( implode( ', ', $location_titles ) ) . '</span>';
		}
	}

	/**
	 * Render team member specialties shortcode.
	 *
	 * Displays a list of specialties (services) assigned to the team member.
	 * Should be used on a team-member post page.
	 *
	 * @since 2.3.0
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_team_member_specialties( $atts ) {
		// Ensure we always return a string, even on errors.
		try {
			$result = $this->render_team_member_specialties_internal( $atts );
			return is_string( $result ) ? $result : '';
		} catch ( Exception $e ) {
			// Log error but return empty string to prevent fatal errors.
			if ( function_exists( 'ACF_Location_Shortcodes::log' ) ) {
				ACF_Location_Shortcodes::log( 'Error in team_member_specialties shortcode', array( 'error' => $e->getMessage() ), 'error' );
			}
			return '';
		}
	}

	/**
	 * Internal method to render team member specialties.
	 *
	 * @since 2.3.0
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	private function render_team_member_specialties_internal( $atts ) {
		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'team_member_id' => get_the_ID(),
				'format'         => 'comma', // 'comma', 'comma_and', or 'list'
				'class'          => '',
			),
			$atts,
			'team_member_specialties'
		);

		// Sanitize attributes.
		$team_member_id = absint( $atts['team_member_id'] );
		$format         = sanitize_key( $atts['format'] );
		$class          = sanitize_html_class( $atts['class'] );

		// Validate format.
		if ( ! in_array( $format, array( 'comma', 'comma_and', 'list' ), true ) ) {
			$format = 'comma';
		}

		// Validate team member ID.
		if ( ! $team_member_id || get_post_type( $team_member_id ) !== 'team-member' ) {
			return $this->render_error( __( 'Invalid team member ID. This shortcode must be used on a team-member post page.', 'acf-location-shortcodes' ) );
		}

		// Check if ACF is available.
		if ( ! function_exists( 'get_field' ) ) {
			return $this->render_error( __( 'ACF plugin is required for this shortcode.', 'acf-location-shortcodes' ) );
		}

		// Get specialties assigned to this team member.
		$specialties = get_field( 'specialties', $team_member_id );

		// Handle empty data (false, null, empty array, etc.).
		if ( empty( $specialties ) || $specialties === false || $specialties === null ) {
			return $this->render_error( __( 'No specialties assigned to this team member.', 'acf-location-shortcodes' ) );
		}

		// Ensure we have an array to work with.
		if ( ! is_array( $specialties ) && ! is_object( $specialties ) ) {
			// If it's not an array or object, it's invalid.
			return $this->render_error( __( 'Invalid specialties data format.', 'acf-location-shortcodes' ) );
		}

		// Normalize to array if single object.
		if ( ! is_array( $specialties ) ) {
			$specialties = array( $specialties );
		}

		// Extract service titles - handle all possible formats.
		$service_titles = array();
		foreach ( $specialties as $specialty ) {
			$title = null;
			
			// Handle post object.
			if ( is_object( $specialty ) ) {
				if ( isset( $specialty->post_title ) ) {
					$title = $specialty->post_title;
				} elseif ( isset( $specialty->ID ) ) {
					// If we have an ID but no title, fetch the post.
					$service_post = get_post( $specialty->ID );
					if ( $service_post && isset( $service_post->post_title ) ) {
						$title = $service_post->post_title;
					}
				}
			}
			// Handle array format.
			elseif ( is_array( $specialty ) ) {
				if ( isset( $specialty['post_title'] ) ) {
					$title = $specialty['post_title'];
				} elseif ( isset( $specialty['ID'] ) ) {
					// If we have an ID but no title, fetch the post.
					$service_post = get_post( $specialty['ID'] );
					if ( $service_post && isset( $service_post->post_title ) ) {
						$title = $service_post->post_title;
					}
				}
			}
			// Handle numeric ID.
			elseif ( is_numeric( $specialty ) ) {
				$service_post = get_post( absint( $specialty ) );
				if ( $service_post && isset( $service_post->post_title ) ) {
					$title = $service_post->post_title;
				}
			}
			
			// Only add valid, non-empty titles.
			if ( ! empty( $title ) && is_string( $title ) ) {
				$service_titles[] = sanitize_text_field( $title );
			}
		}

		// Handle empty titles.
		if ( empty( $service_titles ) ) {
			return $this->render_error( __( 'No valid specialties found for this team member.', 'acf-location-shortcodes' ) );
		}

		// Build CSS classes.
		$css_classes = array( 'acf-ls-team-member-specialties' );
		if ( ! empty( $class ) ) {
			$css_classes[] = $class;
		}

		// Format output based on format attribute.
		if ( $format === 'list' ) {
			// Return as HTML list.
			ob_start();
			?>
			<ul class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>">
				<?php foreach ( $service_titles as $title ) : ?>
					<li class="acf-ls-team-member-specialties__item"><?php echo esc_html( $title ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php
			$output = ob_get_clean();
			return is_string( $output ) ? $output : '';
		} elseif ( $format === 'comma_and' ) {
			// Format: "Service 1, Service 2, and Service 3"
			if ( empty( $service_titles ) ) {
				return '';
			}
			$last_service = array_pop( $service_titles );
			if ( empty( $last_service ) ) {
				return '';
			}
			$output = '';
			if ( ! empty( $service_titles ) ) {
				$output = esc_html( implode( ', ', $service_titles ) );
				$output .= ' ' . esc_html__( 'and', 'acf-location-shortcodes' ) . ' ';
			}
			$output .= esc_html( $last_service );
			return (string) '<span class="' . esc_attr( implode( ' ', $css_classes ) ) . '">' . $output . '</span>';
		} else {
			// Default: comma-separated format.
			return (string) '<span class="' . esc_attr( implode( ' ', $css_classes ) ) . '">' . esc_html( implode( ', ', $service_titles ) ) . '</span>';
		}
	}

	/**
	 * Render an error message.
	 *
	 * Only shown to logged-in users with edit capabilities.
	 *
	 * @since 1.0.0
	 * @param string $message Error message.
	 * @return string HTML output or empty string.
	 */
	private function render_error( $message ) {
		// Only show errors to users who can edit posts.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}

		return sprintf(
			'<div class="acf-ls-error" style="padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; color: #856404;">%s</div>',
			esc_html( $message )
		);
	}
}
