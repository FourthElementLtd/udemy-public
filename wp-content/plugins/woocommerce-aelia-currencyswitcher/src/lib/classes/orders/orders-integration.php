<?php
namespace Aelia\WC\CurrencySwitcher;
if(!defined('ABSPATH')) { exit; } // Exit if accessed directly

use Aelia\WC\CurrencySwitcher\Traits\Logger_Trait;
use Aelia\WC\Traits\Singleton;

/**
 * Implements functions to handle orders, such as storing and updating meta,
 * converting totals, etc.
 *
 * @since 4.11.0.210517
 */
class Orders_Integration {
	use Singleton;
	use Logger_Trait;

	/**
	 * A list of orders corresponding to item IDs. Used to retrieve the order ID starting from one of the items it contains.
	 *
	 * @var array
	 */
	private $items_orders = array();

	/**
	 * Retrieves the order to which an order item belongs.
	 *
	 * @param int order_item_id The order item.
	 * @return Aelia_Order
	 */
	protected function get_order_from_item($order_item_id) {
		// Check if the order is stored in the internal list
		if(empty($this->items_orders[$order_item_id])) {
			// Cache the order after retrieving it. This will reduce the amount of queries executed
			$this->items_orders[$order_item_id] = Aelia_Order::get_by_item_id($order_item_id);
		}

		return $this->items_orders[$order_item_id];
	}

	/**
	 * Constructor.
	 */
	public function __construct()	{
		add_filter('update_order_item_metadata', array($this, 'update_order_item_metadata'), 10, 4);
		add_filter('add_order_item_metadata', array($this, 'update_order_item_metadata'), 10, 4);

		add_filter('woocommerce_hidden_order_itemmeta', array($this, 'woocommerce_hidden_order_itemmeta'), 10, 1);
	}

	/**
	 * Add custom item metadata added by the plugin.
	 *
	 * @param array item_meta The original metadata to hide.
	 * @return array
	 */
	public function woocommerce_hidden_order_itemmeta($item_meta) {
		$custom_order_item_meta = array(
			'_line_subtotal_base_currency',
			'_line_subtotal_tax_base_currency',
			'_line_tax_base_currency',
			'_line_total_base_currency',
			'tax_amount_base_currency',
			'shipping_tax_amount_base_currency',
			'discount_amount_base_currency',
			'discount_amount_tax_base_currency',
		);
		return array_merge($item_meta, $custom_order_item_meta);
	}

	/**
	 * Indicates if the specified meta key corresponds to a value that should be calculated
	 * in shop's base currency.
	 *
	 * @param string $meta_key
	 * @return boolean
	 * @since 4.11.0.210517
	 */
	protected static function should_calculate_order_item_meta_in_base_currency(string $meta_key): bool {
		$meta_to_calculate_in_base_currency = array(
			'_line_subtotal',
			'_line_subtotal_tax',
			'_line_tax',
			'_line_total',
			'tax_amount',
			'shipping_tax_amount',
			'discount_amount',
			'discount_amount_tax',
		);
		return in_array($meta_key, $meta_to_calculate_in_base_currency);
	}

	/**
	 * Adds line totals in base currency for each product in an order.
	 *
	 * @param null $check
	 * @param int $order_item_id The ID of the order item.
	 * @param string $meta_key The meta key being saved.
	 * @param mixed $meta_value The value being saved.
	 * @return null|bool
	 *
	 * @see update_metadata().
	 */
	public function update_order_item_metadata($check, $order_item_id, $meta_key, $meta_value) {
		// Convert line totals into base Currency (they are saved in the currency used
		// to complete the transaction)
		if(self::should_calculate_order_item_meta_in_base_currency($meta_key)) {
			// Load the order
			$order = $this->get_order_from_item($order_item_id);

			$order_id = $order->get_id();
			if(empty($order_id)) {
				// An empty order id indicates that something is not right. Without it,
				// we cannot calculate the amounts in base currency
				$this->get_logger()->info(__('Could not find the order linked to the order item. Calculation of metadata in base currency skipped.', Definitions::TEXT_DOMAIN),
																	array(
																		'Order Item ID' => $order_item_id,
																		'Meta Key' => $meta_key,
																	));
			}
			else {
				// Retrieve the order currency
				// If Order Currency is empty, it means that we are in checkout phase.
				// WooCommerce saves the Order Currency AFTER the Order Total (a bit
				// nonsensical, but that's the way it is). In such case, we can take the
				// currency currently selected to place the Order and set it as the default
				$order_currency = $order->get_currency();
				if(empty($order_currency)) {
					$order_currency = get_woocommerce_currency();
				}

				// Save the amount in base currency. This will be used to correct the reports
				$amount_in_base_currency = WC_Aelia_CurrencySwitcher::instance()->convert(
					$meta_value,
					$order_currency,
					WC_Aelia_CurrencySwitcher::settings()->base_currency(),
					\Aelia\WC\ExchangeRatesModel::EXCHANGE_RATE_DECIMALS,
					false
				);
				$amount_in_base_currency = wc_float_to_string($amount_in_base_currency);

				// Update meta value with new string
				// TODO Rewrite method to use CRUD functions, if possible
				update_metadata('order_item', $order_item_id, $meta_key . '_base_currency', $amount_in_base_currency);
			}
		}

		return $check;
	}
}