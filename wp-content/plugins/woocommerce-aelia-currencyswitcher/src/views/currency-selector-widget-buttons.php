<?php if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\CurrencySwitcher\WC_Aelia_CurrencySwitcher;
use Aelia\WC\CurrencySwitcher\Definitions;
use Aelia\WC\CurrencySwitcher\Widgets\Currency_Selector;

$widget_args = array_merge(array(
	'before_widget' => '',
	'after_widget' => '',
	'after_title' => '',
	'before_title' => '',
	'currency_display_mode' => Currency_Selector::SHOW_CURRENCY_CODE,
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
	<!-- Currency Switcher v. <?= WC_Aelia_CurrencySwitcher::$version ?> - Currency Selector Widget (buttons) -->
	<form method="post" class="currency_switch_form">
		<?php foreach($widget_args['currency_options'] as $currency_code => $currency_name) {
			$button_css_class = 'currency_button ' . $currency_code;
			if($currency_code === $widget_args['selected_currency']) {
				$button_css_class .= ' active';
			}
			$currency_display_name = ($widget_args['currency_display_mode'] === Currency_Selector::SHOW_CURRENCY_CODE) ? $currency_code : $currency_name;
			echo '<button type="submit" name="aelia_cs_currency" value="' . esc_attr($currency_code) . '" class="' . $button_css_class . '">';
			echo esc_html($currency_display_name);
			echo '</button>';
		}
		?>
	</form>
</div>
<?php
echo $widget_args['after_widget'];
