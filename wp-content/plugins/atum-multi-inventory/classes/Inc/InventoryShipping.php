<?php
/**
 * Inventory Shipping class
 *
 * @since       1.3.7.1
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     AtumMultiInventory\Inc
 */

namespace AtumMultiInventory\Inc;

use AtumMultiInventory\Models\Inventory;


defined( 'ABSPATH' ) || die;

class InventoryShipping {

	/**
	 * The singleton instance holder
	 *
	 * @var InventoryShipping
	 */
	private static $instance;

	/**
	 * InventoryShipping singleton constructor
	 *
	 * @since 1.3.7.1
	 */
	private function __construct() {

		// Edit the shiping rates for items with MI enabled and inventories with shipping class.
		add_action( 'woocommerce_flat_rate_shipping_add_rate', array( $this, 'alter_flat_rates' ), 10, 2 );

	}

	/**
	 * Alter the shipping flat rates to handle inventory shipping classes
	 *
	 * @since 1.3.7.1
	 *
	 * @param \WC_Shipping_Flat_Rate $shipping_flat_rate
	 * @param array                  $rate
	 *
	 * @throws \Exception
	 */
	public function alter_flat_rates( $shipping_flat_rate, $rate ) {

		if ( ! empty( $rate['package']['contents'] ) && is_array( $rate['package']['contents'] ) ) {

			foreach ( $rate['package']['contents'] as $cart_item_key => $cart_item ) {

				if (
					Helpers::is_product_multi_inventory_compatible( $cart_item['data'] ) &&
					'yes' === Helpers::get_product_multi_inventory_status( $cart_item['data'] )
				) {

					/**
					 * Calculate the base costs.
					 */
					$cost = $shipping_flat_rate->get_option( 'cost' );

					if ( '' !== $cost ) {

						// Get the cost base (not counting shipping classes).
						$cost = (float) $this->evaluate_cost(
							$cost,
							array(
								'qty'  => $shipping_flat_rate->get_package_item_qty( $rate['package'] ),
								'cost' => $rate['package']['contents_cost'],
							)
						);

					}

					/**
					 * Calculate the shipping costs for inventories.
					 */

					// With selected inventories.
					if ( ! empty( $cart_item['atum']['selected_mi'] ) ) {

						foreach ( $cart_item['atum']['selected_mi'] as $selected_inventory => $quantity ) {

							$inventory = Helpers::get_inventory( $selected_inventory );

							if ( $inventory->shipping_class ) {
								$cost += (float) $this->calculate_inventory_shipping_cost( $inventory, $quantity, $rate, $shipping_flat_rate );
							}

						}

					}
					// Automatic selection.
					else {

						$inventories   = Helpers::get_product_inventories_sorted( $cart_item['data']->get_id() );
						$item_quantity = $remaining_qty = $shipping_flat_rate->get_package_item_qty( $rate['package'] );

						// Determine the inventories that are going to be used, so we can calculate the shipping costs accordingly.
						foreach ( $inventories as $inventory ) {

							if ( $inventory->is_main() ) {
								$inventory->set_stock_status();
							}

							if ( 'outofstock' === $inventory->stock_status ) {
								continue;
							}

							$out_stock_threshold = $inventory->out_stock_threshold > 0 ? $inventory->out_stock_threshold : 0;

							if ( 'no' !== $inventory->backorders || $inventory->stock_quantity - $out_stock_threshold >= $item_quantity ) {
								$quantity = $item_quantity;
							}
							else {
								$quantity = $inventory->stock_quantity - $out_stock_threshold;
							}

							if ( $inventory->shipping_class ) {
								$cost += (float) $this->calculate_inventory_shipping_cost( $inventory, $quantity, $rate, $shipping_flat_rate );
							}

							$remaining_qty -= $quantity;

							if ( $remaining_qty <= 0 ) {
								break;
							}

						}

					}

				}

			}

		}

		if ( isset( $cost, $shipping_flat_rate->rates, $shipping_flat_rate->rates[ $rate['id'] ] ) ) {
			$shipping_flat_rate->rates[ $rate['id'] ]->set_cost( $cost );
		}

	}

	/**
	 * Calculate the shipping costs.
	 * This method is based on the \WC_Shipping_Flat_Rate::calculate_shipping() method
	 *
	 * @since 1.3.7.1
	 *
	 * @param Inventory              $inventory
	 * @param float|int              $qty
	 * @param array                  $rate
	 * @param \WC_Shipping_Flat_Rate $shipping_flat_rate
	 *
	 * @return string
	 */
	private function calculate_inventory_shipping_cost( $inventory, $qty, $rate, $shipping_flat_rate ) {

		// Add shipping class costs (if any).
		$shipping_classes = WC()->shipping()->get_shipping_classes();
		$class_cost       = 0;

		if ( ! empty( $shipping_classes ) ) {

			$inv_shipping_class  = $inventory->shipping_class;
			$shipping_class_term = get_term_by( 'id', $inv_shipping_class, 'product_shipping_class' );
			$class_cost_string   = $shipping_class_term && $shipping_class_term->term_id ? $shipping_flat_rate->get_option( 'class_cost_' . $shipping_class_term->term_id, $shipping_flat_rate->get_option( 'class_cost_' . $shipping_class_term->slug, '' ) ) : $shipping_flat_rate->get_option( 'no_class_cost', '' );

			if ( '' !== $class_cost_string ) {

				$class_cost = $this->evaluate_cost(
					$class_cost_string,
					array(
						'qty'  => $qty,
						'cost' => Helpers::has_multi_price( $inventory->product_id ) ? ( $qty * $inventory->price ) : $rate['package']['contents_cost'],
					)
				);

			}

		}

		do_action( 'atum/multi_inventory/flat_rate_shipping_add_rate', $class_cost, $inventory, $qty, $rate, $shipping_flat_rate );

		return $class_cost;

	}

	/**
	 * Evaluate a cost from a sum/string.
	 *
	 * @since 1.3.7.1
	 *
	 * @param  string $sum Sum of shipping.
	 * @param  array  $args Args, must contain `cost` and `qty` keys. Having `array()` as default is for back compat reasons.
	 *
	 * @return string
	 */
	private function evaluate_cost( $sum, $args = array() ) {

		// Add warning for subclasses.
		if ( ! is_array( $args ) || ! array_key_exists( 'qty', $args ) || ! array_key_exists( 'cost', $args ) ) {
			wc_doing_it_wrong( __FUNCTION__, '$args must contain `cost` and `qty` keys.', '4.0.1' );
		}

		include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';

		// Allow 3rd parties to process shipping cost arguments.
		$args           = apply_filters( 'atum/multi_inventory/inventory_shipping/evaluate_shipping_cost_args', $args, $sum, $this );
		$locale         = localeconv();
		$decimals       = array( wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',' );
		$this->fee_cost = $args['cost'];

		// Expand shortcodes.
		add_shortcode( 'fee', array( $this, 'fee' ) );

		$sum = do_shortcode(
			str_replace(
				array(
					'[qty]',
					'[cost]',
				),
				array(
					$args['qty'],
					$args['cost'],
				),
				$sum
			)
		);

		remove_shortcode( 'fee', array( $this, 'fee' ) );

		// Remove whitespace from string.
		$sum = preg_replace( '/\s+/', '', $sum );

		// Remove locale from string.
		$sum = str_replace( $decimals, '.', $sum );

		// Trim invalid start/end characters.
		$sum = rtrim( ltrim( $sum, "\t\n\r\0\x0B+*/" ), "\t\n\r\0\x0B+-*/" );

		// Do the math.
		return $sum ? \WC_Eval_Math::evaluate( $sum ) : 0;

	}


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 *
	 * @since 1.3.7.1
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 *
	 * @since 1.3.7.1
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @since 1.3.7.1
	 *
	 * @return InventoryShipping instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
