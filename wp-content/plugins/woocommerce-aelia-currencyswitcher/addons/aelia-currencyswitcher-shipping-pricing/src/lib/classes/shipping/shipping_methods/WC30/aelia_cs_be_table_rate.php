<?php
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \Aelia\WC\CurrencySwitcher\ShippingPricing\Aelia_Base_Shipping_Method_Trait;
use \Aelia\WC\CurrencySwitcher\ShippingPricing\Aelia_BE_Table_Rate_Method_Trait;


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
class Aelia_CS_BE_Table_Rate_Method extends BE_Table_Rate_Method {
	use Aelia_Base_Shipping_Method_Trait,
			Aelia_BE_Table_Rate_Method_Trait {
		Aelia_BE_Table_Rate_Method_Trait::__construct insteadof Aelia_Base_Shipping_Method_Trait;
		Aelia_BE_Table_Rate_Method_Trait::init insteadof Aelia_Base_Shipping_Method_Trait;
		Aelia_BE_Table_Rate_Method_Trait::add_extra_form_fields insteadof Aelia_Base_Shipping_Method_Trait;
	}
}