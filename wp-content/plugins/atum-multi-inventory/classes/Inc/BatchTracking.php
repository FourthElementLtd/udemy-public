<?php
/**
 * Batch Tracking class
 *
 * @since       1.3.5
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     AtumMultiInventory\Inc
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Globals;
use AtumMultiInventory\Models\Inventory;

class BatchTracking {

	/**
	 * The singleton instance holder
	 *
	 * @var BatchTracking
	 */
	private static $instance;

	/**
	 * The available order types IDs
	 *
	 * @var array
	 */
	private $order_types_ids = array();

	/**
	 * BatchTracking singleton constructor
	 *
	 * @since 1.3.5
	 */
	private function __construct() {

		if ( is_admin() ) {

			$this->order_types_ids = Globals::get_order_type_table_id( '' );

			// Add the batch number's search box to WC Orders list.
			add_action( 'restrict_manage_posts', array( $this, 'add_batch_searchbox' ) );

			// Alter the orders quqery to search by MI batch numbers.
			add_filter( 'posts_where', array( $this, 'add_order_items_where' ) );

		}

	}

	/**
	 * Add the Batch number searchbox to Orders' List Tables
	 *
	 * @since 1.3.5
	 */
	public function add_batch_searchbox() {

		global $typenow;

		if ( in_array( $typenow, array_keys( $this->order_types_ids ), TRUE ) ) :
			$value = ! empty( $_REQUEST['mi_batch_number'] ) ? wc_clean( $_REQUEST['mi_batch_number'] ) : ''; ?>
			<input type="search" name="mi_batch_number" value="<?php echo esc_attr( $value ) ?>" placeholder="<?php esc_attr_e( 'Search batch number...', ATUM_MULTINV_TEXT_DOMAIN ); ?>">
		<?php endif;

	}


	/**
	 * Modify SQL WHERE for filtering the orders by batch numbers used
	 *
	 * @since 1.3.5
	 *
	 * @param string $where WHERE part of the sql query.
	 *
	 * @return string
	 */
	public function add_order_items_where( $where ) {

		global $typenow, $wpdb;

		if ( in_array( $typenow, array_keys( $this->order_types_ids ), TRUE ) && ! empty( $_REQUEST['mi_batch_number'] ) ) {

			$batch_number = wc_clean( $_REQUEST['mi_batch_number'] );

			if ( $batch_number ) {

				$inventories_table      = $wpdb->prefix . Inventory::INVENTORIES_TABLE;
				$inventory_orders_table = $wpdb->prefix . Inventory::INVENTORY_ORDERS_TABLE;
				$order_items_table      = 'shop_order' === $typenow ? 'woocommerce_order_items' : AtumOrderPostType::ORDER_ITEMS_TABLE;
				$order_type_id          = $this->order_types_ids[ $typenow ];

				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$batch_sql = $wpdb->prepare( "
					SELECT aoi.order_id FROM {$wpdb->prefix}{$order_items_table} aoi 
					LEFT JOIN $inventory_orders_table aio ON (aoi.order_item_id = aio.order_item_id AND aio.order_type = %d)
					LEFT JOIN $inventories_table ai ON (aio.inventory_id = ai.id)
					WHERE ai.lot = %s
				", $order_type_id, $batch_number );
				// phpcs:enable

				$where .= " AND ( $wpdb->posts.ID IN ($batch_sql) )";
				$where  = apply_filters( 'atum/multi_inventory/batch_tracking/order_items_where', $where, $batch_number, $order_type_id );

			}

		}

		return $where;

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
	 * @return BatchTracking instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
