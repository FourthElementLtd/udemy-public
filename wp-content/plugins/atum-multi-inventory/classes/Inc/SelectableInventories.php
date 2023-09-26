<?php
/**
 * Selectable Inventories class
 *
 * @since       1.3.7
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     AtumMultiInventory\Inc
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Models\Inventory;
use League\Csv\Exception;


class SelectableInventories {

	/**
	 * The singleton instance holder
	 *
	 * @var SelectableInventories
	 */
	private static $instance;

	/**
	 * The inventory items added to the cart
	 *
	 * @var array
	 */
	private $cart_inventory_items = [];


	/**
	 * SelectableInventories singleton constructor
	 *
	 * @since 1.3.7
	 */
	private function __construct() {

		// Add frontend hooks.
		if ( ! is_admin() ) {

			// Add the UI to the product's page.
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_selectable_inventories_ui_to_product' ) );
			add_filter( 'woocommerce_available_variation', array( $this, 'add_selectable_inventories_data_to_variation' ), 10, 3 );

			// Add the selectable items data to the cart.
			add_action( 'woocommerce_add_to_cart', array( $this, 'selectable_add_to_cart' ), 10, 6 );

			// Add the selected items to the cart.
			add_action( 'woocommerce_before_cart_contents', array( $this, 'add_cart_filters' ) );

			// Handle selected MI updates within cart.
			add_filter( 'woocommerce_update_cart_action_cart_updated', array( $this, 'update_cart_selected_inventories' ) );

			// Check if an inventory cart item needs to be removed.
			add_action( 'wp_loaded', array( $this, 'maybe_remove_inventory_cart_item' ), 19 ); // The priority must be higher than the WC hook.

			// Enqueue the styles and scripts needed on the frontend.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );

		}

	}

	/**
	 * Add the UI to the product's page
	 *
	 * @since 1.3.7
	 *
	 * @param \WC_Product $product
	 */
	public function add_selectable_inventories_ui_to_product( $product = NULL ) {

		$product = ! $product && isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( self::is_selectable_enabled( $product ) ) {

			$inventory_selection_mode = Helpers::get_product_selectable_inventories_mode( $product );
			$inventories              = Helpers::get_product_inventories_sorted( $product->get_id() );

			AtumHelpers::load_view( ATUM_MULTINV_PATH . "views/selectable-inventories/$inventory_selection_mode", compact( 'product', 'inventories' ) );

		}

	}

	/**
	 * Add the selectable inventories data to the variation JSON that is added to the variable products
	 *
	 * @since 1.3.7.1
	 *
	 * @param array                 $variation_data
	 * @param \WC_Product_Variable  $variable
	 * @param \WC_Product_Variation $variation
	 *
	 * @return array
	 */
	public function add_selectable_inventories_data_to_variation( $variation_data, $variable, $variation ) {

		ob_start();
		$this->add_selectable_inventories_ui_to_product( $variation );
		$ui = ob_get_clean();

		if ( $ui ) {
			$variation_data['atum_mi_selectable'] = $ui;
		}

		return $variation_data;

	}

	/**
	 * Adds selectable inventories' data to the cart.
	 *
	 * @since 1.3.7
	 *
	 * @param  string $cart_item_key
	 * @param  int    $product_id
	 * @param  int    $quantity
	 * @param  int    $variation_id
	 * @param  array  $variation
	 * @param  array  $cart_item_data
	 *
	 * @throws \Exception
	 */
	public function selectable_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

		$cart_contents = WC()->cart->cart_contents;

		if ( isset( $cart_contents[ $cart_item_key ] ) && ! empty( $_POST['atum']['select-mi'] ) ) {

			$cart_item = $cart_contents[ $cart_item_key ];

			// On the list mode an array of inventory_id => $qty is coming.
			if ( is_array( $_POST['atum']['select-mi'] ) ) {

				foreach ( $_POST['atum']['select-mi'] as $inventory_id => $inv_qty ) {

					if ( $inv_qty <= 0 ) {
						continue;
					}

					$selected_inventory = absint( $inventory_id );

					if ( self::is_selectable_enabled( $cart_item['data'] ) ) {

						// If there were some items of this inventories already selected in the cart, we must preserve them.
						$prev_qty = isset( $cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $selected_inventory ] ) ? $cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $selected_inventory ] : 0;
						$new_qty  = $prev_qty + wc_stock_amount( $inv_qty );

						// If the selected inventory there has no enough stock, throw an error.
						if ( $prev_qty > 0 ) {
							$inventory = Helpers::get_inventory( $selected_inventory );
							$this->check_available_stock( $inventory, $prev_qty, $new_qty );
						}

						WC()->cart->cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $selected_inventory ] = $new_qty;

					}

				}

			}
			// On the dropdown mode it comes the selected inventory ID only.
			else {

				$selected_inventory = absint( $_POST['atum']['select-mi'] );

				if ( self::is_selectable_enabled( $cart_item['data'] ) ) {

					// If there were some items of this inventories already selected in the cart, we must preserve them.
					$prev_qty = isset( $cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $selected_inventory ] ) ? $cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $selected_inventory ] : 0;
					$new_qty  = $prev_qty + $quantity;

					// If the selected inventory there has no enough stock, throw an error.
					if ( $prev_qty > 0 ) {
						$inventory = Helpers::get_inventory( $selected_inventory );
						$this->check_available_stock( $inventory, $prev_qty, $new_qty );
					}

					WC()->cart->cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $selected_inventory ] = $prev_qty + $quantity;

				}

			}

		}
	}

	/**
	 * Check if there is enough stock available for any inventory before adding more to the cart
	 *
	 * @since 1.3.7.1
	 *
	 * @param Inventory $inventory
	 * @param int|float $prev_qty
	 * @param int|float $new_qty
	 *
	 * @throws \Exception
	 */
	private function check_available_stock( $inventory, $prev_qty, $new_qty ) {

		if ( $inventory->stock_quantity < $new_qty && 'no' === $inventory->backorders ) {

			throw new \Exception(
				sprintf(
					'<a href="%s" class="button wc-forward">%s</a> %s',
					wc_get_cart_url(),
					__( 'View cart', ATUM_MULTINV_TEXT_DOMAIN ),
					/* translators: 1: quantity in stock 2: current quantity */
					sprintf( __( 'You cannot add that amount to the cart &mdash; we have %1$s in stock for that location and you already have %2$s in your cart.', ATUM_MULTINV_TEXT_DOMAIN ), $inventory->stock_quantity, $prev_qty )
				)
			);

		}

	}

	/**
	 * Add the selectable inventories filters for the cart
	 *
	 * @since 1.3.7
	 */
	public function add_cart_filters() {
		add_filter( 'woocommerce_get_cart_contents', array( $this, 'add_selected_items_to_cart' ) );
	}

	/**
	 * Add the selected inventories as cart items
	 *
	 * @since 1.3.7
	 *
	 * @param array $cart_contents
	 *
	 * @return array
	 */
	public function add_selected_items_to_cart( $cart_contents ) {

		$cart_altered = FALSE;

		foreach ( $cart_contents as $key => $cart_item ) {

			if ( ! empty( $cart_item['atum']['selected_mi'] ) && is_array( $cart_item['atum']['selected_mi'] ) ) {

				$item_pos = array_search( $key, array_keys( $cart_contents ) );

				foreach ( $cart_item['atum']['selected_mi'] as $selected_mi => $qty ) {

					$inventory           = Helpers::get_inventory( $selected_mi );
					$inventory_cart_item = $this->add_inventory_cart_item( $inventory, $cart_item );

					$cart_contents = WC()->cart->cart_contents = array_slice( $cart_contents, 0, $item_pos + 1 ) + $inventory_cart_item + array_slice( $cart_contents, $item_pos + 1 );
					$cart_altered  = TRUE;

				}

			}

		}

		// Add filters to be able to alter the cart item details.
		if ( $cart_altered ) {
			add_filter( 'woocommerce_cart_item_class', array( $this, 'cart_inventory_item_class' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_permalink', array( $this, 'cart_inventory_item_permalink' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'cart_inventory_item_remove_link' ), 10, 2 );
			add_filter( 'woocommerce_cart_item_thumbnail', array( $this, 'cart_inventory_item_thumbnail' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_name', array( $this, 'cart_inventory_item_name' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_backorder_notification', array( $this, 'cart_inventory_item_backorder_notification' ), 10, 2 );
			add_filter( 'woocommerce_cart_item_price', array( $this, 'cart_inventory_item_price' ), PHP_INT_MAX, 3 ); // High priority level required.
			add_filter( 'woocommerce_cart_item_quantity', array( $this, 'cart_inventory_item_quantity' ), 10, 3 );
			add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'cart_inventory_item_subtotal' ), 10, 3 );
		}

		$product = $cart_item['data'];

		if ( self::is_selectable_enabled( $product ) ) {
			add_action( 'woocommerce_after_cart_item_name', array( $this, 'after_cart_selectable_item_name' ), 10, 2 );
		}

		remove_filter( 'woocommerce_get_cart_contents', array( $this, 'add_selected_items_to_cart' ) );

		return $cart_contents;

	}

	/**
	 * Add an inventory item to the WC cart
	 *
	 * @since 1.3.7
	 *
	 * @param Inventory $inventory
	 * @param array     $cart_item
	 *
	 * @return array
	 */
	private function add_inventory_cart_item( $inventory, $cart_item ) {

		$item_hash = $this->get_selected_mi_hash( $inventory->id );

		$inventory_cart_item = array(
			$item_hash => array(
				'key'          => $item_hash,
				'product_id'   => $cart_item['product_id'],
				'variation_id' => $cart_item['variation_id'],
				'variation'    => $cart_item['variation'],
				'quantity'     => $cart_item['atum']['selected_mi'][ $inventory->id ],
				'data'         => $cart_item['data'],
				'inventory'    => $inventory,
				'parent_key'   => $cart_item['key'],
			),
		);

		// Store the inventory item to be able to use it later.
		$this->cart_inventory_items[ $item_hash ] = $inventory_cart_item[ $item_hash ];

		return $inventory_cart_item;

	}

	/**
	 * Get the hash for the specified Inventory ID
	 *
	 * @since 1.3.7
	 *
	 * @param int $inventory_id
	 *
	 * @return string
	 */
	private function get_selected_mi_hash( $inventory_id ) {
		return md5( "inventory: $inventory_id" );
	}

	/**
	 * Alter the CSS classes for the inventory items in cart
	 *
	 * @since 1.3.7
	 *
	 * @param string $classes
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 *
	 * @return string
	 */
	public function cart_inventory_item_class( $classes, $cart_item, $cart_item_key ) {

		if ( array_key_exists( $cart_item_key, $this->cart_inventory_items ) ) {
			$classes .= ' atum-mi-cart-item';
		}

		return $classes;
	}

	/**
	 * Alter the permalink for the inventory items in cart
	 *
	 * @since 1.3.7
	 *
	 * @param string $permalink
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 *
	 * @return string
	 */
	public function cart_inventory_item_permalink( $permalink, $cart_item, $cart_item_key ) {

		if ( array_key_exists( $cart_item_key, $this->cart_inventory_items ) ) {
			$permalink = '';
		}

		return $permalink;
	}

	/**
	 * Alter the remove link for the inventory items in cart
	 *
	 * @since 1.3.7
	 *
	 * @param string $remove_link
	 * @param string $cart_item_key
	 *
	 * @return string
	 */
	public function cart_inventory_item_remove_link( $remove_link, $cart_item_key ) {

		if ( array_key_exists( $cart_item_key, $this->cart_inventory_items ) ) {
			$cart_inventory_item = $this->cart_inventory_items[ $cart_item_key ];
			$remove_link         = str_replace( '<a', '<a data-inventory="' . $cart_inventory_item['inventory']->id . '"', $remove_link );
		}

		return $remove_link;
	}

	/**
	 * Alter the thumbnails for the inventory items in cart
	 *
	 * @since 1.3.7
	 *
	 * @param string $image
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 *
	 * @return string
	 */
	public function cart_inventory_item_thumbnail( $image, $cart_item, $cart_item_key ) {

		if ( array_key_exists( $cart_item_key, $this->cart_inventory_items ) ) {
			$image = '';
		}

		return $image;
	}

	/**
	 * Alter the names for the inventory items in cart
	 *
	 * @since 1.3.7
	 *
	 * @param string $name
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 *
	 * @return string
	 */
	public function cart_inventory_item_name( $name, $cart_item, $cart_item_key ) {

		if ( array_key_exists( $cart_item_key, $this->cart_inventory_items ) ) {
			$cart_inventory_item = $this->cart_inventory_items[ $cart_item_key ];
			$name                = '<span><i class="atum-icon atmi-arrow-child"></i><i class="atum-icon atmi-multi-inventory"></i></span> ' . $cart_inventory_item['inventory']->name;
		}

		return $name;
	}

	/**
	 * Alter the backorder notification for the inventory items in cart
	 *
	 * @since 1.3.7
	 *
	 * @param string $notification
	 * @param int    $product_id
	 *
	 * @return string
	 */
	public function cart_inventory_item_backorder_notification( $notification, $product_id ) {

		// TODO: HOW TO DO THIS ONLY WITH THE PRODUCT ID??

		return $notification;
	}

	/**
	 * Alter the prices for the inventory items in cart
	 *
	 * @since 1.3.7
	 *
	 * @param string $price
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 *
	 * @return string
	 */
	public function cart_inventory_item_price( $price, $cart_item, $cart_item_key ) {

		if ( array_key_exists( $cart_item_key, $this->cart_inventory_items ) ) {

			$product = $cart_item['data'];

			if ( Helpers::has_multi_price( $product ) ) {
				$cart_inventory_item = $this->cart_inventory_items[ $cart_item_key ];
				$price               = wc_price( $cart_inventory_item['inventory']->price );
			}

			$price = '<i class="atum-icon atmi-arrow-child"></i>' . $price;

		}

		return $price;
	}

	/**
	 * Alter the quantities for the invetory items in cart
	 *
	 * @since 1.3.7
	 *
	 * @param string $quantity
	 * @param string $cart_item_key
	 * @param array  $cart_item
	 *
	 * @return string
	 */
	public function cart_inventory_item_quantity( $quantity, $cart_item_key, $cart_item ) {

		if ( array_key_exists( $cart_item_key, $this->cart_inventory_items ) ) {

			$cart_inventory_item = $this->cart_inventory_items[ $cart_item_key ];
			$args                = apply_filters( 'atum/multi_inventory/inventory_item_quantity_args', array(
				'input_id'     => uniqid( 'quantity_' ),
				'input_name'   => "atum_cart[{$cart_inventory_item['parent_key']}][{$cart_inventory_item['inventory']->id}]",
				'input_value'  => $cart_inventory_item['quantity'],
				'classes'      => [ 'input-text', 'qty', 'text', 'atum-mi-qty' ],
				'step'         => 1,
				'max_value'    => 'no' === $cart_inventory_item['inventory']->backorders ? $cart_inventory_item['inventory']->get_available_stock() : '',
				'min_value'    => '0',
				'product_name' => $cart_inventory_item['inventory']->name,
				'pattern'      => '[0-9]*',
				'inputmode'    => 'numeric',
				'placeholder'  => '',
			) );

			ob_start();
			wc_get_template( 'global/quantity-input.php', $args );
			$quantity = ob_get_clean();

		}
		elseif ( ! empty( $cart_item['atum']['selected_mi'] ) ) {
			$quantity = str_replace( '<input', '<input readonly', $quantity );
		}

		return $quantity;

	}

	/**
	 * Alter the subtotal for the inventory items in cart
	 *
	 * @since 1.3.7
	 *
	 * @param string $subtotal
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 *
	 * @return string
	 */
	public function cart_inventory_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {

		if ( array_key_exists( $cart_item_key, $this->cart_inventory_items ) ) {

			$product_id = $cart_item['variation_id'] ?: $cart_item['product_id'];
			$price      = Helpers::has_multi_price( $product_id ) ? $cart_item['inventory']->price : $cart_item['data']->get_price();
			$subtotal   = $cart_item['quantity'] * $price;

			$subtotal = '<span class="atum-icon atmi-arrow-child"></span>' . wc_price( $subtotal );

		}

		return $subtotal;
	}

	/**
	 * Add the selectable items' dropdown to the cart
	 *
	 * @since 1.3.7
	 *
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 *
	 * @throws \Exception
	 */
	public function after_cart_selectable_item_name( $cart_item, $cart_item_key ) {

		// Make sure we don't add the dropdown to cart inventory items.
		if ( isset( $cart_item['inventory'] ) || ! self::is_selectable_enabled( $cart_item['data'] ) ) {
			return;
		}

		$args = apply_filters( 'atum/multi_inventory/selectable_inventories_cart_dropdown/args', array(
			'product'     => $cart_item['data'],
			'inventories' => Helpers::get_product_inventories_sorted( $cart_item['data']->get_id() ),
			'title'       => '',
			'no_option'   => __( 'Add Location', ATUM_MULTINV_TEXT_DOMAIN ),
			'excluded'    => ! empty( $cart_item['atum']['selected_mi'] ) ? array_keys( $cart_item['atum']['selected_mi'] ) : [],
			'input_name'  => "atum[select-mi][$cart_item_key]",
		) );

		AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/selectable-inventories/dropdown', $args );

	}

	/**
	 * Enqueue the styles and scripts needed on the frontend.
	 *
	 * @since 1.3.7
	 */
	public function enqueue_frontend_scripts() {

		if ( is_singular( 'product' ) || is_cart() ) {

			$is_selectable_enabled = FALSE;

			if ( is_cart() ) {

				$cart_contents = WC()->cart->cart_contents;

				foreach ( $cart_contents as $cart_item ) {
					if ( ! empty( $cart_item['atum']['selected_mi'] ) ) {
						$is_selectable_enabled = TRUE;
						break;
					}
				}

			}
			else {
				$product               = wc_get_product( get_the_ID() );
				$is_selectable_enabled = self::is_selectable_enabled( $product );
			}

			if ( $is_selectable_enabled ) {
				wp_register_style( 'atum-mi-selectable', ATUM_MULTINV_URL . 'assets/css/atum-mi-selectable.css', [], ATUM_MULTINV_VERSION );
				wp_enqueue_style( 'atum-mi-selectable' );

				wp_register_script( 'atum-mi-selectable', ATUM_MULTINV_URL . 'assets/js/build/atum-mi-selectable.js', [ 'jquery' ], ATUM_MULTINV_VERSION, TRUE );
				wp_enqueue_script( 'atum-mi-selectable' );
			}

		}

	}

	/**
	 * Check whether the specified product has the "selectable inventories" enabled.
	 *
	 * @since 1.3.7
	 *
	 * @param \WC_Product $product
	 *
	 * @return bool
	 */
	public static function is_selectable_enabled( $product ) {

		if ( ! $product instanceof \WC_Product ) {
			return FALSE;
		}

		$cache_key     = AtumCache::get_cache_key( 'is_selectable_enabled', [ $product->get_id() ] );
		$is_selectable = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $is_selectable;
		}

		if ( $product->is_type( 'variable' ) ) {

			$variations = $product->get_children();

			foreach ( $variations as $variation_id ) {

				$variation = wc_get_product( $variation_id );

				$is_selectable = self::is_selectable_enabled( $variation );

				if ( $is_selectable ) {
					break;
				}

			}

		}
		elseif (
			'yes' === Helpers::get_product_selectable_inventories( $product ) &&
			'yes' === Helpers::get_product_multi_inventory_status( $product ) &&
			Helpers::is_product_multi_inventory_compatible( $product )
		) {
			$is_selectable = TRUE;
		}

		AtumCache::set_cache( $cache_key, $is_selectable, ATUM_MULTINV_TEXT_DOMAIN );

		return $is_selectable;

	}

	/**
	 * Restrict the inventories used according to the selected inventories
	 *
	 * @since 1.3.7
	 *
	 * @param int         $product_id
	 * @param Inventory[] $inventories
	 *
	 * @return Inventory[]
	 */
	public static function maybe_restrict_inventories_in_cart( $product_id, $inventories ) {

		// Check for selectable inventories.
		$cart_contents = WC()->cart->cart_contents;
		$cart_item     = wp_list_filter( $cart_contents, [ 'product_id' => $product_id ] );

		if ( ! empty( $cart_item ) ) {

			$cart_item_key = key( $cart_item );

			if ( ! empty( $cart_item[ $cart_item_key ]['atum']['selected_mi'] ) ) {

				// Restrict the inventories to be used here.
				foreach ( $inventories as $index => $inventory ) {
					if ( ! array_key_exists( $inventory->id, $cart_item[ $cart_item_key ]['atum']['selected_mi'] ) ) {
						unset( $inventories[ $index ] );
					}
				}

			}

		}

		return $inventories;

	}

	/**
	 * Handle selected MI updates within cart
	 *
	 * @since 1.3.7
	 *
	 * @param bool $cart_updated
	 *
	 * @return bool
	 */
	public function update_cart_selected_inventories( $cart_updated ) {

		$cart_contents = WC()->cart->cart_contents;

		// Check whether the inventrory quantities are being updated.
		if ( ! empty( $_POST['atum_cart'] ) && is_array( $_POST['atum_cart'] ) ) {

			foreach ( $_POST['atum_cart'] as $cart_item_key => $inventories_data ) {

				// Check whether the cart item still exists in the cart.
				if ( ! isset( $cart_contents[ $cart_item_key ] ) || empty( $inventories_data ) ) {
					continue;
				}

				foreach ( $inventories_data as $inventory_id => $quantity ) {

					// Setting the quantity as 0 it's the same as removing the inventory item.
					if ( 0 === absint( $quantity ) && isset( $cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $inventory_id ] ) ) {
						unset( WC()->cart->cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $inventory_id ] );
						continue;
					}

					$old_quantity = (float) WC()->cart->cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $inventory_id ];
					$new_quantity = (float) $quantity;

					if ( $old_quantity !== $new_quantity ) {
						WC()->cart->cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $inventory_id ] = $new_quantity;
						$cart_updated = TRUE;
					}

				}

			}

		}

		// Check whether a new inventory is being added to the cart.
		if ( ! empty( $_POST['atum']['select-mi'] ) && is_array( $_POST['atum']['select-mi'] ) ) {

			foreach ( $_POST['atum']['select-mi'] as $cart_item_key => $inventory_id ) {

				// Bypass the empty values (dropdpown's none option).
				if ( empty( $inventory_id ) ) {
					continue;
				}

				// Check if an item with the provided key really exists in the cart.
				if ( ! isset( $cart_contents[ $cart_item_key ] ) ) {
					continue;
				}

				$inventory = Helpers::get_inventory( absint( $inventory_id ) );

				// Check whether is a valid inventory.
				if ( ! $inventory->id ) {
					continue;
				}

				// Check whether this inventory was already selected.
				if ( ! empty( $cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $inventory->id ] ) ) {
					continue;
				}

				// When adding a new inventory to a cart item that has no selected inventories, we should use the item quantity.
				if ( empty( $cart_contents[ $cart_item_key ]['atum']['selected_mi'] ) ) {
					WC()->cart->cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $inventory->id ] = $cart_contents[ $cart_item_key ]['quantity'];
				}
				// Add just 1 item when adding a new inventory.
				else {
					WC()->cart->cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $inventory->id ] = 1;

					// Set the cart item quantity for the product.
					WC()->cart->cart_contents[ $cart_item_key ]['quantity'] ++;
				}

				$cart_updated = TRUE;

			}

		}

		return $cart_updated;

	}

	/**
	 * Check whether the user is trying to remove an inventory item from the cart
	 *
	 * @since 1.3.7
	 */
	public function maybe_remove_inventory_cart_item() {

		if ( ! empty( $_REQUEST['remove_item'] ) ) {

			$remove_item_key = esc_attr( $_REQUEST['remove_item'] );
			$cart_contents   = WC()->cart->cart_contents;

			foreach ( $cart_contents as $cart_item_key => $cart_item ) {

				if ( ! empty( $cart_item['atum']['selected_mi'] ) && is_array( $cart_item['atum']['selected_mi'] ) ) {

					foreach ( $cart_item['atum']['selected_mi'] as $inventory_id => $quantity ) {

						$inventory_item_hash = $this->get_selected_mi_hash( $inventory_id );

						if ( $remove_item_key === $inventory_item_hash ) {

							do_action( 'woocommerce_remove_cart_item', $cart_item_key, WC()->cart );

							unset( WC()->cart->cart_contents[ $cart_item_key ]['atum']['selected_mi'][ $inventory_id ] );
							WC()->cart->cart_contents[ $cart_item_key ]['quantity'] -= $quantity;

							do_action( 'woocommerce_cart_item_removed', $cart_item_key, WC()->cart );

							$inventory = Helpers::get_inventory( $inventory_id );

							/* translators: %s: Item name. */
							$item_removed_title = apply_filters( 'atum/multi_inventory/cart_inventory_item_removed_title', sprintf( _x( '&ldquo;%s&rdquo;', 'Inventory name in quotes', ATUM_MULTINV_TEXT_DOMAIN ), $inventory->name ) );

							// Don't show undo link if removed item is out of stock.
							if ( 'outofstock' !== $inventory->stock_status && $inventory->get_available_stock() >= $quantity ) {
								/* Translators: %s Inventory name. */
								$removed_notice  = sprintf( __( '%s removed.', ATUM_MULTINV_TEXT_DOMAIN ), $item_removed_title );
								$removed_notice .= ' <a href="' . esc_url( wc_get_cart_undo_url( $cart_item_key ) ) . '" class="restore-item">' . __( 'Undo?', ATUM_MULTINV_TEXT_DOMAIN ) . '</a>';
							}
							else {
								/* Translators: %s Inventory name. */
								$removed_notice = sprintf( __( '%s removed.', ATUM_MULTINV_TEXT_DOMAIN ), $item_removed_title );
							}

							wc_add_notice( $removed_notice, apply_filters( 'atum/multi_inventory/cart_inventory_item_removed_notice_type', 'success' ) );

							$referer = wp_get_referer() ? remove_query_arg( [ 'remove_item', 'add-to-cart', 'added-to-cart', 'order_again', '_wpnonce' ], add_query_arg( 'removed_item', '1', wp_get_referer() ) ) : wc_get_cart_url();
							wp_safe_redirect( $referer );
							exit;

						}

					}

				}

			}

		}

	}


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 *
	 * @since 1.3.7
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 *
	 * @since 1.3.7
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @since 1.3.7
	 *
	 * @return SelectableInventories instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
