<?php
/**
 * Multi-Inventory Ajax callbacks
 *
 * @package         AtumMultiInventory
 * @subpackage      Inc
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.0.0
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Inc\Globals;
use Atum\InventoryLogs\InventoryLogs;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumMultiInventory\Models\Inventory;
use Atum\Inc\Helpers as AtumHelpers;

final class Ajax {

	/**
	 * The singleton instance holder
	 *
	 * @var Ajax
	 */
	private static $instance;

	/**
	 * Ajax singleton constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Get Countries list.
		add_action( 'wp_ajax_atum_mi_get_wo_countries_codes_list', array( $this, 'get_wc_countries_codes_list' ) );
		add_action( 'wp_ajax_nopriv_atum_mi_get_wo_countries_codes_list', array( $this, 'get_wc_countries_codes_list' ) );

		// Region Switcher tools.
		add_action( 'wp_ajax_atum_tool_mi_region_to_region', array( $this, 'tool_region_to_region' ) );
		add_action( 'wp_ajax_atum_tool_mi_countries_to_zones', array( $this, 'tool_countries_to_zones' ) );
		add_action( 'wp_ajax_atum_tool_mi_zones_to_countries', array( $this, 'tool_zones_to_countries' ) );

		// Inventory removal.
		add_action( 'wp_ajax_atum_mi_remove_inventory', array( $this, 'remove_inventory' ) );

		// Inventory Write Off.
		add_action( 'wp_ajax_atum_mi_write_off_inventory', array( $this, 'write_off_inventory' ) );
		
		// Change Inventories stock from orders.
		add_action( 'wp_ajax_atum_mi_change_stock', array( $this, 'change_order_stock' ) );
		
		// Hack WC ajax refund_line_items to prevent automatic stock changes.
		add_action( 'wp_ajax_woocommerce_refund_line_items', array( $this, 'maybe_change_refund_update_stock' ), 9 );

		// Update the user's address when using the User Destination widget.
		add_action( 'wp_ajax_atum_mi_save_user_location', array( $this, 'save_user_location' ) );
		
		// Change Inventory purchase price.
		add_action( 'wp_ajax_atum_mi_change_purchase_price', array( $this, 'change_atum_order_item_inventory_purchase_price' ) );

		// Catch add/edit/remove order items (SINCE WC 3.6).
		if ( version_compare( WC()->version, '3.6', '>=' ) ) {
			// Manage MI WC order items add/remove.
			remove_action( 'wp_ajax_woocommerce_add_order_item', array( 'WC_AJAX', 'add_order_item' ) );
			add_action( 'wp_ajax_woocommerce_add_order_item', array( $this, 'add_order_item' ) );
			remove_action( 'wp_ajax_woocommerce_remove_order_item', array( 'WC_AJAX', 'remove_order_item' ) );
			add_action( 'wp_ajax_woocommerce_remove_order_item', array( $this, 'remove_order_item' ) );
		}

		// Create a new inventory from the "Add Inventory" modal.
		add_action( 'wp_ajax_atum_mi_create_inventory', array( $this, 'create_inventory' ) );

		// Get/Set the inventory regions from the regions modal.
		add_action( 'wp_ajax_atum_get_inventory_regions', array( $this, 'get_inventory_regions' ) );
		add_action( 'wp_ajax_atum_set_inventory_regions', array( $this, 'set_inventory_regions' ) );
		
	}

	/**
	 * We need the complete country code list (249 countries) from WooCommerce to validate purposes
	 * Also, we only need the codes here.
	 *
	 * @see WC()->plugin_path() . '/i18n/countries.php'
	 * @see assets/js/atum.multinv.geoprompt.js . validateForm()
	 *
	 * @package FrontEnd
	 *
	 * @since 1.0.0
	 */
	public function get_wc_countries_codes_list() {

		check_ajax_referer( 'atum-mi-geoprompt', 'token' );

		$countries               = Helpers::get_regions( 'countries' );
		$wo_countries_codes_list = array_keys( $countries );

		wp_send_json_success( $wo_countries_codes_list );

	}

	/**
	 * Change from region to region (countries to countries or zones to zones)
	 *
	 * @package    Settings
	 * @subpackage Tools
	 *
	 * @param string $from_mode Optional. The original region mode that we are switching (Just for inner calls).
	 *
	 * @since 1.0.0
	 */
	public function tool_region_to_region( $from_mode = '' ) {

		check_ajax_referer( 'atum-script-runner-nonce', 'token' );

		if ( empty( $_POST['option'] ) ) {
			wp_send_json_error( __( 'Invalid data received', ATUM_MULTINV_TEXT_DOMAIN ) );
		}

		$options = json_decode( stripslashes( $_POST['option'] ), TRUE );

		if ( empty( $options ) ) {
			wp_send_json_error( __( 'Invalid data received', ATUM_MULTINV_TEXT_DOMAIN ) );
		}

		do_action( 'atum/ajax/tool_mi_migrate_' . ( str_replace( '-', '_', $from_mode ) ?: 'regions' ) );

		$from_mode = $from_mode ?: Helpers::get_region_restriction_mode();

		foreach ( $options as $option ) {

			if ( empty( $option['from'] ) || empty( $option['to'] ) ) {
				continue;
			}

			// Get all the inventories currently associated with the "from" countries.
			$region_inventories = Inventory::get_region_inventories( explode( '|', $option['from'] ), $from_mode );

			if ( ! empty( $region_inventories ) ) {
				foreach ( $region_inventories as $inventory ) {

					/**
					 * Variable definition
					 *
					 * @var Inventory $inventory
					 */
					$inventory->delete_regions( $from_mode );
					$inventory->set_data( array( 'region' => $option['to'] ) );
					$inventory->save();

				}
			}

		}

		wp_send_json_success( __( 'All your inventories were updated successfully', ATUM_MULTINV_TEXT_DOMAIN ) );

	}

	/**
	 * Change from countries to zones
	 *
	 * @package    Settings
	 * @subpackage Tools
	 *
	 * @since      1.0.0
	 */
	public function tool_countries_to_zones() {

		$this->tool_region_to_region( 'countries' );

	}

	/**
	 * Change from zones to countries
	 *
	 * @package    Settings
	 * @subpackage Tools
	 *
	 * @since      1.0.0
	 */
	public function tool_zones_to_countries() {

		$this->tool_region_to_region( 'shipping-zones' );

	}

	/**
	 * Remove an inventory from MI UI
	 *
	 * @package    Product Data
	 * @subpackage Multi-Inventory
	 *
	 * @since      1.0.0
	 */
	public function remove_inventory() {

		check_ajax_referer( 'atum-mi-ui-nonce', 'token' );

		if ( empty( $_POST['inventory_id'] ) ) {
			wp_send_json_error( __( 'Invalid inventory ID', ATUM_MULTINV_TEXT_DOMAIN ) );
		}

		// It could be a bulk remove with multiple IDs.
		$inventory_ids = array_filter( array_map( 'absint', explode( ',', $_POST['inventory_id'] ) ) );

		do_action( 'atum/ajax/mi_remove_inventory', $inventory_ids );

		foreach ( $inventory_ids as $inventory_id ) {

			$inventory = Helpers::get_inventory( $inventory_id );

			// Remove the inventory and all its related data.
			$removed = $inventory->delete();

			if ( ! $removed ) {
				wp_send_json_error( _n( 'Something failed when removing the inventory', 'Something failed when removing the inventories', count( $inventory_ids ), ATUM_MULTINV_TEXT_DOMAIN ) );
			}

		}

		wp_send_json_success( _n( 'Inventory removed successfully', 'Inventories removed successfully', count( $inventory_ids ), ATUM_MULTINV_TEXT_DOMAIN ) );

	}

	/**
	 * Write off an inventory from MI UI
	 *
	 * @package    Product Data
	 * @subpackage Multi-Inventory
	 *
	 * @since      1.0.0
	 */
	public function write_off_inventory() {

		check_ajax_referer( 'atum-mi-ui-nonce', 'token' );

		if ( empty( $_POST['inventory_id'] ) ) {
			wp_send_json_error( __( 'Invalid inventory ID', ATUM_MULTINV_TEXT_DOMAIN ) );
		}

		// It could be a bulk write-off with multiple IDs.
		$inventory_ids = array_filter( array_map( 'absint', explode( ',', $_POST['inventory_id'] ) ) );
		$write_off     = isset( $_POST['write_off'] ) && 1 === absint( $_POST['write_off'] ) ? 'yes' : 'no';

		do_action( 'atum/ajax/mi_write_off_inventory', $inventory_ids, $write_off );

		foreach ( $inventory_ids as $inventory_id ) {

			$inventory = Helpers::get_inventory( $inventory_id );

			// Remove the inventory and all its related data.
			$inventory->set_data( array( 'write_off' => $write_off ) );

			$saved = $inventory->save();

			if ( ! $saved ) {
				wp_send_json_error( _n( "Something failed when 'writing off' the inventory", "Something failed when 'writing off' the inventories", count( $inventory_ids ), ATUM_MULTINV_TEXT_DOMAIN ) );
			}

		}

		$message = 'yes' === $write_off ? _n( "The inventory was marked as 'Write Off'", "The inventories were marked as 'Write Off'", count( $inventory_ids ), ATUM_MULTINV_TEXT_DOMAIN )
			: _n( "The inventory was unmarked as 'Write Off'", "The inventories were unmarked as 'Write Off'", count( $inventory_ids ), ATUM_MULTINV_TEXT_DOMAIN );

		wp_send_json_success( $message );

	}
	
	/**
	 * Replace the order's WC decrease and increase stock behaviour
	 * This method is still used for IL, even if the WC version > 3.5.
	 *
	 * @package    Orders
	 * @subpackage Multi-Inventory
	 *
	 * @since 1.0.1
	 *
	 * @throws \Exception
	 */
	public function change_order_stock() {
		
		check_ajax_referer( 'order-item-inventories-nonce', 'token' );

		$order_id        = absint( $_POST['order_id'] );
		$order_post_type = get_post_type( $order_id );
		$order           = in_array( $order_post_type, Globals::get_order_types(), TRUE ) ? AtumHelpers::get_atum_order_model( $order_id, TRUE ) : wc_get_order( $order_id );
		
		if (
			( PurchaseOrders::POST_TYPE === $order_post_type && ! AtumCapabilities::current_user_can( 'edit_purchase_order' ) ) ||
			( InventoryLogs::POST_TYPE === $order_post_type && ! AtumCapabilities::current_user_can( 'edit_inventory_log' ) ) ||
			! current_user_can( 'edit_shop_orders' )
		) {
			wp_send_json_error( __( 'You are not allowed to change this order', ATUM_MULTINV_VERSION ) );
		}
		
		$order_item_ids     = isset( $_POST['order_item_ids'] ) ? $_POST['order_item_ids'] : array();
		$order_item_qty     = isset( $_POST['order_item_qty'] ) ? $_POST['order_item_qty'] : array();
		$order_item_inv_qty = isset( $_POST['order_item_inv_qty'] ) ? $_POST['order_item_inv_qty'] : array();
		$operation          = isset( $_POST['operation'] ) ? $_POST['operation'] : 'increase';
		$order_items        = $order->get_items();
		$return             = array();
		
		if ( $order && ! empty( $order_items ) && count( $order_item_ids ) > 0 ) {

			foreach ( $order_items as $item_id => $order_item ) {

				// Only reduce checked items.
				if ( ! in_array( $item_id, $order_item_ids ) ) {
					continue;
				}

				/**
				 * Variable definition. Don't need original translation because orders don't have languages.
				 *
				 * @var \WC_Product            $product
				 * @var \WC_Order_Item_Product $order_item
				 */
				$product = $order_item->get_product();
				
				if ( $product instanceof \WC_Product && $product->exists() && $product->managing_stock() ) {
					
					$product_id = $product->get_id();
					
					if ( 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product ) ) {
						
						// Get all the inventories including Main and written of ones.
						$inventories = Inventory::get_product_inventories( $product_id, '', FALSE, FALSE );
						
						if ( ! empty( $inventories ) ) {
							
							$item_name = $product->get_formatted_name();
							
							foreach ( $inventories as $inventory ) {

								$inventory_id = absint( $inventory->id );
								
								if ( isset( $order_item_inv_qty[ $item_id ][ $inventory_id ] ) && $order_item_inv_qty[ $item_id ][ $inventory_id ] > 0 ) {

									$qty           = $order_item_inv_qty[ $item_id ][ $inventory_id ];
									$stock_updated = Helpers::update_inventory_stock( $product, $inventory, $qty, $operation );
									$old_stock     = 'increase' === $operation ? $stock_updated - $qty : $stock_updated + $qty;

									if ( is_wp_error( $stock_updated ) || is_null( $stock_updated ) && $inventory->managing_stock() || FALSE === $stock_updated ) {
										/* translators: the first is the inventory name and second is the item name */
										$note_id = $order->add_order_note( sprintf( __( 'Unable to reduce inventory &quot;%1$s&quot; stock for item %2$s.', ATUM_MULTINV_TEXT_DOMAIN ), $inventory->name, $item_name ) );
										AtumHelpers::save_order_note_meta( $note_id, [
											'action'       => 'unable_reduce',
											'item_name'    => $order_item->get_name(),
											'product_id'   => $product->get_id(),
											'inventory_id' => $inventory_id,
											'old_stock'    => $old_stock,
											'new_stock'    => $new_stock,
										] );
										continue;
									}

									// WC doesn't add notes for unmanaged products.
									if ( $inventory->managing_stock() ) {
										$return[] = array(
											/* translators: first is the item name, second is the old stock quantity, third is the new stock quantity and fourth is the inventory name */
											'note'    => sprintf( wp_kses_post( __( '%1$s stock changed from %2$s to %3$s using inventory &quot;%4$s&quot;.', ATUM_MULTINV_TEXT_DOMAIN ) ), $item_name, $old_stock, $stock_updated, $inventory->name ),
											'success' => TRUE,
										);
									}
									
								}
								
							}
						}
					}
					elseif ( isset( $order_item_qty[ $item_id ] ) && $order_item_qty[ $item_id ] > 0 ) {

						$old_stock = $product->get_stock_quantity();
						
						// if stock is null but WC is managing stock.
						if ( is_null( $old_stock ) ) {
							$old_stock = 0;
							wc_update_product_stock( $product, $old_stock );
							
						}

						$stock_change = apply_filters( 'woocommerce_restore_order_stock_quantity', $order_item_qty[ $item_id ], $item_id );
						$new_quantity = wc_update_product_stock( $product, $stock_change, $operation );
						$item_name    = $product->get_formatted_name();
						$return[]     = array(
							/* translators: The stock's qty increased or decreased */
							'note'    => apply_filters( 'atum/atum_order/add_stock_change_note', sprintf( wp_kses_post( __( '%1$s stock changed from %2$s to %3$s.', ATUM_MULTINV_TEXT_DOMAIN ) ), $item_name, $old_stock, $new_quantity ), $product, $operation, $stock_change ),
							'success' => TRUE,
						);

					}
					
				}
			}

			if ( $order instanceof \WC_Order ) {
				do_action( 'woocommerce_restore_order_stock', $order );
			}
			else {
				do_action( "atum/ajax/{$operation}_atum_order_stock", $order );
			}


			if ( empty( $return ) ) {
				$return[] = array(
					/* translators: No product stock's qty changed */
					'note'    => wp_kses_post( __( 'No products had their stock changed - they may not have stock management enabled.', ATUM_MULTINV_TEXT_DOMAIN ) ),
					'success' => false,
				);
			}
			
			wp_send_json_success( $return );
		}

		wp_send_json_error();
		
	}
	
	/**
	 * Change the parameter restock_refunded_items to prevent WC to update automatically the stock
	 *
	 * @package    Orders
	 * @subpackage Multi-Inventory
	 *
	 * @since 1.0.1
	 */
	public function maybe_change_refund_update_stock() {
		
		check_ajax_referer( 'order-item', 'security' );
		
		global $order_refund_restock;
		
		$order_id                        = absint( $_POST['order_id'] );
		$restock_refunded_items          = 'true' === $_POST['restock_refunded_items'];
		$_POST['restock_refunded_items'] = FALSE;
		
		$order_refund_restock[ $order_id ] = $restock_refunded_items;
		
	}

	/**
	 * Save the user's location when using the "User Destination" widget
	 *
	 * @package    User Destination Widget
	 * @subpackage Multi-Inventory
	 *
	 * @since 1.0.1
	 */
	public function save_user_location() {

		check_ajax_referer( 'atum-geo-prompt', 'token' );

		if ( empty( $_POST['data'] ) ) {
			wp_send_json_error( __( 'Invalid Data', ATUM_MULTINV_TEXT_DOMAIN ) );
		}

		$load_address = 'shipping';
		$form_data    = json_decode( stripslashes( $_POST['data'] ), TRUE );
		$user_id      = get_current_user_id();

		$customer_location = array();

		if ( ! empty( $form_data['postcode'] ) ) {
			$customer_location['postcode'] = esc_attr( $form_data['postcode'] );
		}

		if ( ! empty( $form_data['region'] ) ) {
			$customer_location = array_merge( $customer_location, Helpers::explode_formatted_region( esc_attr( $form_data['region'] ) ) );
		}

		if ( empty( $customer_location ) ) {
			wp_send_json_error( __( 'Invalid Data', ATUM_MULTINV_TEXT_DOMAIN ) );
		}

		$address = WC()->countries->get_address_fields( isset( $customer_location['country'] ) ? $customer_location['country'] : '', "{$load_address}_" );
		/* @noinspection PhpUnhandledExceptionInspection */
		$customer = new \WC_Customer( $user_id );

		if ( $customer ) {

			foreach ( $address as $key => $field ) {

				$submitted_key = str_replace( "{$load_address}_", '', $key );

				if ( ! isset( $customer_location[ $submitted_key ] ) ) {
					continue;
				}

				if ( is_callable( array( $customer, "set_$key" ) ) ) {
					call_user_func( array( $customer, "set_$key" ), $customer_location[ $submitted_key ] );
				}
				else {
					$customer->update_meta_data( $key, $customer_location[ $submitted_key ] );
				}

				if ( WC()->customer && is_callable( array( WC()->customer, "set_$key" ) ) ) {
					call_user_func( array( WC()->customer, "set_$key" ), $customer_location[ $submitted_key ] );
				}

			}

			$customer->save();

		}

		wc_add_notice( __( 'Address changed successfully.', ATUM_MULTINV_TEXT_DOMAIN ) );

		do_action( 'woocommerce_customer_save_address', $user_id, $load_address );

		wp_send_json_success( __( 'Address changed successfully.', ATUM_MULTINV_TEXT_DOMAIN ) );

	}
	
	/**
	 * Change the purchase price of a product within a PO
	 *
	 * @package    Purchase Orders
	 * @subpackage Multi-Inventory
	 *
	 * @since 1.1.1.1
	 */
	public function change_atum_order_item_inventory_purchase_price() {
		
		check_ajax_referer( 'order-item-inventories-nonce', 'token' );
		
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to do this', ATUM_MULTINV_TEXT_DOMAIN ) );
		}
		
		if ( empty( $_POST['inventory_id'] ) || empty( $_POST[ Globals::PURCHASE_PRICE_KEY ] ) ) {
			wp_send_json_error( __( 'Invalid data provided', ATUM_MULTINV_TEXT_DOMAIN ) );
		}
		
		$inventory_id = (int) $_POST['inventory_id'];
		
		$inventory = Helpers::get_inventory( $inventory_id );
		
		if ( $inventory->is_main() ) {
			$inventory = Helpers::get_inventory( $inventory_id, 0, TRUE );
		}
		
		$inventory->set_meta( array( Globals::PURCHASE_PRICE_KEY => (float) $_POST[ Globals::PURCHASE_PRICE_KEY ] ) );
		$inventory->save_meta();
		
		wp_send_json_success();
	}

	/**
	 * Manage add order items adding support for MI products, For now only for WC Orders
	 * Replace WC Ajax add_order_item function
	 *
	 * @since 1.2.0
	 *
	 * @throws \Exception If order is invalid.
	 */
	public function add_order_item() {

		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( -1 );
		}

		$response = array();

		try {

			if ( ! isset( $_POST['order_id'] ) ) {
				throw new \Exception( __( 'Invalid order', ATUM_MULTINV_TEXT_DOMAIN ) );
			}

			$order_id = absint( wp_unslash( $_POST['order_id'] ) ); // WPCS: input var ok.
			$order    = wc_get_order( $order_id );

			if ( ! $order ) {
				throw new \Exception( __( 'Invalid order', ATUM_MULTINV_TEXT_DOMAIN ) );
			}

			// If we passed through items it means we need to save first before adding a new one.
			$items = ! empty( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '';

			if ( ! empty( $items ) ) {
				$save_items = array();
				parse_str( $items, $save_items );
				wc_save_order_items( $order->get_id(), $save_items );
			}

			$items_to_add = isset( $_POST['data'] ) ? array_filter( wp_unslash( (array) $_POST['data'] ) ) : array();

			// Add items to order.
			$order_notes = $added_items = array();

			foreach ( $items_to_add as $item ) {

				if ( ! isset( $item['id'], $item['qty'] ) || empty( $item['id'] ) ) {
					continue;
				}

				$product_id = absint( $item['id'] );
				$qty        = wc_stock_amount( $item['qty'] );
				$product    = wc_get_product( $product_id );

				if ( ! $product instanceof \WC_Product ) {
					throw new \Exception( __( 'Invalid product ID', ATUM_MULTINV_TEXT_DOMAIN ) . ' ' . $product_id );
				}

				$validation_error = new \WP_Error();
				$validation_error = apply_filters( 'woocommerce_ajax_add_order_item_validation', $validation_error, $product, $order, $qty );

				if ( $validation_error->get_error_code() ) {
					throw new \Exception( '<strong>' . __( 'Error:', ATUM_MULTINV_TEXT_DOMAIN ) . '</strong> ' . $validation_error->get_error_message() );
				}

				$item_id                 = $order->add_product( $product, $qty );
				$item                    = apply_filters( 'woocommerce_ajax_order_item', $order->get_item( $item_id ), $item_id, $order, $product );
				$added_items[ $item_id ] = $item;
				$order_notes[ $item_id ] = $product->get_formatted_name();

				do_action( 'woocommerce_ajax_add_order_item_meta', $item_id, $item, $order );

			}

			/* translators: %s item name. */
			$note_id = $order->add_order_note( sprintf( __( 'Added line items: %s', ATUM_MULTINV_TEXT_DOMAIN ), implode( ', ', $order_notes ) ), FALSE, TRUE );
			AtumHelpers::save_order_note_meta( $note_id, [
				'action'   => 'added_line_items',
				'items'    => $items_to_add,
			] );

			do_action( 'woocommerce_ajax_order_items_added', $added_items, $order );

			$data = get_post_meta( $order_id );

			// Get HTML to return.
			ob_start();
			include WC()->plugin_path() . '/includes/admin/meta-boxes/views/html-order-items.php';
			$items_html = ob_get_clean();

			ob_start();
			$notes = wc_get_order_notes( array( 'order_id' => $order_id ) );
			include WC()->plugin_path() . '/includes/admin/meta-boxes/views/html-order-notes.php';
			$notes_html = ob_get_clean();

			wp_send_json_success(
				array(
					'html'       => $items_html,
					'notes_html' => $notes_html,
				)
			);

		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

		// wp_send_json_success must be outside the try block not to break phpunit tests.
		wp_send_json_success( $response );

	}

	/**
	 * Manage remove order items adding support for MI products, For now only for WC Orders
	 * Replace WC Ajax remove order_item function
	 *
	 * @since 1.2.0
	 *
	 * @throws \Exception If order is invalid.
	 */
	public function remove_order_item() {

		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'], $_POST['order_item_ids'] ) ) {
			wp_die( -1 );
		}

		$response = array();

		try {
			$order_id = absint( $_POST['order_id'] );
			$order    = wc_get_order( $order_id );

			if ( ! $order ) {
				throw new \Exception( __( 'Invalid order', ATUM_MULTINV_TEXT_DOMAIN ) );
			}

			if ( ! isset( $_POST['order_item_ids'] ) ) {
				throw new \Exception( __( 'Invalid items', ATUM_MULTINV_TEXT_DOMAIN ) );
			}

			$order_item_ids     = wp_unslash( $_POST['order_item_ids'] );
			$items              = ! empty( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '';
			$calculate_tax_args = array(
				'country'  => isset( $_POST['country'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['country'] ) ) ) : '',
				'state'    => isset( $_POST['state'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['state'] ) ) ) : '',
				'postcode' => isset( $_POST['postcode'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['postcode'] ) ) ) : '',
				'city'     => isset( $_POST['city'] ) ? wc_strtoupper( wc_clean( wp_unslash( $_POST['city'] ) ) ) : '',
			);

			if ( ! is_array( $order_item_ids ) && is_numeric( $order_item_ids ) ) {
				$order_item_ids = array( $order_item_ids );
			}

			// If we passed through items it means we need to save first before deleting.
			if ( ! empty( $items ) ) {
				$save_items = array();
				parse_str( $items, $save_items );
				wc_save_order_items( $order->get_id(), $save_items );
			}

			if ( ! empty( $order_item_ids ) ) {

				foreach ( $order_item_ids as $item_id ) {

					$item_id = absint( $item_id );
					$item    = $order->get_item( $item_id );

					// Before deleting the item, adjust any stock values already reduced.
					if ( $item->is_type( 'line_item' ) ) {

						/**
						 * Variable definition
						 *
						 * @var \WC_Order_Item_Product $item
						 */
						$changed_stock = FALSE;
						$product       = $item->get_product();

						// TODO: Implement increase MI inventories stock
						// MI: Do not increase the inventory if is a MI product.
						if ( ! ( 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product ) ) ) {
							$changed_stock = wc_maybe_adjust_line_item_product_stock( $item, 0 );
						}

						if ( $changed_stock && ! is_wp_error( $changed_stock ) ) {
							/* translators: %1$s: item name %2$s: stock change */
							$note_id = $order->add_order_note( sprintf( __( 'Deleted %1$s and adjusted stock (%2$s)', ATUM_MULTINV_TEXT_DOMAIN ), $item->get_name(), $changed_stock['from'] . '&rarr;' . $changed_stock['to'] ), false, true );
							AtumHelpers::save_order_note_meta( $note_id, [
								'action'     => 'deleted_line',
								'item_name'  => $item->get_name(),
								'product_id' => $product->get_id(),
								'old_stock'  => $changed_stock['from'],
								'new_stock'  => $changed_stock['to'],
							] );
						}
						else {
							/* translators: %s item name. */
							$note_id = $order->add_order_note( sprintf( __( 'Deleted %s', ATUM_MULTINV_TEXT_DOMAIN ), $item->get_name() ), FALSE, TRUE );
							AtumHelpers::save_order_note_meta( $note_id, [
								'action'     => 'deleted_line',
								'item_name'  => $order_item->get_name(),
								'product_id' => $product->get_id(),
							] );
						}
					}

					wc_delete_order_item( $item_id );

				}

			}

			$order = wc_get_order( $order_id );
			$order->calculate_taxes( $calculate_tax_args );
			$order->calculate_totals( false );

			// Get HTML to return.
			ob_start();
			include WC()->plugin_path() . '/includes/admin/meta-boxes/views/html-order-items.php';
			$items_html = ob_get_clean();

			ob_start();
			$notes = wc_get_order_notes( array( 'order_id' => $order_id ) );
			include WC()->plugin_path() . '/includes/admin/meta-boxes/views/html-order-notes.php';
			$notes_html = ob_get_clean();

			wp_send_json_success(
				array(
					'html'       => $items_html,
					'notes_html' => $notes_html,
				)
			);

		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

		// wp_send_json_success must be outside the try block not to break phpunit tests.
		wp_send_json_success( $response );

	}

	/**
	 * Create a new inventory from the "Add Inventory" modal.
	 *
	 * @since 1.4.7
	 */
	public function create_inventory() {

		check_ajax_referer( 'mi-list-tables-nonce', 'security' );

		if ( empty( $_POST['inventory_data'] ) || empty( $_POST['product_id'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_MULTINV_TEXT_DOMAIN ) );
		}

		parse_str( $_POST['inventory_data'], $posted_data );
		$posted_data = array_map( 'sanitize_text_field', $posted_data );

		if ( empty( $posted_data['name'] ) ) {
			wp_send_json_error( __( 'The inventory name is required', ATUM_MULTINV_TEXT_DOMAIN ) );
		}

		$inventory = new Inventory();

		$inventory_data = array(
			'product_id' => absint( $_POST['product_id'] ),
			'name'       => $posted_data['name'],
			'is_main'    => FALSE,
		);

		if ( ! empty( $posted_data['bbe_date'] ) ) {
			$inventory_data['bbe_date'] = $posted_data['bbe_date'];
		}

		if ( ! empty( $posted_data['lot'] ) ) {
			$inventory_data['lot'] = $posted_data['lot'];
		}

		// Add the regions (if any).
		if ( ! empty( $posted_data['region'] ) ) {

			$region_restriction_mode = Helpers::get_region_restriction_mode();

			if ( ! is_array( $posted_data['region'] ) ) {
				$posted_data['region'] = (array) $posted_data['region'];
			}

			// We must receive the shipping zone names.
			if ( 'shipping-zones' === $region_restriction_mode ) {
				$wc_shipping_zones        = Helpers::get_regions( 'shipping-zones' );
				$shipping_zones           = array_intersect( array_keys( $wc_shipping_zones ), array_map( 'absint', $posted_data['region'] ) );
				$inventory_data['region'] = $shipping_zones;
			}
			// We must receive the country codes.
			elseif ( 'countries' === $region_restriction_mode ) {
				$wc_countries             = Helpers::get_regions( 'countries' );
				$inventory_data['region'] = array_intersect( $posted_data['region'], array_keys( $wc_countries ) );
			}

		}

		// Add the locations (if any).
		if ( ! empty( $posted_data['location'] ) ) {

			if ( ! is_array( $posted_data['location'] ) ) {
				$posted_data['location'] = (array) $posted_data['location'];
			}

			$inventory_data['location'] = [];

			// We allow term IDs or term slugs here.
			foreach ( $posted_data['location'] as $location ) {

				// Term ID.
				if ( is_numeric( $location ) ) {
					$inventory_data['location'] = absint( $location );
				}
				// Term slug.
				else {

					$location_slug = esc_attr( $location );
					$location_term = get_term_by( 'slug', $location_slug, Globals::PRODUCT_LOCATION_TAXONOMY );

					if ( $location_term ) {
						$inventory_data['location'] = $location_term->term_id;
					}

				}

			}

		}

		$inventory->set_data( $inventory_data );
		$inventory->save();

		$inventory_meta = array(
			'sku'            => ! empty( $posted_data['sku'] ) ? $posted_data['sku'] : '',
			'manage_stock'   => ! empty( $posted_data['manage_stock'] ) ? $posted_data['manage_stock'] : 'yes',
			'stock_quantity' => ! empty( $posted_data['stock_quantity'] ) ? $posted_data['stock_quantity'] : NULL,
			'backorders'     => ! empty( $posted_data['backorders'] ) ? $posted_data['backorders'] : 'no',
			'supplier_id'    => ! empty( $posted_data['supplier'] ) ? $posted_data['supplier'] : NULL,
			'supplier_sku'   => ! empty( $posted_data['supplier_sku'] ) ? $posted_data['supplier_sku'] : '',
		);

		$inventory->set_meta( $inventory_meta );
		$inventory->save_meta();

		wp_send_json_success( __( 'The inventory was created successfully', ATUM_MULTINV_TEXT_DOMAIN ) );

	}

	/**
	 * Get the the inventory regions from the Regions Modal component
	 *
	 * @since 1.4.9
	 */
	public function get_inventory_regions() {

		check_ajax_referer( 'mi-list-tables-nonce', 'token' );

		if ( empty( $_POST['id'] ) ) {
			wp_send_json_error( __( 'No valid ID provided', idATUM_MULTINV_TEXT_DOMAIN ) );
		}

		$ids = explode( ':', $_POST['id'] );

		// Make sure the inventory ID is there.
		if ( count( $ids ) !== 2 ) {
			return;
		}

		$region_restriction = Helpers::get_region_restriction_mode();
		$all_regions        = Helpers::get_regions( $region_restriction );
		$inventory_id       = absint( $ids[1] );
		$inventory          = Helpers::get_inventory( $inventory_id );
		$inventory_regions  = $inventory->region;

		ob_start();

		if ( 'shipping-zones' === $region_restriction ) : ?>

			<select class="edit-inventory-regions" multiple style="width: 100%">
				<option value="-1"><?php esc_attr_e( 'Select Shipping Zone(s)', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
				<?php foreach ( $all_regions as $zone ) : ?>
					<option value="<?php echo esc_attr( $zone['id'] ) ?>"<?php selected( is_array( $inventory_regions ) && in_array( $zone['id'], $inventory_regions ), TRUE ) ?>><?php echo esc_attr( $zone['zone_name'] ) ?></option>
				<?php endforeach ?>
			</select>

		<?php elseif ( 'countries' === $region_restriction ) : ?>

			<select class="edit-inventory-regions" multiple style="width: 100%">
				<option value="-1"><?php esc_attr_e( 'Select Country(ies)', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
				<?php foreach ( $all_regions as $country_code => $country_name ) : ?>
					<option value="<?php echo esc_attr( $country_code ) ?>"<?php selected( is_array( $inventory_regions ) && in_array( $country_code, $inventory_regions ), TRUE ) ?>><?php echo esc_attr( $country_name ) ?></option>
				<?php endforeach ?>
			</select>

		<?php endif;

		wp_send_json_success( ob_get_clean() );

	}

	/**
	 * Set the the inventory regions from the Regions Modal component
	 *
	 * @since 1.4.9
	 */
	public function set_inventory_regions() {

		check_ajax_referer( 'mi-list-tables-nonce', 'token' );

		if ( empty( $_POST['id'] ) ) {
			wp_send_json_error( __( 'No valid ID provided', ATUM_MULTINV_TEXT_DOMAIN ) );
		}

		$regions = empty( $_POST['regions'] ) ? [] : array_map( 'esc_attr', $_POST['regions'] );
		$ids     = explode( ':', $_POST['id'] );

		// Only needed when the requested locations tree is for an inventory (the product ID comes as product_id:inventory_id).
		if ( count( $ids ) !== 2 ) {
			return;
		}

		$inventory_id = absint( $ids[1] );
		$inventory    = Helpers::get_inventory( $inventory_id );
		$inventory->set_data( [ 'region' => $regions ] );
		$inventory->save();

		wp_send_json_success();

	}

	
	/******************
	 * Instace methods
	 ******************/

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
	 * @return Ajax instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
