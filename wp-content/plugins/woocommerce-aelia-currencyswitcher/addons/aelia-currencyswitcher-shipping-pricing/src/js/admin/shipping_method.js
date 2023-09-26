/* Common JavaScript for shipping method pages */
jQuery(document).ready(function($) {
	/**
	 * Intercept clicks on the currency selectors, to reload the page with the new currency.
	 *
	 * @param object event
	 * @return void
	 * @since 1.3.21.210423
	 */
	$(document).on('click', '.aelia.shipping_method_settings .currency_link', function(event) {
		event.preventDefault();
		const $anchor = $(this);

		// Fetch the current page URL and add the currency to it
		const current_url = new URL(document.location);
		current_url.searchParams.set('curr', $anchor.data('currency'));

		// Reload the page
		window.location.href = current_url.toString();
	});
});
