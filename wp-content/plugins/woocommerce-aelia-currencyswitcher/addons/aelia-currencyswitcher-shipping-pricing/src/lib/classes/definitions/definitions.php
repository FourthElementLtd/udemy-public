<?php
namespace Aelia\WC\CurrencySwitcher\ShippingPricing;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

/**
 * Implements a base class to store and handle the messages returned by the
 * plugin. This class is used to extend the basic functionalities provided by
 * standard WP_Error class.
 */
class Definitions {
	// @var string The menu slug for plugin's settings page.
	const MENU_SLUG = 'wc_aelia_cs_shippingpricing';
	// @var string The plugin slug
	const PLUGIN_SLUG = 'wc-aelia-cs-shippingpricing';
	// @var string The plugin text domain
	const TEXT_DOMAIN = 'wc-aelia-cs-shippingpricing';

	/**
	 * The slug used to check for updates.
	 *
	 * @var string
	 * @since 1.3.3.180112
	 */
	const PLUGIN_SLUG_FOR_UPDATES = 'aelia-currencyswitcher-shipping-pricing';

	const ARG_SHIPPING_PRICING_CURRENCY = 'curr';
	const FIELD_SHIPPING_PRICING_CURRENCY = 'currency';
	const FIELD_MANUAL_PRICES_ENABLED = 'currency_manual_prices_enabled';
}
