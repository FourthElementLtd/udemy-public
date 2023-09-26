<?php
/**
 * Extender for the WC's products endpoint
 * Adds the ATUM Multi Inventory Product Data to this endpoint
 *
 * @since       1.2.4
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     AtumMultiInventory\Api
 * @subpackage  Extenders
 */

namespace AtumMultiInventory\Api\Extenders;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use AtumMultiInventory\Inc\Helpers;

class ProductData {

	/**
	 * The singleton instance holder
	 *
	 * @var ProductData
	 */
	private static $instance;

	/**
	 * The known MI product fields for
	 *
	 * @var array
	 */
	private $mi_product_fields = array(
		'mi_inventories'              => [ 'get' ], // Only readable for now.
		'multi_inventory'             => [ 'get', 'update' ],
		'inventory_sorting_mode'      => [ 'get', 'update' ],
		'inventory_iteration'         => [ 'get', 'update' ],
		'expirable_inventories'       => [ 'get', 'update' ],
		'price_per_inventory'         => [ 'get', 'update' ],
		'selectable_inventories'      => [ 'get', 'update' ],
		'selectable_inventories_mode' => [ 'get', 'update' ],
	);

	/**
	 * Internal meta keys that shoudln't appear on the product's meta_data
	 *
	 * @var array
	 *
	 * @deprecated The MI's product props are not meta keys since version 1.3.4.
	 */
	private $internal_meta_keys = array(
		'_multi_inventory',
		'_inventory_iteration',
		'_expirable_inventories',
		'_price_per_inventory',
		'_inventory_sorting_mode',
		'_inventory_sorting_mode_custom',
		'_inventory_sorting_mode_currency',
	);

	/**
	 * MultiInventoryProductData constructor
	 *
	 * @since 1.2.4
	 */
	private function __construct() {

		// Add the MI's product schema.
		add_filter( 'atum/api/product_data/extended_schema', array( $this, 'add_mi_product_schema' ) );

		// Add the MI meta as product fields.
		add_filter( 'atum/api/product_data/product_fields', array( $this, 'add_mi_product_fields' ) );

		// Add the MI's internal meta keys.
		add_filter( 'atum/api/product_data/internal_meta_keys', array( $this, 'add_mi_internal_meta_keys' ) );

		// Get values for the MI fields.
		add_filter( 'atum/api/product_data/get_field_value', array( $this, 'get_field_value' ), 10, 4 );

		// Update values for the MI fields.
		add_action( 'atum/api/product_data/update_product_field', array( $this, 'update_field_value' ), 10, 4 );

		// Exclude some fields from the response when necessary.
		foreach ( [ 'product', 'product_variation' ] as $post_type ) {
			add_filter( "woocommerce_rest_prepare_{$post_type}_object", array( $this, 'prepare_rest_response' ), 11, 3 );
		}

		// Add MI filters to products' query.
		add_filter( 'atum/api/product_data/atum_query_args', array( $this, 'prepare_atum_query_data' ), 10, 2 );

		// Perform some validation to the product before saving.
		add_filter( 'woocommerce_rest_pre_insert_product_object', array( $this, 'validate_product_before_saving' ), 10, 3 );
		add_filter( 'woocommerce_rest_pre_insert_product_variation_object', array( $this, 'validate_product_before_saving' ), 10, 3 );

	}

	/**
	 * Add the MI fields to the product's schema
	 *
	 * @since 1.2.4
	 *
	 * @param array $extended_product_schema
	 *
	 * @return array
	 */
	public function add_mi_product_schema( $extended_product_schema ) {

		$mi_product_schema = array(
			'mi_inventories'              => array(
				'description' => __( 'The array of Multi-Inventories IDs linked to the product (if the product has the Multi-Inventory enabled and it has a compatible product type).', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'        => 'array',
			),
			'multi_inventory'             => array(
				'required'    => FALSE,
				'description' => __( 'The Multi Inventory status for the product.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'        => 'string',
				'enum'        => array( 'yes', 'no', 'global' ),
			),
			'inventory_sorting_mode'      => array(
				'required'    => FALSE,
				'description' => __( 'The sorting mode specified for inventory selling priority.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'        => 'string',
				'enum'        => array( 'fifo', 'lifo', 'bbe', 'manual', 'global' ),
			),
			'inventory_iteration'         => array(
				'required'    => FALSE,
				'description' => __( 'What to do when the first selling inventory runs out of stock.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'        => 'string',
				'enum'        => array( 'use_next', 'out_of_stock', 'global' ),
			),
			'expirable_inventories'       => array(
				'required'    => FALSE,
				'description' => __( "Set the inventories as 'Out of Stock' when reaching their BBE dates.", ATUM_MULTINV_TEXT_DOMAIN ),
				'type'        => 'string',
				'enum'        => array( 'yes', 'no', 'global' ),
			),
			'price_per_inventory'         => array(
				'required'    => FALSE,
				'description' => __( 'Allow distinct inventories to have distinct prices.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'        => 'string',
				'enum'        => array( 'yes', 'no', 'global' ),
			),
			'selectable_inventories'      => array(
				'required'    => FALSE,
				'description' => __( 'Whether the selectable inventories is enabled.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'        => 'string',
				'enum'        => array( 'yes', 'no', 'global' ),
			),
			'selectable_inventories_mode' => array(
				'required'    => FALSE,
				'description' => __( 'The inventory selection mode for the frontend display.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'        => 'string',
				'enum'        => array( 'dropdown', 'list', 'global' ),
			),
		);

		return array_merge( $extended_product_schema, $mi_product_schema );

	}

	/**
	 * Add the Multi Inventory meta as product fields.
	 *
	 * @since 1.2.4
	 *
	 * @param array $product_fields
	 *
	 * @return array
	 */
	public function add_mi_product_fields( $product_fields ) {
		return array_merge( $product_fields, $this->mi_product_fields );
	}

	/**
	 * Add the Multi Inventory's internal meta keys to products
	 *
	 * @since 1.2.4
	 *
	 * @param array $internal_meta_keys
	 *
	 * @return array
	 */
	public function add_mi_internal_meta_keys( $internal_meta_keys ) {
		return array_merge( $internal_meta_keys, $this->internal_meta_keys );
	}

	/**
	 * Get the values for the MI fields
	 *
	 * @since 1.2.4
	 *
	 * @param mixed       $field_value
	 * @param array       $response
	 * @param string      $field_name
	 * @param \WC_Product $product
	 *
	 * @return mixed
	 */
	public function get_field_value( $field_value, $response, $field_name, $product ) {

		if ( in_array( $field_name, array_keys( $this->mi_product_fields ), TRUE ) ) {

			switch ( $field_name ) {
				case 'mi_inventories':
					if ( 'yes' === Helpers::get_product_multi_inventory_status( $product ) && Helpers::is_product_multi_inventory_compatible( $product ) ) {
						$field_value = array_map( 'absint', wp_list_pluck( Helpers::get_product_inventories_sorted( $product->get_id() ), 'id' ) );
					}
					else {
						$field_value = array();
					}
					break;

				case 'multi_inventory':
					$field_value = Helpers::get_product_multi_inventory_status( $product, TRUE );
					break;

				default:
					if ( is_callable( array( '\AtumMultiInventory\Inc\Helpers', "get_product_$field_name" ) ) ) {
						$field_value = Helpers::{"get_product_$field_name"}( $product, TRUE );
					}

					break;

			}

		}

		return $field_value;

	}

	/**
	 * Update the values for the MI fields
	 *
	 * @since 1.2.4
	 *
	 * @param mixed       $field_value
	 * @param mixed       $response
	 * @param string      $field_name
	 * @param \WC_Product $product
	 */
	public function update_field_value( $field_value, $response, $field_name, $product ) {

		if ( in_array( $field_name, array_keys( $this->mi_product_fields ), TRUE ) ) {

			$product_id = $product->get_id();

			if ( is_null( $field_value ) ) {
				delete_post_meta( $product_id, "_$field_name" );
			}
			else {
				update_post_meta( $product_id, "_$field_name", $field_value );
			}

		}

	}

	/**
	 * Exclude the MI fields on some products (when necessary)
	 *
	 * @since 1.3.6
	 *
	 * @param \WP_REST_Response $response
	 * @param \WC_Product       $object
	 * @param \WP_REST_Request  $request
	 *
	 * @return \WP_REST_Response
	 */
	public function prepare_rest_response( $response, $object, $request ) {

		if ( $object instanceof \WC_Product ) {

			AtumCache::disable_cache();

			$product_data = $response->get_data();

			if ( 'cli' !== php_sapi_name() ) {

				if ( ! Helpers::is_product_multi_inventory_compatible( $object ) ) {

					unset(
						$product_data['multi_inventory'],
						$product_data['inventory_sorting_mode'],
						$product_data['inventory_iteration'],
						$product_data['expirable_inventories'],
						$product_data['price_per_inventory'],
						$product_data['selectable_inventories'],
						$product_data['selectable_inventories_mode']
					);

				}
				elseif ( 'yes' !== Helpers::get_product_multi_inventory_status( $object ) ) {

					unset(
						$product_data['inventory_sorting_mode'],
						$product_data['inventory_iteration'],
						$product_data['expirable_inventories'],
						$product_data['price_per_inventory'],
						$product_data['selectable_inventories'],
						$product_data['selectable_inventories_mode']
					);

				}

			}

			// If the product has MI enabled, and no inventories were passed, the main will be created by ATUM but not available prior this stage.
			if ( isset( $product_data['multi_inventory'] ) && 'yes' === Helpers::get_product_multi_inventory_status( $object ) ) {
				$product_data['mi_inventories'] = $this->get_field_value( [], $response, 'mi_inventories', $object );
			}

			$response->set_data( $product_data );

			AtumCache::enable_cache();

		}

		return $response;

	}

	/**
	 * Prepare the objects query for products filtering
	 *
	 * @since 1.2.5
	 *
	 * @param array            $atum_query_data
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	public function prepare_atum_query_data( $atum_query_data, $request ) {

		// Multi-Inventory status filter.
		if ( ! empty( $request['multi_inventory'] ) ) {

			$atum_query_data['where'][] = array(
				'key'   => 'multi_inventory',
				'value' => TRUE === wc_string_to_bool( $request['multi_inventory'] ) ? 1 : 0,
				'type'  => 'NUMERIC',
			);

		}

		// Inventory sorting mode filter.
		if ( ! empty( $request['inventory_sorting_mode'] ) ) {

			$atum_query_data['where'][] = array(
				'key'   => 'inventory_sorting_mode',
				'value' => TRUE === wc_string_to_bool( $request['inventory_sorting_mode'] ) ? 1 : 0,
				'type'  => 'NUMERIC',
			);

		}

		// Inventory iteration filter.
		if ( ! empty( $request['inventory_iteration'] ) ) {

			$atum_query_data['where'][] = array(
				'key'   => 'inventory_sorting_mode',
				'value' => esc_attr( $request['inventory_iteration'] ),
			);

		}

		// Expirable inventories filter.
		if ( ! empty( $request['expirable_inventories'] ) ) {

			$atum_query_data['where'][] = array(
				'key'   => 'expirable_inventories',
				'value' => TRUE === wc_string_to_bool( $request['expirable_inventories'] ) ? 1 : 0,
				'type'  => 'NUMERIC',
			);

		}

		// Price per inventory filter.
		if ( ! empty( $request['price_per_inventory'] ) ) {

			$atum_query_data['where'][] = array(
				'key'   => 'expirable_inventories',
				'value' => TRUE === wc_string_to_bool( $request['price_per_inventory'] ) ? 1 : 0,
				'type'  => 'NUMERIC',
			);

		}

		// Selectable inventories filter.
		if ( ! empty( $request['selectable_inventories'] ) ) {

			$atum_query_data['where'][] = array(
				'key'   => 'selectable_inventories',
				'value' => TRUE === wc_string_to_bool( $request['selectable_inventories'] ) ? 1 : 0,
				'type'  => 'NUMERIC',
			);

		}

		// Selectable inventories mode filter.
		if ( ! empty( $request['selectable_inventories_mode'] ) ) {

			$atum_query_data['where'][] = array(
				'key'   => 'selectable_inventories_mode',
				'value' => esc_attr( $request['selectable_inventories_mode'] ),
			);

		}

		return $atum_query_data;

	}

	/**
	 * Validate a MI product before saving it.
	 *
	 * @since 1.5.4
	 *
	 * @param \WC_Product      $product  Object object.
	 * @param \WP_REST_Request $request  Request object.
	 * @param bool             $creating If is creating a new object.
	 *
	 * @return \WC_Product
	 */
	public function validate_product_before_saving( $product, $request, $creating ) {

		// Only for MI products when trying to set the stock values for the product directly.
		if (
			( isset( $request['stock_quantity'] ) || isset( $request['manage_stock'] ) || isset( $request['stock_status'] ) ) &&
			'yes' === Helpers::get_product_multi_inventory_status( $product )
		) {

			// Reset the values to the original ones.
			$changes  = $product->get_changes();
			$old_data = $product->get_data();

			if ( array_key_exists( 'manage_stock', $changes ) ) {
				$product->set_manage_stock( $old_data['manage_stock'] );
			}

			if ( array_key_exists( 'stock_quantity', $changes ) ) {
				$product->set_stock_quantity( $old_data['stock_quantity'] );
			}

			if ( array_key_exists( 'stock_status', $changes ) ) {
				$product->set_stock_status( $old_data['stock_status'] );
			}

		}

		return $product;

	}


	/****************************
	 * Instance methods
	 ****************************/

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
	 * @return ProductData instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
