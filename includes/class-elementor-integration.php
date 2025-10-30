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
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param ACF_Location_Shortcodes_ACF_Helpers $acf_helpers ACF helpers instance.
	 */
	public function __construct( $acf_helpers ) {
		$this->acf_helpers = $acf_helpers;
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

		// Enqueue editor scripts.
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
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
