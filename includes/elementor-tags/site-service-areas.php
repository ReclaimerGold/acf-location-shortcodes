<?php
/**
 * Elementor Dynamic Tag: Site Service Areas
 *
 * @package ACF_Location_Shortcodes
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site Service Areas Tag.
 *
 * @since 2.3.0
 */
class ACF_SMS_Site_Service_Areas_Tag extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Site settings instance.
	 *
	 * @var ACF_Location_Shortcodes_Site_Settings
	 */
	private $site_settings;

	/**
	 * Constructor.
	 *
	 * @since 2.3.0
	 * @param ACF_Location_Shortcodes_Site_Settings $site_settings Site settings instance.
	 * @param array                                  $data           Tag data.
	 */
	public function __construct( $site_settings, $data = array() ) {
		$this->site_settings = $site_settings;
		parent::__construct( $data );
	}

	/**
	 * Get tag name.
	 *
	 * @since 2.3.0
	 * @return string Tag name.
	 */
	public function get_name() {
		return 'acf-sms-site-service-areas';
	}

	/**
	 * Get tag title.
	 *
	 * @since 2.3.0
	 * @return string Tag title.
	 */
	public function get_title() {
		return __( 'Site Location: Service Areas', 'acf-sms' );
	}

	/**
	 * Get tag group.
	 *
	 * @since 2.3.0
	 * @return string Tag group.
	 */
	public function get_group() {
		return 'acf-sms-site-location';
	}

	/**
	 * Get tag categories.
	 *
	 * @since 2.3.0
	 * @return array Tag categories.
	 */
	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	/**
	 * Register controls.
	 *
	 * @since 2.3.0
	 */
	protected function register_controls() {
		$this->add_control(
			'format',
			array(
				'label'   => __( 'Format', 'acf-sms' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'comma_and',
				'options' => array(
					'comma_and'             => __( 'Comma with "and" (e.g., Area 1, Area 2, and Area 3)', 'acf-sms' ),
					'comma'                 => __( 'Comma only (e.g., Area 1, Area 2, Area 3)', 'acf-sms' ),
					'comma_and_surrounding' => __( 'Comma with "and surrounding locations"', 'acf-sms' ),
					'list'                  => __( 'Bulleted List (HTML <ul> list)', 'acf-sms' ),
				),
			)
		);
	}

	/**
	 * Render tag.
	 *
	 * @since 2.3.0
	 */
	public function render() {
		$settings = $this->get_settings();
		$format   = isset( $settings['format'] ) ? $settings['format'] : 'comma_and';
		
		// For list format, output raw HTML (already escaped).
		if ( $format === 'list' ) {
			echo $this->site_settings->get_service_areas( $format );
		} else {
			echo esc_html( $this->site_settings->get_service_areas( $format ) );
		}
	}
}
