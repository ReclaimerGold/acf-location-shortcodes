<?php
/**
 * Elementor Dynamic Tag: Site Location City, State
 *
 * @package ACF_Location_Shortcodes
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site Location City, State Tag.
 *
 * @since 2.3.0
 */
class ACF_SMS_Site_Location_City_State_Tag extends \Elementor\Core\DynamicTags\Tag {

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
		return 'acf-sms-site-location-city-state';
	}

	/**
	 * Get tag title.
	 *
	 * @since 2.3.0
	 * @return string Tag title.
	 */
	public function get_title() {
		return __( 'Site Location: City, State', 'acf-sms' );
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
				'default' => 'abbrev',
				'options' => array(
					'abbrev' => __( 'City, ST (e.g., Los Angeles, CA)', 'acf-sms' ),
					'full'   => __( 'City, State (e.g., Los Angeles, California)', 'acf-sms' ),
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
		$format   = isset( $settings['format'] ) ? $settings['format'] : 'abbrev';
		
		echo esc_html( $this->site_settings->get_formatted_location( $format ) );
	}
}
