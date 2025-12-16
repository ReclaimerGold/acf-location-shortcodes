<?php
/**
 * Site Settings
 *
 * Manages site-specific location association. All location data is pulled from
 * the associated Location post's ACF fields rather than being stored directly.
 *
 * @package ACF_Location_Shortcodes
 * @since 2.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site Settings class.
 *
 * @since 2.3.0
 */
class ACF_Location_Shortcodes_Site_Settings {

	/**
	 * Option key for site settings.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'acf_sms_site_location_settings';

	/**
	 * Constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.3.0
	 */
	private function init_hooks() {
		// Add settings page to admin menu.
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page to admin menu.
	 *
	 * @since 2.3.0
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Site Location Settings', 'acf-sms' ),
			__( 'Site Location', 'acf-sms' ),
			'manage_options',
			'acf-sms-site-location',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @since 2.3.0
	 */
	public function register_settings() {
		register_setting(
			'acf_sms_site_location_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_default_settings(),
			)
		);

		add_settings_section(
			'acf_sms_site_location_section',
			__( 'Associated Location', 'acf-sms' ),
			array( $this, 'render_section_description' ),
			'acf-sms-site-location'
		);

		add_settings_field(
			'location_id',
			__( 'Select Location', 'acf-sms' ),
			array( $this, 'render_location_id_field' ),
			'acf-sms-site-location',
			'acf_sms_site_location_section'
		);
	}

	/**
	 * Get default settings.
	 *
	 * @since 2.3.0
	 * @return array Default settings.
	 */
	private function get_default_settings() {
		return array(
			'location_id' => 0,
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @since 2.3.0
	 * @param array $input Settings input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		if ( isset( $input['location_id'] ) ) {
			$sanitized['location_id'] = absint( $input['location_id'] );
			
			// Validate that the location exists.
			if ( $sanitized['location_id'] > 0 && get_post_type( $sanitized['location_id'] ) !== 'location' ) {
				add_settings_error(
					self::OPTION_KEY,
					'invalid_location',
					__( 'Invalid location selected.', 'acf-sms' ),
					'error'
				);
				$sanitized['location_id'] = 0;
			}
		}

		return $sanitized;
	}

	/**
	 * Render settings page.
	 *
	 * @since 2.3.0
	 */
	public function render_settings_page() {
		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'acf-sms' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<p class="description">
				<?php esc_html_e( 'Associate this site with a Location post. All location data (city, state, county, service areas, etc.) will be pulled from that Location\'s ACF fields.', 'acf-sms' ); ?>
			</p>

			<?php settings_errors( self::OPTION_KEY ); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'acf_sms_site_location_settings_group' );
				do_settings_sections( 'acf-sms-site-location' );
				submit_button();
				?>
			</form>

			<div class="card" style="max-width: 800px; margin-top: 30px;">
				<h2 class="title"><?php esc_html_e( 'Available ACF Fields', 'acf-sms' ); ?></h2>
				<p><?php esc_html_e( 'The following ACF fields from your selected Location post are available via shortcodes:', 'acf-sms' ); ?></p>
				
				<table class="widefat" style="margin-top: 15px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Shortcode', 'acf-sms' ); ?></th>
							<th><?php esc_html_e( 'ACF Field Used', 'acf-sms' ); ?></th>
							<th><?php esc_html_e( 'Current Value', 'acf-sms' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>[location_info field="service_area_shorthand"]</code></td>
							<td><code>service_area_shorthand</code></td>
							<td>
								<?php
								$location_id = $this->get_setting( 'location_id', 0 );
								if ( $location_id && function_exists( 'get_field' ) ) {
									$value = get_field( 'service_area_shorthand', $location_id );
									echo $value ? esc_html( $value ) : '<em>' . esc_html__( '(not set)', 'acf-sms' ) . '</em>';
								} else {
									echo '<em>' . esc_html__( '(no location selected)', 'acf-sms' ) . '</em>';
								}
								?>
							</td>
						</tr>
						<tr>
							<td><code>[location_info field="full_service_area_name"]</code></td>
							<td><code>full_service_area_name</code></td>
							<td>
								<?php
								if ( $location_id && function_exists( 'get_field' ) ) {
									$value = get_field( 'full_service_area_name', $location_id );
									echo $value ? esc_html( $value ) : '<em>' . esc_html__( '(not set)', 'acf-sms' ) . '</em>';
								} else {
									echo '<em>' . esc_html__( '(no location selected)', 'acf-sms' ) . '</em>';
								}
								?>
							</td>
						</tr>
						<tr>
							<td><code>[location_info field="metro_area_name"]</code></td>
							<td><code>metro_area_name</code></td>
							<td>
								<?php
								if ( $location_id && function_exists( 'get_field' ) ) {
									$value = get_field( 'metro_area_name', $location_id );
									echo $value ? esc_html( $value ) : '<em>' . esc_html__( '(not set)', 'acf-sms' ) . '</em>';
								} else {
									echo '<em>' . esc_html__( '(no location selected)', 'acf-sms' ) . '</em>';
								}
								?>
							</td>
						</tr>
						<tr>
							<td><code>[location_info field="county_name"]</code></td>
							<td><code>county_name</code></td>
							<td>
								<?php
								if ( $location_id && function_exists( 'get_field' ) ) {
									$value = get_field( 'county_name', $location_id );
									echo $value ? esc_html( $value ) : '<em>' . esc_html__( '(not set)', 'acf-sms' ) . '</em>';
								} else {
									echo '<em>' . esc_html__( '(no location selected)', 'acf-sms' ) . '</em>';
								}
								?>
							</td>
						</tr>
						<tr>
							<td><code>[site_service_areas]</code></td>
							<td><em><?php esc_html_e( 'Retrieves child service areas', 'acf-sms' ); ?></em></td>
							<td>
								<?php
								$service_areas = $this->get_service_areas( 'comma_and' );
								echo ! empty( $service_areas ) ? esc_html( $service_areas ) : '<em>' . esc_html__( '(no service areas)', 'acf-sms' ) . '</em>';
								?>
							</td>
						</tr>
					</tbody>
				</table>

				<h3 style="margin-top: 20px;"><?php esc_html_e( 'Usage Notes', 'acf-sms' ); ?></h3>
				<ul style="list-style: disc; margin-left: 20px;">
					<li><?php esc_html_e( 'Use [location_info field="FIELD_NAME"] to display any ACF field from the selected Location post', 'acf-sms' ); ?></li>
					<li><?php esc_html_e( 'Available fields: service_area_shorthand, full_service_area_name, metro_area_name, county_name, located_near, phone_number, address', 'acf-sms' ); ?></li>
					<li><?php esc_html_e( 'Service areas are automatically pulled from child Location posts', 'acf-sms' ); ?></li>
				</ul>

				<h3 style="margin-top: 20px;"><?php esc_html_e( 'Elementor Integration', 'acf-sms' ); ?></h3>
				<p><?php esc_html_e( 'When using Elementor, location data is also available as dynamic tags in the "Site Location" group.', 'acf-sms' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render section description.
	 *
	 * @since 2.3.0
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Select the Location post that represents this site. All ACF fields from that location will be available via shortcodes and Elementor dynamic tags.', 'acf-sms' ) . '</p>';
		echo '<p>' . esc_html__( 'Make sure your Location posts have the necessary ACF fields populated (service_area_shorthand, metro_area_name, county_name, etc.)', 'acf-sms' ) . '</p>';
	}

	/**
	 * Render location ID field.
	 *
	 * @since 2.3.0
	 */
	public function render_location_id_field() {
		$settings    = $this->get_settings();
		$value       = isset( $settings['location_id'] ) ? absint( $settings['location_id'] ) : 0;
		
		// Get all locations.
		$locations = get_posts(
			array(
				'post_type'      => 'location',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<select id="location_id" 
				name="<?php echo esc_attr( self::OPTION_KEY ); ?>[location_id]" 
				class="regular-text">
			<option value="0"><?php esc_html_e( '-- Select Location --', 'acf-sms' ); ?></option>
			<?php foreach ( $locations as $location ) : ?>
				<option value="<?php echo esc_attr( $location->ID ); ?>" <?php selected( $value, $location->ID ); ?>>
					<?php echo esc_html( $location->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the Location post this site represents. All ACF field data will be pulled from this location.', 'acf-sms' ); ?>
		</p>
		<?php
		
		// Show edit link if location is selected.
		if ( $value > 0 && get_post_type( $value ) === 'location' ) {
			$edit_url = get_edit_post_link( $value );
			if ( $edit_url ) {
				echo '<p><a href="' . esc_url( $edit_url ) . '" target="_blank" class="button button-secondary">' . esc_html__( 'Edit Location Post', 'acf-sms' ) . '</a></p>';
			}
		}
	}

	/**
	 * Get site location settings.
	 *
	 * @since 2.3.0
	 * @return array Site location settings.
	 */
	public function get_settings() {
		$settings = get_option( self::OPTION_KEY, $this->get_default_settings() );
		return wp_parse_args( $settings, $this->get_default_settings() );
	}

	/**
	 * Get a specific setting value.
	 *
	 * @since 2.3.0
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value if not set.
	 * @return mixed Setting value or default.
	 */
	public function get_setting( $key, $default = '' ) {
		$settings = $this->get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Get the associated location ID.
	 *
	 * @since 2.3.0
	 * @return int Location post ID or 0 if not set.
	 */
	public function get_location_id() {
		return absint( $this->get_setting( 'location_id', 0 ) );
	}

	/**
	 * Get ACF field value from the associated location.
	 *
	 * @since 2.3.0
	 * @param string $field_name ACF field name.
	 * @param mixed  $default    Default value if field is empty.
	 * @return mixed ACF field value or default.
	 */
	public function get_location_field( $field_name, $default = '' ) {
		$location_id = $this->get_location_id();

		if ( ! $location_id || get_post_type( $location_id ) !== 'location' ) {
			return $default;
		}

		if ( ! function_exists( 'get_field' ) ) {
			return $default;
		}

		$value = get_field( $field_name, $location_id );
		return ! empty( $value ) ? $value : $default;
	}

	/**
	 * Get formatted city and state.
	 *
	 * @since 2.3.0
	 * @param string $format Format: 'full' (City, State) or 'abbrev' (City, ST). Default 'abbrev'.
	 * @return string Formatted location string or empty string if not set.
	 */
	public function get_formatted_location( $format = 'abbrev' ) {
		// This method is kept for backward compatibility but now uses ACF fields.
		// Note: You need to add 'location_city', 'location_state', and 'location_state_abbrev' 
		// ACF fields to your Location post type.
		
		$city  = $this->get_location_field( 'location_city', '' );
		$state = $format === 'full' 
			? $this->get_location_field( 'location_state', '' )
			: $this->get_location_field( 'location_state_abbrev', '' );

		if ( ! empty( $city ) && ! empty( $state ) ) {
			return $city . ', ' . $state;
		} elseif ( ! empty( $city ) ) {
			return $city;
		} elseif ( ! empty( $state ) ) {
			return $state;
		}

		return '';
	}

	/**
	 * Get service areas for the associated location.
	 *
	 * @since 2.3.0
	 * @param string $format Format: 'array', 'comma', 'comma_and', 'comma_and_surrounding', or 'list'. Default 'comma_and'.
	 * @return string|array Service areas formatted as requested.
	 */
	public function get_service_areas( $format = 'comma_and' ) {
		$settings    = $this->get_settings();
		$location_id = isset( $settings['location_id'] ) ? absint( $settings['location_id'] ) : 0;

		if ( ! $location_id || get_post_type( $location_id ) !== 'location' ) {
			return $format === 'array' ? array() : '';
		}

		// Check if ACF is available.
		if ( ! function_exists( 'get_field' ) ) {
			return $format === 'array' ? array() : '';
		}

		// Get the ACF helpers to determine if this is a physical location.
		$plugin = ACF_Location_Shortcodes::instance();
		if ( ! $plugin || ! isset( $plugin->acf_helpers ) ) {
			return $format === 'array' ? array() : '';
		}

		$acf_helpers = $plugin->acf_helpers;
		$service_areas = array();

		// Check if this is a physical location or service area.
		if ( $acf_helpers->is_physical_location( $location_id ) ) {
			// Physical location - get its service areas.
			$service_area_posts = $acf_helpers->get_service_areas_by_physical_location( $location_id );
			
			foreach ( $service_area_posts as $post ) {
				$service_areas[] = $post->post_title;
			}
		} else {
			// Service area - get parent's service areas.
			$parent_location = $acf_helpers->get_servicing_location( $location_id );
			
			if ( $parent_location ) {
				$service_area_posts = $acf_helpers->get_service_areas_by_physical_location( $parent_location->ID );
				
				foreach ( $service_area_posts as $post ) {
					$service_areas[] = $post->post_title;
				}
			}
		}

		// Return based on format.
		if ( $format === 'array' ) {
			return $service_areas;
		}

		if ( empty( $service_areas ) ) {
			return '';
		}

		// Format as bulleted list.
		if ( $format === 'list' ) {
			$output = '<ul class="acf-sms-service-areas-list">';
			foreach ( $service_areas as $area ) {
				$output .= '<li>' . esc_html( $area ) . '</li>';
			}
			$output .= '</ul>';
			return $output;
		}

		// Format as comma-separated list.
		$count = count( $service_areas );
		
		if ( $count === 1 ) {
			return $service_areas[0];
		}

		if ( $format === 'comma' ) {
			return implode( ', ', $service_areas );
		}

		// 'comma_and_surrounding' format - "Area 1, Area 2, and surrounding locations"
		if ( $format === 'comma_and_surrounding' ) {
			if ( $count === 1 ) {
				return $service_areas[0] . ' and surrounding locations';
			}
			
			if ( $count === 2 ) {
				return $service_areas[0] . ', ' . $service_areas[1] . ', and surrounding locations';
			}
			
			return implode( ', ', $service_areas ) . ', and surrounding locations';
		}

		// Default: 'comma_and' format - "Area 1, Area 2, and Area 3"
		if ( $count === 2 ) {
			return $service_areas[0] . ' and ' . $service_areas[1];
		}

		$last_area = array_pop( $service_areas );
		return implode( ', ', $service_areas ) . ', and ' . $last_area;
	}
}
