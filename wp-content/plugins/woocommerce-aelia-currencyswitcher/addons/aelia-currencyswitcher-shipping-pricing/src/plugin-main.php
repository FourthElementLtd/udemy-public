<?php
namespace Aelia\WC\CurrencySwitcher\ShippingPricing;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

require_once('lib/classes/definitions/definitions.php');

use \WC_Aelia_CurrencySwitcher;
use Aelia\WC\Aelia_Plugin;
use Aelia\WC\Aelia_SessionManager;
use Aelia\WC\CurrencySwitcher\ShippingPricing\Messages;

/**
 * Main plugin class.
 **/
class WC_Aelia_CS_ShippingPricing_Plugin extends Aelia_Plugin {
	public static $version = '1.4.17.220704';

	public static $plugin_slug = Definitions::PLUGIN_SLUG;
	public static $text_domain = Definitions::TEXT_DOMAIN;
	public static $plugin_name = 'Aelia Shipping Pricing for Currency Switcher';

	/**
	 * Indicates if the plugin was loaded as an addon, instead of a standalone plugin.
	 *
	 * @var boolean
	 * @since 1.4.0.210517
	 */
	public static $loaded_as_addon = false;

	/**
	 * The slug used to check for updates.
	 *
	 * @var string
	 * @since 1.3.3.180112
	 */
	public static $slug_for_update_check = Definitions::PLUGIN_SLUG_FOR_UPDATES;

	// @var bool Indicates if the settings page of a shipping method is being rendered
	protected static $_processing_settings = false;

	// @var array Contains the declaration of the template classes that will be used to extend shipping methods.
	protected $_shipping_method_template = array();

	/**
	 * Keeps track of the active currency.
	 *
	 * @var string
	 * @since 1.2.3.160516
	 */
	protected $_active_currency;

	/**
	 * Shop's base currency.
	 *
	 * @var string
	 * @since 1.2.3.160516
	 */
	protected $_base_currency;

	// @var array A list of the shipping methods supported by the plugin.
	// It will be used to ensure that only the supported methods will be overridded by the plugin
	protected $_supported_shipping_methods = array(
		'WC_Shipping_Flat_Rate',
		'WC_Shipping_Free_Shipping',
		// Bolder Elements - Table Rate Shipping
		'BE_Table_Rate_Method',
	);

	/**
	 * Indicates if the settings page of a shipping method is being rendered, and
	 * which page.
	 *
	 * @return false|string
	 */
	public static function processing_settings() {
		return self::$_processing_settings;
	}

	/**
	 * Indicates if we are viewing the settings page for the shipping.
	 *
	 * @return bool
	 * @since 1.2.3.160516
	 */
	public static function managing_shipping() {
		return is_admin() && !self::doing_ajax() &&
					 (!empty($_GET['page']) && ($_GET['page'] === 'wc-settings')) &&
					 (!empty($_GET['tab']) && ($_GET['tab'] === 'shipping'));
	}

	/**
	 * Builds and stores the paths used by the plugin. This method also takes into
	 * account the case when the plugin is loaded as an addon.
	 *
	 * @since 1.4.0.210517
	 */
	protected function set_paths() {
		if(!self::$loaded_as_addon) {
			parent::set_paths();
			return;
		}

		$addon_base_dir = WC_Aelia_CurrencySwitcher::instance()->path('addons') . '/' . $this->plugin_dir();
		$this->paths['plugin'] = $addon_base_dir . '/src';
		$this->paths['languages'] = $addon_base_dir  . '/languages';
		$this->paths['languages_rel'] = str_replace(WP_PLUGIN_DIR, '', $addon_base_dir  . '/languages');
		$this->paths['lib'] = $this->path('plugin') . '/lib';
		$this->paths['views'] = $this->path('plugin') . '/views';
		$this->paths['admin_views'] = $this->path('views') . '/admin';
		$this->paths['classes'] = $this->path('lib') . '/classes';
		$this->paths['widgets'] = $this->path('classes') . '/widgets';
		$this->paths['vendor'] = $this->path('plugin') . '/vendor';

		$this->paths['design'] = $this->path('plugin') . '/design';
		$this->paths['css'] = $this->path('design') . '/css';
		$this->paths['images'] = $this->path('design') . '/images';

		$this->paths['js'] = $this->path('plugin') . '/js';
		$this->paths['js_admin'] = $this->path('js') . '/admin';
		$this->paths['js_frontend'] = $this->path('js') . '/frontend';
	}

	/**
	 * Builds and stores the URLs used by the plugin. This method also takes into
	 * account the case when the plugin is loaded as an addon.
	 *
	 * @since 1.4.0.210517
	 */
	protected function set_urls() {
		if(!self::$loaded_as_addon) {
			parent::set_urls();
			return;
		}

		$addon_base_url = WC_Aelia_CurrencySwitcher::instance()->url('addons') . '/' . $this->plugin_dir();
		$this->urls['plugin'] = $addon_base_url . '/src';

		$this->urls['design'] = $this->url('plugin') . '/design';
		$this->urls['css'] = $this->url('design') . '/css';
		$this->urls['images'] = $this->url('design') . '/images';
		$this->urls['js'] = $this->url('plugin') . '/js';
		$this->urls['js_admin'] = $this->url('js') . '/admin';
		$this->urls['js_frontend'] = $this->url('js') . '/frontend';
	}

	/**
	 * Factory method.
	 */
	public static function factory() {
		// Load Composer autoloader
		require_once(__DIR__ . '/vendor/autoload.php');

		// Settings and messages classes are loaded from the same namespace as the
		// plugin
		//$settings_page_renderer = new Settings_Renderer();
		//$settings_controller = new Settings(Settings::SETTINGS_KEY,
		//																		self::$text_domain,
		//																		$settings_page_renderer);
		$messages_controller = new Messages(static::$text_domain);

		$class = get_called_class();
		return new $class(null, $messages_controller);
	}

	/**
	 * Constructor.
	 *
	 * @param \Aelia\WC\Settings settings_controller The controller that will handle
	 * the plugin settings.
	 * @param \Aelia\WC\Messages messages_controller The controller that will handle
	 * the messages produced by the plugin.
	 */
	public function __construct($settings_controller = null,
															$messages_controller = null) {
		// Load Composer autoloader
		require_once(__DIR__ . '/vendor/autoload.php');

		parent::__construct($settings_controller, $messages_controller);
	}

	/**
	 * Sets the hooks required by the plugin.
	 */
	protected function set_hooks() {
		parent::set_hooks();

		add_filter('woocommerce_shipping_methods', array($this, 'woocommerce_shipping_methods'), 15, 1);
		add_action('woocommerce_settings_shipping', array($this, 'set_processing_shipping_flag'), 1);
		add_action('woocommerce_settings_save_shipping', array($this, 'set_processing_shipping_flag'), 1);

		add_action('woocommerce_shipping_zone_before_methods_table', array($this, 'woocommerce_shipping_zone_before_methods_table'), 20);
	}

	/**
	 * Alters the list of the shipping methods passed to WooCommerce.
	 *
	 * @param array shipping_methods The array of shipping methods.
	 * @return array
	 */
	public function woocommerce_shipping_methods($shipping_methods) {
		$new_shipping_methods = array();

		// Replace all shipping classes with dyniamcally generated ones
		foreach($shipping_methods as $key => $original_shipping_method) {
			// Get the shipping method class, to determine if it has to be replaced
			$original_shipping_method_class = $this->get_shipping_method_class($original_shipping_method);

			if(class_exists($original_shipping_method_class) && in_array($original_shipping_method_class, $this->_supported_shipping_methods)) {
				// If shipping method is amongst the supported one, override its class
				$new_shipping_methods[$key] = $this->get_shipping_method_class_override($original_shipping_method_class);
			}
			else {
				// Leave unsupported shipping methods untouched, to prevent unexpected side effects
				$new_shipping_methods[$key] = $original_shipping_method;
			}
		}

		$new_shipping_methods = apply_filters('aelia_cs_shipping_pricing_shipping_methods', $new_shipping_methods);

		return $new_shipping_methods;
	}

	/**
	 * Invoked when the settings page of a shipping method is invoked. This method
	 * keeps track of when a settings page is rendered, so that the shipping methods
	 * ae aware of it.
	 */
	public function set_processing_shipping_flag() {
		self::$_processing_settings = true;
	}

	/**
	 * Determines if one of plugin's admin pages is being rendered. Override it
	 * if plugin implements pages in the Admin section.
	 *
	 * @return bool
	 */
	protected function rendering_plugin_admin_page() {
		$screen = get_current_screen();
		$page_id = $screen->id;

		return ($page_id == 'woocommerce_page_' . Definitions::MENU_SLUG);
	}

	/**
	 * Registers the script and style files needed by the admin pages of the
	 * plugin. Extend in descendant plugins.
	 */
	protected function register_plugin_admin_scripts() {
		// Scripts
		wp_register_script('jquery-ui',
											 '//code.jquery.com/ui/1.10.3/jquery-ui.js',
											 array('jquery'),
											 null,
											 true);
		wp_register_script('chosen',
											 '//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.jquery.min.js',
											 array('jquery'),
											 null,
											 true);

		// Styles
		wp_register_style('chosen',
												'//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.min.css',
												array(),
												null,
												'all');
		wp_register_style('jquery-ui',
											'//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css',
											array(),
											null,
											'all');

		wp_enqueue_style('jquery-ui');
		wp_enqueue_style('chosen');

		wp_enqueue_script('jquery-ui');
		wp_enqueue_script('chosen');

		parent::register_plugin_admin_scripts();
	}

	/**
	 * Returns the class of a shipping method.
	 *
	 * @param object|string shipping_method A shipping method class or instance.
	 * @return string The shipping method class.
	 * @since 1.1.15.151015
	 */
	protected function get_shipping_method_class($shipping_method) {
		// Cover the case in which some other plugin intercepted the
		// woocommerce_shipping_methods hook and replaced a class with its instance
		if(is_object($shipping_method)) {
			$shipping_method_class = get_class($shipping_method);
		}
		else {
			// If the entry is not an object, then it's a class name
			$shipping_method_class = $shipping_method;
		}
		return $shipping_method_class;
	}

	/**
	 * Given a shipping method class, returns the custom class, with multi-currency features,
	 * that should be used to replace it.
	 *
	 * @param string $shipping_method_class
	 * @return string
	 */
	protected function get_shipping_method_class_override($shipping_method_class) {
		/* This array associates the class to be loaded with the file that contains
		 * it. Since WC 2.6 we cannot rely on autoloaders anymore, because the shipping
		 * classes have the same name, but different logic, and are located in different
		 * places. The class map allows to load the correct files, depending on the
		 * WooCommerce version.
		 */
		$class_map = apply_filters('wc_aelia_cs_shipping_method_overrides', array(
			'WC_Shipping_Flat_Rate' => 'Aelia_CS_WC_Shipping_Flat_Rate',
			'WC_Shipping_Free_Shipping' => 'Aelia_CS_WC_Shipping_Free_Shipping',
			'BE_Table_Rate_Method' => 'Aelia_CS_BE_Table_Rate_Method',
		));

		if(isset($class_map[$shipping_method_class]) && class_exists($class_map[$shipping_method_class])) {
			return $class_map[$shipping_method_class];
		}
		return $shipping_method_class;
	}

	/**
	 * Registers the script and style files required in the backend (even outside
	 * of plugin's pages). Extend in descendant plugins.
	 */
	protected function register_common_admin_scripts() {
		parent::register_common_admin_scripts();

		// Admin styles
		wp_register_style(static::$plugin_slug . '-admin',
											$this->url('plugin') . '/design/css/admin.css',
											array(),
											null,
											'all');
		wp_enqueue_style(static::$plugin_slug . '-admin');

		if(self::managing_shipping()) {
			wp_enqueue_script(static::$plugin_slug . '-shipping-method',
												$this->url('js') . '/admin/shipping_method.js',
												array('jquery'),
												null,
												true);
		}
	}

	/**
	 * Indicates if we are on a page used to manage the settings of a shipping
	 * method.
	 *
	 * @return bool
	 */
	public static function managing_shipping_method_settings() {
		// Determine if we are on the shipping method page in the backend, or if we
		// are handling an Ajax call to save the settings
		$result= (!self::is_frontend() &&
						 (!empty($_GET['page']) && ($_GET['page'] == 'wc-settings')) &&
						 (!empty($_GET['tab']) && ($_GET['tab'] == 'shipping'))) ||
						 (self::doing_ajax() && isset($_GET['action']) &&
							in_array($_GET['action'], array('woocommerce_shipping_zone_add_method', 'woocommerce_shipping_zone_methods_save_settings')));
		// Allow 3rd parties to apply their own criteria
		return apply_filters('aelia_cs_shippingpricing_managing_shipping_method_settings', $result);
	}

	/**
	 * Returns the base currency configured in WooCommerce.
	 *
	 * @return string
	 * @since 1.2.3.160516
	 */
	public function base_currency() {
		if(empty($this->_base_currency)) {
			$this->_base_currency = WC_Aelia_CurrencySwitcher::settings()->base_currency();
		}
		return $this->_base_currency;
	}

	/**
	 * Determines the active currency, which will be used to load the appropriate
	 * settings.
	 *
	 * @return string
	 * @since 1.2.3.160516
	 */
	public function active_currency() {
		if(empty($this->_active_currency)) {
			// On the backend, the active currency is the one selected explicitly on
			// the shipping method's settings page
			if(self::managing_shipping_method_settings()) {
				$currency = get_value(Definitions::ARG_SHIPPING_PRICING_CURRENCY, $_GET);

				if(empty($currency)) {
					$currency = Aelia_SessionManager::get_cookie(Definitions::ARG_SHIPPING_PRICING_CURRENCY, '');
				}

				if(!empty($currency) && WC_Aelia_CurrencySwitcher::instance()->is_valid_currency($currency)) {
					$this->_active_currency = $currency;
				}
				else {
					$this->_active_currency = $this->base_currency();
				}

				Aelia_SessionManager::set_cookie(Definitions::ARG_SHIPPING_PRICING_CURRENCY,
																				 $this->_active_currency,
																				 time() + DAY_IN_SECONDS);
			}
			else {
				$this->_active_currency = get_woocommerce_currency();
			}
		}
		return $this->_active_currency;
	}

	/**
	 * Renders the currency selector, to configure shipping pricing in another
	 * currency.
	 *
	 * @since 1.2.3.160516
	 */
	protected function render_currency_selector() {
		?>
		<div class="currency_selector">
			<div class="title"><?php
				echo __('Configuring prices for currency:', static::$text_domain) . ' ' . $this->active_currency();
				if($this->active_currency() === $this->base_currency()) {
					echo ' ' . __('(base currency)', static::$text_domain);
				}
			?></div>
			<div class="selectors">
				<span class="label"><?php
				echo __('Select a currency to configure the shipping prices:', static::$text_domain);
				?></span><?php

				$enabled_currencies = WC_Aelia_CurrencySwitcher::instance()->enabled_currencies();
				asort($enabled_currencies);
				foreach($enabled_currencies as $currency) {
					// Add a link to allow changing the currency. The link is deliberately "void",
					// the redirect will be performed via JavaScript
					// @since 1.3.21.210423
					echo '<a class="currency_link" href="javascript:void(0)" data-currency="'. $currency . '">' . $currency. '</a>';
				}
			?></div>
		</div>
		<?php
	}

	/**
	 * Renders the currency selector on the Zone Shipping Methods page.
	 *
	 * @since 1.2.3.160516
	 */
	public function woocommerce_shipping_zone_before_methods_table() {
		?>
		<div id="shipping_zone_methods_currency_selector" class="aelia shipping_method_settings shipping_zone_methods"><?php
			$this->render_currency_selector();
		?></div>
		<?php
	}

	// Legacy Licensing feature removed
}