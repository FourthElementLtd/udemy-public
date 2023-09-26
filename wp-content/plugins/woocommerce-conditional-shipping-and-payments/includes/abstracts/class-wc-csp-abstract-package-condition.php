<?php
/**
 * WC_CSP_Condition class
 *
 * @author   SomewhereWarm <info@somewherewarm.com>
 * @package  WooCommerce Conditional Shipping and Payments
 * @since    1.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Condition class.
 *
 * @class    WC_CSP_Condition
 * @version  1.4.0
 */
class WC_CSP_Package_Condition extends WC_CSP_Condition {

	/**
	 * Find count of shipping packages.
	 *
	 * @param  array  $args
	 * @return int
	 */
	protected function get_package_count( $args ) {
		return isset( $args[ 'package_count' ] ) ? absint( $args[ 'package_count' ] ) : sizeof( WC()->shipping->get_packages() );
	}

	/**
	 * Find 1-base index of shipping package.
	 *
	 * @param  array  $args
	 * @return int
	 */
	protected function get_package_index( $args ) {

		if ( isset( $args[ 'package_index' ] ) ) {
			$index = $args[ 'package_index' ];
		} else {

			$index = 0;

			if ( 1 === $this->get_package_count() ) {
				$index = 1;
			} else {

				$package_hash      = md5( json_encode( $package ) );
				$shipping_packages = WC()->shipping->get_packages();
				$loop              = 1;

				foreach ( $shipping_packages as $shipping_package ) {
					if ( $package_hash === md5( json_encode( $shipping_package ) ) ) {
						$index = $loop;
						break;
					}
					$loop++;
				}
			}
		}

		return $index;
	}
}
