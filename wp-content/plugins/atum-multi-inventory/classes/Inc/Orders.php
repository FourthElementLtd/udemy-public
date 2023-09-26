<?php
/**
 * Class to handle Orders' customisations
 *
 * @since       1.4.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     AtumMultiInventory\Inc
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Components\AtumOrders\Models\AtumOrderItemModel;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\InventoryLogs\Items\LogItemProduct;
use Atum\InventoryLogs\Models\Log;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumMultiInventory\Models\Inventory;

final class Orders {

	/**
	 * The singleton instance holder
	 *
	 * @var Orders
	 */
	private static $instance;

	/**
	 * Order line items no to be updated by WooCommerce
	 *
	 * @var array
	 */
	private $skip_line_items = [];

	/**
	 * Store the item inventories to be changed in an Order
	 *
	 * @var array
	 */
	public $updated_order_item_inventories;

	/**
	 * Will store the orders that are processed on a single requests to avoid changing stocks multiple times
	 *
	 * @var int[]
	 */
	private $processed_orders = [];

	/**
	 * Hooks singleton constructor
	 *
	 * @since 1.4.2
	 */
	private function __construct() {

		/**
		 * Admin hooks
		 */

		if ( is_admin() ) {

			// Add the MI UI to WooCommerce orders.
			add_action( 'woocommerce_order_item_line_item_html', array( $this, 'add_orders_multi_inventory_ui' ), 10, 3 );
			add_action( 'woocommerce_order_item_add_line_buttons', array( $this, 'add_new_order_item_inventory_template' ) );
			add_action( 'atum/atum_order/add_line_buttons', array( $this, 'add_new_order_item_inventory_template' ) );
			add_filter( 'woocommerce_admin_html_order_item_class', array( $this, 'add_order_item_row_class' ), 10, 3 );
			add_action( 'woocommerce_after_order_itemmeta', array( $this, 'add_mi_meta_to_order_items' ), 10, 3 );

			// Add the MI UI to ATUM orders.
			add_action( 'atum/atum_order/after_item_product_html', array( $this, 'call_add_orders_multi_inventory_ui' ), 10, 2 );
			add_filter( 'atum/atum_order/item_class', array( $this, 'add_order_item_row_class' ), 10, 3 );
			add_action( 'atum/atum_order/after_order_item_icons', array( $this, 'add_mi_meta_to_order_items' ), 10, 3 );

			// Remove the set purchase price in PO if the product has MI activated.
			add_action( 'atum/atum_order/before_item_meta', array( $this, 'maybe_remove_purchase_price' ), 10, 4 );
			add_action( 'atum/atum_order/after_item_meta', array( $this, 'maybe_re_add_purchase_price' ), 10, 4 );

			// Add the first available inventory item to a new order line.
			add_filter( 'atum/atum_order/order_item', array( $this, 'calculate_add_mi_order_item' ), 9, 4 );
			add_filter( 'woocommerce_ajax_order_item', array( $this, 'calculate_add_mi_order_item' ), 9, 4 );

			add_action( 'atum/atum_order/add_order_item_meta', array( $this, 'add_mi_order_item' ), 10, 3 );
			add_action( 'woocommerce_ajax_add_order_item_meta', array( $this, 'add_mi_order_item' ), 10, 3 );

			add_action( 'atum/atum_order/import_order_item', array( $this, 'import_mi_order_items' ), 10, 4 );

			// Catch add/edit/remove order items (SINCE WC3.6).
			if ( version_compare( WC()->version, '3.6', '>=' ) ) {
				// Prevent WC to save lines with MI products. Allow other's play before doing it (p.e. Product Levels).
				add_action( 'woocommerce_before_save_order_items', array( $this, 'prevent_update_mi_order_lines' ), PHP_INT_MAX, 2 );

				// Manage MI WC order items changes.
				add_action( 'atum/multi_inventory/after_update_mi_order_lines', array( $this, 'manage_mi_order_items_changes' ), 10, 3 );
			}

			// Save Order qtys' changes in MI Order lines (from back-end).
			add_action( 'woocommerce_before_save_order_items', array( $this, 'calculate_update_mi_order_lines' ), 9, 2 );
			add_action( 'atum/orders/before_save_items', array( $this, 'calculate_update_mi_order_lines' ), 9, 2 );
			add_action( 'woocommerce_saved_order_items', array( $this, 'update_mi_order_lines' ), 10, 2 );
			add_action( 'atum/orders/after_save_items', array( $this, 'update_mi_order_lines' ), 10, 2 );
			add_action( 'atum/purchase_orders/after_save', array( $this, 'after_save_atum_order' ), 10, 2 );
			add_action( 'atum/inventory_logs/after_save', array( $this, 'after_save_atum_order' ), 10, 2 );

			// WC product bundles compatibility.
			if ( class_exists( '\WC_Product_Bundle' ) ) {
				add_action( 'woocommerce_bundled_add_to_order', array( $this, 'add_mi_bundled_order_item' ), 10, 2 );
			}

			// Maybe restock products after refunding.
			add_action( 'woocommerce_order_refunded', array( $this, 'maybe_restock_after_refund' ), 10, 2 );

			// Show the right subtotal for MI order items.
			add_filter( 'woocommerce_order_item_get_subtotal', array( $this, 'get_order_item_subtotal' ), PHP_INT_MAX, 2 );

			// Avoid the wrong subtotal to be returned when the user is doing manual changes to an order.
			add_action( 'atum/purchase_orders/before_calculate_item_totals', array( $this, 'bypass_get_order_item_subtotal' ) );

			// Remove order item from wp_atum_inventory_orders table.
			add_action( 'woocommerce_before_delete_order_item', array( $this, 'delete_order_item_inventories' ) );
			add_action( 'atum/atum_order/delete_order_item', array( $this, 'delete_atum_order_item_inventories' ) );

			// Delete order items from wp_atum_inventory_orders table when order is removed.
			add_action( 'woocommerce_delete_order_items', array( $this, 'delete_order_inventories' ) );
			add_action( 'atum/orders/delete_order_items', array( $this, 'delete_order_inventories' ) );

			// Recalculate the ATUM props after deleting an ATUM order item.
			add_action( 'atum/orders/after_delete_item', array( $this, 'after_delete_atum_order_item' ), 10, 2 );

			// Recalculate the ATUM props after deleting an WC order item.
			add_action( 'atum/before_delete_order_item', array( $this, 'before_delete_wc_order_item' ), PHP_INT_MAX );
			add_action( 'atum/after_delete_order_item', array( $this, 'after_delete_wc_order_item' ), PHP_INT_MAX );

			// Add the MI info to ATUM orders report.
			add_action( 'atum/atum_order/after_item_product_report', array( $this, 'add_orders_multi_inventory_report' ), 10, 2 );

			// Show or hide product item sku in order report.
			add_action( 'atum/atum_order/report/show_sku', array( $this, 'show_item_product_sku_report' ), 10, 3 );

			// Cancel inventory refund after delete order refund.
			add_action( 'woocommerce_refund_deleted', array( $this, 'cancel_inventory_refund' ), 10, 2 );

			// Add inventories on update order status.
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'bulk_add_unassigned_inventories' ), 1, 3 );
			add_filter( 'handle_bulk_actions-edit-atum_purchase_order', array( $this, 'bulk_add_unassigned_inventories' ), 1, 3 );
			add_action( 'wp_ajax_woocommerce_mark_order_status', array( $this, 'add_orders_unassigned_inventories' ), 1 );
			add_action( 'wp_ajax_atum_order_mark_status', array( $this, 'add_atum_orders_unassigned_inventories' ), 1 );
		}

		/**
		 * Global hooks
		 */

		// Add the order item inventories after creating an order.
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_mi_order_lines' ), PHP_INT_MAX, 3 );

		if ( class_exists( '\WC_Subscriptions' ) ) {
			add_filter( 'wcs_renewal_order_created', array( $this, 'add_mi_renewal_order_lines' ), 10, 2 );
		}

		// Hack the 'wc_reduce_stock_levels' and 'wc_increase_stock_levels' function to reduce the right product's inventory stock.
		add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'can_reduce_stock' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_can_restore_order_stock', array( $this, 'can_increase_stock' ), PHP_INT_MAX, 2 ); // For WC 3.5.0+ only.

		// Hack the PO's 'maybe_decrease_stock_levels' and 'maybe_increase_stock_levels' function to reduce the right product's inventory stock.
		add_filter( 'atum/purchase_orders/can_reduce_order_stock', array( $this, 'atum_order_can_reduce_stock' ), 100, 2 );
		add_filter( 'atum/purchase_orders/can_restore_order_stock', array( $this, 'atum_order_can_increase_stock' ), 100, 2 );

		// Update the sales-related calculated props when changing an order's status.
		add_action( 'atum/orders/status_changed', array( $this, 'update_inventory_calc_props_when_transitioning' ), 10, 4 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'update_inventory_calc_props_when_transitioning' ), 10, 4 );

		// When a restriction mode is on, the user should be informed that some items on his cart are not available on the selected address.
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'chekout_update_order' ) );

		// Recalculate the latest inventories sales every time an order is processed.
		add_action( 'atum/after_save_order_item_props', array( $this, 'recalculate_order_item_inventories_data' ), 10, 2 );

	}

	/**
	 * Add the MI UI to WooCommerce orders
	 *
	 * @since 1.0.0
	 *
	 * @param int                      $item_id
	 * @param \WC_Order_Item_Product   $item
	 * @param \WC_Order|AtumOrderModel $order
	 *
	 * @throws \Exception
	 */
	public function add_orders_multi_inventory_ui( $item_id, $item, $order = NULL ) {

		$product_id             = $item->get_variation_id() ?: $item->get_product_id();
		$product                = AtumHelpers::get_atum_product( $product_id );
		$has_multi_inventory    = 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product );
		$order_type             = $order instanceof \WC_Order ? $order->get_type() : $order->get_post_type();
		$order_type_table_id    = Globals::get_order_type_table_id( $order_type );
		$order_item_inventories = Inventory::get_order_item_inventories( $item_id, $order_type_table_id );

		if ( $has_multi_inventory || ! empty( $order_item_inventories ) ) {

			$is_atum_order = method_exists( $item, 'get_atum_order_id' );

			if ( ! $order ) {
				// Is an Atum Order?
				$order = $is_atum_order ? AtumHelpers::get_atum_order_model( $item->get_atum_order_id(), FALSE ) : $item->get_order();
			}

			if ( in_array( $order_type, Globals::get_order_types(), TRUE ) ) {
				$action      = $order->get_action();
				$data_prefix = ATUM_PREFIX;
			}
			else {
				$action      = 'both';
				$data_prefix = '';
			}

			$has_multi_price         = Helpers::has_multi_price( $product );
			$has_reduced_stock       = ( $item->get_meta( '_reduced_stock', TRUE ) );
			$currency                = $is_atum_order ? $order->currency : $order->get_currency();
			$region_restriction_mode = Helpers::get_region_restriction_mode();

			AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/meta-boxes/order-items/mi-panel', compact( 'order', 'item_id', 'item', 'product', 'order_item_inventories', 'region_restriction_mode', 'action', 'data_prefix', 'currency', 'has_multi_price', 'order_type_table_id', 'has_reduced_stock' ) );

		}

	}

	/**
	 * Add the JS template for adding new inventories to the orders
	 *
	 * @since 1.4.0
	 *
	 * @param \WC_Order $order
	 */
	public function add_new_order_item_inventory_template( $order ) {

		$is_atum_order           = $order instanceof AtumOrderModel;
		$currency                = $is_atum_order ? $order->currency : $order->get_currency();
		$order_item_inventory    = $item = $product = NULL;
		$order_type_table_id     = Globals::get_order_type_table_id( $is_atum_order ? $order->get_post_type() : $order->get_type() );
		$class_line_delete       = '';
		$region_restriction_mode = Helpers::get_region_restriction_mode();
		?>
		<script type="text/template" id="new-order-item-inventory">
			<?php AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/meta-boxes/order-items/inventory', compact( 'order', 'item', 'order_item_inventory', 'currency', 'order_type_table_id', 'product', 'class_line_delete', 'region_restriction_mode' ) ); ?>
		</script>
		<?php

	}

	/**
	 * Add an extra class to order item rows with MI UI within
	 *
	 * @since 1.0.0
	 *
	 * @param string                 $class
	 * @param \WC_Order_Item_Product $item
	 * @param \WC_Order              $order
	 *
	 * @return string
	 */
	public function add_order_item_row_class( $class, $item, $order ) {

		$product             = $item->get_product();
		$has_multi_inventory = 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product );

		if ( $has_multi_inventory ) {

			$class = $class ? " $class with-mi" : 'with-mi';

			if ( Helpers::has_multi_price( $product ) ) {
				$class .= ' mi-multi-price';
			}

		}

		return $class;
	}

	/**
	 * Add the MI icon to distinguish the order items that have MI enabled
	 *
	 * @since 1.0.1
	 *
	 * @param int                    $item_id
	 * @param \WC_Order_Item_Product $item
	 * @param \WC_Product            $product
	 */
	public function add_mi_meta_to_order_items( $item_id, $item, $product ) {

		if ( $item instanceof \WC_Order_Item_Product ) : ?>

			<?php $has_mi = 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product ); ?>

			<?php if ( ! doing_action( 'atum/atum_order/after_order_item_icons' ) ) : ?>
				<div class="order-item-icons">
			<?php endif; ?>

			<?php if ( $has_mi ) : ?>
				<a href="<?php echo esc_url( Helpers::get_documentation_link( $item->get_order_id() ) ) ?>" target="_blank">
					<span class="atmi-multi-inventory tips" data-tip="<?php esc_attr_e( "This item has Multi-Inventory enabled. To be able to add/remove/change inventories to/from this item, please click on the 'Edit item' button", ATUM_MULTINV_TEXT_DOMAIN ) ?>"></span>
				</a>
			<?php endif; ?>

			<?php if ( ! doing_action( 'atum/atum_order/after_order_item_icons' ) ) : ?>
				</div>
			<?php endif; ?>

		<?php endif;

	}

	/**
	 * Call add_orders_multi_inventory_ui from do_action call without item_id
	 *
	 * @since 1.0.1
	 *
	 * @param \WC_Order_Item_Product   $item
	 * @param \WC_Order|AtumOrderModel $atum_order
	 *
	 * @throws \Exception
	 */
	public function call_add_orders_multi_inventory_ui( $item, $atum_order ) {

		$order_post_type = $atum_order instanceof AtumOrderModel ? $atum_order->get_post_type() : $atum_order->get_type();

		if ( PurchaseOrders::POST_TYPE === $order_post_type ) {
			add_filter( 'atum/multi_inventory/meta_boxes/order-items/management-popup/price', array( Hooks::get_instance(), 'get_mi_purchase_price' ), 10, 4 );
		}

		$this->add_orders_multi_inventory_ui( $item->get_id(), $item, $atum_order );

	}

	/**
	 * Remove the change purchase button if is PO and item has MI enabled
	 *
	 * @since 1.1.1.1
	 *
	 * @param int                    $item_id
	 * @param \WC_Order_Item_Product $item
	 * @param \WC_Product            $product
	 * @param AtumOrderModel         $order
	 */
	public function maybe_remove_purchase_price( $item_id, $item, $product, $order ) {

		if (
			'line_item' === $item->get_type() && PurchaseOrders::POST_TYPE === $order->get_post_type() &&
			Helpers::has_multi_price( $product )
		) {

			remove_action( 'atum/atum_order/item_meta_controls', array( PurchaseOrders::get_instance(), 'set_purchase_price_button' ) );
		}
	}


	/**
	 * Add the change purchase button if it has been removed by maybe_remove_purchase_price
	 *
	 * @since 1.1.1.1
	 *
	 * @param int                    $item_id
	 * @param \WC_Order_Item_Product $item
	 * @param \WC_Product            $product
	 * @param AtumOrderModel         $order
	 */
	public function maybe_re_add_purchase_price( $item_id, $item, $product, $order ) {

		if (
			'line_item' === $item->get_type() && PurchaseOrders::POST_TYPE === $order->get_post_type() &&
			Helpers::has_multi_price( $product )
		) {

			add_action( 'atum/atum_order/item_meta_controls', array( PurchaseOrders::get_instance(), 'set_purchase_price_button' ), 10, 2 );
		}
	}

	/**
	 * Calculate the MI order items to be created in add_mi_order_item.
	 * This function is needed to know the Inventory orders before adding BOM order items.
	 *
	 * @change Allow adding more than one qty product in the line on v1.4.3
	 * @change Only create inventories for WC_Orders if the status implies changing the stock.
	 *
	 * @since 1.3.0
	 *
	 * @param \WC_Order_Item_Product|AtumOrderItemProduct $item
	 * @param int                                         $item_id
	 * @param \WC_Order|AtumOrderModel|PurchaseOrder      $order
	 * @param \WC_Product                                 $product
	 *
	 * @return \WC_Order_Item_Product|AtumOrderItemProduct
	 * @throws \Exception
	 */
	public function calculate_add_mi_order_item( $item, $item_id, $order, $product ) {

		// Is Multi-inventory enabled?
		if ( 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product ) ) {

			$order_id      = $order->get_id();
			$order_type    = $order instanceof \WC_Order ? $order->get_type() : $order->get_post_type();
			$order_type_id = Globals::get_order_type_table_id( $order_type );
			$product_id    = $product->get_id();

			// As this is a backend call, inventory iteration is not used.
			$inventories = Helpers::get_product_inventories_sorted( $product_id );

			if ( ! empty( $inventories ) ) {

				$has_multi_price = Helpers::has_multi_price( $product );

				$this->updated_order_item_inventories[ $order_id ]             = [];
				$this->updated_order_item_inventories[ $order_id ][ $item_id ] = [ 'insert' => [] ];

				$remaining_qty = $item->get_quantity();

				// WC Orders.
				if ( 1 === $order_type_id && in_array( AtumHelpers::get_raw_wc_order_status( $order->get_status() ), Globals::get_order_statuses_change_stock() ) ) {

					$inventories_qty = count( $inventories );

					for ( $i = 0; $i < $inventories_qty; $i++ ) {

						$inventory = $inventories[ $i ];

						if ( 0 >= $remaining_qty ) {
							break;
						}

						if ( $inventory->is_main() ) {
							$inventory->set_stock_status();
						}

						if ( 'outofstock' === $inventory->stock_status ) {
							continue;
						}

						if ( $inventory->managing_stock() ) {

							// Also set all the remaining qty if it's the last inventory in the set.
							if ( 'no' !== $inventory->backorders || $remaining_qty <= $inventory->stock_quantity || $i === $inventories_qty - 1 ) {

								$used_qty      = $remaining_qty;
								$remaining_qty = 0;

							}
							else {

								$used_qty       = $inventory->stock_quantity;
								$remaining_qty -= $used_qty;

							}

						}
						else {
							$used_qty      = $remaining_qty;
							$remaining_qty = 0;
						}

						$price  = $has_multi_price ? $inventory->price : $product->get_price();
						$totals = $sub_totals = wc_get_price_excluding_tax( $product, array(
							'price' => $price,
							'qty'   => $used_qty,
						) );

						$this->updated_order_item_inventories[ $order_id ][ $item_id ]['insert'][ $inventory->id ] = [
							'qty'           => $used_qty,
							'subtotal'      => $sub_totals,
							'total'         => $totals,
							'reduced_stock' => $used_qty,
							'extra_data'    => maybe_serialize( $inventory->prepare_order_item_inventory_extra_data( $item_id ) ),
						];
					}

					// if there is stock left to allocate, use the first inventory.
					if ( $remaining_qty ) {

						$inventory_to_use = reset( $inventories );

						$pre_used_qty = isset( $this->updated_order_item_inventories[ $order_id ][ $item_id ]['insert'][ $inventory_to_use->id ] ) ?
							$this->updated_order_item_inventories[ $order_id ][ $item_id ]['insert'][ $inventory_to_use->id ]['qty'] : 0;
						$used_qty     = $remaining_qty + $pre_used_qty;

						$price  = $has_multi_price ? $inventory_to_use->price : $product->get_price();
						$totals = $sub_totals = wc_get_price_excluding_tax( $product, array(
							'price' => $price,
							'qty'   => $used_qty,
						) );

						$this->updated_order_item_inventories[ $order_id ][ $item_id ]['insert'][ $inventory_to_use->id ] = [
							'qty'           => $used_qty,
							'subtotal'      => $sub_totals,
							'total'         => $totals,
							'reduced_stock' => $used_qty,
							'extra_data'    => maybe_serialize( $inventory_to_use->prepare_order_item_inventory_extra_data( $item_id ) ),
						];

					}

				}
				// ATUM Orders.
				elseif ( 3 === $order_type_id ) {

					$inventory_to_use = NULL;

					/* @deprecated v1.8.5 We no longer add MIs to POs when an item is added */

					/*
					// Purchase Orders.
					if ( 2 === $order_type_id ) {

						// Determine if it is a Purchase Order within unique supplier.
						$required_supplier = ! $order->has_multiple_suppliers() ? $order->get_supplier()->id : FALSE;

						// Loop all the product inventories until we find one "out of stock" (Purchase Orders).
						foreach ( $inventories as $inventory ) {

							if ( $inventory->is_main() ) {
								$inventory->set_stock_status();
							}

							// POs with unique supplier.
							if ( $required_supplier ) {

								/**
								 * Inventories priority
								 *
								 * 1. Inventories that matches supplier with PO and outofstock stock status
								 * 2. Inventories that matches supplier with PO and having stock
								 * 3. Inventories without supplier and outofstock
								 * 4. Inventories without supplier and stock
								 */

								/*
								// Do not allow inventories with different supplier.
								if ( $inventory->supplier_id && intval( $required_supplier ) !== intval( $inventory->supplier_id ) ) {
									continue;
								}

								// If the inventory supplier matches with PO supplier, use it.
								if ( intval( $required_supplier ) === intval( $inventory->supplier_id ) ) {
									$inventory_to_use = $inventory;

									// Main priority: outofstock and matching supplier.
									if ( 'outofstock' === $inventory->stock_status ) {
										break;
									}

								}
								// When the inventory has not supplier assigned we can use it, but don't discard an inventory with supplier.
								elseif ( 0 === intval( $inventory->supplier_id ) && ( is_null( $inventory_to_use ) || 0 === intval( $inventory_to_use->supplier_id ) ) ) {

									// Priority to use outofstock inventories.
									if ( is_null( $inventory_to_use ) || ( 'outofstock' === $inventory->stock_status && 'outofstock' !== $inventory_to_use->stock_status ) ) {
										$inventory_to_use = $inventory;
									}
								}

							}
							// Multiple Suppliers, take the first out of stock.
							elseif ( 'outofstock' === $inventory->stock_status ) {

								$inventory_to_use = $inventory;
								break;
							}
						}

						// A product without inventories from the same Supplier in the PO ??
						if ( $required_supplier ) {
							return $item;
						}

					}
					*/

					if ( is_null( $inventory_to_use ) ) {

						// Just use the first one.
						$inventory_to_use = current( $inventories );
					}

					/* @deprecated v1.8.5 We no longer add MIs to POs when an item is added */

					/*
					 Only left the else term.
					if ( 2 === $order_type_id ) {
						$product = AtumHelpers::get_atum_product( $product );
						$price   = $has_multi_price ? $inventory_to_use->purchase_price : $product->get_purchase_price();
					}
					else {
					*/
					$price = $has_multi_price ? $inventory_to_use->price : $product->get_price();
					/*}*/

					$totals = $sub_totals = wc_get_price_excluding_tax( $product, array(
						'price' => $price,
						'qty'   => $remaining_qty,
					) );

					$this->updated_order_item_inventories[ $order_id ][ $item_id ]['insert'][ $inventory_to_use->id ] = [
						'qty'           => $remaining_qty,
						'subtotal'      => $sub_totals,
						'total'         => $totals,
						'reduced_stock' => $remaining_qty,
						'extra_data'    => maybe_serialize( $inventory_to_use->prepare_order_item_inventory_extra_data( $item_id ) ),
					];

				}

			}

		}

		return $item;

	}

	/**
	 * Assign an item from first Inventory Available to a new Order Item
	 *
	 * Executed:
	 * - When adding an "Order Item" manually from the backend's (edit order screen).
	 *
	 * @since 1.0.6
	 *
	 * @param int                                         $item_id
	 * @param \WC_Order_Item_Product|AtumOrderItemProduct $item
	 * @param \WC_Order|AtumOrderModel                    $order
	 *
	 * @throws \Exception
	 */
	public function add_mi_order_item( $item_id, $item, $order ) {

		if ( ! empty( $this->updated_order_item_inventories[ $order->get_id() ][ $item_id ]['insert'] ) ) {

			$order_post_type = $order instanceof \WC_Order ? $order->get_type() : $order->get_post_type();
			$order_type_id   = Globals::get_order_type_table_id( $order_post_type );

			if ( $order_type_id ) {

				$product = $item->get_product();

				if ( $product instanceof \WC_Product ) {

					// there should only be 1 inventory.
					foreach ( $this->updated_order_item_inventories[ $order->get_id() ][ $item_id ]['insert'] as $inventory_id => $data ) {

						$inventory_to_use = Helpers::get_inventory( $inventory_id );
						$inventory_to_use->save_order_item_inventory( $item_id, $product->get_id(), $data, $order_type_id );

					}

					do_action( 'atum/multi_inventory/after_add_mi_order_item', $item, $this->updated_order_item_inventories, $order );
				}

			}

			unset( $this->updated_order_item_inventories[ $order->get_id() ] );

		}

	}

	/**
	 * Copy the WC Order MI Items to an ATUM Item (it only works for Inventory Logs).
	 *
	 * @since 1.3.6
	 *
	 * @param LogItemProduct         $atum_item
	 * @param Log                    $atum_order
	 * @param \WC_Order_Item_Product $item
	 * @param \WC_Order              $wc_order
	 */
	public function import_mi_order_items( $atum_item, $atum_order, $item, $wc_order ) {

		$original_order_item_inventories = Inventory::get_order_item_inventories( $item->get_id(), Globals::get_order_type_table_id( $wc_order->get_type() ) );

		if ( $original_order_item_inventories ) {

			$product_id     = $item->get_product_id();
			$unwanted_keys  = [ 'id', 'order_item_id', 'product_id', 'order_type_id', 'inventory_id' ];
			$main_inventory = Inventory::get_product_main_inventory( $product_id );

			foreach ( $original_order_item_inventories as $original_order_item_inventory ) {

				$original_order_item_inventory = (array) $original_order_item_inventory;
				$inventory_id                  = $original_order_item_inventory['inventory_id'];

				foreach ( $unwanted_keys as $unwanted_key ) {
					unset( $original_order_item_inventory[ $unwanted_key ] );
				}

				$inventory = $inventory_id === $main_inventory->id ? $main_inventory : Helpers::get_inventory( $inventory_id );
				$inventory->save_order_item_inventory( $atum_item->get_id(), $product_id, $original_order_item_inventory, Globals::get_order_type_table_id( $atum_order->get_post_type() ) );

			}

		}

	}

	/**
	 * Prevent WC from updating the line items with MI products
	 *
	 * @since 1.2.0
	 *
	 * @param int   $order_id
	 * @param array $items
	 */
	public function prevent_update_mi_order_lines( $order_id, $items ) {

		// Line items and fees. defined( 'DOING_AJAX' ) && DOING_AJAX.
		if ( isset( $items['order_item_id'], $items['oi_inventory_qty'] ) ) {

			foreach ( $items['order_item_id'] as $item_id ) {
				$item = \WC_Order_Factory::get_order_item( absint( $item_id ) );

				if ( ! $item || 'line_item' !== $item->get_type() ) {
					continue;
				}

				// Has a multi-inventory product.
				if ( ! empty( $items['oi_inventory_qty'][ $item_id ] ) ) {
					$this->skip_line_items[] = $item_id;
				}

			}

			if ( $this->skip_line_items ) {
				add_filter( 'woocommerce_get_order_item_classname', array( $this, 'maybe_return_empty_classname' ), 10, 3 );
			}
		}

	}

	/**
	 * Return an empty string classname if the current item id  is in 'skip_line_items'.
	 * Remove the filter if the variable is empty
	 *
	 * @since 1.2.0
	 *
	 * @param string $classname
	 * @param string $item_type
	 * @param int    $id        Item id.
	 *
	 * @return string
	 */
	public function maybe_return_empty_classname( $classname, $item_type, $id ) {

		$key = array_search( $id, $this->skip_line_items );

		if ( FALSE !== $key ) {

			$classname = '';
			unset( $this->skip_line_items[ $key ] );

		}

		if ( empty( $this->skip_line_items ) ) {
			remove_filter( 'woocommerce_get_order_item_classname', array( $this, 'maybe_return_empty_classname' ), 10 );
		}

		return $classname;
	}

	// TODO: Refactory to get this code working inside a WC_Order_Item's child class (when saving and deleting lines).
	/**
	 * Manage changes in MI order items, For now only for WC Orders
	 * This function is wc_save_order_items without the updates stock part and without the fee item part
	 *
	 * @since 1.2.0
	 *
	 * @param \WC_Order|AtumOrderModel $order
	 * @param array                    $changed_order_item_inventories
	 * @param array                    $original_items
	 */
	public function manage_mi_order_items_changes( $order, $changed_order_item_inventories, $original_items ) {

		$order_post_type = $order instanceof AtumOrderModel ? $order->get_post_type() : $order->get_type();
		$order_type_id   = Globals::get_order_type_table_id( $order_post_type );

		$data_keys = array(
			'line_tax'             => array(),
			'line_subtotal_tax'    => array(),
			'order_item_name'      => null,
			'order_item_qty'       => null,
			'order_item_tax_class' => null,
			'line_total'           => null,
			'line_subtotal'        => null,
		);

		// Line items and fees and only for WC Orders.
		if ( 1 === $order_type_id && isset( $original_items['order_item_id'] ) && $changed_order_item_inventories ) {

			foreach ( $changed_order_item_inventories as $item_id => $item_inventories ) {

				// TODO: Use item_inventories to change the inventories stock.
				$item = \WC_Order_Factory::get_order_item( absint( $item_id ) );

				if ( ! $item ) {
					continue;
				}

				$item_data = array();

				foreach ( $data_keys as $key => $default ) {
					$item_data[ $key ] = isset( $original_items[ $key ][ $item_id ] ) ? wc_check_invalid_utf8( wp_unslash( $original_items[ $key ][ $item_id ] ) ) : $default;
				}

				if ( '0' === $item_data['order_item_qty'] ) {
					$item->delete();
					continue;
				}

				$item->set_props(
					array(
						'name'      => $item_data['order_item_name'],
						'quantity'  => $item_data['order_item_qty'],
						'tax_class' => $item_data['order_item_tax_class'],
						'total'     => $item_data['line_total'],
						'subtotal'  => $item_data['line_subtotal'],
						'taxes'     => array(
							'total'    => $item_data['line_tax'],
							'subtotal' => $item_data['line_subtotal_tax'],
						),
					)
				);

				if ( isset( $original_items['meta_key'][ $item_id ], $original_items['meta_value'][ $item_id ] ) ) {

					foreach ( $original_items['meta_key'][ $item_id ] as $meta_id => $meta_key ) {
						$meta_key   = substr( wp_unslash( $meta_key ), 0, 255 );
						$meta_value = isset( $original_items['meta_value'][ $item_id ][ $meta_id ] ) ? wp_unslash( $original_items['meta_value'][ $item_id ][ $meta_id ] ) : '';

						if ( '' === $meta_key && '' === $meta_value ) {
							if ( ! strstr( $meta_id, 'new-' ) ) {
								$item->delete_meta_data_by_mid( $meta_id );
							}
						}
						elseif ( strstr( $meta_id, 'new-' ) ) {
							$item->add_meta_data( $meta_key, $meta_value, FALSE );
						}
						else {
							$item->update_meta_data( $meta_key, $meta_value, $meta_id );
						}
					}

				}

				// Allow other plugins to change item object before it is saved.
				do_action( 'woocommerce_before_save_order_item', $item );

				$item->save();

			}

			// Calculate discounts and taxes.
			$order->update_taxes();
			$order->calculate_totals( false );

		}

	}

	/**
	 * Calculate the changes in the order items that will ve updated in update_mi_order_lines.
	 * This function is needed to know the changes to be done in lines in products with MI before perform the changes for BOM order items.
	 *
	 * @since 1.3.0
	 *
	 * @param int|AtumOrderModel $order_id
	 * @param array              $items
	 * @param boolean            $delete_not_found Whether to delete or not not present item inventories. Defaults to yes. Used to allow the API updating items without deleting others not present.
	 *
	 * @throws \Exception
	 */
	public function calculate_update_mi_order_lines( $order_id, $items, $delete_not_found = TRUE ) {

		$is_atum_order      = $order_id instanceof AtumOrderModel;
		$order_item_id_name = $is_atum_order ? 'atum_order_item_id' : 'order_item_id';

		// Line items and fees.
		if ( isset( $items[ $order_item_id_name ] ) ) {

			$order = $is_atum_order ? $order_id : wc_get_order( $order_id );

			if ( ! $order ) {
				return;
			}

			$order_id              = $order->get_id();
			$order_post_type       = $is_atum_order ? $order->get_post_type() : $order->get_type();
			$order_type_id         = Globals::get_order_type_table_id( $order_post_type );
			$order_status          = AtumHelpers::get_raw_wc_order_status( $order->get_status() );
			$change_stock_statuses = Globals::get_order_statuses_change_stock();

			if ( ! $order_type_id ) {
				return;
			}

			do_action( 'atum/multi_inventory/before_calculate_update_mi_order_lines', $order, $items );

			$this->updated_order_item_inventories[ $order_id ] = [];

			// Will be false for ATUM Orders.
			$allow_change_stock = in_array( $order_status, $change_stock_statuses );

			// Only create inventory order items for WC_Orders when transitioning from a status that doesn't change the
			// stock to other status that does.
			// To do this, fake the oi_inventory... variables in items.
			if (
				$order instanceof \WC_Order && ! empty( $_POST['order_status'] ) &&
				! $allow_change_stock && AtumHelpers::get_raw_wc_order_status( $_POST['order_status'] ) !== $order_status &&
				in_array( AtumHelpers::get_raw_wc_order_status( $_POST['order_status'] ), $change_stock_statuses )
			) {

				$order_item_inventories = Helpers::prepare_order_items_inventories( $order );

				foreach ( $order_item_inventories as $item_id => $item_inventories ) {

					if ( empty( $items['oi_inventory_qty'][ $item_id ] ) && ! empty( $item_inventories ) ) {

						// It doesn't exist in items so we add the skip line here.
						$this->skip_line_items[] = $item_id;

						if ( ! array_key_exists( $item_id, $items['oi_inventory_qty'] ) ) {
							$items['oi_inventory_qty'][ $item_id ]      = [];
							$items['oi_inventory_total'][ $item_id ]    = [];
							$items['oi_inventory_subtotal'][ $item_id ] = [];
						}

						foreach ( $item_inventories as $inventory_id => $item_inventory ) {

							$items['oi_inventory_qty'][ $item_id ][ $inventory_id ]      = $item_inventory['data']['qty'];
							$items['oi_inventory_total'][ $item_id ][ $inventory_id ]    = $item_inventory['data']['total'];
							$items['oi_inventory_subtotal'][ $item_id ][ $inventory_id ] = $item_inventory['data']['subtotal'];
						}

					}
				}

			}

			foreach ( $items[ $order_item_id_name ] as $item_id ) {

				$order_item = $order->get_item( $item_id );

				if ( ! $order_item instanceof \WC_Order_Item_Product ) {
					continue;
				}

				/**
				 * Variable definition
				 *
				 * @var \WC_Order_Item_Product $order_item
				 */
				$product_id = $order_item->get_variation_id() ?: $order_item->get_product_id();

				// Check that the product still exists.
				$product = wc_get_product( $product_id );

				if ( ! $product instanceof \WC_Product || 'no' === Helpers::get_product_multi_inventory_status( $product ) || ! Helpers::is_product_multi_inventory_compatible( $product ) ) {
					continue;
				}

				$this->updated_order_item_inventories[ $order_id ][ $item_id ] = [];

				$saved_order_item_inventories = Inventory::get_order_item_inventories( $item_id, $order_type_id );
				$main_inventory               = Inventory::get_product_main_inventory( $product_id );

				if ( ! empty( $items['oi_inventory_qty'][ $item_id ] ) ) {

					// Any of the current item inventories has reduced stock NULL?
					$has_reduced_null = FALSE;

					// Add the new inventories setup.
					foreach ( $items['oi_inventory_qty'][ $item_id ] as $inventory_id => $qty ) {

						$data             = [];
						$inventory_id     = absint( $inventory_id );
						$inventory        = $inventory_id === $main_inventory->id ? $main_inventory : Helpers::get_inventory( $inventory_id ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
						$data['qty']      = floatval( $qty );
						$data['total']    = ! empty( $items['oi_inventory_total'][ $item_id ][ $inventory_id ] ) ? floatval( wc_format_decimal( $items['oi_inventory_total'][ $item_id ][ $inventory_id ] ) ) : 0.0;
						$data['subtotal'] = apply_filters( 'atum/multi_inventory/calculate_update_mi_order_lines/line_subtotal', ! empty( $items['oi_inventory_subtotal'][ $item_id ][ $inventory_id ] ) ? floatval( wc_format_decimal( $items['oi_inventory_subtotal'][ $item_id ][ $inventory_id ] ) ) : 0, $items, $item_id, $inventory_id );

						$matching_inventory_order = wp_list_filter( $saved_order_item_inventories, [ 'inventory_id' => $inventory_id ] );

						if ( $inventory->exists() ) {
							$data['extra_data'] = maybe_serialize( $inventory->prepare_order_item_inventory_extra_data( $item_id ) );
						}

						// If this order item already has an entry matching the current, update it (if needed).
						if ( ! empty( $matching_inventory_order ) ) {

							$old_changed_inventory_order = $matching_inventory_order;
							// Remove the processed item from the saved inventories list.
							unset( $saved_order_item_inventories[ key( $matching_inventory_order ) ] );

							$matching_inventory_order = reset( $matching_inventory_order );
							$matching_qty             = floatval( $matching_inventory_order->qty );
							$matching_total           = floatval( wc_format_decimal( $matching_inventory_order->total ) );
							$matching_subtotal        = floatval( wc_format_decimal( $matching_inventory_order->subtotal ) );
							$matching_reduced_stock   = floatval( $matching_inventory_order->reduced_stock );

							if ( ! $inventory->exists() ) {
								$data['extra_data'] = $matching_inventory_order->extra_data;
							}

							if ( is_null( $matching_inventory_order->reduced_stock ) ) {
								$has_reduced_null = TRUE;
							}

							// Only apply the change if the used stocks don't match.
							if ( $matching_qty !== $data['qty'] || $matching_total !== $data['total'] || $matching_subtotal !== $data['subtotal'] ) {

								if ( ! $data['qty'] ) {

									$this->updated_order_item_inventories[ $order_id ][ $item_id ]['delete'][] = array(
										'data'      => $data,
										'qty'       => $matching_reduced_stock,
										'inventory' => $inventory,
										'old'       => $old_changed_inventory_order,
									);
								}
								// If is null, it has no reduced qty, so only change qtys without changing the stock.
								elseif ( is_null( $matching_inventory_order->reduced_stock ) ) {

									$this->updated_order_item_inventories[ $order_id ][ $item_id ]['increase'][] = array(
										'data'          => $data,
										'qty'           => $matching_qty - $data['qty'],
										'reduced_stock' => NULL,
										'inventory'     => $inventory,
										'old'           => $old_changed_inventory_order,
									);

								}
								else {

									if ( $matching_reduced_stock >= $data['qty'] ) {
										$data['reduced_stock'] = $data['qty'];
										$increase              = $matching_reduced_stock - $data['qty'];
									}
									else {
										$data['reduced_stock'] = $matching_reduced_stock + $data['qty'] - $matching_qty;
										$increase              = $matching_qty - $data['qty'];
									}

									$this->updated_order_item_inventories[ $order_id ][ $item_id ]['increase'][] = array(
										'data'      => $data,
										'qty'       => $increase,
										'inventory' => $inventory,
										'old'       => $old_changed_inventory_order,
									);

								}

							}

						}
						// Just add a new order item entry.
						elseif ( $data['qty'] ) {

							$data['reduced_stock'] = ! $allow_change_stock || $has_reduced_null ? NULL : $data['qty'];

							$this->updated_order_item_inventories[ $order_id ][ $item_id ]['decrease'][] = array(
								'data'      => $data,
								'inventory' => $inventory,
								'qty'       => $data['reduced_stock'],
							);

						}

					}

				}

				// Loop all the old saved entries and remove those that are not needed anymore.
				if ( ! empty( $saved_order_item_inventories ) && $delete_not_found ) {

					foreach ( $saved_order_item_inventories as $saved_order_item_inventory ) {

						$inventory_id = absint( $saved_order_item_inventory->inventory_id );
						$inventory    = $inventory_id === $main_inventory->id ? $main_inventory : Helpers::get_inventory( $inventory_id );

						$this->updated_order_item_inventories[ $order_id ][ $item_id ]['delete'][] = array(
							'data'      => [],
							'qty'       => $saved_order_item_inventory->reduced_stock,
							'inventory' => $inventory,
							'old'       => $saved_order_item_inventory,
						);

					}
				}

			}

		}

	}

	// TODO: Refactory to get this code working inside a WC_Order_Item's child class (when saving and deleting lines).
	/**
	 * Update MI order inventory lines
	 *
	 * Executed only in the Edit Order screen:
	 * - When Change "Inventory order item" stock.
	 * - When Add/remove "Inventory order item" (not when add/remove Order Items).
	 * - When remove "Inventory order item" by assigning stock 0 to that inventory.
	 * - When changing to status complete, on-hold, processing from any other status.
	 * - When changing order status from completed, on-hold, processing to others.
	 *
	 * @since 1.0.1
	 *
	 * @param int|AtumOrderModel $order_id
	 * @param array              $items
	 *
	 * @throws \Exception
	 */
	public function update_mi_order_lines( $order_id, $items ) {

		if ( $order_id instanceof AtumOrderModel ) {
			$order              = $order_id;
			$order_type_id      = Globals::get_order_type_table_id( $order->get_post_type() );
			$allow_change_stock = FALSE;
		}
		else {
			$order              = wc_get_order( $order_id );
			$order_type_id      = Globals::get_order_type_table_id( $order->get_type() );
			$allow_change_stock = in_array( AtumHelpers::get_raw_wc_order_status( $order->get_status() ), Globals::get_order_statuses_change_stock() );
		}

		$order_id = $order->get_id();

		$changed_order_item_inventories = [];

		if ( $order_type_id && ! empty( $this->updated_order_item_inventories[ $order_id ] ) ) {

			$changes = [];

			// Delete inventories.
			if ( ! empty( $this->updated_order_item_inventories[ $order_id ] ) ) {

				foreach ( $this->updated_order_item_inventories[ $order_id ] as $item_id => $actions ) {

					$changed_order_item_inventories[ $item_id ] = [];
					$order_item                                 = $order->get_item( $item_id );
					$already_reduced_stock                      = wc_stock_amount( $order_item->get_meta( '_reduced_stock', TRUE ) );

					/**
					 * Variable definition
					 *
					 * @var \WC_Order_Item_Product $order_item
					 */
					$product_id = $order_item->get_variation_id() ?: $order_item->get_product_id();

					// Check that the product still exists.
					$product = wc_get_product( $product_id );

					if ( ! empty( $actions['delete'] ) ) {

						foreach ( $actions['delete'] as $inventory_order_data ) {

							/**
							 * Variable definition
							 *
							 * @var Inventory $inventory
							 */
							$inventory = $inventory_order_data['inventory'];
							$inventory->delete_order_item_inventory( $item_id, $order_id, $order_type_id );

							if ( ! $inventory->exists() )
								continue;

							if ( $allow_change_stock && $inventory_order_data['qty'] && $already_reduced_stock ) {

								$already_reduced_stock -= $inventory_order_data['qty'];
								$old_stock              = $inventory->stock_quantity;
								$new_stock              = Helpers::update_inventory_stock( $product, $inventory, $inventory_order_data['qty'], 'increase' );
								/* translators: the inventory name */
								$changes[] = ' ' . $old_stock . '&rarr;' . $new_stock . ' ' . sprintf( __( 'using inventory &quot;%s&quot;', ATUM_MULTINV_TEXT_DOMAIN ), $inventory->name );

							}

							$changed_order_item_inventories[ $item_id ][ $inventory->id ] = array(
								'old' => $inventory_order_data['old'],
								'new' => $inventory_order_data['data'],
							);

							// Update the inventory extra props for the ATUM Orders.
							if ( in_array( $order_type_id, [ 2, 3 ] ) ) {
								Helpers::update_order_item_inventories_sales_calc_props( $inventory, $order_type_id );
							}

						}
					}

					foreach ( [ 'increase', 'decrease' ] as $action ) {

						if ( ! empty( $actions[ $action ] ) ) {

							foreach ( $actions[ $action ] as $inventory_order_data ) {

								/**
								 * Variable definition
								 *
								 * @var Inventory $inventory
								 */
								$inventory = $inventory_order_data['inventory'];
								$inventory->save_order_item_inventory( $item_id, $product_id, $inventory_order_data['data'], $order_type_id );

								if ( ! $inventory->exists() )
									continue;

								if ( $inventory_order_data['qty'] ) {

									if ( $allow_change_stock ) {
										$already_reduced_stock = 'increase' === $action ? $already_reduced_stock - $inventory_order_data['qty'] : $already_reduced_stock + $inventory_order_data['qty'];
										$old_stock             = $inventory->stock_quantity;
										$new_stock             = Helpers::update_inventory_stock( $product, $inventory, $inventory_order_data['qty'], $action );
										/* translators: the inventory name */
										$changes[] = ' ' . $old_stock . '&rarr;' . $new_stock . ' ' . sprintf( __( 'using inventory &quot;%s&quot;', ATUM_MULTINV_TEXT_DOMAIN ), $inventory->name );
									}

								}

								$changed_order_item_inventories[ $item_id ][ $inventory->id ] = array(
									'old' => isset( $inventory_order_data['old'] ) ? $inventory_order_data['old'] : [],
									'new' => $inventory_order_data['data'],
								);

								// Update the inventory extra props for the ATUM Orders.
								if ( in_array( $order_type_id, [ 2, 3 ] ) ) {
									Helpers::update_order_item_inventories_sales_calc_props( $inventory, $order_type_id );
								}

							}

						}
					}

					if ( ! empty( $changes ) ) {

						$product_name = $product instanceof \WC_Product ? $product->get_formatted_name() : '';

						/* translators: the product name */
						$note_id = $order->add_order_note( sprintf( __( 'Stock levels changed: [%s],', ATUM_MULTINV_TEXT_DOMAIN ) . ' ' . implode( ', ', $changes ), $product_name ) );
						AtumHelpers::save_order_note_meta( $note_id, [
							'action'     => "stock_levels_changed",
							'item_name'  => $product_name,
							'product_id' => $product_id,
							'changes'    => $changes,
						] );

						$order_item->update_meta_data( '_reduced_stock', $already_reduced_stock );
						$order_item->save_meta_data();
					}

				}

			}

			// the order is processed, remove it from memory.
			unset( $this->updated_order_item_inventories[ $order_id ] );

		}

		do_action( 'atum/multi_inventory/after_update_mi_order_lines', $order, $changed_order_item_inventories, $items );

	}

	/**
	 * Save the ATUM extra props after saving an ATUM Order.
	 *
	 * @since 1.2.0
	 *
	 * @param AtumOrderModel $atum_order
	 * @param array          $items
	 */
	public function after_save_atum_order( $atum_order, $items ) {

		foreach ( $items as $item ) {

			/**
			 * Variable declaration
			 *
			 * @var AtumOrderItemProduct $item
			 */
			if ( 'line_item' !== $item->get_type() ) {
				continue;
			}

			$this->recalculate_order_item_inventories_data( $item, $atum_order->get_id(), Globals::get_order_type_table_id( $atum_order->get_post_type() ) );

		}

	}

	/**
	 * Recalculate the order item inventory's data every time an order is processed or changed
	 *
	 * @since 1.2.0
	 *
	 * @param \WC_Order_Item_Product $order_item
	 * @param int                    $order_id
	 * @param int                    $order_type
	 */
	public function recalculate_order_item_inventories_data( $order_item, $order_id, $order_type = 1 ) {

		$order_item_inventories = Inventory::get_order_item_inventories( $order_item->get_id(), $order_type );

		if ( ! empty( $order_item_inventories ) ) {

			foreach ( $order_item_inventories as $order_item_inv ) {
				$inventory = Helpers::get_inventory( $order_item_inv->inventory_id );
				Helpers::update_order_item_inventories_sales_calc_props( $inventory, $order_type );
			}

		}

	}

	/**
	 * Assign an item from first Inventory Available to a new Order Item for product bundles
	 *
	 * @since 1.0.7.7
	 *
	 * @param int                      $bundled_order_item_id
	 * @param \WC_Order|AtumOrderModel $order
	 *
	 * @throws \Exception
	 */
	public function add_mi_bundled_order_item( $bundled_order_item_id, $order ) {

		$items = $order->get_items();

		foreach ( $items as $item ) {
			$this->add_mi_order_item( $item->get_id(), $item, $order );
		}

	}

	/**
	 * Do the restock after the refund if restock_refunded_items was marked
	 *
	 * @since 1.0.1
	 *
	 * @param int $order_id
	 * @param int $refund_id
	 *
	 * @throws \Exception
	 */
	public function maybe_restock_after_refund( $order_id, $refund_id ) {

		global $order_refund_restock;

		$line_item_qtys       = json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_qtys'] ) ), TRUE );
		$line_item_totals     = json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_totals'] ) ), TRUE );
		$line_item_tax_totals = json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_tax_totals'] ) ), TRUE );

		// Prepare line items which we are refunding.
		$line_items = array();
		$item_ids   = array_unique( array_merge( array_keys( $line_item_qtys, $line_item_totals ) ) );

		foreach ( $item_ids as $item_id ) {

			$line_items[ $item_id ] = array(
				'qty'          => 0,
				'refund_total' => 0,
				'refund_tax'   => array(),
			);

		}

		foreach ( $line_item_qtys as $item_id => $qty ) {
			$line_items[ $item_id ]['qty'] = max( $qty, 0 );
		}

		foreach ( $line_item_totals as $item_id => $total ) {
			$line_items[ $item_id ]['refund_total'] = wc_format_decimal( $total );
		}

		foreach ( $line_item_tax_totals as $item_id => $tax_totals ) {
			$line_items[ $item_id ]['refund_tax'] = array_filter( array_map( 'wc_format_decimal', $tax_totals ) );
		}

		parse_str( $_POST['refund_inventory'], $inventory_data );

		$order_post_type = get_post_type( $order_id );
		$order           = in_array( $order_post_type, Globals::get_order_types(), TRUE ) ? AtumHelpers::get_atum_order_model( $order_id, TRUE ) : wc_get_order( $order_id );
		$order_type_id   = Globals::get_order_type_table_id( $order_post_type );

		if ( ! $order_type_id ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {

			/**
			 * Each order product line
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			$product             = $item->get_product();
			$product_id          = $product->get_id();
			$item_id             = $item->get_id();
			$has_multi_inventory = Helpers::get_product_multi_inventory_status( $product );

			if ( 'yes' === $has_multi_inventory && Helpers::is_product_multi_inventory_compatible( $product ) ) {

				// Unset the item on line_items.
				unset( $line_items[ $item_id ] );

				$saved_order_item_inventories = Inventory::get_order_item_inventories( $item_id, $order_type_id );
				$main_inventory               = Inventory::get_product_main_inventory( $product_id );

				// Update the inventory refund.
				if ( ! empty( $inventory_data['oi_inventory_refund_qty'][ $item_id ] ) ) {

					foreach ( $inventory_data['oi_inventory_refund_qty'][ $item_id ] as $inventory_id => $refund_qty ) {

						$inventory_id = absint( $inventory_id );
						$inventory    = $inventory_id == $main_inventory->id ? $main_inventory : Helpers::get_inventory( $inventory_id ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
						$refund_qty   = floatval( $refund_qty );
						$refund_total = ! empty( $inventory_data['oi_inventory_refund'][ $item_id ][ $inventory_id ] ) ? floatval( wc_format_decimal( $inventory_data['oi_inventory_refund'][ $item_id ][ $inventory_id ] ) ) : 0;

						$matching_inventory_order = wp_list_filter( $saved_order_item_inventories, [ 'inventory_id' => $inventory_id ] );

						// If this order item already has an entry matching the current, update it (if needed).
						if ( ! empty( $matching_inventory_order ) ) {

							$matching_inventory_order = reset( $matching_inventory_order );
							$matching_refund_qty      = empty( $matching_inventory_order->refund_qty ) ? 0 : floatval( $matching_inventory_order->refund_qty );
							$matching_refund_total    = empty( $matching_inventory_order->refund_total ) ? 0 : floatval( wc_format_decimal( $matching_inventory_order->refund_total ) );

							$inventory->save_order_item_inventory_refund( $item_id, $matching_refund_qty + $refund_qty, $matching_refund_total + $refund_total, $order_type_id );

							if ( ! empty( $order_refund_restock[ $order_id ] ) ) {
								Helpers::update_inventory_stock( $product, $inventory, $refund_qty, 'increase' );
							}

						}

						// Remove the processed item from the saved inventories list.
						unset( $saved_order_item_inventories[ key( $matching_inventory_order ) ] );

					}

				}

			}
		}

		$line_items = apply_filters( 'atum/multi_inventory/lines_after_refunded', $line_items, $order );

		// Refresh non-MI products' stock (from wc_create_refund).
		if ( ! empty( $order_refund_restock[ $order_id ] ) ) {
			wc_restock_refunded_items( $order, $line_items );
		}
	}

	/**
	 * Show the right subtotal for order items with MI enabled
	 *
	 * @since 1.0.1
	 *
	 * @param string                 $subtotal
	 * @param \WC_Order_Item_Product $order_item
	 *
	 * @return string
	 */
	public function get_order_item_subtotal( $subtotal, $order_item ) {

		if ( $order_item instanceof \WC_Order_Item_Product ) {

			$cache_key       = AtumCache::get_cache_key( 'order_item_subtotal', $order_item->get_id() );
			$cached_subtotal = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

			if ( $has_cache ) {
				return $cached_subtotal;
			}

			// WC Product Bundles compatibility
			// TODO: for now this is not compatible with ATUM orders, as it's failing on the "wc_pb_is_bundled_order_item" function.
			if ( class_exists( '\WC_Product_Bundle' ) && ! is_callable( array( $order_item, 'get_atum_order_id' ) ) ) {

				// We must disable the filter to avoid endless loops.
				remove_filter( 'woocommerce_order_item_get_subtotal', array( $this, 'get_order_item_subtotal' ), PHP_INT_MAX );
				$is_bundled_order_item = wc_pb_is_bundled_order_item( $order_item );
				add_filter( 'woocommerce_order_item_get_subtotal', array( $this, 'get_order_item_subtotal' ), PHP_INT_MAX, 2 );

				if ( $is_bundled_order_item ) {
					// AtumCache::set_cache( $cache_key, $subtotal, ATUM_MULTINV_TEXT_DOMAIN );
					return $subtotal;
				}

			}

			if ( ! empty( $_POST['order_id'] ) ) {
				$order_id = $_POST['order_id'];
			}
			elseif ( ! empty( $_POST['atum_order_id'] ) ) {
				$order_id = $_POST['atum_order_id'];
			}
			else {
				$order_id = method_exists( $order_item, 'get_atum_order_id' ) ? $order_item->get_atum_order_id() : $order_item->get_order_id();
			}

			$order_type = get_post_type( $order_id );

			if ( ! $order_type ) {
				// AtumCache::set_cache( $cache_key, $subtotal, ATUM_MULTINV_TEXT_DOMAIN );
				return $subtotal;
			}

			$table_id = Globals::get_order_type_table_id( $order_type );

			if ( $table_id ) { // No need to check if the product is MI enabled, if there're lines it "was" a MI product.

				$order_item_inventories = Inventory::get_order_item_inventories( $order_item->get_id(), $table_id );

				if ( ! empty( $order_item_inventories ) ) {

					$subtotal = 0;

					foreach ( $order_item_inventories as $order_item_inventory ) {
						$subtotal += floatval( wc_format_decimal( $order_item_inventory->subtotal ) );
					}

				}

				// TODO: Removed to avoid error calculating subtotal, by MA.
				// AtumCache::set_cache( $cache_key, $subtotal, ATUM_MULTINV_TEXT_DOMAIN );

			}

		}

		return (string) $subtotal;

	}

	/**
	 * Bypass the get_order_item_subtotal hook when calculating order totals.
	 *
	 * @since 1.3.4
	 *
	 * @param AtumOrderModel $atum_order
	 */
	public function bypass_get_order_item_subtotal( $atum_order = NULL ) {
		remove_filter( 'woocommerce_order_item_get_subtotal', array( $this, 'get_order_item_subtotal' ), PHP_INT_MAX );
	}

	/**
	 * Remove order item from atum_inventory_orders table.
	 *
	 * @since 1.0.1
	 *
	 * Executed:
	 * - When Delete an "Order Item" from the backend edit order screen.
	 *
	 * @param string $order_item_id
	 * @param int    $order_type    Defaults to 1 (wc_order). Any other order item must specify its order type.
	 *
	 * @throws \Exception
	 */
	public function delete_order_item_inventories( $order_item_id, $order_type = 1 ) {

		global $wpdb;

		if ( 1 === $order_type ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $order_item
			 */
			$order_item = \WC_Order_Factory::get_order_item( $order_item_id );

			if ( $order_item && 'line_item' === $order_item->get_type() ) {

				$product_id = $order_item->get_variation_id() ?: $order_item->get_product_id();
				$product    = wc_get_product( $product_id );

				$saved_order_item_inventories = Inventory::get_order_item_inventories( $order_item_id, $order_type );
				$main_inventory               = Inventory::get_product_main_inventory( $product_id );

				$changes = [];

				foreach ( $saved_order_item_inventories as $order_item_inventory ) {

					$inventory = $order_item_inventory->inventory_id === $main_inventory->id ? $main_inventory : Helpers::get_inventory( $order_item_inventory->inventory_id );

					if ( $order_item_inventory->reduced_stock ) {

						$old_stock = $inventory->stock_quantity;
						$new_stock = Helpers::update_inventory_stock( $product, $inventory, $order_item_inventory->reduced_stock, 'increase' );
						/* translators: the inventory name */
						$changes[] = ' ' . $old_stock . '&rarr;' . $new_stock . ' ' . sprintf( __( 'using inventory &quot;%s&quot;', ATUM_MULTINV_TEXT_DOMAIN ), $inventory->name );

					}

				}

				if ( ! empty( $changes ) ) {

					$order        = $order_item->get_order();
					$product_name = $product instanceof \WC_Product ? $product->get_formatted_name() : '';

					/* translators: the product name */
					$note_id = $order->add_order_note( sprintf( __( 'Stock levels increased: [%s],', ATUM_MULTINV_TEXT_DOMAIN ) . ' ' . implode( ', ', $changes ), $product_name ) );
					AtumHelpers::save_order_note_meta( $note_id, [
						'action'     => 'stock_levels_increased',
						'item_name'  => $product_name,
						'product_id' => $product->get_id(),
						'changes'    => $changes,
					] );
				}

			}
		}

		// Deleting the items this way is faster than doing it one at a time.
		$wpdb->delete(
			$wpdb->prefix . Inventory::INVENTORY_ORDERS_TABLE,
			array(
				'order_item_id' => $order_item_id,
				'order_type'    => $order_type,
			),
			array(
				'%d',
				'%d',
			)
		);

	}

	/**
	 * Remove order item from atum_inventory_orders table.
	 *
	 * @since 1.2.6
	 *
	 * @param int $order_item_id
	 *
	 * @throws \Exception
	 */
	public function delete_atum_order_item_inventories( $order_item_id ) {

		$order = AtumHelpers::get_atum_order_model_from_item_id( $order_item_id );

		if ( ! $order instanceof \WP_Error ) {
			self::delete_order_item_inventories( $order_item_id, Globals::get_order_type_table_id( $order->get_post_type() ) );
		}
	}

	/**
	 * Remove order items from atum_inventory_orders table when an order is removed.
	 *
	 * @since 1.0.1
	 *
	 * @param int $order_id
	 */
	public function delete_order_inventories( $order_id ) {

		$post_type   = get_post_type( $order_id );
		$order_type  = Globals::get_order_type_table_id( $post_type );
		$order       = in_array( $post_type, Globals::get_order_types(), TRUE ) ? AtumHelpers::get_atum_order_model( $order_id, TRUE ) : wc_get_order( $order_id );
		$order_items = $order->get_items();

		if ( ! empty( $order_items ) ) {
			foreach ( $order_items as $item ) {
				$this->delete_order_item_inventories( $item->get_id(), $order_type );
			}
		}

	}

	/**
	 * Recalculate the ATUM props after deleting an order item
	 *
	 * @since 1.2.0
	 *
	 * @param AtumOrderItemModel   $item
	 * @param AtumOrderItemProduct $atum_order_item
	 */
	public function after_delete_atum_order_item( $item, $atum_order_item ) {

		if ( 'line_item' === $atum_order_item->get_type() ) {
			$order = $atum_order_item->get_order();
			$this->recalculate_order_item_inventories_data( $atum_order_item, $order->get_id(), Globals::get_order_type_table_id( $order->get_post_type() ) );
		}

	}

	/**
	 * Store the inventories from which we need to re-calc before deleting an Order Item.
	 *
	 * @since 1.3.0
	 *
	 * @param int $order_item_id
	 */
	public function before_delete_wc_order_item( $order_item_id ) {

		// It's always a WC Order.
		$order_item_inventories = Inventory::get_order_item_inventories( $order_item_id );

		if ( $order_item_inventories ) {
			global $atum_mi_wc_delete_item_inventories;

			$atum_mi_wc_delete_item_inventories = $order_item_inventories;
		}
	}

	/**
	 * Update the inventories stats for stored inventories.
	 *
	 * @since 1.3.0
	 *
	 * @param int $order_item_id
	 */
	public function after_delete_wc_order_item( $order_item_id ) {

		global $atum_mi_wc_delete_item_inventories;

		if ( $atum_mi_wc_delete_item_inventories ) {

			foreach ( $atum_mi_wc_delete_item_inventories as $order_item_inv ) {
				$inventory = Helpers::get_inventory( $order_item_inv->inventory_id );
				Helpers::update_order_item_inventories_sales_calc_props( $inventory, 1 );
			}

		}
	}

	/**
	 * Render MI sku information in order report
	 *
	 * @since 1.3.9
	 *
	 * @param \WC_Order_Item_Product   $item
	 * @param \WC_Order|AtumOrderModel $atum_order
	 */
	public function add_orders_multi_inventory_report( $item, $atum_order ) {

		$product_id          = $item->get_variation_id() ?: $item->get_product_id();
		$product             = AtumHelpers::get_atum_product( $product_id );
		$has_multi_inventory = 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product );

		if ( $has_multi_inventory ) {

			$is_atum_order = method_exists( $item, 'get_atum_order_id' );

			if ( ! $atum_order ) {
				// Is an Atum Order?
				$atum_order = $is_atum_order ? AtumHelpers::get_atum_order_model( $item->get_atum_order_id(), FALSE ) : $item->get_order();
			}

			$order_type = $atum_order instanceof \WC_Order ? $atum_order->get_type() : $atum_order->get_post_type();

			$order_type_table_id    = Globals::get_order_type_table_id( $order_type );
			$order_item_inventories = Inventory::get_order_item_inventories( $item->get_id(), $order_type_table_id );

			AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/reports/mi-sku', compact( 'item', 'product', 'order_item_inventories' ) );

		}
	}

	/**
	 * Show or hide product item sku in order report
	 *
	 * @since 1.3.9
	 *
	 * @param bool                     $show
	 * @param \WC_Order_Item_Product   $item
	 * @param \WC_Order|AtumOrderModel $order
	 *
	 * @return bool
	 */
	public function show_item_product_sku_report( $show, $item, $order ) {

		$product             = $item->get_product();
		$has_multi_inventory = 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product );

		if ( $has_multi_inventory ) {
			return FALSE;
		}

		return $show;

	}

	/**
	 * Add the inventory order items when creating a new WC Order (from front-end)
	 *
	 * @since 1.2.0
	 *
	 * @param int       $order_id
	 * @param array     $posted_data
	 * @param \WC_Order $order
	 */
	public function add_mi_order_lines( $order_id, $posted_data, $order ) {

		if ( $order instanceof \WC_Order ) {

			$this->add_order_item_inventories( $order );

		}

	}

	/**
	 * Add the inventory order items when creating a new WC Order (from front-end)
	 *
	 * @since 1.5.4
	 *
	 * @param \WC_Order|AtumOrderModel $order
	 */
	public function add_order_item_inventories( $order ) {

		$order_items_inventories = Helpers::prepare_order_items_inventories( $order );
		$order_post_type         = $order instanceof AtumOrderModel ? $order->get_post_type() : $order->get_type();
		$order_type_id           = Globals::get_order_type_table_id( $order_post_type );

		if ( ! empty( $order_items_inventories ) ) {

			$order_items = $order->get_items();

			//$allow_change_stock = in_array( AtumHelpers::get_raw_wc_order_status( $order->get_status() ), Globals::get_order_statuses_change_stock() );

			foreach ( $order_items_inventories as $order_item_id => $inventories_data ) {

				foreach ( $inventories_data as $inventory_id => $order_item_inventory ) {

					/**
					 * Variable definition
					 *
					 * @var Inventory $inventory
					 */
					$inventory  = $order_item_inventory['inventory'];
					$product_id = $order_item_inventory['product_id'];
					$data       = $order_item_inventory['data'];

					// WC Product Bundles compatibility.
					if ( class_exists( '\WC_Product_Bundle' ) && isset( $order_items[ $order_item_id ] ) && wc_pb_is_bundled_order_item( $order_items[ $order_item_id ] ) ) {

						$order_item          = $order_items[ $order_item_id ];
						$bundled_item_id     = absint( $order_item->get_meta( '_bundled_item_id' ) );
						$stamp               = $order_item->get_meta( '_stamp' );
						$priced_individually = $order_item->get_meta( '_bundled_item_priced_individually' );

						// We just need to recalculate the totals if an item discount was applied to a bundled item.
						if (
							$bundled_item_id && 'yes' === $priced_individually && is_array( $stamp ) &&
							! empty( $stamp[ $bundled_item_id ] ) && ! empty( $stamp[ $bundled_item_id ]['discount'] )
						) {
							$discount         = $stamp[ $bundled_item_id ]['discount'];
							$data['total']    = \WC_PB_Product_Prices::get_discounted_price( (float) $data['total'], $discount );
							$data['subtotal'] = \WC_PB_Product_Prices::get_discounted_price( (float) $data['subtotal'], $discount );
						}

					}

					// Add a new inventory order item to the Inventory Orders table.
					$inventory->save_order_item_inventory( $order_item_id, $product_id, $data, $order_type_id );

					// Update inventory's calculated order data.
					Helpers::update_order_item_inventories_sales_calc_props( $inventory, $order_type_id );

				}

			}

		}

	}

	/**
	 * Add the inventory order items when creating a new Renewal Order for WC Subscriptions.
	 *
	 * @since 1.3.10
	 *
	 * @param \WC_Order              $renewal_order
	 * @param int | \WC_Subscription $subscription
	 *
	 * @return \WC_Order
	 */
	public function add_mi_renewal_order_lines( $renewal_order, $subscription ) {

		// The items aren't included in the renewal order though they exist, so we need to re-read the order.
		$order = wc_get_order( $renewal_order );

		$this->add_mi_order_lines( 0, [], $order );

		return $renewal_order;
	}

	/**
	 * Tells WooCommerce whether is allowed to reduce the stock levels for the order passed.
	 *
	 * @since 1.0.0
	 *
	 * @param bool      $allowed
	 * @param \WC_Order $order
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public function can_reduce_stock( $allowed, $order ) {
		return $this->can_change_stock( $allowed, $order, 'reduce' );
	}

	/**
	 * Tells WooCommerce whether is allowed to restore the stock levels for the order passed.
	 *
	 * @since 1.0.1
	 *
	 * @param bool      $allowed
	 * @param \WC_Order $order
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public function can_increase_stock( $allowed, $order ) {
		return $this->can_change_stock( $allowed, $order, 'increase' );
	}

	/**
	 * Check if the order item is a MI item and change the stock accordingly to the action passed
	 *
	 * @since 1.0.1
	 *
	 * @param bool      $allowed
	 * @param \WC_Order $order
	 * @param string    $action
	 *
	 * @return bool
	 */
	private function can_change_stock( $allowed, $order, $action ) {

		// Check if any of the items within the order has Multi-Inventories.
		// If so, we'll reduce the stock levels from the methods below.
		$items    = $order->get_items();
		$order_id = $order->get_id();

		foreach ( $items as $item ) {

			/**
			 * Each order product line
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			$product = $item->get_product();

			if (
				$product instanceof \WC_Product && 'yes' === Helpers::get_product_multi_inventory_status( $product ) &&
				Helpers::is_product_multi_inventory_compatible( $product )
			) {

				// The first item found would trigger our own increase/reduce methods
				// and would prevent WC from deducting when returning FALSE.
				if ( 'reduce' === $action ) {
					$this->maybe_reduce_stock_levels( $order_id );
				}
				else {
					$this->maybe_increase_stock_levels( $order_id );
				}

				$allowed = FALSE;

				break; // Stock already changed. No need to continue checking the other items.

			}

		}

		return apply_filters( "atum/multi_inventory/can_{$action}_order_stock", $allowed, $order );

	}

	/**
	 * When a payment is complete, we can reduce multi-inventory stock levels for items within an order.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id Order ID.
	 *
	 * @throws \Exception
	 */
	public function maybe_reduce_stock_levels( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$stock_reduced  = $order->get_data_store()->get_stock_reduced( $order_id );
		$trigger_reduce = apply_filters( 'woocommerce_payment_complete_reduce_order_stock', ! $stock_reduced, $order_id );

		// Only continue if we're reducing stock.
		if ( ! $trigger_reduce ) {
			return;
		}

		Helpers::reduce_stock_levels( $order );

		// Ensure stock is marked as "reduced" in case payment complete or other stock actions are called.
		$order->get_data_store()->set_stock_reduced( $order_id, TRUE );

	}

	/**
	 * When a payment is cancelled, restore stock.
	 *
	 * @since 1.0.1
	 *
	 * @param int $order_id Order ID.
	 *
	 * @throws \Exception
	 */
	public function maybe_increase_stock_levels( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$stock_reduced    = $order->get_data_store()->get_stock_reduced( $order_id );
		$trigger_increase = (bool) $stock_reduced;

		// Only continue if we're reducing stock.
		if ( ! $trigger_increase ) {
			return;
		}

		Helpers::increase_stock_levels( $order );

		// Ensure stock is unmarked as "reduced" to be able to reduce it again when needed.
		$order->get_data_store()->set_stock_reduced( $order_id, FALSE );

	}

	/**
	 * Tells ATUM Orders whether is allowed to reduce the stock levels for the order passed.
	 *
	 * @since 1.5.0
	 *
	 * @param bool           $allowed
	 * @param AtumOrderModel $order
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public function atum_order_can_reduce_stock( $allowed, $order ) {
		return $this->atum_order_can_change_stock( $allowed, $order, 'decrease' );
	}

	/**
	 * Tells ATUM Orders whether is allowed to restore the stock levels for the order passed.
	 *
	 * @since 1.5.0
	 *
	 * @param bool           $allowed
	 * @param AtumOrderModel $order
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	public function atum_order_can_increase_stock( $allowed, $order ) {
		return $this->atum_order_can_change_stock( $allowed, $order, 'increase' );
	}

	/**
	 * Check if the order has MI enabled items and change the stock accordingly to the action passed
	 *
	 * @since 1.5.0
	 *
	 * @param bool           $allowed
	 * @param AtumOrderModel $order
	 * @param string         $action
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	private function atum_order_can_change_stock( $allowed, $order, $action ) {

		$order_id = $order->get_id();

		// Avoid processing the same order multiple times.
		if ( in_array( $order->get_id(), $this->processed_orders ) ) {
			return FALSE;
		}

		// Check if any of the items within the order has Multi-Inventories.
		// If so, we'll reduce the stock levels from the methods below.
		$items = $order->get_items();

		foreach ( $items as $item ) {

			/**
			 * Each order product line
			 *
			 * @var AtumOrderItemProduct $item
			 */
			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			$product = $item->get_product();

			if (
				$product instanceof \WC_Product && 'yes' === Helpers::get_product_multi_inventory_status( $product ) &&
				Helpers::is_product_multi_inventory_compatible( $product )
			) {

				// The first item found would trigger our own increase/reduce methods and exit the loop
				// and would prevent ATUM from deducting when returning FALSE.
				$allowed = FALSE;

				// On POs, if the item has already discounted the stock, bypass the change.
				// For example, the 'stock_change' is sent on an API call because was already changed on the ATUM's App.
				if ( PurchaseOrders::POST_TYPE === $order->get_post_type() && 'yes' === $item->get_stock_changed() ) {
					break;
				}

				if ( ! apply_filters( "atum/multi_inventory/{$action}_order_stock", TRUE, $order_id ) ) {
					break;
				}

				// Perform the stock change to all the PO items.
				Helpers::atum_order_change_stock_levels( $order_id, $action );
				$this->processed_orders[] = $order_id;
				break;

			}

		}

		return apply_filters( "atum/multi_inventory/atum_order_can_{$action}_order_stock", $allowed, $order );

	}

	/**
	 * Update ATUM inventories' calculated props that depend exclusively on the sale.
	 *
	 * @since 1.3.6
	 *
	 * @param int       $order_id
	 * @param string    $old_status
	 * @param string    $new_status
	 * @param \WC_Order $order
	 */
	public function update_inventory_calc_props_when_transitioning( $order_id, $old_status, $new_status, $order ) {

		$order_post_type = $order instanceof AtumOrderModel ? $order->get_post_type() : $order->get_type();
		$order_type_id   = Globals::get_order_type_table_id( $order_post_type );
		$items           = $order->get_items();

		foreach ( $items as $item ) {

			$order_item_inventories = Inventory::get_order_item_inventories( $item->get_id(), $order_type_id );

			if ( ! empty( $order_item_inventories ) ) {

				foreach ( $order_item_inventories as $order_item_inventory ) {
					Helpers::update_order_item_inventories_sales_calc_props( Helpers::get_inventory( $order_item_inventory->inventory_id ), $order_type_id );
				}

			}

		}

	}

	/**
	 * When a restriction mode is on, the user should be informed that some items on his cart are not available
	 * on the selected address
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_data
	 *
	 * @throws \Exception
	 */
	public function chekout_update_order( $post_data ) {

		// Only when a restriction mode is enabled.
		$restriction_mode = Helpers::get_region_restriction_mode();
		if ( 'no-restriction' === $restriction_mode ) {
			return;
		}

		parse_str( $post_data, $post_data_params );

		if ( wc_ship_to_billing_address_only() ) {

			$shipping_address = array(
				'country'  => esc_attr( $post_data_params['billing_country'] ),
				'state'    => isset( $post_data_params['billing_state'] ) ? esc_attr( $post_data_params['billing_state'] ) : '',
				'postcode' => esc_attr( $post_data_params['billing_postcode'] ),
			);

		}
		else {

			$get_shipping_address = isset( $post_data_params['ship_to_different_address'] ) && '1' === $post_data_params['ship_to_different_address'];

			$shipping_address['country']  = esc_attr( $get_shipping_address ? $post_data_params['shipping_country'] : $post_data_params['billing_country'] );
			$shipping_address['postcode'] = esc_attr( $get_shipping_address ? $post_data_params['shipping_postcode'] : $post_data_params['billing_postcode'] );

			if ( $get_shipping_address ) {
				$shipping_address['state'] = esc_attr( isset( $post_data_params['shipping_state'] ) ? $post_data_params['shipping_state'] : '' );
			}
			else {
				$shipping_address['state'] = esc_attr( isset( $post_data_params['billing_state'] ) ? $post_data_params['billing_state'] : '' );
			}

		}

		$customer_location = Helpers::get_visitor_location();

		if (
			( 'country' === $restriction_mode && $shipping_address['country'] !== $customer_location['country'] ) ||
			( 'shipping-zones' === $restriction_mode && $shipping_address !== $customer_location )
		) {

			// Set the location cookie to the new shipping address.
			$region = ! empty( $shipping_address['state'] ) ? $shipping_address['country'] . ':' . $shipping_address['state'] : $shipping_address['country'];
			GeoPrompt::set_location_cookie( array(
				'region'   => $region,
				'postcode' => $shipping_address['postcode'],
			) );

			// As the region has changed, we must delete the conflicting cache that was saved for the old region.
			Helpers::set_visitor_location( $shipping_address );
			$cart_items = WC()->cart->get_cart_contents();

			foreach ( $cart_items as $cart_item ) {
				$cart_product_id              = $cart_item['variation_id'] ?: $cart_item['product_id'];
				$inventories_sorted_cache_key = AtumCache::get_cache_key( 'product_inventories_sorted', $cart_product_id );
				AtumCache::delete_cache( $inventories_sorted_cache_key, ATUM_MULTINV_TEXT_DOMAIN );
			}

			// TODO: Test item stock including BOMs when adding compatibility with PL.
			$valid_stock = WC()->cart->check_cart_item_stock();

			if ( is_wp_error( $valid_stock ) ) {

				// Get order review fragment.
				ob_start();
				woocommerce_order_review();
				$woocommerce_order_review = ob_get_clean();

				wp_send_json(
					array(
						'result'    => 'failure',
						'messages'  => '<div class="woocommerce-error">' . $valid_stock->get_error_message() . '</div>',
						'reload'    => 'true',
						'fragments' => apply_filters(
							'woocommerce_update_order_review_fragments',
							array(
								'.woocommerce-checkout-review-order-table' => $woocommerce_order_review,
								'.woocommerce-checkout-payment'            => '<div id="payment" class="woocommerce-checkout-payment"></div>', // Empty the payment fragment.
							)
						),
					)
				);

			}

		}

	}

	/**
	 * If we we add order item inventories to any order item from the server-side,
	 * the related order item might remain with outdated/incorrect data (cost, taxes, etc)
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Order_Item_Product $order_item
	 * @param int                    $order_type_id
	 */
	public static function recalculate_order_item_totals( $order_item, $order_type_id ) {

		$order_item_inventories = Inventory::get_order_item_inventories( $order_item->get_id(), $order_type_id );

		if ( ! empty( $order_item_inventories ) ) {

			$quantity = $subtotal = $total = 0;

			// The order item data must be the sum of every linked order item inventories' data.
			foreach ( $order_item_inventories as $order_item_inventory ) {

				$quantity += $order_item_inventory->qty;
				$subtotal += $order_item_inventory->subtotal;
				$total    += $order_item_inventory->total;

			}

			$order_item->set_quantity( $quantity );
			$order_item->set_subtotal( $subtotal );
			$order_item->set_total( $total );
			$order_item->save();
			$order_item->calculate_taxes();

		}

	}

	/**
	 * Remove refund information from order item inventories when refund is deleted from the order.
	 *
	 * @since 1.4.9
	 *
	 * @param int $refund_id
	 * @param int $order_id
	 */
	public function cancel_inventory_refund( $refund_id, $order_id ) {

		if ( $refund_id && 'shop_order_refund' === get_post_type( $refund_id ) ) {
			$refund = wc_get_order( $refund_id );
		}

		$order = wc_get_order( $order_id );

		if ( is_wp_error( $order ) || empty( $order ) ) {
			return;
		}

		$order_items = $order->get_items();

		foreach ( $order_items as $order_item_id => $item ) {

			$refunded_qty           = $order->get_qty_refunded_for_item( $order_item_id );
			$refunded_total         = $order->get_total_refunded_for_item( $order_item_id );
			$order_item_inventories = Inventory::get_order_item_inventories( $item->get_id(), 1 );

			$product        = $item->get_product();
			$product_id     = $product->get_id();
			$main_inventory = Inventory::get_product_main_inventory( $product_id );

			if ( ! empty( $order_item_inventories ) ) {

				$inventory_updated = FALSE;

				foreach ( $order_item_inventories as $order_item_inventory ) {
					/**
					 * Variable definition
					 *
					 * @var Inventory $inventory
					 */
					$inventory = $order_item_inventory->inventory_id == $main_inventory->id ? $main_inventory : Helpers::get_inventory( $order_item_inventory->inventory_id ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					if ( $inventory_updated ) {
						$inventory->save_order_item_inventory_refund( $order_item_id, 0, '', 1 );

					}
					elseif ( $order_item_inventory->refund_total !== $refunded_total || $order_item_inventory->refund_qty !== $refunded_qty ) {
						$inventory->save_order_item_inventory_refund( $order_item_id, $refunded_qty, $refunded_total, 1 );
						$inventory_updated = TRUE;
					}

				}
			}

		}

	}

	/**
	 * Add unassigned inventories to MI products items on completed orders/atum_orders.
	 *
	 * @since 1.5.4
	 *
	 * @param string $sendback
	 * @param string $action
	 * @param array  $post_ids
	 *
	 * @return string
	 */
	public function bulk_add_unassigned_inventories( $sendback, $action, $post_ids ) {

		if( FALSE === in_array( $action, [ 'mark_processing', 'mark_completed', 'atum_order_mark_atum_received' ] ) ) {
			return $sendback;
		}

		$new_status = str_replace( 'atum_order_', '', str_replace( 'mark_', '', $action ) );

		foreach ( $post_ids as $order_id ) {

			$post_type     = get_post_type( $order_id );
			$is_atum_order = 'shop_order' === $post_type ? FALSE : TRUE;

			/**
			 * Variable definition
			 *
			 * @var \WC_Order|AtumOrderModel $order
			 */
			$order        = $is_atum_order ? AtumHelpers::get_atum_order_model( $order_id, TRUE ) : wc_get_order( $order_id );
			$new_status   = $is_atum_order ? $new_status : AtumHelpers::get_raw_wc_order_status( $new_status );

			if ( is_wp_error( $order ) ) {
				return $sendback;
			}

			$this->process_orders_inventories( $order, $new_status );

		}

		return $sendback;

	}

	/**
	 * Add unassigned inventories to MI products items just in case of completed orders.
	 *
	 * @since 1.5.4
	 */
	public function add_atum_orders_unassigned_inventories() {

		if ( ! check_admin_referer( 'atum-order-mark-status' ) ) {
			return;
		}

		$atum_order_id = absint( $_GET['atum_order_id'] );
		$new_status    = sanitize_text_field( $_GET['status'] );
		$order         = AtumHelpers::get_atum_order_model( $atum_order_id, TRUE );

		if ( is_wp_error( $order ) || ! $order instanceof AtumOrderModel ) {
			return;
		}

		$this->process_orders_inventories( $order, $new_status );

	}

	/**
	 * Add unassigned inventories to MI products items just in case of completed orders.
	 *
	 * @since 1.5.4
	 */
	public function add_orders_unassigned_inventories() {

		if ( ! check_admin_referer( 'woocommerce-mark-order-status' ) ) {
			return;
		}

		$order_id   = absint( $_GET['order_id'] );
		$new_status = AtumHelpers::get_raw_wc_order_status( sanitize_text_field( $_GET['status'] ) );
		$order      = wc_get_order( $order_id );

		if ( is_wp_error( $order ) || ! $order instanceof \WC_Order ) {
			return;
		}

		$this->process_orders_inventories( $order, $new_status );

	}

	public function process_orders_inventories( $order, $new_status ) {

		$is_atum_order         = $order instanceof AtumOrderModel ? TRUE : FALSE;
		$change_stock_statuses = array_merge( [ 'atum_received' ], Globals::get_order_statuses_change_stock() );
		$order_status          = $is_atum_order ? $order->get_status( 'edit' ) : AtumHelpers::get_raw_wc_order_status( $order->get_status() );

		if (
			! empty( $new_status ) && $new_status !== $order_status &&
			! in_array( $order_status, $change_stock_statuses ) &&
			in_array( $new_status, $change_stock_statuses )
		) {

			$this->add_order_item_inventories( $order );

		}

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
	 * @return Orders instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
