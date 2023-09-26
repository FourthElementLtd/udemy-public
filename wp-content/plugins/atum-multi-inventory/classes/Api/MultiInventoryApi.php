<?php
/**
 * The Multi Inventory's API class
 *
 * @since       1.2.4
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     AtumMultiInventory\Api
 */

namespace AtumMultiInventory\Api;

defined( 'ABSPATH' ) || die;

use AtumMultiInventory\Api\Extenders\InventoryOrders;
use AtumMultiInventory\Api\Extenders\ProductData;


class MultiInventoryApi {

	/**
	 * The singleton instance holder
	 *
	 * @var MultiInventoryApi
	 */
	private static $instance;

	/**
	 * The Multi Inventory API controllers
	 *
	 * @var array
	 */
	private $api_controllers = array(
		'atum-mi-inventories' => __NAMESPACE__ . '\Controllers\V3\InventoriesController',
	);

	/**
	 * MultiInventoryApi constructor
	 *
	 * @since 1.2.4
	 */
	private function __construct() {

		// Add the Multi Inventory controllers to the WooCommerce API (/wp-json/wc/v3).
		add_filter( 'atum/api/registered_controllers', array( $this, 'register_api_controllers' ) );

		// Load the WC API extenders.
		$this->load_extenders();

	}

	/**
	 * Register the Multi Inventory API controllers
	 *
	 * @since 1.2.4
	 *
	 * @param array $api_controllers
	 *
	 * @return array
	 */
	public function register_api_controllers( $api_controllers ) {

		return array_merge( $api_controllers, $this->api_controllers );

	}

	/**
	 * Load the ATUM Multi Inventory API extenders (all those that are extending an existing WC endpoint)
	 *
	 * @since 1.2.4
	 */
	public function load_extenders() {

		ProductData::get_instance();
		InventoryOrders::get_instance();

	}


	/****************************
	 * Instance methods
	 ****************************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return MultiInventoryApi instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
