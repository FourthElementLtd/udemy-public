<?php
/**
 * The Model class for Inventories
 *
 * @package        AtumMultiInventory
 * @subpackage     Models
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2021 Stock Management Labs™
 *
 * @since          1.0.0
 */

namespace AtumMultiInventory\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Inc\Helpers;


/**
 * Class Inventory
 *
 * @property int       $id
 * @property int       $product_id
 * @property string    $name
 * @property int       $priority
 * @property bool      $is_main
 * @property string    $inventory_date
 * @property string    $lot
 * @property bool      $write_off
 * @property int|float $inbound_stock
 * @property int|float $stock_on_hold
 * @property int|float $sold_today
 * @property int|float $sales_last_days
 * @property int|float $reserved_stock
 * @property int|float $customer_returns
 * @property int|float $warehouse_damage
 * @property int|float $lost_in_post
 * @property int|float $other_logs
 * @property int       $out_stock_days
 * @property int|float $lost_sales
 * @property string    $update_date
 * @property string    $region
 * @property string    $location
 * @property string    $bbe_date
 * @property int       $expiry_days
 * @property string    $sku
 * @property string    $manage_stock
 * @property int|float $stock_quantity
 * @property string    $backorders
 * @property string    $stock_status
 * @property int       $supplier_id
 * @property string    $supplier_sku
 * @property string    $sold_individually
 * @property int|float $out_stock_threshold
 * @property int|float $original_stock
 * @property int|float $purchase_price
 * @property int|float $price
 * @property int|float $regular_price
 * @property int|float $sale_price
 * @property string    $date_on_sale_from
 * @property string    $date_on_sale_to
 * @property string    $out_stock_date
 * @property int       $shipping_class
 * @property int|float $expired_stock
 */
class Inventory {

	/**
	 * The db table where all the inventories are stored and linked to products
	 */
	const INVENTORIES_TABLE = 'atum_inventories';

	/**
	 * The db table where are stored the data for each inventory
	 */
	const INVENTORY_META_TABLE = 'atum_inventory_meta';

	/**
	 * The db table where are stored the ATUM Location terms relationships
	 */
	const INVENTORY_LOCATIONS_TABLE = 'atum_inventory_locations';

	/**
	 * The db table where are stored the relationships between inventories and WC's shipping zones
	 */
	const INVENTORY_REGIONS_TABLE = 'atum_inventory_regions';

	/**
	 * The db table where are stored the MI stock changes performed in WC orders
	 */
	const INVENTORY_ORDERS_TABLE = 'atum_inventory_orders';

	/**
	 * The db table where are stored the temp reserved stock
	 */
	const INVENTORY_RESERVED_STOCK_TABLE = 'atum_inventory_reserved_stock';
	
	/**
	 * The backorders statuses in the database.
	 */
	const BACKORDERS_STATUSES = array(
		0 => 'no',
		1 => 'yes',
		2 => 'notify',
	);

	/**
	 * Default values for the inventory order data array
	 *
	 * @since 1.4.9
	 */
	const DEFAULT_INVENTORY_ORDER_DATA = [
		'qty'        => 0,
		'subtotal'   => '0',
		'total'      => '0',
		'extra_data' => '',
	];

	/**
	 * Stores Inventory Order Data fields data type (only those variables)
	 *
	 * @since 1.4.9
	 */
	const INVENTORY_DATA_TYPES = [
		'qty'           => 'numeric',
		'subtotal'      => 'string',
		'total'         => 'string',
		'reduced_stock' => 'numeric',
		'extra_data'    => 'string',
		'refund_qty'    => 'numeric',
		'refund_total'  => 'string',
	];

	/**
	 * The inventory ID
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * Removed inventory
	 *
	 * @var int
	 */
	protected $removed_from_db;

	/**
	 * Whether the current inventory was already read from db.
	 *
	 * @var bool
	 */
	protected $is_read = FALSE;

	/**
	 * Whether the data must be read from the database.
	 *
	 * @since 1.7.2
	 *
	 * @var bool
	 */
	protected $allow_read = TRUE;

	/**
	 * The default data.
	 *
	 * @since 1.7.2
	 *
	 * @var array
	 */
	protected static $default_data = array(
		'product_id'       => 0,
		'name'             => '',
		'priority'         => 0,
		'is_main'          => FALSE,
		'inventory_date'   => '',
		'lot'              => '',
		'write_off'        => FALSE,
		'inbound_stock'    => NULL,
		'stock_on_hold'    => NULL,
		'sold_today'       => NULL,
		'sales_last_days'  => NULL,
		'reserved_stock'   => NULL,
		'customer_returns' => NULL,
		'warehouse_damage' => NULL,
		'lost_in_post'     => NULL,
		'other_logs'       => NULL,
		'out_stock_days'   => NULL,
		'lost_sales'       => NULL,
		'update_date'      => NULL,
		'region'           => '',
		'location'         => '',
		'bbe_date'         => '',
		'expiry_days'      => 0,
	);

	/**
	 * The default meta data
	 *
	 * @since 1.7.2
	 *
	 * @var array
	 */
	protected static $default_meta = array(
		'sku'                 => NULL,
		'manage_stock'        => NULL,
		'stock_quantity'      => NULL,
		'backorders'          => NULL,
		'stock_status'        => NULL,
		'supplier_id'         => NULL,
		'supplier_sku'        => NULL,
		'sold_individually'   => NULL,
		'out_stock_threshold' => NULL,
		'original_stock'      => NULL,
		'purchase_price'      => NULL,
		'price'               => NULL,
		'regular_price'       => NULL,
		'sale_price'          => NULL,
		'date_on_sale_from'   => NULL,
		'date_on_sale_to'     => NULL,
		'out_stock_date'      => NULL,
		'shipping_class'      => NULL,
		'expired_stock'       => NULL,
	);

	/**
	 * The inventory data from database
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * The meta keys attached to the inventory
	 *
	 * @var array
	 */
	protected $meta = array();

	/**
	 * Relation between ATUM order types' IDs and their corresponding tables
	 *
	 * @var array
	 */
	protected static $item_table_names = array(
		1 => 'woocommerce_order_items',
		2 => AtumOrderPostType::ORDER_ITEM_META_TABLE,
		3 => AtumOrderPostType::ORDER_ITEM_META_TABLE,
	);

	/**
	 * Inventory constructor
	 *
	 * @since 1.0.0
	 *
	 * @param int  $inventory_id
	 * @param bool $allow_read
	 */
	public function __construct( $inventory_id = 0, $allow_read = TRUE ) {
		
		$this->data = self::$default_data;
		$this->meta = self::$default_meta;

		$this->removed_from_db = FALSE;

		if ( $inventory_id ) {
			$this->id = $inventory_id;
			if ( $allow_read ) {
				$this->read();
			}
		}

	}

	/**************
	 * CRUD METHODS
	 **************/

	/**
	 * Read an inventory from database
	 *
	 * @since 1.0.0
	 *
	 * @param int  $inventory_id    Optional. The inventory ID.
	 * @param bool $force           Optional. Whether to force the read.
	 *
	 * @return Inventory|bool
	 */
	public function read( $inventory_id = NULL, $force = FALSE ) {

		if ( ! $force && $this->is_read ) {
			return $this;
		}

		global $wpdb;
		
		$inventory_id = absint( $inventory_id ?: $this->id );
		$keys         = $this->data;
		
		unset( $keys['location'] );

		// The regions will be handled by the Inventory Regions table when the region mode is set to 'shipping-zones'.
		$region_restriction_mode = Helpers::get_region_restriction_mode();

		if ( 'shipping-zones' === $region_restriction_mode ) {
			unset( $keys['region'] );
		}

		// Avoid duplicated queries.
		$cache_key = AtumCache::get_cache_key( 'inventory_db_data', [ $inventory_id, array_keys( $keys ) ] );
		$data      = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache && ! $force ) {

			// phpcs:disable WordPress.DB.PreparedSQL
			$query = $wpdb->prepare( '
				SELECT ' . implode( ',', array_keys( $keys ) ) . " 
				FROM $wpdb->prefix" . self::INVENTORIES_TABLE . '
				WHERE `id` = %d	
			', $inventory_id );
			// phpcs:enable

			$data = $wpdb->get_row( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// Deleted inventories readed from old orders.
			if ( empty( $data ) ) {
				$this->removed_from_db = TRUE;
			}
			else {

				// Get the linked locations.
				$data['location'] = $this->get_locations();

				// Get the linked regions.
				if ( 'shipping-zones' === $region_restriction_mode ) {
					$data['region'] = $this->get_shipping_zones();
				}
				elseif ( 'countries' === $region_restriction_mode ) {
					$data['region'] = isset( $data['region'] ) ? maybe_unserialize( $data['region'] ) : '';
				}

				AtumCache::set_cache( $cache_key, $data, ATUM_MULTINV_TEXT_DOMAIN );
			}

		}

		if ( $data ) {

			$this->set_data( $data );

			// Get all the meta.
			$meta = $this->get_meta( $inventory_id );

			if ( $meta ) {
				$this->set_meta( $meta );
			}

			$this->is_read = TRUE;

			return $this;
		}

		$this->is_read = FALSE;

		return FALSE;

	}

	/**
	 * Checks if inventory exists
	 *
	 * @since 1.3.9.2
	 *
	 * @return bool
	 */
	public function exists() {
		return TRUE === $this->removed_from_db ? FALSE : TRUE;
	}

	/**
	 * Set the object's data
	 *
	 * @since 1.0.0
	 *
	 * @param array $data
	 *
	 * @return \WP_Error|bool
	 */
	public function set_data( $data ) {

		try {

			// Get rid of non-allowed data props.
			$data = array_intersect_key( $data, $this->data );

			foreach ( $data as $name => $value ) {
				$this->data[ $name ] = $value;
			}

			$this->sanitize_data_for_display();

		} catch ( \Exception $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}

		return TRUE;

	}

	/**
	 * Set the object's meta
	 *
	 * @since 1.0.0
	 *
	 * @param array $meta
	 *
	 * @return \WP_Error|bool
	 */
	public function set_meta( $meta ) {

		try {

			// Remove the underscore at the beginning of the meta keys (not used anymore).
			foreach ( $meta as $key => $value ) {

				if ( '_' === substr( $key, 0, 1 ) ) {
					$meta[ substr( $key, 1 ) ] = $value;
				}

			}

			// Get rid of non-allowed data props.
			$meta = array_intersect_key( $meta, $this->meta );

			foreach ( $meta as $key => $value ) {
				$this->meta[ $key ] = $value;
			}

			$this->sanitize_meta_for_display();
			
		} catch ( \Exception $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}

		return TRUE;

	}

	/**
	 * Allow setting the is_read variable value when setting data and meta externally
	 *
	 * @since 1.7.2
	 *
	 * @param bool $value
	 */
	public function set_is_read( $value ) {
		$this->is_read = $value;
	}
	
	/**
	 * Save a product inventory to the db
	 *
	 * @since 1.0.0
	 *
	 * @return int|void  The inserted/updated ID
	 */
	public function save() {
		
		global $wpdb;
		
		// Check first whether the inventory is already present in the db.
		if ( ! $this->id || ! is_numeric( $this->id ) ) {

			$query = $wpdb->prepare( "SELECT `id` FROM $wpdb->prefix" . self::INVENTORIES_TABLE . ' WHERE `id` = %d ', $this->id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$current_id = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( $current_id ) {
				$this->id = $current_id;
			}

		}

		// Check if the product type is not compatible so we must bypass the saving.
		if ( ! Helpers::is_product_multi_inventory_compatible( $this->product_id, TRUE, TRUE ) ) {

			if ( $this->id ) {
				$this->delete();
			}

			return;

		}

		// It gets the same order as the data prop.
		$formats = array(
			'product_id'       => '%d',
			'name'             => '%s',
			'priority'         => '%d',
			'is_main'          => '%d',
			'inventory_date'   => '%s',
			'lot'              => '%s',
			'write_off'        => '%d',
			'inbound_stock'    => '%s', // Can have decimal values if enabled.
			'stock_on_hold'    => '%s', // Can have decimal values if enabled.
			'sold_today'       => '%s', // Can have decimal values if enabled.
			'sales_last_days'  => '%s', // Can have decimal values if enabled.
			'reserved_stock'   => '%s', // Can have decimal values if enabled.
			'customer_returns' => '%s', // Can have decimal values if enabled.
			'warehouse_damage' => '%s', // Can have decimal values if enabled.
			'lost_in_post'     => '%s', // Can have decimal values if enabled.
			'other_logs'       => '%s', // Can have decimal values if enabled.
			'out_stock_days'   => '%d',
			'lost_sales'       => '%s', // Can have decimal values if enabled.
			'update_date'      => '%s',
			'region'           => '%s',
			'bbe_date'         => '%s',
			'expiry_days'      => '%d',
		);

		$filtered_data = $this->data;

		// Set the update date.
		$filtered_data['update_date'] = gmdate( 'Y-m-d H:i:s' );

		// The region can contain an array of values (for countries) or can be empty and handled the relationships
		// in the inventory_regions table (for shipping zones).
		$region_restriction_mode = Helpers::get_region_restriction_mode();
		switch ( $region_restriction_mode ) {

			case 'countries':
				// The countries will be saved as a serialized array within the inventories table.
				$filtered_data['region'] = maybe_serialize( $filtered_data['region'] );

				if ( is_null( $filtered_data['region'] ) ) {
					$formats['region'] = NULL;
				}
				break;

			case 'shipping-zones':
				$regions = (array) $filtered_data['region'];
				unset( $filtered_data['region'] );
				break;

			default:
				unset( $filtered_data['region'] );
				break;
		}

		// Save the locations and unset the values from db data.
		$locations = (array) $filtered_data['location'];
		unset( $filtered_data['location'] );

		// Set the BBE date + format.
		if ( ! $filtered_data['bbe_date'] ) {
			$filtered_data['bbe_date']    = $formats['bbe_date'] = NULL;
			$filtered_data['expiry_days'] = 0; // If there is no BBE date the expiry days makes no sense.
		}

		// The inventories always should have a date.
		if ( ! $this->inventory_date ) {
			$this->data['inventory_date'] = $filtered_data['inventory_date'] = new \WC_DateTime( 'now', new \DateTimeZone( 'UTC' ) );
		}

		// Once all the data is set, sanitize it before saving to db.
		$filtered_data = $this->sanitize_data_for_db( $filtered_data );
		
		// Update row.
		if ( $this->id && is_numeric( $this->id ) ) {
			
			$wpdb->update(
				$wpdb->prefix . self::INVENTORIES_TABLE,
				$filtered_data,
				array( 'id' => $this->id ),
				array_values( $formats ),
				array( '%d' )
			);
			
		}
		// Insert row.
		else {
			
			$wpdb->insert(
				$wpdb->prefix . self::INVENTORIES_TABLE,
				$filtered_data,
				array_values( $formats )
			);

			$this->id = $wpdb->insert_id;
			
		}

		// Handle relationships.
		if ( $this->id ) {

			// Set the Inventory Locations.
			if ( ! empty( $locations ) ) {
				$this->set_locations( $locations );
			}
			else {
				$this->delete_locations();
			}

			// Set the regions if the region restriction mode is set to shipping-zones.
			if ( 'shipping-zones' === $region_restriction_mode ) {

				if ( ! empty( $regions ) ) {
					$this->set_regions( $regions );
				}
				else {
					$this->delete_regions( $region_restriction_mode );
				}

			}

		}
		
		do_action( 'atum/multi_inventory/after_save_inventory', $this->data );
		
		return $this->id;
		
	}

	/**
	 * Ensure that all the data has the right format
	 *
	 * @since 1.0.7
	 */
	public function sanitize_data_for_display() {

		foreach ( $this->data as $data_key => $data_value ) {

			switch ( $data_key ) {
				// Yes/No columns.
				case 'is_main':
				case 'write_off':
					$this->data[ $data_key ] = wc_bool_to_string( $data_value );
					break;

				// Integer columns.
				case 'product_id':
				case 'priority':
				case 'expiry_days':
					$this->data[ $data_key ] = absint( $data_value );
					break;

				// Stock columns.
				case 'stock_on_hold':
				case 'sold_today':
				case 'sales_last_days':
				case 'reserved_stock':
				case 'customer_returns':
				case 'warehouse_damage':
				case 'lost_in_post':
				case 'other_logs':
				case 'lost_sales':
				case 'out_stock_days':
					$this->data[ $data_key ] = in_array( $data_value, [ NULL, '' ] ) ? NULL : wc_stock_amount( $data_value );
					break;

				// Date columns.
				case 'inventory_date':
				case 'bbe_date':
					if ( $data_value && ! $data_value instanceof \WC_DateTime ) {
						$this->data[ $data_key ] = new \WC_DateTime( $data_value, new \DateTimeZone( 'UTC' ) );
					}

					break;

				// Text columns.
				default:
					if ( is_array( $data_value ) ) {
						$this->data[ $data_key ] = array_map( 'esc_attr', $data_value );
					}
					else {
						$this->data[ $data_key ] = esc_attr( $data_value );
					}

					break;
			}

		}

	}

	/**
	 * Sanitize the inventory data values to have the right format before saving them to db
	 *
	 * @since 1.0.7
	 *
	 * @param array $data The data to be sanitized.
	 *
	 * @return array The sanitized data array
	 */
	protected function sanitize_data_for_db( $data ) {

		$sanitized_data = array();

		foreach ( $data as $data_key => $data_value ) {

			switch ( $data_key ) {
				// Yes/No columns.
				case 'is_main':
				case 'write_off':
					$sanitized_data[ $data_key ] = wc_string_to_bool( $data_value );
					break;

				// Integer columns.
				case 'product_id':
				case 'priority':
				case 'out_stock_days':
				case 'expiry_days':
					$sanitized_data[ $data_key ] = absint( $data_value );
					break;

				// Date columns.
				case 'inventory_date':
				case 'bbe_date':
					if ( $data_value instanceof \WC_DateTime ) {
						$sanitized_data[ $data_key ] = $data_value->date( 'Y-m-d H:i:s' );
					}
					else {
						$sanitized_data[ $data_key ] = NULL;
					}

					break;

				// Stock columns.
				case 'stock_on_hold':
				case 'sold_today':
				case 'sales_last_days':
				case 'reserved_stock':
				case 'customer_returns':
				case 'warehouse_damage':
				case 'lost_in_post':
				case 'other_logs':
				case 'lost_sales':
					$sanitized_data[ $data_key ] = wc_stock_amount( $data_value );
					break;

				// The region can have distinct formats and the sanitization is handled by the save method.
				case 'region':
					$sanitized_data[ $data_key ] = $data_value;
					break;

				// Other text columns.
				default:
					if ( is_array( $data_value ) ) {
						$sanitized_data[ $data_key ] = maybe_serialize( $data_value );
					}
					else {
						$sanitized_data[ $data_key ] = esc_attr( $data_value );
					}

					break;
			}

		}

		return $sanitized_data;

	}

	/**
	 * Saves the given meta key/value pairs
	 *
	 * @since 1.0.0
	 *
	 * @param string $meta_key Individual meta key to save, defaults to ''.
	 */
	public function save_meta( $meta_key = '' ) {

		// The Main Inventory meta should never be saved as its meta is being handled by WC.
		// If we need this in the future, we could refactory it.
		if ( $this->is_main() ) {
			return;
		}

		$this->validate_meta();

		// Restore stock on removing bbe date.
		if ( ! $this->is_expired( TRUE ) && $this->expired_stock > 0 && 'no' === $this->manage_stock ) {
			$this->meta['stock_quantity'] = $this->expired_stock;
			$this->meta['manage_stock']   = 'yes';
			$this->meta['stock_status']   = 'instock';
			$this->meta['expired_stock']  = NULL;
		}

		$meta = $this->sanitize_meta_for_db( $meta_key );
		
		// Translate backorders for the database.
		if ( isset( $meta['backorders'] ) ) {
			$meta['backorders'] = (int) array_search( $meta['backorders'], self::BACKORDERS_STATUSES );
		}

		global $wpdb;

		$inventory_meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->prefix" . self::INVENTORY_META_TABLE . ' WHERE inventory_id = %d', $this->id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Check whether to do an insert or update.
		if ( ! $this->id || ! $inventory_meta_id ) {

			// When inserting a new meta row, we have to record the inventory ID.
			$meta['inventory_id'] = $this->id;

			$wpdb->insert(
				$wpdb->prefix . self::INVENTORY_META_TABLE,
				$meta
			);

		}
		else {

			$wpdb->update(
				$wpdb->prefix . self::INVENTORY_META_TABLE,
				$meta,
				[ 'id' => $inventory_meta_id ]
			);

		}

	}

	/**
	 * Ensure properties are set correctly before save
	 *
	 * @since 1.0.0
	 */
	public function validate_meta() {
		
		// Before updating, ensure stock props are all aligned. Qty and backorders are not needed if not stock managed.
		if ( 'no' === $this->manage_stock ) {
			$this->meta['stock_quantity'] = '';
			$this->meta['backorders']     = 'no';
		}
		else {
			$this->set_stock_status();
		}

		//
		// Handle stock changes.
		// ---------------------!
		if ( ! empty( $this->stock_quantity ) ) {

			$current_inventory_stock = $this->stock_quantity;

			if ( ! empty( $this->original_stock ) && wc_stock_amount( $current_inventory_stock ) !== wc_stock_amount( $this->original_stock ) ) {
				/* translators: first one is the inventory name and second the stock quantity */
				\WC_Admin_Meta_Boxes::add_error( sprintf( __( 'The stock has not been updated because the value has changed since editing. Inventory %1$s has %2$d units in stock.', ATUM_MULTINV_TEXT_DOMAIN ), $this->name, $this->stock_quantity ) );
				$this->meta['stock_quantity'] = wc_stock_amount( $this->original_stock );
			}
			else {
				$this->meta['stock_quantity'] = wc_stock_amount( $this->stock_quantity );
			}

		}

		// The original stock must not be saved.
		unset( $this->original_stock );

		//
		// Handle price changes.
		// ---------------------!
		if ( Helpers::has_multi_price( $this->product_id ) ) {

			// The sale price cannot be higher than the regular price.
			if ( $this->regular_price && $this->sale_price >= $this->regular_price ) {
				/* translators: the inventory name */
				\WC_Admin_Meta_Boxes::add_error( sprintf( __( "The sale price of the Inventory %s can't be higher than its regular price and was adjusted accordingly.", ATUM_MULTINV_TEXT_DOMAIN ), $this->name ) );
				$this->meta['sale_price'] = $this->regular_price;
			}

			// Check for sale dates.
			if ( $this->date_on_sale_from || $this->date_on_sale_to ) {

				/**
				 * Variable definition
				 *
				 * @var \WC_DateTime $sale_date_from
				 * @var \WC_DateTime $sale_date_to
				 */
				$sale_date_from = wc_clean( $this->date_on_sale_from );
				$sale_date_to   = wc_clean( $this->date_on_sale_to );

				if ( $sale_date_to && ! $sale_date_from ) {
					$sale_date_from = new \WC_DateTime( 'now', new \DateTimeZone( 'UTC' ) );
				}

				$timestamp = AtumHelpers::get_current_timestamp();
				if ( $sale_date_to && $sale_date_to->getOffsetTimestamp() < $timestamp ) {
					$sale_date_from = $sale_date_to = '';
				}

				$this->set_meta( [
					'date_on_sale_from' => $sale_date_from,
					'date_on_sale_to'   => $sale_date_to,
				] );

			}

			$this->meta['price'] = $this->is_on_sale() ? $this->sale_price : $this->regular_price;

		}

	}

	/**
	 * Remove the current inventory and all its related data
	 *
	 * @since 1.0.0
	 *
	 * @return int|bool
	 */
	public function delete() {
		
		global $wpdb;

		// Remove the locations.
		$this->delete_locations();

		// Remove shipping zone regions (if any).
		$this->delete_regions( 'shipping-zones' );

		// Delete all the meta data.
		$this->delete_meta();

		// Delete the inventory.
		$deleted = $wpdb->delete(
			$wpdb->prefix . self::INVENTORIES_TABLE,
			array( 'id' => $this->id ),
			array( '%d' )
		);

		do_action( 'atum/multi_inventory/after_delete_inventory', $this );
		
		return $deleted;
		
	}

	/**
	 * Delete all the meta data associated to the current inventory
	 *
	 * @since 1.0.0
	 *
	 * @return int|bool
	 */
	public function delete_meta() {

		global $wpdb;

		return $wpdb->delete(
			$wpdb->prefix . self::INVENTORY_META_TABLE,
			array( 'inventory_id' => $this->id ),
			array( '%d' )
		);

	}

	/**
	 * Returns all the data + meta associated to the current inventory
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_all_data() {

		return array_merge( array( 'id' => $this->id ), $this->data, $this->meta );

	}

	/**
	 * Returns requested meta keys' values
	 *
	 * @since 1.0.0
	 *
	 * @param int $inventory_id  Optional. The inventory ID.
	 *
	 * @return string|array
	 */
	public function get_meta( $inventory_id = 0 ) {

		if ( ! $inventory_id ) {
			$inventory_id = $this->id;
		}

		if ( ! $inventory_id ) {
			return array();
		}

		// The main inventory is the WC product, so get the meta from it.
		if ( $this->is_main() ) {

			$product   = AtumHelpers::get_atum_product( $this->product_id );
			$meta_data = array();

			if ( $product instanceof \WC_Product ) {
				$meta_data                   = $product->get_data();
				$meta_data['shipping_class'] = isset( $meta_data['shipping_class_id'] ) ? $meta_data['shipping_class_id'] : NULL; // Adjust the name.
			}

		}
		else {
			global $wpdb;
			$meta_data = (array) $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->prefix" . self::INVENTORY_META_TABLE . ' WHERE inventory_id = %d', $inventory_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			
			// Translate backorders database values.
			$meta_data['backorders'] = empty( $meta_data['backorders'] ) ? self::BACKORDERS_STATUSES[0] : self::BACKORDERS_STATUSES[ $meta_data['backorders'] ];
		}

		// Get rid of non-accepted meta.
		$meta_data = array_intersect_key( $meta_data, $this->meta );

		return $meta_data;

	}

	/**
	 * Ensure that all the meta has the right format when getting meta
	 *
	 * @since 1.0.7
	 */
	public function sanitize_meta_for_display() {

		foreach ( $this->meta as $meta_key => $meta_value ) {

			switch ( $meta_key ) {
				case 'manage_stock':
				case 'sold_individually':
					$this->meta[ $meta_key ] = wc_bool_to_string( $meta_value );
					break;

				case 'price':
				case 'regular_price':
				case 'sale_price':
				case 'purchase_price':
					$this->meta[ $meta_key ] = in_array( $meta_value, [ NULL, '' ], TRUE ) ? '' : wc_format_decimal( $meta_value );
					break;

				case 'stock_quantity':
					$this->meta[ $meta_key ] = in_array( $meta_value, [ NULL, '' ], TRUE ) ? NULL : wc_stock_amount( $meta_value );
					break;

				case 'expired_stock':
					$this->meta[ $meta_key ] = in_array( $meta_value, [ NULL, '', 0 ], TRUE ) ? NULL : wc_stock_amount( $meta_value );
					break;

				case 'out_stock_threshold':
					$this->meta[ $meta_key ] = in_array( $meta_value, [ NULL, '' ], TRUE ) ? '' : wc_stock_amount( $meta_value );
					break;
					
				case 'supplier_id':
				case 'shipping_class':
					$this->meta[ $meta_key ] = in_array( $meta_value, [ NULL, '' ], TRUE ) ? '' : absint( $meta_value );
					break;

				case 'out_stock_date':
				case 'date_on_sale_from':
				case 'date_on_sale_to':
				case 'update_date':
					if ( $meta_value && ! $meta_value instanceof \WC_DateTime ) {
						$this->meta[ $meta_key ] = new \WC_DateTime( $meta_value, new \DateTimeZone( 'UTC' ) );
					}

					break;

				default:
					$this->meta[ $meta_key ] = esc_attr( $meta_value );
					break;
			}

		}

	}

	/**
	 * Sanitize the meta values to have the right format before saving them to db
	 *
	 * @since 1.0.7
	 *
	 * @param string $meta_key Optional. Individual meta key to sanitize, defaults to ''.
	 *
	 * @return array The sanitized meta array
	 */
	protected function sanitize_meta_for_db( $meta_key = '' ) {

		$sanitized_meta   = array();
		$meta_to_sanitize = $meta_key ? array( $meta_key => $this->meta[ $meta_key ] ) : $this->meta;

		foreach ( $meta_to_sanitize as $key => $value ) {

			switch ( $key ) {
				case 'manage_stock':
				case 'sold_individually':
					$sanitized_meta[ $key ] = wc_string_to_bool( $value );
					break;

				case 'price':
				case 'regular_price':
				case 'sale_price':
				case 'purchase_price':
					$sanitized_meta[ $key ] = in_array( $value, [ NULL, '' ], TRUE ) ? NULL : wc_format_decimal( $value );
					break;

				case 'stock_quantity':
					$sanitized_meta[ $key ] = wc_stock_amount( $value );
					break;

				case 'expired_stock':
					$sanitized_meta[ $key ] = in_array( $value, [ NULL, '', 0 ] ) ? NULL : wc_stock_amount( $value );
					break;

				case 'out_stock_threshold':
					$sanitized_meta[ $key ] = in_array( $value, [ NULL, '' ] ) ? NULL : wc_stock_amount( $value );
					break;

				case 'supplier_id':
				case 'shipping_class':
					$sanitized_meta[ $key ] = in_array( $value, [ NULL, '' ] ) ? NULL : absint( $value );
					break;

				case 'out_stock_date':
				case 'date_on_sale_from':
				case 'date_on_sale_to':
				case 'update_date':
					if ( $value instanceof \WC_DateTime ) {
						$sanitized_meta[ $key ] = $value->date( 'Y-m-d H:i:s' );
					}
					else {
						$sanitized_meta[ $key ] = NULL;
					}

					break;

				default:
					$sanitized_meta[ $key ] = esc_attr( $value );
					break;
			}

		}

		if ( ( isset( $sanitized_meta['manage_stock'] ) && ! $sanitized_meta['manage_stock'] ) || 'no' === $this->meta['manage_stock'] ) {
			$sanitized_meta['stock_quantity'] = NULL;
		}

		return $sanitized_meta;

	}

	/**
	 * Get the inventories linked to a specific product
	 *
	 * @since 1.0.0
	 *
	 * @param int|array $product_id     The ID(s) of the product holding the inventories.
	 * @param string    $name           Optional. If passed, will return only the matching inventory.
	 * @param bool      $not_main       Optional. Whether to return the Main inventory too.
	 * @param bool      $not_write_off  Optional. Whether to return the inventories in "write-off" status too.
	 * @param bool      $return_objects Optional. Whether to return Inventory objects or raw db results.
	 *
	 * @return Inventory[]
	 */
	public static function get_product_inventories( $product_id, $name = '', $not_main = TRUE, $not_write_off = FALSE, $return_objects = TRUE ) {

		$cache_key   = AtumCache::get_cache_key( 'product_inventories', [ $product_id, $name, $not_main, $not_write_off, $return_objects ] );
		$inventories = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $inventories;
		}

		global $wpdb;

		$default_data = self::$default_data;
		$default_meta = self::$default_meta;
		unset( $default_data['location'] );
		unset( $default_meta['original_stock'] );

		$fields          = $return_objects ? 'i.' . implode( ', i.', array_keys( $default_data ) ) . ', m.' . implode( ', m.', array_keys( $default_meta ) ) : ' i.is_main, i.product_id';
		$product_id      = apply_filters( 'atum/multi_inventory/product_id', $product_id );
		$name_where      = $name ? $wpdb->prepare( 'AND i.`name` LIKE %s', $name ) : '';
		$main_where      = $not_main ? 'AND i.`is_main` = 0' : '';
		$write_off_where = $not_write_off ? 'AND i.`write_off` = 0' : '';

		$query = "
			SELECT i.id, $fields
			FROM $wpdb->prefix" . self::INVENTORIES_TABLE . " i 
			LEFT JOIN $wpdb->prefix" . self::INVENTORY_META_TABLE . ' m ON i.`id` = m.`inventory_id`
			WHERE i.`product_id` IN (' . implode( ',', (array) $product_id ) . ") $name_where $main_where $write_off_where
			ORDER BY i.`priority` ASC
		";

		$inventories = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$return_inventories      = [];
		$region_restriction_mode = Helpers::get_region_restriction_mode();

		foreach ( (array) $product_id as $temp_product_id ) {

			if ( $inventories ) {

				$product_inventories = array_filter( $inventories, function ( $obj ) use ( $temp_product_id ) {
					return ( (int) $temp_product_id === (int) $obj->product_id );
				} );

				if ( $product_inventories ) {

					if ( $return_objects ) {

						foreach ( $product_inventories as $inventory ) {

							// Array format is needed to seg_data and set_meta.
							$inventory = (array) $inventory;

							$is_main = absint( $inventory['is_main'] ) === 1;

							// Maybe read it from the cache.
							$key_main       = $is_main ? '_main' : '';
							$inv_cache_key  = AtumCache::get_cache_key( "get_inventory$key_main", [ $inventory['id'], $temp_product_id ] );
							$read_inventory = AtumCache::get_cache( $inv_cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

							if ( ! $has_cache || ! $read_inventory instanceof Inventory ) {

								if ( $is_main ) {

									// The meta is set by reading the product.
									$read_inventory = new MainInventory( $inventory['id'], $temp_product_id, FALSE );
									$inventory      = array_merge( $inventory, $read_inventory->get_meta() );
								}
								else {
									$read_inventory          = new Inventory( $inventory['id'], FALSE );
									$inventory['backorders'] = empty( $inventory['backorders'] ) ? self::BACKORDERS_STATUSES[0] : self::BACKORDERS_STATUSES[ $inventory['backorders'] ];
								}

								$read_inventory->set_meta( $inventory );

								$inventory['location'] = $read_inventory->get_locations();

								// Get the linked regions.
								if ( 'shipping-zones' === $region_restriction_mode ) {
									$inventory['region'] = $read_inventory->get_shipping_zones();
								}
								elseif ( 'countries' === $region_restriction_mode ) {
									$inventory['region'] = maybe_unserialize( $inventory['region'] );
								}

								$read_inventory->set_data( $inventory );
								$read_inventory->set_is_read( TRUE );
								AtumCache::set_cache( $inv_cache_key, $read_inventory, ATUM_MULTINV_TEXT_DOMAIN );

							}

							$return_inventories[] = $read_inventory;
						}

					}
					else {
						$return_inventories = array_merge( $return_inventories, $product_inventories );
					}

				}
				elseif ( ! $not_main && 'yes' === Helpers::get_product_multi_inventory_status( $temp_product_id ) ) {

					$main_inventory = self::get_product_main_inventory( $temp_product_id );

					if ( $return_objects ) {
						$return_inventories[] = $main_inventory;
					}
					else {

						$return_inventories[] = (object) array(
							'id'         => $main_inventory->id,
							'is_main'    => $main_inventory->is_main(),
							'product_id' => $main_inventory->product_id,
						);

					}
				}

			}
			elseif ( ! $not_main && 'yes' === Helpers::get_product_multi_inventory_status( $temp_product_id ) ) {

				$main_inventory = self::get_product_main_inventory( $temp_product_id );

				if ( $return_objects ) {
					$return_inventories[] = $main_inventory;
				}
				else {
					$return_inventories[] = (object) array(
						'id'         => $main_inventory->id,
						'is_main'    => $main_inventory->is_main(),
						'product_id' => $main_inventory->product_id,
					);
				}
			}
		}

		AtumCache::set_cache( $cache_key, $return_inventories, ATUM_MULTINV_TEXT_DOMAIN );

		return $return_inventories;

	}

	/**
	 * Remove all inventories related to a product
	 *
	 * @since 1.2.5.1
	 *
	 * @param integer $product_id
	 */
	public static function delete_inventories( $product_id ) {

		$product_inventories = self::get_product_inventories( $product_id, FALSE, FALSE );

		if ( ! empty( $product_inventories ) ) {
			foreach ( $product_inventories as $inventory ) {
				$inventory->delete();
			}
		}

	}

	/**
	 * Get the inventories associated to a specific region (or set of regions)
	 *
	 * @since 1.0.0
	 *
	 * @param array  $regions       An array of regions used to filter the inventories.
	 * @param string $region_type   The region type: "countries" or "shipping-zones".
	 *
	 * @return Inventory|array
	 */
	public static function get_region_inventories( $regions, $region_type ) {

		$cache_key   = AtumCache::get_cache_key( 'region_inventories', [ $regions, $region_type ] );
		$inventories = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $inventories;
		}

		global $wpdb;

		if ( 'countries' === $region_type ) {

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$query = $wpdb->prepare("
				SELECT id 
				FROM $wpdb->prefix" . self::INVENTORIES_TABLE . '
				WHERE `region` = %s
			', maybe_serialize( (array) $regions ) );
			// phpcs:enable

		}
		else {

			$regions = array_map( 'absint', $regions );

			// The query must get all the inventory IDs that match exactly with the set of queried zones.
			$query = "
				SELECT DISTINCT inventory_id
				FROM $wpdb->prefix" . self::INVENTORY_REGIONS_TABLE . " ir		  
				WHERE '" . implode( ',', $regions ) . "' = (
					SELECT GROUP_CONCAT(zone_id) 
					FROM $wpdb->prefix" . self::INVENTORY_REGIONS_TABLE . ' nir 
					WHERE nir.inventory_id = ir.inventory_id 
				)			  		
			';

		}

		$inventories = $wpdb->get_col( $query );// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $inventories ) {

			// Convert to an array of Inventory objects.
			array_walk( $inventories, function( &$inventory ) {
				$inventory = new self( $inventory );
			} );

		}

		AtumCache::set_cache( $cache_key, $inventories, ATUM_MULTINV_TEXT_DOMAIN );

		return $inventories;

	}

	/**
	 * Get all the ATUM Locations linked to the current Inventory
	 *
	 * @since 1.0.0
	 */
	public function get_locations() {

		global $wpdb;

		if ( ! $this->id || ! is_numeric( $this->id ) ) {
			return [];
		}

		// Avoid duplicated queries.
		$cache_key = AtumCache::get_cache_key( 'inventory_locations', $this->id );
		$locations = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$query = $wpdb->prepare( "
				SELECT `term_taxonomy_id` 
				FROM $wpdb->prefix" . self::INVENTORY_LOCATIONS_TABLE . '
				WHERE `inventory_id` = %d
				ORDER BY `term_order` ASC
			', $this->id );
			// phpcs:enable

			$locations = [];
			$result    = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			foreach ( $result as $term_taxonomy_id ) {

				$term = get_term_by( 'term_taxonomy_id', $term_taxonomy_id, Globals::PRODUCT_LOCATION_TAXONOMY );

				if ( $term ) {
					$locations[] = $term->term_id;
				}

			}

			AtumCache::set_cache( $cache_key, $locations, ATUM_MULTINV_TEXT_DOMAIN );

		}

		return $locations;

	}

	/**
	 * Set the locations for the current inventory
	 *
	 * @since 1.0.0
	 *
	 * @param array $locations
	 */
	public function set_locations( array $locations ) {

		if ( ! $this->id || ! is_numeric( $this->id ) ) {
			return;
		}

		global $wpdb;
		$inventory_locations_table = $wpdb->prefix . self::INVENTORY_LOCATIONS_TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL
		$inventory_locations = $wpdb->get_col( $wpdb->prepare(
			"SELECT `term_taxonomy_id` FROM $inventory_locations_table WHERE `inventory_id` = %d",
			$this->id
		) );
		// phpcs:enable

		foreach ( $locations as $location ) {

			$location_id = absint( $location );

			if ( ! $location_id ) {
				continue;
			}

			// Check that an ATUM Location with this ID still exists (use cache to avoid duplicated queries).
			$cache_key     = AtumCache::get_cache_key( 'location_exists', $location_id );
			$atum_location = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

			if ( ! $has_cache ) {
				$atum_location = get_term( $location_id, Globals::PRODUCT_LOCATION_TAXONOMY );
				AtumCache::set_cache( $cache_key, $atum_location, ATUM_MULTINV_TEXT_DOMAIN );
			}

			if ( ! is_wp_error( $atum_location ) ) {

				if ( ! in_array( $atum_location->term_taxonomy_id, $inventory_locations ) ) {

					// Add new relationship.
					$wpdb->insert(
						$inventory_locations_table,
						array(
							'inventory_id'     => $this->id,
							'term_taxonomy_id' => $atum_location->term_taxonomy_id,
						),
						array(
							'%d',
							'%d',
						)
					);

				}
				else {
					$index = array_search( $atum_location->term_taxonomy_id, $inventory_locations );

					if ( FALSE !== $index ) {
						unset( $inventory_locations[ $index ] );
					}
				}

			}

		}

		// Unlink removed locations (if any).
		if ( ! empty( $inventory_locations ) ) {

			foreach ( $inventory_locations as $location_id ) {

				$wpdb->delete(
					$inventory_locations_table,
					array(
						'inventory_id'     => $this->id,
						'term_taxonomy_id' => $location_id,
					),
					array(
						'%d',
						'%d',
					)
				);

			}

		}

	}

	/**
	 * Delete all the locations for the current inventory
	 *
	 * @since 1.0.0
	 */
	public function delete_locations() {

		global $wpdb;

		$wpdb->delete(
			$wpdb->prefix . self::INVENTORY_LOCATIONS_TABLE,
			array(
				'inventory_id' => $this->id,
			),
			array(
				'%d',
			)
		);

	}

	/**
	 * Get all the shipping zones linked to the current Inventory
	 *
	 * @since 1.0.0
	 */
	public function get_shipping_zones() {

		global $wpdb;

		if ( ! $this->id || ! is_numeric( $this->id ) ) {
			return array();
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( "
			SELECT `zone_id` 
			FROM $wpdb->prefix" . self::INVENTORY_REGIONS_TABLE . '
			WHERE `inventory_id` = %d
			ORDER BY `region_order` ASC
		', $this->id );
		// phpcs:enable

		return $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	}

	/**
	 * Set the shipping zones for the current inventory
	 *
	 * @since 1.0.0
	 *
	 * @param array $zones
	 */
	public function set_regions( array $zones ) {

		if ( ! $this->id || ! is_numeric( $this->id ) ) {
			return;
		}

		global $wpdb;
		$inventory_regions_table = $wpdb->prefix . self::INVENTORY_REGIONS_TABLE;
		$shipping_zones          = Helpers::get_regions( 'shipping-zones' );

		// phpcs:disable WordPress.DB.PreparedSQL
		$inventory_regions = $wpdb->get_col( $wpdb->prepare(
			"SELECT `zone_id` FROM $inventory_regions_table WHERE `inventory_id` = %d",
			$this->id
		) );
		// phpcs:enable

		foreach ( $zones as $zone ) {

			$zone_id = absint( $zone );

			if ( ! $zone_id ) {
				continue;
			}

			// Check that a WC Shipping Zone with this ID still exists.
			$shipping_zone = wp_list_filter( $shipping_zones, [ 'id' => $zone_id ] );

			$where = array(
				'inventory_id' => $this->id,
				'zone_id'      => $zone_id,
			);

			$where_format = array(
				'%d',
				'%d',
			);

			if ( ! empty( $shipping_zone ) ) {

				if ( ! in_array( $zone_id, $inventory_regions ) ) {

					// Add new relationship.
					$wpdb->insert( $inventory_regions_table, $where, $where_format );

				}

			}
			// Shipping zone was removed from the system?
			else {
				$wpdb->delete( $inventory_regions_table, $where, $where_format );
			}

			$found_index = array_search( $zone_id, $inventory_regions );

			if ( FALSE !== $found_index ) {
				unset( $inventory_regions[ $found_index ] );
			}

		}

		// Unlink removed regions (if any).
		if ( ! empty( $inventory_regions ) ) {

			foreach ( $inventory_regions as $region_id ) {

				$wpdb->delete(
					$inventory_regions_table,
					array(
						'inventory_id' => $this->id,
						'zone_id'      => $region_id,
					),
					array(
						'%d',
						'%d',
					)
				);

			}

		}

	}

	/**
	 * Delete all the regions for the current inventory
	 *
	 * @since 1.0.0
	 *
	 * @param string $region_type   The region type to remove: "countries" or "shipping-zones".
	 */
	public function delete_regions( $region_type ) {

		global $wpdb;

		if ( 'countries' === $region_type ) {

			// Set the `region` column to NULL.
			$wpdb->update(
				$wpdb->prefix . self::INVENTORIES_TABLE,
				array(
					'region' => NULL,
				),
				array(
					'id' => $this->id,
				),
				array(
					NULL,
				),
				array(
					'%d',
				)
			);

		}
		else {

			// Delete all the records related to the current inventory in the regions table.
			$wpdb->delete(
				$wpdb->prefix . self::INVENTORY_REGIONS_TABLE,
				array(
					'inventory_id' => $this->id,
				),
				array(
					'%d',
				)
			);

		}

	}

	/**
	 * Get the Main Inventory for the specified product
	 *
	 * @since 1.0.0
	 *
	 * @param int $product_id Must be original translation.
	 *
	 * @return MainInventory
	 */
	public static function get_product_main_inventory( $product_id ) {

		$cache_key      = AtumCache::get_cache_key( 'product_main_inventory', $product_id );
		$main_inventory = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			global $wpdb;

			$product_id = apply_filters( 'atum/multi_inventory/product_id', $product_id );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$query = $wpdb->prepare( "
				SELECT id 
				FROM $wpdb->prefix" . self::INVENTORIES_TABLE . '
				WHERE `product_id` = %d AND `is_main` = 1	  
			', $product_id );
			// phpcs:enable

			$main_inventory_id = absint( $wpdb->get_var( $query ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$main_inventory    = Helpers::get_inventory( $main_inventory_id, $product_id, TRUE );

			AtumCache::set_cache( $cache_key, $main_inventory, ATUM_MULTINV_TEXT_DOMAIN );

		}

		return $main_inventory;

	}

	/**
	 * Set current stock status depending on settings and meta values
	 *
	 * @since 1.0.0
	 */
	public function set_stock_status() {

		if ( ! $this->managing_stock() ) {
			return;
		}
		
		if ( AtumHelpers::get_option( 'out_stock_threshold', 'no' ) === 'yes' ) {
			
			$threshold = $this->out_stock_threshold;
			
			if ( FALSE === $threshold || '' === $threshold ) {
				$threshold = get_option( 'woocommerce_notify_no_stock_amount' );
			}

		}
		else {
			$threshold = get_option( 'woocommerce_notify_no_stock_amount' );
		}

		$threshold = wc_stock_amount( $threshold );
		
		// If we are stock managing and we don't have stock, force out of stock status.
		if ( $this->stock_quantity <= $threshold && 'no' === $this->backorders ) {
			$this->meta['stock_status']   = 'outofstock';
			$timestamp                    = AtumHelpers::get_current_timestamp();
			$this->meta['out_stock_date'] = AtumHelpers::date_format( $timestamp, TRUE );
		}
		// If we are stock managing, backorders are allowed, and we don't have stock, force on backorder status.
		elseif ( $this->stock_quantity <= $threshold && 'no' !== $this->backorders ) {
			$this->meta['stock_status']   = 'onbackorder';
			$this->meta['out_stock_date'] = '';
		}
		// If the stock level is changing and we do now have enough, force in stock status.
		elseif ( $this->stock_quantity > $threshold ) {
			$this->meta['stock_status']   = 'instock';
			$this->meta['out_stock_date'] = '';
		}
		
	}

	/**
	 * Get the Inventories linked to a specified Order Item
	 *
	 * @since 1.0.1
	 *
	 * @param int $order_item_id
	 * @param int $order_type_id
	 *
	 * @return array|NULL
	 */
	public static function get_order_item_inventories( $order_item_id, $order_type_id = 1 ) {

		$cache_key              = AtumCache::get_cache_key( 'order_item_inventories', [ $order_item_id, $order_type_id ] );
		$order_item_inventories = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $order_item_inventories;
		}

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( "
			SELECT * 
			FROM $wpdb->prefix" . self::INVENTORY_ORDERS_TABLE . '
			WHERE `order_item_id` = %d
			AND `order_type` = %d
		', $order_item_id, $order_type_id );
		// phpcs:enable

		$order_item_inventories = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		AtumCache::set_cache( $cache_key, $order_item_inventories, ATUM_MULTINV_TEXT_DOMAIN );

		return $order_item_inventories;

	}

	/* @noinspection PhpDocSignatureInspection */
	/**
	 * Register the stock changes from WC orders
	 *
	 * @since 1.0.1
	 *
	 * @param int   $order_item_id
	 * @param int   $product_id     Must be original translation.
	 * @param array $data           {
	 *      Array of arguments.
	 *
	 *      @type float  $qty
	 *      @type string $subtotal
	 *      @type string $total
	 *      @type string $extra_data Serialized string
	 *      @type float  $reduced_stock
	 *      @type float  $refund_qty
	 *      @type string $refund_total
	 * }
	 * @param int   $order_type_id
	 */
	public function save_order_item_inventory( $order_item_id, $product_id, $data, $order_type_id = 1 ) {

		global $wpdb;

		// Check if the specified Order Item already has an entry in the db.
		$order_item_rows = self::get_order_item_inventories( $order_item_id, $order_type_id );
		$table           = $wpdb->prefix . self::INVENTORY_ORDERS_TABLE;

		// There are entries for this Order Item --> UPDATE.
		if ( ! empty( $order_item_rows ) && ! empty( wp_list_filter( $order_item_rows, [ 'inventory_id' => $this->id ] ) ) ) {

			$data_string = '';

			foreach ( self::INVENTORY_DATA_TYPES as $name => $type ) {

				if ( array_key_exists( $name, $data ) ) {

					if ( NULL === $data[ $name ] ) {
						$value = 'NULL';
					}
					else {
						$value = 'string' === $type ? "'{$data[ $name ]}'" : $data[ $name ];
					}

					$data_string .= "$name = $value,";

				}
			}

			$data_string = rtrim( $data_string, ',' );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "
				UPDATE $table
				SET $data_string
				WHERE order_item_id = {$order_item_id}
				AND inventory_id = {$this->id}
				AND order_type = $order_type_id
			" );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

		}
		// There are no entries for this Order Item --> INSERT.
		else {

			$data = array_merge( self::DEFAULT_INVENTORY_ORDER_DATA, $data );

			$insert_fields = 'order_item_id, inventory_id, product_id, qty, subtotal, total, order_type, extra_data';
			$insert_data   = " $order_item_id, {$this->id}, $product_id, {$data['qty']} , '{$data['subtotal']}', '{$data['total']}', $order_type_id,'";
			$insert_data  .= ! empty( $data['extra_data'] ) ? $data['extra_data'] : maybe_serialize( $this->prepare_order_item_inventory_extra_data( $order_item_id ) );
			$insert_data  .= "'";

			if ( array_key_exists( 'reduced_stock', $data ) ) {

				$insert_fields .= ',reduced_stock';
				$insert_data   .= NULL === $data['reduced_stock'] ? ', NULL' : ", {$data['reduced_stock']}";
			}

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "
				INSERT INTO $table
				($insert_fields)
				VALUES($insert_data)
			" );
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

			do_action( 'atum/multi_inventory/after_save_order_item_inventory', $order_item_id, $product_id, $this, $data['qty'], $order_type_id );

		}
		
		$cache_key = AtumCache::get_cache_key( 'order_item_inventories', [ $order_item_id, $order_type_id ] );
		AtumCache::delete_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN );

	}
	
	/**
	 * Update the refund changes from WC orders
	 *
	 * @since 1.0.1
	 *
	 * @param int    $order_item_id
	 * @param float  $refund_qty
	 * @param string $refund_total
	 * @param int    $order_type_id
	 */
	public function save_order_item_inventory_refund( $order_item_id, $refund_qty, $refund_total, $order_type_id = 1 ) {
		
		global $wpdb;
		
		// Check if the specified Order Item already has an entry in the db.
		$order_item_rows = self::get_order_item_inventories( $order_item_id, $order_type_id );
		
		// There are entries for this Order Item --> UPDATE.
		if ( ! empty( $order_item_rows ) && ! empty( wp_list_filter( $order_item_rows, [ 'inventory_id' => $this->id ] ) ) ) {
			
			// Update the stock change.
			$wpdb->update(
				$wpdb->prefix . self::INVENTORY_ORDERS_TABLE,
				array(
					'refund_qty'   => $refund_qty,
					'refund_total' => $refund_total,
				),
				array(
					'order_item_id' => $order_item_id,
					'inventory_id'  => $this->id,
					'order_type'    => $order_type_id,
				),
				array(
					'%f',
					'%s',
				),
				array(
					'%d',
					'%d',
					'%d',
				)
			);
			
			$cache_key = AtumCache::get_cache_key( 'order_item_inventories', [ $order_item_id, $order_type_id ] );
			AtumCache::delete_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN );
			
		}
		
	}

	/**
	 * Deletes the current inventory relationship with the specified order item
	 *
	 * @since 1.0.1
	 *
	 * @param int $order_item_id
	 * @param int $order_id
	 * @param int $order_type_id
	 *
	 * @return int|bool
	 */
	public function delete_order_item_inventory( $order_item_id, $order_id, $order_type_id = 1 ) {
		
		global $wpdb;
		
		$result = $wpdb->delete( $wpdb->prefix . self::INVENTORY_ORDERS_TABLE, [
			'order_item_id' => $order_item_id,
			'inventory_id'  => $this->id,
			'order_type'    => $order_type_id,
		], [ '%d', '%d', '%d' ] );
		
		$cache_key = AtumCache::get_cache_key( 'order_item_inventories', [ $order_item_id, $order_type_id ] );
		AtumCache::delete_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN );

		do_action( 'atum/multi_inventory/after_delete_order_item_inventory', $order_item_id, $this, $order_id, $order_type_id );
		
		return $result;
	}

	/**
	 * Get a list of all the inventories which their BBE dates were reached
	 *
	 * @since 1.2.3.1
	 */
	public static function get_expired_inventories() {

		$cache_key           = AtumCache::get_cache_key( 'expired_inventories' );
		$expired_inventories = AtumCache::get_cache( $cache_key, ATUM_MULTINV_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $expired_inventories;
		}

		global $wpdb;

		// Get all the inventories taking into account their corresponding expiry days.
		$query = "
			SELECT * 
			FROM $wpdb->prefix" . self::INVENTORIES_TABLE . '
			WHERE `bbe_date` != 0 AND `bbe_date` < DATE_ADD( NOW(), INTERVAL `expiry_days` DAY )
		';

		$expired_inventories = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		AtumCache::set_cache( $cache_key, $expired_inventories, ATUM_MULTINV_TEXT_DOMAIN );

		return $expired_inventories;

	}
	
	/**
	 * Get the database order item table name from the table id
	 *
	 * @since 1.0.1
	 *
	 * @param int $table_id
	 *
	 * @return int
	 */
	public static function get_table_name( $table_id ) {
		return self::$item_table_names[ $table_id ];
	}

	/**
	 * Is the current inventory the main (WC) inventory?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_main() {
		return 'yes' === $this->is_main;
	}

	/**
	 * Is the current inventory in "Write-Off" status?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_write_off() {
		return 'yes' === $this->write_off;
	}

	/**
	 * Is the current inventory expired?
	 *
	 * @since 1.0.0
	 *
	 * @param bool $check_expiry_days Optional. Whether to take into account the expiry_days prop.
	 *
	 * @return bool
	 */
	public function is_expired( $check_expiry_days = FALSE ) {

		$current_timestamp = AtumHelpers::get_current_timestamp();

		/**
		 * Variable definition
		 *
		 * @var \WC_DateTime $bbe_date
		 */
		$bbe_date         = $this->bbe_date;
		$expiry_timestamp = 0;

		if ( $bbe_date ) {

			$expiry_days = absint( $this->expiry_days );

			if ( $check_expiry_days && $expiry_days > 0 ) {
				$expiry_timestamp = strtotime( $bbe_date->date( 'Y-m-d' ) . " - $expiry_days days" );
			}
			else {
				$expiry_timestamp = $bbe_date->getOffsetTimestamp();
			}

		}

		return $bbe_date && $expiry_timestamp < $current_timestamp;
	}

	/**
	 * Returns whether or not the current inventory is on sale
	 *
	 * @since 1.0.1
	 *
	 * @return bool
	 */
	public function is_on_sale() {

		if ( '' !== (string) $this->sale_price && $this->regular_price > $this->sale_price ) {

			$on_sale = TRUE;

			$date_on_sale_from = $this->date_on_sale_from instanceof \WC_DateTime ? $this->date_on_sale_from->getOffsetTimestamp() : $this->date_on_sale_from;
			$timestamp         = AtumHelpers::get_current_timestamp();

			if ( $date_on_sale_from && $date_on_sale_from > $timestamp ) {
				$on_sale = FALSE;
			}

			$date_on_sale_to = $this->date_on_sale_to instanceof \WC_DateTime ? $this->date_on_sale_to->getOffsetTimestamp() : $this->date_on_sale_to;

			if ( $date_on_sale_to && $date_on_sale_to < $timestamp ) {
				$on_sale = FALSE;
			}

		}
		else {
			$on_sale = FALSE;
		}

		return apply_filters( 'atum/multi_inventory/inventory_is_on_sale', $on_sale, $this );

	}

	/**
	 * Return if an inventory can be used in the frontend (items added to available stock or bought).
	 *
	 * @since 1.4.2
	 *
	 * @return bool
	 */
	public function is_sellable() {

		// NOTE: Avoiding this check was causing issues when saving inventories without price on the backend (atum_stock_status not being updated).
		// We'll have to check if this breaks something else.
		if ( is_admin() ) {
			return TRUE;
		}

		// has_multiprice is cached, so we can call twice the function.
		$is_sellable = ( Helpers::has_multi_price( $this->product_id ) && $this->price ) || ! Helpers::has_multi_price( $this->product_id );

		return apply_filters( 'atum/multi_inventory/inventory_sellable', $is_sellable, $this );
	}

	/**
	 * Check whether the current inventory's stock is being managed by WC
	 *
	 * @since 1.0.1
	 *
	 * @return bool
	 */
	public function managing_stock() {

		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
			return in_array( $this->manage_stock, [ 'yes', 'parent' ] );
		}

		return FALSE;

	}

	/**
	 * Get the max available stock quantity for the current inventory checking its "Out of Stock Threshold"
	 *
	 * @since 1.0.7
	 *
	 * @return float
	 */
	public function get_available_stock() {

		$inventory_out_stock_threshold = $this->out_stock_threshold;

		if ( AtumHelpers::get_option( 'out_stock_threshold', 'no' ) === 'yes' &&
			$inventory_out_stock_threshold > 0 && $this->stock_quantity >= $this->out_stock_threshold ) {

			$inventory_stock = $this->stock_quantity - $this->out_stock_threshold;
		}
		else {
			$inventory_stock = $this->stock_quantity;
		}

		return $inventory_stock;

	}

	/**
	 * Reset the inventory ID (useful when cloning the inventory for example)
	 *
	 * @since 1.3.0
	 */
	public function reset_id() {
		$this->id = 0; // Reset the ID, so when saved it won't affect the original.
	}

	/**
	 * Getter for the default_data prop
	 *
	 * @since 1.4.2
	 *
	 * @return array
	 */
	public static function get_default_data() {
		return self::$default_data;
	}

	/**
	 * Getter for the default_meta prop
	 *
	 * @since 1.4.2
	 *
	 * @return array
	 */
	public static function get_default_meta() {
		return self::$default_meta;
	}

	/**
	 * Get an order item inventory object from db matching the current inventory on specified order item ID
	 *
	 * @since 1.4.4
	 *
	 * @param int $order_item_id
	 *
	 * @return object
	 */
	public function get_order_item_inventory( $order_item_id ) {

		global $wpdb;

		// phpcs:disable
		return $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . $wpdb->prefix . self::INVENTORY_ORDERS_TABLE . ' WHERE order_item_id = %d AND inventory_id = %d',
			$order_item_id, $this->id
		) );
		// phpcs:enable

	}

	/**
	 * Prepare the extra data for the order item inventories
	 *
	 * @since 1.4.4
	 *
	 * @param int $order_item_id
	 *
	 * @return array
	 */
	public function prepare_order_item_inventory_extra_data( $order_item_id ) {

		$extra_data = array(
			'name'         => $this->name,
			'sku'          => $this->sku,
			'supplier_sku' => $this->supplier_sku,
		);

		if ( $this->is_main ) {
			$extra_data['is_main'] = wc_bool_to_string( $this->is_main );
		}

		return (array) apply_filters( 'atum/multi_inventory/order_item_inventory/extra_data', $extra_data, $order_item_id, $this->id );

	}


	/***************
	 * MAGIC METHODS
	 ***************/

	/**
	 * Magic Getter
	 * To avoid illegal access errors, the property being accessed must be declared within data or meta prop arrays
	 *
	 * @since 1.0.0
	 *
	 * @param string $name
	 *
	 * @return mixed|\WP_Error
	 */
	public function __get( $name ) {

		// Search in declared class props.
		if ( isset( $this->$name ) ) {
			return $this->$name;
		}

		// Search in props array.
		if ( array_key_exists( $name, $this->data ) ) {
			return $this->data[ $name ];
		}

		if ( array_key_exists( $name, $this->meta ) ) {
			return $this->meta[ $name ];
		}

		return new \WP_Error( __( 'Invalid property', ATUM_MULTINV_TEXT_DOMAIN ) );

	}

	/**
	 * Magic Unset
	 *
	 * @since 1.0.0
	 *
	 * @param string $name
	 */
	public function __unset( $name ) {

		if ( isset( $this->$name ) ) {
			unset( $this->$name );
		}
		elseif ( array_key_exists( $name, $this->data ) ) {
			unset( $this->data[ $name ] );
		}
		else {

			if ( array_key_exists( $name, $this->meta ) ) {
				unset( $this->meta[ $name ] );
			}
		}

	}

}
