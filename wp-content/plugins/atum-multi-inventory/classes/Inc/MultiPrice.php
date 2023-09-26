<?php
/**
 * Handle multiple prices for distinct product inventories
 *
 * @package     AtumMultiInventory\Inc
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @since       1.0.1
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Models\Products\AtumProductTrait;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumMultiInventory\Models\Inventory;
use AtumMultiInventory\MultiInventory;
use WC_Bundled_Item;
use WC_Subscriptions_Product;


class MultiPrice {

	/**
	 * The singleton instance holder
	 *
	 * @var MultiPrice
	 */
	private static $instance;

	/**
	 * Store the multi-price items discounts (edited) here.
	 *
	 * @var array
	 */
	private $discounts = [];

	/**
	 * Store all the discounts applied by WooCoomerce to check the limit usage.
	 *
	 * @var array
	 */
	private $wc_discounts = [];

	/**
	 * When displaying a bundle product that contains bundled items with MI, we need to hack their prices.
	 *
	 * @var array
	 */
	private $bundled_items = [];

	/**
	 * When the selectable inventories is enabled, we'll store them here.
	 *
	 * @var array
	 */
	private $selected_inventories = [];

	/**
	 * Prefix used for the multi-price transient
	 */
	const TRANSIENT_PREFIX = 'atmmi_';

	/**
	 * MultiPrice singleton constructor
	 *
	 * @since 1.0.1
	 */
	private function __construct() {

		// Hack the WC's get_prop methods to return the multi-inventory price props.
		$props = (array) apply_filters( 'atum/multi_inventory/multi_price_props', array(
			'regular_price',
			'sale_price',
			'date_on_sale_from',
			'date_on_sale_to',
			'price',
		) );

		foreach ( $props as $prop ) {

			if ( is_callable( array( $this, "get_{$prop}" ) ) ) {
				add_filter( "woocommerce_product_get_{$prop}", array( $this, "get_{$prop}" ), PHP_INT_MAX - 1, 2 );
				add_filter( "woocommerce_product_variation_get_{$prop}", array( $this, "get_{$prop}" ), PHP_INT_MAX - 1, 2 );
			}

		}

		// Change the WC's cart item before inserting it.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'add_cart_product_price_filter' ), PHP_INT_MAX, 1 );
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'remove_cart_product_price_filter' ), PHP_INT_MAX, 1 );

		// Hack the WC's cart totals with the inventory prices.
		add_filter( 'woocommerce_cart_product_subtotal', array( $this, 'cart_product_subtotal' ), PHP_INT_MAX, 4 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'cart_item_price' ), PHP_INT_MAX, 3 );
		add_filter( 'woocommerce_widget_cart_item_quantity', array( $this, 'cart_item_price' ), PHP_INT_MAX, 3 );

		// Hack the WC's discounts.
		add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'coupon_discount_amount' ), PHP_INT_MAX, 5 );

		// Show a range of prices when multiple inventory prices are available.
		add_filter( 'woocommerce_get_price_html', array( $this, 'get_price_html' ), PHP_INT_MAX - 1, 2 );

		// Add cron for recalculate the entire Price transient.
		add_filter( 'atum/queues/recurring_hooks', array( $this, 'add_transient_recalc_hook' ) );

		// Remove the products in the order from the Price Transient.
		add_action( 'atum/multi_inventory/reduce_order_stock', array( $this, 'update_price_transient_for_orders' ), 10 );
		add_action( 'atum/multi_inventory/restore_order_stock', array( $this, 'update_price_transient_for_orders' ), 10 );
		add_action( 'atum/multi_inventory/after_atum_order_change_stock_levels', array( $this, 'update_price_transient_for_orders' ), 10 );

		// Show variation price when main inventory has not stock.
		add_filter( 'woocommerce_show_variation_price', array( $this, 'show_variation_price' ), 10, 3 );

		// WooCommerce Product Bundles compatibility.
		if ( class_exists( '\WC_Product_Bundle' ) && ! is_admin() ) {

			add_filter( 'woocommerce_bundled_items', array( $this, 'maybe_register_loaded_bundled_items' ), 10, 2 );
			add_filter( 'woocommerce_order_amount_line_subtotal', array( $this, 'maybe_recalculate_order_item_subtotal_for_bundled_items' ), 10, 5 );
			// add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'maybe_adjust_bundled_item_subtotal_before_save' ), 11, 4 ); // Priority must be higher than the one used by WC Bundles.
			add_action( 'woocommerce_bundled_product_price_filters_added', array( $this, 'change_bundled_items_price_filters_priority' ) );
			add_action( 'woocommerce_bundled_product_price_filters_removed', array( $this, 'remove_bundled_items_price_filters' ) );

		}

		// Admin hooks.
		if ( is_admin() ) {

			add_action( 'atum/product_data/after_save_product_meta_boxes', array( $this, 'refresh_price_transient' ), 11, 2 );
			add_action( 'atum/product_data/after_save_product_variation_meta_boxes', array( $this, 'refresh_price_transient' ), 11, 2 );

			// Force transient updating after price related variables changed.
			add_action( 'update_option', array( $this, 'maybe_delete_price_transient_after_options_chg' ), 10, 3 );
			add_action( 'atum/multi_inventory/expired_inventory', array( $this, 'update_price_transient_after_expire_inventory' ), 10 );
			add_action( 'atum/ajax/tool_mi_migrate_regions', array( $this, 'delete_price_transient' ) );
			add_action( 'atum/ajax/tool_mi_migrate_countries', array( $this, 'delete_price_transient' ) );
			add_action( 'atum/ajax/tool_mi_migrate_shipping_zones', array( $this, 'delete_price_transient' ) );
			add_action( 'atum/out_stock_threshold/after_rebuild', array( $this, 'delete_price_transient' ) );

			// As WC has stopped WP if no chages were made, this hook only will be executed if WC taxes changed.
			add_action( 'woocommerce_tax_rates_save_changes', array( $this, 'delete_price_transient' ), 11 );

			// Update list table changed products in the price transient.
			add_action( 'atum/ajax/after_update_list_data', array( $this, 'update_price_transient_after_sc' ), 100 );
			add_action( 'atum/multi_inventory/after_save_product_data', array( $this, 'update_price_transient_for_product' ) );

			// Support bulk and quick edit products fot the Price Transient.
			add_action( 'woocommerce_product_quick_edit_save', array( $this, 'update_price_transient_for_product' ) );
			add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'update_price_transient_for_product' ) );

			// Change the purchase price for PO if has MultiPrice and the added inventory is not the first in order.
			add_filter( 'atum/order/add_product/price', array( $this, 'maybe_change_item_purchase_price' ), 12, 4 );

			// Change the order's price in product with multi price activated.
			add_filter( 'woocommerce_ajax_order_item', array( $this, 'use_wc_first_inventory_price' ), 10, 2 );
			add_filter( 'atum/order/add_product/price', array( $this, 'use_atum_first_inventory_price' ), 11, 4 );

		}

	}

	/**
	 * Hack que WC's get_prop method for price
	 *
	 * @since 1.0.1
	 *
	 * @param string   $price
	 * @param \WC_Data $product_data
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function get_price( $price, $product_data ) {

		if ( is_admin() && ! ( wp_doing_ajax() && isset( $_POST['action'] ) && 'woocommerce_add_order_item' === $_POST['action'] ) ) {
			return $price;
		}

		$product_id = $product_data->get_id();

		// Do not apply to products that do not have Price per Inventory enabled.
		if ( ! Helpers::has_multi_price( $product_id ) ) {
			return $price;
		}

		if ( class_exists( '\WC_Product_Bundle' ) && array_key_exists( $product_id, $this->bundled_items ) ) {
			$bundled_item = $this->bundled_items[ $product_id ];

			if ( $bundled_item->get_discount() ) {
				$price = Helpers::get_first_inventory_prop( 'regular_price', $price, $product_id );
				return $price;
			}
		}

		$price = Helpers::get_first_inventory_prop( 'price', $price, $product_id );

		return Helpers::get_first_inventory_prop( 'price', $price, $product_id );

	}

	/**
	 * Hack que WC's get_prop method for regular_price
	 *
	 * @since 1.0.1
	 *
	 * @param string   $regular_price
	 * @param \WC_Data $product_data
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function get_regular_price( $regular_price, $product_data ) {

		if ( is_admin() ) {
			return $regular_price;
		}

		$product_id = $product_data->get_id();

		// Do not apply to products that do not have Price per Inventory enabled.
		if ( ! Helpers::has_multi_price( $product_id ) ) {
			return $regular_price;
		}

		return Helpers::get_first_inventory_prop( 'regular_price', $regular_price, $product_id );

	}

	/**
	 * Hack que WC's get_prop method for sale_price
	 *
	 * @since 1.0.1
	 *
	 * @param string   $sale_price
	 * @param \WC_Data $product_data
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function get_sale_price( $sale_price, $product_data ) {

		if ( is_admin() ) {
			return $sale_price;
		}

		$product_id = $product_data->get_id();

		// Do not apply to products that do not have Price per Inventory enabled.
		if ( ! Helpers::has_multi_price( $product_id ) ) {
			return $sale_price;
		}

		return Helpers::get_first_inventory_prop( 'sale_price', $sale_price, $product_id );

	}

	/**
	 * Hack que WC's get_prop method for date_on_sale_from
	 *
	 * @since 1.0.1
	 *
	 * @param string   $date_sale_from
	 * @param \WC_Data $product_data
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function get_date_on_sale_from( $date_sale_from, $product_data ) {

		if ( is_admin() ) {
			return $date_sale_from;
		}

		$product_id = $product_data->get_id();

		// Do not apply to products that do not have Price per Inventory enabled.
		if ( ! Helpers::has_multi_price( $product_id ) ) {
			return $date_sale_from;
		}

		return Helpers::get_first_inventory_prop( 'date_on_sale_from', $date_sale_from, $product_id );

	}

	/**
	 * Hack que WC's get_prop method for date_on_sale_to
	 *
	 * @since 1.0.1
	 *
	 * @param string   $date_sale_to
	 * @param \WC_Data $product_data
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function get_date_on_sale_to( $date_sale_to, $product_data ) {

		if ( is_admin() ) {
			return $date_sale_to;
		}

		$product_id = $product_data->get_id();

		// Do not apply to products that do not have Price per Inventory enabled.
		if ( ! Helpers::has_multi_price( $product_id ) ) {
			return $date_sale_to;
		}

		return Helpers::get_first_inventory_prop( 'date_on_sale_to', $date_sale_to, $product_id );

	}

	/**
	 * Hack the WC's cart coupon discount amount for products with the "Price per Inventory" option enabled
	 *
	 * @since 1.0.1
	 *
	 * @param float      $discount           The discount to apply.
	 * @param float      $price_to_discount  The price before the discount.
	 * @param array      $item               The item to apply the discount to.
	 * @param bool       $false              Always is false.
	 * @param \WC_Coupon $coupon             Coupon object. Passed through filters.
	 *
	 * @return float
	 *
	 * @throws \Exception
	 */
	public function coupon_discount_amount( $discount, $price_to_discount, $item, $false, $coupon ) {

		$product_id  = $item['variation_id'] ?: $item['product_id'];
		$coupon_code = $coupon->get_code();

		// Store all the coupons applied by WC.
		$this->wc_discounts[] = array(
			'coupon'   => $coupon_code,
			'item'     => $item,
			'discount' => $discount,
		);

		// Only need to hack the discounts for the products that have MI and Multi-Price enabled.
		if ( ! Helpers::has_multi_price( $product_id ) ) {
			return $discount;
		}

		$cache_key      = AtumCache::get_cache_key( "coupon_discount_amount_$coupon_code", [ $product_id, $discount ] );
		$cache_discount = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, $false, $has_cache );

		if ( ! $has_cache ) {

			// If a MI product has no inventories, it would get the main, so no need to continue either.
			$inventories = Helpers::get_product_inventories_sorted( $product_id );

			// Check if there are selected inventories.
			$inventories = SelectableInventories::maybe_restrict_inventories_in_cart( $product_id, $inventories );

			if ( empty( $inventories ) ) {
				return $discount;
			}

			// Initialize discount only if not initialized previously (could happen with recursive coupons like cart totals).
			if ( ! isset( $this->discounts[ $coupon_code ] ) ) {
				$this->discounts[ $coupon_code ] = [];
			}

			foreach ( $inventories as $inventory ) {

				if ( ! isset( $this->discounts[ $coupon_code ][ $inventory->id ] ) ) {
					$this->discounts[ $coupon_code ][ $inventory->id ] = 0;
				}

			}

			switch ( $coupon->get_discount_type() ) {
				case 'percent':
					$discount = $this->apply_coupon_percent( $coupon, $item, $discount );
					break;

				case 'fixed_product':
					$discount = $this->apply_coupon_fixed_product( $coupon, $item, $discount );
					break;

				case 'fixed_cart':
					// The hook for the fixed cart discount is called from apply_coupon_fixed_product, so we apply it for MI.
					$discount = $this->apply_coupon_fixed_product( $coupon, $item, $discount );
					break;

				default:
					$discount = $this->apply_coupon_custom( $coupon, $item, $discount );
					break;
			}

			AtumCache::set_cache( $cache_key, $discount, ATUM_MULTINV_TEXT_DOMAIN );

		}
		else {
			$discount = $cache_discount;
		}

		return apply_filters( 'atum/multi_inventory/coupon_discount_amount', $discount, $item, $coupon );

	}

	/**
	 * Apply a discount percent coupon to a inventory multi price product assigned to a mi order item.
	 *
	 * @since 1.0.1
	 *
	 * @param \WC_Coupon $coupon    Coupon object. Passed through filters.
	 * @param array      $item      The item being used for the discount.
	 * @param float      $discount  The discount being applied before our filter.
	 *
	 * @return float
	 *
	 * @throws \Exception
	 */
	private function apply_coupon_percent( $coupon, $item, $discount ) {

		$product_id = $item['variation_id'] ?: $item['product_id'];

		// For variations, sometimes the item received does not contains the line total.
		// But not needed here, as is going to be calculated within the get_cart_item_total method.
		$item_total     = isset( $item['line_total'] ) ? $item['line_total'] : 0;
		$item_total_obj = $this->get_cart_item_total( $product_id, $item_total, $item['quantity'], wc_get_product( $product_id ), FALSE );

		// Get inventories discount from cache if set.
		$cache_key             = AtumCache::get_cache_key( 'inventories_discounts' );
		$inventories_discounts = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {
			$inventories_discounts = [];
		}

		// Check if the discount applied is greater than the multi-price total and readjust if needed.
		if ( $item_total_obj->changed ) {

			$coupon_amount = $coupon->get_amount();

			if ( $coupon_amount <= 0 ) {
				return $discount;
			}

			$limit_usage_qty = $this->get_current_usage_limit( $coupon, $item );

			// Once reached the usage limit (if any), do not continue applying discounts.
			if ( FALSE !== $limit_usage_qty && $limit_usage_qty <= 0 ) {
				$discount = 0;
			}
			// Take care of any limit of products to apply the discount.
			else {

				$coupon_code = $coupon->get_code();

				if ( ! isset( $inventories_discounts[ $coupon_code ] ) ) {

					$inventories_discounts[ $coupon_code ] = [];
				}

				$used_qty      = $discount = 0;
				$coupon_amount = $coupon->get_amount();

				foreach ( $item_total_obj->prices as $inventory_id => $price_obj ) {

					$inventory_price  = $price_obj['qty'] * $price_obj['price'];
					$discounted_price = $inventory_price - $this->get_inventory_discount( $inventory_id );
					$inventory_price  = 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ? $discounted_price : $inventory_price;

					$qty_to_discount    = FALSE === $limit_usage_qty ? $price_obj['qty'] : min( $price_obj['qty'], $limit_usage_qty - $used_qty );
					$inventory_discount = min( $discounted_price, ( $inventory_price * $coupon_amount ) / 100 );

					if ( ! isset( $inventories_discounts[ $coupon_code ][ $inventory_id ] ) ) {
						$inventories_discounts[ $coupon_code ][ $inventory_id ] = 0;
					}

					$inventories_discounts[ $coupon_code ][ $inventory_id ] += $inventory_discount;

					$this->discounts[ $coupon_code ][ $inventory_id ] += wc_add_number_precision( $inventory_discount );
					$discount += $inventory_discount;
					$used_qty += $qty_to_discount;

					if ( FALSE !== $limit_usage_qty && $used_qty >= $limit_usage_qty ) {
						break;
					}

				}

			}

			$discount = wc_round_discount( min( $item_total_obj->item_total, $discount ), 2 );

		}

		AtumCache::set_cache( $cache_key, $inventories_discounts, ATUM_MULTINV_TEXT_DOMAIN );

		return $discount;

	}

	/**
	 * Apply a discount fixed quantity per product coupon to a inventory multi price product assigned to a mi order item.
	 *
	 * @since 1.0.1
	 *
	 * @param \WC_Coupon $coupon    Coupon object. Passed through filters.
	 * @param array      $item      The item being used for the discount.
	 * @param float      $discount  The discount being applied before our filter.
	 *
	 * @return int
	 *
	 * @throws \Exception
	 */
	private function apply_coupon_fixed_product( $coupon, $item, $discount ) {

		$product_id = $item['variation_id'] ?: $item['product_id'];

		// For variations, sometimes the item received does not contains the line total.
		// But not needed here, as is going to be calculated within the get_cart_item_total method.
		$item_total     = isset( $item['line_total'] ) ? $item['line_total'] : 0;
		$item_total_obj = $this->get_cart_item_total( $product_id, $item_total, $item['quantity'], wc_get_product( $product_id ), FALSE );

		// Get inventories discount from cache if set.
		$cache_key             = AtumCache::get_cache_key( 'inventories_discounts' );
		$inventories_discounts = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {
			$inventories_discounts = [];
		}

		// Check if the discount applied is greater than the multi-price total and readjust if needed.
		if ( $item_total_obj->changed ) {

			$limit_usage_qty = $this->get_current_usage_limit( $coupon, $item );

			// Once reached the usage limit (if any), do not continue applying discounts.
			if ( FALSE !== $limit_usage_qty && $limit_usage_qty <= 0 ) {
				$discount = 0;
			}
			// Take care of any limit of products to apply the discount.
			else {

				$coupon_code = $coupon->get_code();

				if ( ! isset( $inventories_discounts[ $coupon_code ] ) ) {

					$inventories_discounts[ $coupon_code ] = [];
				}

				$qty_divisor = $item['quantity'];
				foreach ( $item_total_obj->prices as $inventory_id => $price_obj ) {

					if ( 0 === ( $price_obj['qty'] * $price_obj['price'] ) - $this->get_inventory_discount( $inventory_id ) ) {

						$qty_divisor--;
					}
				}

				$qty_divisor = 0 === $qty_divisor ? $item['quantity'] : $qty_divisor;

				// Distinguish between fixed_product and fixed_cart discounts.
				$coupon_amount = 'fixed_product' === $coupon->get_discount_type() ? $coupon->get_amount() : $discount / $qty_divisor;
				$used_qty      = $discount = 0;

				foreach ( $item_total_obj->prices as $inventory_id => $price_obj ) {

					$inventory_price  = $price_obj['qty'] * $price_obj['price'];
					$discounted_price = $inventory_price - $this->get_inventory_discount( $inventory_id );

					$qty_to_discount    = FALSE === $limit_usage_qty ? $price_obj['qty'] : min( $price_obj['qty'], $limit_usage_qty - $used_qty );
					$inventory_discount = $qty_to_discount * $coupon_amount;
					$inventory_discount = min( $discounted_price, $inventory_discount );

					if ( ! isset( $inventories_discounts[ $coupon_code ][ $inventory_id ] ) ) {

						$inventories_discounts[ $coupon_code ][ $inventory_id ] = 0;
					}
					$inventories_discounts[ $coupon_code ][ $inventory_id ] += $inventory_discount;

					$this->discounts[ $coupon_code ][ $inventory_id ] += wc_add_number_precision( $inventory_discount );
					$discount += $inventory_discount;
					$used_qty += $qty_to_discount;

					if ( FALSE !== $limit_usage_qty && $used_qty >= $limit_usage_qty ) {
						break;
					}

				}

			}

			$discount = wc_round_discount( min( $item_total_obj->item_total, $discount ), 2 );

		}

		AtumCache::set_cache( $cache_key, $inventories_discounts, ATUM_MULTINV_TEXT_DOMAIN );

		return $discount;

	}

	/**
	 * Apply custom coupon discount to items.
	 *
	 * @since  1.0.3
	 *
	 * @param \WC_Coupon $coupon   Coupon object. Passed through filters.
	 * @param array      $item      The item being used for the discount.
	 * @param float      $discount  The discount being applied before our filter.
	 *
	 * @return int Total discounted.
	 */
	protected function apply_coupon_custom( $coupon, $item, $discount ) {

		// TODO: ADD COMPATIBILITY FOR CUSTOM COUPONS (FOR EXAMPLE: WC BOOKINGS).
		return $discount;

	}

	/**
	 * Calculate the current status of the coupon's limit usage for a specific item
	 *
	 * @since 1.0.4
	 *
	 * @param \WC_Coupon $coupon
	 * @param array      $item
	 *
	 * @return int|bool
	 */
	private function get_current_usage_limit( $coupon, $item ) {

		$limit_usage_qty = $coupon->get_limit_usage_to_x_items();

		// No limits.
		if ( ! $limit_usage_qty ) {
			return FALSE;
		}

		// If there is set a usage limit, calculate how many were discounted before this.
		if ( $limit_usage_qty ) {

			$discounts_used_before = wp_list_filter( $this->wc_discounts, [ 'coupon' => $coupon->get_code() ] );

			foreach ( $discounts_used_before as $discount_used ) {

				if ( $item['key'] === $discount_used['item']['key'] ) {
					break;
				}

				// Decrease the usage limit.
				$limit_usage_qty -= $discount_used['item']['quantity'];

			}

		}

		return $limit_usage_qty;

	}

	/**
	 * Hack the WC's cart subtotal for products with the "Price per Inventory" option enabled
	 *
	 * @since 1.0.1
	 *
	 * @param string      $product_subtotal
	 * @param \WC_Product $product
	 * @param int         $quantity Quantity being purchased.
	 * @param \WC_Cart    $cart
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function cart_product_subtotal( $product_subtotal, $product, $quantity, $cart ) {

		// Check product type compatibility.
		if ( ! MultiInventory::is_mi_compatible_product_type( $product->get_type() ) ) {
			return $product_subtotal;
		}

		$product_id     = $product->get_id();
		$item_total_obj = $this->get_cart_item_total( $product_id, $product_subtotal, $quantity, $product );

		if ( ! $item_total_obj->changed ) {
			return $product_subtotal;
		}

		if ( $product->is_taxable() ) {

			if ( $cart->display_prices_including_tax() ) {

				$row_price = wc_get_price_including_tax( $product, [
					'qty'   => 1,
					// We already calculated the subtotal, so don't need the qty for further WC calculations.
					'price' => $item_total_obj->item_total,
				] );

				$product_subtotal = wc_price( $row_price );

				if ( ! wc_prices_include_tax() && $cart->get_subtotal_tax() > 0 ) {
					$product_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
				}

			}
			else {

				$row_price = wc_get_price_excluding_tax( $product, [
					'qty'   => 1,
					'price' => $item_total_obj->item_total,
				] );

				$product_subtotal = wc_price( $row_price );

				if ( wc_prices_include_tax() && $cart->get_subtotal_tax() > 0 ) {
					$product_subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
				}

			}

		}
		else {
			$row_price        = $item_total_obj->item_total;
			$product_subtotal = wc_price( $row_price );
		}

		// As the Product Bundles are hooking the product subtotal on a higher level, we have to edit that result too.
		if ( class_exists( '\WC_Product_Bundle' ) && 'bundle' === $product->get_type() ) {

			add_filter( 'woocommerce_cart_item_subtotal', function ( $orig_product_subtotal, $cart_item, $cart_item_key ) use ( $product_subtotal, $product_id ) {

				if (
					! empty( $cart_item['data'] ) && $cart_item['data'] instanceof \WC_Product &&
					'bundle' === $cart_item['data']->get_type() && $product_id === $cart_item['data']->get_id()
				) {
					return $product_subtotal;
				}

				return apply_filters( 'atum/multi_inventory/cart_product_subtotal', $orig_product_subtotal, $cart_item, $cart_item_key );

			}, PHP_INT_MAX, 3 );

		}

		return apply_filters( 'atum/multi_inventory/cart_product_subtotal', $product_subtotal, $product, $quantity, $cart );

	}

	/**
	 * Hack the product price column on cart
	 *
	 * @since 1.0.1
	 *
	 * @param string $price
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function cart_item_price( $price, $cart_item, $cart_item_key ) {

		// For the products within a bundle we don't need to calculate anything.
		if ( class_exists( '\WC_Product_Bundle' ) && wc_pb_is_bundled_cart_item( $cart_item ) && ! array_key_exists( $cart_item['product_id'], $this->bundled_items ) ) {
			return $price;
		}

		$product_id = $cart_item['variation_id'] ?: $cart_item['product_id'];
		$product    = wc_get_product( $product_id );

		// If the product is not compatible with MI, return the unaltered coming value.
		if ( ! $product instanceof \WC_Product || ! MultiInventory::is_mi_compatible_product_type( $product->get_type() ) ) {
			return $price;
		}

		// Check if there are selected inventories.
		if ( ! empty( $cart_item['atum']['selected_mi'] ) && ! isset( $this->selected_inventories[ $product_id ] ) ) {
			$this->selected_inventories[ $product_id ] = $cart_item['atum']['selected_mi'];
		}

		$item_total_obj = $this->get_cart_item_total( $product_id, $price, $cart_item['quantity'], $cart_item['data'] );

		if ( $item_total_obj->changed ) {

			$product_prices = array();

			// Breakdown the prices.
			if ( ! empty( $item_total_obj->prices ) ) {

				foreach ( $item_total_obj->prices as $price_arr ) {

					// Get the unitary price for each of the inventories used.
					if ( WC()->cart->display_prices_including_tax() ) {

						$inventory_price = wc_price( wc_get_price_including_tax( $product, [
							'qty'   => 1,
							'price' => $price_arr['price'],
						] ) );

					}
					else {

						$inventory_price = wc_price( wc_get_price_excluding_tax( $product, [
							'qty'   => 1,
							'price' => $price_arr['price'],
						] ) );

					}

					if ( count( $item_total_obj->prices ) > 1 ) {
						$inventory_price = "<span class='woocommerce-Price-quantity'>{$price_arr['qty']} &times;</span> $inventory_price";
					}

					$product_prices[] = $inventory_price;

				}

			}

			$price = '<span class="quantity">' . implode( ' + ', $product_prices ) . '</span>';

		}

		return apply_filters( 'atum/multi_inventory/cart_item_price', $price, $cart_item, $cart_item_key );

	}

	/**
	 * Ran to remove all base taxes from an item. Used when prices include tax, and the customer is tax exempt.
	 *
	 * @see WC_Cart_Totals::remove_item_base_taxes
	 *
	 * @since 1.0.1
	 *
	 * @param array       $item    Item to adjust the prices of.
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	protected function remove_item_base_taxes( $item, $product ) {

		if ( ! $product instanceof \WC_Product || ! MultiInventory::is_mi_compatible_product_type( $product->get_type() ) ) {
			return $item;
		}

		if ( is_array( $item['price'] ) ) {

			// Apply recursivity for every price.
			foreach ( $item['price'] as $key => $item_price ) {
				$temp_item             = $item;
				$temp_item['price']    = $item_price;
				$temp_item             = $this->remove_item_base_taxes( $temp_item, $product );
				$item['price'][ $key ] = $temp_item['price'];
			}

		}
		else {

			$base_tax_rates = \WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );

			// Work out a new base price without the shop's base tax.
			$taxes = \WC_Tax::calc_tax( $item['price'], $base_tax_rates, TRUE );

			// Now we have a new item price (excluding TAX).
			$item['price']              = round( $item['price'] - array_sum( $taxes ) );
			$item['price_includes_tax'] = FALSE;

		}

		return $item;

	}

	/**
	 * Only ran if woocommerce_adjust_non_base_location_prices is true.
	 *
	 * If the customer is outside of the base location, this removes the base
	 * taxes. This is off by default unless the filter is used.
	 *
	 * Uses edit context so unfiltered tax class is returned.
	 *
	 * @see WC_Cart_Totals::adjust_non_base_location_price
	 *
	 * @since 1.0.1
	 *
	 * @param array       $item     Item to adjust the prices of.
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	protected function adjust_non_base_location_price( $item, $product ) {

		if ( ! $product instanceof \WC_Product || ! MultiInventory::is_mi_compatible_product_type( $product->get_type() ) ) {
			return $item;
		}

		if ( is_array( $item['price'] ) ) {

			// Apply recursivity for every price.
			foreach ( $item['price'] as $key => $item_price ) {
				$temp_item             = $item;
				$temp_item['price']    = $item_price;
				$temp_item             = $this->adjust_non_base_location_price( $temp_item, $product );
				$item['price'][ $key ] = $temp_item['price'];
			}

		}
		else {

			$base_tax_rates    = \WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
			$item['tax_rates'] = \WC_Tax::get_rates( $product->get_tax_class(), WC()->cart->get_customer() );

			if ( $item['tax_rates'] !== $base_tax_rates ) {
				// Work out a new base price without the shop's base tax.
				$taxes     = \WC_Tax::calc_tax( $item['price'], $base_tax_rates, TRUE );
				$new_taxes = \WC_Tax::calc_tax( $item['price'] - array_sum( $taxes ), $item['tax_rates'], FALSE );

				// Now we have a new item price.
				$item['price'] = round( $item['price'] - array_sum( $taxes ) + array_sum( $new_taxes ) );
			}

		}

		return $item;

	}

	/**
	 * Apply rounding to an array of taxes before summing. Rounds to store DP setting, ignoring precision.
	 *
	 * @see WC_Cart_Totals:round_line_tax
	 *
	 * @since  1.0.1
	 *
	 * @param  float $value Tax value.
	 *
	 * @return float
	 */
	protected function round_line_tax( $value ) {

		if ( 'yes' !== get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
			$value = wc_round_tax_total( $value, 0 );
		}

		return $value;

	}

	/**
	 * Calculate the total for the products in cart that have prices per inventory
	 *
	 * @since 1.0.1
	 *
	 * @param int         $product_id
	 * @param float       $item_total
	 * @param float       $quantity
	 * @param \WC_Product $product
	 * @param bool        $group_by_prices If true the prices array will be grouped by price, else the array id will be the inventory_id.
	 * @param int         $bundled_item_id if set, the item is a bundled item.
	 * @param bool        $has_discount    Only for bundled item with discount.
	 *
	 * @return object
	 *
	 * @throws \Exception
	 */
	private function get_cart_item_total( $product_id, $item_total, $quantity, $product, $group_by_prices = TRUE, $bundled_item_id = 0, $has_discount = FALSE ) {

		// As this function could be called multiple times, let's cache it.
		$cache_key      = AtumCache::get_cache_key( 'multi_inventories_item_total_obj', $product_id );
		$item_total_obj = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $item_total_obj;
		}

		$item_total_obj = (object) array(
			'changed'    => FALSE,
			'item_total' => $item_total,
		);

		// WC Product Bundles compatibility.
		if ( class_exists( '\WC_Product_Bundle' ) && $product instanceof \WC_Product_Bundle ) {

			$bundled_items     = $product->get_bundled_items();
			$bundle_item_total = $product->get_price() * $quantity;
			// $bundle_item_total = 0;

			foreach ( $bundled_items as $bundled_item ) {

				/**
				 * Variable definition
				 *
				 * @var \WC_Bundled_Item $bundled_item
				 */
				if ( ! $bundled_item->is_priced_individually() ) {
					// $bundle_item_total += ( $bundle_price * $quantity );
					continue;
				}

				$item_qty = $quantity * $bundled_item->get_quantity( 'min' );

				$bundled_item_total_obj = $this->get_cart_item_total( $bundled_item->get_product_id(), '', $item_qty, $bundled_item->get_product(), TRUE, TRUE, $bundled_item->get_discount() ? TRUE : FALSE );

				// Delete the cache created to not mess the prices when showing the bundled items on the cart.
				$bundled_item_cache_key = AtumCache::get_cache_key( 'multi_inventories_item_total_obj', $bundled_item->get_product_id() );
				AtumCache::delete_cache( $bundled_item_cache_key, ATUM_MULTINV_TEXT_DOMAIN );

				if ( ! empty( $bundled_item_total_obj->item_total ) ) {

					if ( $bundled_item->get_discount() && ! Helpers::has_multi_price( $bundled_item->get_product() ) ) {
						// We need multiplicate for quantity after aplying discount.
						$price_total = 0;
						foreach ( $bundled_item_total_obj->prices as $price ) {
							$price_total += \WC_PB_Product_Prices::get_discounted_price( (float) $price['price'], $bundled_item->get_discount() ) * $price['qty'];
						}
						$bundled_item_total_obj->item_total = $price_total;
					}

					$bundle_item_total += $bundled_item_total_obj->item_total;
				}

			}

			$item_total_obj = (object) array(
				'changed'    => TRUE,
				'prices'     => array(
					array(
						'qty'   => $quantity,
						'price' => $bundle_item_total / $quantity,
					),
				),
				'item_total' => $bundle_item_total,
			);

			AtumCache::set_cache( $cache_key, $item_total_obj, ATUM_MULTINV_TEXT_DOMAIN );

			return $item_total_obj;

		}
		elseif ( $bundled_item_id && class_exists( '\WC_Subscriptions' ) && WC_Subscriptions_Product::is_subscription( $product ) ) {

			// We assume that the subscription is not bundle, the price is correct.
			$recurring_price = $product->get_price();
			$sign_up_fee     = (float) \WC_Subscriptions_Product::get_sign_up_fee( $product );
			$bundled_item    = new WC_Bundled_Item( $bundled_item_id );
			$price           = $bundled_item->get_up_front_subscription_price( $recurring_price, $sign_up_fee, $product );

			if ( $price ) {

				$item_total_obj = (object) array(
					'changed'    => TRUE,
					'prices'     => array(
						array(
							'qty'   => $quantity,
							'price' => $price,
						),
					),
					'item_total' => $price * $quantity,
				);

			}

		}
		elseif ( ! Helpers::has_multi_price( $product ) ) {

			if ( $bundled_item_id ) {

				$price = $has_discount ? $product->get_regular_price() : $product->get_price();
				
				if ( $price ) {

					$item_total_obj = (object) array(
						'changed'    => TRUE,
						'prices'     => array(
							array(
								'qty'   => $quantity,
								'price' => $price,
							),
						),
						'item_total' => $price * $quantity,
					);

				}

				if ( ! Helpers::has_multi_inventory( $product_id ) )
					return $item_total_obj;

			}
			else {
				AtumCache::delete_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN ); // In case the status changed in the meantime.
				return $item_total_obj;
			}
		}

		$inventories = Helpers::get_product_inventories_sorted( $product_id );

		// Check if there are selected inventories.
		$inventories = SelectableInventories::maybe_restrict_inventories_in_cart( $product_id, $inventories );

		if ( ! empty( $inventories ) ) {

			$inventory_iteration = Helpers::get_product_inventory_iteration( $product_id );

			if ( 'use_next' !== $inventory_iteration && 'no' === Helpers::get_product_selectable_inventories( $product_id ) ) {

				$inventories     = array_values( $inventories );
				$first_inventory = current( $inventories );
				$inventories     = [];
				$inventories[]   = $first_inventory;

			}

			$subtotal       = $used_qty = 0;
			$remaining_qty  = $quantity;
			$item_total_obj = new \stdClass();

			// Iterate all the used inventories getting the prices for all of them.
			foreach ( $inventories as $inventory ) {

				if ( ! $inventory->is_sellable() ) {
					continue;
				}

				if ( $inventory->is_main() ) {
					$inventory->set_stock_status();
				}

				if ( 'outofstock' === $inventory->stock_status ) {
					continue;
				}

				// If the item has an "out of stock threshold" lower than the total stock quantity, use it as the max available stock.
				$inventory_stock = $inventory->get_available_stock();

				if ( $inventory->managing_stock() && $inventory_stock <= 0 && 'no' === $inventory->backorders ) {
					continue;
				}

				// Check if there is a selected inventory for this product.
				if ( ! empty( $this->selected_inventories[ $product_id ] ) && ! empty( $this->selected_inventories[ $product_id ][ $inventory->id ] ) ) {

					// There isn't enough stock. What happened?
					if ( $inventory_stock < $this->selected_inventories[ $product_id ][ $inventory->id ] ) {
						continue;
					}

					// Restrict the number of items for this inventories to the number that has been selected by the customer.
					$inventory_stock = $this->selected_inventories[ $product_id ][ $inventory->id ];

				}

				// Bundled products with multi-inventory and multi-price takes regular price if has discount.
				$bundled_item = array_key_exists( $product_id, $this->bundled_items ) ? $this->bundled_items[ $product_id ] : FALSE;

				if ( ! empty( $bundled_item ) && $bundled_item->get_discount() ) {
					$inventory_price = $bundled_item_id ? $inventory->regular_price : \WC_PB_Product_Prices::get_discounted_price( (float) $inventory->regular_price, $bundled_item->get_discount() );
				} else {
					$inventory_price = $inventory->price;
				}

				// Once an inventory with "back orders enabled" or "unmanaged + in stock" is reached, will take all the outstanding items.
				if ( 'no' !== $inventory->backorders || 'no' === $inventory->manage_stock && 'instock' === $inventory->stock_status ) {
					$used_qty      = $remaining_qty;
					$remaining_qty = 0;
				}
				else {
					$used_qty       = $remaining_qty <= $inventory_stock ? $remaining_qty : $inventory_stock;
					$remaining_qty -= $used_qty;
				}

				$subtotal += $inventory_price * $used_qty;

				// If distinct inventories have identical prices, group them.
				if ( $group_by_prices ) {
					$found_price = FALSE;

					if ( isset( $item_total_obj->prices ) ) {
						$found_price = wp_list_filter( $item_total_obj->prices, [ 'price' => $inventory_price ] );
					}

					if ( ! empty( $found_price ) ) {
						// TODO: TEST...
						$item_total_obj->prices[ key( $found_price ) ]['qty'] += $used_qty;
					}
					else {

						$item_total_obj->prices[] = array(
							'qty'   => $used_qty,
							'price' => $inventory_price,
						);

					}
				}
				else {

					$item_total_obj->prices[ $inventory->id ] = array(
						'qty'   => $used_qty,
						'price' => $inventory_price,
					);
				}

				if ( $remaining_qty <= 0 ) {
					break;
				}

			}

			$item_total_obj->changed    = TRUE;
			$item_total_obj->item_total = $subtotal;

			AtumCache::set_cache( $cache_key, $item_total_obj, ATUM_MULTINV_TEXT_DOMAIN );

		}

		return apply_filters( 'atum/multi_inventory/cart_item_total', $item_total_obj, $product_id, $item_total, $quantity );

	}

	/**
	 * Show a range of prices in front-end when multiple inventory prices are available
	 *
	 * @since 1.0.1
	 *
	 * @param string      $price
	 * @param \WC_Product $product
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function get_price_html( $price, $product ) {

		// Do nothing if the current product type is not compatible with MI.
		if ( ! MultiInventory::is_mi_compatible_product_type( $product->get_type() ) ) {
			return $price;
		}

		$transient_key    = AtumCache::get_transient_key( 'product_multi_prices', [], self::TRANSIENT_PREFIX );
		$transient_prices = AtumCache::get_transient( $transient_key, TRUE );
		$product_id       = $product->get_id();
		$prices           = [];
		$found            = FALSE;

		// Use the transient to prevent duplicate queries if possible.
		if ( isset( $transient_prices[ $product_id ] ) && ! array_key_exists( $product_id, $this->bundled_items ) ) {

			// If isset but is empty, has no MP active.
			if ( ! empty( $transient_prices[ $product_id ] ) ) {

				$region_restriction_mode = Helpers::get_region_restriction_mode();

				if ( 'no-restriction' === $region_restriction_mode ) {

					if ( ! empty( $transient_prices[ $product_id ]['nore'] ) ) {
						$prices = $transient_prices[ $product_id ]['nore'];
						$found  = TRUE;
					}

				}
				else {

					$visitor_location = Helpers::get_visitor_location();

					// Countries mode.
					if (
						'countries' === $region_restriction_mode && ! empty( $visitor_location['country'] ) &&
						! empty( $transient_prices[ $product_id ][ $visitor_location['country'] ] )
					) {

						$prices = $transient_prices[ $product_id ][ $visitor_location['country'] ];
						$found  = TRUE;

					}
					// Shipping Zones mode.
					elseif ( 'shipping-zones' === $region_restriction_mode ) {

						if ( ! empty( array_filter( $visitor_location ) ) ) {
							$shipping_zones = Helpers::get_zones_matching_package( [ 'destination' => $visitor_location ], 'ids' );
						}
						// If the visitor location can not be obtained, get the default zone.
						else {
							$shipping_zones = array_filter( [ AtumHelpers::get_option( 'mi_default_shipping_zone', '' ) ] );
						}

						if ( ! empty( $shipping_zones ) ) {

							$shipping_prices = [
								'price'         => [],
								'regular_price' => [],
							];

							foreach ( $shipping_zones as $shipping_zone ) {

								if ( ! empty( $transient_prices[ $product_id ][ $shipping_zone ] ) ) {
									$shipping_prices['price']         = array_merge( $shipping_prices['price'], $transient_prices[ $product_id ][ $shipping_zone ]['price'] );
									$shipping_prices['regular_price'] = array_merge( $shipping_prices['regular_price'], $transient_prices[ $product_id ][ $shipping_zone ]['regular_price'] );
								}

							}

							if ( ! empty( $shipping_prices['price'] ) ) {

								$prices = $shipping_prices;

								$prices['price']         = array_filter( array_unique( $prices['price'] ) );
								$prices['regular_price'] = array_filter( array_unique( $prices['regular_price'] ) );

								sort( $prices['price'], SORT_NUMERIC );
								sort( $prices['regular_price'], SORT_NUMERIC );

								$found = TRUE;

							}
						}
					}

				}
			}
		}

		if ( ! $found ) {

			$prices = $this->get_all_inventory_prices( $product, FALSE, TRUE );

			// Do not refresh the transient when showing bundled items to not affect the underlying product prices.
			if ( ! array_key_exists( $product_id, $this->bundled_items ) ) {
				$this->refresh_price_transient( $product_id, NULL );
			}

		}

		// if empty, Multi-Price is not enabled, so return the original price string.
		if ( ! empty( $prices ) ) {

			$min_price     = (float) current( $prices['price'] );
			$max_price     = (float) end( $prices['price'] );
			$min_reg_price = (float) current( $prices['regular_price'] );
			$max_reg_price = (float) end( $prices['regular_price'] );

			if ( $min_price !== $max_price ) {

				$price = wc_format_price_range(
					wc_get_price_to_display( $product, array( 'price' => $min_price ) ),
					wc_get_price_to_display( $product, array( 'price' => $max_price ) )
				);

			}
			else {

				$on_sale = $product->is_on_sale();

				// Check is_on_sale separately for variations.
				if ( $product->is_type( 'variable' ) ) {

					foreach ( $product->get_children() as $child_id ) {

						$child_product = wc_get_product( $child_id );
						if ( $child_product->is_on_sale() ) {
							$on_sale = TRUE;
							break;
						}
					}

				}

				if ( $on_sale && (float) $min_price < (float) $max_reg_price ) {

					$price = wc_format_sale_price(
						wc_price( wc_get_price_to_display( $product, array( 'price' => $max_reg_price ) ) ),
						wc_price( wc_get_price_to_display( $product, array( 'price' => $min_price ) ) )
					);
				}
				else {
					$price = wc_price( wc_get_price_to_display( $product, array( 'price' => $min_price ) ) );
				}

			}

			$price .= $product->get_price_suffix();

		}

		return apply_filters( 'atum/multi_inventory/price_html', $price, $product, $prices );

	}

	/**
	 * Get a list of all the available inventory prices for the specified product
	 *
	 * @since 1.0.1
	 *
	 * @param \WC_Product $product
	 * @param bool        $force             Whether to read or not the non multi price enabled product prices (used to read child's price from the parent).
	 * @param bool        $hide_out_of_stock
	 * @param bool        $skip_cache        Optional. Whether to skip the cache. It's used to calculate prices for distinct zones within the same process.
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	private function get_all_inventory_prices( $product, $force = FALSE, $hide_out_of_stock = NULL, $skip_cache = FALSE ) {

		if ( ! $product instanceof \WC_Product ) {
			return array();
		}

		$product_id = $product->get_id();
		$cache_key  = AtumCache::get_cache_key( 'all_inventory_prices', $product_id, ATUM_MULTINV_TEXT_DOMAIN );
		$has_cache  = FALSE;
		$prices     = [];

		if ( ! $skip_cache ) {
			$prices = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );
		}

		if ( $has_cache ) {
			return $prices;
		}

		if ( is_null( $hide_out_of_stock ) ) {
			$hide_out_of_stock = ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! is_admin() );
		}

		$prices       = array();
		$product_type = $product->get_type();

		if ( AtumHelpers::is_inheritable_type( $product_type ) ) {

			$prices = array();

			// WC Product Bundles compatibility.
			if ( class_exists( '\WC_Product_Bundle' ) && 'bundle' === $product_type ) {

				/**
				 * Variable definition.
				 *
				 * @var $product \WC_Product_Bundle
				 */
				$bundled_items = $product->get_bundled_items();

				// Seacrh for any discount applied to a bundled item.
				foreach ( $bundled_items as $bundled_item ) {

					/**
					 * Variable definition.
					 *
					 * @var \WC_Bundled_Item $bundled_item
					 */
					if ( ! $bundled_item->is_priced_individually() ) {
						continue;
					}

					$bundled_product = wc_get_product( $bundled_item->get_product_id() );
					$bundled_prices  = $this->get_all_inventory_prices( $bundled_product, TRUE, $hide_out_of_stock, TRUE );
					$bundled_min_qty = $bundled_item->get_quantity( 'min' );
					$bundled_max_qty = $bundled_item->get_quantity( 'max' );

					/**
					 * Variable definition
					 *
					 * @var \WC_Bundled_Item $bundled_item
					 */
					if ( $bundled_item->get_discount() ) {

						foreach ( $bundled_prices as $key => $bundled_price ) {

							if ( ! empty( $bundled_price ) ) {
								foreach ( $bundled_price as $index => $bundled_regular_price ) {

									// WC Product Bundles applies percent discount over the regular price.
									if ( 'regular_price' === $key ) {
										$bundled_prices[ $key ][ $index ] = (float) $bundled_regular_price;
									}
									else {
										$bundled_prices[ $key ][ $index ] = \WC_PB_Product_Prices::get_discounted_price( (float) $bundled_prices['regular_price'][ $index ], $bundled_item->get_discount() );
									}

								}
							}

						}

					}

					foreach ( [ 'price', 'regular_price' ] as $key ) {

						$key_prices = [];

						if ( ! empty( $bundled_prices[ $key ] ) && ! empty( $prices[ $key ] ) ) {

							foreach ( $bundled_prices[ $key ] as $bundled_price ) {

								foreach ( $prices[ $key ] as $price ) {

									$key_prices[] = $price + $bundled_price * $bundled_min_qty;

									if ( $bundled_max_qty && $bundled_max_qty !== $bundled_min_qty ) {
										$key_prices[] = $price + $bundled_price * $bundled_max_qty;
									}
								}
							}

						}
						elseif ( ! empty( $bundled_prices[ $key ] ) ) {

							$key_prices = [];

							foreach ( $bundled_prices[ $key ] as $bundled_price ) {

								$key_prices[] = $bundled_price * $bundled_min_qty;

								if ( $bundled_max_qty && $bundled_max_qty !== $bundled_min_qty ) {
									$key_prices[] = $bundled_price * $bundled_max_qty;
								}
							}

						}

						$prices[ $key ] = $key_prices;
					}

				}

				foreach ( [ 'price', 'regular_price' ] as $key ) {

					$key_product_price = $product->{"get_$key"}();

					if ( ! empty( $prices[ $key ] ) ) {

						foreach ( $prices[ $key ] as $index => $price ) {
							$prices[ $key ][ $index ] = $price + $key_product_price;
						}

					}
					else {
						$prices[ $key ] = [ $key_product_price ];
					}
				}

				sort( $prices['price'], SORT_NUMERIC );
				sort( $prices['regular_price'], SORT_NUMERIC );

			}
			else {

				// Loop all the variations, to get all the inventory prices for them.
				$children = $product->get_children();

				foreach ( $children as $child_id ) {

					$child_product = wc_get_product( $child_id );
					$child_prices  = $this->get_all_inventory_prices( $child_product, TRUE, $hide_out_of_stock, $skip_cache );

					if ( ! empty( $child_prices ) && ! empty( $prices ) ) {
						$prices['price']         = array_unique( array_merge( $prices['price'], $child_prices['price'] ) );
						$prices['regular_price'] = array_unique( array_merge( $prices['regular_price'], $child_prices['regular_price'] ) );

						sort( $prices['price'], SORT_NUMERIC );
						sort( $prices['regular_price'], SORT_NUMERIC );
					}
					elseif ( ! empty( $child_prices ) ) {
						$prices = $child_prices;
					}

				}

			}

		}
		elseif ( ! Helpers::has_multi_price( $product ) ) {

			if ( $force && ( ! $hide_out_of_stock || 'outofstock' !== $product->get_stock_status() ) ) {

				$price         = $product->get_price();
				$regular_price = $product->get_regular_price();

				if ( $price || '0' === $price ) {
					$prices['price'][] = $price;
				}

				if ( $regular_price || '0' === $regular_price ) {
					$prices['regular_price'][] = $regular_price;
				}

			}

		}
		else {

			$product_id  = apply_filters( 'atum/multi_inventory/product_id', $product_id );
			$inventories = Helpers::get_product_inventories_sorted( $product_id, TRUE, $skip_cache );

			if ( ! empty( $inventories ) ) {

				$inventory_iteration = Helpers::get_product_inventory_iteration( $product_id );

				foreach ( $inventories as $inventory ) {

					if ( ! $inventory->is_sellable() ) {
						continue;
					}

					if ( $hide_out_of_stock && 'outofstock' === $inventory->stock_status ) {
						continue;
					}

					// Apply WooCommerce Bundles discounts per item (if needed).
					if ( array_key_exists( $product_id, $this->bundled_items ) && isset( $product->bundled_item_price ) ) {
						$prices['price'][] = \WC_PB_Product_Prices::get_discounted_price( $inventory->regular_price, $this->bundled_items[ $product_id ]->get_discount() );
					}
					else {
						$prices['price'][] = $inventory->price;
					}

					$prices['regular_price'][] = $inventory->regular_price;

					if ( 'use_next' !== $inventory_iteration && 'no' === Helpers::get_product_selectable_inventories( $product_id ) ) {
						break; // Only the fist inventory will be used, so won't have multiple prices.
					}

				}

				if ( empty( $prices ) ) {

					foreach ( $inventories as $inventory ) {

						if ( ! $inventory->is_sellable() ) {
							continue;
						}

						// Apply WooCommerce Bundles discounts per item (if needed).
						$prices['price'][]         = array_key_exists( $product_id, $this->bundled_items ) && isset( $product->bundled_item_price ) ?
							\WC_PB_Product_Prices::get_discounted_price( $inventory->regular_price, $this->bundled_items[ $product_id ]->get_discount() ) :
							$inventory->price;
						$prices['regular_price'][] = $inventory->regular_price;

						break; // Only the fist inventory will be used.

					}

				}

				if ( ! empty( $prices ) ) {

					$prices['price']         = array_filter( array_unique( $prices['price'] ) );
					$prices['regular_price'] = array_filter( array_unique( $prices['regular_price'] ) );

					sort( $prices['price'], SORT_NUMERIC );
					sort( $prices['regular_price'], SORT_NUMERIC );

				}

			}

		}

		if ( ! $skip_cache ) {
			AtumCache::set_cache( $cache_key, $prices, ATUM_MULTINV_TEXT_DOMAIN );
		}

		return $prices;

	}

	/**
	 * Get the children IDs for a given parent product
	 *
	 * @since 1.3.6
	 *
	 * @param \WC_Product $product
	 *
	 * @return int[]
	 */
	private function get_children( $product ) {

		$children_ids = [];

		// WoooCommerce Product bundles compatibility.
		if ( class_exists( '\WC_Product_Bundle' ) && 'bundle' === $product->get_type() ) {

			$bundle_items = $product->get_bundled_items();

			if ( ! empty( $bundle_items ) ) {
				foreach ( $bundle_items as $bundle_item ) {

					/**
					 * Variable definition
					 *
					 * @var \WC_Bundled_Item $bundle_item
					 */
					$child_id = $bundle_item->get_product_id();
					if ( $bundle_item->is_priced_individually() ) {
						$children_ids[] = $child_id;
					}
				}
			}

		}
		else {
			$children_ids = $product->get_children();
		}

		return $children_ids;

	}

	/**
	 * Change get price filters during Totals Cart calculation
	 *
	 * @since 1.2.5.1
	 *
	 * @param \WC_Cart $cart
	 */
	public function add_cart_product_price_filter( $cart ) {

		remove_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), PHP_INT_MAX );
		remove_filter( 'woocommerce_product_variation_get_price', array( $this, 'get_price' ), PHP_INT_MAX );

		add_filter( 'woocommerce_product_get_price', array( $this, 'change_price_for_cart' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_price_for_cart' ), PHP_INT_MAX, 2 );

	}

	/**
	 * UnChange get price filters.
	 *
	 * @since 1.2.5.1
	 *
	 * @param \WC_Cart $cart
	 */
	public function remove_cart_product_price_filter( $cart ) {

		add_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'get_price' ), PHP_INT_MAX, 2 );

		remove_filter( 'woocommerce_product_get_price', array( $this, 'change_price_for_cart' ), PHP_INT_MAX );
		remove_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_price_for_cart' ), PHP_INT_MAX );

	}

	/**
	 * Change the price returned for MultiPrice products in cart
	 *
	 * @since 1.2.5.1
	 *
	 * @param float       $value
	 * @param \WC_Product $product
	 *
	 * @return float
	 */
	public function change_price_for_cart( $value, $product ) {

		$cart_items = WC()->cart->get_cart_contents();
		$product_id = $product->get_id();

		// WC Product Bundles compatibility.
		if ( class_exists( '\WC_Product_Bundle' ) && 'bundle' === $product->get_type() ) {
			return $value;
		}

		if ( ! Helpers::has_multi_price( $product ) ) {
			return $value;
		}

		foreach ( $cart_items as $cart_item ) {

			$cart_product_id = $cart_item['variation_id'] ?: $cart_item['product_id'];

			// For the products within a bundle we don't need to calculate anything.
			if ( class_exists( '\WC_Product_Bundle' ) && wc_pb_is_bundled_cart_item( $cart_item ) ) {

				$bundled_item = new \WC_Bundled_Item( $cart_item['bundled_item_id'] );

				if ( $product_id === $cart_product_id && $bundled_item->is_priced_individually() ) {

					$bundled_item_total = $this->get_cart_item_total( $product_id, $value, $cart_item['quantity'], $product, FALSE );

					return ! empty( $bundled_item_total->item_total ) ? ( $bundled_item_total->item_total / $cart_item['quantity'] ) : $value;

				}
				else {
					//return $value;
					continue;
				}
			}
			else {

				if ( $cart_product_id === $product->get_id() ) {

					$cache_key = AtumCache::get_cache_key( 'price_for_cart', $cart_product_id );
					$price     = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

					if ( $has_cache ) {
						return $price;
					}

					$inventory_iteration = Helpers::get_product_inventory_iteration( $cart_product_id );
					$inventories         = Helpers::get_product_inventories_sorted( $cart_product_id );

					// Check if there are selected inventories.
					$inventories = SelectableInventories::maybe_restrict_inventories_in_cart( $cart_product_id, $inventories );

					// If inventory iteration is not allowed, get only the first one.
					if ( 'out_of_stock' === $inventory_iteration && 'no' === Helpers::get_product_selectable_inventories( $product_id ) ) {
						$inventories = array( reset( $inventories ) );
					}

					$total_cost    = 0;
					$qty_remaining = $cart_item['quantity'];

					// Loop all the product inventories. Don't need to check fo availability as it was checked before.
					foreach ( $inventories as $inventory ) {

						if ( 0 === $qty_remaining ) {
							break;
						}

						if ( ! $inventory->is_sellable() ) {
							continue;
						}

						if ( $inventory->is_main() ) {
							$inventory->set_stock_status();
						}

						if ( 'outofstock' === $inventory->stock_status ) {
							continue;
						}

						if ( $inventory->managing_stock() ) {

							// If the item has an "out of stock threshold" lower than the total stock quantity, use it as the max available stock.
							$inventory_stock = $inventory->get_available_stock();

							if ( $inventory_stock <= 0 && 'no' === $inventory->backorders ) {
								continue;
							}

							// Check if there are selected inventories.
							if (
								! empty( $cart_item['atum']['selected_mi'] ) && is_array( $cart_item['atum']['selected_mi'] ) &&
								array_key_exists( $inventory->id, $cart_item['atum']['selected_mi'] )
							) {

								// There isn't enough stock. What happened?
								if ( $inventory_stock < $cart_item['atum']['selected_mi'][ $inventory->id ] ) {
									continue;
								}

								// Restrict the number of items for this inventories to the number that has been selected by the customer.
								$inventory_stock                           = $cart_item['atum']['selected_mi'][ $inventory->id ];
								$this->selected_inventories[ $product_id ] = $cart_item['atum']['selected_mi'];

							}

							if ( 'no' === $inventory->backorders && $inventory_stock <= $qty_remaining ) {

								// If no backorders allowed, $inventory_stock >= 0.
								$qty_remaining -= $inventory_stock;
								$total_cost    += $inventory->price * $inventory_stock;

							}
							else { // if backorders allowed or enough inventory stock.

								$total_cost   += $inventory->price * $qty_remaining;
								$qty_remaining = 0;
							}

						}
						else { // not managing the stock.
							$total_cost   += $inventory->price * $qty_remaining;
							$qty_remaining = 0;
						}

					}

					$value = $cart_item['quantity'] > 0 ? $total_cost / $cart_item['quantity'] : 0;
					AtumCache::set_cache( $cache_key, $value, ATUM_MULTINV_TEXT_DOMAIN );

				}

			}

		}

		return $value;
	}

	/**
	 * Get all discount totals per inventory.
	 *
	 * @since  1.2.5.1
	 * @param  bool $in_cents Should the totals be returned in cents, or without precision.
	 * @return array
	 */
	public function get_discounts_by_inventory( $in_cents = false ) {

		$discounts            = $this->discounts;
		$item_discount_totals = (array) array_shift( $discounts );

		foreach ( $discounts as $coupon_discounts ) {
			foreach ( $coupon_discounts as $inventory_id => $inventory_discount ) {
				$item_discount_totals[ $inventory_id ] += $inventory_discount;
			}
		}

		return $in_cents ? $item_discount_totals : wc_remove_number_precision_deep( $item_discount_totals );
	}

	/**
	 * Get discount by key with or without precision.
	 *
	 * @since 1.2.5.1
	 *
	 * @param string $inventory_id name of discount row to return.
	 * @param bool   $in_cents     Should the totals be returned in cents, or without precision.
	 *
	 * @return array|int
	 */
	public function get_inventory_discount( $inventory_id, $in_cents = false ) {
		$item_discount_totals = $this->get_discounts_by_inventory( $in_cents );
		return isset( $item_discount_totals[ $inventory_id ] ) ? $item_discount_totals[ $inventory_id ] : 0;
	}

	/**
	 * Refresh the MultiPrice transient if needed.
	 *
	 * @since 1.3.5
	 *
	 * @param int $product_id The product ID. If WPML is installed, must be original translation.
	 * @param int $loop       Optional. Only needed if a variation is being saved. If WPML is installed, must be original translation.
	 */
	public function refresh_price_transient( $product_id, $loop ) {

		$product = AtumHelpers::get_atum_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$product_type = $product->get_type();

		if ( AtumHelpers::is_child_type( $product_type ) ) {

			// Only parent and simple products can have the Multi Price transient.
			$product_id      = $product->get_parent_id();
			$product         = AtumHelpers::get_atum_product( $product_id );
			$has_multi_price = FALSE;
			$product_ids     = $product->get_children();

			foreach ( $product_ids as $child_id ) {

				if ( Helpers::has_multi_price( $child_id ) ) {
					$has_multi_price = TRUE;
					break;
				}
			}

		}
		elseif ( AtumHelpers::is_inheritable_type( $product_type ) ) {

			$has_multi_price = FALSE;
			$product_ids     = $this->get_children( $product );

			foreach ( $product_ids as $child_id ) {

				if ( Helpers::has_multi_price( $child_id ) ) {
					$has_multi_price = TRUE;
					break;
				}
			}

		}
		else {
			$has_multi_price = Helpers::has_multi_price( $product );
			$product_ids     = [ $product_id ];
		}

		$transient_key = AtumCache::get_transient_key( 'product_multi_prices', [], self::TRANSIENT_PREFIX );
		$prices        = AtumCache::get_transient( $transient_key, TRUE );

		// Used by WPML integration, only save original products mi data.
		if ( apply_filters( 'atum/multi_inventory/can_save_mi_metabox_data', TRUE, $product_id ) ) {

			$prices[ $product_id ] = [];

			// If it has_multi_price, it also is MI compatible and has MI active.
			if ( $has_multi_price ) {

				// If an error occurred, leave blank the $price.
				try {

					$region_restriction_mode = Helpers::get_region_restriction_mode();

					if ( 'no-restriction' === $region_restriction_mode ) {
						$prices[ $product_id ]['nore'] = $this->get_all_inventory_prices( $product, TRUE, TRUE, TRUE );
					}
					else {

						$regions = Helpers::get_product_used_regions( $product_ids, $region_restriction_mode );

						// If no regions returned, the product will show main inventory's prices.
						if ( $regions ) {

							if ( 'countries' === $region_restriction_mode ) {

								foreach ( $regions as $region ) {

									$function = function() use ( $region ) {
										return [ 'country' => $region ];
									};

									add_filter( 'atum/multi_inventory/visitor_location', $function );
									$prices[ $product_id ][ $region ] = $this->get_all_inventory_prices( $product, TRUE, TRUE, TRUE );
									remove_filter( 'atum/multi_inventory/visitor_location', $function );

								}

							}
							// Shipping zones.
							else {

								foreach ( $regions as $region ) {

									$function = function() use ( $region ) {
										return [ $region ];
									};

									add_filter( 'atum/multi_inventory/zones_matching_package', $function );
									$prices[ $product_id ][ $region ] = $this->get_all_inventory_prices( $product, TRUE, TRUE, TRUE );
									remove_filter( 'atum/multi_inventory/zones_matching_package', $function );

								}
							}

						}

					}

				} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch

				}

			}

			AtumCache::set_transient( $transient_key, $prices, 0, TRUE );

			if ( class_exists( '\WC_Product_Bundle' ) ) {

				$bundles = wc_pb_get_bundled_product_map( $product_id );

				if ( $bundles ) {
					foreach ( $bundles as $bundle_id ) {
						$this->refresh_price_transient( $bundle_id, NULL );
					}
				}
			}

		}
	}

	/**
	 * Check for ATUM MI and WooCommerce settings that would affect price transient current values.
	 *
	 * @since 1.3.5
	 *
	 * @param string $option_name   Name of the updated option.
	 * @param mixed  $old_value     The old option value.
	 * @param mixed  $value         The new option value.
	 */
	public function maybe_delete_price_transient_after_options_chg( $option_name, $old_value, $value ) {

		$changed = FALSE;

		if ( 'atum_settings' === $option_name ) {

			$options_to_check = [
				'mi_region_restriction_mode',
				'mi_default_shipping_zone',
				'mi_default_zone_for_empty_regions',
				'mi_default_country',
				'mi_default_country_for_empty_regions',
				'mi_default_inventory_sorting_mode',
				'mi_default_inventory_iteration',
				'mi_default_expirable_inventories',
				'mi_default_price_per_inventory',
			];

			foreach ( $options_to_check as $option ) {

				if (
					( ! empty( $old_value[ $option ] ) && empty( $value[ $option ] ) ) ||
					( empty( $old_value[ $option ] ) && ! empty( $value[ $option ] ) ) ||
					( empty( $old_value[ $option ] ) && empty( $value[ $option ] ) ) ||
					$old_value[ $option ] !== $value[ $option ]
				) {
					$changed = TRUE;
					break;
				}

			}

		}

		if ( ! $changed ) {

			$wc_changes = [
				'woocommerce_calc_taxes',
				'woocommerce_hide_out_of_stock_items',
				'woocommerce_prices_include_tax',
				'woocommerce_tax_display_shop',
			];

			if ( in_array( $option_name, $wc_changes ) ) {
				if ( ( ! $value && $old_value ) || ( $value && ! $old_value ) || $value !== $old_value ) {
					$changed = TRUE;
				}
			}

		}

		if ( $changed ) {
			$this->delete_price_transient();
		}

	}

	/**
	 * Remove expired inventory's product entry from the Multi Price product transient.
	 *
	 * @since 1.3.5
	 *
	 * @param Inventory $inventory
	 */
	public function update_price_transient_after_expire_inventory( $inventory ) {

		$transient_key    = AtumCache::get_transient_key( 'product_multi_prices', [], self::TRANSIENT_PREFIX );
		$transient_prices = AtumCache::get_transient( $transient_key, TRUE );

		if ( ! empty( $transient_prices ) && is_array( $transient_prices ) ) {
			unset( $transient_prices[ $inventory->product_id ] );
			AtumCache::set_transient( $transient_key, $transient_prices, 0, TRUE );
		}
	}

	/**
	 * Delete the price transient.
	 *
	 * @since 1.3.5
	 */
	public function delete_price_transient() {

		$transient_key = AtumCache::get_transient_key( 'product_multi_prices', [], self::TRANSIENT_PREFIX );
		AtumCache::delete_transients( $transient_key, self::TRANSIENT_PREFIX );
	}

	/**
	 * Add the recalculate price transient hook to the list of queues.
	 *
	 * @since 1.3.5
	 *
	 * @param array $recurring_hooks
	 *
	 * @return array
	 */
	public function add_transient_recalc_hook( $recurring_hooks ) {

		// Every day at midnight.
		$recurring_hooks['atum/multi_inventory/recalculate_price_transient'] = [
			'time'     => 'midnight tomorrow',
			'interval' => DAY_IN_SECONDS,
		];

		// Register the recurring hook action.
		add_action( 'atum/multi_inventory/recalculate_price_transient', array( $this, 'recalculate_price_transient' ) );

		return $recurring_hooks;
	}

	/**
	 * This hook is only intended to be used within a cron schedule.
	 * It gets all simple and parent products prices ans fills the price transient.
	 *
	 * @since 1.3.5
	 */
	public function recalculate_price_transient() {

		$transient_key = AtumCache::get_transient_key( 'product_multi_prices', [], self::TRANSIENT_PREFIX );
		AtumCache::delete_transients( $transient_key, self::TRANSIENT_PREFIX );

		$products = AtumHelpers::get_all_products();

		if ( ! empty( $products ) ) {

			foreach ( $products as $product_id ) {
				$this->refresh_price_transient( $product_id, NULL );
			}

		}

	}

	/**
	 * Recalculate updated by a list table call product entries in the Price transient.
	 *
	 * @since 1.3.5
	 *
	 * @param array $data The data that was saved in the List Table.
	 */
	public function update_price_transient_after_sc( $data ) {

		if ( empty( array_filter( $data ) ) ) {
			return;
		}

		foreach ( $data as $product_id => $product_data ) {
			$this->refresh_price_transient( $product_id, NULL );
		}
	}

	/**
	 * Recalculate price transients for a product.
	 *
	 * @since 1.3.5
	 *
	 * @param \WC_Product $product
	 */
	public function update_price_transient_for_product( $product ) {

		$this->refresh_price_transient( $product->get_id(), NULL );

	}

	/**
	 * Remove the products included in an order from the Product Transient.
	 *
	 * @since 1.3.5
	 *
	 * @param \WC_Order $order
	 */
	public function update_price_transient_for_orders( $order ) {

		$transient_key    = AtumCache::get_transient_key( 'product_multi_prices', [], self::TRANSIENT_PREFIX );
		$transient_prices = AtumCache::get_transient( $transient_key, TRUE );

		if ( ! empty( $transient_prices ) && is_array( $transient_prices ) ) {

			foreach ( $order->get_items() as $item ) {

				/**
				 * Variable definition
				 *
				 * @var \WC_Order_Item_Product $item
				 */

				// Only for products items.
				if ( ! $item->is_type( 'line_item' ) ) {
					continue;
				}

				$product_id = $item->get_product_id();

				unset( $transient_prices[ $product_id ] );

			}

			AtumCache::set_transient( $transient_key, $transient_prices, 0, TRUE );

		}

	}

	/**
	 * When displaying a bundle product with priced-individually bundled items with MI, we have to apply the discounts (if any).
	 *
	 * @since 1.3.6
	 *
	 * @param \WC_Bundled_Item[] $bundled_items
	 * @param \WC_Product_Bundle $bundle_product
	 *
	 * @return \WC_Bundled_Item[]
	 */
	public function maybe_register_loaded_bundled_items( $bundled_items, $bundle_product = NULL ) {

		if ( ! empty( $bundled_items ) ) {

			foreach ( $bundled_items as $bundled_item ) {

				$product_id = $bundled_item->get_product_id();

				if ( ! array_key_exists( $product_id, $this->bundled_items ) && $bundled_item->is_priced_individually() ) {
					$this->bundled_items[ $product_id ] = $bundled_item;
				}

			}

		}

		return $bundled_items;

	}

	/**
	 * Show the right subtotals for bundled items with MI in the order details page
	 *
	 * @since 1.3.6
	 *
	 * @param float                  $subtotal
	 * @param \WC_Order              $order
	 * @param \WC_Order_Item_Product $item
	 * @param bool                   $inc_tax
	 * @param bool                   $round
	 *
	 * @return float|int
	 */
	public function maybe_recalculate_order_item_subtotal_for_bundled_items( $subtotal, $order, $item, $inc_tax = TRUE, $round = TRUE ) {

		if ( function_exists( 'wc_pb_is_bundled_order_item' ) && wc_pb_is_bundled_order_item( $item ) ) {

			$bundled_item_id     = $item->get_meta( '_bundled_item_id' );
			$bundled_item        = new \WC_Bundled_Item( $bundled_item_id );
			$stamp               = $item->get_meta( '_stamp' );
			$priced_individually = $item->get_meta( '_bundled_item_priced_individually' );

			if (
				Helpers::has_multi_price( $bundled_item->get_product_id() ) &&
				$bundled_item_id && 'yes' === $priced_individually && is_array( $stamp ) &&
				! empty( $stamp[ $bundled_item_id ] ) && ! empty( $stamp[ $bundled_item_id ]['discount'] )
			) {

				$order_item_inventories = Inventory::get_order_item_inventories( $item->get_id() );

				if ( ! empty( $order_item_inventories ) ) {

					$subtotal = 0;

					foreach ( $order_item_inventories as $order_item_inventory ) {
						$subtotal += (float) $order_item_inventory->subtotal;
					}

				}

			}

		}

		return $subtotal;

	}

	/**
	 * When saving an order item with priced-individually bundled items with MI, we have to apply the discounts (if any).
	 *
	 * @since 1.3.6
	 *
	 * TODO: REMOVE IF NOT NEEDED
	 *
	 * @param \WC_Order_Item $item
	 * @param string         $cart_item_key
	 * @param array          $cart_item
	 * @param \WC_Order      $order
	 */
	/*public function maybe_adjust_bundled_item_subtotal_before_save( $item, $cart_item_key, $cart_item, $order ) {

		if ( wc_pb_is_bundled_cart_item( $cart_item ) ) {

			$bundled_item_id = $cart_item['bundled_item_id'];
			$bundled_item    = new \WC_Bundled_Item( $cart_item['bundled_item_id'] );
			$product_id      = $bundled_item->get_product_id();

			if (
				Helpers::has_multi_price( $product_id ) && $bundled_item_id &&
				$bundled_item->is_priced_individually() && is_array( $cart_item['stamp'] ) &&
				! empty( $cart_item['stamp'][ $bundled_item_id ] ) && ! empty( $cart_item['stamp'][ $bundled_item_id ]['discount'] )
			) {

				$subtotal      = 0;
				$quantity_used = $cart_item['quantity'];
				$inventories   = Helpers::get_product_inventories_sorted( $product_id );
				$line_subtotal = $cart_item['line_subtotal'];

				foreach ( $inventories as $inventory ) {

					$discounted_price = \WC_PB_Product_Prices::get_discounted_price( $inventory->price, $cart_item['stamp'][ $bundled_item_id ]['discount'] );

					if ( $inventory->stock_quantity >= $quantity_used ) {
						$subtotal     += ( $quantity_used * $discounted_price );
						$quantity_used = 0;
					}
					else {
						$subtotal      += ( $inventory->stock_quantity * $discounted_price );
						$quantity_used -= $inventory->stock_quantity;
					}

					if ( $quantity_used <= 0 ) {
						break;
					}

				}

				if ( $line_subtotal !== $subtotal ) {
					$item->set_subtotal( $subtotal );

					// TODO: HANDLE TAXES.
					$item->set_total( $subtotal );
				}

			}

		}

	}*/

	/**
	 * Change the added item price (purchase price) to a PO if the product is MI multi price and the added inventory is not the first in order
	 *
	 * @since 1.3.6
	 *
	 * @param float                        $price
	 * @param float                        $qty
	 * @param \WC_Product|AtumProductTrait $product
	 * @param AtumOrderModel               $order
	 *
	 * @return float|string
	 */
	public function maybe_change_item_purchase_price( $price, $qty, $product, $order ) {

		$product_id = $product->get_id();

		if ( PurchaseOrders::POST_TYPE === $order->get_post_type() && ( Helpers::has_multi_price( $product ) ) ) {

			try {
				$inventories = Helpers::get_product_inventories_sorted( $product_id );
			}
			catch ( \Exception $e ) {
				return $price;
			}

			foreach ( $inventories as $inventory ) {

				if ( $inventory->is_main() ) {
					$inventory->set_stock_status();
				}

				if ( 'outofstock' === $inventory->stock_status ) {
					$price = $inventory->purchase_price;
					break;
				}

			}

		}

		return $price;
	}

	/**
	 * Returns the price including or excluding tax, based on the 'woocommerce_tax_display_shop' setting.
	 *
	 * @since  1.3.7
	 *
	 * @param  Inventory $inventory The inventory object (with multi-price enabled).
	 * @param  array     $args      Optional arguments to pass product quantity and price.
	 * @return float
	 */
	public static function get_inventory_price_to_display( $inventory, $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'qty'   => 1,
				'price' => $inventory->price,
			)
		);

		$price   = $args['price'];
		$qty     = $args['qty'];
		$product = wc_get_product( $inventory->product_id );

		if ( ! $product instanceof \WC_Product ) {
			return (float) $price * (float) $qty;
		}

		return 'incl' === get_option( 'woocommerce_tax_display_shop' ) ?
			self::get_inventory_price_including_tax(
				$inventory,
				$product,
				array(
					'qty'   => $qty,
					'price' => $price,
				)
			) :
			self::get_inventory_price_excluding_tax(
				$inventory,
				$product,
				array(
					'qty'   => $qty,
					'price' => $price,
				)
			);

	}

	/**
	 * For a given inventory, and optionally price/qty, work out the price with tax included, based on store settings.
	 *
	 * @since  1.3.7
	 *
	 * @param  Inventory   $inventory The inventory object (with multi-price enabled).
	 * @param  \WC_Product $product   WC_Product object.
	 * @param  array       $args      Optional arguments to pass product quantity and price.
	 *
	 * @return float
	 */
	public static function get_inventory_price_including_tax( $inventory, $product, $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'qty'   => '',
				'price' => '',
			)
		);

		$price = '' !== $args['price'] ? max( 0.0, (float) $args['price'] ) : $inventory->price;
		$qty   = '' !== $args['qty'] ? max( 0.0, (float) $args['qty'] ) : 1;

		if ( '' === $price ) {
			return '';
		}
		elseif ( empty( $qty ) ) {
			return 0.0;
		}

		$line_price   = $price * $qty;
		$return_price = $line_price;

		if ( $product->is_taxable() ) {

			if ( ! wc_prices_include_tax() ) {

				$tax_rates = \WC_Tax::get_rates( $product->get_tax_class() );
				$taxes     = \WC_Tax::calc_tax( $line_price, $tax_rates, FALSE );

				if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
					$taxes_total = array_sum( $taxes );
				}
				else {
					$taxes_total = array_sum( array_map( 'wc_round_tax_total', $taxes ) );
				}

				$return_price = round( $line_price + $taxes_total, wc_get_price_decimals() );

			}
			else {

				$tax_rates      = \WC_Tax::get_rates( $product->get_tax_class() );
				$base_tax_rates = \WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );

				/**
				 * If the customer is excempt from VAT, remove the taxes here.
				 * Either remove the base or the user taxes depending on woocommerce_adjust_non_base_location_prices setting.
				 */
				if ( ! empty( WC()->customer ) && WC()->customer->get_is_vat_exempt() ) {

					$remove_taxes = apply_filters( 'woocommerce_adjust_non_base_location_prices', TRUE ) ? \WC_Tax::calc_tax( $line_price, $base_tax_rates, TRUE ) : \WC_Tax::calc_tax( $line_price, $tax_rates, TRUE );

					if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
						$remove_taxes_total = array_sum( $remove_taxes );
					}
					else {
						$remove_taxes_total = array_sum( array_map( 'wc_round_tax_total', $remove_taxes ) );
					}

					$return_price = round( $line_price - $remove_taxes_total, wc_get_price_decimals() );

				}
				/**
				 * The woocommerce_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing with out of base locations.
				 * e.g. If a product costs 10 including tax, all users will pay 10 regardless of location and taxes.
				 * This feature is experimental @since 2.4.7 and may change in the future. Use at your risk.
				 */
				elseif ( $tax_rates !== $base_tax_rates && apply_filters( 'woocommerce_adjust_non_base_location_prices', TRUE ) ) {

					$base_taxes   = \WC_Tax::calc_tax( $line_price, $base_tax_rates, TRUE );
					$modded_taxes = \WC_Tax::calc_tax( $line_price - array_sum( $base_taxes ), $tax_rates, FALSE );

					if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
						$base_taxes_total   = array_sum( $base_taxes );
						$modded_taxes_total = array_sum( $modded_taxes );
					}
					else {
						$base_taxes_total   = array_sum( array_map( 'wc_round_tax_total', $base_taxes ) );
						$modded_taxes_total = array_sum( array_map( 'wc_round_tax_total', $modded_taxes ) );
					}

					$return_price = round( $line_price - $base_taxes_total + $modded_taxes_total, wc_get_price_decimals() );
				}
			}
		}

		return apply_filters( 'atum/multi_inventory/get_inventory_price_including_tax', $return_price, $qty, $inventory, $product );

	}

	/**
	 * For a given product, and optionally price/qty, work out the price with tax excluded, based on store settings.
	 *
	 * @since  1.3.7
	 *
	 * @param  Inventory   $inventory The inventory object (with multi-price enabled).
	 * @param  \WC_Product $product   WC_Product object.
	 * @param  array       $args      Optional arguments to pass product quantity and price.
	 *
	 * @return float
	 */
	public static function get_inventory_price_excluding_tax( $inventory, $product, $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'qty'   => '',
				'price' => '',
			)
		);

		$price = '' !== $args['price'] ? max( 0.0, (float) $args['price'] ) : $inventory->price;
		$qty   = '' !== $args['qty'] ? max( 0.0, (float) $args['qty'] ) : 1;

		if ( '' === $price ) {
			return '';
		}
		elseif ( empty( $qty ) ) {
			return 0.0;
		}

		$line_price = $price * $qty;

		if ( $product->is_taxable() && wc_prices_include_tax() ) {

			$tax_rates      = \WC_Tax::get_rates( $product->get_tax_class() );
			$base_tax_rates = \WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
			$remove_taxes   = apply_filters( 'woocommerce_adjust_non_base_location_prices', TRUE ) ? \WC_Tax::calc_tax( $line_price, $base_tax_rates, TRUE ) : \WC_Tax::calc_tax( $line_price, $tax_rates, TRUE );
			$return_price   = $line_price - array_sum( $remove_taxes ); // Unrounded since we're dealing with tax inclusive prices. Matches logic in cart-totals class. @see adjust_non_base_location_price.

		}
		else {
			$return_price = $line_price;
		}

		return apply_filters( 'atum/multi_inventory/get_inventory_price_excluding_tax', $return_price, $qty, $inventory, $product );
	}

	/**
	 * Get the suffix to display after prices > 0.
	 *
	 * @since 1.3.7
	 *
	 * @param Inventory   $inventory
	 * @param \WC_Product $product
	 * @param string      $price     To calculate, left blank to just use get_price().
	 * @param integer     $qty       Passed on to get_price_including_tax() or get_price_excluding_tax().
	 *
	 * @return string
	 */
	public static function get_inventory_price_suffix( $inventory, $product, $price = '', $qty = 1 ) {

		$html = '';

		if ( ( $suffix = get_option( 'woocommerce_price_display_suffix' ) ) && wc_tax_enabled() && 'taxable' === $product->get_tax_status() ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found, WordPress.CodeAnalysis.AssignmentInCondition.Found

			if ( '' === $price ) {
				$price = $inventory->price;
			}

			$replacements = array(
				'{price_including_tax}' => wc_price( self::get_inventory_price_including_tax( $inventory, $product, [ 'qty' => $qty, 'price' => $price ] ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine, WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
				'{price_excluding_tax}' => wc_price( self::get_inventory_price_excluding_tax( $inventory, $product, [ 'qty' => $qty, 'price' => $price ] ) ), // @phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			);

			$html = str_replace( array_keys( $replacements ), array_values( $replacements ), ' <small class="woocommerce-price-suffix">' . wp_kses_post( $suffix ) . '</small>' );

		}

		return apply_filters( 'atum/multi_inventory/get_inventory_price_suffix', $html, $inventory, $product, $price, $qty );

	}

	/**
	 * Remove the original bundled items price filters and add them at the bottom of the pile.
	 *
	 * @since 1.3.9
	 *
	 * @param \WC_Bundled_Item $bundled_item
	 */
	public function change_bundled_items_price_filters_priority( $bundled_item ) {

		remove_filter( 'woocommerce_get_price_html', array( 'WC_PB_Product_Prices', 'filter_get_price_html' ), 10 );

		add_filter( 'woocommerce_get_price_html', array( 'WC_PB_Product_Prices', 'filter_get_price_html' ), PHP_INT_MAX, 2 );

	}

	/**
	 * Remove our bundled items price filters.
	 *
	 * @since 1.3.9
	 *
	 * @param \WC_Bundled_Item $bundled_item
	 */
	public function remove_bundled_items_price_filters( $bundled_item ) {
		remove_filter( 'woocommerce_get_price_html', array( 'WC_PB_Product_Prices', 'filter_get_price_html' ), PHP_INT_MAX );
	}

	/**
	 * Use the first available inventory price for the multi-price products added to WC Orders
	 *
	 * @param \WC_Order_Item_Product $item
	 * @param int                    $item_id
	 *
	 * @return mixed
	 *
	 * @throws \Exception
	 */
	public function use_wc_first_inventory_price( $item, $item_id ) {

		$product_id = $item->get_variation_id() ?: $item->get_product_id();

		if ( Helpers::has_multi_price( $product_id ) ) {

			$product = $item->get_product();

			// Get the purchase price (if set).
			$product_price = $product->get_price();

			$price = Helpers::get_first_inventory_prop( 'price', $product_price, $product_id );

			$total = wc_get_price_excluding_tax( $product, array(
				'qty'   => $item->get_quantity(),
				'price' => $price,
			) );

			$item->set_total( $total );
			$item->set_subtotal( $total );
			$item->save();

			$order = $item->get_order();
			$order->add_item( $item );
			$order->save();

		}

		return $item;

	}

	/**
	 * Use the first available inventory price for the multi-price products added to ATUM Orders
	 *
	 * @since 1.1.1.1
	 *
	 * @param float                        $price
	 * @param float                        $qty
	 * @param \WC_Product|AtumProductTrait $product
	 * @param AtumOrderModel               $order
	 *
	 * @return float|string
	 *
	 * @throws \Exception
	 */
	public function use_atum_first_inventory_price( $price, $qty, $product, $order ) {

		if ( Helpers::has_multi_price( $product ) ) {

			$product_id = $product->get_id();

			// For PO, purchase price must be used.
			if ( PurchaseOrders::POST_TYPE === $order->get_post_type() ) {
				$product_price = $product->get_purchase_price();
				$price         = Helpers::get_first_inventory_prop( 'purchase_price', $product_price, $product_id, FALSE );
			}
			// Inventory Logs.
			else {
				$product_price = $product->get_price();
				$price         = Helpers::get_first_inventory_prop( 'price', $product_price, $product_id, FALSE );
			}

			if ( ! $price ) {
				return '';
			}
			elseif ( empty( $qty ) ) {
				return 0.0;
			}

			if ( $product->is_taxable() && wc_prices_include_tax() ) {
				$tax_rates = \WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
				$taxes     = \WC_Tax::calc_tax( $price * $qty, $tax_rates, TRUE );
				$price     = \WC_Tax::round( $price * $qty - array_sum( $taxes ) );
			}
			else {
				$price *= $qty;
			}

		}

		return $price;

	}

	/**
	 * Overide the WC show price filter for Multiprice products.
	 *
	 * @since 1.5.4
	 *
	 * @param bool                  $show
	 * @param \WC_Product           $product
	 * @param \WC_Product_Variation $variation
	 *
	 * @return bool
	 */
	public function show_variation_price( $show, $product, $variation ) {

		if ( Helpers::has_multi_price( $variation ) ) {
			$show = TRUE;
		}

		return $show;

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
	 * @return MultiPrice instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
