<?php
/**
 * Multi-Inventory customizations for the ATUM List Tables
 *
 * @package     AtumMultiInventory\Inc
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @since       1.0.1
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumCache;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumListTables\AtumListTable;
use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\StockCentral\Lists\ListTable as StockCentralList;
use Atum\StockCentral\StockCentral;
use AtumMultiInventory\Models\Inventory;
use Atum\Settings\Settings as AtumSettings;
use AtumLevels\ManufacturingCentral\ManufacturingCentral;
use AtumLevels\ManufacturingCentral\Lists\ListTable as ManufacturingCentralList;
use AtumMultiInventory\MultiInventory;


class ListTables {

	/**
	 * The singleton instance holder
	 *
	 * @var ListTables
	 */
	private static $instance;

	/**
	 * List of products with their inventories displayed on the current page
	 *
	 * @var array
	 */
	protected $inventories = [];

	/**
	 * If any displayed inventory was edited, will be added here
	 *
	 * @var Inventory[]
	 */
	protected $edited_inventories = [];

	/**
	 * If any displayed product was edited, will be added here
	 *
	 * @var array
	 */
	protected $edited_products = [];

	/**
	 * Totals for the products with MI enabled
	 *
	 * @var array
	 */
	protected $totalizers = [
		'_stock'                    => 0,
		'calc_available_to_produce' => 0,
		'_out_stock_threshold'      => 0,
		'_sales_last_days'          => 0,
		'_lost_sales'               => 0,
		'_inbound_stock'            => 0,
		'_stock_on_hold'            => 0,
		'_reserved_stock'           => 0,
		'_customer_returns'         => 0,
		'_warehouse_damage'         => 0,
		'_lost_in_post'             => 0,
		'_other_logs'               => 0,
	];

	/**
	 * Time of query
	 *
	 * @var string
	 */
	protected $day;

	/**
	 * If there is a MI column search running, the column will be saved here
	 *
	 * @var string
	 */
	protected $searched_column = '';

	/**
	 * If there is a MI column search running, the search terms will be saved here
	 *
	 * @var array
	 */
	protected $searched_terms = [];


	/**
	 * Searchable columns and their types
	 */
	const SEARCHABLE_COLUMNS = array(
		'string'  => array(
			'mi_inventory_date',
			'mi_bbe_date',
			'mi_lot',
			'mi_sku',
			'mi_supplier_sku',
		),
		'numeric' => array(
			'mi_priority',
			'mi_expiry_days',
		),
	);


	/**
	 * ListTables singleton constructor
	 *
	 * @since 1.0.1
	 */
	private function __construct() {

		$timestamp = AtumHelpers::get_current_timestamp();
		$this->day = AtumHelpers::date_format( $timestamp, TRUE );

		if ( is_admin() ) {

			// Add the MI columns to the ListTables.
			add_filter( 'atum/stock_central_list/table_columns', array( $this, 'add_mi_columns' ), 100 );
			add_filter( 'atum/manufacturing_list_table/table_columns', array( $this, 'add_mi_columns' ), 100 );
			add_filter( 'atum/stock_central_list/column_group_members', array( $this, 'add_mi_group' ) );
			add_filter( 'atum/manufacturing_list_table/column_group_members', array( $this, 'add_mi_group' ) );
			add_filter( 'atum/list_table/column_default_calc_mi_status', array( $this, 'mi_column_status' ), 10, 3 );
			add_filter( 'atum/stock_central_list/searchable_columns', array( $this, 'add_searchable_columns' ) );
			add_filter( 'atum/product_levels/manufacturing_list_table/searchable_columns', array( $this, 'add_searchable_columns' ) );

			// Hack the existing ListTable columns for inventories.
			// NOTE: Actually, it isn't possible to get the Manufacturing Central columns here because the PL plugin is still not instantiated.
			$specific_mc_cols = [ 'calc_available_to_produce' ];
			$cols             = array_merge( array_keys( StockCentralList::get_table_columns() ), $specific_mc_cols );
			foreach ( $cols as $table_column ) {
				add_filter( "atum/list_table/column_source_object/_column_{$table_column}", array( $this, 'maybe_inventory_column' ), 10, 2 );
				add_filter( "atum/list_table/column_source_object/column_{$table_column}", array( $this, 'maybe_inventory_column' ), 10, 2 );
			}

			// Add the MI products filter to list tables (only makes sense when the MI is not enabled for all the products).
			if ( 'no' === AtumHelpers::get_option( 'mi_default_multi_inventory', 'no' ) && 'yes' === AtumHelpers::get_option( 'mi_list_tables_filter', 'yes' ) ) {
				add_filter( 'woocommerce_products_admin_list_table_filters', array( $this, 'add_mi_products_filter_to_products_list' ) );
				add_filter( 'posts_clauses', array( $this, 'mi_products_filter_request_post_clauses' ), 100 );
				add_action( 'atum/list_table/after_nav_filters', array( $this, 'render_mi_products_filter' ), 100 );
			}

			// Add the inventories as child rows for each List Table product.
			add_action( 'atum/list_table/after_single_row', array( $this, 'add_inventories_to_products' ), 10, 2 );
			add_action( 'atum/list_table/after_single_expandable_row', array( $this, 'add_inventories_to_products' ), 10, 2 );

			// Hack the ListTable cells of products with inner inventories.
			add_filter( 'atum/list_table/column_regular_price', array( $this, 'mi_product_price' ), 10, 3 );
			add_filter( 'atum/list_table/column_sale_price', array( $this, 'mi_product_price' ), 10, 3 );
			add_filter( 'atum/list_table/column_purchase_price', array( $this, 'mi_product_price' ), 10, 4 );
			add_filter( 'atum/list_table/column_gross_profit', array( $this, 'mi_product_price' ), 10, 4 );
			add_filter( 'atum/list_table/column_sku', array( $this, 'mi_product_empty_col' ), 10, 4 );
			add_filter( 'atum/list_table/column_supplier', array( $this, 'mi_product_empty_col' ), 10, 4 );
			add_filter( 'atum/list_table/column_supplier_sku', array( $this, 'mi_product_empty_col' ), 10, 4 );
			add_filter( 'atum/list_table/column_locations', array( $this, 'mi_product_empty_col' ), 10, 4 );
			add_filter( 'atum/list_table/column_out_stock_threshold', array( $this, 'mi_product_empty_col' ), 10, 4 );
			add_filter( 'atum/list_table/column_stock', array( $this, 'mi_product_stock' ), 10, 4 );
			add_filter( 'atum/list_table/column_stock_indicator', array( $this, 'mi_product_stock_indicator' ), 10, 4 );
			add_filter( 'atum/list_table/column_back_orders', array( $this, 'mi_product_backorders' ), 10, 4 );
			add_filter( 'atum/stock_central_list/column_stock_will_last_days', array( $this, 'mi_product_empty_col' ), 10, 4 );

			// Hack the totals columns for products with MI enabled.
			add_filter( 'atum/list_table/totalizers', array( $this, 'add_inventory_totals' ) );

			// Add the multi inventory status column to the sticky columns.
			add_filter( 'atum/stock_central_list/sticky_columns', array( $this, 'sticky_columns' ) );

			// Enqueue scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

			// Add styles to ATUM reports.
			add_filter( 'atum/data_export/report_styles', array( $this, 'load_report_styles' ) );

			// Allow searching MI data within List Tables.
			add_filter( 'atum/list_table/product_search/where', array( $this, 'search_mi_data' ), 10, 5 );

			// Add MI help to SC.
			add_action( 'atum/help_tabs/stock_central/after_product_details', array( $this, 'add_mi_status_column_help' ) );

			// Add MI row actions to SC and MC.
			add_filter( 'atum/stock_central_list/row_actions', array( $this, 'add_mi_row_actions' ) );
			add_filter( 'atum/product_levels/manufacturing_list_table/row_actions', array( $this, 'add_mi_row_actions' ) );

			// Add the "add inventory" modal's JS template to List Tables.
			add_action( 'atum/list_table/after_display', array( $this, 'load_add_inventory_modal_template' ) );

			// Manage the inventory locations shown in SC.
			add_action( 'atum/ajax/stock_central_list/get_locations_tree', array( $this, 'get_locations_tree' ) );
			add_action( 'atum/ajax/stock_central_list/before_set_locations', array( $this, 'set_locations_tree' ), 10, 2 );

			// Sort products by MI fields.
			add_action( 'atum/list_table/before_query_data', array( $this, 'add_filter_mi_query_data' ) );
			add_action( 'atum/list_table/after_query_data', array( $this, 'remove_filter_mi_query_data' ) );

			// Filter suppliers query data.
			add_filter( 'atum/list_table/supplier_filter_query_data', array( $this, 'supplier_filter_query_data' ) );

			// Filter suppliers view data.
			add_action( 'atum/list_table/set_views_data/before_query_data', array( $this, 'add_filter_supplier_view_query_data' ) );
			add_action( 'atum/list_table/set_views_data/after_query_data', array( $this, 'remove_filter_supplier_view_query_data' ) );

		}

	}

	/**
	 * Filter the List Table columns array, to add the MI data columns
	 *
	 * @since 1.0.1
	 *
	 * @param array $table_columns
	 *
	 * @return array
	 */
	public function add_mi_columns( $table_columns ) {

		$new_table_colums = array();

		// Add the columns after the Product Details group.
		foreach ( $table_columns as $column_key => $column_value ) {

			$new_table_colums[ $column_key ] = $column_value;

			// Add the MI button col.
			if ( 'calc_type' === $column_key ) {
				$new_table_colums['calc_mi_status'] = '<span class="atum-icon atmi-multi-inventory tips" data-bs-placement="bottom" data-tip="' . esc_attr__( 'Multi-Inventory Status', ATUM_MULTINV_TEXT_DOMAIN ) . '">' . __( 'Multi-Inventory Status', ATUM_MULTINV_TEXT_DOMAIN ) . '</span>';
			}

			// Add the columns after the 'weight' column.
			if ( '_weight' === $column_key ) {

				$new_table_colums['mi_inventory_date'] = __( 'Inventory Date', ATUM_MULTINV_TEXT_DOMAIN );
				$new_table_colums['mi_bbe_date']       = __( 'BBE Date', ATUM_MULTINV_TEXT_DOMAIN );
				$new_table_colums['mi_expiry_days']    = __( 'Expiry Days', ATUM_MULTINV_TEXT_DOMAIN );
				$new_table_colums['mi_priority']       = __( 'Priority', ATUM_MULTINV_TEXT_DOMAIN );
				$new_table_colums['mi_lot']            = __( 'LOT/Batch', ATUM_MULTINV_TEXT_DOMAIN );

				if ( 'no-restriction' !== Helpers::get_region_restriction_mode() ) {
					$new_table_colums['calc_mi_regions'] = '<span class="atum-icon atmi-earth tips" data-bs-placement="bottom" data-tip="' . esc_attr__( 'Regions', ATUM_MULTINV_TEXT_DOMAIN ) . '">' . esc_attr__( 'Regions', ATUM_MULTINV_TEXT_DOMAIN ) . '</span>';
				}

			}

		}

		return $new_table_colums;

	}

	/**
	 * Add the Multi-Inventory group to List Tables
	 *
	 * @since 1.0.1
	 *
	 * @param array $groups
	 *
	 * @return array
	 */
	public function add_mi_group( $groups ) {

		$new_table_groups = array();

		foreach ( $groups as $group_key => $group ) {

			$new_table_groups[ $group_key ] = $group;

			if ( 'product-details' === $group_key ) {

				// Add the MI status columns to Product Details group.
				$new_table_groups[ $group_key ]['members'][] = 'calc_mi_status';

				$group_members = array(
					'mi_inventory_date',
					'mi_bbe_date',
					'mi_expiry_days',
					'mi_priority',
					'mi_lot',
				);

				if ( 'no-restriction' !== Helpers::get_region_restriction_mode() ) {
					$group_members[] = 'calc_mi_regions';
				}

				// Add the MI columns as an independent group after Product Details.
				$new_table_groups['multi-inventory'] = array(
					'title'     => __( 'Multi-Inventory Details', ATUM_MULTINV_TEXT_DOMAIN ),
					'toggler'   => TRUE,
					'collapsed' => TRUE,
					'members'   => $group_members,
				);
			}

		}

		return $new_table_groups;

	}

	/**
	 * Add the MI columns to the list table's searchable columns
	 *
	 * @since 1.4.9
	 *
	 * @param array $searchable_columns
	 *
	 * @return array
	 */
	public function add_searchable_columns( $searchable_columns ) {
		return array_merge_recursive( $searchable_columns, self::SEARCHABLE_COLUMNS );
	}

	/**
	 * Add the MI Status to ListTables
	 *
	 * @since 1.0.1
	 *
	 * @param string      $column_item
	 * @param \WP_Post    $item
	 * @param \WC_Product $product
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function mi_column_status( $column_item, $item, $product ) {

		// When applying the mi_products filter and trying to hide the MI products, the MI status icons makes no sense.
		if ( ! empty( $_GET['mi_products'] ) && 'non_mi' === $_GET['mi_products'] ) {
			return $column_item;
		}

		$product_type         = $product->get_type();
		$inheritable_products = Globals::get_inheritable_product_types();

		if (
			$item instanceof Inventory || ( in_array( $product_type, $inheritable_products ) && 'bundle' !== $product_type ) ||
			'yes' !== Helpers::get_product_multi_inventory_status( $product ) || ! Helpers::is_product_multi_inventory_compatible( $product )
		) {

			// If any of the inner products have multi-inventory enabled, show an icon noticing it.
			if ( in_array( $product_type, $inheritable_products ) ) {

				$children         = $product->get_children();
				$children_with_mi = FALSE;

				foreach ( $children as $child_id ) {
					if ( 'yes' === Helpers::get_product_multi_inventory_status( $child_id ) && Helpers::is_product_multi_inventory_compatible( $child_id ) ) {
						$children_with_mi = TRUE;
						break;
					}
				}

				if ( $children_with_mi ) {
					$column_item = '<span class="multi-inventory-children tips" data-tip="' . esc_attr__( 'Inner items with Multi-Inventory enabled', ATUM_MULTINV_TEXT_DOMAIN ) . '">
									<i class="atum-icon atmi-multi-inventory"></i></span>';
				}
				else {
					$column_item = AtumListTable::EMPTY_COL;
				}

			}
			else {
				$column_item = AtumListTable::EMPTY_COL;
			}

		}
		else {

			if ( ! empty( $_REQUEST['orderby'] ) ) {
				$order   = ( isset( $_REQUEST['order'] ) && 'asc' === $_REQUEST['order'] ) ? 'ASC' : 'DESC';
				$orderby = array(
					'field' => $_REQUEST['orderby'],
					'order' => $order,
				);
			} else {
				$orderby = FALSE;
			}

			$product_id  = $product->get_id();
			$inventories = Helpers::get_product_inventories_sorted( $product_id, TRUE, FALSE, $orderby );

			// If searching, filter the inventories shown.
			if ( $this->searched_column && ! empty( $this->searched_terms ) ) {

				foreach ( $inventories as $key => $inventory ) {

					if ( ! $this->maybe_show_inventory( $inventory ) ) {
						unset( $inventories[ $key ] );
					}

				}

			}
			// When filtering by supplier, hide the inventories not matching with it.
			elseif ( ! empty( $_GET['supplier'] ) ) {

				$filtered_supplier = absint( $_GET['supplier'] );

				foreach ( $inventories as $key => $inventory ) {

					if ( $inventory->supplier_id !== $filtered_supplier ) {
						unset( $inventories[ $key ] );
					}

				}

			}

			$count = count( $inventories );

			// Save it in the class prop for later use.
			$this->inventories[ $product_id ] = $inventories;

			$column_item = '<span class="multi-inventory has-child tips" data-tip="' . esc_attr__( 'Click to show/hide inventories', ATUM_MULTINV_TEXT_DOMAIN ) . '">
				<i class="atum-icon atmi-multi-inventory"></i><span class="count">' . $count . '</span></span>';

		}

		return apply_filters( 'atum/multi_inventory/list_tables/mi_status', $column_item, $item, $product );

	}

	/**
	 * Add the MI products filter to the WC products list.
	 *
	 * @since 1.3.1
	 *
	 * @param array $filters
	 *
	 * @return array
	 */
	public function add_mi_products_filter_to_products_list( $filters ) {

		$filters['mi_products'] = array( $this, 'render_mi_products_filter' ); // Add the callback method.
		return $filters;
	}

	/**
	 * Render the MI products filters on List Tables
	 *
	 * @since 1.3.1
	 *
	 * @param AtumListTable $list_table Optional. When passed we can check the ATUM List Table that it's being loaded.
	 */
	public function render_mi_products_filter( $list_table = NULL ) {

		$mi_filter = isset( $_REQUEST['mi_products'] ) ? wc_clean( wp_unslash( $_REQUEST['mi_products'] ) ) : FALSE;

		ob_start();
		?>
		<select name="mi_products"<?php echo $list_table ? ' class="wc-enhanced-select atum-enhanced-select dropdown_mi_products auto-filter"' : '' ?>>
			<option value=""<?php selected( empty( $mi_filter ) ) ?>><?php esc_html_e( 'Filter by MI status', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
			<option value="mi_only"<?php selected( $mi_filter, 'mi_only' ) ?>><?php esc_html_e( 'MI products', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
			<option value="non_mi"<?php selected( $mi_filter, 'non_mi' ) ?>><?php esc_html_e( 'Non MI products', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
		</select>
		<?php

		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Filter the products query by the MI status
	 *
	 * @since 1.3.1
	 *
	 * @param array $args Query args.
	 *
	 * @return array
	 */
	public function mi_products_filter_request_post_clauses( $args ) {

		if ( ! empty( $_GET['mi_products'] ) ) {

			global $wpdb;

			$atum_products_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

			$args['join'] .= " 
				LEFT JOIN $atum_products_table atum_mi_apd ON $wpdb->posts.ID = atum_mi_apd.product_id
			";

			$mi_meta_col = 'atum_mi_apd.multi_inventory';

			$where_clause     = 'mi_only' === $_GET['mi_products'] ? "$mi_meta_col = 1" : "($mi_meta_col = 0 OR $mi_meta_col IS NULL)";
			$variations_where = " OR $wpdb->posts.ID IN (
				SELECT DISTINCT p.post_parent FROM $wpdb->posts p
				LEFT JOIN $atum_products_table atum_mi_apd ON p.ID = atum_mi_apd.product_id
				WHERE $where_clause AND p.post_type = 'product_variation'
			)";

			$args['where'] .= " AND ($where_clause $variations_where)";

		}

		return $args;

	}

	/**
	 * Add the inventories as child rows to List Table products
	 *
	 * @since 1.0.1
	 *
	 * @param \WP_Post      $item
	 * @param AtumListTable $list_table
	 *
	 * @throws \Exception
	 */
	public function add_inventories_to_products( $item, $list_table ) {

		$product        = $list_table->get_current_product();
		$product_id     = $product->get_id();
		$has_compounded = in_array( $product->get_type(), MultiInventory::get_compatible_child_types() ) ? 'has-compounded ' : '';

		if ( 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product, TRUE, TRUE ) ) {

			if ( ! empty( $_REQUEST['orderby'] ) ) {
				$order   = ( isset( $_REQUEST['order'] ) && 'asc' === $_REQUEST['order'] ) ? 'ASC' : 'DESC';
				$orderby = array(
					'field' => $_REQUEST['orderby'],
					'order' => $order,
				);
			} else {
				$orderby = FALSE;
			}

			$inventories = isset( $this->inventories[ $product_id ] ) ? $this->inventories[ $product_id ] : Helpers::get_product_inventories_sorted( $product_id, TRUE, FALSE, $orderby );

			if ( ! empty( $inventories ) ) {

				$extra_indent = in_array( $product->get_type(), Globals::get_child_product_types() ) ? ' extra-indent' : '';
				$row_style    = 'yes' !== AtumHelpers::get_option( 'expandable_rows', 'no' ) ? ' style="display: none"' : '';

				// Check if the user is searching by MI column.
				foreach ( $inventories as $inventory ) {

					$data = ' data-id="' . $product_id . ':' . $inventory->id . '"';

					if ( $inventory->is_main() ) {
						$data .= ' data-is-main="yes"';
					}

					echo '<tr' . $data . ' class="mi-row ' . $has_compounded . $extra_indent . '"' . $row_style . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					// Passing the inventory as param will force the ListTable to get the columns from this class methods.
					$list_table->single_row_columns( $inventory );
					echo '</tr>';
				}

			}

			// Save the edited inventories for the current product.
			if ( ! empty( $this->edited_inventories ) ) {

				foreach ( $this->edited_inventories as $id => $edited_inventory ) {
					$edited_inventory->save();
					unset( $this->edited_inventories[ $id ] );
				}

			}

		}

	}

	/**
	 * Filter the inventories to be shown if there is a search running
	 *
	 * @since 1.2.3
	 *
	 * @param Inventory $inventory
	 *
	 * @return bool
	 */
	protected function maybe_show_inventory( $inventory ) {

		foreach ( $this->searched_terms as $searched_term ) {

			// Remove the prefix from the column name.
			$prop_name    = strpos( $this->searched_column, 'mi_' ) === 0 ? str_replace( 'mi_', '', $this->searched_column ) : $this->searched_column;
			$column_value = $inventory->__get( $prop_name );

			if ( ! is_wp_error( $column_value ) ) {

				// String terms.
				if ( in_array( $this->searched_column, self::SEARCHABLE_COLUMNS['string'] ) ) {

					if ( FALSE !== strpos( strtolower( $column_value ), strtolower( $searched_term ) ) ) {
						return TRUE;
					}

				}
				// Numeric terms (actually, only int values are being used).
				else {

					if ( intval( $column_value ) === intval( $searched_term ) ) {
						return TRUE;
					}

				}

			}

		}

		return FALSE;

	}

	/**
	 * Don't show the stock-related columns for MI products
	 *
	 * @since 1.0.1
	 *
	 * @param mixed         $value
	 * @param \WP_Post      $item
	 * @param \WC_Product   $product
	 * @param AtumListTable $list_table
	 *
	 * @return mixed
	 */
	public function mi_product_empty_col( $value, $item, $product, $list_table ) {

		if (
			( $list_table instanceof StockCentralList || $list_table instanceof ManufacturingCentralList ) &&
			'yes' === Helpers::get_product_multi_inventory_status( $product ) &&
			Helpers::is_product_multi_inventory_compatible( $product )
		) {
			return AtumListTable::EMPTY_COL;
		}

		return $value;
	}

	/**
	 * Don't show the editable prices for MI products with multi-price enabled
	 *
	 * @since 1.0.1
	 *
	 * @param string        $price
	 * @param \WP_Post      $item
	 * @param \WC_Product   $product
	 * @param AtumListTable $list_table
	 *
	 * @return string
	 */
	public function mi_product_price( $price, $item, $product, $list_table = NULL ) {

		if (
			( ! $list_table || ( $list_table && (
				$list_table instanceof StockCentralList || $list_table instanceof ManufacturingCentralList
			) ) ) &&
			Helpers::has_multi_price( $product )
		) {
			return AtumListTable::EMPTY_COL;
		}

		return $price;
	}

	/**
	 * For MI products show the total stock
	 *
	 * @since 1.0.1
	 *
	 * @param string        $stock
	 * @param \WP_Post      $item
	 * @param \WC_Product   $product
	 * @param AtumListTable $list_table
	 *
	 * @return string
	 */
	public function mi_product_stock( $stock, $item, $product, $list_table = NULL ) {

		$product_type          = $product->get_type();
		$has_bom_stock_control = false;
		$has_bom_stock_control = apply_filters( 'atum/multi_inventory/is_bom_stock_control_enabled', $has_bom_stock_control );
		$has_bom               = apply_filters( 'atum/multi_inventory/list_tables/inventory_has_bom', $product->get_id() );

		if (
			'bundle' !== $product_type && 'yes' === Helpers::get_product_multi_inventory_status( $product ) &&
			Helpers::is_product_multi_inventory_compatible( $product, TRUE, TRUE ) &&
			( $list_table instanceof StockCentralList || $list_table instanceof ManufacturingCentralList )
		) {

			$inventories = Inventory::get_product_inventories( $product->get_id(), '', FALSE, TRUE );
			if ( ! empty( $inventories ) ) {

				$stock = 0;

				if ( $list_table instanceof ManufacturingCentralList && $has_bom_stock_control ) {

					foreach ( $inventories as $inventory ) {

						if ( ( count( $inventories ) === 1 && ! $has_bom ) || ! $inventory->is_main() ) {
							$stock += $inventory->stock_quantity;
						}
						elseif ( count( $inventories ) >= 1 && ! $has_bom ) {
							$stock += $inventory->stock_quantity;
						}

					}

				}
				else {

					foreach ( $inventories as $inventory ) {
						$stock += $inventory->stock_quantity;
					}

				}

				$this->edited_products[ $product->get_id() ] = [ 'stock' => $stock ];

			}
			else {
				return AtumListTable::EMPTY_COL;
			}

		}

		elseif (
			'bundle' !== $product_type && 'no' === Helpers::get_product_multi_inventory_status( $product ) &&
			Helpers::is_product_multi_inventory_compatible( $product, TRUE, TRUE ) &&
			( $list_table instanceof ManufacturingCentralList ) && $has_bom && $has_bom_stock_control
		) {
			$stock = 0;
		}

		return $stock;

	}
	/**
	 * For MI products show the back orders if applies
	 *
	 * @since 1.3.7
	 *
	 * @param int|string    $back_orders
	 * @param \WP_Post      $item
	 * @param \WC_Product   $product
	 * @param AtumListTable $list_table
	 *
	 * @return string
	 */
	public function mi_product_backorders( $back_orders, $item, $product, $list_table ) {

		$product_id = $product->get_id();

		if ( ! empty( $this->edited_products[ $product_id ] ) ) {

			// Allow to bypass the MI's get_stock_status.
			add_filter( 'atum/multi_inventory/bypass_mi_get_stock_status', '__return_false' );
			$wc_stock_status                                      = $product->get_stock_status();
			$this->edited_products[ $product_id ]['stock_status'] = $wc_stock_status;
			remove_filter( 'atum/multi_inventory/bypass_mi_get_stock_status', '__return_false' );

			if ( 'onbackorder' === $wc_stock_status ) {

				// it was calculated in mi_product_stock.
				$back_orders = $this->edited_products[ $product_id ]['stock'];
			}

		}

		return $back_orders;

	}

	/**
	 * For MI products show the stock indicator
	 *
	 * @since 1.2.3
	 *
	 * @param string        $stock_indicator
	 * @param \WP_Post      $item
	 * @param \WC_Product   $product
	 * @param AtumListTable $list_table
	 *
	 * @return string
	 */
	public function mi_product_stock_indicator( $stock_indicator, $item, $product, $list_table = NULL ) {

		$product_id = $product->get_id();

		if ( ! empty( $this->edited_products[ $product_id ] ) ) {

			// it was calculated in mi_product_backorders.
			$wc_stock_status = $this->edited_products[ $product_id ]['stock_status'];

			$icon_class = '';

			switch ( $wc_stock_status ) {
				case 'instock':
					$data_tip        = ! $list_table::is_report() ? ' data-tip="' . esc_attr__( 'In Stock', ATUM_MULTINV_TEXT_DOMAIN ) . '"' : '';
					$stock_indicator = '<span class="atum-icon atmi-checkmark-circle tips"' . $data_tip . '></span>';
					$icon_class      = ' cell-green';
					break;

				case 'outofstock':
					$data_tip        = ! $list_table::is_report() ? ' data-tip="' . esc_attr__( 'Out of Stock', ATUM_MULTINV_TEXT_DOMAIN ) . '"' : '';
					$stock_indicator = '<span class="atum-icon atmi-cross-circle tips"' . $data_tip . '></span>';
					$icon_class      = ' cell-red';
					break;

				case 'onbackorder':
					$data_tip        = ! $list_table::is_report() ? ' data-tip="' . esc_attr__( 'On Backorder', ATUM_MULTINV_TEXT_DOMAIN ) . '"' : '';
					$stock_indicator = '<span class="atum-icon atmi-circle-minus tips"' . $data_tip . '></span>';
					$icon_class      = ' cell-yellow';
					break;
			}

			// TODO: LOW STOCK??

			$adjust_classes = function ( $classes, $product ) use ( $icon_class, &$adjust_classes ) {

				$classes = str_replace( [ 'cell-red', 'cell-yellow', 'cell-green' ], '', $classes );
				// We have to remove the filter to not affect other products when it's set for the first time.
				remove_filter( 'atum/list_table/column_stock_indicator_classes', $adjust_classes );

				return $classes . $icon_class;

			};

			// Add the cell class for the icon color.
			add_filter( 'atum/list_table/column_stock_indicator_classes', $adjust_classes, 10, 2 );

		}

		return $stock_indicator;

	}

	/**
	 * Increase the total of the specified column by the specified amount
	 *
	 * @since 1.0.7.4
	 *
	 * @param string    $column_name
	 * @param int|float $amount
	 * @param Inventory $inventory
	 */
	protected function increase_total( $column_name, $amount, $inventory ) {

		if ( $inventory->is_main() ) {
			return;
		}
		if ( isset( $this->totalizers[ $column_name ] ) && is_numeric( $amount ) ) {
			$this->totalizers[ $column_name ] += floatval( $amount );
		}
	}

	/**
	 * Add the inventory totals if needed
	 *
	 * @since 1.0.7.4
	 *
	 * @param array $totalizers
	 *
	 * @return array
	 */
	public function add_inventory_totals( $totalizers ) {

		foreach ( $totalizers as $column => $total ) {

			if ( isset( $this->totalizers[ $column ] ) ) {
				$totalizers[ $column ] += $this->totalizers[ $column ];
			}

		}

		return $totalizers;

	}

	/**
	 * Return this object to retrieve the right column methods for inventories
	 *
	 * @since 1.0.1
	 *
	 * @param AtumListTable $list_table
	 * @param object        $item
	 *
	 * @return object
	 */
	public function maybe_inventory_column( $list_table, $item ) {

		// TODO: WHAT IF IS THE MAIN INVENTORY?
		if ( $item instanceof Inventory ) {
			return $this;
		}

		return $list_table;

	}

	/**
	 * Column for Inventory thumbnails
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return string
	 */
	public function column_thumb( $inventory ) {
		return apply_filters( 'atum/multi_inventory/list_tables/column_thumb', '', $inventory );
	}

	/**
	 * Column for inventory IDs
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return string
	 */
	public function column_id( $inventory ) {
		return apply_filters( 'atum/multi_inventory/list_tables/column_id', $inventory->id, $inventory );
	}

	/**
	 * Column for inventory names
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return string
	 */
	public function column_title( $inventory ) {

		$title = '<span><i class="atum-icon atmi-arrow-child"></i><i class="atum-icon atmi-multi-inventory"></i></span> ' . $inventory->name;
		return apply_filters( 'atum/multi_inventory/list_tables/column_title', $title, $inventory );
	}

	/**
	 * Column for inventory SKUs
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 * @param bool      $editable  Whether the SKU will be editable.
	 *
	 * @return string
	 */
	public function column__sku( $inventory, $editable = TRUE ) {

		$sku = $inventory->sku;
		$sku = $sku ?: AtumListTable::EMPTY_COL;

		if ( $editable ) {

			$args = array(
				'meta_key'   => 'sku',
				'value'      => $sku,
				'input_type' => 'text',
				'tooltip'    => esc_attr__( 'Click to edit the inventory SKU', ATUM_MULTINV_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Inventory SKU', ATUM_MULTINV_TEXT_DOMAIN ),
				'extra_data' => array(
					'inventory_id' => $inventory->id,
				),
			);

			$sku = AtumListTable::get_editable_column( $args );

		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_sku', $sku, $inventory );

	}

	/**
	 * Column for inventory Suppliers
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return string
	 */
	public function column__supplier( $inventory ) {

		$supplier = AtumListTable::EMPTY_COL;

		if ( ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			return $supplier;
		}

		$supplier_id = $inventory->supplier_id;

		if ( $supplier_id ) {

			$supplier_post = get_post( $supplier_id );

			if ( $supplier_post ) {

				$supplier        = $supplier_post->post_title;
				$supplier_length = absint( apply_filters( 'atum/list_table/column_supplier_length', 20 ) );
				$supplier_abb    = mb_strlen( $supplier ) > $supplier_length ? trim( mb_substr( $supplier, 0, $supplier_length ) ) . '...' : $supplier;
				/* translators: first one is the supplier name and second is the supplier's ID */
				$supplier_tooltip = sprintf( esc_attr__( '%1$s (ID: %2$d)', ATUM_MULTINV_TEXT_DOMAIN ), $supplier, $supplier_id );

				$supplier = '<span class="tips" data-tip="' . $supplier_tooltip . '">' . $supplier_abb . '</span><span class="atum-title-small">' . $supplier_tooltip . '</span>';

			}

		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_supplier', $supplier, $inventory );

	}

	/**
	 * Column for inventory Supplier SKUs
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 * @param bool      $editable  Optional. Whether the current column is editable.
	 *
	 * @return float
	 */
	public function column__supplier_sku( $inventory, $editable = TRUE ) {

		$supplier_sku = AtumListTable::EMPTY_COL;

		if ( ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			return $supplier_sku;
		}

		if ( $editable ) {

			$supplier_sku = $inventory->supplier_sku;

			if ( 0 === strlen( $supplier_sku ) ) {
				$supplier_sku = AtumListTable::EMPTY_COL;
			}

			$args = apply_filters( 'atum/multi_inventory/list_tables/args_supplier_sku', array(
				'meta_key'   => 'supplier_sku',
				'value'      => $supplier_sku,
				'input_type' => 'text',
				'tooltip'    => esc_attr__( 'Click to edit the inventory supplier Sku', ATUM_MULTINV_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Inventory Supplier SKU', ATUM_MULTINV_TEXT_DOMAIN ),
				'extra_data' => array(
					'inventory_id' => $inventory->id,
				),
			) );

			$supplier_sku = AtumListTable::get_editable_column( $args );
		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_supplier_sku', $supplier_sku, $inventory );

	}

	/**
	 * Column for inventory locations
	 *
	 * @since 1.4.9
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return string
	 */
	public function column_calc_location( $inventory ) {

		$locations            = $inventory->get_locations();
		$location_terms_class = ! empty( $locations ) ? ' not-empty' : '';

		$data_tip  = ' data-tip="' . esc_attr__( 'Show Inventory Locations', ATUM_MULTINV_TEXT_DOMAIN ) . '"';
		$locations = '<a href="#" class="show-locations inventory-locations atum-icon atmi-map-marker tips' . $location_terms_class . '"' . $data_tip . ' data-locations=""></a>';

		return apply_filters( 'atum/multi_inventory/list_tables/column_locations', $locations, $inventory );

	}

	/**
	 * Column for inventory Regular Prices
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return float
	 */
	public function column__regular_price( $inventory ) {

		$regular_price = AtumListTable::EMPTY_COL;
		$product_id    = $inventory->product_id;

		if ( Helpers::has_multi_price( $product_id ) ) {

			$regular_price_value = $inventory->regular_price;
			$regular_price_value = is_numeric( $regular_price_value ) ? AtumHelpers::format_price( $regular_price_value, [
				'trim_zeros' => TRUE,
				'currency'   => AtumListTable::get_default_currency(),
			] ) : $regular_price;

			$args = apply_filters( 'atum/multi_inventory/list_tables/args_regular_price', array(
				'meta_key'   => 'regular_price',
				'value'      => $regular_price_value,
				'symbol'     => get_woocommerce_currency_symbol(),
				'currency'   => AtumListTable::get_default_currency(),
				'tooltip'    => esc_attr__( 'Click to edit the inventory regular price', ATUM_MULTINV_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Inventory Regular Price', ATUM_MULTINV_TEXT_DOMAIN ),
				'extra_data' => array(
					'inventory_id' => $inventory->id,
				),
			) );

			$regular_price = AtumListTable::get_editable_column( $args );

		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_regular_price', $regular_price, $inventory );

	}

	/**
	 * Column for inventory Sale Prices
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return float|string
	 */
	public function column__sale_price( $inventory ) {

		$sale_price = AtumListTable::EMPTY_COL;
		$product_id = $inventory->product_id;

		if ( Helpers::has_multi_price( $product_id ) ) {

			$sale_price_value = $inventory->sale_price;
			$sale_price_value = is_numeric( $sale_price_value ) ? AtumHelpers::format_price( $sale_price_value, [
				'trim_zeros' => TRUE,
				'currency'   => AtumListTable::get_default_currency(),
			] ) : $sale_price;

			/**
			 * Variable definition
			 *
			 * @var \WC_DateTime $date_on_sale_from
			 * @var \WC_DateTime $date_on_sale_to
			 */
			$date_on_sale_from = $inventory->date_on_sale_from;
			$date_on_sale_to   = $inventory->date_on_sale_to;

			$date_on_sale_from = $date_on_sale_from ? $date_on_sale_from->date_i18n() : '';
			$date_on_sale_to   = $date_on_sale_to ? $date_on_sale_to->date_i18n() : '';

			$args = apply_filters( 'atum/multi_inventory/list_tables/args_sale_price', array(
				'meta_key'   => 'sale_price',
				'value'      => $sale_price_value,
				'symbol'     => get_woocommerce_currency_symbol(),
				'currency'   => AtumListTable::get_default_currency(),
				'tooltip'    => esc_attr__( 'Click to edit the inventory sale price', ATUM_MULTINV_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Inventory Sale Price', ATUM_MULTINV_TEXT_DOMAIN ),
				'extra_meta' => array(
					array(
						'name'        => '_sale_price_dates_from',
						'type'        => 'text',
						'placeholder' => _x( 'Inventory Sale date from...', 'placeholder', ATUM_MULTINV_TEXT_DOMAIN ) . ' YYYY-MM-DD',
						'value'       => $date_on_sale_from,
						'maxlength'   => 10,
						'pattern'     => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'class'       => 'atum-datepicker from',
					),
					array(
						'name'        => '_sale_price_dates_to',
						'type'        => 'text',
						'placeholder' => _x( 'Inventory Sale date to...', 'placeholder', ATUM_MULTINV_TEXT_DOMAIN ) . ' YYYY-MM-DD',
						'value'       => $date_on_sale_to,
						'maxlength'   => 10,
						'pattern'     => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'class'       => 'atum-datepicker to',
					),
				),
				'extra_data' => array(
					'inventory_id' => $inventory->id,
				),
			) );

			$sale_price = AtumListTable::get_editable_column( $args );

		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_sale_price', $sale_price, $inventory );

	}

	/**
	 * Column for inventory Purchase Prices
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return float|string
	 */
	public function column__purchase_price( $inventory ) {

		$purchase_price = AtumListTable::EMPTY_COL;

		if ( ! AtumCapabilities::current_user_can( 'view_purchase_price' ) ) {
			return $purchase_price;
		}

		$product_id = $inventory->product_id;

		if ( Helpers::has_multi_price( $product_id ) ) {

			$purchase_price_value = $inventory->purchase_price;
			$purchase_price_value = is_numeric( $purchase_price_value ) ? AtumHelpers::format_price( $purchase_price_value, [
				'trim_zeros' => TRUE,
				'currency'   => AtumListTable::get_default_currency(),
			] ) : $purchase_price;

			$args = apply_filters( 'atum/multi_inventory/list_tables/args_purchase_price', array(
				'meta_key'   => 'purchase_price',
				'value'      => $purchase_price_value,
				'symbol'     => get_woocommerce_currency_symbol(),
				'currency'   => AtumListTable::get_default_currency(),
				'tooltip'    => esc_attr__( 'Click to edit the Inventory purchase price', ATUM_MULTINV_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Inventory Purchase Price', ATUM_MULTINV_TEXT_DOMAIN ),
				'extra_data' => array(
					'inventory_id' => $inventory->id,
				),
			) );

			$purchase_price = AtumListTable::get_editable_column( $args );

		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_purchase_price', $purchase_price, $inventory );

	}

	/**
	 * Column for gross profit
	 *
	 * @since 1.4.8
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return string
	 */
	public function column_calc_gross_profit( $inventory ) {

		$gross_profit = AtumListTable::EMPTY_COL;

		if ( ! AtumCapabilities::current_user_can( 'view_purchase_price' ) ) {
			return $gross_profit;
		}

		$product_id = $inventory->product_id;

		if ( Helpers::has_multi_price( $product_id ) ) {

			$purchase_price = (float) $inventory->purchase_price;
			$regular_price  = (float) $inventory->regular_price;

			// Exclude rates if prices includes them.
			if ( 'yes' === get_option( 'woocommerce_prices_include_tax' ) ) {
				$product        = wc_get_product( $product_id );
				$base_tax_rates = \WC_Tax::get_base_tax_rates( $product->get_tax_class() );
				$base_pur_taxes = \WC_Tax::calc_tax( $purchase_price, $base_tax_rates, true );
				$base_reg_taxes = \WC_Tax::calc_tax( $regular_price, $base_tax_rates, true );
				$purchase_price = round( $purchase_price - array_sum( $base_pur_taxes ), absint( get_option( 'woocommerce_price_num_decimals' ) ) );
				$regular_price  = round( $regular_price - array_sum( $base_reg_taxes ), absint( get_option( 'woocommerce_price_num_decimals' ) ) );
			}

			if ( $purchase_price > 0 && $regular_price > 0 ) {
				$gross_profit_value      = wp_strip_all_tags( wc_price( $regular_price - $purchase_price ) );
				$gross_profit_percentage = wc_round_discount( ( 100 - ( ( $purchase_price * 100 ) / $regular_price ) ), 2 );

				if ( 'percentage' === AtumHelpers::get_option( 'gross_profit', 'percentage' ) ) {
					$gross_profit = '<span class="tips" data-tip="' . $gross_profit_value . '">' . $gross_profit_percentage . '%</span>';
				}
				else {
					$gross_profit = '<span class="tips" data-tip="' . $gross_profit_percentage . '%">' . $gross_profit_value . '</span>';
				}
			}

		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_calc_gross_profit', $gross_profit, $inventory );
	}

	/**
	 * Column for inventory regions
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return string
	 */
	public function column_calc_mi_regions( $inventory ) {

		$region_restriction = Helpers::get_region_restriction_mode();

		if ( 'no-restriction' === $region_restriction ) {
			$regions = AtumListTable::EMPTY_COL;
		}
		else {

			$regions       = $inventory->region;
			$regions_class = ! empty( $regions ) ? ' not-empty' : '';

			$data_tip = ' data-tip="' . esc_attr__( 'Show Inventory Regions', ATUM_MULTINV_TEXT_DOMAIN ) . '"';
			$regions  = '<a href="#" class="show-inventory-regions atum-icon atmi-earth tips' . $regions_class . '"' . $data_tip . ' data-regions=""></a>';

		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_calc_mi_regions', $regions, $inventory );

	}

	/**
	 * Column for inventory date
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 * @param bool      $editable Whether the SKU will be editable.
	 *
	 * @return string
	 */
	public function column_mi_inventory_date( $inventory, $editable = TRUE ) {

		$inventory_date = $inventory->inventory_date ? $inventory->inventory_date->date( 'Y-m-d H:i' ) : AtumListTable::EMPTY_COL;

		if ( $editable ) {

			$args = array(
				'meta_key'   => 'inventory_date',
				'value'      => $inventory_date,
				'input_type' => 'text',
				'tooltip'    => esc_attr__( 'Click to edit the inventory date', ATUM_MULTINV_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Inventory Date', ATUM_MULTINV_TEXT_DOMAIN ),
				'extra_data' => array(
					'has-datepicker' => 'yes',
					'date-format'    => 'YYYY-MM-DD HH:mm',
					'min-date'       => 'false',
				),
			);

			$inventory_date = AtumListTable::get_editable_column( $args );

		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_mi_inventory_date', $inventory_date, $inventory );

	}

	/**
	 * Column for BBE date
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 * @param bool      $editable  Whether the SKU will be editable.
	 *
	 * @return string
	 */
	public function column_mi_bbe_date( $inventory, $editable = TRUE ) {

		$bbe_date = $inventory->bbe_date ? $inventory->bbe_date->date( 'Y-m-d H:i' ) : AtumListTable::EMPTY_COL;

		if ( $editable ) {

			$args = array(
				'meta_key'   => 'bbe_date',
				'value'      => $bbe_date,
				'input_type' => 'text',
				'tooltip'    => esc_attr__( 'Click to edit the BBE date', ATUM_MULTINV_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'BBE Date', ATUM_MULTINV_TEXT_DOMAIN ),
				'extra_data' => array(
					'has-datepicker' => 'yes',
					'date-format'    => 'YYYY-MM-DD HH:mm',
					'min-date'       => 'moment',
					'max-date'       => 'false',
				),
			);

			$bbe_date = AtumListTable::get_editable_column( $args );

		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_mi_bbe_date', $bbe_date, $inventory );

	}

	/**
	 * Column for Expiry days
	 *
	 * @since 1.3.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return string
	 */
	public function column_mi_expiry_days( $inventory ) {
		$expiry_days = $inventory->bbe_date && $inventory->expiry_days ? $inventory->expiry_days : AtumListTable::EMPTY_COL;
		return apply_filters( 'atum/multi_inventory/list_tables/column_expiry_days', $expiry_days, $inventory );
	}

	/**
	 * Column for inventory priority
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return string
	 */
	public function column_mi_priority( $inventory ) {
		$priority = ! is_null( $inventory->priority ) ? $inventory->priority : AtumListTable::EMPTY_COL;
		return apply_filters( 'atum/multi_inventory/list_tables/column_mi_priority', $priority, $inventory );
	}

	/**
	 * Column for inventory lot/batch number
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 * @param bool      $editable  Whether the SKU will be editable.
	 *
	 * @return string
	 */
	public function column_mi_lot( $inventory, $editable = TRUE ) {

		$lot = $inventory->lot ?: AtumListTable::EMPTY_COL;

		if ( $editable ) {

			$args = array(
				'meta_key'   => 'lot',
				'value'      => $lot,
				'input_type' => 'text',
				'tooltip'    => esc_attr__( 'Click to edit the LOT/Batch number', ATUM_MULTINV_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'LOT/Batch', ATUM_MULTINV_TEXT_DOMAIN ),
			);

			$lot = AtumListTable::get_editable_column( $args );

		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_mi_lot', $lot, $inventory );

	}

	/**
	 * Column for inventory stock amount
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 * @param bool      $editable  Whether the stock will be editable.
	 *
	 * @return string|int
	 */
	public function column__stock( $inventory, $editable = TRUE ) {

		$stock = AtumListTable::EMPTY_COL;

		$classes_title             = '';
		$tooltip_warning           = '';
		$wc_notify_no_stock_amount = get_option( 'woocommerce_notify_no_stock_amount' );
		$editable                  = apply_filters( 'atum/multi_inventory/list_tables/editable_column_stock', $editable, $inventory );

		// Do not show the stock if the inventory is not managed by WC.
		if ( ! $inventory->managing_stock() && ! $inventory->is_expired() ) {
			return $stock;
		}

		$stock            = $inventory->stock_quantity;
		$inventory_values = [
			'stock'     => $stock,
			'inventory' => $inventory,
			'increase'  => TRUE,
		];

		$inventory_values = apply_filters( 'atum/multi_inventory/list_tables/stock_with_bom_stock_control', $inventory_values );

		$stock = $inventory_values['stock'];

		if ( $inventory_values['increase'] ) {
			$this->increase_total( '_stock', $stock, $inventory );
		}

		// Settings value is on.
		$is_out_stock_threshold_managed = 'no' === AtumHelpers::get_option( 'out_stock_threshold', 'no' ) ? FALSE : TRUE;

		if ( $inventory->is_expired() ) {

			$classes_title = ' class="cell-red" title="' . esc_attr__( 'Inventory stock is expired', ATUM_MULTINV_TEXT_DOMAIN ) . '"';
			$stock         = $inventory->expired_stock ?: '-';
			$editable      = FALSE;

		}
		elseif ( $is_out_stock_threshold_managed ) {

			$out_stock_threshold = $inventory->out_stock_threshold;

			if ( strlen( $out_stock_threshold ) > 0 ) {

				if ( wc_stock_amount( $out_stock_threshold ) >= $stock ) {

					if ( ! $editable ) {
						$classes_title = ' class="cell-yellow" title="' . esc_attr__( "Inventory stock is below the 'Out of Stock Threshold'", ATUM_MULTINV_TEXT_DOMAIN ) . '"';
					}
					else {
						$classes_title   = ' class="cell-yellow"';
						$tooltip_warning = esc_attr__( "Click to edit the inventory stock quantity (it's below the 'Out of Stock Threshold')", ATUM_MULTINV_TEXT_DOMAIN );
					}

				}

			}
			elseif ( wc_stock_amount( $wc_notify_no_stock_amount ) >= $stock ) {

				if ( '&#45;' !== $stock ) {

					if ( ! $editable ) {
						$classes_title = ' class="cell-yellow" title="' . esc_attr__( "Inventory stock is below the WooCommerce's 'Out of Stock Threshold'", ATUM_MULTINV_TEXT_DOMAIN ) . '"';
					}
					else {
						$classes_title   = ' class="cell-yellow"';
						$tooltip_warning = esc_attr__( "Click to edit the inventory stock quantity (it's below the WooCommerce's 'Out of Stock Threshold')", ATUM_MULTINV_TEXT_DOMAIN );
					}

				}

			}

		}
		elseif ( wc_stock_amount( $wc_notify_no_stock_amount ) >= $stock ) {

			if ( ! $editable ) {
				$classes_title = ' class="cell-yellow" title="' . esc_attr__( "Inventory stock is below the WooCommerce's 'Out of Stock Threshold'", ATUM_MULTINV_TEXT_DOMAIN ) . '"';

			}
			else {
				$classes_title   = ' class="cell-yellow"';
				$tooltip_warning = esc_attr__( "Click to edit the inventory stock quantity (it's below WooCommerce's 'Out of Stock Threshold')", ATUM_MULTINV_TEXT_DOMAIN );
			}

		}

		if ( $editable ) {

			$args = array(
				'meta_key'   => 'stock',
				'value'      => $stock,
				'tooltip'    => $tooltip_warning ?: esc_attr__( 'Click to edit the inventory stock quantity', ATUM_MULTINV_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Inventory Stock Quantity', ATUM_MULTINV_TEXT_DOMAIN ),
				'extra_data' => array(
					'inventory_id' => $inventory->id,
				),
			);

			$stock = AtumListTable::get_editable_column( $args );

		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_stock', "<span{$classes_title}>{$stock}</span>", $inventory );

	}

	/**
	 * Column for available stock amount
	 *
	 * @since 1.5.0
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return string
	 */
	public function column_calc_available_to_produce( $inventory ) {

		$available_stock = AtumListTable::EMPTY_COL;
		$has_bom         = apply_filters( 'atum/multi_inventory/list_tables/inventory_has_bom', $inventory->product_id );

		if ( $inventory->is_main() && $has_bom ) {
			$available_stock_html = '<span class="calculated atum-tooltip" data-tip="' . esc_attr__( 'Calculated stock quantity', ATUM_LEVELS_TEXT_DOMAIN ) . '">' . $inventory->stock_quantity . '</span>';
		}
		else {
			$available_stock_html = "<span>$available_stock</span>";
		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_available_stock', $available_stock_html, $inventory );
	}

	/**
	 * Column for inventory weight
	 * NOTE: Only added here to not throw PHP notices about incorrect calls.
	 *
	 * @since 1.4.6
	 *
	 * @param Inventory $inventory The product inventory.
	 * @param bool      $editable  Optional. Whether the current column is editable.
	 *
	 * @return double|string
	 */
	public function column__weight( $inventory, $editable = TRUE ) {
		return apply_filters( 'atum/multi_inventory/list_tables/column_weight', AtumListTable::EMPTY_COL, $inventory );
	}

	/**
	 * Column for inventory out_stock_threshold
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 * @param bool      $editable  Optional. Whether the current column is editable.
	 *
	 * @return double|string
	 */
	public function column__out_stock_threshold( $inventory, $editable = TRUE ) {

		$out_stock_threshold = $inventory->out_stock_threshold;
		$out_stock_threshold = $out_stock_threshold ?: AtumListTable::EMPTY_COL;
		$manage_stock        = $inventory->manage_stock;

		if ( 'no' === $manage_stock ) {
			$editable            = FALSE;
			$out_stock_threshold = AtumListTable::EMPTY_COL;
		}

		$this->increase_total( '_out_stock_threshold', $out_stock_threshold, $inventory );

		if ( $editable ) {

			$args = array(
				'meta_key'   => 'out_stock_threshold',
				'value'      => $out_stock_threshold,
				'input_type' => 'number',
				'tooltip'    => esc_attr__( 'Click to edit the Inventory out of stock threshold', ATUM_MULTINV_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Out of Stock Threshold', ATUM_MULTINV_TEXT_DOMAIN ),
				'extra_data' => array(
					'inventory_id' => $inventory->id,
				),
			);

			$out_stock_threshold = AtumListTable::get_editable_column( $args );

		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_out_stock_threshold', $out_stock_threshold, $inventory );

	}

	/**
	 * Column for inbound stock: shows sum of inbound stock within Purchase Orders
	 *
	 * @since 1.2.0
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return float|int
	 */
	public function column__inbound_stock( $inventory ) {

		$inbound_stock = $inventory->inbound_stock;

		if ( ! is_numeric( $inbound_stock ) ) {
			$inbound_stock = Helpers::get_inventory_inbound_stock( $inventory );
			$this->maybe_add_to_edited_inventories( $inventory );
		}

		return apply_filters( 'atum/multi_inventory/column_inbound_stock', $inbound_stock, $inventory );
	}

	/**
	 * Column for stock on hold: show amount of items with pending payment.
	 *
	 * @since 1.2.0
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return float|int
	 */
	public function column__stock_on_hold( $inventory ) {

		$stock_on_hold = $inventory->stock_on_hold;

		if ( ! is_numeric( $stock_on_hold ) ) {
			$stock_on_hold = Helpers::get_inventory_stock_on_hold( $inventory );
			$this->maybe_add_to_edited_inventories( $inventory );
		}

		return apply_filters( 'atum/multi_inventory/column_stock_hold', $stock_on_hold, $inventory );
	}

	/**
	 * Column for reserved stock: sums the items within "Reserved Stock" logs
	 *
	 * @since 1.2.0
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return float|int
	 */
	public function column__reserved_stock( $inventory ) {

		$reserved_stock = $inventory->reserved_stock;

		if ( ! is_numeric( $reserved_stock ) ) {
			$reserved_stock = Helpers::get_log_item_inventory_qty( 'reserved-stock', $inventory );
			$this->maybe_add_to_edited_inventories( $inventory );
		}

		return apply_filters( 'atum/multi_inventory/column_reserved_stock', $reserved_stock, $inventory );
	}

	/**
	 * Column for customer returns: sums the items within "Reserved Stock" logs
	 *
	 * @since 1.2.0
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return int
	 */
	public function column__customer_returns( $inventory ) {

		$customer_returns = $inventory->customer_returns;

		if ( ! is_numeric( $customer_returns ) ) {
			$customer_returns = Helpers::get_log_item_inventory_qty( 'customer-returns', $inventory );
			$this->maybe_add_to_edited_inventories( $inventory );
		}

		return apply_filters( 'atum/multi_inventory/column_cutomer_returns', $customer_returns, $inventory );
	}

	/**
	 * Column for warehouse damages: sums the items within "Warehouse Damage" logs
	 *
	 * @since 1.2.0
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return int
	 */
	public function column__warehouse_damage( $inventory ) {

		$warehouse_damage = $inventory->warehouse_damage;

		if ( ! is_numeric( $warehouse_damage ) ) {
			$warehouse_damage = Helpers::get_log_item_inventory_qty( 'warehouse-damage', $inventory );
			$this->maybe_add_to_edited_inventories( $inventory );
		}

		return apply_filters( 'atum/multi_inventory/column_warehouse_damage', $warehouse_damage, $inventory );
	}

	/**
	 * Column for lost in post: sums the items within "Lost in Post" logs
	 *
	 * @since 1.2.0
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return int
	 */
	public function column__lost_in_post( $inventory ) {

		$lost_in_post = $inventory->lost_in_post;

		if ( ! is_numeric( $lost_in_post ) ) {
			$lost_in_post = Helpers::get_log_item_inventory_qty( 'lost-in-post', $inventory );
			$this->maybe_add_to_edited_inventories( $inventory );
		}

		return apply_filters( 'atum/multi_inventory/column_lost_in_post', $lost_in_post, $inventory );
	}

	/**
	 * Column for other: sums the items within "Other" logs
	 *
	 * @since 1.2.0
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return int
	 */
	public function column__other_logs( $inventory ) {

		$other_logs = $inventory->other_logs;

		if ( ! is_numeric( $other_logs ) ) {
			$other_logs = Helpers::get_log_item_inventory_qty( 'other', $inventory );
			$this->maybe_add_to_edited_inventories( $inventory );
		}

		return apply_filters( 'atum/multi_inventory/column_other_logs', $other_logs, $inventory );
	}

	/**
	 * Column for inventory item sold today
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return int
	 */
	public function column__sold_today( $inventory ) {

		$sold_today = $inventory->sold_today;

		if ( ! is_numeric( $sold_today ) || Helpers::is_inventory_data_outdated( $inventory ) ) {
			$sold_today = Helpers::get_inventory_sold_last_days( $inventory->id, 'today 00:00:00', $this->day );
			$inventory->set_data( [ 'sold_today' => $sold_today ] );
			$this->maybe_add_to_edited_inventories( $inventory );
		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_sold_today', $sold_today, $inventory );

	}

	/**
	 * Column for inventories sold during the last N days
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return int
	 */
	public function column__sales_last_days( $inventory ) {

		$sales_last_ndays = $inventory->sales_last_days;
		$sale_days        = AtumHelpers::get_sold_last_days_option();

		if (
			! is_numeric( $sales_last_ndays ) || AtumSettings::DEFAULT_SALE_DAYS !== $sale_days ||
			Helpers::is_inventory_data_outdated( $inventory )
		) {

			$sales_last_ndays = Helpers::get_inventory_sold_last_days( $inventory->id, "$this->day -$sale_days days", $this->day );
			$inventory->set_data( [ 'sales_last_days' => $sales_last_ndays ] );
			$this->maybe_add_to_edited_inventories( $inventory );

		}

		if ( ! is_numeric( $sales_last_ndays ) ) {
			$sales_last_ndays = 0;
		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_sales_last_days', $sales_last_ndays, $inventory );

	}

	/**
	 * Column for number of days the inventory stock will be sufficient to fulfill orders
	 * Formula: Current Stock Value / (Sales Last N Days / N)
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return int|string
	 */
	public function column_calc_will_last( $inventory ) {

		$sold_last_days = AtumHelpers::get_sold_last_days_option();
		$will_last      = AtumListTable::EMPTY_COL;
		$sales          = $this->column__sales_last_days( $inventory );
		$stock          = $inventory->stock_quantity;

		if ( $stock > 0 && $sales > 0 ) {
			$will_last = ceil( $stock / ( $sales / $sold_last_days ) );
		}
		elseif ( $stock > 0 ) {
			$will_last = '>30';
		}

		return apply_filters( 'atum/multi_inventory/list_tables/column_stock_will_last_days', $will_last, $inventory );

	}

	/**
	 * Column for number of days the inventory is out of stock
	 *
	 * @since 1.2.0
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return int
	 */
	public function column__out_stock_days( $inventory ) {

		$out_stock_days = $inventory->out_stock_days;

		if ( ! is_numeric( $out_stock_days ) || Helpers::is_inventory_data_outdated( $inventory ) ) {
			$out_stock_days = Helpers::get_inventory_out_stock_days( $inventory, TRUE );
			$inventory->set_data( [ 'out_stock_days' => $out_stock_days ] );
			$this->maybe_add_to_edited_inventories( $inventory );
		}

		$out_stock_days = is_numeric( $out_stock_days ) ? $out_stock_days : AtumListTable::EMPTY_COL;

		return apply_filters( 'atum/multi_inventory/column_out_stock_days', $out_stock_days, $inventory );

	}

	/**
	 * Column for lost inventory sales
	 *
	 * @since  1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return int|string
	 */
	public function column__lost_sales( $inventory ) {

		$lost_sales = $inventory->lost_sales;

		if ( ! is_numeric( $lost_sales ) || Helpers::is_inventory_data_outdated( $inventory ) ) {
			$lost_sales = Helpers::get_inventory_lost_sales( $inventory );
			$inventory->set_data( [ 'lost_sales' => $lost_sales ] );
			$this->maybe_add_to_edited_inventories( $inventory );
		}

		$lost_sales = is_numeric( $lost_sales ) ? AtumHelpers::format_price( $lost_sales, [ 'trim_zeros' => TRUE ] ) : AtumListTable::EMPTY_COL;

		return apply_filters( 'atum/multi_inventory/list_tables/column_lost_sales', $lost_sales, $inventory );

	}

	/**
	 * Checks if the inventory is on the edited inventories array and, if not, add it.
	 *
	 * @since 1.2.0
	 *
	 * @param Inventory $inventory
	 */
	protected function maybe_add_to_edited_inventories( $inventory ) {
		// Add it to edited array for saving it later.
		if ( ! array_key_exists( $inventory->id, $this->edited_inventories ) ) {
			$this->edited_inventories[ $inventory->id ] = $inventory;
		}
	}

	/**
	 * Column for BOM hierarchy
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 *
	 * @return string
	 */
	public function column_calc_hierarchy( $inventory ) {
		return apply_filters( 'atum/multi_inventory/list_tables/column_calc_hierarchy', AtumListTable::EMPTY_COL, $inventory );
	}

	/**
	 * Column for the inventories on backorder quantity.
	 *
	 * @since 1.3.7
	 *
	 * @param Inventory $inventory
	 *
	 * @return string
	 */
	public function column_calc_back_orders( $inventory ) {

		if ( ! $inventory->managing_stock() || 'onbackorder' !== $inventory->stock_status ) {
			return AtumListTable::EMPTY_COL;

		}
		return $inventory->stock_quantity;
	}

	/**
	 * Column for stock indicators
	 *
	 * @since 1.0.1
	 *
	 * @param Inventory $inventory The product inventory.
	 * @param string    $classes
	 * @param string    $data
	 * @param string    $primary
	 */
	public function _column_calc_stock_indicator( $inventory, $classes, $data, $primary ) {

		$content          = '';
		$stock_status     = $inventory->stock_status;
		$is_report        = isset( $_REQUEST['action'] ) && 'atum_export_data' === $_REQUEST['action'];
		$atum_icons_style = ' style="font-family: atum-icon-font; font-size: 20px;"';

		// Stock not managed by WC.
		if ( ! $inventory->managing_stock() ) {

			switch ( $stock_status ) {
				case 'instock':
					$classes  .= ' cell-green';
					$icon_attr = ! $is_report ? 'data-tip="' . esc_attr__( 'In Stock (not managed by WC)', ATUM_MULTINV_TEXT_DOMAIN ) . '"' : $atum_icons_style;
					$icon_code = $is_report ? '&#xe991;' : '';
					$content   = '<span class="atum-icon atmi-question-circle tips" ' . $icon_attr . '>' . $icon_code . '</span>';
					break;

				case 'outofstock':
					$classes  .= ' cell-red';
					$icon_attr = ! $is_report ? 'data-tip="' . esc_attr__( 'Out of Stock (not managed by WC)', ATUM_MULTINV_TEXT_DOMAIN ) . '"' : $atum_icons_style;
					$icon_code = $is_report ? '&#xe991;' : '';
					$content   = '<span class="atum-icon atmi-question-circle tips" ' . $icon_attr . '>' . $icon_code . '</span>';
					break;

				case 'onbackorder':
					$classes  .= ' cell-yellow';
					$icon_attr = ! $is_report ? 'data-tip="' . esc_attr__( 'On Backorder (not managed by WC)', ATUM_MULTINV_TEXT_DOMAIN ) . '"' : $atum_icons_style;
					$icon_code = $is_report ? '&#xe991;' : '';
					$content   = '<span class="atum-icon atmi-question-circle tips" ' . $icon_attr . '>' . $icon_code . '</span>';
					break;
			}

		}
		// Out of stock.
		elseif ( 'outofstock' === $stock_status ) {
			$classes  .= ' cell-red';
			$icon_attr = ! $is_report ? 'data-tip="' . esc_attr__( 'Out of Stock', ATUM_MULTINV_TEXT_DOMAIN ) . '"' : $atum_icons_style;
			$icon_code = $is_report ? '&#xe941;' : '';
			$content   = '<span class="atum-icon atmi-cross-circle tips" ' . $icon_attr . '>' . $icon_code . '</span>';
		}
		// Back Orders.
		elseif ( 'onbackorder' === $stock_status ) {
			$classes  .= ' cell-yellow';
			$icon_attr = ! $is_report ? 'data-tip="' . esc_attr__( 'Out of Stock (back orders allowed)', ATUM_MULTINV_TEXT_DOMAIN ) . '"' : $atum_icons_style;
			$icon_code = $is_report ? '&#xe935;' : '';
			$content   = '<span class="atum-icon atmi-circle-minus tips" ' . $icon_attr . '>' . $icon_code . '</span>';
		}
		// Low Stock.
		// TODO...
		// elseif ( in_array( $inventory_id, $this->id_views['low_stock'] ) ) {
		// $classes .= ' cell-yellow';
		// $content  = '<span class="atum-icon atmi-arrow-down-circle tips" data-tip="' . esc_attr__( 'Low Stock', ATUM_MULTINV_TEXT_DOMAIN ) . '"></span>';
		// }
		// In Stock.
		elseif ( 'instock' === $stock_status ) {
			$classes  .= ' cell-green';
			$icon_attr = ! $is_report ? 'data-tip="' . esc_attr__( 'In Stock', ATUM_MULTINV_TEXT_DOMAIN ) . '"' : $atum_icons_style;
			$icon_code = $is_report ? '&#xe92c;' : '';
			$content   = '<span class="atum-icon atmi-checkmark-circle tips" ' . $icon_attr . '>' . $icon_code . '</span>';
		}

		$classes = $classes ? ' class="' . $classes . '"' : '';

		echo '<td ' . $data . $classes . '>' . apply_filters( 'atum/multi_inventory/list_tables/column_stock_indicator', $content, $inventory ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Set the MI Status column sticky
	 *
	 * @since 1.0.1
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function sticky_columns( $columns ) {

		$columns[] = 'calc_mi_status';
		return $columns;

	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 1.0.1
	 *
	 * @param string $hook
	 */
	public function enqueue_admin_scripts( $hook ) {

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Stock Central List or Manufacturing Central List pages.
		if (
			FALSE !== strpos( $screen_id, StockCentral::UI_SLUG ) || 
			( Addons::is_addon_active( 'product_levels' ) && FALSE !== strpos( $screen_id, ManufacturingCentral::UI_SLUG ) )
		) {

			wp_register_style( 'atum-mi-list', ATUM_MULTINV_URL . 'assets/css/atum-mi-list.css', [], ATUM_MULTINV_VERSION );
			wp_register_script( 'atum-mi-list', ATUM_MULTINV_URL . 'assets/js/build/atum-mi-list-tables.js', [ 'jquery', 'atum-list' ], ATUM_MULTINV_VERSION, TRUE );

			$vars = array(
				'createInventory'        => __( 'Create Inventory', ATUM_MULTINV_TEXT_DOMAIN ),
				'done'                   => __( 'Done!', ATUM_MULTINV_TEXT_DOMAIN ),
				'editInventoryLocations' => __( 'Edit Inventory Locations', ATUM_MULTINV_TEXT_DOMAIN ),
				'inventoryLocations'     => __( 'Inventory Locations', ATUM_MULTINV_TEXT_DOMAIN ),
				'inventoryRegions'       => __( 'Inventory Regions', ATUM_MULTINV_TEXT_DOMAIN ),
				'ok'                     => __( 'OK', ATUM_MULTINV_TEXT_DOMAIN ),
				'miListTableNonce'       => wp_create_nonce( 'mi-list-tables-nonce' ),
				'nameRequired'           => __( 'The inventory name is required', ATUM_MULTINV_TEXT_DOMAIN ),
				'regionsSaved'           => __( 'Regions saved succesfully', ATUM_MULTINV_TEXT_DOMAIN ),
				'saveButton'             => __( 'Save', ATUM_MULTINV_TEXT_DOMAIN ),
			);
			wp_localize_script( 'atum-mi-list', 'atumMultInvVars', $vars );

			wp_enqueue_style( 'atum-mi-list' );
			wp_enqueue_script( 'atum-mi-list' );

		}

	}

	/**
	 * Load the MI styles to ATUM List Table's reports
	 *
	 * @since 1.0.1
	 *
	 * @param string $report_styles
	 *
	 * @return string
	 */
	public function load_report_styles( $report_styles ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return $report_styles . file_get_contents( ATUM_MULTINV_PATH . 'assets/css/atum-mi-list.css' );
	}

	/**
	 * Allow searching by MI data
	 *
	 * @since 1.2.3
	 *
	 * @param string $where
	 * @param string $search_column
	 * @param string $search_term
	 * @param array  $search_terms
	 * @param string $cache_key
	 *
	 * @return string
	 */
	public function search_mi_data( $where, $search_column, $search_term, $search_terms, $cache_key ) {

		global $wpdb;

		if ( '_' === substr( $search_column, 0, 1 ) ) {
			$search_column = substr( $search_column, 1, strlen( $search_column ) - 1 );
		}

		// The MI search is only available when searching in column.
		if ( AtumHelpers::in_multi_array( $search_column, self::SEARCHABLE_COLUMNS ) ) {

			$this->searched_column = $search_column;
			$this->searched_terms  = $search_terms;
			$prop_name             = strpos( $search_column, 'mi_' ) === 0 ? str_replace( 'mi_', '', $search_column ) : $search_column;

			$search_query = $this->build_search_query( $search_terms, $prop_name, in_array( $search_column, self::SEARCHABLE_COLUMNS['string'], TRUE ) ? 'string' : 'int', TRUE );

			// Get all (parent and variations, and build where).
			$query = "
				SELECT ID, post_type, post_parent FROM $wpdb->posts
			    WHERE $search_query
			    AND post_type IN ('product', 'product_variation')
		    ";

			$search_term_ids = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( empty( $search_term_ids ) ) {
				return $where;
			}

			$search_terms_ids_str = '';

			foreach ( $search_term_ids as $search_term_id ) {

				if ( 'product' === $search_term_id->post_type ) {

					$search_terms_ids_str .= $search_term_id->ID . ',';

					// If has children, add them.
					$product = wc_get_product( $search_term_id->ID );

					// Get an array of the children IDs (if any).
					$children = $product->get_children();

					if ( ! empty( $children ) ) {
						foreach ( $children as $child ) {
							$search_terms_ids_str .= $child . ',';
						}
					}

				}
				// Add parent and current.
				else {
					$search_terms_ids_str .= $search_term_id->post_parent . ',';
					$search_terms_ids_str .= $search_term_id->ID . ',';
				}

			}

			$search_terms_ids_str = rtrim( $search_terms_ids_str, ',' );
			$where               .= " OR ( $wpdb->posts.ID IN ($search_terms_ids_str) )";

			AtumCache::set_cache( $cache_key, $where );

		}

		return $where;

	}

	/**
	 * Build the search SQL query for the given search terms
	 *
	 * @since 1.5.2
	 *
	 * @param array  $search_terms      An array of search terms.
	 * @param string $column            Optional. If passed will search in the specified table column.
	 * @param string $format            Optional. The format that has that column.
	 * @param bool   $is_meta_search    Optional. Whether the search is being performed for meta keys.
	 *
	 * @return string
	 */
	protected function build_search_query( $search_terms, $column, $format = 'string', $is_meta_search = FALSE ) {

		global $wpdb;

		$search_and = $search_where = $search_query = '';

		/**
		 * Filters the prefix that indicates that a search term should be excluded from results.
		 * Note that uses the WP_Query's filter name for compatibility.
		 *
		 * @param string $exclusion_prefix The prefix. Default '-'. Returning an empty value disables exclusions.
		 */
		$exclusion_prefix = apply_filters( 'wp_query_search_exclusion_prefix', '-' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		foreach ( $search_terms as $term ) {

			// If there is an $exclusion_prefix, terms prefixed with it should be excluded.
			$exclude = $exclusion_prefix && ( substr( $term, 0, 1 ) === $exclusion_prefix );

			if ( $exclude ) {
				$operator = 'string' === $format ? 'NOT LIKE' : '!=';
				$term     = substr( $term, 1 );
			}
			else {
				$operator = 'string' === $format ? 'LIKE' : '=';
			}

			switch ( $format ) {
				case 'int':
					$term = intval( $term );
					break;

				case 'float':
					$term = floatval( $term );
					break;

				default:
					$term = "'%" . esc_sql( $wpdb->esc_like( $term ) ) . "%'";
					break;
			}

			$search_where .= "{$search_and}(($column $operator $term))";
			$search_and    = ' AND ';

		}

		if ( $search_where ) {

			$inventories_table      = $wpdb->prefix . Inventory::INVENTORIES_TABLE;
			$inventories_meta_table = $wpdb->prefix . Inventory::INVENTORY_META_TABLE;
			$meta_join              = $is_meta_search ? "LEFT JOIN $inventories_meta_table miim ON mii.id=miim.inventory_id" : '';

			$search_query = "ID IN( 
				SELECT product_id FROM $inventories_table mii
				$meta_join
				WHERE $search_where
			)";

		}

		return $search_query;

	}

	/**
	 * Add the MI status to the Stock Central's help tab
	 *
	 * @since 1.0.7
	 */
	public function add_mi_status_column_help() {
		?>
		<tr>
			<td>
				<span class="atum-icon atmi-multi-inventory" title="<?php esc_attr_e( 'Multi-Inventory status', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></span>
			</td>
			<td><?php esc_attr_e( 'The Multi-Inventory icon in this column characterises products activated for Multi-Inventory. Click the green icon to show and hide the inventories. The small number located at the top right of the icon is the number of the inventories. The black icon appears in Grouped and variable products when contains a product or a variation with Multi-Inventory activated.', ATUM_MULTINV_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Inventory Date', ATUM_MULTINV_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Shows the inventory creation date.', ATUM_MULTINV_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'BBE Date', ATUM_MULTINV_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Shows the Best Before End date for the inventory (Called as well Expiry Date or Use By Date in other countries).', ATUM_MULTINV_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Priority', ATUM_MULTINV_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Shows the inventory selling priority number with 0 (Zero) being the default inventory.', ATUM_MULTINV_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'LOT/Batch', ATUM_MULTINV_TEXT_DOMAIN ) ?></strong></td>
			<td><?php esc_html_e( 'Shows the LOT/Batch number for the inventory.', ATUM_MULTINV_TEXT_DOMAIN ) ?></td>
		</tr>
		<tr>
			<td>
				<span class="atum-icon atmi-earth" title="<?php esc_attr_e( 'Regions', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></span>
			</td>
			<td><?php esc_html_e( 'Shows the selling region restrictions for the inventory.', ATUM_MULTINV_TEXT_DOMAIN ) ?></td>
		</tr>
		<?php
	}

	/**
	 * Add MI row actions to SC and MC
	 *
	 * @since 1.4.7
	 *
	 * @param array $row_actions
	 *
	 * @return array
	 */
	public function add_mi_row_actions( $row_actions ) {

		$row_actions[] = array(
			'name'  => 'addInventory',
			'icon'  => 'atmi-multi-inventory',
			'label' => __( 'Add new inventory', ATUM_MULTINV_VERSION ),
		);

		return $row_actions;

	}

	/**
	 * Load the "Add Inventory" modal's JS template to SC
	 *
	 * @since 1.4.7
	 *
	 * @param AtumListTable $list_table
	 */
	public function load_add_inventory_modal_template( $list_table ) {

		if ( empty( $list_table->screen ) ) {
			return;
		}

		$screen_id = $list_table->screen->id;

		if (
			strpos( $screen_id, StockCentral::UI_SLUG ) !== FALSE ||
			( Addons::is_addon_active( 'product_levels' ) && strpos( $screen_id, ManufacturingCentral::UI_SLUG ) !== FALSE )
		) {
			AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/js-templates/add-inventory-modal' );
		}

	}

	/**
	 * Get the inventory locations tree
	 * NOTE: This is being called during an Ajax call.
	 *
	 * @since 1.4.9
	 */
	public function get_locations_tree() {

		$ids = explode( ':', $_POST['product_id'] );

		// Only needed when the requested locations tree is for an inventory (the product ID comes as product_id:inventory_id).
		if ( count( $ids ) !== 2 ) {
			return;
		}

		$inventory_id = absint( $ids[1] );
		$inventory    = Helpers::get_inventory( $inventory_id );
		$locations    = $inventory->get_locations();

		if ( empty( $locations ) ) {
			wp_send_json_success( '<div class="alert alert-warning no-locations-set">' . __( 'No locations were set for this inventory', ATUM_MULTINV_TEXT_DOMAIN ) . '</div>' );
		}
		else {

			$locations_tree = wp_list_categories( array(
				'taxonomy'   => Globals::PRODUCT_LOCATION_TAXONOMY,
				'include'    => $locations,
				'title_li'   => '',
				'hide_empty' => FALSE,
				'echo'       => FALSE,
			) );

			// Remove the links.
			$locations_tree = str_replace( '<a href', '<span data-href', $locations_tree );
			$locations_tree = str_replace( '</a>', '</span>', $locations_tree );

			wp_send_json_success( "<ul>$locations_tree</ul>" );

		}

	}

	/**
	 * Set the locations for any inventory from the locations tree modal
	 * NOTE: This is being accessed during an Ajax call.
	 *
	 * @since 1.4.9
	 *
	 * @param int   $product_id
	 * @param int[] $terms
	 */
	public function set_locations_tree( $product_id, $terms ) {

		// Get the product_id from the posted data because the coming parameter doesn't contain the inventory ID.
		$ids = explode( ':', $_POST['product_id'] );

		// Only needed when the requested locations tree is for an inventory (the product ID comes as product_id:inventory_id).
		if ( count( $ids ) !== 2 ) {
			//wp_send_json_error( __( 'No valid inventory ID provided', ATUM_MULTINV_TEXT_DOMAIN ) );
			return;
		}

		$inventory_id = absint( $ids[1] );
		$inventory    = Helpers::get_inventory( $inventory_id );
		$inventory->set_data( [ 'location' => $terms ] );
		$inventory->save();

		wp_send_json_success();

	}

	/**
	 * Sort the product list by inventory field.
	 *
	 * @since 1.5.3
	 */
	public function add_filter_mi_query_data() {
		add_filter( 'posts_clauses', array( $this, 'multi_inventory_data_query_clauses' ) );
	}

	/**
	 * Remove sorting filter.
	 *
	 * @since 1.5.3
	 */
	public function remove_filter_mi_query_data() {
		remove_filter( 'posts_clauses', array( $this, 'multi_inventory_data_query_clauses' ) );
	}

	/**
	 * Customize the WP_Query in the prepare_items method to handle MI data.
	 *
	 * @since 1.5.3
	 *
	 * @param array $pieces
	 * @param bool  $need_sorting
	 *
	 * @return array
	 */
	public function multi_inventory_data_query_clauses( $pieces, $need_sorting = TRUE ) {

		$is_supplier_filtered = ! empty( $_REQUEST['supplier'] ) ? TRUE : FALSE;
		$is_mi_sorted         = ! empty( $_REQUEST['orderby'] ) && 'mi_' === substr( $_REQUEST['orderby'], 0, 3 ) && TRUE === $need_sorting ? TRUE : FALSE;

		if ( $is_mi_sorted || $is_supplier_filtered ) {

			global $wpdb;
			$must_sort = FALSE;

			$order     = ( isset( $_REQUEST['order'] ) && 'asc' === $_REQUEST['order'] ) ? 'ASC' : 'DESC';
			$sort_func = 'DESC' === $order ? 'MAX' : 'MIN';
			$orderby   = stripslashes( $_REQUEST['orderby'] );
			$supplier  = stripslashes( $_REQUEST['supplier'] );
			$apd_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
			$field     = str_replace( 'mi_', '', $_REQUEST['orderby'] );
			$table     = FALSE === in_array( $field, [ 'sku', 'supplier_sku' ] ) ? 'ai' : 'aim';
			$table2    = FALSE === in_array( $field, [ 'sku', 'supplier_sku' ] ) ? 'ai2' : 'aim2';

			$join_inv   = " LEFT JOIN $wpdb->prefix" . Inventory::INVENTORIES_TABLE . " ai ON $wpdb->posts.ID = ai.product_id";
			$join_meta  = 'aim' === $table || $is_supplier_filtered ? " LEFT JOIN $wpdb->prefix" . Inventory::INVENTORY_META_TABLE . ' aim ON ai.id = aim.inventory_id ' : '';
			$join_var   = " LEFT JOIN $wpdb->posts p2 ON $wpdb->posts.ID = p2.post_parent ";
			$join_inv2  = " LEFT JOIN $wpdb->prefix" . Inventory::INVENTORIES_TABLE . " ai2 ON p2.ID = ai2.product_id";
			$join_meta2 = 'aim2' === $table2 || $is_supplier_filtered  ? " LEFT JOIN $wpdb->prefix" . Inventory::INVENTORY_META_TABLE . ' aim2 ON ai2.id = aim2.inventory_id ' : '';

			foreach ( self::SEARCHABLE_COLUMNS as $type => $columns ) {
				if ( FALSE !== in_array( $orderby, $columns ) ) {
					$must_sort  = TRUE;
					break;
				}
			}

			$pieces['join'] .= "$join_inv $join_meta $join_var $join_inv2 $join_meta2";

			if ( $is_mi_sorted && $must_sort ) {
				$pieces['fields'] .= ", $sort_func(IFNULL($table.$field,$table2.$field))";
				$pieces['orderby'] = "$sort_func(IFNULL($table.$field,$table2.$field)) $order";
			}

			if ( $is_supplier_filtered ) {
				$pieces['where'] .= " AND $supplier IN ($apd_table.supplier_id, aim.supplier_id, aim2.supplier_id)";
			}

		}

		return $pieces;
	}

	/**
	 * Replace simple criteria for supplier query with empty array since the supplier can be queried also at MI fields.
	 *
	 * @since 1.5.3
	 *
	 * @param $query_data
	 *
	 * @return array
	 */
	public function supplier_filter_query_data( $query_data ) {

		if ( ! empty( $_REQUEST['supplier'] ) ) {

			return [];
		}

		return $query_data;
	}

	/**
	 * Request the inventory data from ListTable views.
	 *
	 * @since 1.5.3
	 */
	public function add_filter_supplier_view_query_data() {
		add_filter( 'posts_clauses', array( $this, 'multi_inventory_view_data_query_clauses' ) );
	}

	/**
	 * Disable filter from ListTable views.
	 *
	 * @since 1.5.3
	 */
	public function remove_filter_supplier_view_query_data() {
		remove_filter( 'posts_clauses', array( $this, 'multi_inventory_view_data_query_clauses' ) );
	}

	/**
	 * Customize the WP_Query with MI joins for the ListTable wiews.
	 *
	 * @since 1.5.3
	 *
	 * @param array $pieces
	 *
	 * @return array
	 */
	public function multi_inventory_view_data_query_clauses( $pieces ) {
		return $this->multi_inventory_data_query_clauses( $pieces, FALSE );
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
	 * @return ListTables instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
