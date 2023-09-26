<?php
namespace Aelia\WC\CurrencySwitcher\ShippingPricing;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\CurrencySwitcher\ShippingPricing\Definitions;
use Aelia\WC\CurrencySwitcher\ShippingPricing\WC_Aelia_CS_ShippingPricing_Plugin;
use \WC_Aelia_CurrencySwitcher;

/**
 * A template class that will be used to extend the shipping methods to handle
 * multiple currencies. The class will be parsed using an eval() statement, after
 * having been modified to extend the target shipping method class.
 *
 * Example
 * Target class: WC_Shipping_Flat_Rate
 * New class declaration: Aelia_WC_Shipping_Flat_Rate extends WC_Shipping_Flat_Rate
 *
 * @see Aelia\WC\CurrencySwitcher\ShippingPricing\WC_Aelia_CS_ShippingPricing_Plugin::generate_shipping_method_class().
 * @since 1.3.0.170510
 */
trait Aelia_Base_Shipping_Method_Trait {
	// @var string Keeps track of the active currency.
	protected $_active_currency;
	// @var array The default settings stored for the shipping method.
	protected $default_settings;
	// @var string The text domain to use for localisation.
	protected $text_domain;

	// @var bool Indicates if the manual prices for the active currencies are enabled.
	// When set to false, it means that the default shipping prices should be taken instead and
	// converted using exchange rates
	protected $manual_prices_enabled = false;
	// @var bool Indicates if the shipping prices loaded by the class are already in the active currency.
	public $shipping_prices_in_currency = false;

	// @var array The settings that apply to the base currency. They are used when
	// merchat has not entered shipping costs in a specific currency
	protected $base_currency_settings;

	/**
	 * Returns the instance of the Shipping Pricing plugin.
	 *
	 * @return WC_Aelia_CS_ShippingPricing_Plugin
	 */
	protected function sp() {
		return WC_Aelia_CS_ShippingPricing_Plugin::instance();
	}

	/**
	 * Returns the base currency configured in WooCommerce.
	 *
	 * @return string
	 */
	protected function base_currency() {
		return $this->sp()->base_currency();
	}

	/**
	 * Returns the base settings key uses by the shipping method (i.e. the one
	 * used by the original class, without multi-currency support).
	 *
	 * @return string
	 * @since 1.2.0
	 */
	protected function base_settings_key() {
		if($this->instance_id) {
			return $this->get_instance_option_key();
		}
		return $this->get_option_key();
	}

	/**
	 * Generates the settings key for a specific currency.
	 *
	 * @param string currency The target currency.
	 * @return string
	 */
	protected function get_settings_key($currency) {
		return $this->get_option_key_by_currency($this->base_settings_key(), $currency);
	}

	/**
	 * Generates the settings key for a the specified option.
	 *
	 * @param string option The target option.
	 * @param string currency The target currency.
	 * @return string
	 */
	public function get_option_key_by_currency($option, $currency) {
		$settings_key = $option;
		if(!empty($currency)) {
			$settings_key .= '_' . $currency;
		}
		return $settings_key;
	}

	/**
	 * Determines the active currency, which will be used to load the appropriate
	 * settings.
	 *
	 * @return string
	 */
	protected function active_currency() {
		return $this->sp()->active_currency();
	}

	/**
	 * Retrieves the default settings for the shipping method. The default settings
	 * are the ones saved by WooCommerce when the Shipping Pricing plugin is not
	 * enabled, and are not associated to any currency.
	 *
	 * @return array
	 */
	protected function get_default_settings() {
    if(empty($this->default_settings) || !is_array($this->default_settings)) {
			// Load default form_field settings
			$this->default_settings = get_option($this->base_settings_key(), null);
			$this->default_settings[Definitions::FIELD_SHIPPING_PRICING_CURRENCY] = $this->base_currency();
			$this->default_settings[Definitions::FIELD_MANUAL_PRICES_ENABLED] = null;
		}
		return is_array($this->default_settings) ? $this->default_settings : array();
	}

	/**
	 * Retrieves the settings for the shipping method in base currency.
	 *
	 * @return array
	 */
	protected function get_base_currency_settings() {
    if(empty($this->base_currency_settings) || !is_array($this->base_currency_settings)) {
			// Load default form_field settings
			$this->base_currency_settings = get_option($this->get_settings_key($this->base_currency()), null);
		}
		return is_array($this->base_currency_settings) ? $this->base_currency_settings : array();
	}

	/**
	 * Indicates if the shipping method is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		// Apparently, in WC 2.6, when a shipping method is associated to a zone,
		// it's always enabled.
		// @since WC 2.6
		if($this->instance_id) {
			return true;
		}

		return $this->enabled == 'yes';
	}

	/**
	 * Loads the scripts and CSS for the shipping method settings page.
	 *
	 * @param bool render_html Indicates if the HTML should be rendered.
	 * If set to false, the HTML will just be retured.
	 */
	protected function load_settings_page_scripts($render_html = true) {
	}

	/**
	 * Constructor.
	 *
	 * @param int $instance_id
	 */
	public function __construct($instance_id = 0) {
		parent::__construct($instance_id);

		// Add a "CS" suffix to the shipping method, to easily identify the ones extended by the Currency Switcher
		$this->method_title .= ' ' . '(CS)';
		$this->text_domain = WC_Aelia_CS_ShippingPricing_Plugin::$text_domain;
	}

	/**
	 * Stores the settings for the base currency. Mainly used to initialise the
	 * settings for base currency the first time the plugin is used to configure
	 * them.
	 *
	 * @param array settings An array of settings.
	 */
	protected function init_settings_for_base_currency($settings) {
		update_option($this->get_settings_key($this->base_currency()), $settings);
	}

	/**
	 * Loads the settings for the gateway, using the specified key.
	 *
	 * @param string settings_key The key to use when loading the settings.
	 */
	protected function load_settings($settings_key) {
		// Load base currency settings (some of them apply to all currencies).
		$base_currency_settings = $this->get_base_currency_settings(); // NOSONAR

		// Load form_field settings
		$this->settings = get_option($settings_key, null);

		if(empty($this->settings) || !is_array($this->settings)) {
			if($this->active_currency() == $this->base_currency()) {
				// For base currency, when settings are empty, fetch and clone the
				// default ones
				$this->settings = $this->get_default_settings();

				$this->init_settings_for_base_currency($this->settings);
			}
			else {
				// For all other currencies, follow the standard logic
				$this->settings = array();
				// If there are no settings defined, load defaults
				if($form_fields = $this->get_form_fields()) {
					foreach($form_fields as $k => $v) {
						$this->settings[$k] = isset($v['default']) ? $v['default'] : '';
					}
				}
			}
		}

		if(!empty($this->settings) && is_array($this->settings)) {
			// Load the "manual prices enabled" flag
			$this->manual_prices_enabled = (get_arr_value(Definitions::FIELD_MANUAL_PRICES_ENABLED, $this->settings, null) == 'yes');
		}

		/* Base currency settings should be used in the following cases:
		 * - There are no settings for the specific currency.
		 * - There are settings for the specific currency, but they are disabled (i.e.
		 *   the "manual pricing" option is disabled.
		 */
		if($this->should_use_base_currency_settings()) {
			$this->settings = $base_currency_settings;
		}

		// $base_currency_settings['enabled'] indicates if the shipping method
		// itself is enabled. If it's not enabled, it won't be possible to use it,
		// regardless of any other settings
		$this->enabled = isset($base_currency_settings['enabled']) && $base_currency_settings['enabled'] == 'yes' ? 'yes' : 'no';
		$this->settings['enabled'] = $this->enabled;

		// If a specific instance was loaded, copy the settings to the instance
		// settings
		if($this->instance_id) {
			$this->instance_settings = $this->settings;
		}
	}

	/**
	 * Loads settings for the shipping method. This method takes into account the
	 * active currency and loads the appropriate settings, rather than the default
	 * ones.
	 */
	public function init_settings() {
		$settings_key = $this->get_settings_key($this->active_currency());

		$this->load_settings($settings_key);

		// If there are no settings defined, use defaults
		if(!is_array($this->settings)) {
			$form_fields = $this->get_form_fields();
			$this->settings = array_merge(array_fill_keys(array_keys($form_fields), ''),
																		wp_list_pluck($form_fields, 'default'));

			// If a specific instance was loaded, copy the settings to the instance
			// settings
			if($this->instance_id) {
				$this->instance_settings = $this->settings;
			}
		}

		// If we are viewing the settings page for this shipping method, or the method
		// is not enabled don't do anything else and keep the settings as they are
		if(!$this->is_enabled() ||
			 WC_Aelia_CS_ShippingPricing_Plugin::managing_shipping_method_settings() ||
			 (is_admin() && WC_Aelia_CS_ShippingPricing_Plugin::processing_settings())) {
			return;
		}

		// If we reach this point, we are not viewing the settings page for the shipping
		// method. In such case, we need to determine if we should use the pricing
		// for the specific currency, or fall back to the default pricing.
		// If this shipping method is not enabled, it means that the admins unticked
		// the "enabled" field for the shipping prices in the active currency. In
		// such case, the prices should be ignored and that the default settings
		// should be taken instead, to be converted using exchange rates
		if(!$this->manual_prices_enabled) {
			// Load and store the default settings (i.e. the ones that would be used
			// normally
			$this->load_settings($this->get_settings_key($this->base_currency()));
			$this->shipping_prices_in_currency = false;
		}
		else {
			$this->shipping_prices_in_currency = true;
		}
	}

	public function init_instance_settings() {
		return $this->init_settings();
	}

	public function init() {
		$this->init_settings();

		parent::init();
	}

	protected function add_extra_form_fields(array $form_fields = null) {
		if(!empty($form_fields) && is_array($form_fields)) {
			$this->form_fields = $form_fields;
		}

		$this->form_fields[Definitions::FIELD_SHIPPING_PRICING_CURRENCY] = array(
			'title' => '',
			'type' => 'hidden',
			'label' => null,
			'class' => 'currency hidden',
			'default' => $this->active_currency(),
			'custom_attributes' => array(
				'readonly' => 'readonly',
			),
		);

		// Change the description for the "enabled" field when a non-base currency is
		// Change the description for the "enabled" field when a non-base currency is
		// active. For such currencies, the "enabled" field does not disable the
		// shipping method, it simply ignores the prices entered manually
		if($this->active_currency() != $this->base_currency()) {
			if(isset($this->form_fields['enabled']['label'])) {
				$original_enabled_field_label = $this->form_fields['enabled']['title'];
			}
			else {
				$original_enabled_field_label = __('Enable/Disable', $this->text_domain);
			}
			// Remove the "enabled" field for the settings related to the extra currencies
			// When viewing any currency except the base one, the "enabled" field should
			// be hidden. This field is always inherited from the base currency settings,
			// the administrator should not change it
			unset($this->form_fields['enabled']);

			$this->form_fields = array(Definitions::FIELD_MANUAL_PRICES_ENABLED => array(
				'type' => 'checkbox',
				'title' => __('Enable currency-specific settings', $this->text_domain),
				'default' => null,
				'label' => __('Enable custom settings for this currency', $this->text_domain),
				'description' => implode(' ', [
					sprintf(
						__('If ticked, the settings entered in this page will be used for this shipping method, when "%s" is the selected currency.', Definitions::TEXT_DOMAIN),
						$this->active_currency()
					),
					__('If unticked, <strong>all</strong> these settings will be ignored, and the ones configured for the base currency will be used instead.', Definitions::TEXT_DOMAIN),
					'<br />',
					sprintf(
						__('If you wish to <strong>completely disable</strong> this shipping method, please select the base currency (%1$s) above, and untick the "<strong>%2$s</strong>" option.', Definitions::TEXT_DOMAIN),
			 			$this->base_currency(),
			 			$original_enabled_field_label
					),
				]),
			)) + $this->form_fields;
		}

		return $this->form_fields;
	}

	/**
	 * Initialise settings form fields
	 *
	 * @deprecated since WC 2.6
	 */
	public function init_form_fields() {
		if(method_exists(get_parent_class($this), __FUNCTION__)) {
			parent::init_form_fields();
		}
		$this->add_extra_form_fields();
	}

	/**
	 * Adds a shipping rate. If taxes are not set they will be calculated based on
	 * cost.
	 *
	 * @param array args The arguments
	 */
	public function add_rate($args = array()) {
		parent::add_rate($args);

		// If prices have been loaded for the active currency, flag the rates
		// so that the prices won't be converted by the Currency Switcher
		if($this->shipping_prices_in_currency) {
			foreach($this->rates as $idx => $rate) {
				$this->rates[$idx] = $rate;
				// Track the currency of the shipping rate, to prevent double conversions
				// @since 1.4.11.220122
				$rate->add_meta_data('currency', $this->active_currency());
			}
		}
	}

	/**
	 * Renders the currency selector, to configure shipping pricing in another
	 * currency.
	 */
	protected function render_currency_selector() {
		?>
		<div class="currency_selector">
			<div class="title"><?php
				echo __('Configuring prices for currency:', $this->text_domain) . ' ' . $this->active_currency();
				if($this->active_currency() == $this->base_currency()) {
					echo ' ' . __('(base currency)', $this->text_domain);
				}
			?></div>
			<div class="selectors">
				<span class="label"><?php
				echo __('Select a currency to configure the shipping prices:', $this->text_domain);
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

	protected function get_settings_currency() {
		$settings_currency = null;
		// When settings are saved, determine for which currency they are
		$currency_field_key = $this->plugin_id . $this->id . '_' . Definitions::FIELD_SHIPPING_PRICING_CURRENCY;

		$post_data = $this->get_post_data();
		if(isset($post_data[$currency_field_key])) {
			$settings_currency = $post_data[$currency_field_key];
		}
		return $settings_currency;
	}

	public function get_form_fields() {
		// If needed, return the form fields for the instance
		if($this->instance_id) {
			$form_fields = $this->get_instance_form_fields();
		}
		else {
			// By default, return the "global" form fields
			return parent::get_form_fields();
		}
		return $this->add_extra_form_fields($form_fields);
	}

	/**
	 * Processes the settings for the shipping method, saving them to the database.
	 */
	public function process_admin_options() {
		$post_data = $this->get_post_data();

		// get_form_fields() will fetch the global or instance fields, automatically
		foreach($this->get_form_fields() as $key => $field) {
			if($this->get_field_type($field) != 'title') {
				try {
					$this->settings[$key] = $this->get_field_value($key, $field, $post_data);
				}
				catch (Exception $e) {
					$this->add_error($e->getMessage());
				}
			}
		}

		// Determine which filter to trigger (instance or global)
		if($this->instance_id) {
			$settings_filter = 'woocommerce_shipping_' . $this->id . '_instance_settings_values';
		}
		else {
			$settings_filter = 'woocommerce_settings_api_sanitized_fields_' . $this->id;
		}

		// Copy the settings in the instance settings. In the base class, the two
		// are mutually exclusive (i.e. either one or the other is filled and used),
		// but they have the exact same purpose. It'effectively an unnecessary
		// duplication of logic
		$this->instance_settings = $this->settings;

		return update_option($this->get_settings_key($this->get_settings_currency()),
												 apply_filters($settings_filter, $this->settings, $this));
	}

	/**
	 * Indicates if class should load settings entered for base currency, rather
	 * than the ones explicitly entered for the active currency.
	 *
	 * @return bool
	 */
	protected function should_use_base_currency_settings() {
		// If viewing the settings page for this shipping method, don't do anything
		// else and keep the settings as they are
		if(is_admin() && WC_Aelia_CS_ShippingPricing_Plugin::processing_settings() ||
			 WC_Aelia_CS_ShippingPricing_Plugin::managing_shipping_method_settings()) {
			return false;
		}

		// If we're adding a new shipping method, we should not load the base currency
		// settings, because they don't exist
		// @since 1.4.4.210624
		if(($_GET['action'] ?? '') === 'woocommerce_shipping_zone_add_method') {
			return false;
		}

		// If we reach this point, we need to determine if we should use the flat
		// rates for the specific currency (when manual prices are enabled), or fall
		// back to the default pricing, to be converted using exchange rates
		return !$this->manual_prices_enabled;
	}

	/*** Rendering methods ***/
	/**
	 * Returns the HTML for the currency selector.
	 *
	 * @return string
	 * @since 1.2.3.160516
	 */
	protected function get_currency_selector_html() {
		ob_start();
		$this->render_currency_selector();
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/**
	 * Return admin options as a html string.
	 * @return string
	 *
	 * @since 1.2.3.160516
	 */
	public function get_admin_options_html() {
		$html = '';
		$html .= $this->load_settings_page_scripts(false);
		$html .= '<div class="aelia shipping_method_settings">';
		$html .= (!empty($this->method_description)) ? wpautop($this->method_description) : '';
		$html .= $this->get_currency_selector_html();

		$html .= '<table class="form-table">';
		$html .= $this->generate_settings_html($this->get_form_fields(), false);
		$html .= '</table>';
		$html .= '</div>';

		return $html;
	}

}
