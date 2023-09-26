<?php if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\CurrencySwitcher\Definitions;
use Aelia\WC\CurrencySwitcher\WC_Aelia_CurrencySwitcher;
?>

<!-- Variation - Currency Switcher -->
<div class="wc_aelia_cs_product_prices pricing variation_prices clearfix"><!-- WC 2.3+ Wrapper - START -->
<?php
	// This view is designed to be loaded by an instance of
	// WC_Aelia_CurrencyPrices_Manager. Such instance is what "$this" and "self"
	// refer to.
	$currencyprices_manager = $this;
	$enabled_currencies = $currencyprices_manager->enabled_currencies();
	$base_currency = WC_Aelia_CurrencySwitcher::settings()->base_currency();

	$post_id = $currencyprices_manager->current_post->ID;
	$loop = $currencyprices_manager->loop_idx;

	echo '<div class="wc_aelia_cs_product_prices clearfix hide_if_variable-subscription">';
	// Display header of currency pricing section
	include('product_currencyprices_header.php');
	?>
	<div class="prices_wrapper clearfix">
		<div class="regular_prices"><?php
			$product_regular_prices = $currencyprices_manager->get_variation_regular_prices($post_id);
			// Outputs the Product Variation prices in the different Currencies
			foreach($enabled_currencies as $currency) {
				if($currency == $base_currency) {
					continue;
				}

				woocommerce_wp_text_input(array('id' => WC_Aelia_CurrencyPrices_Manager::FIELD_VARIABLE_REGULAR_CURRENCY_PRICES . "[$loop][$currency]",
																				'class' => 'wc_input_price short',
																				'label' => __('Regular Price', 'woocommerce') . ' (' . $currency . ')',
																				'type' => 'number',
																				'value' => get_value($currency, $product_regular_prices, null),
																				'placeholder' => __('Auto',
																														Definitions::TEXT_DOMAIN),
																				'custom_attributes' => array('step' => 'any',
																																		 'min' => '0'
																																		 ),
																				)
																	);
			}
		?></div>
		<div class="sale_prices"><?php
			$product_sale_prices = $currencyprices_manager->get_variation_sale_prices($post_id);
			// Outputs the Product Variation Sale prices in the different Currencies
			foreach($enabled_currencies as $currency) {
				if($currency == $base_currency) {
					continue;
				}

				woocommerce_wp_text_input(array('id' => WC_Aelia_CurrencyPrices_Manager::FIELD_VARIABLE_SALE_CURRENCY_PRICES . "[$loop][$currency]",
																				'class' => 'wc_input_price short',
																				'label' => __('Sale Price', 'woocommerce') . ' (' . $currency . ')',
																				'type' => 'number',
																				'value' => get_value($currency, $product_sale_prices, null),
																				'placeholder' => __('Auto',
																														Definitions::TEXT_DOMAIN),
																				'custom_attributes' => array('step' => 'any',
																																		 'min' => '0'
																																		 ),
																				)
																	);
			}
		?></div> <!-- .sale_prices -->
	</div> <!-- .prices_wrapper -->
</div>

</div><!-- WC 2.3+ Wrapper - END -->
