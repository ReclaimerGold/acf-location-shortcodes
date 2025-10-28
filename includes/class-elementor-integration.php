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
		// Get all locations for the dropdown.
		$locations = $this->get_locations_for_control();

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
				'description'  => __( 'Filter posts by assigned service location.', 'acf-location-shortcodes' ),
			)
		);

		// Add location selection control.
		$element->add_control(
			'acf_ls_location_ids',
			array(
				'label'       => __( 'Select Locations', 'acf-location-shortcodes' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $locations,
				'multiple'    => true,
				'label_block' => true,
				'condition'   => array(
					'acf_ls_filter_by_location' => 'yes',
				),
				'description' => __( 'Select one or more locations to filter by. Leave empty to use current location.', 'acf-location-shortcodes' ),
			)
		);

		// Add filter mode control.
		$element->add_control(
			'acf_ls_filter_mode',
			array(
				'label'     => __( 'Filter Mode', 'acf-location-shortcodes' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'any' => __( 'Match Any Location (OR)', 'acf-location-shortcodes' ),
					'all' => __( 'Match All Locations (AND)', 'acf-location-shortcodes' ),
				),
				'default'   => 'any',
				'condition' => array(
					'acf_ls_filter_by_location' => 'yes',
					'acf_ls_location_ids!'      => '',
				),
			)
		);

		// Add field name control for custom post types.
		$element->add_control(
			'acf_ls_relationship_field',
			array(
				'label'       => __( 'Location Relationship Field Name', 'acf-location-shortcodes' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'assigned_location',
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

		// Get location IDs.
		$location_ids = ! empty( $settings['acf_ls_location_ids'] ) ? $settings['acf_ls_location_ids'] : array();

		// If no locations specified, try to use current post as location.
		if ( empty( $location_ids ) && is_singular( 'location' ) ) {
			$location_ids = array( get_the_ID() );
		}

		// If still no location IDs, return original query.
		if ( empty( $location_ids ) ) {
			ACF_Location_Shortcodes::log(
				'Elementor filter enabled but no location IDs found',
				array(
					'widget'   => $widget->get_name(),
					'settings' => $settings,
				),
				'warning'
			);
			return $query_args;
		}

		// Get filter mode and field name.
		$filter_mode        = ! empty( $settings['acf_ls_filter_mode'] ) ? $settings['acf_ls_filter_mode'] : 'any';
		$relationship_field = ! empty( $settings['acf_ls_relationship_field'] ) ? $settings['acf_ls_relationship_field'] : 'assigned_location';

		// Sanitize location IDs.
		$location_ids = array_map( 'absint', (array) $location_ids );
		$location_ids = array_filter( $location_ids );

		if ( empty( $location_ids ) ) {
			ACF_Location_Shortcodes::log(
				'No valid location IDs after sanitization',
				array( 'widget' => $widget->get_name() ),
				'warning'
			);
			return $query_args;
		}

		ACF_Location_Shortcodes::log(
			'Applying Elementor location filter',
			array(
				'widget'             => $widget->get_name(),
				'location_ids'       => $location_ids,
				'filter_mode'        => $filter_mode,
				'relationship_field' => $relationship_field,
			),
			'info'
		);

		// Initialize meta_query if not set.
		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array();
		}

		// Build the meta query.
		if ( count( $location_ids ) === 1 ) {
			// Single location - simple query.
			$query_args['meta_query'][] = array(
				'key'     => $relationship_field,
				'value'   => '"' . $location_ids[0] . '"',
				'compare' => 'LIKE',
			);
		} else {
			// Multiple locations.
			$location_queries = array();

			foreach ( $location_ids as $location_id ) {
				$location_queries[] = array(
					'key'     => $relationship_field,
					'value'   => '"' . $location_id . '"',
					'compare' => 'LIKE',
				);
			}

			// Add relation based on filter mode.
			if ( 'all' === $filter_mode ) {
				$location_queries['relation'] = 'AND';
			} else {
				$location_queries['relation'] = 'OR';
			}

			$query_args['meta_query'][] = $location_queries;
		}

		// Set meta_query relation to AND if there are multiple meta queries.
		if ( count( $query_args['meta_query'] ) > 1 && ! isset( $query_args['meta_query']['relation'] ) ) {
			$query_args['meta_query']['relation'] = 'AND';
		}

		return $query_args;
	}

	/**
	 * Get locations formatted for Elementor control.
	 *
	 * @since 1.0.0
	 * @return array Location options array.
	 */
	private function get_locations_for_control() {
		$locations = $this->acf_helpers->get_all_locations();
		$options   = array();

		foreach ( $locations as $location ) {
			$label = $location->post_title;

			// Add indicator for physical vs service area.
			if ( $this->acf_helpers->is_physical_location( $location->ID ) ) {
				$label .= ' ' . __( '(Physical Location)', 'acf-location-shortcodes' );
			} else {
				$label .= ' ' . __( '(Service Area)', 'acf-location-shortcodes' );
			}

			$options[ $location->ID ] = $label;
		}

		return $options;
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
