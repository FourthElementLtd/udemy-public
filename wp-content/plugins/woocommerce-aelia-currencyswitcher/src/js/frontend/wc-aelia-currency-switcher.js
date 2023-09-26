jQuery(document).ready(function($) {
	/**
	 * Updates the Price Filter Slider widget to reflect the price ranges using
	 * selected Currency.
	 */
	function update_price_filter_slider() {
		var $ = jQuery;
		if($('.price_slider').length <= 0) {
			return;
		}
		var $price_slider = $('.price_slider');
		var $price_slider_amount = $('.price_slider_amount');

		if($price_slider_amount.length <= 0) {
			return;
		}

		// Store the Currency used by the Price Filter. This will be used to determine
		// how to convert filter range into base Currency
		var selected_currency = wc_aelia_currency_switcher_params.selected_currency;

		// If the "price filter currency" field already exists, use it
		// @since 4.6.9.181217
		var price_filter_currency_field = $price_slider_amount.find('input[name="price_filter_currency"]').attr('id', 'price_filter_currency');

		if(price_filter_currency_field.length <= 0) {
			var price_filter_currency_field = $('<input>')
				.attr('id', 'price_filter_currency')
				.attr('name', 'price_filter_currency')
				.attr('value', selected_currency)
				.hide();
				$price_slider_amount.append(price_filter_currency_field);
		}
	}

	update_price_filter_slider();

	/**
	 * Returns the value of a parameter passed via the URL.
	 *
	 * @param string param_name
	 * @return string|false The value of the parameter, or false if the parameter
	 * was not found.
	 * @since 4.4.15.170421
	 */
	function get_url_param(param_name){
		var results = new RegExp('[\?&]' + param_name + '=([^&#]*)').exec(window.location.href);
		return results ? results[1] : false;
	}

	// Check if the currency was set via the URL
	var currency_set_by_url = get_url_param('aelia_cs_currency');

	try {
		// Invalidate cache of WooCommerce minicart when the currency is selected via
		// the URL. This will ensure that the minicart is updated correctly
		var supports_html5_storage = ('sessionStorage' in window && window['sessionStorage'] !== null);
		if(supports_html5_storage && currency_set_by_url) {
			// The fragment name might be generated dynamically by WooCommerce, so
			// we have to retrieve it from the WC parameters
			// @since WC 3.1
			var fragment_name = 'wc_fragments';
			if(wc_cart_fragments_params && wc_cart_fragments_params.fragment_name) {
				fragment_name = wc_cart_fragments_params.fragment_name;
			}

			sessionStorage.removeItem(fragment_name, '');
		}
	}
	catch(exception) {
		var error_msg = 'Aelia - Exception occurred while accessing window.sessionStorage. ' +
										'This could be caused by the browser disabling cookies. ' +
										'COOKIES MUST BE ENABLED for the site to work correctly. ' +
										'Exception details below.';
		console.log(error_msg);
		console.log(exception);
	}
});
