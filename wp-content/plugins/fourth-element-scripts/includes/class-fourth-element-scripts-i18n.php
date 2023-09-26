<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.fourthelement.com
 * @since      1.0.0
 *
 * @package    Fourth_Element_Scripts
 * @subpackage Fourth_Element_Scripts/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Fourth_Element_Scripts
 * @subpackage Fourth_Element_Scripts/includes
 * @author     Fourth Element Devs <devs@fourthelement.com>
 */
class Fourth_Element_Scripts_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'fourth-element-scripts',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
