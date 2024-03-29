<?php
/**
 * Compatibility class for WooCommerce 3.6.0
 *
 * @package     affiliate-for-woocommerce/includes/integration/woocommerce/compat
 * @version     1.0.0
 * @since       WooCommerce 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SA_WC_Compatibility_3_6' ) ) {

	/**
	 * Class to check WooCommerce version is greater than and equal to 3.6.0
	 */
	class SA_WC_Compatibility_3_6 extends SA_WC_Compatibility_3_5 {

		/**
		 * Function to check if WooCommerce is Greater Than And Equal To 3.6.0
		 *
		 * @return boolean
		 */
		public static function is_wc_gte_36() {
			return self::is_wc_greater_than( '3.5.8' );
		}

	}

}
