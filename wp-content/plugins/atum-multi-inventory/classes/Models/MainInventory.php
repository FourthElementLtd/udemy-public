<?php
/**
 * The Model class for Main Inventories
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

use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Inc\Helpers;


class MainInventory extends Inventory {

	/* @noinspection PhpMissingParentConstructorInspection */
	/**
	 * MainInventory constructor
	 *
	 * @param int  $inventory_id
	 * @param int  $product_id
	 * @param bool $allow_read
	 */
	public function __construct( $inventory_id = 0, $product_id = 0, $allow_read = TRUE ) {

		$this->data = self::$default_data;
		$this->meta = self::$default_meta;
		
		$default_data = array(
			'is_main'    => 1,
			'product_id' => $product_id,
		);

		if ( ! $inventory_id ) {

			$default_data['name'] = __( 'Main Inventory', ATUM_MULTINV_TEXT_DOMAIN );
			$this->set_data( $default_data );
			$this->id = 'main';

			// If the current product is still not saved, the main inventory should have set the default meta to the WC inventory meta.
			$product = AtumHelpers::get_atum_product( $this->product_id );

			if ( $product instanceof \WC_Product ) {

				$default_meta = $product->get_data();
				$this->set_meta( $default_meta );

				// Save the main inventory for future uses (if not already coming on a form submission).
				if ( empty( $_POST['atum_mi']['main'] ) && 'yes' === Helpers::get_product_multi_inventory_status( $product ) ) {
					$this->save();
				}

			}

		}
		else {
			$this->set_data( $default_data );
			$this->id = $inventory_id;
		}

		if ( $allow_read ) {
			// As all the products will have always a main inventory, read the data regardless it was previously saved or not.
			$this->read();
		}
		
	}

	/**
	 * Sanitize the meta values to have the right format to be handled by WooCommerce before saving them to db
	 *
	 * @since 1.2.1
	 *
	 * @param string $meta_key Optional. Individual meta key to sanitize, defaults to ''.
	 *
	 * @return array The sanitized meta array
	 */
	protected function sanitize_meta_for_db( $meta_key = '' ) {

		$sanitized_meta   = parent::sanitize_meta_for_db( $meta_key );
		$meta_to_sanitize = $meta_key ? array( $meta_key => $this->meta[ $meta_key ] ) : $this->meta;

		// If it returns NULL as it's doing the Inventory model, WC won't take care of it.
		foreach ( $meta_to_sanitize as $key => $value ) {

			switch ( $key ) {
				case 'price':
				case 'regular_price':
				case 'sale_price':
				case 'purchase_price':
					// If it returns NULL as it's doing the Inventory model, WC won't care of it.
					$sanitized_meta[ $key ] = in_array( $value, [ NULL, '' ] ) ? '' : floatval( $value );
					break;

				case 'out_stock_threshold':
				case 'expired_stock':
					// If it returns NULL as it's doing the Inventory model, WC won't care of it.
					$sanitized_meta[ $key ] = in_array( $value, [ NULL, '' ] ) ? '' : wc_stock_amount( $value );
					break;

				case 'supplier_id':
					// If it returns NULL as it's doing the Inventory model, WC won't care of it.
					$sanitized_meta[ $key ] = in_array( $value, [ NULL, '' ] ) ? '' : absint( $value );
					break;

				case 'shipping_class':
					// If it returns NULL as it's doing the Inventory model, WC won't care of it.
					$sanitized_meta[ "{$key}_id" ] = in_array( $value, [ NULL, '' ] ) ? '' : absint( $value );
					break;

				case 'out_stock_date':
				case 'date_on_sale_from':
				case 'date_on_sale_to':
				case 'update_date':
					if ( ! $value instanceof \WC_DateTime ) {
						$sanitized_meta[ $key ] = '';
					}

					break;

				// Never return a NULL value because the WC's "set_props" methood will discard it.
				default:
					if ( is_null( $value ) ) {
						$sanitized_meta[ $key ] = '';
					}
					break;
			}

		}

		return $sanitized_meta;

	}
	
	/**
	 * If we need to save the Main inventory meta, set the products props and save
	 *
	 * @since 1.0.0
	 *
	 * @param string $meta_key Individual meta key to save, defaults to ''.
	 */
	public function save_meta( $meta_key = '' ) {

		$this->validate_meta();

		$product = AtumHelpers::get_atum_product( $this->product_id );
		$meta    = $this->sanitize_meta_for_db( $meta_key );

		$product->set_props( $meta );
		$product->save();
		
	}
	
}
