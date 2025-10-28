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
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param ACF_Location_Shortcodes_ACF_Helpers $acf_helpers ACF helpers instance.
	 */
	public function __construct( $acf_helpers ) {
		$this->acf_helpers = $acf_helpers;
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
		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'location_id'        => get_the_ID(),
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

		// Sanitize attributes.
		$location_id       = absint( $atts['location_id'] );
		$location_specific = filter_var( $atts['location_specific'], FILTER_VALIDATE_BOOLEAN );
		$type              = sanitize_key( $atts['type'] );
		$orderby           = sanitize_key( $atts['orderby'] );
		$order             = strtoupper( sanitize_key( $atts['order'] ) ) === 'DESC' ? 'DESC' : 'ASC';
		$limit             = absint( $atts['limit'] );
		$class             = sanitize_html_class( $atts['class'] );
		$show_emoji        = filter_var( $atts['show_emoji'], FILTER_VALIDATE_BOOLEAN );

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
			return $this->render_location_specific_list( $location_id, $orderby, $order, $css_classes, $show_emoji );
		}

		// Handle all locations mode.
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
