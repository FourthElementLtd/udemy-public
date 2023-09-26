<?php if(!defined('ABSPATH')) { exit; } // Exit if accessed directly
/*
Plugin Name: Aelia Shipping Pricing for Currency Switcher
Description: Allows to specify shipping prices for each currency, rather than rely on automatic conversion.
Author: Aelia
Author URI: https://aelia.co
Plugin URI: https://aelia.co/shop/shipping-pricing-for-currency-switcher/
Version: 1.4.17.220704
Text Domain: wc-aelia-cs-shippingpricing
Domain Path: /languages
WC requires at least: 3.0
WC tested up to: 6.7
Requires PHP: 7.1
*/

// If the plugin is not being loaded as an addon, load the requirement checking class and
// check that the requirements are met
// @since 1.4.0.210517
if(empty($aelia_cs_loading_addons)) {
	require_once dirname(__FILE__) . '/src/lib/classes/install/aelia-wc-cs-shipping-pricing-requirementscheck.php';
	$requirements_met = Aelia_WC_ShippingPricing_RequirementsChecks::factory()->check_requirements();
}

// If the plugin is being loaded as an addon by the Currency Switcher, or the requirements as a standalone
// plugin are met, load the plugin
// @since 1.4.0.210517
// Ensure that the addon is not loaded more than once
// @since 1.4.3.210623
if(empty($GLOBALS['wc-aelia-cs-shippingpricing']) && (!empty($aelia_cs_loading_addons) || $requirements_met)) {
	require_once dirname(__FILE__) . '/src/plugin-main.php';

	// Indicate if the plugin is being loaded as an embedded addon
	// @since 1.4.0.210517
	\Aelia\WC\CurrencySwitcher\ShippingPricing\WC_Aelia_CS_ShippingPricing_Plugin::$loaded_as_addon = !empty($aelia_cs_loading_addons);

	// Initialise the plugin
	$GLOBALS['wc-aelia-cs-shippingpricing'] = \Aelia\WC\CurrencySwitcher\ShippingPricing\WC_Aelia_CS_ShippingPricing_Plugin::factory();

	// Register this plugin file for auto-updates, if such capability exists
	if(!empty($GLOBALS['wc-aelia-cs-shippingpricing']) && method_exists($GLOBALS['wc-aelia-cs-shippingpricing'], 'set_main_plugin_file')) {
		// Set the path and name of the main plugin file (i.e. this file), for update
		// checks. This is needed because this is the main plugin file, but the updates
		// will be checked from within plugin-main.php
		$GLOBALS['wc-aelia-cs-shippingpricing']->set_main_plugin_file(__FILE__);
	}
}
