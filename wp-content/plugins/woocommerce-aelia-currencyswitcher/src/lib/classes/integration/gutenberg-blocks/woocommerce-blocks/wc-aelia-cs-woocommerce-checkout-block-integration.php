<?php
namespace Aelia\WC\CurrencySwitcher\Integrations\Blocks\WC;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\CurrencySwitcher\Definitions;
use Aelia\WC\CurrencySwitcher\Integration\Blocks\WC_Aelia_CS_Base_Block_Integration;
use Aelia\WC\CurrencySwitcher\Settings;

/**
 * Implements support for the WooCommerce Checkout Block.
 *
 * @since 4.10.0.210312
 */
class WC_Aelia_CS_WooCommerce_Checkout_Block_Integration extends WC_Aelia_CS_Base_Block_Integration {
	/**
	 * The API routes used by the WC Checkout Block to update the user data on the checkout page.
	 *
	 * @var string
	 * @since 4.13.4.220315
	 */
	const URI_CHECKOUT_API_ROUTES = [
		'/wc/store/cart/update-customer/',
		'/wc/store/v1/cart/update-customer/',
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_filter('rest_pre_dispatch', [$this, 'rest_pre_dispatch'], 1, 3);
	}

	/**
	 * Intercepts requests to the REST API, made to update the user data at checkout. If option "force currency
	 * by country" is enabled, this method tries to update customer's country.
	 *
	 * @param bool $is_request_to_rest_api
	 * @return bool
	 * @since 4.13.4.220315
	 */
	public function rest_pre_dispatch($response, $rest_server, $request) {
		if(self::rest_request_matches_route($request, self::URI_CHECKOUT_API_ROUTES)) {
			// Check if the "force currency by country" option is enabled
			$country_source = $this->cs()->force_currency_by_country();

			if($country_source !== Settings::OPTION_DISABLED) {
				$this->get_logger()->debug(__('Handling request to update user date from WooCommerce Checkout Block.', Definitions::TEXT_DOMAIN), [
					'Request' => $request,
					'Billing Address' => $request['billing_address'] ?? null,
					'Shipping Address' => $request['shipping_address'] ?? null,
				]);

				// Extract customer's country from the request, so that it can be set to select the currency
				if($country_source === Settings::OPTION_SHIPPING_COUNTRY) {
					if(isset($request['shipping_address'])) {
						$customer_country = $request['shipping_address']['country'] ?? ($request['billing_address']['country'] ?? '');
					}
					else {
						// If the shipping address was not passed, it means that the customer only change the billing
						// address. The block only sends the data that changed and, since we have to take into account
						// the shipping address changes, we set the customer country to an empty value to ignore this event
						$customer_country = '';
					}
				}
				else {
					$customer_country = $request['billing_address']['country'] ?? '';
				}

				$this->get_logger()->debug(__('Customer country fetched from the data passed by the WooCommerce Checkout Block.', Definitions::TEXT_DOMAIN), [
					'Country Source' => $country_source,
					'Customer Country' => $customer_country,
				]);

				// Simulate an explicit selection of customer's country. This will also set the active currency
				if(!empty($customer_country)) {
					$_POST['aelia_customer_country'] = $customer_country;
					// Reset the selected country and currency
					// @since 4.13.4.220315
					do_action('wc_aelia_reset_selected_country', true);
					do_action('wc_aelia_reset_selected_currency', true);
				}
			}
		}

		return $response;
	}
}