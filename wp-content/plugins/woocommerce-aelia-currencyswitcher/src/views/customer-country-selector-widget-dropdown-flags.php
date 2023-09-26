<?php if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\CurrencySwitcher\Currencies\Country_Flags;
use Aelia\WC\CurrencySwitcher\Definitions;
use Aelia\WC\CurrencySwitcher\Widgets\Currency_Selector;

$widget_args = array_merge(array(
	'before_widget' => '',
	'after_widget' => '',
	'after_title' => '',
	'before_title' => '',
	'country_display_mode' => Currency_Selector::SHOW_CURRENCY_NAME,
), $widget_args);

// $widget_args is passed when widget is initialised
echo $widget_args['before_widget'];

// Fetch all the country flags
$country_flags = Country_Flags::get_country_flags();

?>
<div class="widget_wc_aelia_currencyswitcher_widget country_switcher flags">
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
	do_action('wc_aelia_cs_widget_before_country_selector_form', $this);
	?>
	<!-- Currency Switcher v. <?= WC_Aelia_CurrencySwitcher::$version ?> - Currency Selector Widget (dropdown with flags) -->
	<div class="wc_aelia_cs_country_selector dropdown_selector">
		<div class="selected_option selected_country">
			 <div class="country_flag">
				 <img src="<?= $country_flags[$widget_args['selected_country']] ?>" alt="<?= esc_attr(__('Flag', Definitions::TEXT_DOMAIN) . ' ' . $widget_args['selected_country']) ?>" />
			</div>
			<div class="country_name"><?php
			 	echo esc_html($widget_args['countries'][$widget_args['selected_country']]);
			 ?></div>
		</div>
		<div class="dropdown" style="display: none;">
			<div class="search-container">
				<input type="text" class="search" placeholder="<?= esc_attr__('Search...', Definitions::TEXT_DOMAIN) ?>">
			</div>
			<ul class="options countries">
			<?php foreach($widget_args['countries'] as $country_code => $country_name):
				$selected_css = ($country_code === $widget_args['selected_country']) ? 'selected' : '';
			?>
				<li class="option country <?= esc_attr($country_code) ?> <?= $selected_css ?>" data-value="<?= esc_attr($country_code) ?>" data-search_data="<?= esc_attr(json_encode([$country_code, $country_name])) ?>">
					<div class="country_flag">
						<img src="<?= $country_flags[$country_code] ?>" alt="<?= esc_attr(__('Flag', Definitions::TEXT_DOMAIN) . ' ' . $country_code) ?>" />
					</div>
					<div class="country_name"><?php
						echo esc_html($country_name);
					?></div>
				</li>
			<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
<?php
echo $widget_args['after_widget'];
