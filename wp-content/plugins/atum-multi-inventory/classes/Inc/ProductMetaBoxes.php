<?php
/**
 * Class for handling Product Meta Boxes' hooks
 *
 * @since       1.4.2
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     AtumMultiInventory\Inc
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Inc\Hooks as AtumHooks;
use AtumMultiInventory\Models\Inventory;
use AtumMultiInventory\Models\MainInventory;


final class ProductMetaBoxes {

	/**
	 * The singleton instance holder
	 *
	 * @var ProductMetaBoxes
	 */
	private static $instance;

	/**
	 * ProductMetaBoxes singleton constructor
	 *
	 * @since 1.4.2
	 */
	private function __construct() {

		if ( is_admin() ) {

			$this->register_product_meta_boxes_hooks();

			// Add extra fields to ATUM's product data tab.
			add_action( 'atum/after_product_data_panel', array( $this, 'add_product_data_tab_fields' ), 12 );
			add_action( 'atum/after_variation_product_data_panel', array( $this, 'add_product_data_tab_fields' ), 9, 3 );

			// Build the Multi-Inventory management UI.
			add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'add_multi_inventory_ui' ) );
			add_action( 'atum/after_variation_product_data_panel', array( $this, 'add_multi_inventory_ui' ), 9, 3 );

		}

	}

	/**
	 * Register product meta boxes hooks
	 *
	 * @since 1.0.1
	 */
	public function register_product_meta_boxes_hooks() {

		// Save the Multi-Inventory meta boxes once ATUM has processed all its own meta boxes.
		add_action( 'atum/product_data/after_save_product_meta_boxes', array( $this, 'save_meta_boxes' ), 10 );
		add_action( 'atum/product_data/after_save_product_variation_meta_boxes', array( $this, 'save_meta_boxes' ), 10, 2 );
	}

	/**
	 * Add a field to WC product data meta box for setting the Multi-Inventory at product level
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $loop           The current item in the loop of variations.
	 * @param array                $variation_data The current variation data.
	 * @param \WP_Post|\WC_Product $variation      The variation post.
	 */
	public function add_product_data_tab_fields( $loop = NULL, $variation_data = array(), $variation = NULL ) {

		global $post, $thepostid;

		$is_variation = $variation && ( $variation instanceof \WP_Post || $variation instanceof \WC_Product );
		$thepostid    = $is_variation ? $variation->get_id() : $post->ID;
		$product      = AtumHelpers::get_atum_product( $thepostid );

		$view_args = array(
			'is_variation'                => $is_variation,
			'loop'                        => $loop,
			'multi_inventory'             => Helpers::get_product_multi_inventory_status( $product, TRUE ),
			'inventory_iteration'         => Helpers::get_product_inventory_iteration( $product, TRUE ),
			'inventory_sorting_mode'      => Helpers::get_product_inventory_sorting_mode( $product, TRUE ),
			'expirable_inventories'       => Helpers::get_product_expirable_inventories( $product, TRUE ),
			'price_per_inventory'         => Helpers::get_product_price_per_inventory( $product, TRUE ),
			'selectable_inventories'      => Helpers::get_product_selectable_inventories( $product, TRUE ),
			'selectable_inventories_mode' => Helpers::get_product_selectable_inventories_mode( $product, TRUE ),
			'field_visibility'            => apply_filters( 'atum/multi_inventory/atum_tab_fields_visibility', 'show_if_simple' ),
			'product_type'                => $product->get_type(),
		);

		AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/meta-boxes/product-data/atum-tab-fields', $view_args );
	}

	/**
	 * Adds the Multi-Inventory UI within WC's product data meta box
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $loop           For variations only. The loop item number.
	 * @param array                $variation_data For variations only. The variation item data.
	 * @param \WP_Post|\WC_Product $variation      For variations only. The variation product. Must be original translation.
	 *
	 * @throws \Exception
	 */
	public function add_multi_inventory_ui( $loop = NULL, $variation_data = array(), $variation = NULL ) {

		global $post;

		$is_variation = $variation && ( $variation instanceof \WP_Post || $variation instanceof \WC_Product );
		$product_id   = ! $is_variation ? $post->ID : $variation->get_id();

		// If WPML is enabled, it must be the original translation.
		if ( apply_filters( 'atum/multi_inventory/can_add_mi_ui', TRUE, $product_id ) ) {

			$inventories             = Inventory::get_product_inventories( $product_id );
			$region_restriction_mode = AtumHelpers::get_option( 'mi_region_restriction_mode', 'no-restriction' );

			// WC product bundles compatibility.
			if ( class_exists( '\WC_Product_Bundle' ) ) {
				Helpers::bundled_product_stock_changed( wc_get_product( $post ) );
			}

			// Visible in the same cases thant ATUM tab.
			$field_visibility = apply_filters( 'atum/multi_inventory/atum_mi_ui_visibility', 'show_if_simple' );

			$regions   = Helpers::get_regions( $region_restriction_mode );
			$locations = get_terms(
				array(
					'taxonomy'   => Globals::PRODUCT_LOCATION_TAXONOMY,
					'hide_empty' => FALSE,
				)
			);

			$main_inventory = Inventory::get_product_main_inventory( $product_id );

			// If it was already saved, set the stock status.
			if ( $main_inventory instanceof MainInventory && 'main' !== $main_inventory->id ) {
				$main_inventory->set_stock_status();
			}

			AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/meta-boxes/product-data/mi-panel', compact( 'main_inventory', 'region_restriction_mode', 'inventories', 'regions', 'locations', 'loop', 'is_variation', 'field_visibility' ) );

		}

	}

	/**
	 * Saves the Multi-Inventory meta boxes
	 *
	 * @since 1.0.0
	 *
	 * @param int $product_id The product ID. If WPML is installed, must be original translation.
	 * @param int $loop       Optional. Only needed if a variation is being saved. If WPML is installed, must be original translation.
	 */
	public function save_meta_boxes( $product_id, $loop = NULL ) {

		$product = AtumHelpers::get_atum_product( $product_id );

		// Multi-Inventory options at product level.
		foreach (
			[
				'multi_inventory',
				'inventory_sorting_mode',
				'inventory_iteration',
				'expirable_inventories',
				'price_per_inventory',
				'selectable_inventories',
				'selectable_inventories_mode',
			] as $prop
		) {

			if ( NULL === $loop ) {
				$value = isset( $_POST[ $prop ] ) ? wc_clean( $_POST[ $prop ] ) : '';
			}
			else {
				$value = isset( $_POST['variation_atum_tab'][ $prop ], $_POST['variation_atum_tab'][ $prop ][ $loop ] ) ? wc_clean( $_POST['variation_atum_tab'][ $prop ][ $loop ] ) : '';
			}

			if ( is_callable( array( $product, "set_$prop" ) ) ) {
				call_user_func( array( $product, "set_$prop" ), $value );
			}

		}

		$product->save_atum_data();

		if ( Helpers::is_product_multi_inventory_compatible( $product, FALSE, TRUE ) && apply_filters( 'atum/multi_inventory/can_save_mi_metabox_data', TRUE, $product_id ) ) {

			// Save the multi-inventories for the current product (if enabled).
			if (
				'yes' === Helpers::get_product_multi_inventory_status( $product_id ) &&
				! empty( $_POST['atum_mi'] ) && is_array( $_POST['atum_mi'] ) &&
				( NULL === $loop || ( NULL !== $loop && ! empty( $_POST['atum_mi'][ $loop ] ) && is_array( $_POST['atum_mi'][ $loop ] ) ) )
			) {

				remove_action( 'woocommerce_after_product_object_save', array( AtumHooks::get_instance(), 'defer_update_atum_product_calc_props' ), PHP_INT_MAX );

				// For variations, get the right loop item.
				$inventories = NULL === $loop ? $_POST['atum_mi'] : $_POST['atum_mi'][ $loop ];
				$on_sale     = 0;

				// Save the custom inventories.
				foreach ( $inventories as $inventory_id => &$inventory_meta ) {

					// Separate the inventory data from meta (the sanitization is handled by the model).
					$inventory_data = array(
						'product_id'     => $product_id,
						'name'           => ! empty( $inventory_meta['_inventory_name'] ) ? $inventory_meta['_inventory_name'] : '',
						'region'         => ! empty( $inventory_meta['_region'] ) ? explode( ',', $inventory_meta['_region'] ) : NULL,
						'location'       => ! empty( $inventory_meta['_location'] ) ? explode( ',', $inventory_meta['_location'] ) : NULL,
						'bbe_date'       => ! empty( $inventory_meta['_bbe_date'] ) ? $inventory_meta['_bbe_date'] : NULL,
						'expiry_days'    => ! empty( $inventory_meta['_expiry_days'] ) ? $inventory_meta['_expiry_days'] : 0,
						'priority'       => ! empty( $inventory_meta['_priority'] ) ? $inventory_meta['_priority'] : 0,
						'inventory_date' => ! empty( $inventory_meta['_inventory_date'] ) ? $inventory_meta['_inventory_date'] : NULL,
						'is_main'        => ! empty( $inventory_meta['_is_main'] ) ? 'yes' : 'no',
						'lot'            => ! empty( $inventory_meta['_lot'] ) ? $inventory_meta['_lot'] : '',
					);

					$is_main   = ( 'yes' === $inventory_data['is_main'] || FALSE !== strpos( $inventory_id, 'main' ) );
					$inv_class = $is_main ? '\AtumMultiInventory\Models\MainInventory' : '\AtumMultiInventory\Models\Inventory';

					// The new inventories will come with the "new_" prefix.
					$inventory = 0 !== strpos( $inventory_id, 'new_' ) ? new $inv_class( absint( $inventory_id ) ) : new $inv_class();

					/**
					 * Used variables
					 *
					 * @var Inventory $inventory
					 */
					$inventory->set_data( $inventory_data );

					// Save the data.
					$inventory->save();

					// The main inventory meta is already being saved by WC in simple products.
					if ( FALSE === in_array( $product->get_type(), apply_filters( 'atum/get_simple_product_types', [ 'simple' ] ) ) ||
					     ! $is_main || Helpers::has_multi_price( $product ) ) {

						// Ensure manage stock is set.
						$inventory_meta['_manage_stock'] = isset( $inventory_meta['_manage_stock'] ) ? $inventory_meta['_manage_stock'] : 'no';

						$inventory->set_meta( $inventory_meta );
						$inventory->save_meta();

					}

					// Check the onsale status.
					if ( ! $on_sale && $inventory->sale_price && $inventory->price === $inventory->sale_price ) {
						$on_sale = 1;
					}

				}

				// Update the onsale prop on the WC product meta lookup table.
				// NOTE: A product will be on sale if at least one of its inventories is on sale.
				global $wpdb;
				if ( isset( $wpdb->wc_product_meta_lookup ) ) {

					$is_product_on_sale = $product->is_on_sale();

					if ( wc_string_to_bool( $on_sale ) !== $is_product_on_sale ) {

						// Delete the WC transient so the sale products are recalculated again.
						delete_transient( 'wc_products_onsale' );

						$wpdb->update(
							$wpdb->wc_product_meta_lookup,
							[ 'onsale' => $on_sale ],
							[ 'product_id' => $product_id ],
							'%d',
							'%d'
						);

					}

				}

				// WC Subscriptions compatibility. Update supscription_price if MI is enabled.
				if ( class_exists( '\WC_Subscriptions' ) ) {

					$product_type = $product->get_type();

					$variable_product = 'variation' === $product_type ? AtumHelpers::get_atum_product( $product->get_parent_id() ) : NULL;

					if ( 'subscription' === $product_type || ( $variable_product instanceof \WC_Product && 'variable-subscription' === $variable_product->get_type() ) ) {
						update_post_meta( $product_id, '_regular_price', $_POST['variable_subscription_price'][ $loop ] );
					}

				}

				add_action( 'woocommerce_after_product_object_save', array( AtumHooks::get_instance(), 'defer_update_atum_product_calc_props' ), PHP_INT_MAX, 2 );
				AtumHelpers::update_atum_product_calc_props( $product );

			}
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
	 * @return ProductMetaBoxes instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
