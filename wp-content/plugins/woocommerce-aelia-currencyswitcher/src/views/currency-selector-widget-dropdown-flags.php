<?php if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\CurrencySwitcher\Currencies\Country_Flags;
use Aelia\WC\CurrencySwitcher\Definitions;
use Aelia\WC\CurrencySwitcher\Widgets\Currency_Selector;

$widget_args = array_merge(array(
	'before_widget' => '',
	'after_widget' => '',
	'after_title' => '',
	'before_title' => '',
	'currency_display_mode' => Currency_Selector::SHOW_CURRENCY_NAME,
), $widget_args);

// $widget_args is passed when widget is initialised
echo $widget_args['before_widget'];

$currency_flags = Country_Flags::get_currency_flags(array_keys($widget_args['currency_options']));

?>
<div class="widget_wc_aelia_currencyswitcher_widget flags">
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
	<!-- Currency Switcher v. <?= WC_Aelia_CurrencySwitcher::$version ?> - Currency Selector Widget (dropdown with flags) -->
	<div class="wc_aelia_cs_currency_selector dropdown_selector">
		<div class="selected_option selected_currency">
			 <div class="currency_flag">
				 <img src="<?= $currency_flags[$widget_args['selected_currency']] ?>" alt="<?= esc_attr(__('Flag', Definitions::TEXT_DOMAIN) . ' ' . $widget_args['selected_currency']) ?>" />
			</div>
			<div class="currency_name"><?php
				$currency_display_name = ($widget_args['currency_display_mode'] === Currency_Selector::SHOW_CURRENCY_CODE) ? $widget_args['selected_currency'] : $widget_args['currency_options'][$widget_args['selected_currency']];
			 	echo esc_html($currency_display_name);
			 ?></div>
		</div>
		<div class="dropdown" style="display: none;">
			<ul class="options currencies">
			<?php foreach($widget_args['currency_options'] as $currency_code => $currency_name):
				$selected_css = ($currency_code === $widget_args['selected_currency']) ? 'selected' : '';
			?>
				<li class="option currency <?= esc_attr($currency_code) ?> <?= $selected_css ?>" data-value="<?= esc_attr($currency_code) ?>" data-search_data="<?= esc_attr(json_encode([$currency_code, $currency_name])) ?>">
					<div class="currency_flag">
						<img src="<?= $currency_flags[$currency_code] ?>" alt="<?= esc_attr(__('Flag', Definitions::TEXT_DOMAIN) . ' ' . $currency_code) ?>" />
					</div>
					<div class="currency_name"><?php
						$currency_display_name = ($widget_args['currency_display_mode'] === Currency_Selector::SHOW_CURRENCY_CODE) ? $currency_code : $currency_name;
						echo esc_html($currency_display_name);
					?></div>
				</li>
			<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
<?php
echo $widget_args['after_widget'];
