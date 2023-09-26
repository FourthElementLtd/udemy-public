<?php
namespace Aelia\WC\Integrations\Freemius;

use Aelia\WC\Freemius_Integration\Shortcodes\Freemius_Account_Page_Shortcode;
use Aelia\WC\Freemius_Integration\Shortcodes\Freemius_Contact_Form_Shortcode;
use Aelia\WC\Freemius_Integration\Shortcodes\Freemius_Plugin_Pricing_Shortcode;
use Aelia\WC\Shortcodes\Shortcode_Settings;

if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

/**
 * Handles the integration with the Freemius for each plugin.
 *
 * @since 2.1.9.210525
 */
class Freemius_Plugin_Integration {
	/**
	 * Stores a list of the integrations linked to the registered plugins.
	 *
	 * @var array
	 */
	protected static $registered_plugins = array();

	/**
	 *
	 *
	 * @var Freemius_Plugin_Manager
	 */
	protected static $fs_plugin_manager;

	/**
	 * Initialises the class.
	 *
	 * @return void
	 */
	public static function init(): void	{
		// Register the shortcodes related to the interaction with Freemius
		self::init_shortcodes();

		add_action('init', array(__CLASS__, 'init_freemius_plugin_manager'));
	}

	/**
	 * Initialises the Freemius Plugin Manager, which will display a section with
	 * the registered Freemius plugins at WooCommerce > Settings > Aelia > Freemius Licences
	 *
	 * @return void
	 */
	public static function init_freemius_plugin_manager(): void {
		if(empty(self::$fs_plugin_manager)) {
			self::$fs_plugin_manager = new Freemius_Plugins_Manager(self::$registered_plugins);
		}
	}

	/**
	 * Initialises the shortcodes introduced by the integration.
	 *
	 * @return void
	 */
	protected static function init_shortcodes(): void {
		$shortcode_settings = new Shortcode_Settings([]);
		Freemius_Account_Page_Shortcode::init($shortcode_settings);
		Freemius_Contact_Form_Shortcode::init($shortcode_settings);
		Freemius_Plugin_Pricing_Shortcode::init($shortcode_settings);
	}

	/**
	 * Initialises the Freemius integration for a given plugin.
	 *
	 * @param string $plugin_slug
	 * @param string $main_plugin_file
	 * @param array $freemius_settings
	 * @return Freemius
	 */
	public static function init_freemius(string $plugin_slug, string $main_plugin_file, array $freemius_settings) {
		// Load the Freemius SDK once
		// @since 2.1.15.210831
		require_once \Aelia\WC\WC_AeliaFoundationClasses::instance()->path('vendor') . '/freemius/wordpress-sdk/start.php';

		if(!isset(self::$registered_plugins[$plugin_slug])) {
			self::$registered_plugins[$plugin_slug] = fs_dynamic_init($freemius_settings);
			self::$registered_plugins[$plugin_slug]->set_basename(true, $main_plugin_file);
		}
		return self::$registered_plugins[$plugin_slug];
	}

	/**
	 * Returns the instance of the Freemius integration linked to the specified plugin slug.
	 *
	 * @param string $plugin_slug
	 * @return Freemius|null
	 */
	public static function get_freemius_integration(string $plugin_slug) {
		return self::$registered_plugins[$plugin_slug] ?? null;
	}

	/**
	 * Returns the list of the plugins registered with Freemius.
	 *
	 * @return array
	 * @since 2.1.9.210525
	 */
	public static function get_registered_plugins(): array {
		return self::$registered_plugins;
	}

	/**
	 * Indicates if a plugin installed on the site was registered and activated with Freemius.
	 *
	 * @param string $plugin_slug
	 * @return bool
	 */
	public static function is_plugin_license_activated(string $plugin_slug): bool {
		$fs_integration = self::$registered_plugins[$plugin_slug] ?? null;

		return !empty($fs_integration) && ($fs_integration->has_features_enabled_license());
	}

	/**
	 * Returns the URL to display the account page for the specified plugin. The page
	 * shows the licence information and allows the customer to modify the account details.
	 *
	 * @param string $plugin_slug
	 * @return string
	 */
	public static function get_account_page_url(string $plugin_slug): string {
		return isset(self::$registered_plugins[$plugin_slug]) ? self::$registered_plugins[$plugin_slug]->get_account_url() : '';
	}
}