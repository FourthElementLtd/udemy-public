<?php
/**
 * Product Levels + Multi-Inventory integration
 *
 * @package        AtumMultiInventory
 * @subpackage     Integrations
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2021 Stock Management Labs™
 *
 * @since          1.0.0
 */

namespace AtumMultiInventory\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumListTables\AtumListTable;
use Atum\Components\AtumOrders\AtumOrderPostType;
use AtumLevels\Models\BOMModel;
use AtumLevels\Inc\Helpers as AtumLevelsHelpers;
use AtumMultiInventory\Inc\Helpers;
use AtumMultiInventory\Models\Inventory;

class ProductLevels {

	/**
	 * The singleton instance holder
	 *
	 * @var ProductLevels
	 */
	private static $instance;


	/**
	 * ProductLevels integration constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Add the inventories to the BOM' hierarchy tree.
		add_filter( 'atum/product_levels/add_bom_tree_node', array( $this, 'add_bom_tree_mi_nodes' ), 10, 2 );

		// Adapt some calculated stock quantities when the region restriction is enabled.
		if ( AtumLevelsHelpers::is_bom_stock_control_enabled() && 'no-restriction' !== Helpers::get_region_restriction_mode() ) {
			add_filter( 'atum/multi_inventory/get_stock_quantity', array( $this, 'get_stock_quantity_with_region_restriction' ), 10, 3 );
		}

		// Check if BOM stock control is enabled.
		if ( AtumLevelsHelpers::is_bom_stock_control_enabled() ) {
			add_filter( 'atum/multi_inventory/is_bom_stock_control_enabled', array( $this, 'maybe_bom_stock_control_is_enabled' ), 10, 1 );
			add_filter( 'atum/multi_inventory/list_tables/stock_with_bom_stock_control', array( $this, 'mc_stock_control_enabled' ), 10, 1 );
		}

		if ( is_admin() ) {

			// Show empty the BOM hierarchy cells for inventories.
			add_filter( 'atum/list_table/column_default_calc_hierarchy', array( $this, 'column_calc_hierarchy' ), 11, 3 );

			// Allow BOM MI order items' batch tracking.
			add_filter( 'atum/multi_inventory/batch_tracking/order_items_where', array( $this, 'bom_batch_tracking_where' ), 10, 3 );

			// The products with calculated stock should not allow to edit the stock.
			if ( AtumLevelsHelpers::is_bom_stock_control_enabled() ) {
				add_filter( 'atum/multi_inventory/list_tables/editable_column_stock', array( $this, 'maybe_editable_inventory_stock' ), 10, 2 );
				add_filter( 'atum/multi_inventory/list_tables/column_stock', array( $this, 'maybe_add_tooltip_to_inventory_stock' ), 12, 2 );
			}

			// Inventory with bom.
			add_filter( 'atum/multi_inventory/list_tables/inventory_has_bom', array( $this, 'maybe_inventory_is_bom' ), 10, 1 );

		}

	}

	/**
	 * Column for BOM hierarchy in Inventories
	 *
	 * @since 1.0.1
	 *
	 * @param string      $column_item
	 * @param \WP_Post    $item
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public function column_calc_hierarchy( $column_item, $item, $product ) {

		if ( $item instanceof Inventory ) {
			return AtumListTable::EMPTY_COL;
		}

		return $column_item;
	}

	/**
	 * Add the MI nodes to the BOM hierarchy tree nodes
	 *
	 * @since 1.3.0
	 *
	 * @param array       $tree_node
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	public function add_bom_tree_mi_nodes( $tree_node, $product ) {

		if ( Helpers::is_product_multi_inventory_compatible( $product ) && 'yes' === Helpers::get_product_multi_inventory_status( $product ) ) {

			// Only need to be added to the last level (all the mid-level items are getting the Main Inventory).
			if ( empty( $tree_node['children'] ) ) {

				$inventories = Helpers::get_product_inventories_sorted( $product->get_id() );

				if ( ! empty( $inventories ) ) {

					$tree_node['children'] = array();

					foreach ( $inventories as $inventory ) {

						$class                   = 0 > $inventory->stock_quantity ? 'stock-negative' : '';
						$tree_node['children'][] = array(
							'text'    => "$inventory->name ($inventory->stock_quantity)",
							'uiIcon'  => 'atum-icon atmi-multi-inventory',
							'textCss' => $class,
						);
					}

				}

			}

		}

		return $tree_node;

	}

	/**
	 * Modify SQL WHERE for filtering the BOM MI order items by batch numbers used
	 *
	 * @since 1.3.5
	 *
	 * @param string $where         WHERE part of the sql query.
	 * @param string $batch_number  The batch number being searched.
	 * @param int    $order_type_id The order type ID being searched.
	 *
	 * @return string
	 */
	public function bom_batch_tracking_where( $where, $batch_number, $order_type_id ) {

		global $wpdb;

		$inventories_table = $wpdb->prefix . Inventory::INVENTORIES_TABLE;
		$bom_orders_table  = $wpdb->prefix . BOMModel::get_order_bom_table();
		$order_items_table = 1 === $order_type_id ? 'woocommerce_order_items' : AtumOrderPostType::ORDER_ITEMS_TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$bom_orders_select = $wpdb->prepare( "
			SELECT aoi.order_id FROM {$wpdb->prefix}{$order_items_table} aoi 
			LEFT JOIN $bom_orders_table boi ON (aoi.order_item_id = boi.order_item_id AND boi.order_type = %d) 
			LEFT JOIN $inventories_table ai ON (boi.inventory_id = ai.id)
			WHERE ai.lot = %s	
		", $order_type_id, $batch_number );
		// phpcs:enable

		$where = str_replace( "'$batch_number'", "'$batch_number' ) OR $wpdb->posts.ID IN ($bom_orders_select", $where );

		return $where;

	}

	/**
	 * Get the right calculated stock quantity when the region restricion is enabled for any product with BOM + MI
	 * and some ot its BOM with MI restricted by region
	 *
	 * @since 1.3.6
	 *
	 * @param float       $stock_quantity
	 * @param int         $product_id
	 * @param Inventory[] $inventories
	 *
	 * @return float
	 */
	public function get_stock_quantity_with_region_restriction( $stock_quantity, $product_id, $inventories ) {

		if ( BOMModel::has_linked_bom( $product_id ) ) {

			$linked_bom           = BOMModel::get_linked_bom( $product_id );
			$main_inventory_stock = 0;
			$changed              = FALSE;

			foreach ( $linked_bom as $linked_bom ) {

				if ( Helpers::has_multi_inventory( $linked_bom->bom_id ) ) {

					if ( ! $linked_bom->qty ) {
						continue;
					}

					$bom_inventories = Helpers::get_product_inventories_sorted( $linked_bom->bom_id );

					foreach ( $bom_inventories as $bom_inventory ) {
						$main_inventory_stock += ( $bom_inventory->stock_quantity / (float) $linked_bom->qty );
						$changed               = TRUE;
					}

				}

			}

			if ( Helpers::has_multi_inventory( $product_id ) ) {

				$new_stock_quantity = 0;

				foreach ( $inventories as $inventory ) {

					if ( $inventory->is_main() ) {

						if ( $inventory->stock_quantity === $main_inventory_stock ) {
							return $stock_quantity; // No changes needed.
						}

						$new_stock_quantity += $main_inventory_stock;

					}
					else {
						$new_stock_quantity += $inventory->stock_quantity;
					}

				}

				$stock_quantity = $new_stock_quantity;

			}
			elseif ( $changed && $stock_quantity !== $main_inventory_stock ) {
				$stock_quantity = $main_inventory_stock;
			}

		}

		return $stock_quantity;

	}

	/**
	 * For the products that have their stock calculated, disable edits
	 *
	 * @since 1.4.7
	 *
	 * @param bool      $editable
	 * @param Inventory $inventory
	 *
	 * @return bool
	 */
	public function maybe_editable_inventory_stock( $editable, $inventory ) {

		if ( $editable && $inventory->is_main() && BOMModel::has_linked_bom( $inventory->product_id ) ) {

			$editable = FALSE;
		}

		return $editable;

	}

	/**
	 * Add the tooltip when the stock isn't editable because it's calculated
	 *
	 * @since 1.4.7
	 *
	 * @param string    $stock_html
	 * @param Inventory $inventory
	 *
	 * @return string
	 */
	public function maybe_add_tooltip_to_inventory_stock( $stock_html, $inventory ) {

		if ( $inventory->is_main() && BOMModel::has_linked_bom( $inventory->product_id ) && strpos( $stock_html, 'atum-tooltip' ) === FALSE && strpos( $stock_html, 'tips' ) === FALSE ) {
			$stock_html = '<span class="calculated atum-tooltip" data-tip="' . esc_attr__( 'Calculated stock quantity', ATUM_LEVELS_TEXT_DOMAIN ) . '">' . $stock_html . '</span>';
		}

		return $stock_html;

	}

	/**
	 * Column for BOM hierarchy in Inventories
	 *
	 * @since 1.5.0
	 *
	 * @param string $product_id
	 *
	 * @return boolean
	 */
	public function maybe_inventory_is_bom( $product_id ) {

		$has_bom = BOMModel::has_linked_bom( $product_id );

		return $has_bom;
	}

	/**
	 * Check if BOM stock control is enabled
	 *
	 * @since 1.5.0
	 *
	 * @param boolean $has_bom_stock_control
	 *
	 * @return boolean
	 */
	public function maybe_bom_stock_control_is_enabled( $has_bom_stock_control ) {

		$has_bom_stock_control = AtumLevelsHelpers::is_bom_stock_control_enabled();

		return $has_bom_stock_control;
	}

	/**
	 * Modify when stock is added and printed when BOM stock control is enabled
	 *
	 * @since 1.5.0
	 *
	 * @param array $inventory_values
	 *
	 * @return array
	 */
	public function mc_stock_control_enabled( $inventory_values ) {

		$inventory                 = $inventory_values['inventory'];
		$inventory_values['stock'] = AtumListTable::EMPTY_COL;
		$current_screen            = get_current_screen();
		$has_associated            = count( BOMModel::get_associated_products( $inventory->product_id ) ) > 0;

		// Check whether the current List Table is MC.
		if ( $current_screen ) {
			$slug_value = strpos( $current_screen->id, 'atum-manufacturing-central' );
		}
		else {
			$slug_value = ! empty( $_REQUEST['screen'] ) && strpos( $_REQUEST['screen'], 'atum-manufacturing-central' );
		}

		// Do not show the main stock if have BOM asociated on MC.
		if ( FALSE !== $slug_value ) {

			$has_bom = BOMModel::has_linked_bom( $inventory->product_id );

			if ( ! $has_bom && $has_associated ) {

				$inventory_values['stock']    = $inventory->stock_quantity;
				$inventory_values['increase'] = FALSE;

			}

			elseif ( ! $has_bom && ! $has_associated ) {
				$inventory_values['stock'] = $inventory->stock_quantity;
			}

			elseif ( ! $inventory->is_main() ) {
				$inventory_values['stock'] = $inventory->stock_quantity;

			}

		}
		else {
			$inventory_values['stock'] = $inventory->stock_quantity;

			if ( $inventory->is_main() || $has_associated ) {
				$inventory_values['increase'] = FALSE;
			}

		}

		return $inventory_values;

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
	 * @return ProductLevels instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
