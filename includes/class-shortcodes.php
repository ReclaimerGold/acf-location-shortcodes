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
	private function register_shortcodes() {
		add_shortcode( 'location_communities', array( $this, 'render_communities_list' ) );
		add_shortcode( 'location_info', array( $this, 'render_location_info' ) );
		add_shortcode( 'location_list', array( $this, 'render_location_list' ) );
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
	 * Displays physical locations and their child service areas.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_location_list( $atts ) {
		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'orderby'    => 'title',
				'order'      => 'ASC',
				'limit'      => 0,
				'class'      => '',
				'show_emoji' => 'yes',
			),
			$atts,
			'location_list'
		);

		// Sanitize attributes.
		$orderby     = sanitize_key( $atts['orderby'] );
		$order       = strtoupper( sanitize_key( $atts['order'] ) ) === 'DESC' ? 'DESC' : 'ASC';
		$limit       = absint( $atts['limit'] );
		$class       = sanitize_html_class( $atts['class'] );
		$show_emoji  = $atts['show_emoji'] === 'yes';

		// Get physical locations.
		$query_args = array(
			'orderby' => $orderby,
			'order'   => $order,
		);

		$physical_locations = $this->acf_helpers->get_physical_locations();

		// Apply ordering.
		if ( 'title' === $orderby ) {
			usort( $physical_locations, function( $a, $b ) use ( $order ) {
				$result = strcmp( $a->post_title, $b->post_title );
				return $order === 'DESC' ? -$result : $result;
			});
		}

		if ( $limit > 0 ) {
			$physical_locations = array_slice( $physical_locations, 0, $limit );
		}

		// Handle empty data.
		if ( empty( $physical_locations ) ) {
			return $this->render_error( __( 'No locations found.', 'acf-location-shortcodes' ) );
		}

		// Build CSS classes.
		$css_classes = array( 'acf-ls-locations' );
		if ( ! empty( $class ) ) {
			$css_classes[] = $class;
		}
		if ( $show_emoji ) {
			$css_classes[] = 'acf-ls-locations--with-emoji';
		}

		// Build HTML output.
		ob_start();
		?>
		<ul class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>">
			<?php foreach ( $physical_locations as $location ) : ?>
				<?php
				$service_areas = $this->acf_helpers->get_service_areas_by_physical_location( $location->ID );
				?>
				<li class="acf-ls-locations__item acf-ls-locations__item--physical">
					<?php if ( $show_emoji ) : ?>
						<span class="acf-ls-locations__emoji" aria-hidden="true">📍</span>
					<?php endif; ?>
					<span class="acf-ls-locations__text">
						<?php echo esc_html( $location->post_title ); ?>
					</span>
				</li>
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
			<?php endforeach; ?>
		</ul>
		<?php
		return ob_get_clean();
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
