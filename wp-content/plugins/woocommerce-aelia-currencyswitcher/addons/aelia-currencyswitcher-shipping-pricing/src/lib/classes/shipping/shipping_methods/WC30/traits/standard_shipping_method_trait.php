<?php
namespace Aelia\WC\CurrencySwitcher\ShippingPricing;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

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
trait Aelia_Standard_Shipping_Method_Trait {
	/**
	 * Renders the settings screen.
	 */
	public function admin_options() {
		$this->load_settings_page_scripts();
		?>
		<div class="aelia shipping_method_settings">
			<?php echo (!empty($this->method_description)) ? wpautop($this->method_description) : ''; ?>

			<?php $this->render_currency_selector(); ?>

			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table>
		</div>
		<?php
	}
}
