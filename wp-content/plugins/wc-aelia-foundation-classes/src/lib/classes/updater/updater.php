<?php
namespace Aelia\WC;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use \Exception;
use \InvalidArgumentException;

/**
 * Base updater class.
 *
 * @since 1.7.1.150824
 */
abstract class Updater extends Base_Class {
	/**
	 * The ID of the updater class.
	 *
	 * @var string
	 * @since 1.8.3.170110
	 */
	public static $id = 'aelia_updater';

	/**
	 * The name of this updater.
	 *
	 * @since 1.9.8.171002
	 */
	public static $updater_name = 'Aelia Base Updater';

	/**
	 * A list of instances of descendant updater classes.
	 *
	 * @var array
	 */
	protected static $updaters = array();

	/**
	 * An array of products (plugins, themes) that registered for
	 * automatic updates.
	 * @var array
	 */
	protected $products_to_update = array();

	/**
	 * An array of product licenses that have to be managed. If this list is not
	 * empty, a user interface will be added to WooCommerce settings, to allow the
	 * site owner to manage the licenses.
	 *
	 * @var array
	 */
	protected $product_licenses = array();

	/**
	 * Text domain.
	 *
	 * @var string
	 */
	protected static $text_domain;

	/**
	 * The logger used by the class.
	 *
	 * @var Aelia\WC\Logger
	 */
	protected $logger;

	/**
	 * The instance of the integration with the Product Addons plugin.
	 *
	 * @var Aelia\WC\WC_AeliaFoundationClasses
	 * @since 2.4.12.230803
	 */
	protected $_afc;

	/**
	 * Constructor.
	 *
	 * @param array products_to_update An array of products (plugins, themes) whose
	 * updates will be handled by the class.
	 */
	public function __construct(array $products_to_update) {
		parent::__construct();

		self::$text_domain = Definitions::TEXT_DOMAIN;

		$this->logger = $this->AFC()->get_logger();
		$this->products_to_update = $products_to_update;
		$this->set_hooks();
	}

	/**
	 * Sets the hooks required by the class.
	 */
	protected function set_hooks() {
		// Automatic updates
		$this->add_license_management_settings();
	}

	public function add_license_management_settings() {
		$licenses = $this->get_product_licenses();
		if(!empty($licenses)) {
			add_action('woocommerce_update_options_'. self::$id, array($this, 'save_licenses'));

			// Add sections and the settings for each section
			// @since 1.9.8.171002.
			add_filter('woocommerce_get_sections_wc_aelia_foundation_classes', array($this, 'add_settings_section'), 10, 1);
			add_filter('wc_aelia_afc_settings', array($this, 'get_settings'), 10, 1);
		}
	}

	/**
	 * Adds a settings page to WooCommerce settings.
	 *
	 * @param array pages An array of settings pages.
	 * @return array An array of settings pages.
	 * @since 1.8.3.170110
	 */
	public function add_license_management_page($pages){
		$pages[self::$id] = __('Aelia Licences', self::$text_domain);
		return $pages;
	}

	/**
	 * Indicates if the save button should be hidden on a settings page.
	 *
	 * @return bool
	 * @since 1.9.8.171002
	 */
	 protected function should_hide_save_button() {
		return true;
	}

	/**
	 * Saves the licences managed by this updater.
	 *
	 * @since 1.8.3.170110
	 */
	public function save_licenses() {
		// TODO When the license data is saved, validate each license
		// TODO When a license is valid, also store its status (active, inactive, etc) and expiration date
		woocommerce_update_options($this->get_license_management_fields());
	}

	/**
	 * Adds the settings section for this updater.
	 *
	 * @param array sections
	 * @return array
	 * @since 1.9.8.171002
	 */
	public function add_settings_section($sections) {
		$settings = $this->get_license_management_fields();
		if(!empty($settings)) {
			// Add the section for this updater to the existing list of sections
			$sections[static::$id] = __(static::$updater_name, self::$text_domain);
		}
		return $sections;
	}

	/**
	 * Adds the settings for this updater, when the updater section is loaded.
	 *
	 * @param array settings
	 * @return settings
	 * @since 1.9.8.171002
	 */
	public function get_settings($settings) {
		global $current_section;

		// Only add the settings if the section is the correct one
		if($current_section === static::$id) {
			$settings = $this->get_license_management_fields();

			// Hide the Save button. License activations/deactivations are saved via Ajax
			$GLOBALS['hide_save_button'] = $this->should_hide_save_button();
		}

		return $settings;
	}

	/**
	 * Returns a list of fields used by this updater to manage the licenses.
	 *
	 * @param array settings
	 * @return settings
	 */
	protected function get_license_management_fields($settings = array()) {
		// TODO Process $this->product_licenses and build a list of fields
		return array();
	}

	/**
	 * Loads the licenses handled by this updater.
	 *
	 * @return array
	 */
	protected function load_product_licenses() {
		return array();
	}

	/**
	 * Returns the product licenses handled by this updater. This method is a
	 * wrapper which caches the licenses returned by load_product_licenses().
	 *
	 * @return array
	 * @see Updater::load_product_licenses()
	 */
	protected function get_product_licenses() {
		if(empty($this->product_licenses)) {
			$this->product_licenses = $this->load_product_licenses();
		}

		return apply_filters('wc_aelia_afc_product_licenses', $this->product_licenses);
	}

	/**
	 * Returns the URL for current site.
	 *
	 * @return string
	 */
	protected static function get_site_url() {
		/**
		 * Some web hosts have security policies that block the : (colon) and // (slashes) in http://,
		 * so only the host portion of the URL can be sent. For example the host portion might be
		 * www.example.com or example.com. http://www.example.com includes the scheme http,
		 * and the host www.example.com.
		 * Sending only the host also eliminates issues when a client site changes from http to https,
		 * but their activation still uses the original scheme.
		 * To send only the host, use a line like the one below:
		 *
		 * $domain = str_ireplace(array('http://', 'https://'), '', strtolower(home_url()));
		 */
		return str_ireplace(array('http://', 'https://'), '', strtolower(home_url()));
	}

	/**
	 * Returns the ID of current site instance. To handle multiuser installations,
	 * this method returns the site URL, followed by the blog ID.
	 *
	 * @param string prefix An optional prefix.
	 * @param string suffix An optional suffix.
	 * @return The installation instance ID.
	 */
	protected function get_installation_instance_id($prefix = '', $suffix = '') {
		return $prefix . self::get_site_url() . '[' . (string)get_current_blog_id() . ']' . $suffix;
	}

	/**
	 * Generates the URL to be used to query the updates server.
	 *
	 * @param array args An array of arguments to be passed to the update server.
	 * @return string The URL to call to check for updates.
	 */
	abstract protected function get_api_call_url(array $args);

	/**
	 * Checks for updates for the specified product. This method must be implemented
	 * by descendant classes.
	 *
	 * @param object product A product (plugin, theme) descriptor.
	 */
	abstract protected function check_for_product_updates($product);

	/**
	 * Checks for updates for the registered products.
	 */
	public function check_for_updates() {
		foreach($this->products_to_update as $product_to_update) {
			$this->check_for_product_updates($product_to_update);
		}
	}

	/**
	 * Initialises the updater class.
	 *
	 * @param string product_type The product type for which the class is initialised.
	 * @param array product_list The list of the products that the updater will
	 * process.
	 */
	public static function init_updater($product_type, array $product_list) {
		if(empty(self::$updaters[$product_type])) {
			$plugin_updaters_map = apply_filters('wc_aelia_afc_updaters_map', array(
				'premium' => 'Aelia\WC\Premium_Plugin_Updater',
				'free' => 'Aelia\WC\Free_Plugin_Updater',
				// Add updater for development versions of free plugins
				'free-dev' => 'Aelia\WC\Free_Plugin_Dev_Version_Updater',
			));
			$updater_class = isset($plugin_updaters_map[$product_type]) ? $plugin_updaters_map[$product_type] : '';
			if(empty($updater_class)) {
				$error_msg = sprintf(__('Aelia Updater - Updater could not be loaded. ' .
																'Invalid product type specified: "%s". Please ' .
																'contact Aelia Support team and provide them ' .
																'with the information you can find below.',
																Definitions::TEXT_DOMAIN),
														 $product_type) .
														 '<pre>' .
														 sprintf(__('Backtrace (JSON): %s', Definitions::TEXT_DOMAIN),
																		 json_encode(debug_backtrace())) .
														 '</pre>';
				// Show the to the Shop Admininistrator
				Messages::admin_message(
					$error_msg,
					array(
						'level' => E_USER_ERROR,
						'code' => Definitions::ERROR_INVALID_PRODUCT_FOR_UPDATER,
					)
				);
				return false;
			}
			self::$updaters[$product_type] = new $updater_class($product_list);
		}
		return self::$updaters[$product_type];
	}

	/**
	 * Returns the instance of the Aelia Foundation Classes.
	 *
	 * @return WC_AeliaFoundationClasses
	 * @since 1.9.4.170410
	 */
	protected function AFC() {
		if(empty($this->_afc)) {
			$this->_afc = WC_AeliaFoundationClasses::instance();
		}
		return $this->_afc;
	}

	/**
	 * Indicates if debug mode is active.
	 *
	 * @return bool
	 * @since 1.9.4.170410
	 */
	protected function debug_mode() {
		return $this->AFC()->debug_mode();
	}

	/**
	 * Returns the message matching a message code.
	 *
	 * @param string message_code
	 * @return string
	 * @since 1.9.8.171002
	 */
	protected function get_message($message_code) {
		return WC_AeliaFoundationClasses::messages()->get_message($message_code);
	}
}
