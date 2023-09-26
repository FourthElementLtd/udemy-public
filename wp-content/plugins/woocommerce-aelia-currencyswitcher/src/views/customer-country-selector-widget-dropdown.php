<?php if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\CurrencySwitcher\WC_Aelia_CurrencySwitcher;
use Aelia\WC\CurrencySwitcher\Definitions;

$widget_args = array_merge(array(
	'before_widget' => '',
	'after_widget' => '',
	'after_title' => '',
	'before_title' => '',
), $widget_args);

// $widget_args is passed when widget is initialised
echo $widget_args['before_widget'];

// This wrapper is needed for widget JavaScript to work correctly
?>
<div class="currency_switcher widget_wc_aelia_country_selector_widget">
	<?php
	// Title is set in WC_Aelia_Customer_Country_Selector_Widget::widget()
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
	<!-- Currency Switcher v. <?= WC_Aelia_CurrencySwitcher::$version ?> - Country Selector Widget (dropdown) -->
	<form method="post" class="country_selector_form">
		<select class="countries" name="<?= Definitions::ARG_CUSTOMER_COUNTRY ?>">
			<?php foreach($widget_args['countries'] as $country_code => $country_name): ?>
				<option value="<?= esc_attr($country_code) ?>" <?php selected($widget_args['selected_country'], $country_code) ?>><?php
					echo esc_html($country_name);
				?></option>
			<?php endforeach; ?>
		</select>
		<noscript>
			<?php
				// Shpw the "change country" button only when JavaScript is disabled. When it's enabled, selecting a
				// country in the dropdown will automatically trigger the country switch
			?>
			<button type="submit" class="button change_country"><?= esc_html__('Change Country', Definitions::TEXT_DOMAIN) ?></button>
		</noscript>
	</form>
</div>
<?php
echo $widget_args['after_widget'];
