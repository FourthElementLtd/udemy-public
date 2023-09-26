<?php
namespace Aelia\WC\CurrencySwitcher\ShippingPricing;
if(!defined('ABSPATH')) exit; // Exit ifaccessed directly

/**
 * Handles the settings for the Shipping Pricing plugin and provides convenience
 * methods to read and write them.
 */
class Settings extends \Aelia\WC\Settings {
	/*** Settings Key ***/
	// @var string The key to identify plugin settings amongst WP options.
	const SETTINGS_KEY = 'wc_aelia_cs_shippingpricing';

	/*** Settings fields ***/

	/**
	 * Returns the default settings for the plugin. Used mainly at first
	 * installation.
	 *
	 * @param string key If specified, method will return only the setting identified
	 * by the key.
	 * @param mixed default The default value to return if the setting requested
	 * via the "key" argument is not found.
	 * @return array|mixed The default settings, or the value of the specified
	 * setting.
	 *
	 * @see WC_Aelia_Settings:default_settings().
	 */
	public function default_settings($key = null, $default = null) {
		// TODO Add default options
		$default_options = array(
		);

		if(empty($key)) {
			return $default_options;
		}
		else {
			return get_value($key, $default_options, $default);
		}
	}

	/**
	 * Validates the settings specified via the Options page.
	 *
	 * @param array settings An array of settings.
	 */
	public function validate_settings($settings) {
		// TODO Implement method
		//var_dump($settings);die();
		$processed_settings = $this->current_settings();

		// Return the array processing any additional functions filtered by this action.
		return apply_filters('wc_aelia_cs_shippingpricing_validate_settings', $processed_settings, $settings);
	}

	/**
	 * Class constructor.
	 */
	public function __construct($settings_key = self::SETTINGS_KEY,
															$textdomain = '',
															\Aelia\WC\Settings_Renderer $renderer = null) {
		if(empty($renderer)) {
			// Instantiate the render to be used to generate the settings page
			$renderer = new \Aelia\WC\Settings_Renderer();
		}
		parent::__construct($settings_key, $textdomain, $renderer);

		add_action('admin_init', array($this, 'init_settings'));

		// If no settings are registered, save the default ones
		if($this->load() === null) {
			$this->save();
		}
	}

	/*** Validation methods ***/
}
