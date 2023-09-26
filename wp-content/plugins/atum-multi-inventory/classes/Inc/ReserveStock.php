<?php
/**
 * Class responsible of handling the reserved stock for MI
 *
 * @since       1.3.8
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     AtumMultiInventory\Inc
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Models\Inventory;
use Automattic\WooCommerce\Checkout\Helpers\ReserveStockException;


class ReserveStock {

	/**
	 * The singleton instance holder
	 *
	 * @var ReserveStock
	 */
	private static $instance;

	/**
	 * WooCommerce out of stock threshold
	 *
	 * @since 1.3.8
	 *
	 * @var int
	 */
	private $wc_threshold;


	/**
	 * ReserveStock singleton constructor.
	 *
	 * @since 1.3.8
	 */
	private function __construct() {

		// Replace the WC actions with our own copies to handle MIs.
		remove_action( 'woocommerce_checkout_order_created', 'wc_reserve_stock_for_order' );
		add_action( 'woocommerce_checkout_order_created', array( $this, 'maybe_reserve_stock_for_order' ) );

		// Remove reserved inventories when needed.
		add_action( 'woocommerce_checkout_order_exception', array( $this, 'release_inventory_stock_for_order' ), 11 );
		add_action( 'woocommerce_payment_complete', array( $this, 'release_inventory_stock_for_order' ), 12 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'release_inventory_stock_for_order' ), 12 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'release_inventory_stock_for_order' ), 12 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'release_inventory_stock_for_order' ), 12 );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'release_inventory_stock_for_order' ), 12 );

		add_filter( 'atum/multi_inventory/order_item_inventories', array( $this, 'filter_reserved_inventories_on_checkout' ), 10, 2 );

	}

	/**
	 * Hold stock for an order.
	 *
	 * @since 1.3.8
	 *
	 * @param \WC_Order|int $order Order ID or instance.
	 *
	 * @throws ReserveStockException If reserve stock fails.
	 */
	public function maybe_reserve_stock_for_order( $order ) {

		/**
		 * Filter: woocommerce_hold_stock_for_checkout
		 * Allows enable/disable hold stock functionality on checkout.
		 *
		 * NOTE: Preserving the WC filter name here to maintain compatibility.
		 *
		 * @param bool $enabled Default to true if managing stock globally.
		 */
		if ( ! apply_filters( 'woocommerce_hold_stock_for_checkout', wc_string_to_bool( get_option( 'woocommerce_manage_stock', 'yes' ) ) ) ) {
			return;
		}

		$order = $order instanceof \WC_Order ? $order : wc_get_order( $order );

		if ( $order ) {
			$this->reserve_stock_for_order( $order );
		}

	}

	/**
	 * Put a temporary hold on stock for an order if enough is available.
	 *
	 * @since 1.3.8
	 *
	 * @param \WC_Order|object $order Order object.
	 * @param int              $minutes How long to reserve stock in minutes. Defaults to woocommerce_hold_stock_minutes.
	 *
	 * @throws ReserveStockException If stock cannot be reserved.
	 */
	public function reserve_stock_for_order( $order, $minutes = 0 ) {

		$minutes = $minutes ? $minutes : (int) get_option( 'woocommerce_hold_stock_minutes', 60 );

		if ( ! $minutes ) {
			return;
		}

		try {

			$this->wc_threshold = wc_stock_amount( get_option( 'woocommerce_notify_no_stock_amount' ) );

			$items        = array_filter(
				$order->get_items(),
				function ( $item ) {

					/**
					 * Variable declaration,
					 *
					 * @var \WC_Order_Item_Product $item
					 */
					return $item->is_type( 'line_item' ) && $item->get_product() instanceof \WC_Product && $item->get_quantity() > 0;
				}
			);
			$product_rows = $inventory_rows = array();

			foreach ( $items as $item ) {

				/**
				 * Variable declaration.
				 *
				 * @var \WC_Product            $product
				 * @var \WC_Order_Item_Product $item
				 */
				$product = $item->get_product();

				if ( ! $product->is_in_stock() ) {

					throw new ReserveStockException(
						'woocommerce_product_out_of_stock',
						sprintf(
						/* translators: %s: product name */
							__( '&quot;%s&quot; is out of stock and cannot be purchased.', ATUM_MULTINV_TEXT_DOMAIN ),
							$product->get_name()
						),
						403
					);

				}

				// If stock management is off, no need to reserve any stock here.
				if ( ! $product->managing_stock() || $product->backorders_allowed() ) {
					continue;
				}

				$managed_by_id = $product->get_stock_managed_by_id();

				/**
				 * Filter order item quantity.
				 *
				 * @param int|float             $quantity Quantity.
				 * @param \WC_Order              $order    Order data.
				 * @param \WC_Order_Item_Product $item Order item data.
				 */
				$item_quantity = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );

				$product_rows[ $managed_by_id ] = isset( $product_rows[ $managed_by_id ] ) ? $product_rows[ $managed_by_id ] + $item_quantity : $item_quantity;

			}

			if ( ! empty( $product_rows ) ) {

				foreach ( $product_rows as $product_id => $quantity ) {

					$this->reserve_stock_for_product( $product_id, $quantity, $order, $minutes );
				}

			}

		} catch ( ReserveStockException $e ) {

			$this->release_stock_for_order( $order );
			throw $e;

		}

	}

	/**
	 * Reserve stock for a product by inserting rows into the DB.
	 *
	 * @since 1.3.8
	 *
	 * @param int              $product_id     Product ID which is having stock reserved.
	 * @param int|float        $stock_quantity Stock amount to reserve.
	 * @param \WC_Order|object $order          Order object which contains the product.
	 * @param int              $minutes        How long to reserve stock in minutes.
	 *
	 * @throws ReserveStockException If a not enough inventories stock.
	 */
	public function reserve_stock_for_product( $product_id, $stock_quantity, $order, $minutes ) {

		global $wpdb;

		$order_id = $order->get_id();

		if ( 'yes' === Helpers::get_product_multi_inventory_status( $product_id ) ) {

			$product             = AtumHelpers::get_atum_product( $product_id );
			$inventory_iteration = Helpers::get_product_inventory_iteration( $product );

			try {
				$inventories = Helpers::get_product_inventories_sorted( $product_id );
			}
			catch ( \Exception $e ) {
				throw new ReserveStockException(
					'woocommerce_product_not_enough_stock',
					sprintf(
					/* translators: %s: product name */
						__( 'Not enough units of %s are available in stock to fulfil this order.', ATUM_MULTINV_TEXT_DOMAIN ),
						$product ? $product->get_name() : '#' . $product_id
					),
					403
				);
			}

			// Check whether to use only selected inventories. It's assumed that one product only appears once per cart.
			$selected_mi = Helpers::get_cart_product_selected_inventories( $product );

			// If inventory iteration is not allowed, get only the first one.
			if ( empty( $selected_mi ) && 'out_of_stock' === $inventory_iteration ) {
				$inventories = array( reset( $inventories ) );
			}

			$inventories_ids = array_map( function ( $inv ) {

				return $inv->id;
			}, $inventories );
			$reserved_qtys   = $this->get_inventories_reserved_qtys( $inventories_ids, $order_id );
			$new_reserved    = $unavailable_selected = [];

			foreach ( $inventories as $inventory ) {

				// If there are selected inventories, get the configuration from them.
				if ( ! empty( $selected_mi ) && ! array_key_exists( $inventory->id, $selected_mi ) ) {
					continue;
				}

				if ( $inventory->is_main() ) {
					$inventory->set_stock_status();
				}

				// If the item has an "out of stock threshold" lower than the total stock quantity, use it as the max available stock.
				$reserved_stock  = ! empty( $reserved_qtys[ $inventory->id ] ) ? $reserved_qtys[ $inventory->id ] : 0;
				$available_stock = $inventory->get_available_stock();
				$inventory_stock = $available_stock > $reserved_stock ? $available_stock - $reserved_stock : 0;

				if ( empty( $selected_mi ) ) {
					$stock_quantity -= $inventory_stock;

					if ( 0 < $stock_quantity ) {

						$new_reserved[ $inventory->id ] = $inventory_stock;
					}
					else {
						$new_reserved[ $inventory->id ] = $inventory_stock + $stock_quantity;
						break;
					}

				}
				elseif ( $selected_mi[ $inventory->id ] > $inventory_stock ) {
					$unavailable_selected[] = $inventory->name;
				}
				else {
					$new_reserved[ $inventory->id ] = $selected_mi[ $inventory->id ];
				}

			}

			// If not all the qty can be assigned.
			if ( empty( $selected_mi ) && 0 < $stock_quantity ) {
				throw new ReserveStockException(
					'woocommerce_product_not_enough_stock',
					sprintf(
					/* translators: %s: product name */
						__( 'Not enough units of %s are available in stock to fulfil this order.', ATUM_MULTINV_TEXT_DOMAIN ),
						$product ? $product->get_name() : '#' . $product_id
					),
					403
				);
			}

			if ( ! empty( $selected_mi ) && ! empty( $unavailable_selected ) ) {

				$unavailable = implode( ', ', $unavailable_selected );

				throw new ReserveStockException(
					'atum_inventories_not_enough_stock',
					sprintf(
					/* translators: %s: product name */
						__( 'Not enough units of %1$s are available in %2$s to fulfil this order.', ATUM_MULTINV_TEXT_DOMAIN ),
						$product ? $product->get_name() : '#' . $product_id,
						$unavailable
					),
					403
				);

			}

			$values_clauses = [];
			foreach ( $new_reserved as $inventory_id => $qty ) {

				$values_clauses[] = "($order_id, $inventory_id, $qty, NOW(), ( NOW() + INTERVAL $minutes MINUTE ))";
			}

			$values = implode( ',', $values_clauses );

			// phpcs:disable WordPress.DB.PreparedSQL
			$result = $wpdb->query(
				"
				INSERT INTO $wpdb->prefix" . Inventory::INVENTORY_RESERVED_STOCK_TABLE . " ( `order_id`, `inventory_id`, `stock_quantity`, `timestamp`, `expires` )
				VALUES
					$values
				ON DUPLICATE KEY UPDATE	`expires` = VALUES( `expires` ), `stock_quantity` = VALUES( `stock_quantity` )
					"
			);
			// phpcs:enable WordPress.DB.PreparedSQL

		}
		else {

			$query_for_stock          = $this->get_query_for_stock( $product_id );
			$query_for_reserved_stock = $this->get_query_for_reserved_stock( $product_id, $order_id );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$result = $wpdb->query(
				$wpdb->prepare(
					"
				INSERT INTO {$wpdb->wc_reserved_stock} ( `order_id`, `product_id`, `stock_quantity`, `timestamp`, `expires` )
				SELECT %d, %d, %d, NOW(), ( NOW() + INTERVAL %d MINUTE ) FROM DUAL
				WHERE ( $query_for_stock FOR UPDATE ) - ( $query_for_reserved_stock FOR UPDATE ) >= %d
				ON DUPLICATE KEY UPDATE `expires` = VALUES( `expires` ), `stock_quantity` = VALUES( `stock_quantity` )
				",
					$order_id,
					$product_id,
					$stock_quantity,
					$minutes,
					$stock_quantity
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

			if ( ! $result ) {

				$product = wc_get_product( $product_id );
				throw new ReserveStockException(
					'woocommerce_product_not_enough_stock',
					sprintf(
					/* translators: %s: product name */
						__( 'Not enough units of %s are available in stock to fulfil this order.', ATUM_MULTINV_TEXT_DOMAIN ),
						$product instanceof \WC_Product ? $product->get_name() : '#' . $product_id
					),
					403
				);

			}

		}

	}

	/**
	 * Release a temporary hold on stock for an order.
	 *
	 * @since 1.3.8
	 *
	 * @param \WC_Order|object|int $order Order object.
	 */
	public function release_stock_for_order( $order ) {

		global $wpdb;

		$order_id = $order instanceof \WC_Order ? $order->get_id() : (int) $order;

		$wpdb->delete(
			$wpdb->wc_reserved_stock,
			array(
				'order_id' => $order_id,
			)
		);

		$this->release_inventory_stock_for_order( $order );

	}

	/**
	 * Release a temporary hold on inventories stock for an order.
	 *
	 * @since 1.3.8
	 *
	 * @param \WC_Order|object|int $order Order object.
	 */
	public function release_inventory_stock_for_order( $order ) {

		global $wpdb;

		$order_id = $order instanceof \WC_Order ? $order->get_id() : (int) $order;

		$wpdb->delete(
			$wpdb->prefix . Inventory::INVENTORY_RESERVED_STOCK_TABLE,
			array(
				'order_id' => $order_id,
			)
		);
	}

	/**
	 * Returns query statement for getting current stock of a product.
	 * Based on \WC_Product_Data_Store_CPT::get_query_for_stock()
	 *
	 * @since 1.3.8
	 *
	 * @internal MAX function below is used to make sure result is a scalar.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return string|void Query statement.
	 */
	private function get_query_for_stock( $product_id ) {

		global $wpdb;

		return $wpdb->prepare(
			"
			SELECT COALESCE ( MAX( meta_value ), 0 ) FROM $wpdb->postmeta as meta_table
			WHERE meta_table.meta_key = '_stock'
			AND meta_table.post_id = %d
			",
			$product_id
		);

	}

	/**
	 * Returns query statement for getting reserved stock of a product.
	 *
	 * @since 1.3.8
	 *
	 * @param int     $product_id       Product ID.
	 * @param integer $exclude_order_id Optional order to exclude from the results.
	 *
	 * @return string|void Query statement.
	 */
	private function get_query_for_reserved_stock( $product_id, $exclude_order_id = 0 ) {

		global $wpdb;

		$query = $wpdb->prepare(
			"
			SELECT COALESCE( SUM( stock_table.`stock_quantity` ), 0 ) FROM $wpdb->wc_reserved_stock stock_table
			LEFT JOIN $wpdb->posts posts ON stock_table.`order_id` = posts.ID
			WHERE posts.post_status IN ( 'wc-checkout-draft', 'wc-pending' )
			AND stock_table.`expires` > NOW()
			AND stock_table.`product_id` = %d
			AND stock_table.`order_id` != %d
			",
			$product_id,
			$exclude_order_id
		);

		/**
		 * Filter: woocommerce_query_for_reserved_stock
		 * Allows to filter the query for getting reserved stock of a product.
		 *
		 * @since 4.5.0
		 * @param string $query            The query for getting reserved stock of a product.
		 * @param int    $product_id       Product ID.
		 * @param int    $exclude_order_id Order to exclude from the results.
		 */
		return apply_filters( 'woocommerce_query_for_reserved_stock', $query, $product_id, $exclude_order_id );

	}

	/**
	 * Returns an array with the queried inventories reserved stock.
	 *
	 * @since 1.3.8
	 *
	 * @param array $inventories
	 * @param int   $exclude_order_id
	 *
	 * @return array
	 */
	public function get_inventories_reserved_qtys( $inventories, $exclude_order_id = 0 ) {

		global  $wpdb;

		$reserved = [];

		// phpcs:disable WordPress.DB.PreparedSQL
		$results = $wpdb->get_results( "
			SELECT st.`inventory_id`, COALESCE( SUM( st.`stock_quantity` ), 0 ) qty FROM $wpdb->prefix" . Inventory::INVENTORY_RESERVED_STOCK_TABLE . " st
			LEFT JOIN $wpdb->posts posts ON st.`order_id` = posts.ID
			WHERE posts.post_status IN ( 'wc-checkout-draft', 'wc-pending' )
			AND st.`expires` > NOW()
			AND st.`inventory_id` IN(" . implode( ',', $inventories ) . ")
			AND st.`order_id` != $exclude_order_id
			GROUP BY st.`inventory_id`
			" );
		// phpcs:enable WordPress.DB.PreparedSQL

		if ( $results ) {
			foreach ( $results as $result ) {
				$reserved[ $result->inventory_id ] = $result->qty;
			}
		}

		return $reserved;
	}

	/**
	 * Returns an array with the inventories reserved stock for an order.
	 *
	 * @since 1.4.1
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function get_order_reserved_inventories( $order_id = 0 ) {

		global  $wpdb;

		$reserved = [];

		if ( $order_id ) {

			// phpcs:disable WordPress.DB.PreparedSQL
			$results = $wpdb->get_results( "
			SELECT st.`inventory_id`, COALESCE( SUM( st.`stock_quantity` ), 0 ) qty FROM $wpdb->prefix" . Inventory::INVENTORY_RESERVED_STOCK_TABLE . " st
			LEFT JOIN $wpdb->posts posts ON st.`order_id` = posts.ID
			WHERE posts.post_status IN ( 'wc-checkout-draft', 'wc-pending' )
			AND st.`expires` > NOW()
			AND st.`order_id` = $order_id
			GROUP BY st.`inventory_id`
			" );
			// phpcs:enable WordPress.DB.PreparedSQL

			if ( $results ) {
				foreach ( $results as $result ) {
					$reserved[ $result->inventory_id ] = $result->qty;
				}
			}
		}

		return $reserved;
	}

	/**
	 * Update the sorted inventories stock (without saving) removing the reserved stock to allow consuming the stock from the proper inventories during the checkout.
	 *
	 * @since 1.3.8
	 *
	 * @param Inventory[]            $sorted_inventories
	 * @param \WC_Order_Item_Product $item
	 *
	 * @return Inventory[]
	 */
	public function filter_reserved_inventories_on_checkout( $sorted_inventories, $item ) {

		if ( ! apply_filters( 'woocommerce_hold_stock_for_checkout', wc_string_to_bool( get_option( 'woocommerce_manage_stock', 'yes' ) ) ) ) {
			return $sorted_inventories;
		}

		if ( ! empty( $sorted_inventories ) ) {

			$inventories_ids = array_map( function ( $inv ) {

				return $inv->id;
			}, $sorted_inventories );

			$reserved_qtys = $this->get_inventories_reserved_qtys( $inventories_ids, $item->get_order_id() );

			foreach ( $sorted_inventories as $inventory ) {

				if ( ! empty( $reserved_qtys[ $inventory->id ] ) ) {

					$inventory->set_meta( array( 'stock_quantity' => $inventory->stock_quantity - $reserved_qtys[ $inventory->id ] ) );
				}

			}

		}

		return $sorted_inventories;
	}

	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 *
	 * @since 1.3.8
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 *
	 * @since 1.3.8
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @since 1.3.8
	 *
	 * @return ReserveStock instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
