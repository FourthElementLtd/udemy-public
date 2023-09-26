<?php
/**
 * Class to hack the WC Product props
 *
 * @package     AtumMultiInventory\Inc
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @since       1.4.2
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\MultiInventory;

final class ProductProps {

	/**
	 * The singleton instance holder
	 *
	 * @var ProductProps
	 */
	private static $instance;

	/**
	 * ProductData singleton constructor
	 *
	 * @since 1.4.2
	 */
	private function __construct() {

		// Hack the WC's get_prop methods to return the right multi-inventory props.
		$props = (array) apply_filters( 'atum/multi_inventory/mi_props', array( 'stock_quantity', 'stock_status', 'manage_stock' ) );

		foreach ( $props as $prop ) {

			if ( is_callable( array( $this, "get_{$prop}" ) ) ) {
				add_filter( "woocommerce_product_get_{$prop}", array( $this, "get_{$prop}" ), PHP_INT_MAX, 2 );
				add_filter( "woocommerce_product_variation_get_{$prop}", array( $this, "get_{$prop}" ), PHP_INT_MAX, 2 );
			}

		}

		// Add the MI product props to be saved with ATUM Data Store columns.
		add_filter( 'atum/data_store/columns', array( $this, 'add_data_store_mi_props' ) );
		add_filter( 'atum/data_store/allow_null_columns', array( $this, 'add_data_store_allow_null_mi_props' ) );
		add_filter( 'atum/data_store/yes_no_columns', array( $this, 'add_data_store_yes_no_mi_props' ) );

	}

	/**
	 * Add the MI product props to the ATUM data store
	 *
	 * @since 1.3.4
	 *
	 * @param array $atum_props
	 *
	 * @return array
	 */
	public function add_data_store_mi_props( $atum_props ) {

		$atum_props = array_merge( $atum_props, [
			'multi_inventory',
			'inventory_iteration',
			'inventory_sorting_mode',
			'expirable_inventories',
			'price_per_inventory',
			'selectable_inventories',
			'selectable_inventories_mode',
		] );

		return $atum_props;
	}

	/**
	 * Add the MI props that allow NULL to the ATUM data store
	 *
	 * @since 1.3.4
	 *
	 * @param array $atum_props
	 *
	 * @return array
	 */
	public function add_data_store_allow_null_mi_props( $atum_props ) {

		$atum_props = array_merge( $atum_props, [
			'multi_inventory',
			'inventory_iteration',
			'inventory_sorting_mode',
			'expirable_inventories',
			'price_per_inventory',
			'selectable_inventories',
			'selectable_inventories_mode',
		] );

		return $atum_props;
	}

	/**
	 * Add the MI props that handle yes/no values to the ATUM data store
	 *
	 * @since 1.3.4
	 *
	 * @param array $atum_props
	 *
	 * @return array
	 */
	public function add_data_store_yes_no_mi_props( $atum_props ) {

		$atum_props = array_merge( $atum_props, [
			'multi_inventory',
			'expirable_inventories',
			'price_per_inventory',
			'selectable_inventories',
		] );

		return $atum_props;
	}

	/**
	 * Hack tue WC's get_prop method for stock_quantity
	 *
	 * @since 1.0.0
	 *
	 * @param float       $stock
	 * @param \WC_Product $product_data
	 *
	 * @return float
	 */
	public function get_stock_quantity( $stock, $product_data ) {

		// Only need to calculate the stock on frontend, so allow if not is_admin or if is a non atum ajax call.
		if ( apply_filters( 'atum/multi_inventory/bypass_mi_get_stock_quantity', Helpers::bypass_product_properties_filters(), $stock, $product_data ) ) {
			return $stock;
		}

		// Do not apply to products that do not have the multi-inventory enabled.
		if ( 'yes' !== Helpers::get_product_multi_inventory_status( $product_data ) || ! Helpers::is_product_multi_inventory_compatible( $product_data ) ) {
			return $stock;
		}

		$product_id = $product_data->get_id();

		$cache_key      = AtumCache::get_cache_key( 'product_stock_quantity', $product_id );
		$stock_quantity = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $stock_quantity;
		}

		$inventories = Helpers::get_product_inventories_sorted( $product_id );

		$stock_quantity = 0;

		if ( ! empty( $inventories ) ) {

			$inventory_iteration = Helpers::get_product_inventory_iteration( $product_id );
			$stock_total         = 0;

			foreach ( $inventories as $inventory ) {

				if ( $inventory->is_main() ) {
					$inventory->set_stock_status();
				}

				if ( $inventory->is_sellable() ) {

					// If we find one of the inventories as Unmanaged by WC and set to "In Stock", this will get all the purchases.
					if ( 'no' === $inventory->manage_stock && 'instock' === $inventory->stock_status ) {
						$stock_total = NULL;
						break;
					}

					// For those not "use_next", take only the first stockable inventory stock.
					if ( 'use_next' !== $inventory_iteration && 'no' === Helpers::get_product_selectable_inventories( $product_id ) ) {
						$stock_total = 0 < $inventory->stock_quantity ? $inventory->stock_quantity : 0;
						break;
					}

					if ( in_array( $inventory->stock_status, [ 'instock', 'onbackorder' ] ) ) {
						$stock_total += 0 < $inventory->get_available_stock() ? $inventory->get_available_stock() : 0;
					}

				}

			}

			$stock_quantity = $stock_total;

		}

		$stock_quantity = apply_filters( 'atum/multi_inventory/get_stock_quantity', $stock_quantity, $product_id, $inventories );
		AtumCache::set_cache( $cache_key, $stock_quantity, ATUM_MULTINV_TEXT_DOMAIN );

		return $stock_quantity;

	}

	/**
	 * Hack que WC's get_prop method for stock_status
	 *
	 * @since 1.0.0
	 *
	 * @param string   $stock_status
	 * @param \WC_Data $product_data
	 *
	 * @return string
	 */
	public function get_stock_status( $stock_status, $product_data ) {

		// Only need to calculate the stock on frontend, so allow if not is_admin or if is a non atum ajax call.
		if ( apply_filters( 'atum/multi_inventory/bypass_mi_get_stock_status', Helpers::bypass_product_properties_filters(), $stock_status, $product_data ) ) {
			return $stock_status;
		}

		$product_id = $product_data->get_id();

		$cache_key            = AtumCache::get_cache_key( 'product_stock_status', $product_id );
		$product_stock_status = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $product_stock_status;
		}

		// product is_visible for parent products.
		if (
			$product_data instanceof \WC_Product && 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' )
			&& in_array( $product_data->get_type(), MultiInventory::get_compatible_parent_types(), TRUE )
		) {

			$stock_status = 'outofstock';
			$children     = $product_data->get_children();

			foreach ( $children as $child_id ) {

				$product = AtumHelpers::get_atum_product( $child_id );

				if ( $product ) {
					$new_status = $this->get_stock_status( $product->get_stock_status(), $product );

					if ( 'instock' === $new_status ) {
						return $new_status;
					}
					elseif ( 'onbackorder' === $new_status ) {
						$stock_status = $new_status;
					}
				}

			}

			return $stock_status;

		}

		// Do not apply to products that do not have the multi-inventory enabled.
		if ( 'yes' !== Helpers::get_product_multi_inventory_status( $product_data ) || ! Helpers::is_product_multi_inventory_compatible( $product_data ) ) {
			return $stock_status;
		}

		$inventories = Helpers::get_product_inventories_sorted( $product_id );

		if ( ! empty( $inventories ) ) {

			$inventory_iteration = Helpers::get_product_inventory_iteration( $product_id );

			// Iterate all the inventories in the sorting mode order until find the first stockable and in stock (if any).
			foreach ( $inventories as $inventory ) {

				// In case some inventory reached the "Out of stock threshold" and is not out of stock.
				if ( $inventory->is_main() ) {
					$inventory->set_stock_status();
				}

				if ( $inventory->is_sellable() ) {

					// For those not "use_next", take only the first stockable inventory stock.
					if ( 'use_next' !== $inventory_iteration ) {
						$product_stock_status = $inventory->stock_status;
						break;
					}

					// If we find one of the inventories as Unmanaged by WC and set to "In Stock", this will get all the purchases.
					if ( 'no' === $inventory->manage_stock && 'instock' === $inventory->stock_status ) {
						$product_stock_status = 'instock';
						break;
					}

					$stock_status = $inventory->stock_status;

					if ( in_array( $stock_status, [ 'instock', 'onbackorder' ], TRUE ) ) {
						$product_stock_status = $stock_status;
						break;
					}

				}

			}

			if ( ! $product_stock_status ) {
				$product_stock_status = 'outofstock';
			}

		}

		// No valid inventories found --> Out of Stock.
		$product_stock_status = $product_stock_status ?: 'outofstock';

		AtumCache::set_cache( $cache_key, $product_stock_status, ATUM_MULTINV_TEXT_DOMAIN );

		return $product_stock_status;

	}

	/**
	 * Hack que WC's get_prop method for manage_stock
	 *
	 * @since 1.0.7
	 *
	 * @param bool        $manage_stock
	 * @param \WC_Product $product_data
	 *
	 * @return bool
	 */
	public function get_manage_stock( $manage_stock, $product_data ) {

		// Only need to calculate the stock on frontend, so allow if not is_admin or if is a non atum ajax call.
		if ( apply_filters( 'atum/multi_inventory/bypass_mi_get_manage_stock', Helpers::bypass_product_properties_filters(), $manage_stock, $product_data ) ) {
			return $manage_stock;
		}

		// In some cases (f.e. when trying to change the manage_stock value through the API),
		// the manage_stock has just changed and we are not aware of it yet, so just return the new value.
		$changes = $product_data->get_changes();

		if ( ! empty( $changes ) && isset( $changes['manage_stock'] ) ) {
			add_filter( 'atum/multi_inventory/bypass_mi_get_manage_stock', '__return_true' ); // Once we bypass this once, bypass every future access here.
			return $changes['manage_stock'];
		}

		$product_id = $product_data->get_id();

		// Do not apply to products that do not have the multi-inventory enabled.
		if ( 'yes' !== Helpers::get_product_multi_inventory_status( $product_data ) || ! Helpers::is_product_multi_inventory_compatible( $product_data ) ) {
			return $manage_stock;
		}

		$cache_key    = AtumCache::get_cache_key( 'product_manage_stock', $product_id );
		$manage_stock = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return wc_string_to_bool( $manage_stock );
		}

		$inventories = Helpers::get_product_inventories_sorted( $product_id );

		if ( ! empty( $inventories ) ) {

			$manage_stock = 'yes';

			$inventory_iteration = Helpers::get_product_inventory_iteration( $product_id );

			foreach ( $inventories as $inventory ) {

				if ( $inventory->is_main() ) {
					$inventory->set_stock_status();
				}

				if ( $inventory->is_sellable() ) {

					if ( 'use_next' !== $inventory_iteration ) {
						$manage_stock = $inventory->manage_stock;
						break;
					}

					// If we find one of the inventories as Unmanaged by WC and set to "In Stock",
					// this will get all the purchases after the previous inventories have been emptied.
					if ( 'no' === $inventory->manage_stock && in_array( $inventory->stock_status, [ 'instock', 'onbackorder' ], TRUE ) ) {
						$manage_stock = 'no';
						break;
					}

				}

			}

		}

		AtumCache::set_cache( $cache_key, $manage_stock, ATUM_MULTINV_TEXT_DOMAIN );

		return wc_string_to_bool( $manage_stock );

	}


	/*******************
	 * Instance methods
	 *******************/

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
	 * @return ProductProps instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
