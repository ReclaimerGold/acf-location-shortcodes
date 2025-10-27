/**
 * ACF Location Shortcodes - Elementor Editor Controls
 *
 * JavaScript for enhanced Elementor editor experience.
 *
 * @package ACF_Location_Shortcodes
 * @since 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * Initialize when Elementor editor is ready.
	 */
	$(window).on('elementor:init', function() {
		
		/**
		 * Add custom validation or interactions if needed.
		 */
		elementor.hooks.addAction('panel/open_editor/widget', function(panel, model, view) {
			// You can add custom logic here when a widget panel is opened.
			// For example, show helpful tooltips or validate location selections.
		});

		/**
		 * Handle location control changes.
		 */
		elementor.channels.editor.on('change', function(controlView) {
			// Check if this is our location filter control.
			if (controlView.model && controlView.model.get('name') === 'acf_ls_filter_by_location') {
				const isEnabled = controlView.getControlValue();
				
				// You can add custom behavior when the filter is toggled.
				if (isEnabled === 'yes') {
					// Optionally show a notification or help text.
					console.log('ACF Location filter enabled');
				}
			}
		});
	});

	/**
	 * Add preview refresh when location settings change.
	 */
	$(window).on('elementor/frontend/init', function() {
		// This runs in the preview frame.
		// Add any preview-specific JavaScript here if needed.
	});

})(jQuery);
