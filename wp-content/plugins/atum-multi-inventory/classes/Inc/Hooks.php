<?php
/**
 * Multi-Inventory general hooks
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

use Atum\Components\AtumCache;
use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Models\Products\AtumProductTrait;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Suppliers;
use AtumMultiInventory\Legacy\HooksLegacyTrait;
use AtumMultiInventory\Models\Inventory;
use AtumMultiInventory\MultiInventory;


final class Hooks {

	/**
	 * The singleton instance holder
	 *
	 * @var Hooks
	 */
	private static $instance;

	/**
	 * Tax query params to allow modify the tax query.
	 *
	 * @var array
	 */
	private $product_tax_query;

	/**
	 * Stock status to be filtered.
	 *
	 * @var string
	 */
	private $stock_status_queried;

	/**
	 * Counters for the Current Stock Value widget
	 *
	 * @var array
	 */
	private $current_stock_value_counters = array(
		'items_stocks_counter'         => 0,
		'items_purchase_price_total'   => 0,
		'items_without_purchase_price' => 0,
	);


	/**
	 * Hooks singleton constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		if ( is_admin() ) {
			$this->register_admin_hooks();
		}

		$this->register_global_hooks();

	}

	/**
	 * Register the admin-side hooks
	 *
	 * @since 1.0.0
	 */
	public function register_admin_hooks() {

		// Add MI suppliers assigned to non-main inventories to supplier's products list.
		add_filter( 'atum/suppliers/products', array( $this, 'add_mi_supplier_products' ), 10, 5 );

		// Check for expired inventories before loading a product in the admin side.
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'maybe_expired_inventories' ) );
		add_action( 'woocommerce_variation_header', array( $this, 'maybe_expired_inventories' ) );

		// Enqueue_scripts (the priority is important here).
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 1 );

		// Add MI column to WC's product list.
		add_filter( 'manage_edit-product_columns', array( $this, 'add_mi_column' ) );

		// Add MI stuff to product's columns content.
		add_action( 'manage_product_posts_custom_column', array( $this, 'customize_column_content' ), 10, 2 );
		add_action( 'manage_product_posts_custom_column', array( $this, 'remove_filters_column_content' ), 11, 2 );

		// Allow WC's products list to query MI stock fields.
		add_action( 'current_screen', array( $this, 'force_mi_get_stock_props' ) );

		// Allow right MI stock statuses to show in Orders' product searches.
		add_action( 'wp_ajax_woocommerce_json_search_products_and_variations', array( $this, 'force_mi_get_stock_props' ), 1 );

		// Delete product inventories after product removal.
		add_action( 'atum/after_delete_atum_product_data', array( $this, 'remove_inventories_after_product_removal' ) );

		// Remove shipping zones linked into inventories when WC shipping zone is removed.
		add_action( 'woocommerce_delete_shipping_zone', array( $this, 'remove_deleted_shipping_zones' ) );

		// When searching products from orders' "Add Product(s)" popup, mark the MI products.
		add_filter( 'woocommerce_json_search_found_products', array( $this, 'mark_json_search_products' ) );

		// Update data coming from SC.
		add_filter( 'atum/ajax/before_update_product_meta', array( $this, 'update_inventory_data' ) );

		// Current stock values within current stock values widget.
		add_filter( 'atum/dashboard/get_items_in_stock/allowed_product', array( $this, 'get_current_stock_values' ), 10, 2 );
		add_filter( 'atum/dashboard/get_items_in_stock/counters', array( $this, 'sum_mi_current_stock_value_counters' ) );
		add_action( 'atum/dashboard/current_stock_value_widget/after_filters', array( $this, 'add_current_stock_value_filter' ) );

		// Rebuild the stock status for Inventories when the "Out of Stock Threshold" option is changed.
		add_action( 'atum/out_stock_threshold/after_rebuild', array( $this, 'rebuild_mi_out_stock_threshold' ) );

		// Remove inventories if needed when changing product type.
		add_action( 'woocommerce_product_type_changed', array( $this, 'product_type_changed' ), 1, 3 );

		// Duplicate all the MI configuration when duplicating a product.
		add_action( 'atum/after_duplicate_product', array( $this, 'duplicate_product_mi' ), 10, 2 );

		// Update the manage stock for all the inventories at once.
		add_action( 'atum/after_change_status_meta', array( $this, 'change_inventories_manage_stock' ), 10, 2 );

		// Prevent default stock status query from being applied and apply our own query (if needed).
		add_filter( 'request', array( $this, 'maybe_change_product_stock_status_query' ), 9 );

	}

	/**
	 * Register the global hooks
	 *
	 * @since 1.0.0
	 */
	public function register_global_hooks() {

		// Hack the quantity input args for the products with MI enabled.
		add_filter( 'woocommerce_quantity_input_args', array( $this, 'quantity_input_args' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_product_backorders_allowed', array( $this, 'product_inventories_backorders_allowed' ), 10, 3 );
		add_filter( 'woocommerce_product_backorders_require_notification', array( $this, 'product_inventories_backorders_require_notification' ), 10, 2 );

		// Check for expired inventories on a CRON job.
		add_filter( 'atum/queues/recurring_hooks', array( $this, 'add_expired_inventories_check_cron' ) );

		// Use our own data to set the customer's default location.
		add_filter( 'default_checkout_billing_country', array( $this, 'default_checkout_values' ), 10, 2 );
		add_filter( 'default_checkout_shipping_country', array( $this, 'default_checkout_values' ), 10, 2 );
		add_filter( 'default_checkout_billing_state', array( $this, 'default_checkout_values' ), 10, 2 );
		add_filter( 'default_checkout_shipping_state', array( $this, 'default_checkout_values' ), 10, 2 );
		add_filter( 'default_checkout_billing_postcode', array( $this, 'default_checkout_values' ), 10, 2 );
		add_filter( 'default_checkout_shipping_postcode', array( $this, 'default_checkout_values' ), 10, 2 );

		// Hack the stock notifications email messages.
		add_filter( 'woocommerce_email_content_no_stock', array( $this, 'email_content_inventory_no_stock' ), 10, 2 );
		add_filter( 'woocommerce_email_content_low_stock', array( $this, 'email_content_inventory_low_stock' ), 10, 2 );
		add_filter( 'woocommerce_email_content_backorder', array( $this, 'email_content_inventory_backorder' ), 10, 2 );

		// Hack the bundled items with MI enabled before getting their data.
		if ( class_exists( '\WC_Product_Bundle' ) ) {
			add_filter( 'woocommerce_bundled_items', array( $this, 'get_bundled_items' ), 10, 2 );
		}

		if ( ! is_admin() && 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			// Products loop tax query filter.
			add_filter( 'woocommerce_product_query_tax_query', array( $this, 'modify_product_tax_query' ), 10, 2 );
		}

		// Disable the force rebuild made on ATUM core for the products with MI.
		add_filter( 'atum/force_rebuild_stock_status_allowed', array( $this, 'maybe_force_rebuild_stock_status' ), 10, 2 );

		// Check whether we should add the BBE dates in cart.
		if ( 'yes' === AtumHelpers::get_option( 'mi_expiry_dates_in_cart', 'no' ) ) {
			add_action( 'woocommerce_after_cart_item_name', array( $this, 'add_expiry_dates_in_cart' ), 10, 2 );
		}

		// Show all the MI's SKUs on the product pages.
		add_action( 'woocommerce_product_meta_start', array( $this, 'maybe_show_mi_skus' ) );

		if ( 'no-restriction' !== Helpers::get_region_restriction_mode() ) {
			add_action( 'init', array( $this, 'maybe_update_location_cookie' ) );
		}

		if ( 'yes' === AtumHelpers::get_option( 'chg_stock_order_complete' ) ) {
			add_filter( 'atum/order_statuses_allow_change_stock', array( $this, 'modify_changing_stock_order_statuses' ) );
		}

	}

	/**
	 * Get the purchase price for MultiInventory if the product has the multi price enabled
	 *
	 * @since 1.0.1
	 *
	 * @param float                        $price
	 * @param bool                         $has_multi_price
	 * @param Inventory                    $inventory
	 * @param \WC_Product|AtumProductTrait $product
	 *
	 * @return float
	 */
	public function get_mi_purchase_price( $price, $has_multi_price, $inventory, $product ) {

		$price = $has_multi_price ? $inventory->purchase_price : $product->get_purchase_price();

		return wc_prices_include_tax() ? wc_get_price_excluding_tax( $product, [ 'price' => $price ] ) : $price;
	}

	/**
	 * If the site is not using the new tables, use the legacy methods
	 *
	 * @since 1.0.7
	 * @deprecated Only for backwards compatibility and will be removed in a future version.
	 */
	use HooksLegacyTrait;

	/**
	 * Add MI suppliers assigned to non-main inventories to supplier's products list
	 *
	 * @since 1.0.1
	 *
	 * @param array        $products
	 * @param \WP_Post     $supplier
	 * @param array|string $post_type
	 * @param bool         $type_filter
	 * @param array        $extra_filters
	 *
	 * @return array
	 */
	public function add_mi_supplier_products( $products, $supplier, $post_type, $type_filter, $extra_filters ) {

		/**
		 * If the site is not using the new tables, use the legacy method
		 *
		 * @since 1.0.7
		 * @deprecated Only for backwards compatibility and will be removed in a future version.
		 */
		if ( ! AtumHelpers::is_using_new_wc_tables() ) {
			return $this->add_mi_supplier_products_legacy( $products, $supplier, $post_type, $type_filter, $extra_filters );
		}

		global $wpdb;

		$supplier_products = $supplier_variations = $supplier_variables = array();

		$mi_meta_col        = 'apd.multi_inventory';
		$meta_where         = 'yes' === AtumHelpers::get_option( 'mi_default_multi_inventory', 'no' ) ? "$mi_meta_col = 1 OR $mi_meta_col IS NULL" : "$mi_meta_col = 1";
		$atum_product_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

		// Get all product IDs with the supplier assigned in an secondary inventory.
		$supplier_select = "
			SELECT DISTINCT wpd.product_id, p.post_parent
			FROM {$wpdb->prefix}wc_products wpd
			INNER JOIN $atum_product_table apd ON wpd.product_id = apd.product_id
			INNER JOIN $wpdb->prefix" . Inventory::INVENTORY_META_TABLE . " im ON wpd.id = im.inventory_id
			INNER JOIN $wpdb->posts p ON wpd.product_id = p.ID
		";

		// phpcs:disable WordPress.DB.PreparedSQL
		$supplier_where = $wpdb->prepare( "
			WHERE $meta_where
			AND im.supplier = %d
		", $supplier->ID );
		// phpcs:enable

		$term_join_products = $term_join_variations = $term_where = '';

		// Product type filter.
		$product_types = (array) apply_filters( 'atum/suppliers/supplier_product_types', Globals::get_product_types() );

		// Check the product type if needed.
		$is_filtering_product_type = FALSE;

		if ( ! empty( $extra_filters['tax_query'] ) ) {
			$is_filtering_product_type = ! empty( wp_list_filter( $extra_filters['tax_query'], [ 'taxonomy' => 'product_type' ] ) );
		}

		if ( $type_filter && ! $is_filtering_product_type ) {
			$term_where = " AND wpd.type IN ('" . implode( "','", $product_types ) . "')";
		}

		// Add any extra filter (product category for example).
		if ( ! empty( $extra_filters['tax_query'] ) && is_array( $extra_filters['tax_query'] ) ) {

			foreach ( $extra_filters['tax_query'] as $index => $tax_query ) {
				$term_ids              = AtumHelpers::get_term_ids_by_slug( (array) $tax_query['terms'], $tax_query['taxonomy'] );
				$term_join_products   .= " LEFT JOIN $wpdb->term_relationships tr$index ON (p.ID = tr$index.object_id) ";
				$term_join_variations .= " LEFT JOIN $wpdb->term_relationships tr$index ON (p.ID = tr$index.object_id) ";
				$term_where           .= " AND tr$index.term_taxonomy_id IN (" . implode( ',', $term_ids ) . ') ';
			}

		}

		if ( ( is_array( $post_type ) && in_array( 'product', $post_type, TRUE ) ) || 'product' === $post_type ) {
			$supplier_products = $wpdb->get_results( $supplier_select . $term_join_products . $supplier_where . $term_where . " AND p.post_type = 'product'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$supplier_products = ! empty( $supplier_products ) ? wp_list_pluck( $supplier_products, 'product_id' ) : [];
		}

		if ( ( is_array( $post_type ) && in_array( 'product_variation', $post_type, TRUE ) ) || 'product_variation' === $post_type ) {

			$supplier_variations = $wpdb->get_results( $supplier_select . $supplier_where . " AND p.post_type = 'product_variation'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( ! empty( $supplier_variations ) ) {

				// Get all the variations that have a parent variable of the specified type filter.
				if ( $type_filter ) {

					$term_where .= " AND p.post_parent IN (
						SELECT product_id FROM {$wpdb->prefix}wc_products pr WHERE pr.type IN ('" . implode( "','", $product_types ) . "') 
					)";

				}

				// phpcs:disable WordPress.DB.PreparedSQL
				$supplier_variables = $wpdb->get_col( "
					SELECT DISTINCT p.post_parent
					FROM $wpdb->posts p
					$term_join_variations
					WHERE p.ID IN (" . implode( ',', wp_list_pluck( $supplier_variations, 'product_id' ) ) . ")
					$term_where
				" );
				// phpcs:enable

				// Exclude all the variations belonging to not returned variables.
				array_filter( $supplier_variations, function ( $item ) use ( $supplier_variables ) {
					return in_array( $item->post_parent, $supplier_variables ) ? $item : FALSE;
				} );

			}

		}

		$products = array_unique( array_merge( $products, $supplier_products, $supplier_variables, $supplier_variations ) );

		return $products;

	}

	/**
	 * Customise the ATUM's supplier fields for the distinct inventories
	 *
	 * @since 1.0.0
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function supplier_fields_view_args( $args ) {

		global $atum_mi_inventory;

		if ( ! empty( $atum_mi_inventory ) ) {

			$id_for_name = ( ! is_numeric( $atum_mi_inventory->id ) && strpos( $atum_mi_inventory->id, 'new_' ) !== 0 && ( 'main' !== $atum_mi_inventory->id ) ) ? "new_{$atum_mi_inventory->id}" : $atum_mi_inventory->id;

			$args['supplier_field_name']     = empty( $args['variation'] ) ? "atum_mi[$atum_mi_inventory->id][supplier_id]" : "atum_mi[{$args['loop']}][$id_for_name][supplier_id]";
			$args['supplier_field_id']       = empty( $args['variation'] ) ? "_supplier_id_{$atum_mi_inventory->id}" : "_supplier_id{$args['loop']}_{$id_for_name}";
			$args['supplier_sku_field_name'] = empty( $args['variation'] ) ? "atum_mi[$atum_mi_inventory->id][supplier_sku]" : "atum_mi[{$args['loop']}][$id_for_name][supplier_sku]";
			$args['supplier_sku_field_id']   = empty( $args['variation'] ) ? "_supplier_sku_{$atum_mi_inventory->id}" : "_supplier_sku{$args['loop']}_{$id_for_name}";

			$supplier_id          = $atum_mi_inventory->supplier_id;
			$args['supplier_sku'] = $atum_mi_inventory->supplier_sku;
			$args['supplier']     = $supplier_id ? get_post( $supplier_id ) : NULL;

		}

		return $args;

	}

	/**
	 * Add extra atts to the Supplier field
	 *
	 * @since 1.0.0
	 *
	 * @param string   $extra_atts
	 * @param \WP_Post $variation
	 * @param int      $loop
	 *
	 * @return string
	 */
	public function supplier_field_extra_atts( $extra_atts, $variation, $loop ) {

		$id = ! empty( $variation ) ? Suppliers::SUPPLIER_META_KEY . $loop : Suppliers::SUPPLIER_META_KEY;
		return $extra_atts . ' data-sync="#' . $id . '"';
	}

	/**
	 * Add extra atts to the Supplier SKU field
	 *
	 * @since 1.0.0
	 *
	 * @param string   $extra_atts
	 * @param \WP_Post $variation
	 * @param int      $loop
	 *
	 * @return string
	 */
	public function supplier_sku_field_extra_atts( $extra_atts, $variation, $loop ) {

		$id = ! empty( $variation ) ? Suppliers::SUPPLIER_SKU_META_KEY . $loop : Suppliers::SUPPLIER_SKU_META_KEY;
		return $extra_atts . ' data-sync="#' . $id . '"';
	}

	/**
	 * Customise the ATUM's "Out of Stock Threshold" fields for the distinct inventories
	 *
	 * @since 1.0.0
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function out_stock_threshold_field_view_args( $args ) {

		global $atum_mi_inventory;

		if ( ! empty( $atum_mi_inventory ) ) {

			$id_for_name = ( ! is_numeric( $atum_mi_inventory->id ) && strpos( $atum_mi_inventory->id, 'new_' ) !== 0 && ( 'main' !== $atum_mi_inventory->id ) ) ? "new_{$atum_mi_inventory->id}" : $atum_mi_inventory->id;

			$args['out_stock_threshold_field_name'] = empty( $args['variation'] ) ? "atum_mi[$atum_mi_inventory->id][out_stock_threshold]" : "atum_mi[{$args['loop']}][$id_for_name][out_stock_threshold]";
			$args['out_stock_threshold_field_id']   = empty( $args['variation'] ) ? "_out_stock_threshold_{$atum_mi_inventory->id}" : "_out_stock_threshold{$args['loop']}_{$id_for_name}";

			$args['out_stock_threshold'] = $atum_mi_inventory->out_stock_threshold;

		}

		return $args;

	}

	/**
	 * Add extra atts to the Out of Stock Threshold field
	 *
	 * @since 1.0.0
	 *
	 * @param string   $extra_atts
	 * @param \WP_Post $variation
	 * @param int      $loop
	 *
	 * @return string
	 */
	public function out_stock_threshold_field_extra_atts( $extra_atts, $variation, $loop ) {

		$id = ! empty( $variation ) ? Globals::OUT_STOCK_THRESHOLD_KEY . $loop : Globals::OUT_STOCK_THRESHOLD_KEY;
		return $extra_atts . ' data-sync="#' . $id . '"';
	}

	/**
	 * Customise the ATUM's purchase price field for the distinct inventories
	 *
	 * @since 1.0.1
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function purchase_price_field_view_args( $args ) {

		global $atum_mi_inventory;

		if ( ! empty( $atum_mi_inventory ) ) {

			$id_for_name = ( ! is_numeric( $atum_mi_inventory->id ) && strpos( $atum_mi_inventory->id, 'new_' ) !== 0 && ( 'main' !== $atum_mi_inventory->id ) ) ? "new_{$atum_mi_inventory->id}" : $atum_mi_inventory->id;

			$args['field_name']  = empty( $args['variation'] ) ? "atum_mi[$atum_mi_inventory->id][purchase_price]" : "atum_mi[{$args['loop']}][$id_for_name][purchase_price]";
			$args['field_id']    = empty( $args['variation'] ) ? "_purchase_price_{$atum_mi_inventory->id}" : "_purchase_price{$args['loop']}_{$id_for_name}";
			$args['field_value'] = $atum_mi_inventory->purchase_price;
			$args['price']       = $atum_mi_inventory->price;

		}

		return $args;

	}

	/**
	 * Add extra atts to the Purchase Price field
	 *
	 * @since 1.0.0
	 *
	 * @param string   $extra_atts
	 * @param \WP_Post $variation
	 * @param int      $loop
	 *
	 * @return string
	 */
	public function purchase_price_field_extra_atts( $extra_atts, $variation, $loop ) {

		$id = ! empty( $variation ) ? 'variation' . Globals::PURCHASE_PRICE_KEY . $loop : Globals::PURCHASE_PRICE_KEY;
		return $extra_atts . ' data-sync="#' . $id . '"';
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook
	 */
	public function enqueue_admin_scripts( $hook ) {

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// WooCommerce product edit page.
		if ( 'product' === $screen_id ) {

			// Only can edit original translation.
			$wc_product = wc_get_product( get_the_ID() );

			wp_register_style( 'atum-mi-products', ATUM_MULTINV_URL . 'assets/css/atum-mi-products.css', array(), ATUM_MULTINV_VERSION );
			wp_register_script( 'atum-mi-products', ATUM_MULTINV_URL . 'assets/js/build/atum-mi-product-data.js', array( 'jquery', 'sweetalert2', 'wp-hooks' ), ATUM_MULTINV_VERSION, TRUE );

			$vars = array(
				'add'                          => __( 'Add', ATUM_MULTINV_TEXT_DOMAIN ),
				'areYouSure'                   => __( 'Are you sure?', ATUM_MULTINV_TEXT_DOMAIN ),
				'cancel'                       => __( 'Cancel', ATUM_MULTINV_TEXT_DOMAIN ),
				'clearThem'                    => __( 'Yes, clear them!', ATUM_MULTINV_TEXT_DOMAIN ),
				'cloned'                       => __( 'cloned', ATUM_MULTINV_VERSION ),
				'collapseAll'                  => __( 'Collapse All', ATUM_MULTINV_TEXT_DOMAIN ),
				'compatibleParentTypes'        => MultiInventory::get_compatible_parent_types(),
				'compatibleTypes'              => MultiInventory::get_compatible_types(),
				'confirmClearing'              => __( 'All the fields from this inventory will be cleared', ATUM_MULTINV_TEXT_DOMAIN ),
				'confirmClearingMulti'         => __( 'All the fields from the selected inventories will be cleared', ATUM_MULTINV_TEXT_DOMAIN ),
				'defaultMultiInventory'        => AtumHelpers::get_option( 'mi_default_multi_inventory', 'no' ),
				'defaultSortingMode'           => AtumHelpers::get_option( 'mi_default_inventory_sorting_mode', 'fifo' ),
				'defaultPricePerInventory'     => AtumHelpers::get_option( 'mi_default_price_per_inventory', 'no' ),
				'defaultExpirableInventories'  => AtumHelpers::get_option( 'mi_default_expirable_inventories', 'no' ),
				'defaultSelectableInventories' => AtumHelpers::get_option( 'mi_default_selectable_inventories', 'no' ),
				'done'                         => __( 'Done', ATUM_MULTINV_TEXT_DOMAIN ),
				'error'                        => __( 'Error', ATUM_MULTINV_TEXT_DOMAIN ),
				'expandAll'                    => __( 'Expand All', ATUM_MULTINV_TEXT_DOMAIN ),
				'fieldsToHide'                 => (array) apply_filters( 'atum/multi_inventory/localized_vars/fields_to_hide', array(
					'._sku_field',
					'._manage_stock_field',
					'.stock_fields',
					'._sold_individually_field',
					'._out_stock_threshold_field',
					'._supplier_field',
					'._supplier_sku_field',
					'.shipping_class_field',
					'.variable_stock{%d}_field',
					'.variable_backorders{%d}_field',
					'.variable_stock_status{%d}_field',
					'.variable_sku{%d}_field',
					'[id="variable_shipping_class[{%d}]"]',
				) ),
				'inventoryRemoval'             => __( 'Inventory Removal', ATUM_MULTINV_TEXT_DOMAIN ),
				'inStock'                      => __( 'In Stock', ATUM_MULTINV_TEXT_DOMAIN ),
				'nameInvalid'                  => __( 'Invalid Name!', ATUM_MULTINV_TEXT_DOMAIN ),
				'mustHaveName'                 => __( 'The inventory must have a name', ATUM_MULTINV_TEXT_DOMAIN ),
				'nameAlreadyUsed'              => __( 'That name is already used by another inventory, please try again', ATUM_MULTINV_TEXT_DOMAIN ),
				'none'                         => __( 'None', ATUM_MULTINV_TEXT_DOMAIN ),
				'ok'                           => __( 'OK', ATUM_MULTINV_TEXT_DOMAIN ),
				'outOfStock'                   => __( 'Out of Stock', ATUM_MULTINV_TEXT_DOMAIN ),
				'remove'                       => __( 'Remove', ATUM_MULTINV_TEXT_DOMAIN ),
				'removeConfirmation'           => __( 'Do you want to remove or to write off this inventory?', ATUM_MULTINV_TEXT_DOMAIN ),
				'removeConfirmation2'          => __( 'Do you want to remove or to un-write off this inventory?', ATUM_MULTINV_TEXT_DOMAIN ),
				'removeIt'                     => __( 'Yes, remove it!', ATUM_MULTINV_TEXT_DOMAIN ),
				'removePermanently'            => __( 'This inventory will be removed permanently', ATUM_MULTINV_TEXT_DOMAIN ),
				'removePermanentlyMulti'       => __( 'The selected inventories will be removed permanently', ATUM_MULTINV_TEXT_DOMAIN ),
				'saveBeforeWriteOff'           => __( 'Please save the inventories before writing off them', ATUM_MULTINV_TEXT_DOMAIN ),
				'saveProduct'                  => __( 'Please save the product to set the inventory details', ATUM_MULTINV_TEXT_DOMAIN ),
				'saveToSet'                    => __( 'Save to Set', ATUM_MULTINV_TEXT_DOMAIN ),
				'selectAll'                    => __( 'Select All', ATUM_MULTINV_TEXT_DOMAIN ),
				'selectBulkAction'             => __( 'Please, select a bulk action from the dropdown', ATUM_MULTINV_TEXT_DOMAIN ),
				'selectInventories'            => __( 'Please, select at least one inventory', ATUM_MULTINV_TEXT_DOMAIN ),
				'setButton'                    => __( 'Set', ATUM_MULTINV_TEXT_DOMAIN ),
				'typeName'                     => __( 'Type a name for your new Inventory', ATUM_MULTINV_TEXT_DOMAIN ),
				'unselectAll'                  => __( 'Unselect All', ATUM_MULTINV_TEXT_DOMAIN ),
				'unwriteOff'                   => __( 'Un-write Off', ATUM_MULTINV_TEXT_DOMAIN ),
				'writeOff'                     => __( 'Write Off', ATUM_MULTINV_TEXT_DOMAIN ),
			);

			$vars = array_merge( $vars, Globals::get_date_time_picker_js_vars( [ 'dateFormat' => 'YYYY-MM-DD HH:mm' ] ) );

			// If it's a variable product, get the main inventory for each variation.
			if ( is_a( $wc_product, apply_filters( 'atum/multi_inventory/variable_product_classes', '\WC_Product_Variable' ) ) && $wc_product->has_child() ) {

				$variations = $wc_product->get_children();

				if ( ! empty( $variations ) ) {

					$variations_main_inventories = array();

					foreach ( $variations as $variation_id ) {
						$variation_main_inventory      = Inventory::get_product_main_inventory( $variation_id );
						$variation_main_inventory_data = $variation_main_inventory ? $variation_main_inventory->get_all_data() : '';

						$variations_main_inventories[] = $variation_main_inventory_data;
					}

					$vars['variationsMainInventoryData'] = $variations_main_inventories;

				}

			}

			$vars = array_merge( (array) apply_filters( 'atum/multi_inventory/localized_vars', [] ), $vars );

			wp_localize_script( 'atum-mi-products', 'atumMultInvVars', $vars );

			wp_enqueue_style( 'atum-mi-products' );

			if ( is_rtl() ) {
				wp_register_style( 'atum-mi-products-rtl', ATUM_MULTINV_URL . 'assets/css/atum-mi-products-rtl.css', array(), ATUM_MULTINV_VERSION );
				wp_enqueue_style( 'atum-mi-products-rtl' );
			}

			wp_enqueue_script( 'atum-mi-products' );

		}
		// WooCommerce order edit page.
		elseif ( 'shop_order' === $screen_id || in_array( $screen_id, Globals::get_order_types(), TRUE ) ) {

			wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', [], ATUM_MULTINV_VERSION );
			wp_register_style( 'atum-mi-orders', ATUM_MULTINV_URL . 'assets/css/atum-mi-orders.css', [ 'sweetalert2' ], ATUM_MULTINV_VERSION );

			wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', [], ATUM_MULTINV_VERSION, TRUE );
			AtumHelpers::maybe_es6_promise();
			wp_register_script( 'atum-mi-orders', ATUM_MULTINV_URL . 'assets/js/build/atum-mi-orders.js', [ 'jquery', 'sweetalert2', 'wp-hooks' ], ATUM_MULTINV_VERSION, TRUE );

			wp_register_style( 'atum-icons', ATUM_URL . 'assets/css/atum-icons.css', [], ATUM_MULTINV_VERSION );

			$vars = array(
				'nonce'                 => wp_create_nonce( 'order-item-inventories-nonce' ),
				'managementPopupTitle'  => __( 'Multi-Inventory Management', ATUM_MULTINV_TEXT_DOMAIN ),
				'saveButton'            => __( 'Add', ATUM_MULTINV_TEXT_DOMAIN ),
				'originalLower'         => __( 'The originally deducted stock of {{originalStock}} is lower than the currently deducted stock of {{currentStock}}', ATUM_MULTINV_TEXT_DOMAIN ),
				'currentLower'          => __( 'The currently deducted stock of {{currentStock}} is lower than the originally deducted stock of {{originalStock}}', ATUM_MULTINV_TEXT_DOMAIN ),
				'matchingStocks'        => __( 'The originally deducted stock matches the currently deducted stock', ATUM_MULTINV_TEXT_DOMAIN ),
				'areYouSure'            => __( 'Are you sure?', ATUM_MULTINV_TEXT_DOMAIN ),
				'continue'              => __( 'Continue', ATUM_MULTINV_TEXT_DOMAIN ),
				'cancel'                => __( 'Cancel', ATUM_MULTINV_TEXT_DOMAIN ),
				'notMatching'           => __( 'The originally deducted stock does not match with your current settings', ATUM_MULTINV_TEXT_DOMAIN ),
				'success'               => __( 'Success', ATUM_MULTINV_TEXT_DOMAIN ),
				'ok'                    => __( 'OK', ATUM_MULTINV_TEXT_DOMAIN ),
				'done'                  => __( 'Done!', ATUM_MULTINV_TEXT_DOMAIN ),
				'editInventory'         => __( 'Edit Used Inventories and quantities', ATUM_MULTINV_TEXT_DOMAIN ),
				'roundingPrecision'     => wc_get_rounding_precision(),
				'decimalSeparator'      => wc_get_price_decimal_separator(),
				'decimals'              => wc_get_price_decimals(),
				'confirmRefund'         => __( 'Are you sure you wish to process this refund? This action cannot be undone.', ATUM_MULTINV_TEXT_DOMAIN ),
				'noQty'                 => __( 'Please set quantities to the order item inventories before updating the stock', ATUM_MULTINV_TEXT_DOMAIN ),
				'miEnabled'             => __( 'This product has Multi-Inventory enabled', ATUM_MULTINV_TEXT_DOMAIN ),
				'confirmPurchasePrice'  => __( 'Do you want to set the purchase price of this product to {{number}}?', ATUM_MULTINV_TEXT_DOMAIN ),
				'purchasePriceChanged'  => __( 'The purchase price was changed successfully', ATUM_MULTINV_TEXT_DOMAIN ),
				'purchasePriceField'    => Globals::PURCHASE_PRICE_KEY,
				'orderType'             => $screen_id,
				'createEmptyOrderItems' => TRUE,
			);

			if ( PurchaseOrders::POST_TYPE === $screen_id ) {
				$vars['completedStatus']     = PurchaseOrders::FINISHED;
				$vars['statusNotUpdated']    = __( "The PO status couldn't be changed", ATUM_MULTINV_TEXT_DOMAIN );
				$vars['missingMiOrderItems'] = __( "All the items with MI enabled within this Purchase Order, must have at least one inventory assigned. Please, fix the items marked in red before marking the PO as 'received'", ATUM_MULTINV_TEXT_DOMAIN );
			}
			elseif ( 'shop_order' === $screen_id ) {
				$vars['reduceStockStatuses'] = Globals::get_order_statuses_change_stock();
				$vars['statusNotUpdated']    = __( "The Order status couldn't be changed", ATUM_MULTINV_TEXT_DOMAIN );
				$vars['missingMiOrderItems'] = __( "All the items with MI enabled within this Order, should have at least one inventory assigned. Please, click 'Continue' to add the inventories automatically according to their priorities or 'Cancel' if you want to add the inventories manually.", ATUM_MULTINV_TEXT_DOMAIN );
			}

			wp_localize_script( 'atum-mi-orders', 'atumMultInvOrdersVars', $vars );

			wp_enqueue_style( 'atum-icons' );
			wp_enqueue_style( 'atum-mi-orders' );
			wp_enqueue_script( 'atum-mi-orders' );

			if ( is_rtl() ) {
				wp_register_style( 'atum-mi-orders-rtl', ATUM_MULTINV_URL . 'assets/css/atum-mi-orders-rtl.css', array( 'atum-mi-orders' ), ATUM_MULTINV_VERSION );
				wp_enqueue_style( 'atum-mi-orders-rtl' );
			}

		}
		// WooCommerce product list.
		elseif ( 'edit-product' === $screen_id ) {
			wp_register_style( 'atum-icons', ATUM_URL . 'assets/css/atum-icons.css', [], ATUM_MULTINV_VERSION );
			wp_add_inline_style( 'list-tables', '.column-is_mi{width:2ch;}.mi-enabled{ display: block; margin: 4px 4px 0 4px; width: 14px; height: 14px;color:#7ad03a}' );
			wp_enqueue_style( 'atum-icons' );
		}
	}

	/**
	 * Edit the bundled items with MI enabled
	 *
	 * @since 1.2.1
	 *
	 * @param array              $bundled_items
	 * @param \WC_Product_Bundle $product_bundle
	 *
	 * @return array
	 */
	public function get_bundled_items( $bundled_items, $product_bundle ) {

		$cache_key     = AtumCache::get_cache_key( 'bundled_items', [ $product_bundle->get_id() ] );
		$bundled_cache = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $bundled_cache;
		}

		foreach ( $bundled_items as $bundled_item ) {

			/**
			 * Variable Definition
			 *
			 * @var \WC_Bundled_Item $bundled_item
			 */
			if ( Helpers::has_multi_inventory( $bundled_item->get_product_id() ) ) {

				$bundled_product = $bundled_item->get_product();

				if ( ! $bundled_product instanceof \WC_Product ) {
					continue;
				}

				/**
				 * Variable definition
				 *
				 * @var \WC_Bundled_Item_Data $data
				 */
				$data      = $bundled_item->data;
				$meta_data = $data->get_meta_data();

				// The stock status is wrongly saved by the bundled products with MI enabled.
				$stock_status = $bundled_product->get_stock_status();

				/*
				 * NOTE: The Product Bundles is using "in_stock", "out_of_stock" and "on_back_order"
				 * instead of "instock", "outofstock" and "onbackorders" as WC and ATUM.
				 */

				$updated_meta = [];

				if ( empty( $meta_data['stock_status'] ) || str_replace( '_', '', $meta_data['stock_status'] ) !== $stock_status ) {

					$quantity_min   = max( 1, $bundled_item->get_quantity() );
					$stock_quantity = $bundled_product->get_stock_quantity();

					if ( 'instock' === $stock_status && $quantity_min > $stock_quantity && $bundled_product->managing_stock() ) {
						$updated_meta['stock_status'] = 'out_of_stock';
						$updated_meta['max_stock']    = 0;
					}
					else {

						switch ( $stock_status ) {
							case 'instock':
								$updated_meta['stock_status'] = 'in_stock';
								$updated_meta['max_stock']    = $stock_quantity;
								break;

							case 'outfostock':
								$updated_meta['stock_status'] = 'out_of_stock';
								$updated_meta['max_stock']    = 0;
								break;

							case 'onbackorder':
								$updated_meta['stock_status'] = 'on_backorder';
								$updated_meta['max_stock']    = '';
								break;
						}

					}

					if ( ! empty( $updated_meta ) ) {

						// TODO: THE FIRST PAGE LOAD AFTER THIS CHANGE, SHOWS A WRONG STOCK STATUS FOR THE BUNDLE, LATER LOADS IT GETS THE RIGHT ONE.
						$data->set_meta_data( $updated_meta );
						$data->save();
					}

				}

			}

		}

		AtumCache::set_cache( $cache_key, $bundled_items, ATUM_MULTINV_TEXT_DOMAIN );

		return $bundled_items;

	}

	/**
	 * Hack the quantity input args for the products with MI enabled
	 *
	 * @since 1.0.7
	 *
	 * @param array       $args
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	public function quantity_input_args( $args, $product ) {

		if ( 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product ) ) {
			$args['max_value'] = $product->managing_stock() && ! $product->backorders_allowed() ? $product->get_stock_quantity() : -1;
		}

		return apply_filters( 'atum/multi_inventory/quantity_input_args', $args );

	}

	/**
	 * Get if backorders are allowed in a product inventories
	 *
	 * @since 1.0.7.5
	 *
	 * @param boolean     $allowed
	 * @param integer     $product_id
	 * @param \WC_Product $product
	 *
	 * @return boolean
	 */
	public function product_inventories_backorders_allowed( $allowed, $product_id, $product ) {

		if ( 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product ) ) {

			$allowed     = FALSE;
			$inventories = Helpers::get_product_inventories_sorted( $product_id );

			if ( ! empty( $inventories ) ) {

				$inventory_iteration = Helpers::get_product_inventory_iteration( $product_id );

				foreach ( $inventories as $inventory ) {

					if ( ! $inventory->is_sellable() ) {
						continue;
					}

					if ( 'use_next' === $inventory_iteration ) {
						if ( 'no' !== $inventory->backorders ) {
							$allowed = TRUE;
							break;
						}
					}
					// Get the first selling inventory.
					else {

						$allowed = 'no' !== $inventory->backorders;
						break;
					}

				}

			}
		}

		return $allowed;
	}

	/**
	 * Returns whether or not backorders with notify option set in a product inventories
	 *
	 * @since 1.0.7.5
	 *
	 * @param boolean     $required
	 * @param \WC_Product $product
	 *
	 * @return boolean
	 */
	public function product_inventories_backorders_require_notification( $required, $product ) {

		if ( 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product ) ) {

			$required    = FALSE;
			$product_id  = $product->get_id();
			$inventories = Helpers::get_product_inventories_sorted( $product_id );

			if ( ! empty( $inventories ) ) {

				$inventory_iteration = Helpers::get_product_inventory_iteration( $product_id );

				foreach ( $inventories as $inventory ) {

					if ( ! $inventory->is_sellable() ) {
						continue;
					}

					if ( 'use_next' === $inventory_iteration ) {
						if ( 'no' === $inventory->manage_stock || 'yes' === $inventory->backorders ) {
							break;
						}

						if ( 'notify' === $inventory->backorders ) {
							$required = TRUE;
							break;
						}
					}
					// Get the first selling inventory.
					else {

						$required = 'yes' === $inventory->manage_stock && 'notify' === $inventory->backorders;
						break;
					}

				}

			}
		}

		return $required;

	}

	/**
	 * Check if the current product has some expired inventories and change stock status if applicable
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Post $post_object Optional. Only passed if this method is called through the_post action.
	 */
	public function maybe_expired_inventories( $post_object = NULL ) {

		if ( $post_object && ! in_array( $post_object->post_type, [ 'product', 'product_variation' ] ) ) {
			return;
		}

		$product_id = $post_object ? $post_object->ID : get_the_ID();
		$product_id = apply_filters( 'atum/multi_inventory/product_id', $product_id );

		if (
			'yes' === Helpers::get_product_multi_inventory_status( $product_id ) &&
			'yes' === Helpers::get_product_expirable_inventories( $product_id ) &&
			Helpers::is_product_multi_inventory_compatible( $product_id )
		) {

			$inventories = Inventory::get_product_inventories( $product_id, '', FALSE );

			if ( ! empty( $inventories ) ) {

				foreach ( $inventories as $inventory ) {
					$this->maybe_set_expired_inventory_out_of_stock( $inventory );
				}

			}

		}

	}

	/**
	 * Set out of stock the specified inventory (if needed)
	 *
	 * @since 1.2.3.1
	 *
	 * @param Inventory $inventory
	 */
	private function maybe_set_expired_inventory_out_of_stock( $inventory ) {

		if ( $inventory->is_expired( TRUE ) && ( 'no' !== $inventory->manage_stock || 'outofstock' !== $inventory->stock_status ) ) {

			$inventory_meta = array(
				'expired_stock' => $inventory->stock_quantity,
				'stock_status'  => 'outofstock',
				'manage_stock'  => 'no',
			);

			$inventory->set_meta( $inventory_meta );
			$inventory->save_meta();

			do_action( 'atum/multi_inventory/expired_inventory', $inventory );

		}

	}

	/**
	 * Add the expired inventories checking hook to the list of queues
	 *
	 * @since 1.2.3.1
	 *
	 * @param array $recurring_hooks
	 *
	 * @return array
	 */
	public function add_expired_inventories_check_cron( $recurring_hooks ) {

		// Every day at midnight.
		$recurring_hooks['atum/multi_inventory/update_expired_inventories'] = [
			'time'     => 'midnight tomorrow',
			'interval' => DAY_IN_SECONDS,
		];

		// Register the recurring hook action.
		add_action( 'atum/multi_inventory/update_expired_inventories', array( $this, 'update_expired_inventories' ) );

		return $recurring_hooks;

	}

	/**
	 * This hook is only intended to be used within a cron schedule.
	 * It checks all the expired inventories in the database that should go "out of stock".
	 *
	 * @since 1.2.3.1
	 */
	public function update_expired_inventories() {

		$expired_inventories = Inventory::get_expired_inventories();

		if ( ! empty( $expired_inventories ) ) {

			foreach ( $expired_inventories as $expired_inventory ) {

				if (
					'yes' === Helpers::get_product_multi_inventory_status( $expired_inventory->product_id ) &&
					'yes' === Helpers::get_product_expirable_inventories( $expired_inventory->product_id ) &&
					Helpers::is_product_multi_inventory_compatible( $expired_inventory->product_id )
				) {

					$inventory = Helpers::get_inventory( $expired_inventory->id );
					$this->maybe_set_expired_inventory_out_of_stock( $inventory );

				}

			}

		}

	}

	/**
	 * Set the default values for checkout form inputs
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value
	 * @param string $input
	 *
	 * @return mixed
	 *
	 * @throws \Exception
	 */
	public function default_checkout_values( $value, $input ) {

		// Only when a restriction mode is enabled.
		if ( 'no-restriction' === Helpers::get_region_restriction_mode() ) {
			return $value;
		}

		$customer_location = Helpers::get_visitor_location();

		if ( isset( $customer_location['country'] ) && in_array( $input, [ 'billing_country', 'shipping_country' ] ) ) {
			return $customer_location['country'];
		}

		if ( isset( $customer_location['state'] ) && in_array( $input, [ 'billing_state', 'shipping_state' ] ) ) {
			return $customer_location['state'];
		}

		if ( isset( $customer_location['postcode'] ) && in_array( $input, [ 'billing_postcode', 'shipping_postcode' ] ) ) {
			return $customer_location['postcode'];
		}

		return $value;

	}

	/**
	 * Add MI column to products list (show which products have MI enabled)
	 *
	 * @since 1.0.1
	 *
	 * @param array $table_columns
	 *
	 * @return array
	 */
	public function add_mi_column( $table_columns ) {

		$new_table_colums = array();

		// Add the columns after the Product Details group.
		foreach ( $table_columns as $column_key => $column_value ) {

			if ( 'is_in_stock' === $column_key ) {
				$new_table_colums['is_mi'] = '<div class="mi-enabled tips" data-tip="' . esc_attr__( 'Multi Inventory', ATUM_MULTINV_TEXT_DOMAIN ) . '"><i class="atum-icon atmi-multi-inventory"></i></div>';
			}

			$new_table_colums[ $column_key ] = $column_value;
		}

		return $new_table_colums;

	}

	/**
	 * Add MI icon if the product has MI set
	 *
	 * @since 1.0.1
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public function customize_column_content( $column, $post_id ) {

		if ( 'is_mi' === $column && Helpers::has_multi_inventory( $post_id ) ) {
			echo '<div class="mi-enabled tips" data-tip="' . esc_attr__( 'Multi-Inventory enabled', ATUM_MULTINV_TEXT_DOMAIN ) . '"><i class="atum-icon atmi-multi-inventory"></i></div>';
		}
		elseif ( 'is_in_stock' === $column ) {

			// For a variable product, if it's managing stock, the real stock status will be taken for the WC product list.
			$product = wc_get_product( $post_id );
			if ( in_array( $product->get_type(), array_diff( Globals::get_inheritable_product_types(), [ 'grouped', 'bundle' ] ), TRUE ) ) {

				add_filter( 'atum/multi_inventory/bypass_mi_get_manage_stock', '__return_true' );

				if ( $product->managing_stock() ) {
					add_filter( 'atum/multi_inventory/bypass_mi_get_stock_status', '__return_true' );
				}

				remove_filter( 'atum/multi_inventory/bypass_mi_get_manage_stock', '__return_true' );

			}

		}

	}

	/**
	 * Bypass the MI's get_stock_status on products list
	 *
	 * @since 1.3.9
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public function remove_filters_column_content( $column, $post_id ) {

		if ( 'is_in_stock' === $column ) {
			remove_filter( 'atum/multi_inventory/bypass_mi_get_stock_status', '__return_true' );
		}
	}

	/**
	 * Force the custom MI get_prop methods to get MI values
	 *
	 * @since 1.0.1
	 *
	 * @param \WP_Screen $current_screen The current screen object (if available).
	 */
	public function force_mi_get_stock_props( $current_screen = NULL ) {

		// TODO: THIS IS AFFECTING THE LOADING TIME OF THE PRODUCTS LIST A LOT AND WE SHOULD TRY TO OPTIMIZE IT.
		if (
			( $current_screen instanceof \WP_Screen && 'edit-product' === $current_screen->id && ! isset( $_REQUEST['bulk_edit'] ) ) ||
			( doing_action( 'wp_ajax_woocommerce_json_search_products_and_variations' ) )
		) {
			add_filter( 'atum/multi_inventory/bypass_mi_get_stock_quantity', '__return_false' );
			add_filter( 'atum/multi_inventory/bypass_mi_get_stock_status', '__return_false' );
			add_filter( 'atum/multi_inventory/bypass_mi_get_manage_stock', '__return_false' );
		}

	}

	/**
	 * Delete Inventories linked to product
	 *
	 * @since 1.0.1
	 *
	 * @param \WC_Product $product
	 */
	public function remove_inventories_after_product_removal( $product ) {
		Inventory::delete_inventories( $product->get_id() );
	}

	/**
	 * Remove shipping zones linked into inventories when WC shipping zone is removed.
	 *
	 * @since 1.0.1
	 *
	 * @param string $shipping_zone_id
	 */
	public function remove_deleted_shipping_zones( $shipping_zone_id ) {

		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . Inventory::INVENTORY_REGIONS_TABLE,
			array(
				'zone_id' => $shipping_zone_id,
			),
			array(
				'%d',
			)
		);
	}

	/**
	 * Edit the products returned by \WC_Ajax::json_search_products to mark the ones with MI enabled.
	 *
	 * @since 1.0.1
	 *
	 * @param array $products
	 *
	 * @return array
	 */
	public function mark_json_search_products( $products ) {

		// Only needed by WC 3.5.0+.
		if ( version_compare( WC()->version, '3.5.0', '<' ) ) {
			return $products;
		}

		// TODO: Change this and get the value from MI settings when included.
		$createEmptyOrderItems = TRUE;

		foreach ( $products as $product_id => $product_name ) {

			if ( ! $createEmptyOrderItems && 'yes' === Helpers::get_product_multi_inventory_status( $product_id ) && Helpers::is_product_multi_inventory_compatible( $product_id ) ) {
				$products[ $product_id ] = '<span class="mi-product">' . $product_name . '</span>';
			}

		}

		return $products;

	}

	/**
	 * Update inventory data coming from ListTables and remove it from the array.
	 *
	 * @since 1.0.6
	 *
	 * @param array $data
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function update_inventory_data( $data ) {

		$saved_products = [];

		foreach ( $data as $key => $meta ) {

			if ( strpos( $key, ':' ) !== FALSE ) {

				list( $product_id, $inventory_id ) = explode( ':', $key );

				$main_inventory = Inventory::get_product_main_inventory( $product_id );
				$inventory_id   = absint( $inventory_id );
				$product        = wc_get_product( $product_id );
				$inventory      = $inventory_id == $main_inventory->id ? $main_inventory : Helpers::get_inventory( $inventory_id ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				$inventory_data = $inventory_meta = [];
				$default_data   = Inventory::get_default_data();
				$default_meta   = Inventory::get_default_meta();

				foreach ( $meta as $meta_key => $meta_value ) {

					$meta_key = esc_attr( $meta_key );

					switch ( $meta_key ) {

						case 'stock':
							Helpers::update_inventory_stock( $product, $inventory, $meta_value );
							break;

						case '_sale_price_dates_from':
						case '_sale_price_dates_to':
							$meta_key                    = '_sale_price_dates_from' === $meta_key ? 'date_on_sale_from' : 'date_on_sale_to';
							$inventory_meta[ $meta_key ] = $meta_value;
							break;

						// Any other data.
						default:
							if ( array_key_exists( $meta_key, $default_data ) ) {
								$inventory_data[ $meta_key ] = $meta_value;
							}
							elseif ( array_key_exists( $meta_key, $default_meta ) ) {
								$inventory_meta[ $meta_key ] = $meta_value;
							}

							break;
					}

				}

				// Update the inventory data.
				if ( ! empty( $inventory_data ) ) {

					$inventory->set_data( $inventory_data );
					$inventory->save();

					$saved_products[ $product_id ] = $product;

				}

				// Update the inventory meta.
				if ( ! empty( $inventory_meta ) ) {

					$inventory->set_meta( $inventory_meta );
					$inventory->save_meta();

					$saved_products[ $product_id ] = $product;

				}

				unset( $data[ $key ] );
			}

		}

		// Run all the hooks that are triggered after a product is saved.
		if ( ! empty( $saved_products ) ) {
			foreach ( $saved_products as $saved_product ) {
				do_action( 'atum/product_data/after_save_data', $data, $saved_product );
				do_action( 'atum/multi_inventory/after_save_product_data', $saved_product );
			}
		}

		return $data;

	}

	/**
	 * Current stock values within current stock values widget.
	 *
	 * @since   1.0.7.3
	 * @version 1.1
	 *
	 * @param bool        $allow
	 * @param \WC_Product $product
	 *
	 * @return bool
	 */
	public function get_current_stock_values( $allow, $product ) {

		$get_write_off = isset( $_POST['writeOff'] ) && 'yes' === $_POST['writeOff'];

		if ( 'yes' === Helpers::get_product_multi_inventory_status( $product ) ) {

			$product_id  = $product->get_id();
			$inventories = Inventory::get_product_inventories( $product_id, '', FALSE, ! $get_write_off );

			if ( ! empty( $inventories ) ) {

				foreach ( $inventories as $inventory ) {

					// Check if only the write-off are being requested.
					if ( $get_write_off && ! $inventory->is_write_off() ) {
						continue;
					}

					$inventory_stock = $inventory->stock_quantity;
					$product         = AtumHelpers::get_atum_product( $product_id );

					$product_purchase_price = 'yes' === Helpers::get_product_price_per_inventory( $product ) ?
						(float) $inventory->purchase_price :
						(float) $product->get_purchase_price();

					if ( $inventory_stock > 0 ) {

						$this->current_stock_value_counters['items_stocks_counter'] += $inventory_stock;

						if ( $product_purchase_price && ! empty( $product_purchase_price ) ) {
							$this->current_stock_value_counters['items_purchase_price_total'] += ( $product_purchase_price * $inventory_stock );
						}
						else {
							$this->current_stock_value_counters['items_without_purchase_price'] += $inventory_stock;
						}

					}
				}

			}

			$allow = FALSE;

		}
		elseif ( $get_write_off ) {
			$allow = FALSE; // Discard all the products that have MI disabled when getting the write-off inventories.
		}

		return $allow;

	}

	/**
	 * Sum the MI's current stock values to counters
	 *
	 * @since 1.3.0
	 *
	 * @param array $counters
	 *
	 * @return array
	 */
	public function sum_mi_current_stock_value_counters( $counters ) {

		foreach ( $counters as $key => $value ) {

			if ( array_key_exists( $key, $this->current_stock_value_counters ) ) {
				$counters[ $key ] += $this->current_stock_value_counters[ $key ];
			}

		}

		return $counters;

	}

	/**
	 * Add the MI's write-off filter to the dashboard's Current Stock Value widget
	 *
	 * @since 1.3.0
	 */
	public function add_current_stock_value_filter() {

		?>
		<select class="write-off-filter" name="write_off">
			<option value="no"><?php esc_html_e( 'No write-off', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
			<option value="yes"><?php esc_html_e( 'Write-off', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
		</select>
		<?php

	}

	/**
	 * Rebuild the stock status for Inventories when the "Out of Stock Threshold" option is changed.
	 *
	 * @since 1.2.3
	 *
	 * @param bool $clean_meta
	 */
	public function rebuild_mi_out_stock_threshold( $clean_meta ) {

		global $wpdb;

		$inventories_table      = $wpdb->prefix . Inventory::INVENTORIES_TABLE;
		$inventories_meta_table = $wpdb->prefix . Inventory::INVENTORY_META_TABLE;

		// Get all the inventories that are not main and reached the out of stock threshold.
		// phpcs:disable WordPress.DB.PreparedSQL
		$ids_to_rebuild_stock_status = $wpdb->get_col( "
            SELECT DISTINCT i.id FROM $inventories_table i
            LEFT JOIN $inventories_meta_table im ON i.id = im.inventory_id
            WHERE im.out_stock_threshold > 0
            AND i.is_main != 1;
        " );
		// phpcs:enable

		// Ensure the option is updated.
		AtumHelpers::get_option( 'out_stock_threshold', FALSE, FALSE, TRUE );

		foreach ( $ids_to_rebuild_stock_status as $inventory_id ) {

			$inventory = Helpers::get_inventory( $inventory_id );

			if ( $clean_meta ) {
				$inventory->set_meta( [ 'out_stock_threshold' => NULL ] );
			}

			$inventory->save_meta();

		}

	}

	/**
	 * Prevent adding the tax query when the restriction mode is active. Keep the attributes to add later the query.
	 *
	 * @since 1.2.3.1
	 *
	 * @param array     $tax_query
	 * @param \WC_Query $wc_query
	 *
	 * @return array
	 */
	public function modify_product_tax_query( $tax_query, $wc_query ) {

		$this->product_tax_query = $tax_query;
		$tax_query               = [];

		// Add the filter only if the tax query was modified.
		add_filter( 'posts_clauses', array( $this, 'add_mi_restrictions_to_product_loop' ), 10, 2 );

		return $tax_query;

	}

	/**
	 * Add MI modified tax query and the multi inventory modifications to the product's loop modifying the clauses (only when WC hide out of stock products is active)
	 * The where clause will need three parts, all of them with the original tax_query modificator:
	 *      1. For MI active ATUM MI supported products (it will change depending on the region restriction value).
	 *      2. For non-active MI ATUM MI supported products.
	 *      3. For non-supported MI ATUM products.
	 * 2 and 3 will depend only on the original tax query.
	 *
	 * @since 1.2.3.1
	 *
	 * @param array     $clauses
	 * @param \WP_Query $query   Per reference.
	 *
	 * @return array
	 */
	public function add_mi_restrictions_to_product_loop( $clauses, $query ) {

		global $wpdb;

		$inv_table          = $wpdb->prefix . Inventory::INVENTORIES_TABLE;
		$inv_meta_table     = $wpdb->prefix . Inventory::INVENTORY_META_TABLE;
		$atum_product_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		$tax_product_types  = array(
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => MultiInventory::get_compatible_types(),
			'operator' => 'NOT IN',
		);

		$product_tax_query        = $this->product_tax_query;
		$product_visibility_terms = wc_get_product_visibility_term_ids();

		foreach ( $product_tax_query as &$product_tax ) {

			if ( is_array( $product_tax ) && 'product_visibility' === $product_tax['taxonomy'] ) {

				$pos = array_search( $product_visibility_terms['outofstock'], $product_tax['terms'] );

				if ( FALSE !== $pos ) {
					unset( $product_tax['terms'][ $pos ] );
				}

				break;

			}

		}

		$atum_tax_query   = new \WP_Tax_Query( $product_tax_query );
		$atum_tax_clauses = $atum_tax_query->get_sql( $wpdb->posts, 'ID' );

		$this->product_tax_query[] = $tax_product_types;
		$new_tax_query             = new \WP_Tax_Query( $this->product_tax_query );
		$new_tax_clauses           = $new_tax_query->get_sql( $wpdb->posts, 'ID' );

		$tax_product_types['operator'] = 'IN';

		$non_mi_tax_query   = new \WP_Tax_Query( [ $tax_product_types ] );
		$non_mi_tax_clauses = $non_mi_tax_query->get_sql( $wpdb->posts, 'ID' );

		$region_restriction_mode = Helpers::get_region_restriction_mode();

		$meta_inv_col = 'apd.multi_inventory';

		// If MI is enabled globally, get all those distinct from 0.
		if ( 'yes' === AtumHelpers::get_option( 'mi_default_multi_inventory', 'no' ) ) {
			$meta_inv_value     = "($meta_inv_col = 1 OR $meta_inv_col IS NULL)";
			$non_meta_inv_value = "$meta_inv_col = 0";
		}
		// If MI is disabled globally, get only those enabled individually (=1).
		else {
			$meta_inv_value     = "$meta_inv_col = 1";
			$non_meta_inv_value = "($meta_inv_col = 0 OR $meta_inv_col IS NULL)";
		}

		if ( ! empty( $wpdb->wc_product_meta_lookup ) ) {

			$common_join = " 
				LEFT JOIN $inv_meta_table atim ON ati.ID = atim.inventory_id
				LEFT JOIN $wpdb->wc_product_meta_lookup pml ON ati.product_id = pml.product_id
				LEFT JOIN $atum_product_table apd ON ati.product_id = apd.product_id
		    ";

		}
		/* @deprecated Uses the postmeta table to get the stock quantity */
		else {

			$common_join = " 
				LEFT JOIN $inv_meta_table atim ON ati.ID = atim.inventory_id
				LEFT JOIN $wpdb->postmeta pm ON (ati.product_id = pm.post_id AND pm.meta_key = '_stock_status')
				LEFT JOIN $atum_product_table apd ON ati.product_id = apd.product_id
		    ";

		}

		$sql        = apply_filters( 'atum/multi_inventory/product_loop_mi_restrictions', "SELECT DISTINCT ati.product_id FROM $inv_table ati $common_join", $common_join );
		$sql_parent = apply_filters( 'atum/multi_inventory/product_loop_parent_mi_restrictions', "
			SELECT DISTINCT pr.post_parent FROM $inv_table ati
			LEFT JOIN $wpdb->posts pr ON ati.product_id = pr.ID $common_join
		", $common_join );


		if ( ! empty( $wpdb->wc_product_meta_lookup ) ) {
			$sql_where = "WHERE $meta_inv_value AND ( pml.stock_status IN ('instock','onbackorder') OR atim.stock_status IN ('instock','onbackorder'))";
		}
		/* @deprecated Uses the postmeta table to get the stock quantity */
		else {
			$sql_where = "WHERE $meta_inv_value AND ( pm.meta_value IN ('instock','onbackorder') OR atim.stock_status IN ('instock','onbackorder'))";
		}

		$sql_where_parent = "$sql_where AND pr.post_parent IS NOT NULL AND pr.post_parent > 0";

		// Countries mode.
		if ( 'countries' === $region_restriction_mode ) {

			$visitor_location = Helpers::get_visitor_location();

			$country = ! empty( $visitor_location['country'] ) ?
				$visitor_location['country'] :
				AtumHelpers::get_option( 'mi_default_country', get_option( 'woocommerce_default_country' ) );

			$where_region      = ' AND ati.region LIKE "%' . $country . '%"';
			$sql_where        .= $where_region;
			$sql_where_parent .= $where_region;

		}
		// Shipping Zones mode.
		elseif ( 'shipping-zones' === $region_restriction_mode ) {

			$visitor_location = Helpers::get_visitor_location();

			if ( ! empty( array_filter( $visitor_location ) ) ) {
				$shipping_zones = Helpers::get_zones_matching_package( [ 'destination' => $visitor_location ] );
			}
			// If the visitor location can not be obtained, get de default zone.
			else {
				$shipping_zones = array_filter( [ AtumHelpers::get_option( 'mi_default_shipping_zone', '' ) ] );
			}

			$join_zone         = " LEFT JOIN {$wpdb->prefix}atum_inventory_regions atir ON ati.id = atir.inventory_id";
			$sql              .= $join_zone;
			$sql_parent       .= $join_zone;
			$where_zone        = ' AND atir.zone_id IN (' . implode( ',', $shipping_zones ) . ')';
			$sql_where        .= $where_zone;
			$sql_where_parent .= $where_zone;

		}

		// non MI products where.
		$non_mi_where = " 
			OR ( $wpdb->posts.ID IN ( 
				SELECT DISTINCT $wpdb->posts.ID FROM $wpdb->posts
				LEFT JOIN $atum_product_table apd ON $wpdb->posts.ID = apd.product_id
				{$non_mi_tax_clauses['join']}
				WHERE $non_meta_inv_value {$atum_tax_clauses['where']} {$non_mi_tax_clauses['where']}
			) )
		";

		$clauses['join']         .= $new_tax_clauses['join'];
		$new_tax_clauses['where'] = preg_replace( '/AND/', 'OR', $new_tax_clauses['where'], 1 );

		$clauses['where'] .= " AND ( ( $wpdb->posts.ID IN ( $sql_parent $sql_where_parent ) OR $wpdb->posts.ID IN ( $sql $sql_where )) {$atum_tax_clauses['where']} $non_mi_where {$new_tax_clauses['where']} )";

		// Prevent to duplicate the filter if the tax query is called another time.
		remove_filter( 'posts_clauses', array( $this, 'add_mi_restrictions_to_product_loop' ) );

		return $clauses;

	}

	/**
	 * Handle product type changes.
	 *
	 * @since 1.2.5.1
	 *
	 * @param \WC_Product $product Product data.
	 * @param string      $from    Origin type.
	 * @param string      $to      New type.
	 */
	public function product_type_changed( $product, $from, $to ) {

		if ( MultiInventory::can_have_assigned_mi_product_type( $from ) && ! MultiInventory::can_have_assigned_mi_product_type( $to ) ) {
			Inventory::delete_inventories( $product->get_id() );
		}

	}

	/**
	 * Duplicate all the BOM configuration when duplicating any product
	 * This hook it's executed on variation duplication also.
	 *
	 * @since 1.3.0
	 *
	 * @param \WC_Product $duplicate
	 * @param \WC_Product $product
	 */
	public function duplicate_product_mi( $duplicate, $product ) {

		$product_id   = $product->get_id();
		$duplicate_id = $duplicate->get_id();

		/**
		 * Duplicate the inventories.
		 */
		$inventories = Helpers::get_product_inventories_sorted( $product_id, FALSE );

		if ( ! empty( $inventories ) ) {

			global $wpdb;
			$wpdb->delete(
				$wpdb->prefix . Inventory::INVENTORIES_TABLE,
				array(
					'product_id' => $duplicate_id,
				),
				array(
					'%d',
				)
			);

			foreach ( $inventories as $inventory ) {
				$duplicated_inventory = clone $inventory;
				$duplicated_inventory->reset_id(); // Unset the inventory ID, so a new one is saved to the DB.
				$duplicated_inventory->set_data( [ 'product_id' => $duplicate_id ] );
				$duplicated_inventory->save();
				$duplicated_inventory->save_meta();
			}

		}

	}

	/**
	 * Disable the force rebuild made on ATUM core for the products with MI.
	 * MI is already setting setting the right stock status when reaching the OOST.
	 *
	 * @since 1.3.0
	 *
	 * @param bool        $allow
	 * @param \WC_Product $product
	 *
	 * @return bool
	 */
	public function maybe_force_rebuild_stock_status( $allow, $product ) {

		if ( 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product ) ) {
			$allow = FALSE;
		}

		return $allow;

	}

	/**
	 * Change the manage stock for all the inventories at once.
	 * This runs after the manage stock is set for all the products.
	 *
	 * @since 1.3.1
	 *
	 * @param string $meta_key The meta key being changed.
	 * @param string $status   The value to set (yes or no).
	 */
	public function change_inventories_manage_stock( $meta_key, $status ) {

		if ( '_manage_stock' === $meta_key ) {

			global $wpdb;

			$inventories_meta_table = $wpdb->prefix . Inventory::INVENTORY_META_TABLE;
			$value                  = 'yes' === $status ? 1 : 0;

			$wpdb->query( $wpdb->prepare( "UPDATE $inventories_meta_table SET manage_stock = %d", $value ) ); // phpcs:ignore WordPress.DB.PreparedSQL

		}

	}

	/**
	 * Add the BBE dates to products in cart.
	 *
	 * @since 1.3.1
	 *
	 * @param array  $cart_item
	 * @param string $cart_item_key
	 */
	public function add_expiry_dates_in_cart( $cart_item, $cart_item_key ) {

		$product             = AtumHelpers::get_atum_product( $cart_item['data'] );
		$has_multi_inventory = 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product );

		if ( $has_multi_inventory ) {

			$inventories   = Helpers::get_product_inventories_sorted( $product->get_id() );
			$remaining_qty = $cart_item['quantity'];
			$bbe_dates     = array();

			if ( isset( $cart_item['inventory'] ) ) {

				$inventory = $cart_item['inventory'];
				$bbe_date  = $inventory->bbe_date;

				$bbe_dates[] = array(
					'date'      => $bbe_date->date_i18n( get_option( 'date_format' ) ),
					'items'     => $remaining_qty,
					'inventory' => $inventory,
				);
			} else {

				foreach ( $inventories as $inventory ) {

					/**
					 * Variable definition
					 *
					 * @var \WC_DateTime $bbe_date
					 */
					$bbe_date   = $inventory->bbe_date;
					$backorders = $inventory->backorders;
					$stock      = 'no' !== $backorders ? $remaining_qty : $inventory->stock_quantity;

					if ( $bbe_date && $stock ) {

						$bbe_dates[] = array(
							'date'      => $bbe_date->date_i18n( get_option( 'date_format' ) ),
							'items'     => $remaining_qty >= $stock ? $stock : $remaining_qty,
							'inventory' => $inventory,
						);

					}

					$remaining_qty -= $stock;

					if ( $remaining_qty <= 0 ) {
						break;
					}

				}
			}

			$num_dates = count( $bbe_dates );
			foreach ( $bbe_dates as $bbe_date ) {

				if ( $num_dates > 1 ) {
					/* translators: first is the number of item and second is the expiry date */
					$message = sprintf( __( 'Expiry Date (%1$d items): %2$s', ATUM_MULTINV_TEXT_DOMAIN ), $bbe_date['items'], $bbe_date['date'] );
				}
				else {
					/* translators: the expiry date */
					$message = sprintf( __( 'Expiry Date: %s', ATUM_MULTINV_TEXT_DOMAIN ), $bbe_date['date'] );
				}

				echo apply_filters( 'atum/multi_inventory/expiry_date_message', '<div class="atum-mi-bbe-date"><small>' . $message . '</small></div>', $bbe_date['inventory'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			}

		}

	}

	/**
	 * Show all the MI's SKUs on the product pages
	 *
	 * @since 1.3.6
	 */
	public function maybe_show_mi_skus() {

		add_filter( 'woocommerce_product_get_sku', function ( $value, $product ) {

			if ( 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product ) ) {

				$skus        = [];
				$inventories = Helpers::get_product_inventories_sorted( $product->get_id() );

				foreach ( $inventories as $inventory ) {

					if ( 'outofstock' === $inventory->stock_status ) {
						continue;
					}

					$skus[] = $inventory->sku;
				}

				$value = apply_filters( 'atum/multi_inventory/mi_skus', implode( ' | ', array_filter( array_unique( $skus ) ) ), $product, $inventories );

			}

			return $value;

		}, 10, 2 );

	}

	/**
	 * Maybe prevent default stock status query to be applied and apply our own query (with atum_stock_status).
	 * Probably this hook should be applied in ATUM, but for now only is failing for MI products.
	 *
	 * @since 1.3.7
	 *
	 * @param array $query_vars
	 *
	 * @return array
	 */
	public function maybe_change_product_stock_status_query( $query_vars ) {

		global $typenow;

		if ( 'product' === $typenow && ! empty( $_GET['stock_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_filter( 'posts_clauses', array( $this, 'filter_stock_status_post_clauses' ) );
			$this->stock_status_queried = wc_clean( wp_unslash( $_GET['stock_status'] ) );
			unset( $_GET['stock_status'] );
		}

		return $query_vars;
	}

	/**
	 * Filter by stock status.
	 *
	 * @since 1.3.7
	 *
	 * @param array $args Query args.
	 *
	 * @return array
	 */
	public function filter_stock_status_post_clauses( $args ) {

		global $wpdb;

		if ( ! empty( $this->stock_status_queried ) ) {

			if ( ! strstr( $args['join'], Globals::ATUM_PRODUCT_DATA_TABLE ) ) {
				$args['join'] .= " LEFT JOIN {$wpdb->prefix}" . Globals::ATUM_PRODUCT_DATA_TABLE . " apd ON $wpdb->posts.ID = apd.product_id ";
			}

			$args['where'] .= $wpdb->prepare( ' AND apd.atum_stock_status=%s ', $this->stock_status_queried );

		}

		return $args;
	}

	/**
	 * Change the location cookie if the shipping has changed in cart
	 *
	 * @since 1.3.10
	 */
	public function maybe_update_location_cookie() {

		$nonce_value = wc_get_var( $_REQUEST['woocommerce-shipping-calculator-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) );

		if ( ! empty( $_POST['calc_shipping'] ) && ( wp_verify_nonce( $nonce_value, 'woocommerce-shipping-calculator' ) || wp_verify_nonce( $nonce_value, 'woocommerce-cart' ) ) ) {

			$address = array();

			$address['country']  = isset( $_POST['calc_shipping_country'] ) ? wc_clean( wp_unslash( $_POST['calc_shipping_country'] ) ) : '';
			$address['state']    = isset( $_POST['calc_shipping_state'] ) ? wc_clean( wp_unslash( $_POST['calc_shipping_state'] ) ) : '';
			$address['postcode'] = isset( $_POST['calc_shipping_postcode'] ) ? wc_clean( wp_unslash( $_POST['calc_shipping_postcode'] ) ) : '';
			$address['city']     = isset( $_POST['calc_shipping_city'] ) ? wc_clean( wp_unslash( $_POST['calc_shipping_city'] ) ) : '';

			$address = apply_filters( 'woocommerce_cart_calculate_shipping_address', $address );

			// If no CP is set or if set is a valid CP.
			if ( ! $address['postcode'] || \WC_Validation::is_postcode( $address['postcode'], $address['country'] ) ) {

				$address['postcode'] = wc_format_postcode( $address['postcode'], $address['country'] );

				// Set the location cookie to the new shipping address.
				$region = ! empty( $address['state'] ) ? $address['country'] . ':' . $address['state'] : $address['country'];
				GeoPrompt::set_location_cookie( array(
					'region'   => $region,
					'postcode' => $address['postcode'],
				) );

				unset( $address['city'] );
				Helpers::set_visitor_location( $address );

			}

		}

	}

	/**
	 * Hack the email content for the no_stock notification
	 *
	 * @since 1.3.9.2
	 *
	 * @param string      $message
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public function email_content_inventory_no_stock( $message, $product ) {

		return $this->email_stock_notification_message( $message, $product, 'no_stock' );

	}

	/**
	 * Hack the email content for the low_stock notification
	 *
	 * @since 1.3.9.2
	 *
	 * @param string      $message
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public function email_content_inventory_low_stock( $message, $product ) {

		return $this->email_stock_notification_message( $message, $product, 'low_stock' );

	}

	/**
	 * Hack the email content for the backorder notification
	 *
	 * @since 1.3.9.2
	 *
	 * @param string      $message
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public function email_content_inventory_backorder( $message, $product ) {

		return $this->email_stock_notification_message( $message, $product, 'backorder' );

	}


	/**
	 * Generate the email content message.
	 *
	 * @since 1.3.9.2
	 *
	 * @param string            $message
	 * @param \WC_Product|array $product
	 * @param string            $notification
	 *
	 * @return string
	 */
	public function email_stock_notification_message( $message, $product, $notification ) {

		$args = array();

		if ( is_array( $product ) && isset( $product['product'] ) ) {
			$args    = $product;
			$product = $product['product'];
		}

		$cache_key = AtumCache::get_cache_key( 'product_inventories_notify_stock', $product->get_id() );
		$data      = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {

			foreach ( $data as $inventory_id => $states ) {

				if ( isset( $states[ $notification ] ) && FALSE !== $states[ $notification ] ) {

					$inventory      = Helpers::get_inventory( $inventory_id, $product->get_id() );
					$inventory_name = $inventory->name;

					if ( strlen( $inventory->sku ) > 0 )
						$inventory_name .= ' (' . $inventory->sku . ')';

					if ( 'no_stock' === $notification ) {
						$message = sprintf(
							/* Translators: 1: inventory name, 2: product name */
							__( 'The inventory %1$s from the product %2$s is out of stock.', ATUM_MULTINV_TEXT_DOMAIN ),
							$inventory_name,
							html_entity_decode( wp_strip_all_tags( $product->get_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) )
						);
					}
					elseif ( 'low_stock' === $notification ) {
						$message = sprintf(
							/* translators: 1: inventory name, 2: product name, 3: items in stock */
							__( 'The inventory %1$s from the product %2$s is low in stock. There are %3$d left.', ATUM_MULTINV_TEXT_DOMAIN ),
							$inventory_name,
							html_entity_decode( wp_strip_all_tags( $product->get_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
							html_entity_decode( wp_strip_all_tags( $inventory->get_available_stock() ) )
						);
					}
					elseif ( 'backorder' === $notification ) {
						$message = sprintf(
							/* translators: 1: items backordered, 2: inventory name, 3: product name, 4: Order id */
							__( '%1$s units of the inventory %2$s from the product %3$s have been backordered in order #%4$s.', ATUM_MULTINV_TEXT_DOMAIN ),
							$args['quantity'],
							$inventory_name,
							html_entity_decode( wp_strip_all_tags( $product->get_name() ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
							$args['order_id']
						);
					}

					$data[ $inventory_id ][ $notification ] = FALSE;
					AtumCache::set_cache( $cache_key, $data, ATUM_MULTINV_TEXT_DOMAIN );

					break;
				}

			}

		}

		return $message;

	}

	/**
	 * Modify WC Order statuses that can change the stock
	 *
	 * @since 1.4.9
	 *
	 * @param array $statuses
	 *
	 * @return array
	 */
	public function modify_changing_stock_order_statuses( $statuses ) {

		return [ 'wc-completed' ];

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
	 * @return Hooks instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
