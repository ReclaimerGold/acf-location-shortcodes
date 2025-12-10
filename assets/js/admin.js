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

		// Handle auto-install template button.
		$('.acf-sms-auto-install-btn').on('click', function(e) {
			e.preventDefault();
			
			var $button = $(this);
			var nonce = $button.data('nonce');
			var originalHtml = $button.html();
			
			// Disable button and show loading state.
			$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Installing...');
			
			// Send AJAX request.
			$.ajax({
				url: acfSmsAdmin.ajaxurl,
				type: 'POST',
				data: {
					action: 'acf_sms_auto_install_template',
					nonce: nonce
				},
				success: function(response) {
					if (response.success) {
						$button.html('<span class="dashicons dashicons-yes-alt"></span> Installed!').removeClass('button-primary').addClass('button-success');
						
						// Show success message.
						var $card = $button.closest('.acf-sms-card');
						$card.find('.acf-sms-notice').fadeOut(300, function() {
							$(this).remove();
						});
						
						// Reload page after 2 seconds to show updated status.
						setTimeout(function() {
							location.reload();
						}, 2000);
					} else {
						$button.prop('disabled', false).html(originalHtml);
						alert(response.data.message || 'Installation failed. Please try manual installation.');
					}
				},
				error: function() {
					$button.prop('disabled', false).html(originalHtml);
					alert('An error occurred. Please try again.');
				}
			});
		});
	}

	// Initialize when document is ready.
	$(document).ready(init);

})(jQuery);
