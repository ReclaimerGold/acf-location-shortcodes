/**
 * Admin JavaScript
 *
 * @package ACF_Location_Shortcodes
 * @since 2.2.0
 */

(function($) {
	'use strict';

	/**
	 * Initialize admin functionality.
	 */
	function init() {
		// Handle notice dismissal.
		$('.acf-sms-dismiss-notice').on('click', function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var $notice = $button.closest('.acf-sms-notice');
			var nonce = $button.data('nonce');
			
			// Send AJAX request to dismiss notice.
			$.ajax({
				url: acfSmsAdmin.ajaxurl,
				type: 'POST',
				data: {
					action: 'acf_sms_dismiss_notice',
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						$notice.fadeOut(300, function() {
							$(this).remove();
						});
					}
				}
			});
		});

		// Handle download button click.
		$('.acf-sms-download-btn').on('click', function() {
			var $button = $(this);
			var originalText = $button.html();
			
			// Change button text temporarily.
			$button.html('<span class="dashicons dashicons-update spin"></span> ' + acfSmsAdmin.downloading);
			
			// Reset button after download starts.
			setTimeout(function() {
				$button.html(originalText);
			}, 2000);
		});
	}

	// Initialize when document is ready.
	$(document).ready(init);

})(jQuery);
