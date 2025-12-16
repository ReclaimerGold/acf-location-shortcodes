<?php
/**
 * Elementor Integration
 *
 * Provides Elementor query filters and controls for location-based filtering.
 *
 * @package ACF_Location_Shortcodes
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor integration class.
 *
 * @since 1.0.0
 */
class ACF_Location_Shortcodes_Elementor {

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
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Add controls to query section of post widgets.
		add_action( 'elementor/element/posts/section_query/before_section_end', array( $this, 'add_location_controls' ), 10, 2 );
		add_action( 'elementor/element/archive-posts/section_query/before_section_end', array( $this, 'add_location_controls' ), 10, 2 );
		
		// Add controls to loop grid (Elementor Pro).
		add_action( 'elementor/element/loop-grid/section_query/before_section_end', array( $this, 'add_location_controls' ), 10, 2 );
		add_action( 'elementor/element/loop-carousel/section_query/before_section_end', array( $this, 'add_location_controls' ), 10, 2 );

		// Filter the query.
		add_filter( 'elementor/query/query_args', array( $this, 'filter_query_by_location' ), 10, 2 );

		// Register dynamic tags.
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_dynamic_tags' ) );

		// Enqueue editor scripts.
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );

		// Note: Elementor Display Conditions fix is now handled in the main plugin class
		// to ensure it runs even if this integration class isn't loaded.
	}

	/**
	 * Add location filter controls to Elementor widgets.
	 *
	 * @since 1.0.0
	 * @param \Elementor\Widget_Base $element The widget instance.
	 * @param array                  $args    Additional arguments.
	 */
	public function add_location_controls( $element, $args ) {
		// Add toggle control.
		$element->add_control(
			'acf_ls_filter_by_location',
			array(
				'label'        => __( 'Filter by Service Location', 'acf-location-shortcodes' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'acf-location-shortcodes' ),
				'label_off'    => __( 'No', 'acf-location-shortcodes' ),
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
				'description'  => __( 'Automatically filter team members by the current location page. If on a service area (child location), shows team members from the parent physical location.', 'acf-location-shortcodes' ),
			)
		);

		// Add field name control for custom post types.
		$element->add_control(
			'acf_ls_relationship_field',
			array(
				'label'       => __( 'Location Relationship Field Name', 'acf-location-shortcodes' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'location',
				'description' => __( 'The ACF relationship field name that connects posts to locations.', 'acf-location-shortcodes' ),
				'condition'   => array(
					'acf_ls_filter_by_location' => 'yes',
				),
			)
		);
	}

	/**
	 * Filter Elementor query by location.
	 *
	 * Automatically filters team members based on the current location page.
	 * If on a service area (child location), uses the parent physical location.
	 *
	 * @since 1.0.0
	 * @param array                  $query_args The query arguments.
	 * @param \Elementor\Widget_Base $widget     The widget instance.
	 * @return array Modified query arguments.
	 */
	public function filter_query_by_location( $query_args, $widget ) {
		$settings = $widget->get_settings();

		// Check if location filtering is enabled.
		if ( empty( $settings['acf_ls_filter_by_location'] ) || 'yes' !== $settings['acf_ls_filter_by_location'] ) {
			return $query_args;
		}

		// Only apply filter on location pages.
		if ( ! is_singular( 'location' ) ) {
			ACF_Location_Shortcodes::log(
				'Elementor location filter enabled but not on a location page',
				array( 'widget' => $widget->get_name() ),
				'info'
			);
			return $query_args;
		}

		$current_location_id = get_the_ID();
		$location_id_to_use  = $current_location_id;

		// Check if current location is a service area (has a parent).
		if ( ! $this->acf_helpers->is_physical_location( $current_location_id ) ) {
			// Get the parent physical location.
			$parent_location = $this->acf_helpers->get_servicing_location( $current_location_id );
			
			if ( $parent_location ) {
				$location_id_to_use = $parent_location->ID;
				
				ACF_Location_Shortcodes::log(
					'Service area detected, using parent physical location',
					array(
						'service_area_id'        => $current_location_id,
						'service_area_title'     => get_the_title( $current_location_id ),
						'physical_location_id'   => $parent_location->ID,
						'physical_location_title' => $parent_location->post_title,
					),
					'info'
				);
			} else {
				ACF_Location_Shortcodes::log(
					'Service area has no parent physical location',
					array(
						'service_area_id'    => $current_location_id,
						'service_area_title' => get_the_title( $current_location_id ),
					),
					'warning'
				);
				return $query_args;
			}
		} else {
			ACF_Location_Shortcodes::log(
				'Physical location detected, filtering by this location',
				array(
					'location_id'    => $current_location_id,
					'location_title' => get_the_title( $current_location_id ),
				),
				'info'
			);
		}

		// Get field name from settings.
		$relationship_field = ! empty( $settings['acf_ls_relationship_field'] ) ? $settings['acf_ls_relationship_field'] : 'location';

		ACF_Location_Shortcodes::log(
			'Applying Elementor location filter',
			array(
				'widget'             => $widget->get_name(),
				'location_id'        => $location_id_to_use,
				'relationship_field' => $relationship_field,
				'post_type'          => isset( $query_args['post_type'] ) ? $query_args['post_type'] : 'not set',
			),
			'info'
		);

		// Initialize meta_query if not set.
		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array();
		}

		// Build the meta query for the single location.
		$meta_query_clause = array(
			'key'     => $relationship_field,
			'value'   => '"' . $location_id_to_use . '"',
			'compare' => 'LIKE',
		);
		
		ACF_Location_Shortcodes::log(
			'Building location meta query',
			array(
				'location_id'       => $location_id_to_use,
				'meta_query_clause' => $meta_query_clause,
			),
			'info'
		);
		
		$query_args['meta_query'][] = $meta_query_clause;

		// Set meta_query relation to AND if there are multiple meta queries.
		if ( count( $query_args['meta_query'] ) > 1 && ! isset( $query_args['meta_query']['relation'] ) ) {
			$query_args['meta_query']['relation'] = 'AND';
		}

		ACF_Location_Shortcodes::log(
			'Final query arguments with location filter',
			array(
				'post_type'  => isset( $query_args['post_type'] ) ? $query_args['post_type'] : 'not set',
				'meta_query' => $query_args['meta_query'],
			),
			'info'
		);

		return $query_args;
	}

	/**
	 * Register dynamic tags.
	 *
	 * @since 2.3.0
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
	 */
	public function register_dynamic_tags( $dynamic_tags_manager ) {
		// Only register if site settings are available.
		if ( ! $this->site_settings ) {
			return;
		}

		// Register our custom group.
		$dynamic_tags_manager->register_group(
			'acf-sms-site-location',
			array(
				'title' => __( 'Site Location', 'acf-sms' ),
			)
		);

		// Register individual tags.
		require_once ACF_LS_PLUGIN_DIR . 'includes/elementor-tags/site-location-title.php';
		require_once ACF_LS_PLUGIN_DIR . 'includes/elementor-tags/site-location-city.php';
		require_once ACF_LS_PLUGIN_DIR . 'includes/elementor-tags/site-location-state.php';
		require_once ACF_LS_PLUGIN_DIR . 'includes/elementor-tags/site-location-state-abbrev.php';
		require_once ACF_LS_PLUGIN_DIR . 'includes/elementor-tags/site-location-city-state.php';
		require_once ACF_LS_PLUGIN_DIR . 'includes/elementor-tags/site-service-areas.php';

		$dynamic_tags_manager->register( new \ACF_SMS_Site_Location_Title_Tag( $this->site_settings ) );
		$dynamic_tags_manager->register( new \ACF_SMS_Site_Location_City_Tag( $this->site_settings ) );
		$dynamic_tags_manager->register( new \ACF_SMS_Site_Location_State_Tag( $this->site_settings ) );
		$dynamic_tags_manager->register( new \ACF_SMS_Site_Location_State_Abbrev_Tag( $this->site_settings ) );
		$dynamic_tags_manager->register( new \ACF_SMS_Site_Location_City_State_Tag( $this->site_settings ) );
		$dynamic_tags_manager->register( new \ACF_SMS_Site_Service_Areas_Tag( $this->site_settings ) );
	}


	/**
	 * Convert ACF relationship/post_object arrays to strings for Elementor compatibility.
	 *
	 * Elementor's Display Conditions expects strings, but ACF relationship fields
	 * return arrays. This filter converts arrays to comma-separated strings when
	 * Elementor Pro is active and we're on the frontend.
	 *
	 * @since 2.3.0
	 * @param mixed  $value   The field value.
	 * @param int    $post_id The post ID.
	 * @param array  $field   The field array.
	 * @return mixed The converted value (string if in Elementor context, original otherwise).
	 */
	public function convert_acf_arrays_to_strings_for_elementor( $value, $post_id, $field ) {
		// Only process if Elementor Pro is active.
		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return $value;
		}

		// Only process on frontend (not admin).
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $value;
		}

		// Get field type.
		$field_type = isset( $field['type'] ) ? $field['type'] : '';

		// Only process relationship and post_object fields.
		if ( ! in_array( $field_type, array( 'relationship', 'post_object' ), true ) ) {
			return $value;
		}

		// Handle empty values.
		if ( empty( $value ) || $value === false || $value === null ) {
			return '';
		}

		// Handle arrays (multiple selections).
		if ( is_array( $value ) ) {
			$titles = array();
			foreach ( $value as $item ) {
				$title = $this->extract_post_title( $item );
				if ( ! empty( $title ) ) {
					$titles[] = $title;
				}
			}
			return ! empty( $titles ) ? implode( ', ', $titles ) : '';
		}

		// Handle single post object.
		if ( is_object( $value ) ) {
			$title = $this->extract_post_title( $value );
			return ! empty( $title ) ? $title : '';
		}

		// Return original value if it's already a string.
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Extract post title from various formats.
	 *
	 * @since 2.3.0
	 * @param mixed $item The item (post object, array, or ID).
	 * @return string The post title or empty string.
	 */
	private function extract_post_title( $item ) {
		// Handle post object.
		if ( is_object( $item ) && isset( $item->post_title ) ) {
			return (string) $item->post_title;
		}

		// Handle array format.
		if ( is_array( $item ) && isset( $item['post_title'] ) ) {
			return (string) $item['post_title'];
		}

		// Handle ID.
		if ( is_numeric( $item ) ) {
			$post = get_post( absint( $item ) );
			if ( $post && isset( $post->post_title ) ) {
				return (string) $post->post_title;
			}
		}

		return '';
	}

	/**
	 * Enqueue editor scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_editor_scripts() {
		wp_enqueue_script(
			'acf-ls-elementor-controls',
			ACF_LS_PLUGIN_URL . 'assets/js/elementor-controls.js',
			array( 'jquery', 'elementor-editor' ),
			ACF_LS_VERSION,
			true
		);

		// Pass data to JavaScript.
		wp_localize_script(
			'acf-ls-elementor-controls',
			'acfLsElementor',
			array(
				'i18n' => array(
					'noLocations' => __( 'No locations found. Please create some location posts first.', 'acf-location-shortcodes' ),
				),
			)
		);
	}
}
