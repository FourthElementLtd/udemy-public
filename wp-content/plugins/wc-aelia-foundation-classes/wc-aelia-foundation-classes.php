<?php if(!defined('ABSPATH')) { exit; } // Exit if accessed directly
/*
Plugin Name: Aelia Foundation Classes for WooCommerce
Description: This plugin implements common classes for other WooCommerce plugins developed by Aelia.
Author: Aelia
Author URI: https://aelia.co
Version: 2.4.15.230905
Plugin URI: https://aelia.co/shop/product-category/woocommerce/
Text Domain: wc-aelia-foundation-classes
Domain Path: /languages
WC requires at least: 3.0
WC tested up to: 8.1
Requires PHP: 7.1
*/

require_once __DIR__ . '/src/lib/classes/install/aelia-wc-afc-requirementscheck.php';

// If requirements are not met, deactivate the plugin
if(Aelia_WC_AFC_RequirementsChecks::factory()->check_requirements()) {
	require_once __DIR__ . '/src/plugin-main.php';

	// Set the path and name of the main plugin file (i.e. this file), for update
	// checks. This is needed because this is the main plugin file, but the updates
	// will be checked from within plugin-main.php
	$GLOBALS['wc-aelia-foundation-classes']->set_main_plugin_file(__FILE__);

	register_activation_hook(__FILE__, array($GLOBALS['wc-aelia-foundation-classes'], 'setup'));
}


// Declare compatibility with the HPOS feature
// @since 2.4.0.230202
add_action('before_woocommerce_init', function() {
	if(function_exists('aelia_declare_feature_support')) {
		aelia_declare_feature_support(__FILE__, 'custom_order_tables', true);
	}
});
