<?php
/**
 * Multi-Inventory Upgrade tasks class
 *
 * @package         AtumMultiInventory
 * @subpackage      Inc
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.0.7
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Globals;
use Atum\InventoryLogs\InventoryLogs;
use AtumMultiInventory\Models\Inventory;


class Upgrade {

	/**
	 * The current Multi-Inventory version
	 *
	 * @var string
	 */
	private $current_mi_version = '';

	/**
	 * Whether ATUM is being installed for the first time
	 *
	 * @var bool
	 */
	private $is_fresh_install = FALSE;

	/**
	 * Upgrade constructor
	 *
	 * @since 1.0.7
	 *
	 * @param string $db_version  The Multi-Inventory version saved in db as an option.
	 */
	public function __construct( $db_version ) {

		$this->current_mi_version = $db_version;

		if ( ! $db_version || version_compare( $db_version, '0.0.1', '<=' ) ) {
			$this->is_fresh_install = TRUE;
		}
		
		// Update the db version to the current Multi-Inventory version before upgrade to prevent various executions.
		update_option( 'atum_multi_inventory_version', ATUM_MULTINV_VERSION );

		/************************
		 * UPGRADE ACTIONS START
		 **********************!*/

		// ** version 1.0.0 ** Add Multi-Iventory tables.
		if ( version_compare( $db_version, '1.0.0', '<' ) ) {
			$this->add_inventory_tables();
		}

		// ** version 1.0.7 ** Moved all the inventory meta to columns for better performance.
		if ( version_compare( $db_version, '1.0.7', '<' ) ) {
			$this->migrate_inventory_meta_table();
		}
		
		// ** version 1.0.7.1 ** Change order_type id for Inventory Logs.
		if ( version_compare( $db_version, '1.0.7.1', '<' ) && ! $this->is_fresh_install ) {
			$this->change_inventory_logs_id();
		}
		
		// ** version 1.0.7.4 ** Fixed money quantities where saved including taxes. Now we deduct the taxes applied
		if ( version_compare( $db_version, '1.0.7.4', '<' ) && ! $this->is_fresh_install ) {
			$this->deduct_taxes();
		}

		// ** version 1.2.0 ** New tables to store ATUM data for inventories.
		if ( version_compare( $db_version, '1.2.0', '<' ) ) {
			$this->create_list_table_columns();
		}

		// ** version 1.2.3.1 ** Change field types in ATUM data for inventories.
		if ( version_compare( $db_version, '1.2.3.1', '<' ) ) {
			$this->alter_list_table_columns();
		}

		// ** version 1.2.4 ** Change the selling_priority meta name to avoid confusion with the PL's selling priority.
		if ( version_compare( $db_version, '1.2.4', '<' ) && ! $this->is_fresh_install ) {
			$this->change_selling_priority_meta();
		}

		// ** version 1.3.0 ** Add the reduced_stock field to the ATUM Order Item Inventories table
		if ( version_compare( $db_version, '1.3.0', '<' ) ) {
			$this->add_order_item_inventory_reduced_stock_column();
		}

		// ** version 1.3.1 ** Add the expiry_days field to the ATUM Inventories table
		if ( version_compare( $db_version, '1.3.1', '<' ) ) {
			$this->add_inventory_expiry_days_column();
		}

		// ** version 1.3.4 ** Moved all the products' MI meta to columns for better performance.
		if ( version_compare( $db_version, '1.3.4', '<' ) ) {
			$this->add_mi_product_meta_columns();
		}

		// ** version 1.3.7 ** Add new columns for selectable inventories options.
		if ( version_compare( $db_version, '1.3.7', '<' ) ) {
			$this->add_selectable_inventories_columns();
		}

		// ** version 1.3.7.1 ** Add the shipping class column to the inventories meta table.
		if ( version_compare( $db_version, '1.3.7.1', '<' ) ) {
			$this->add_shipping_class_column();
		}

		// ** version 1.3.8 ** Add the inventory reserved stock table.
		if ( version_compare( $db_version, '1.3.8', '<' ) ) {
			$this->create_inventory_rs_table();
		}

		// ** version 1.3.8.1 ** Modify the inventory reserved stock table datetime columns to allow NULL values.
		if ( version_compare( $db_version, '1.3.8.1', '<' ) ) {
			$this->alter_inventory_rs_table();
		}

		// ** version 1.3.9.1 ** Add the expired_stock column to the inventory meta table.
		if ( version_compare( $db_version, '1.3.9.1', '<' ) ) {
			$this->add_expired_stock_column();
		}

		// ** version 1.3.9.2 ** Add the expired_stock column to the inventory meta table.
		if ( version_compare( $db_version, '1.3.9.2', '<' ) ) {
			$this->add_order_item_inventory_extra_data();
		}

		/**********************
		 * UPGRADE ACTIONS END
		 ********************!*/

		do_action( 'atum/multi_inventory/after_upgrade', $db_version );

	}

	/**
	 * Alter the Inventories table to add all the meta as columns
	 *
	 * @since 1.0.7
	 */
	private function migrate_inventory_meta_table() {

		global $wpdb;

		$inventory_meta_table = $wpdb->prefix . 'atum_inventory_meta';

		// phpcs:ignore WordPress.DB.PreparedSQL
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$inventory_meta_table';" ) ) {

			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			$sql = "
				CREATE TABLE $inventory_meta_table (
					`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				    `inventory_id` BIGINT(20) UNSIGNED NOT NULL,
					`sku` VARCHAR(100) NULL DEFAULT '',
					`manage_stock` TINYINT(1) NULL DEFAULT 0,
					`stock_quantity` DOUBLE NULL DEFAULT NULL,
				    `backorders` TINYINT(1) NULL DEFAULT 0,
				    `stock_status` VARCHAR(100) NULL DEFAULT 'instock',
					`supplier_id` BIGINT(20) NULL DEFAULT NULL,		  
					`supplier_sku` VARCHAR(100) NULL DEFAULT '',		  		  
					`sold_individually` TINYINT(1) NULL DEFAULT 0,	  
					`out_stock_threshold` DOUBLE NULL DEFAULT NULL,
					`purchase_price` DOUBLE NULL DEFAULT NULL,
					`price` DOUBLE NULL DEFAULT NULL,
					`regular_price` DOUBLE NULL DEFAULT NULL,
					`sale_price` DOUBLE NULL DEFAULT NULL,
					`date_on_sale_from` DATETIME NULL DEFAULT NULL,
					`date_on_sale_to` DATETIME NULL DEFAULT NULL,
					`out_stock_date` DATETIME NULL DEFAULT NULL,
					`expired_stock` DOUBLE NULL DEFAULT NULL,
				    PRIMARY KEY (`id`),
					KEY `inventory_id` (`inventory_id`),
					KEY `supplier_id` (`supplier_id`),
					KEY `stock_status` (`stock_status`)		
          		) $collate
            ";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			// Only need to migrate the data if ATUM was previously installed.
			if ( ! $this->is_fresh_install ) {
				$this->migrate_inventories_meta();
			}

		}

	}

	/**
	 * Migrate all the inventories' meta from the old table to their corresponding columns
	 *
	 * @since 1.0.7
	 */
	private function migrate_inventories_meta() {

		global $wpdb;

		$meta_keys_to_migrate = array(
			'_sku'                   => 'sku',
			'_manage_stock'          => 'manage_stock',
			'_stock'                 => 'stock_quantity',
			'_backorders'            => 'backorders',
			'_stock_status'          => 'stock_status',
			'_supplier'              => 'supplier_id',
			'_supplier_sku'          => 'supplier_sku',
			'_sold_individually'     => 'sold_individually',
			'_out_stock_threshold'   => 'out_stock_threshold',
			'_purchase_price'        => 'purchase_price',
			'_price'                 => 'price',
			'_regular_price'         => 'regular_price',
			'_sale_price'            => 'sale_price',
			'_sale_price_dates_from' => 'date_on_sale_from',
			'_sale_price_dates_to'   => 'date_on_sale_to',
			'_out_of_stock_date'     => 'out_stock_date',
		);

		$inventories_table        = $wpdb->prefix . 'atum_inventories';
		$old_inventory_meta_table = $wpdb->prefix . 'atum_inventorymeta';
		$new_inventory_meta_table = $wpdb->prefix . 'atum_inventory_meta';

		// Get all the available inventories.
		$inventories = $wpdb->get_col( "SELECT DISTINCT id FROM $inventories_table" ); // phpcs:ignore WordPress.DB.PreparedSQL

		// Get all the meta from the old table.
		$inventories_meta = $wpdb->get_results( "SELECT * FROM $old_inventory_meta_table" ); // phpcs:ignore WordPress.DB.PreparedSQL

		$errors = array();

		foreach ( $inventories as $inventory_id ) {

			$inventory_meta = wp_list_filter( $inventories_meta, [ 'inventory_id' => $inventory_id ] );
			$new_data       = array(
				'inventory_id' => $inventory_id,
			);

			if ( ! empty( $inventory_meta ) ) {

				foreach ( $meta_keys_to_migrate as $meta_key => $new_field_name ) {

					$meta = wp_list_filter( $inventory_meta, [ 'meta_key' => $meta_key ] );

					if ( ! empty( $meta ) ) {

						$meta = current( $meta );

						switch ( $meta_key ) {

							// Yes/No metas.
							case '_manage_stock':
							case '_backorders':
							case '_sold_individually':
								$meta_value = 'yes' === $meta->meta_value ? 1 : 0;
								break;

							// Other metas.
							default:
								$meta_value = ! empty( $meta->meta_value ) || '0' === $meta->meta_value ? $meta->meta_value : NULL;
								break;
						}

						$new_data[ $new_field_name ] = $meta_value;

					}

				}

				if ( ! empty( $new_data ) ) {
					$inserted_row = $wpdb->insert( $new_inventory_meta_table, $new_data );

					if ( FALSE === $inserted_row ) {
						$errors[] = $inventory_id;
					}
				}

			}

		}

		// If all was migrated successfully, delete the old table.
		if ( empty( $errors ) ) {
			$wpdb->query( "DROP TABLE $old_inventory_meta_table" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

	}
	
	/**
	 * Change order_type in multi inventory inventory orders table for elements included in an Inventory Order
	 *
	 * @since 1.0.7.1
	 */
	private function change_inventory_logs_id() {
		
		global $wpdb;
		
		$inventory_orders_table = $wpdb->prefix . Inventory::INVENTORY_ORDERS_TABLE;
		$order_items_table      = $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE;
		$post_type              = InventoryLogs::get_post_type();

		// phpcs:disable WordPress.DB.PreparedSQL
		$wpdb->query( $wpdb->prepare("
			UPDATE $inventory_orders_table invo
            INNER JOIN $order_items_table ordi ON invo.order_item_id = ordi.order_item_id
            INNER JOIN $wpdb->posts p ON ordi.order_id = p.ID
			SET invo.order_type = 3
			WHERE p.post_type = %s
		", $post_type ) );
		// phpcs:enable
		
	}
	
	/**
	 * Change order_type in multi inventory inventory orders table for elements included in an Inventory Order
	 *
	 * @since 1.0.7.4
	 */
	private function deduct_taxes() {
		
		global $wpdb;

		$inventory_orders_table = $wpdb->prefix . Inventory::INVENTORY_ORDERS_TABLE;
		$updated                = get_option( 'atum_multi_inventory_updated1074' );
		
		if ( ! $updated ) {

			// phpcs:disable WordPress.DB.PreparedSQL
			$wpdb->query( "
				UPDATE $inventory_orders_table io
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta woi_sub ON woi_sub.order_item_id = io.order_item_id
				AND woi_sub.meta_key = '_line_subtotal'
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta woi_tax ON woi_tax.order_item_id = io.order_item_id
				AND woi_tax.meta_key = '_line_subtotal_tax'
				SET io.subtotal = io.subtotal - ( io.subtotal * woi_tax.meta_value/woi_sub.meta_value ),
				    io.total = io.total - ( io.total *	woi_tax.meta_value/woi_sub.meta_value )
				WHERE io.order_type = 1
			");
			// phpcs:enable
			
			update_option( 'atum_multi_inventory_updated1074', TRUE );
		
		}
		
	}

	/**
	 * Add Multi-Iventory tables
	 *
	 * @since 1.1.4
	 */
	private function add_inventory_tables() {

		global $wpdb;

		// Create the DB table to store all the inventories created for each product.
		/* @noinspection PhpIncludeInspection */
		require_once ATUM_MULTINV_PATH . 'vendor/autoload.php';
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$inventories_table         = $wpdb->prefix . Inventory::INVENTORIES_TABLE;
		$inventory_locations_table = $wpdb->prefix . Inventory::INVENTORY_LOCATIONS_TABLE;
		$inventory_regions_table   = $wpdb->prefix . Inventory::INVENTORY_REGIONS_TABLE;
		$inventory_orders_table    = $wpdb->prefix . Inventory::INVENTORY_ORDERS_TABLE;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		// Create the Inventories table.
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s;', $inventories_table ) ) ) {

			$sql = "
				CREATE TABLE $inventories_table (
			  		`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  		`product_id` bigint(20) unsigned NOT NULL,
			  		`name` varchar(200) NOT NULL DEFAULT '',
			  		`priority` bigint(20) unsigned DEFAULT NULL,
			  		`region` longtext,
			  		`inventory_date` datetime DEFAULT NULL,
			  		`bbe_date` datetime DEFAULT NULL,
			  		`is_main` tinyint(1) DEFAULT '0',
			  		`lot` varchar(200) DEFAULT NULL,
			  		`write_off` tinyint(1) DEFAULT '0',
			  		PRIMARY KEY (`id`),
			  		KEY `product_id` (`product_id`),
			  		KEY `name` (`name`(191))
				) $collate;
			";

			dbDelta( $sql );

		}

		// Create the Inventory locations table (to handle relationships between inventories and ATUM Location terms).
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s;', $inventory_locations_table ) ) ) {

			$sql = "
				CREATE TABLE $inventory_locations_table (
			  		`inventory_id` bigint(20) unsigned NOT NULL DEFAULT '0',
			  		`term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT '0',
			  		`term_order` int(11) NOT NULL DEFAULT '0',
			  		PRIMARY KEY (`inventory_id`,`term_taxonomy_id`),
			  		KEY `term_taxonomy_id` (`term_taxonomy_id`)
				) $collate;
			";

			dbDelta( $sql );

		}

		// Create the Inventory regions table (to handle relationships between inventories and WC's shipping zones).
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s;', $inventory_regions_table ) ) ) {

			$sql = "
				CREATE TABLE $inventory_regions_table (
			  		`inventory_id` bigint(20) unsigned NOT NULL DEFAULT '0',
			  		`zone_id` bigint(20) unsigned NOT NULL DEFAULT '0',
			  		`region_order` int(11) NOT NULL DEFAULT '0',
			  		PRIMARY KEY (`inventory_id`,`zone_id`),
			  		KEY `zone_id` (`zone_id`)
				) $collate;";

			dbDelta( $sql );

		}

		// Create the Inventory orders table (to store all the stock changes performed within a WC order for products with MI).
		// phpcs:ignore WordPress.DB.PreparedSQL
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$inventory_orders_table';" ) ) {

			$sql = "
				CREATE TABLE $inventory_orders_table (
					`id` bigint(20) NOT NULL auto_increment,
					`order_item_id` bigint(20) NOT NULL,
					`inventory_id` bigint(20) NOT NULL,
					`product_id` bigint(20) NOT NULL,			  
			    	`qty` double NOT NULL,
			    	`subtotal` varchar(200) NOT NULL DEFAULT '',
			    	`total` varchar(200) NOT NULL DEFAULT '',
			    	`order_type` smallint unsigned NOT NULL DEFAULT '1',
			    	`refund_qty` double DEFAULT NULL,
  					`refund_total` varchar(200) DEFAULT NULL,
  					`extra_data` longtext,
			    	PRIMARY KEY (`id`),
			    	KEY `order_item_id` (`order_item_id`),
			    	KEY `inventory_id` (`inventory_id`)
				) $collate;";

			dbDelta( $sql );

		}

	}
	
	/**
	 * Create the new columns for the inventories table
	 *
	 * @since 1.2.0
	 */
	private function create_list_table_columns() {

		global $wpdb;

		$db_name           = DB_NAME;
		$inventories_table = $wpdb->prefix . Inventory::INVENTORIES_TABLE;
		$columns           = array(
			'inbound_stock'    => 'DOUBLE',
			'stock_on_hold'    => 'DOUBLE',
			'sold_today'       => 'BIGINT(20)',
			'sales_last_days'  => 'BIGINT(20)',
			'reserved_stock'   => 'BIGINT(20)',
			'customer_returns' => 'BIGINT(20)',
			'warehouse_damage' => 'BIGINT(20)',
			'lost_in_post'     => 'BIGINT(20)',
			'other_logs'       => 'BIGINT(20)',
			'out_stock_days'   => 'INT(11)',
			'lost_sales'       => 'BIGINT(20)',
			'update_date'      => 'DATETIME',
		);

		foreach ( array_keys( $columns ) as $column_name ) {

			// Avoid adding the column if was already added.
			$column_exist = $wpdb->prepare( '
				SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = %s
			', $db_name, $inventories_table, $column_name );

			// Add the new column to the table.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $wpdb->get_var( $column_exist ) ) {
				$wpdb->query( "ALTER TABLE $inventories_table ADD `$column_name` {$columns[ $column_name ]} DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}

		}

		// Add extra key indexes to ATUM inventories table to improve performance.
		$indexes = array(
			'priority',
			'is_main',
			'write_off',
		);

		foreach ( $indexes as $index ) {

			// Avoid adding the index if was already added.
			$index_exist = $wpdb->prepare( '
				SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
				WHERE table_schema = %s AND TABLE_NAME = %s AND index_name = %s;
			', $db_name, $inventories_table, $index );

			// Add the new index to the table.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $wpdb->get_var( $index_exist ) ) {
				$wpdb->query( "ALTER TABLE $inventories_table ADD INDEX `$index` (`$index`)" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}

		}

	}

	/**
	 * Modify the stock count inventory fields from bigint to double
	 *
	 * @since 1.2.3.1
	 */
	private function alter_list_table_columns() {

		global $wpdb;

		$db_name           = DB_NAME;
		$inventories_table = $wpdb->prefix . Inventory::INVENTORIES_TABLE;
		$columns           = array(
			'sold_today'       => 'DOUBLE',
			'sales_last_days'  => 'DOUBLE',
			'reserved_stock'   => 'DOUBLE',
			'customer_returns' => 'DOUBLE',
			'warehouse_damage' => 'DOUBLE',
			'lost_in_post'     => 'DOUBLE',
			'other_logs'       => 'DOUBLE',
		);

		foreach ( array_keys( $columns ) as $column_name ) {

			// Avoid adding the column if was already added.
			$column_exist = $wpdb->prepare( '
				SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = %s
			', $db_name, $inventories_table, $column_name );

			// Add the new column to the table.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $wpdb->get_var( $column_exist ) ) {
				$wpdb->query( "ALTER TABLE $inventories_table ADD `$column_name` {$columns[ $column_name ]} DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}
			else {
				$wpdb->query( "ALTER TABLE $inventories_table MODIFY `$column_name` {$columns[ $column_name ]} DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}

		}

	}

	/**
	 * Change the selling_priority meta name to avoid confusion with the PL's selling priority.
	 *
	 * @since 1.2.4
	 */
	private function change_selling_priority_meta() {

		global $wpdb;

		$wpdb->update(
			$wpdb->postmeta,
			[ 'meta_key' => '_inventory_sorting_mode' ],
			[ 'meta_key' => '_selling_priority' ],
			[ '%s' ],
			[ '%s' ]
		);

		$wpdb->update(
			$wpdb->postmeta,
			[ 'meta_key' => '_inventory_sorting_mode_custom' ],
			[ 'meta_key' => '_selling_priority_custom' ],
			[ '%s' ],
			[ '%s' ]
		);

		$wpdb->update(
			$wpdb->postmeta,
			[ 'meta_key' => '_inventory_sorting_mode_currency' ],
			[ 'meta_key' => '_selling_priority_currency' ],
			[ '%s' ],
			[ '%s' ]
		);

	}

	/**
	 * Add the reduced_stock field to the ATUM Order Item Inventories table
	 *
	 * @since 1.3.0
	 */
	private function add_order_item_inventory_reduced_stock_column() {

		global $wpdb;

		$inventory_orders_table = $wpdb->prefix . Inventory::INVENTORY_ORDERS_TABLE;

		// Avoid adding the column if was already added.
		$column_exist = $wpdb->prepare( "
			SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'reduced_stock'
		", DB_NAME, $inventory_orders_table );

		// Add the new column to the table.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $column_exist ) ) {
			$wpdb->query( "ALTER TABLE $inventory_orders_table ADD `reduced_stock` double DEFAULT 0;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

	}

	/**
	 * Add a new column to inventories table for the expiry days
	 *
	 * @since 1.3.1
	 */
	private function add_inventory_expiry_days_column() {

		global $wpdb;

		$inventories_table = $wpdb->prefix . Inventory::INVENTORIES_TABLE;

		// Avoid adding the column if was already added.
		$column_exist = $wpdb->prepare( "
			SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'expiry_days'
		", DB_NAME, $inventories_table );

		// Add the new column to the table.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $column_exist ) ) {
			$wpdb->query( "ALTER TABLE $inventories_table ADD `expiry_days` INT(11) DEFAULT 0;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

	}

	/**
	 * Alter the the ATUM product data table to add the MI product meta columns to improve performance
	 *
	 * @since 1.3.4
	 */
	private function add_mi_product_meta_columns() {

		global $wpdb;

		$db_name         = DB_NAME;
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		$columns         = array(
			'multi_inventory'        => 'TINYINT(1)',
			'inventory_iteration'    => 'VARCHAR(15)',
			'inventory_sorting_mode' => 'VARCHAR(10)',
			'expirable_inventories'  => 'TINYINT(1)',
			'price_per_inventory'    => 'TINYINT(1)',
		);

		foreach ( $columns as $column_name => $type ) {

			// Avoid adding the column if was already added.
			$column_exist = $wpdb->prepare( '
				SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = %s
			', $db_name, $atum_data_table, $column_name );

			// Add the new column to the table.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $wpdb->get_var( $column_exist ) ) {
				$wpdb->query( "ALTER TABLE $atum_data_table ADD `$column_name` $type DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}

			/**
			 * Migrate all the meta keys to the ATUM product data table.
			 */

			// yes/no columns.
			if ( strpos( $type, 'INT' ) !== FALSE ) {

				// phpcs:disable WordPress.DB.PreparedSQL
				$wpdb->query( $wpdb->prepare( "
					UPDATE $atum_data_table apd 
					LEFT JOIN ( 
						SELECT post_id, IF(
							MAX(meta_value) = 'yes', 1, IF(
								MAX(meta_value)= 'no', 0, NULL
							)
						) AS meta_value FROM $wpdb->postmeta
						WHERE meta_key = %s
						GROUP BY post_id			
					) AS meta ON apd.product_id = meta.post_id
					SET apd.{$column_name} = meta.meta_value
				", "_$column_name" ) );
				// phpcs:enable

			}
			// string columns.
			else {

				// phpcs:disable WordPress.DB.PreparedSQL
				$wpdb->query( $wpdb->prepare( "
					UPDATE $atum_data_table apd 
					LEFT JOIN ( 
						SELECT post_id, IF( MAX(meta_value) = 'global', NULL, MAX(meta_value) )
						AS meta_value FROM $wpdb->postmeta
						WHERE meta_key = %s
						GROUP BY post_id			
					) AS meta ON apd.product_id = meta.post_id
					SET apd.{$column_name} = meta.meta_value
				", "_$column_name" ) );
				// phpcs:enable

			}

		}

		// Add extra key indexes to ATUM product data table to improve performance.
		$indexes = [ 'multi_inventory' ];

		foreach ( $indexes as $index ) {

			// Avoid adding the index if was already added.
			$index_exist = $wpdb->prepare( '
				SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
				WHERE table_schema = %s AND TABLE_NAME = %s AND index_name = %s;
			', $db_name, $atum_data_table, $index );

			// Add the new index to the table.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $wpdb->get_var( $index_exist ) ) {
				$wpdb->query( "ALTER TABLE $atum_data_table ADD INDEX `$index` (`$index`)" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}

		}

	}

	/**
	 * Add new columns to ATUM product data for the selectable inventories options
	 *
	 * @since 1.3.7
	 */
	private function add_selectable_inventories_columns() {

		global $wpdb;

		$db_name         = DB_NAME;
		$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
		$columns         = array(
			'selectable_inventories'      => 'TINYINT(1)',
			'selectable_inventories_mode' => 'VARCHAR(15)',
		);

		foreach ( $columns as $column_name => $type ) {

			// Avoid adding the column if was already added.
			$column_exist = $wpdb->prepare( '
				SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = %s
			', $db_name, $atum_data_table, $column_name );

			// Add the new column to the table.
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $wpdb->get_var( $column_exist ) ) {
				$wpdb->query( "ALTER TABLE $atum_data_table ADD `$column_name` $type DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
			}

		}

	}

	/**
	 * Add the shipping class column to the inventories meta table
	 *
	 * @since 1.3.7.1
	 */
	private function add_shipping_class_column() {

		global $wpdb;

		$db_name        = DB_NAME;
		$inv_meta_table = $wpdb->prefix . Inventory::INVENTORY_META_TABLE;

		// Avoid adding the column if was already added.
		$column_exist = $wpdb->prepare( "
			SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'shipping_class'
		", $db_name, $inv_meta_table );

		// Add the new column to the table.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $column_exist ) ) {
			$wpdb->query( "ALTER TABLE $inv_meta_table ADD `shipping_class` BIGINT(20) DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

	}

	/**
	 * Create the reserved stock table for inventories
	 *
	 * @since 1.3.8
	 */
	private function create_inventory_rs_table() {

		global $wpdb;

		$reserved_stock_table = $wpdb->prefix . Inventory::INVENTORY_RESERVED_STOCK_TABLE;

		// phpcs:ignore WordPress.DB.PreparedSQL
		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$reserved_stock_table';" ) ) {

			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			$sql = "
				CREATE TABLE $reserved_stock_table (
				`order_id` bigint(20) NOT NULL,
				`inventory_id` bigint(20) NOT NULL,
				`stock_quantity` double NOT NULL DEFAULT 0,
				`timestamp` datetime NULL DEFAULT NULL,
				`expires` datetime NULL DEFAULT NULL,
				PRIMARY KEY  (`order_id`, `inventory_id`)
          		) $collate
            ";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

	}

	/**
	 * Ensure the rs table is created and the datetime columns are properly defined.
	 *
	 * @since 1.3.8.1
	 */
	private function alter_inventory_rs_table() {

		$this->create_inventory_rs_table();

		global $wpdb;

		$reserved_stock_table = $wpdb->prefix . Inventory::INVENTORY_RESERVED_STOCK_TABLE;

		$wpdb->query( "ALTER TABLE $reserved_stock_table MODIFY `timestamp` datetime NULL DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		$wpdb->query( "ALTER TABLE $reserved_stock_table MODIFY `expires` datetime NULL DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL

	}

	/**
	 * Add the expired stock column to the inventories meta table
	 *
	 * @since 1.3.9.1
	 */
	private function add_expired_stock_column() {

		global $wpdb;

		$db_name        = DB_NAME;
		$inv_meta_table = $wpdb->prefix . Inventory::INVENTORY_META_TABLE;

		// Avoid adding the column if was already added.
		$column_exist = $wpdb->prepare( "
			SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'expired_stock'
		", $db_name, $inv_meta_table );

		// Add the new column to the table.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $column_exist ) ) {
			$wpdb->query( "ALTER TABLE $inv_meta_table ADD `expired_stock` DOUBLE NULL DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

	}

	/**
	 * Add the extra_data field to the ATUM Order Item Inventories table
	 *
	 * @since 1.3.9.2
	 */
	private function add_order_item_inventory_extra_data() {

		global $wpdb;

		$inventory_orders_table = $wpdb->prefix . Inventory::INVENTORY_ORDERS_TABLE;

		// Avoid adding the column if was already added.
		$column_exist = $wpdb->prepare( "
			SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND column_name = 'extra_data'
		", DB_NAME, $inventory_orders_table );

		// Add the new column to the table.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $wpdb->get_var( $column_exist ) ) {
			$wpdb->query( "ALTER TABLE $inventory_orders_table ADD `extra_data` longtext;" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

		$this->update_order_item_inventories_extra_data();

	}

	/**
	 * Populate the extra_data field in the ATUM Order Item Inventories table
	 *
	 * @since 1.3.9.2
	 */
	private function update_order_item_inventories_extra_data() {

		global $wpdb;

		$inventories_table      = $wpdb->prefix . Inventory::INVENTORIES_TABLE;
		$inventories_meta_table = $wpdb->prefix . Inventory::INVENTORY_META_TABLE;
		$inventory_orders_table = $wpdb->prefix . Inventory::INVENTORY_ORDERS_TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL
		$inventories = $wpdb->get_results( "
			SELECT DISTINCT io.inventory_id, i.name, im.sku, im.supplier_sku FROM $inventory_orders_table io
			LEFT JOIN $inventories_table i ON io.inventory_id = i.id
			LEFT JOIN $inventories_meta_table im ON io.inventory_id = im.inventory_id
		" );
		// phpcs:enable

		foreach ( $inventories as $inventory ) {

			$extra_data = array( 'name' => is_null( $inventory->name ) ? __( 'Removed Inventory', ATUM_MULTINV_TEXT_DOMAIN ) : $inventory->name );

			if ( ! empty( $inventory->sku ) )
				$extra_data['sku'] = $inventory->sku;

			if ( ! empty( $inventory->supplier_sku ) )
				$extra_data['supplier_sku'] = $inventory->supplier_sku;

			// phpcs:disable WordPress.DB.PreparedSQL
			$wpdb->query( $wpdb->prepare( "UPDATE $inventory_orders_table SET extra_data = %s WHERE inventory_id = %s", maybe_serialize( $extra_data ), $inventory->inventory_id ) );
			// phpcs:enable

		}

	}

}
