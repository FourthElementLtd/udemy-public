<?php
/**
 * Extender for the WC's orders, Purchase Orders and Inventory Logs endpoints
 * Adds the order item's inventories to this endpoint
 *
 * @since       1.2.4
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     AtumMultiInventory\Api
 * @subpackage  Extenders
 */

namespace AtumMultiInventory\Api\Extenders;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Inc\Hooks as AtumHooks;
use AtumMultiInventory\Inc\Helpers;
use AtumMultiInventory\Inc\Orders;
use AtumMultiInventory\Models\Inventory;


class InventoryOrders {

	/**
	 * The singleton instance holder
	 *
	 * @var InventoryOrders
	 */
	private static $instance;

	/**
	 * InventoryOrders constructor
	 *
	 * @since 1.2.4
	 */
	private function __construct() {

		/**
		 * Register the ATUM Multi-Inventory custom fields to the WC API.
		 */
		add_action( 'rest_api_init', array( $this, 'register_order_fields' ), 0 );

	}

	/**
	 * Register the ATUM Multi-Inventory API custom fields for order requests.
	 *
	 * @since 1.2.4
	 */
	public function register_order_fields() {

		// Add temporary hooks for order processing.
		add_filter( 'rest_dispatch_request', array( $this, 'add_hooks' ), 10, 4 );

		$order_types = Globals::get_order_type_table_id( '' );

		foreach ( array_keys( $order_types ) as $order_type ) {

			// Schema.
			add_filter( "woocommerce_rest_{$order_type}_schema", array( $this, 'filter_order_schema' ) );

			// Add extra data to line items.
			add_filter( "woocommerce_rest_prepare_{$order_type}_object", array( $this, 'filter_order_response' ), 10, 3 );

			// Delete order item inventories.
			add_action( "woocommerce_rest_delete_{$order_type}_object", array( $this, 'delete_order_item_inventories' ), 10, 3 );

		}

		// Add/Modify order item inventories.
		add_action( 'woocommerce_rest_insert_shop_order_object', array( $this, 'save_order_item_inventories' ), 10, 3 );
		add_action( 'atum/api/before_save_atum_order', array( $this, 'save_order_item_inventories' ), 10, 3 );

		// Add MI configuration data as meta for later post-processing.
		add_action( 'woocommerce_rest_set_order_item', array( $this, 'check_order_item_inventories' ), 10, 2 );

	}

	/**
	 * Gets extended (unprefixed) schema properties for order item inventories.
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	private function get_extended_schema() {

		return array(
			'mi_inventories' => array(
				'description' => __( 'An array containing all the order item inventories linked to this order item', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'            => array(
							'required'    => TRUE,
							'description' => __( 'The order item inventory ID.', ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'delete'        => array(
							'description' => __( 'Set to true to delete the order item inventory with the specified inventory ID.', ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'boolean',
							'default'     => FALSE,
							'context'     => array( 'edit' ),
						),
						'order_item_id' => array(
							'required'    => TRUE,
							'description' => __( 'The order item ID linked to this order item inventory.', ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'integer',
							'context'     => array( 'edit' ),
						),
						'inventory_id'  => array(
							'required'    => TRUE,
							'description' => __( 'The inventory ID linked to the order item.', ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'product_id'    => array(
							'required'    => TRUE,
							'description' => __( 'The product ID from where the inventory comes.', ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'integer',
							'context'     => array( 'edit' ),
						),
						'qty'           => array(
							'required'    => FALSE,
							'description' => __( 'The quantity of the specified inventory that is used on the order item.', ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'number',
							'default'     => 0,
							'context'     => array( 'view', 'edit' ),
						),
						'order_type'    => array(
							'required'    => FALSE,
							'description' => __( 'The type of order (WC Order = 1, Purchase Order = 2, Inventory Log = 3).', ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'integer',
							'enum'        => array_keys( Globals::get_order_type_table_id( '' ) ),
							'context'     => array( 'edit' ),
						),
						'subtotal'      => array(
							'required'    => FALSE,
							'description' => __( "Order item inventory's subtotal.", ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'number',
							'context'     => array( 'view', 'edit' ),
						),
						'total'         => array(
							'required'    => FALSE,
							'description' => __( "Order item inventory's total.", ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'number',
							'context'     => array( 'view', 'edit' ),
						),
						'refund_qty'    => array(
							'required'    => FALSE,
							'description' => __( "Order item inventory's refund quantity.", ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'number',
							'context'     => array( 'view', 'edit' ),
						),
						'refund_total'  => array(
							'required'    => FALSE,
							'description' => __( "Order item inventory's refund total.", ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'number',
							'context'     => array( 'view', 'edit' ),
						),
						'reduced_stock' => array(
							'required'    => FALSE,
							'description' => __( "Order item inventory's already reduced stock.", ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'number',
							'context'     => array( 'view', 'edit' ),
						),
						'extra_data'    => array(
							'required'    => FALSE,
							'description' => __( "Order item inventory's extra data.", ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
			),
		);

	}

	/**
	 * Add temporary hooks when REST saving orders
	 *
	 * @since 1.8.0
	 *
	 * @param mixed            $dispatch_result Dispatch result, will be used if not empty.
	 * @param \WP_REST_Request $request         Request used to generate the response.
	 * @param string           $route           Route matched for the request.
	 * @param array            $handler         Route handler used for the request.
	 *
	 * @return mixed
	 */
	public function add_hooks( $dispatch_result, $request, $route, $handler ) {

		if (
			! empty( $handler['callback'] ) && is_array( $handler['callback'] ) &&
			$handler['callback'][0] instanceof \WC_REST_Orders_Controller && 'create_item' === $handler['callback'][1]
		) {

			add_filter( 'woocommerce_order_item_quantity', array( $this, 'maybe_change_order_item_qty' ), 10, 3 );
			add_filter( 'woocommerce_new_order_note_data', array( $this, 'maybe_remove_order_note' ) );
		}

		return $dispatch_result;
	}

	/**
	 * Adds order item inventories schema's properties to line items
	 *
	 * @since 1.2.4
	 *
	 * @param array $schema
	 *
	 * @return array
	 */
	public function filter_order_schema( $schema ) {

		foreach ( $this->get_extended_schema() as $field_name => $field_content ) {
			$schema['line_items']['properties'][ $field_name ] = $field_content;
		}

		return $schema;

	}

	/**
	 * Filters WC REST API order responses to add order item inventories' data.
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Response                $response
	 * @param \WP_Post|\WC_Data|AtumOrderModel $object
	 * @param \WP_REST_Request                 $request
	 *
	 * @return \WP_REST_Response
	 */
	public function filter_order_response( $response, $object, $request ) {

		if ( $response instanceof \WP_HTTP_Response ) {

			if ( $object instanceof \WP_Post ) {
				$object = wc_get_order( $object );
			}

			$order_data = $response->get_data();
			$order_data = $this->get_extended_order_data( $order_data, $object );

			$response->set_data( $order_data );

		}

		return $response;

	}

	/**
	 * Append order item inventories' data to order data
	 *
	 * @since 1.2.4
	 *
	 * @param array                    $order_data
	 * @param \WC_Order|AtumOrderModel $order
	 *
	 * @return array
	 */
	private function get_extended_order_data( $order_data, $order ) {

		if ( ! empty( $order_data['line_items'] ) ) {

			$schema   = $this->get_extended_schema();
			$oi_props = array_keys( $schema['mi_inventories']['items']['properties'] );

			foreach ( $order_data['line_items'] as $order_data_item_index => $order_data_item ) {

				$order_data_item_id     = $order_data_item['id'];
				$order_post_type        = $order instanceof \WC_Order ? $order->get_type() : $order->get_post_type();
				$order_item_inventories = Inventory::get_order_item_inventories( $order_data_item_id, Globals::get_order_type_table_id( $order_post_type ) );
				$filtered_inventories   = array();

				foreach ( $order_item_inventories as $index => $inventory_data ) {

					$inventory_data = $this->filter_response_by_context( $inventory_data, 'view' );

					foreach ( $inventory_data as $key => $value ) {

						if ( in_array( $key, $oi_props, TRUE ) ) {

							switch ( $schema['mi_inventories']['items']['properties'][ $key ]['type'] ) {
								case 'integer':
									$filtered_inventories[ $index ][ $key ] = (int) $value;
									break;

								case 'number':
									$filtered_inventories[ $index ][ $key ] = (float) $value;
									break;

								default:
									$filtered_inventories[ $index ][ $key ] = $value;
									break;
							}

						}

					}

				}

				$order_data['line_items'][ $order_data_item_index ]['mi_inventories'] = $filtered_inventories;

			}

		}

		return $order_data;

	}

	/**
	 * Filters a response based on the context defined in the schema.
	 *
	 * @since 1.2.4
	 *
	 * @param array  $data    Response data to fiter.
	 * @param string $context Context defined in the schema.
	 *
	 * @return array Filtered response.
	 */
	protected function filter_response_by_context( $data, $context ) {

		$schema = $this->get_extended_schema();
		$schema = $schema['mi_inventories']['items'];

		foreach ( $data as $key => $value ) {

			if ( empty( $schema['properties'][ $key ] ) || empty( $schema['properties'][ $key ]['context'] ) ) {
				continue;
			}

			if ( ! in_array( $context, $schema['properties'][ $key ]['context'], TRUE ) ) {
				unset( $data->$key );
				continue;
			}

			if ( 'object' === $schema['properties'][ $key ]['type'] && ! empty( $schema['properties'][ $key ]['properties'] ) ) {

				foreach ( $schema['properties'][ $key ]['properties'] as $attribute => $details ) {

					if ( empty( $details['context'] ) ) {
						continue;
					}

					if ( ! in_array( $context, $details['context'], TRUE ) && isset( $data->$key->$attribute ) ) {
						unset( $data->$key->$attribute );
					}

				}

			}

		}

		return $data;

	}

	/**
	 * Check whether the order item inventories data is correct before processing
	 *
	 * @since 1.2.4
	 *
	 * @param \WC_Order_Item $item
	 * @param array          $posted_item_data
	 *
	 * @throws \WC_REST_Exception
	 */
	public function check_order_item_inventories( $item, $posted_item_data ) {

		// If the order items inventories data is not coming, there is no reason to continue.
		if ( ! isset( $posted_item_data['mi_inventories'] ) ) {
			return;
		}

		// Only valid for line items.
		if ( ! $item instanceof \WC_Order_Item_Product ) {
			return;
		}

		foreach ( $posted_item_data['mi_inventories'] as $item_data ) {

			// The inventory ID is required for every order item inventory.
			if ( empty( $item_data['inventory_id'] ) ) {
				throw new \WC_REST_Exception( 'atum_mi_rest_missing_inventory_id', __( 'Missing inventory ID.', ATUM_MULTINV_TEXT_DOMAIN ), 400 );
			}

			$inventory = Helpers::get_inventory( absint( $item_data['inventory_id'] ) );

			// Check that the coming inventory ID really exists for the specified product.
			if ( absint( $posted_item_data['product_id'] ) !== $inventory->product_id ) {
				/* translators: first one is the inventory ID and second one is the product ID */
				throw new \WC_REST_Exception( 'atum_mi_rest_invalid_inventory', sprintf( __( 'No inventory was found with ID %1$d linked to the product with ID %2$d.', ATUM_MULTINV_TEXT_DOMAIN ), $item_data['inventory_id'], $posted_item_data['product_id'] ), 404 );
			}

		}

	}

	/**
	 * Modify order contents to include order item inventories
	 *
	 * @since 1.2.4
	 *
	 * @param AtumOrderModel|\WC_Order $order
	 * @param \WP_REST_Request         $request
	 * @param bool                     $creating
	 */
	public static function save_order_item_inventories( $order, $request, $creating ) {

		$request_params = $request->get_params();
		$order_items    = $order->get_items();
		$orders         = Orders::get_instance();
		$order_type_id  = Globals::get_order_type_table_id( $order instanceof AtumOrderModel ? $order->get_post_type() : $order->get_type() );
		$items_data     = array(
			'order_item_qty'    => [],
			'line_total'        => [],
			'line_subtotal'     => [],
			'line_tax'          => [],
			'line_subtotal_tax' => [],
		);

		// Prepare the data to be compatible with the Orders::update_mi_order_lines method.
		if ( ! empty( $request_params['line_items'] ) ) {

			foreach ( $request_params['line_items'] as $line_item ) {

				// NOTE: for some reason, we were receiving a float number for the product ID from WC, so we must cast the value to avoid issues.
				$product_id   = absint( ! empty( $line_item['variation_id'] ) ? $line_item['variation_id'] : $line_item['product_id'] );
				$product      = wc_get_product( $product_id );
				$current_item = FALSE;

				if ( ! $product instanceof \WC_Product ) {
					continue;
				}

				// If the lin'es product is not MI there is no reason to continue.
				if ( 'no' === Helpers::get_product_multi_inventory_status( $product ) || ! Helpers::is_product_multi_inventory_compatible( $product ) ) {
					continue;
				}

				if ( ! empty( $line_item['id'] ) ) {
					$order_item_id = $line_item['id'];
					$current_item  = $order->get_item( $order_item_id );
				}
				else {

					// Try to get the right order item ID from the order.
					foreach ( $order_items as $order_item ) {

						/**
						 * Variable definition
						 *
						 * @var \WC_Order_Item_Product $order_item
						 */

						// Check for variation and for product.
						if ( $product_id === $order_item->get_variation_id() || $product_id === $order_item->get_product_id() ) {
							$order_item_id = $order_item->get_id();
							$current_item  = $order_item;
							break;
						}

					}

				}

				if ( empty( $order_item_id ) || ! $current_item ) {
					continue;
				}

				$has_multiprice               = Helpers::has_multi_price( $product );
				$item_id_key                  = $order instanceof AtumOrderModel ? 'atum_order_item_id' : 'order_item_id';
				$items_data[ $item_id_key ][] = $order_item_id;

				if ( ! empty( $line_item['mi_inventories'] ) ) {

					$items_data['order_item_qty'][ $order_item_id ] = 0;
					$items_data['line_total'][ $order_item_id ]     = 0;
					$items_data['line_subtotal'][ $order_item_id ]  = 0;

					$saved_order_item_inventories = Inventory::get_order_item_inventories( $order_item_id, $order_type_id );

					foreach ( $saved_order_item_inventories as $saved_order_item_inventory ) {

						$inventory_id = (int) $saved_order_item_inventory->inventory_id;

						$matching_item_inventory = array_filter( $line_item['mi_inventories'], function ( $order_inventory ) use ( $inventory_id ) {
							return ! empty( $order_inventory['inventory_id'] && (int) $inventory_id === (int) $order_inventory['inventory_id'] );
						} );

						// The item inventory wasn't included in the API call, so it remains as it is.
						if ( empty( $matching_item_inventory ) ) {
							$items_data = self::add_item_data( $items_data, (array) $saved_order_item_inventory, $product, $has_multiprice );
						}
						else {
							$matching_inventory_order = reset( $matching_item_inventory );

							$matching_inventory_order['order_item_id'] = $order_item_id;
							$items_data                                = self::add_item_data( $items_data, $matching_inventory_order, $product, $has_multiprice );

							// Prevent processing twice an item inventory.
							unset( $line_item['mi_inventories'][ key( $matching_item_inventory ) ] );
						}

					}

					foreach ( $line_item['mi_inventories'] as $order_item_inventory ) {
						$order_item_inventory['order_item_id'] = $order_item_id;

						$items_data = self::add_item_data( $items_data, $order_item_inventory, $product, $has_multiprice );
					}

					// Manage MI WC order items changes.
					add_action( 'atum/multi_inventory/after_update_mi_order_lines', array( $orders, 'manage_mi_order_items_changes' ), 10, 3 );

					$order_id = $order instanceof AtumOrderModel ? $order : $order->get_id();
					$orders->calculate_update_mi_order_lines( $order_id, $items_data );
					$orders->update_mi_order_lines( $order_id, $items_data );

					// Recalculate the order item totals.
					Orders::recalculate_order_item_totals( $order_item, $order_type_id );

					// Grab the order and recalculate taxes.
					$order = $order instanceof AtumOrderModel ? $order : wc_get_order( $order_id );
					$order->calculate_totals();

				}
				elseif ( $creating ) {

					$orders->calculate_add_mi_order_item( $current_item, $order_item_id, $order, $product );
					$orders->add_mi_order_item( $order_item_id, $current_item, $order );
				}

			}

		}

	}

	/**
	 * This hook is reached when an order is deleted permanently.
	 *
	 * @since 1.2.4
	 *
	 * @param \WC_Order         $order    The deleted order.
	 * @param \WP_REST_Response $response The response data.
	 * @param \WP_REST_Request  $request  The request sent to the API.
	 */
	public function delete_order_item_inventories( $order, $response, $request ) {

		global $wpdb;
		$order_items = $order->get_items();

		foreach ( $order_items as $order_item ) {
			$wpdb->delete(
				$wpdb->prefix . Inventory::INVENTORY_ORDERS_TABLE,
				[
					'order_item_id' => $order_item->get_id(),
				],
				[
					'%d',
				]
			);
		}

	}

	/**
	 * Prevent items with MI products reduce stock
	 *
	 * @since 1.8.0
	 *
	 * @param int|float              $qty
	 * @param \WC_Order              $order
	 * @param \WC_Order_Item_Product $item
	 *
	 * @return int|float
	 */
	public function maybe_change_order_item_qty( $qty, $order, $item ) {

		$product_id = absint( ! empty( $item->get_variation_id() ) ? $item->get_variation_id() : $item->get_product_id() );
		$product    = wc_get_product( $product_id );

		if ( 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product ) ) {
			$qty = 0;
		}

		return $qty;

	}
	/**
	 * Disable the WC order notes for stock changes when the product has calculated stock
	 *
	 * @since 1.8.0
	 *
	 * @param array $note_data
	 *
	 * @return array
	 */
	public function maybe_remove_order_note( $note_data ) {

		$text_notes = [
			__( 'Stock levels increased:', 'woocommerce' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			__( 'Stock levels reduced:', 'woocommerce' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
		];

		$ids = AtumHelpers::get_order_note_ids( $note_data, $text_notes );

		if ( ! empty( $ids ) ) {
			foreach ( $ids as $id ) {

				if ( $id && 'yes' === Helpers::get_product_multi_inventory_status( $id ) && Helpers::is_product_multi_inventory_compatible( $id ) ) {

					// A product with MI was found, so remove the comment.
					add_action( 'wp_insert_comment', array( AtumHooks::get_instance(), 'remove_order_comment' ), PHP_INT_MAX );
					break;
				}
			}
		}

		return $note_data;

	}

	/**
	 * Add an inventory data to the items_data (this array replaces the one sent from the order meta-boxes)
	 *
	 * @since 1.8.0
	 *
	 * @param array       $items_data
	 * @param array       $new_inv_data
	 * @param \WC_Product $product
	 * @param bool        $has_multiprice
	 *
	 * @return array
	 */
	private static function add_item_data( $items_data, $new_inv_data, $product, $has_multiprice ) {

		$inventory_id  = (int) $new_inv_data['inventory_id'];
		$inventory     = Helpers::get_inventory( $inventory_id );
		$order_item_id = (int) $new_inv_data['order_item_id'];

		// If delete is set and is TRUE, don't include it in the items_data so it will be deleted.
		if ( ! isset( $new_inv_data['delete'] ) || FALSE === $new_inv_data['delete'] ) {

			$qty = isset( $new_inv_data['qty'] ) ? floatval( $new_inv_data['qty'] ) : '';
			$items_data['oi_inventory_qty'][ $order_item_id ][ $inventory_id ] = $qty;
			$items_data['order_item_qty'][ $order_item_id ]                   += $qty;

			if ( $has_multiprice ) {
				$price = $inventory->price;
			}
			else {
				$price = $product->get_price();
			}

			// If the subtotal is coming within the request data, force it. If not, get it from the inventory.
			if ( isset( $new_inv_data['subtotal'] ) ) {
				$items_data['oi_inventory_subtotal'][ $order_item_id ][ $inventory_id ] = floatval( $new_inv_data['subtotal'] );
			}
			else {
				$items_data['oi_inventory_subtotal'][ $order_item_id ][ $inventory_id ] = wc_get_price_excluding_tax( $product, [
					'price' => $price,
					'qty'   => $qty,
				] );
			}
			$items_data['line_subtotal'][ $order_item_id ] += $items_data['oi_inventory_subtotal'][ $order_item_id ][ $inventory_id ];

			// If the total is coming within the request data, force it. If not, get it from the inventory.
			if ( isset( $new_inv_data['total'] ) ) {
				$items_data['oi_inventory_total'][ $order_item_id ][ $inventory_id ] = floatval( $new_inv_data['total'] );
			}
			else {
				$items_data['oi_inventory_total'][ $order_item_id ][ $inventory_id ] = floatval( $price ) * $qty;
			}

			$items_data['line_total'][ $order_item_id ] += $items_data['oi_inventory_total'][ $order_item_id ][ $inventory_id ];

			// TODO: Calculate the refunded totals to update the parent item?
			$items_data['oi_inventory_refund_qty'][ $order_item_id ][ $inventory_id ]   = isset( $new_inv_data['refund_qty'] ) ? floatval( $new_inv_data['refund_qty'] ) : '';
			$items_data['oi_inventory_refund_total'][ $order_item_id ][ $inventory_id ] = isset( $new_inv_data['refund_total'] ) ? floatval( $new_inv_data['refund_total'] ) : '';

			// Inventory extra data - for keep in order if inventory is deleted.
			$items_data['oi_inventory_extra_data'][ $order_item_id ][ $inventory_id ] = $inventory->prepare_order_item_inventory_extra_data( $order_item_id );
		}

		return $items_data;
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
	 * @return InventoryOrders instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
