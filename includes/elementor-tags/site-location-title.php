<?php
/**
 * Elementor Dynamic Tag: Site Location Title
 *
 * @package ACF_Location_Shortcodes
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site Location Title Tag.
 *
 * @since 2.3.0
 */
class ACF_SMS_Site_Location_Title_Tag extends \Elementor\Core\DynamicTags\Tag {

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
		return 'acf-sms-site-location-title';
	}

	/**
	 * Get tag title.
	 *
	 * @since 2.3.0
	 * @return string Tag title.
	 */
	public function get_title() {
		return __( 'Site Location: Title', 'acf-sms' );
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
	 * Render tag.
	 *
	 * Displays the post title from the associated Location post.
	 *
	 * @since 2.3.0
	 */
	public function render() {
		$location_id = $this->site_settings->get_location_id();
		
		if ( ! $location_id || get_post_type( $location_id ) !== 'location' ) {
			return;
		}

		echo esc_html( get_the_title( $location_id ) );
	}
}
