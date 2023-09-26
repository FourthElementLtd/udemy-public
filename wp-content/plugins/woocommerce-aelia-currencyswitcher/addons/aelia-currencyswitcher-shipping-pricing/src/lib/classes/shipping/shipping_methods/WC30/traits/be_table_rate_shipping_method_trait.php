<?php
namespace Aelia\WC\CurrencySwitcher\ShippingPricing;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\CurrencySwitcher\ShippingPricing\Definitions;
use Aelia\WC\CurrencySwitcher\ShippingPricing\WC_Aelia_CS_ShippingPricing_Plugin;

/**
 * A template class that will be used to extend the Be_Table_Rate_Metod
 * to handle settings for multiple currencies. The class will be parsed using an
 * eval() statement, after having been modified to extend the target shipping
 * method class.
 *
 * Example
 * Target class: WC_Shipping_Flat_Rate
 * New class declaration: Aelia_WC_Shipping_Flat_Rate extends WC_Shipping_Flat_Rate
 *
 * @see Aelia\WC\CurrencySwitcher\ShippingPricing\WC_Aelia_CS_ShippingPricing_Plugin::generate_shipping_method_class().
 * @since 1.3.0.170510
 */
trait Aelia_BE_Table_Rate_Method_Trait {
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

		$this->set_hooks();
	}

	public function init() {
		$this->init_settings();
		parent::init();
		$this->instance_form_fields = $this->add_extra_form_fields($this->instance_form_fields);
	}

	protected function set_hooks() {
		add_filter('betrs_processed_table_rates_settings', array($this, 'betrs_processed_table_rates_settings'), 10, 1);
		add_filter('betrs_shipping_cost_options', array($this, 'betrs_shipping_cost_options'), 10, 1);
		add_filter('betrs_shipping_cost_units_every', array($this, 'betrs_shipping_cost_units_every'), 10, 1);
	}

	/**
	 * Given a settings key, it returns its currency-specific counterpart.
	 *
	 * SPECIAL CASE
	 * When the active currency is the base one, and no settings are present for it,
	 * the original settings are cloned into the base currency ones. This allows the
	 * BE Table Rates plugin the settings that were saved before the Shipping Pricing
	 * Addon was enabled.
	 *
	 * @param string $original_settings_key
	 * @return string
	 * @since 1.4.0.200225
	 * @link https://aelia.freshdesk.com/a/tickets/85175
	 */
	protected function get_betrs_settings_key($original_settings_key) {
		// If we are viewing the settings page for this shipping method, or the method
		// is not enabled don't do anything else and keep the settings as they are
		if(!$this->is_enabled() ||
			 WC_Aelia_CS_ShippingPricing_Plugin::managing_shipping_method_settings() ||
			 (is_admin() && WC_Aelia_CS_ShippingPricing_Plugin::processing_settings())) {
			$currency = $this->active_currency();
		}
		else {
			$currency = $this->manual_prices_enabled ? $this->active_currency() : $this->base_currency();
		}

		// If the settings for the base currency are empty, clone the original settings entered before
		// the Shipping Pricing Addon was enabled
		//
		// @since 1.4.0.200225
		// @link https://aelia.freshdesk.com/a/tickets/85175
		$new_settings_key = $this->get_option_key_by_currency($original_settings_key, $currency);

		// If the settings for the base currency are empty, clone the original settings entered before
		// the Shipping Pricing Addon was enabled
		// @since 1.4.0.200225
		if($this->active_currency() === $this->base_currency() && (get_option($new_settings_key, null) === null)) {
			update_option($new_settings_key, get_option($original_settings_key), false);
		}

		return $new_settings_key;
	}

	/**
	 * Returns the option name to save and load the BE Table Rates settings.
	 *
	 * @return string
	 */
	public function get_options_save_name() {
		return apply_filters('betrs_instance_options_save_name', $this->get_betrs_settings_key($this->options_save_name), $this);
	}

	/**
	 * Returns the option name to save and load the BE Table Rates shipping method conditions.
	 *
	 * @return string
	 * @since 1.4.0.200225
	 */
	public function get_method_conditions_save_name() {
		return apply_filters('betrs_instance_method_conditions_save_name', $this->get_betrs_settings_key($this->m_conds_save_name), $this);
	}

	/**
	 * Renders the settings screen.
	 */
	public function admin_options() {
		$this->load_settings_page_scripts();
		?>
		<div class="aelia shipping_method_settings"><?php
			$this->render_currency_selector();
			parent::admin_options();
		?></div>
		<?php
	}

	protected function add_extra_form_fields(array $form_fields = null) {
		if(!empty($form_fields) && is_array($form_fields)) {
			$this->form_fields = $form_fields;
		}

		$this->form_fields['general']['settings'][Definitions::FIELD_SHIPPING_PRICING_CURRENCY] = array(
			'title' => 'Currency',
			'type' => 'text',
			'label' => 'Currency',
			'class' => 'currency hidden',
			'default' => $this->active_currency(),
			'custom_attributes' => array(
				'readonly' => 'readonly',
			),
		);

		// Change the description for the "enabled" field when a non-base currency is
		// active. For such currencies, the "enabled" field does not disable the
		// shipping method, it simply ignores the prices entered manually
		if($this->active_currency() != $this->base_currency()) {
			$this->form_fields['general']['settings'] = array(Definitions::FIELD_MANUAL_PRICES_ENABLED => array(
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
				]),
			)) + $this->form_fields['general']['settings'];
		}

		return $this->form_fields;
	}

	/**
	 * Saves the extra information to keep track of currency-specific settings.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function betrs_processed_table_rates_settings($settings) {
		foreach($settings as $key => $data) {
			$data[Definitions::FIELD_MANUAL_PRICES_ENABLED] = isset($_POST['woocommerce_betrs_shipping_currency_manual_prices_enabled']) ? $_POST['woocommerce_betrs_shipping_currency_manual_prices_enabled'] : 0;
			$settings[$key] = $data;
		}
		return $settings;
	}

	/**
	 * Alters the options displayed by the BE Table Rates Shipping plugin, to show the correct currency.
	 *
	 * @param array $options
	 * @return array
	 */
	public function betrs_shipping_cost_options($options) {
		$options[''] = get_woocommerce_currency_symbol($this->active_currency());
		return $options;
	}

	/**
	 * Alters the options displayed by the BE Table Rates Shipping plugin, to show the correct currency.
	 *
	 * @param array $options
	 * @return array
	 * @since 1.4.0.200225
	 */
	public function betrs_shipping_cost_units_every($options) {
		$options['subtotal'] = get_woocommerce_currency_symbol($this->active_currency());
		return $options;
	}
}
