<?php if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\CurrencySwitcher\WC_Aelia_CurrencySwitcher;
use Aelia\WC\CurrencySwitcher\Definitions;
use Aelia\WC\CurrencySwitcher\Widgets\Currency_Selector;

$widget_args = array_merge(array(
	'before_widget' => '',
	'after_widget' => '',
	'after_title' => '',
	'before_title' => '',
	'currency_display_mode' => Currency_Selector::SHOW_CURRENCY_NAME,
	// @since 4.12.6.210825
	'show_currency_selection_button' => false,
	'currency_selection_button_label' => __('Change Currency', Definitions::TEXT_DOMAIN),
), $widget_args);

// $widget_args is passed when widget is initialised
echo $widget_args['before_widget'];

// This wrapper is needed for widget JavaScript to work correctly
?>
<div class="widget_wc_aelia_currencyswitcher_widget">
	<?php
	// Title is set in WC_Aelia_CurrencySwitcher_Widget::widget()
	if(!empty($widget_args['title'])) {
		echo $widget_args['before_title'];
		echo apply_filters('widget_title', __($widget_args['title'], Definitions::TEXT_DOMAIN), $widget_args, $this->id_base);
		echo $widget_args['after_title'];
	}

	// Trigger an action to allow rendering elements before the selector form
	// (e.g. to show error messages)
	// @since 4.5.7.171124
	do_action('wc_aelia_cs_widget_before_currency_selector_form', $this);
	?>
	<!-- Currency Switcher v. <?= WC_Aelia_CurrencySwitcher::$version ?> - Currency Selector Widget (dropdown) -->
	<form method="post" class="currency_switch_form">
		<select class="aelia_cs_currencies" name="<?= Definitions::ARG_CURRENCY ?>">
			<?php foreach($widget_args['currency_options'] as $currency_code => $currency_name): ?>
				<option value="<?= esc_attr($currency_code) ?>" <?php selected($widget_args['selected_currency'], $currency_code) ?>><?php
					$currency_display_name = ($widget_args['currency_display_mode'] === Currency_Selector::SHOW_CURRENCY_CODE) ? $currency_code : $currency_name;
					echo esc_html($currency_display_name);
				?></option>
			<?php endforeach; ?>
		</select>
		<?php
			$currency_selection_button = '<button type="submit" class="button change_currency">' . esc_html($widget_args['currency_selection_button_label']) . '</button>';

			// If the "show_currency_selection_button" argument is not set, or false, show the "change currency" button only
			// when JavaScript is disabled. When JavaScript enabled, selecting a currency in the dropdown will automatically
			// trigger the currency switch on the frontend.

			// @since 4.12.6.210825
			// In other cases, such as the "Edit Order" page, the button must remain visible, because the currency selection
			// must be confirmed by the administrator when creating an order.
			if(!$widget_args['show_currency_selection_button']) {
				$currency_selection_button = sprintf('<noscript>%s</noscript>', $currency_selection_button);
			}
			echo $currency_selection_button;
		?>
	</form>
</div>
<?php
echo $widget_args['after_widget'];
