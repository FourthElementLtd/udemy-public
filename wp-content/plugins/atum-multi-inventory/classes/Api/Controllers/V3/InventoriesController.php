<?php
/**
 * REST Multi Inventory's API Inventories controller
 * Handles requests to the /products/<product_id>/inventories endpoint.
 *
 * @since       1.2.4
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     AtumMultiInventory\Api\Controllers
 * @subpackage  V3
 */

namespace AtumMultiInventory\Api\Controllers\V3;

defined( 'ABSPATH' ) || exit;

use Atum\Components\AtumCache;
use Atum\Inc\Globals;
use AtumMultiInventory\Inc\Helpers;
use AtumMultiInventory\Models\Inventory;

class InventoriesController extends \WC_REST_Controller {

	/**
	 * Endpoint namespace
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/(?P<product_id>[\d]+)/inventories';

	/**
	 * If the region restriction mode is set to shipping zones, it'll contain all the
	 * shipping zones registered in WooCommerce
	 *
	 * @var array
	 */
	protected $shipping_zones = array();

	/**
	 * The changes to be applied to the inventory data
	 *
	 * @var array
	 */
	protected $data_changes = array();

	/**
	 * The changes to be applied to the inventory meta data
	 *
	 * @var array
	 */
	protected $meta_data_changes = array();


	/**
	 * Register routes
	 *
	 * @since 1.2.4
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		/**
		 * Extra endpoint to get a list with all the registered inventories (for all products).
		 */
		register_rest_route(
			$this->namespace,
			'/atum/inventories',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all_inventories' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_all_inventories_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', ATUM_MULTINV_TEXT_DOMAIN ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array( 'context' => $this->get_context_param( [ 'default' => 'view' ] ) ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => FALSE,
							'description' => __( 'Whether to bypass trash and force deletion.', ATUM_MULTINV_TEXT_DOMAIN ),
							'type'        => 'boolean',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/batch',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);

		/**
		 * Extra endpoint to perform batch actions to any registered inventories directly
		 * (using the same call to update inventories from distinct products).
		 */
		register_rest_route(
			$this->namespace,
			'/atum/inventories/batch',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);

	}

	/**
	 * Get the items schema, conforming to JSON Schema.
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'atum-inventories',
			'type'       => 'object',
			'properties' => array(
				'id'               => array(
					'description' => __( 'Unique identifier for the inventory.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'product_id'       => array(
					'description' => __( 'The product ID this inventory is linked to.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => TRUE,
				),
				'name'             => array(
					'description' => __( 'The inventory name.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'priority'         => array(
					'description' => __( "The priority index within the list of the product inventories' list.", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'is_main'          => array(
					'description' => __( 'Whether the current inventory is the main inventory.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'inventory_date'   => array(
					'description' => __( 'The date the inventory was created, as GMT.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'lot'              => array(
					'description' => __( 'The LOT/BATCH number.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'write_off'        => array(
					'description' => __( "Whether the current inventory was marked as 'write-off'.", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'region'           => array(
					'description' => __( "If the region restriction mode is enabled, it'll show the list of countries or shipping zones linked to the inventory.", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'location'         => array(
					'description' => __( 'ATUM Location(s) linked to the inventory.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'bbe_date'         => array(
					'description' => __( 'The Best-Before-Expiry date for the inventory, as GMT.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'expiry_days'      => array(
					'description' => __( 'The expiry days before the BBE date when the product should go out of stock.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'inbound_stock'    => array(
					'description' => __( "Inventory's inbound stock.", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'stock_on_hold'    => array(
					'description' => __( "Inventory's stock on hold.", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'sold_today'       => array(
					'description' => __( 'Inventory units sold today.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'sales_last_days'  => array(
					'description' => __( 'Inventory sales the last 14 days.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'reserved_stock'   => array(
					'description' => __( "Inventory stock set 'reserved_stock' within Inventory Logs.", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'customer_returns' => array(
					'description' => __( "Inventory stock set as 'customer returns' within Inventory Logs.", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'warehouse_damage' => array(
					'description' => __( "Stock set as 'warehouse damage' within Inventory Logs.", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'lost_in_post'     => array(
					'description' => __( "Stock set as 'lost in post' within Inventory Logs.", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'other_logs'       => array(
					'description' => __( "Stock set as 'other' within Inventory Logs.", ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'out_stock_days'   => array(
					'description' => __( 'The number of days that the product is Out of stock.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'lost_sales'       => array(
					'description' => __( 'Product lost sales.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'number',
					'context'     => array( 'view' ),
					'readonly'    => TRUE,
				),
				'update_date'      => array(
					'description' => __( 'Last date when the inventory data was calculated and saved for this product, as GMT.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
				),
				'meta_data'        => array(
					'description' => __( 'Meta data.', ATUM_MULTINV_TEXT_DOMAIN ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'sku'                 => array(
								'description' => __( "Inventory's SKU.", ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'manage_stock'        => array(
								'description' => __( 'Whether the stock is being managed for the inventory.', ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'stock_quantity'      => array(
								'description' => __( "Inventory's stock amount.", ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'number',
								'context'     => array( 'view', 'edit' ),
							),
							'backorders'          => array(
								'description' => __( 'Whether the back orders are allowed.', ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'stock_status'        => array(
								'description' => __( "Inventory's stock status.", ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'supplier_id'         => array(
								'description' => __( "Inventoy supplier's ID.", ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'supplier_sku'        => array(
								'description' => __( "Inventory supplier's SKU.", ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'sold_individually'   => array(
								'description' => __( 'Whether the inventory should be sold individually.', ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'out_stock_threshold' => array(
								'description' => __( "Inventory's out of stock threshold.", ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'number',
								'context'     => array( 'view', 'edit' ),
							),
							'purchase_price'      => array(
								'description' => __( "Inventory's purchase price.", ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'number',
								'context'     => array( 'view', 'edit' ),
							),
							'price'               => array(
								'description' => __( "Inventory's price.", ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'number',
								'context'     => array( 'view', 'edit' ),
							),
							'regular_price'       => array(
								'description' => __( "Inventory's regular price.", ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'number',
								'context'     => array( 'view', 'edit' ),
							),
							'sale_price'          => array(
								'description' => __( "Inventory's sale price.", ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'number',
								'context'     => array( 'view', 'edit' ),
							),
							'date_on_sale_from'   => array(
								'description' => __( 'The date when starts the sale price, as GMT.', ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
							),
							'date_on_sale_to'     => array(
								'description' => __( 'The date when ends the sale price, as GMT.', ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
							),
							'out_stock_date'      => array(
								'description' => __( 'The date when the inventory run out of stock, as GMT.', ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'date-time',
								'context'     => array( 'view', 'edit' ),
							),
							'expired_stock'       => array(
								'description' => __( 'The expired stock amount.', ATUM_MULTINV_TEXT_DOMAIN ),
								'type'        => 'number',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );

	}

	/**
	 * Get the query params for collections of inventories (for filtering purposes)
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();

		$inventory_params = array(
			'after'             => array(
				'description'       => __( 'Limit response to resources created after a given ISO8601 compliant date.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'before'            => array(
				'description'       => __( 'Limit response to resources created before a given ISO8601 compliant date.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'exclude'           => array(
				'description'       => __( 'Ensure result set excludes specific IDs.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'integer',
				),
				'default'           => array(),
				'sanitize_callback' => 'wp_parse_id_list',
			),
			'include'           => array(
				'description'       => __( 'Limit result set to specific IDs.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'integer',
				),
				'default'           => array(),
				'sanitize_callback' => 'wp_parse_id_list',
			),
			'offset'            => array(
				'description'       => __( 'Offset the result set by a specific number of items.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'order'             => array(
				'description'       => __( 'Order sort attribute ascending or descending.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'              => 'string',
				'default'           => 'asc',
				'enum'              => array( 'asc', 'desc' ),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'orderby'           => array(
				'description'       => __( 'Sort collection by object attribute.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'              => 'string',
				'default'           => 'priority',
				'enum'              => array(
					'priority',
					'inventory_date',
					'id',
					'name',
					'bbe_date',
				),
				'validate_callback' => 'rest_validate_request_arg',
			),
			'name'              => array(
				'description'       => __( 'Limit result set to inventories with a specific name.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'exclude_write_off' => array(
				'description'       => __( "Exclude from result set the inventories that were marked as 'write off'.", ATUM_MULTINV_TEXT_DOMAIN ),
				'default'           => TRUE,
				'type'              => 'boolean',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'lot'               => array(
				'description'       => __( 'Limit result set to inventories with the specified LOT/BATCH number.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		$region_restriction_mode = Helpers::get_region_restriction_mode();

		if ( 'countries' === $region_restriction_mode ) {

			$params['country'] = array(
				'description'       => __( 'If the country restriction mode is enabled, limit the result set to inventories linked to the specified country.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => array_keys( Helpers::get_regions( 'countries' ) ),
			);

		}
		elseif ( 'shipping-zones' === $region_restriction_mode ) {

			$params['shipping_zone'] = array(
				'description'       => __( 'If the shipping zone restriction mode is enabled, limit the result set to inventories linked to the specified shipping zone.', ATUM_MULTINV_TEXT_DOMAIN ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => $this->get_shipping_zones(),
			);

		}

		return array_merge( $params, $inventory_params );

	}

	/**
	 * Get the query params for collections of inventories (used by the 'all inventories' endpoint)
	 *
	 * @since 1.4.2
	 *
	 * @return array
	 */
	public function get_all_inventories_collection_params() {

		$collection_params = $this->get_collection_params();

		$collection_params['product'] = array(
			'description'       => __( 'Limit result set to inventories of the specified product ID.', ATUM_MULTINV_TEXT_DOMAIN ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$collection_params['orderby']['default'] = 'id';

		return $collection_params;

	}

	/**
	 * Check if a given request has access to read items
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! wc_rest_check_post_permissions( 'product', 'read' ) ) {
			return new \WP_Error( 'atum_mi_rest_cannot_view', __( 'Sorry, you cannot list resources.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access to create an item
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {

		if ( ! wc_rest_check_post_permissions( 'product', 'create' ) ) {
			return new \WP_Error( 'atum_mi_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access to read an item
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {

		$object = $this->get_object( (int) $request['id'] );

		if ( $object && 0 !== $object->id && ! wc_rest_check_post_permissions( 'product', 'read', $object->product_id ) ) {
			return new \WP_Error( 'atum_mi_rest_cannot_view', __( 'Sorry, you cannot view this resource.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access to update an item
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {

		$object = $this->get_object( (int) $request['id'] );

		if ( $object && 0 !== $object->id && ! wc_rest_check_post_permissions( 'product', 'edit', $object->product_id ) ) {
			return new \WP_Error( 'atum_mi_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access to delete an item
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error
	 */
	public function delete_item_permissions_check( $request ) {

		$object = $this->get_object( (int) $request['id'] );

		if ( $object && 0 !== $object->id && ! wc_rest_check_post_permissions( 'product', 'delete', $object->product_id ) ) {
			return new \WP_Error( 'atum_mi_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Check if a given request has access batch create, update and delete items
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|\WP_Error
	 */
	public function batch_items_permissions_check( $request ) {

		if ( ! wc_rest_check_post_permissions( 'product', 'batch' ) ) {
			return new \WP_Error( 'atum_mi_rest_cannot_batch', __( 'Sorry, you are not allowed to batch manipulate this resource.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => rest_authorization_required_code() ] );
		}

		return TRUE;

	}

	/**
	 * Get the inventory object
	 *
	 * @since  1.2.4
	 *
	 * @param int $id Object ID.
	 *
	 * @return Inventory
	 */
	protected function get_object( $id ) {

		$was_cache_disabled = AtumCache::is_cache_disabled();

		if ( ! $was_cache_disabled ) {
			AtumCache::disable_cache();
		}

		$inventory = Helpers::get_inventory( $id );

		if ( ! $was_cache_disabled ) {
			AtumCache::enable_cache();
		}

		return $inventory;
	}

	/**
	 * Get all the inventories for a specific product
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_REST_Response|array
	 */
	public function get_items( $request ) {

		$product_id              = (int) $request['product_id'];
		$inventories             = Inventory::get_product_inventories( $product_id, '', FALSE, $request['exclude_write_off'] );
		$region_restriction_mode = Helpers::get_region_restriction_mode();
		$shipping_zones          = 'shipping-zones' === $region_restriction_mode ? $this->get_shipping_zones() : array();
		$prepared_inventories    = $filtered_inventories = array();

		foreach ( $inventories as $index => $inventory ) {

			// Exclude inventories that were created before the 'after' date filter.
			if ( $request['after'] ) {
				if ( ! $inventory->inventory_date || $inventory->inventory_date->getTimestamp() > strtotime( $request['after'] ) ) {
					continue;
				}
			}

			// Exclude inventories that were created after the 'before' date filter.
			if ( $request['before'] ) {
				if ( ! $inventory->inventory_date || $inventory->inventory_date->getTimestamp() < strtotime( $request['before'] ) ) {
					continue;
				}
			}

			// Exclude inventory IDs.
			if ( ! empty( $request['exclude'] ) && in_array( $inventory->id, $request['exclude'] ) ) {
				continue;
			}

			// Include only the specified inventory IDs.
			if ( ! empty( $request['include'] ) && ! in_array( $inventory->id, $request['include'] ) ) {
				continue;
			}

			// Set an offset.
			if ( $request['offset'] && $request['offset'] > $index ) {
				continue;
			}

			// Filter by name.
			if ( $request['name'] && $inventory->name !== $request['name'] ) {
				continue;
			}

			// Filter by LOT/BATCH.
			if ( $request['lot'] && $inventory->lot !== $request['lot'] ) {
				continue;
			}

			// Filter by country.
			if ( 'countries' === $region_restriction_mode && $request['country'] ) {

				if ( ! in_array( $request['country'], $inventory->region, TRUE ) ) {
					continue;
				}

			}

			// Filter by shipping zones.
			if (
				'shipping-zones' === $region_restriction_mode && $request['shipping_zone'] &&
				in_array( $request['shipping_zone'], $shipping_zones, TRUE )
			) {

				if ( ! in_array( array_search( $request['shipping_zone'], $shipping_zones ), $inventory->region ) ) {
					continue;
				}

			}

			$filtered_inventories[] = $inventory;

		}

		// Process the order.
		if ( 'priority' !== $request['orderby'] ) {

			$order_by = $request['orderby'];

			usort( $filtered_inventories, function ( $a, $b ) use ( $order_by ) {

				$a_value = $a->$order_by;
				$b_value = $b->$order_by;

				switch ( $order_by ) {
					case 'id':
						return ( $a_value < $b_value ) ? -1 : 1;

					case 'name':
						return strcasecmp( $a_value, $b_value );

					case 'inventory_date':
					case 'bbe_date':
						/**
						 * Variable definition
						 *
						 * @var \WC_DateTime $a_value
						 * @var \WC_DateTime $b_value
						 */
						if ( ! $a_value && ! $b_value ) {
							return 0;
						}
						elseif ( ! $a_value ) {
							return 1;
						}
						elseif ( ! $a_value ) {
							return -1;
						}

						return ( $a_value->getTimestamp() < $b_value->getTimestamp() ) ? -1 : 1;

				}

				return 0;

			} );

		}

		if ( 'desc' === $request['order'] ) {
			$filtered_inventories = array_reverse( $filtered_inventories );
		}

		// Prepare the response.
		foreach ( $filtered_inventories as $filtered_inventory ) {
			$data                   = $this->prepare_item_for_response( $filtered_inventory, $request );
			$prepared_inventories[] = $this->prepare_response_for_collection( $data );
		}

		return $prepared_inventories;

	}

	/**
	 * Get all the registered inventories
	 *
	 * @since 1.4.2
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_REST_Response|array|\WP_Error
	 */
	public function get_all_inventories( $request ) {

		global $wpdb;

		$default_data = array_keys( Inventory::get_default_data() );
		$default_meta = array_keys( Inventory::get_default_meta() );
		unset( $default_data[ array_search( 'location', $default_data ) ] );
		unset( $default_meta[ array_search( 'original_stock', $default_meta ) ] );

		// Pagination.
		$per_page = $request['per_page'] ?: 10;
		$offset   = $request['offset'] ?: 0;

		if ( 0 === $offset ) {
			$page   = $request['page'] ?: 1;
			$offset = ( $page - 1 ) * $per_page;
		}

		$limit = "LIMIT $offset, $per_page";

		// Order by.
		$orderby = '';
		if (
			$request['orderby'] && (
				in_array( $request['orderby'], $default_data ) ||
				in_array( $request['orderby'], $default_meta ) ||
				'id' === $request['orderby']
			)
		) {

			$order   = $request['order'];
			$orderby = $request['orderby'];

			$orderby = in_array( $request['orderby'], $default_data ) ? "i.$orderby" : "m.$orderby";
			$orderby = " ORDER BY $orderby $order";

		}

		$where = array();

		// Included IDs.
		if ( $request['include'] ) {
			$included = array_map( 'absint', (array) $request['include'] );
			$where[]  = 'i.id IN (' . implode( ',', $included ) . ')';
		}

		// Excluded IDs.
		if ( $request['exclude'] ) {
			$excluded = array_map( 'absint', (array) $request['exclude'] );
			$where[]  = 'i.id NOT IN (' . implode( ',', $excluded ) . ')';
		}

		// When filtering by product, get the inventories of the specified product ID.
		if ( ! empty( $request['product'] ) ) {
			$product_id = absint( $request['product'] );
			$where[]    = "i.product_id = $product_id";
		}

		// When filtering by name, get the inventories of the specified name.
		if ( ! empty( $request['name'] ) ) {
			$name    = sanitize_text_field( $request['name'] );
			$where[] = "i.name = '$name'";
		}

		// Exclude write off.
		$exclude_write_off = FALSE === $request['exclude_write_off'] ? 1 : 0;
		if ( $exclude_write_off ) {
			$where[] = "i.write_off = $exclude_write_off";
		}

		// When filtering by lot, get the inventories of the specified LOT/BATCH number.
		if ( ! empty( $request['lot'] ) ) {
			$lot     = sanitize_text_field( $request['lot'] );
			$where[] = "i.lot = '$lot'";
		}

		// Search (only by name for now).
		if ( $request['search'] ) {
			$search  = sanitize_text_field( $request['search'] );
			$where[] = "i.name LIKE '%$search%'";
		}

		// Date filtering.
		if ( $request['after'] ) {
			$where[] = "i.inventory_date > '{$request['after']}'";
		}

		if ( $request['before'] ) {
			$where[] = "i.inventory_date < '{$request['before']}'";
		}

		$region_restriction_mode = Helpers::get_region_restriction_mode();

		// Filter by countries.
		if ( $request['countries'] && 'countries' === $region_restriction_mode ) {
			$country = sanitize_text_field( $request['countries'] );
			$where[] = "i.region LIKE '%\"$country\"%'";
		}

		// Filter by shipping zones.
		$join = '';
		if ( $request['shipping_zones'] && 'shipping_zones' === $region_restriction_mode ) {
			$shipping_zone = sanitize_text_field( $request['shipping_zones'] );
			$where[]       = "ir.zone_id IN ( SELECT zone_id FROM {$wpdb->prefix}woocommerce_shipping_zones WHERE zone_name = '$shipping_zone' )";
			$join          = "LEFT JOIN $wpdb->prefix" . Inventory::INVENTORY_REGIONS_TABLE . ' ir ON i.`id` = ir.`inventory_id`';
		}

		$where_str = ! empty( $where ) ? 'WHERE ' . implode( "\n AND ", $where ) : '';
		$fields    = 'i.' . implode( ', i.', $default_data ) . ', m.' . implode( ', m.', $default_meta );

		$query = "
			SELECT i.id, $fields
			FROM $wpdb->prefix" . Inventory::INVENTORIES_TABLE . " i 
			LEFT JOIN $wpdb->prefix" . Inventory::INVENTORY_META_TABLE . " m ON i.`id` = m.`inventory_id`
			$join
			$where_str
			$orderby
			$limit
		";

		try {

			$query_result = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		} catch ( \Exception $e ) {
			return new \WP_Error( 'atum_mi_rest_internal_error', __( 'Internal server error.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 500 ] );
		}

		$inventories = array();
		foreach ( $query_result as $inventory ) {

			$inventory     = $this->get_object( $inventory->id );
			$data          = $this->prepare_item_for_response( $inventory, $request );
			$inventories[] = $this->prepare_response_for_collection( $data );

		}

		$page              = (int) $request['page'];
		$total_inventories = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->prefix" . Inventory::INVENTORIES_TABLE ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$max_pages = ceil( (int) $total_inventories / (int) $per_page );

		$response = rest_ensure_response( $inventories );
		$response->header( 'X-WP-Total', (int) $total_inventories );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = rest_url( sprintf( '/%s/%s', $this->namespace, 'inventories' ) );

		if ( $page > 1 ) {

			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );

		}

		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;

	}

	/**
	 * Get a single inventory
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item( $request ) {

		$id        = (int) $request['id'];
		$inventory = $this->get_object( $id );

		if ( ! $id || 0 === $inventory->id ) {
			return new \WP_Error( 'atum_mi_rest_invalid_inventory_id', __( 'Invalid ID.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}
		elseif ( 0 === $inventory->product_id ) {
			return new \WP_Error( 'atum_mi_rest_invalid_inventory', __( 'No inventory was found with the specified ID within the specified product.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}
		elseif ( $inventory->product_id !== (int) $request['product_id'] ) {
			return new \WP_Error( 'atum_mi_rest_invalid_inventory_product_id', __( 'Invalid product ID.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		return $this->prepare_item_for_response( $inventory, $request );

	}

	/**
	 * Update a single inventory.
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ) {

		$id        = absint( $request['id'] );
		$inventory = $this->get_object( $id );

		if ( ! $id || 0 === $inventory->id ) {
			return new \WP_Error( 'atum_mi_rest_inventory_invalid_id', __( 'ID is invalid.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 400 ] );
		}

		/**
		 * Fires before a single item to be created or updated via the REST API.
		 *
		 * @param Inventory        $inventory Inventory object.
		 * @param \WP_REST_Request $request   Request object.
		 * @param bool             $creating  True when creating item, false when updating.
		 */
		do_action( 'atum/multi_inventory/api/rest_before_insert_inventory', $inventory, $request, FALSE );

		$inventory = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $inventory ) ) {
			return $inventory;
		}

		if ( ! empty( $this->data_changes ) ) {
			$inventory->save();
		}

		if ( ! empty( $this->meta_data_changes ) ) {
			$inventory->save_meta();
		}

		$this->update_additional_fields_for_object( $inventory, $request );

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param Inventory        $inventory Inventory object.
		 * @param \WP_REST_Request $request   Request object.
		 * @param bool             $creating  True when creating item, false when updating.
		 */
		do_action( 'atum/multi_inventory/api/rest_insert_inventory', $inventory, $request, FALSE );

		$request->set_param( 'context', 'edit' );
		$inventory = $this->get_object( $inventory->id );
		$response  = $this->prepare_item_for_response( $inventory, $request );

		return rest_ensure_response( $response );

	}

	/**
	 * Creates a single inventory.
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or \WP_Error object on failure.
	 */
	public function create_item( $request ) {

		if ( ! empty( $request['id'] ) ) {
			return new \WP_Error( 'atum_mi_rest_inventory_exists', __( 'Cannot create existing inventory.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 400 ] );
		}

		$product_id = absint( $request['product_id'] );
		$product    = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			return new \WP_Error( 'atum_mi_rest_inventory_product_not_found', __( 'The product ID does not match with any existing product.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 400 ] );
		}

		$prepared_inventory = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $prepared_inventory ) ) {
			return $prepared_inventory;
		}

		$prepared_inventory->set_data( [ 'product_id' => $product_id ] );
		$prepared_inventory->save();

		/**
		 * Fires after a single inventory is created or updated via the REST API.
		 *
		 * @param Inventory        $prepared_inventory Inserted or updated post object.
		 * @param \WP_REST_Request $request            Request object.
		 * @param bool             $creating           True when creating a post, false when updating.
		 */
		do_action( 'atum/multi_inventory/api/rest_insert_inventory', $prepared_inventory, $request, TRUE );

		if ( ! empty( $this->meta_data_changes ) ) {
			$prepared_inventory->save_meta();
		}

		$fields_update = $this->update_additional_fields_for_object( $prepared_inventory, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		/**
		 * Fires after a single inventory is completely created or updated via the REST API.
		 *
		 * @param Inventory        $prepared_inventory Inserted or updated post object.
		 * @param \WP_REST_Request $request            Request object.
		 * @param bool             $creating           True when creating a post, false when updating.
		 */
		do_action( 'atum/multi_inventory/api/rest_after_insert_inventory', $prepared_inventory, $request, TRUE );

		$inventory = $this->get_object( $prepared_inventory->id );
		$response  = $this->prepare_item_for_response( $inventory, $request );
		$response  = rest_ensure_response( $response );

		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $prepared_inventory->id ) ) );

		return $response;

	}

	/**
	 * Delete a single inventory
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {

		$id        = absint( $request['id'] );
		$force     = (bool) $request['force'];
		$inventory = $this->get_object( $id );

		if ( ! $id || 0 === $inventory->id ) {
			return new \WP_Error( 'atum_mi_rest_inventory_invalid_id', __( 'Invalid ID.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		if ( $inventory->is_main() ) {
			return new \WP_Error( 'atum_mi_rest_inventory_is_main', __( 'The Main Inventory cannot be deleted.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 404 ] );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $inventory, $request );

		// Only permanent deletions are supported for inventories.
		if ( ! $force ) {
			return new \WP_Error( 'atum_mi_rest_inventory_trash_not_supported', __( 'The Inventories do not support trashing.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 501 ] );
		}

		// Get all the products that were linked to this supplier and unlink them.
		$result = $inventory->delete();

		if ( ! $result ) {
			return new \WP_Error( 'atum_mi_rest_cannot_delete', __( 'The Inventory cannot be deleted.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 500 ] );
		}

		/**
		 * Fires after a single inventory is deleted via the REST API.
		 *
		 * @param Inventory         $inventory The deleted inventory.
		 * @param \WP_REST_Response $response  The response data.
		 * @param \WP_REST_Request  $request   The request sent to the API.
		 */
		do_action( 'atum/multi_inventory/api/rest_delete_inventory', $inventory, $response, $request );

		return $response;

	}

	/**
	 * Bulk create, update and delete items.
	 *
	 * @since 1.4.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_Error[] | \WP_REST_Response[]
	 */
	public function batch_items( $request ) {

		/**
		 * REST Server
		 *
		 * @var \WP_REST_Server $wp_rest_server
		 */
		global $wp_rest_server;

		// Get the request params.
		$items    = array_filter( $request->get_params() );
		$query    = $request->get_query_params();
		$response = array();

		// Check batch limit.
		$limit = $this->check_batch_limit( $items );
		if ( is_wp_error( $limit ) ) {
			return $limit;
		}

		if ( ! empty( $items['create'] ) ) {

			foreach ( $items['create'] as $item ) {

				$_item = new \WP_REST_Request( 'POST' );

				// Default parameters.
				$defaults = array();
				$schema   = $this->get_public_item_schema();

				foreach ( $schema['properties'] as $arg => $options ) {
					if ( isset( $options['default'] ) ) {
						$defaults[ $arg ] = $options['default'];
					}
				}

				$_item->set_default_params( $defaults );

				// Set request parameters.
				$_item->set_body_params( $item );

				// Set query (GET) parameters.
				$_item->set_query_params( $query );

				if ( isset( $items['product_id'] ) ) {
					$_item->set_url_params( [ 'product_id' => $items['product_id'] ] );
				}

				$_response = $this->create_item( $_item );

				if ( is_wp_error( $_response ) ) {

					$response['create'][] = array(
						'id'    => 0,
						'error' => array(
							'code'    => $_response->get_error_code(),
							'message' => $_response->get_error_message(),
							'data'    => $_response->get_error_data(),
						),
					);

				}
				else {
					$response['create'][] = $wp_rest_server->response_to_data( $_response, '' );
				}

			}

		}

		if ( ! empty( $items['update'] ) ) {

			foreach ( $items['update'] as $item ) {

				$_item = new \WP_REST_Request( 'PUT' );
				$_item->set_body_params( $item );

				// If coming from the /products/<product_id>/inventories endpoint.
				// The product ID can be taken from the URL.
				if ( isset( $items['product_id'] ) ) {
					$_item->set_url_params( [ 'product_id' => $items['product_id'] ] );
				}
				// If coming from the /atum/inventories endpoint.
				// The product ID must be passed within each item.
				else {

					$inventory = $this->get_object( $item['id'] );
					$_item->set_url_params( [ 'product_id' => $inventory->product_id ] );
				}

				$_response = $this->update_item( $_item );

				if ( is_wp_error( $_response ) ) {

					$response['update'][] = array(
						'id'    => $item['id'],
						'error' => array(
							'code'    => $_response->get_error_code(),
							'message' => $_response->get_error_message(),
							'data'    => $_response->get_error_data(),
						),
					);

				}
				else {
					$response['update'][] = $wp_rest_server->response_to_data( $_response, '' );
				}

			}

		}

		if ( ! empty( $items['delete'] ) ) {

			foreach ( $items['delete'] as $id ) {

				$id = (int) $id;

				if ( 0 === $id ) {
					continue;
				}

				$_item = new \WP_REST_Request( 'DELETE' );
				$_item->set_query_params(
					array(
						'id'    => $id,
						'force' => TRUE,
					)
				);
				$_response = $this->delete_item( $_item );

				if ( is_wp_error( $_response ) ) {

					$response['delete'][] = array(
						'id'    => $id,
						'error' => array(
							'code'    => $_response->get_error_code(),
							'message' => $_response->get_error_message(),
							'data'    => $_response->get_error_data(),
						),
					);

				}
				else {
					$response['delete'][] = $wp_rest_server->response_to_data( $_response, '' );
				}
			}
		}

		return $response;

	}

	/**
	 * Prepares a single inventory for response
	 *
	 * @since 1.2.4
	 *
	 * @param Inventory        $inventory Inventory object.
	 * @param \WP_REST_Request $request   Request object.
	 *
	 * @return \WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $inventory, $request ) {

		// Base fields for every inventory.
		$fields = $this->get_fields_for_response( $request );
		$data   = $date_fields = array();

		foreach ( $fields as $field ) {

			$field_value = $inventory->$field;

			if ( is_wp_error( $field_value ) && is_callable( array( $inventory, "get_$field" ) ) ) {
				$field_value = call_user_func( array( $inventory, "get_$field" ) );
			}

			if ( 'meta_data' === $field || is_wp_error( $field_value ) ) {
				continue;
			}

			// Date fields.
			if ( strpos( $field, 'date' ) !== FALSE ) {
				$data[ $field ] = wc_rest_prepare_date_response( $field_value );
			}
			// Boolean fields.
			elseif ( in_array( $field, [ 'is_main', 'write_off' ], TRUE ) ) {
				$data[ $field ] = wc_string_to_bool( $field_value );
			}
			// Other fields.
			else {
				$data[ $field ] = $field_value;
			}

		}

		// Get rid of the non-existing shipping zones and add the names.
		$region_restriction_mode = Helpers::get_region_restriction_mode();
		if ( ! empty( $data['region'] ) && 'shipping-zones' === $region_restriction_mode ) {

			$shipping_zones    = array();
			$wc_shipping_zones = $this->get_shipping_zones();

			foreach ( $data['region'] as $shipping_id ) {

				if ( in_array( $shipping_id, array_keys( $wc_shipping_zones ) ) ) {
					$shipping_zones[ $shipping_id ] = $wc_shipping_zones[ $shipping_id ];
				}

			}

			$data['region'] = $shipping_zones;

		}

		// Filter locations and show location names.
		if ( ! empty( $data['location'] ) ) {

			$locations = get_terms( array(
				'taxonomy'   => Globals::PRODUCT_LOCATION_TAXONOMY,
				'hide_empty' => FALSE,
				'include'    => $data['location'],
			) );

			$data['location'] = array();
			foreach ( $locations as $location ) {
				$data['location'][ $location->term_id ] = $location->slug;
			}

		}

		// Get the inventory meta data.
		$schema      = $this->get_item_schema();
		$meta_fields = array_keys( $schema['properties']['meta_data']['items']['properties'] );

		foreach ( $meta_fields as $meta_field ) {

			$meta_value = $inventory->$meta_field;

			if ( is_wp_error( $meta_value ) ) {
				continue;
			}

			// Date fields.
			if ( strpos( $meta_field, 'date' ) !== FALSE ) {
				$data['meta_data'][ $meta_field ] = wc_rest_prepare_date_response( $meta_value );
			}
			// Boolean fields.
			elseif ( in_array( $meta_field, [ 'manage_stock', 'sold_individually' ], TRUE ) ) {
				$data['meta_data'][ $meta_field ] = wc_string_to_bool( $meta_value );
			}
			// Other fields.
			else {
				$data['meta_data'][ $meta_field ] = $meta_value;
			}

		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$links = $this->prepare_links( $inventory, $request );
		$response->add_links( $links );

		/**
		 * Filters the post data for a response.
		 *
		 * @param \WP_REST_Response $response  The response object.
		 * @param Inventory         $inventory Inventory object.
		 * @param \WP_REST_Request  $request   Request object.
		 */
		return apply_filters( 'atum/multi_inventory/api/rest_prepare_inventories', $response, $inventory, $request );

	}

	/**
	 * Prepare a single inventory for create or update.
	 *
	 * @since 1.2.4
	 *
	 * @param \WP_REST_Request $request  Request object.
	 * @param bool             $creating If it's creating a new object.
	 *
	 * @return \WP_Error|Inventory
	 */
	protected function prepare_item_for_database( $request, $creating = FALSE ) {

		$inventory_id = 0;

		if ( isset( $request['id'] ) ) {

			$existing_inventory = $this->get_object( $request['id'] );

			if ( 0 === $existing_inventory->id ) {
				return new \WP_Error( 'atum_mi_rest_inventory_not_found', __( 'Inventory not found.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 400 ] );
			}

			$inventory_id = $existing_inventory->id;

		}

		// Only one Main Inventory is allowed per product.
		if ( isset( $request['is_main'] ) && TRUE === $request['is_main'] ) {

			$main_inventory = Inventory::get_product_main_inventory( $request['product_id'] );

			if ( 0 !== $main_inventory->id && $inventory_id !== $main_inventory->id ) {
				return new \WP_Error( 'atum_mi_rest_main_inventory_exists', __( 'This product already has a Main Inventory.', ATUM_MULTINV_TEXT_DOMAIN ), [ 'status' => 400 ] );
			}

		}

		// Filter the regions.
		$region = array();
		if ( isset( $request['region'] ) ) {

			$region_restriction_mode = Helpers::get_region_restriction_mode();

			// We must receive the shipping zone names.
			if ( 'shipping-zones' === $region_restriction_mode ) {

				$wc_shipping_zones = $this->get_shipping_zones();
				$shipping_zones    = array_intersect( $wc_shipping_zones, $request['region'] );
				$region            = array_keys( $shipping_zones ); // Get only the IDs.

			}
			// We must receive the country codes.
			elseif ( 'countries' === $region_restriction_mode ) {

				$wc_countries = Helpers::get_regions( 'countries' );
				$region       = array_intersect( $request['region'], array_keys( $wc_countries ) );

			}

		}

		// Filter the locations.
		$locations = array();
		if ( isset( $request['location'] ) ) {

			// We allow term IDs or term slugs here.
			foreach ( $request['location'] as $location ) {

				// Term ID.
				if ( is_numeric( $location ) ) {
					$locations[] = absint( $location );
				}
				// Term slug.
				else {

					$location_slug = esc_attr( $location );
					$location_term = get_term_by( 'slug', $location_slug, Globals::PRODUCT_LOCATION_TAXONOMY );

					if ( $location_term ) {
						$locations[] = $location_term->term_id;
					}

				}

			}

		}

		$inventory = $this->get_object( $inventory_id );
		$fields    = $this->get_fields_for_response( $request );
		$schema    = $this->get_item_schema();

		// Clean any previous stored value to not cause problems when performing a batch operation.
		$this->data_changes = $this->meta_data_changes = array();

		// Prepare data.
		foreach ( $fields as $field ) {

			if (
				isset( $request[ $field ] ) && 'meta_data' !== $field &&
				( ! isset( $schema['properties'][ $field ]['readonly'] ) || FALSE === $schema['properties'][ $field ]['readonly'] )
			) {

				switch ( $field ) {
					case 'region':
						$field_value = $region;
						break;

					case 'location':
						$field_value = $locations;
						break;

					default:
						$field_value = $request[ $field ];
						break;
				}

				$this->data_changes[ $field ] = $field_value;

			}

		}

		if ( ! empty( $this->data_changes ) ) {
			$inventory->set_data( $this->data_changes );
		}

		// Prepare meta.
		if ( ! empty( $request['meta_data'] ) ) {
			foreach ( array_keys( $schema['properties']['meta_data']['items']['properties'] ) as $meta_field ) {

				if (
					isset( $request['meta_data'][ $meta_field ] ) &&
					( ! isset( $meta_field['readonly'] ) || FALSE === $meta_field['readonly'] )
				) {
					$this->meta_data_changes[ $meta_field ] = $request['meta_data'][ $meta_field ];
				}

			}
		}

		if ( ! empty( $this->meta_data_changes ) ) {
			$inventory->set_meta( $this->meta_data_changes );
		}

		/**
		 * Filters an inventory before it is inserted/updated via the REST API.
		 *
		 * @param \stdClass        $inventory An object representing a single inventory prepared for inserting or updating the database.
		 * @param \WP_REST_Request $request   Request object.
		 */
		return apply_filters( 'atum/multi_inventory/api/rest_pre_insert_inventory', $inventory, $request );

	}

	/**
	 * Prepare links for the request
	 *
	 * @since 1.2.4
	 *
	 * @param Inventory        $object  Object data.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return array Links for the given inventory.
	 */
	protected function prepare_links( $object, $request ) {

		$product_id = (int) $object->product_id;
		$base       = str_replace( '(?P<product_id>[\d]+)', $product_id, $this->rest_base );

		return array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $base, $object->id ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ),
			),
			'up'         => array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $product_id ) ),
			),
		);

	}

	/**
	 * Get all the shhipping zones registered in WooCommerce
	 *
	 * @since 1.2.4
	 *
	 * @return array
	 */
	protected function get_shipping_zones() {

		if ( empty( $this->shipping_zones ) ) {

			$wc_shipping_zones = Helpers::get_regions( 'shipping-zones' );

			if ( ! empty( $wc_shipping_zones ) ) {
				foreach ( $wc_shipping_zones as $shipping_zone ) {
					$this->shipping_zones[ $shipping_zone['id'] ] = $shipping_zone['zone_name'];
				}
			}

		}

		return $this->shipping_zones;

	}

}
