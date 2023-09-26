<?php
/**
 * Main class for Multi-Inventory add-on
 *
 * @package         AtumMultiInventory
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.0.0
 */

namespace AtumMultiInventory;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Api\MultiInventoryApi;
use AtumMultiInventory\Inc\BatchTracking;
use AtumMultiInventory\Inc\GeoPrompt;
use AtumMultiInventory\Inc\Hooks;
use AtumMultiInventory\Inc\Ajax;
use AtumMultiInventory\Inc\InventoryShipping;
use AtumMultiInventory\Inc\ListTables;
use AtumMultiInventory\Inc\MultiPrice;
use AtumMultiInventory\Inc\Orders;
use AtumMultiInventory\Inc\ProductMetaBoxes;
use AtumMultiInventory\Inc\ProductProps;
use AtumMultiInventory\Inc\ReserveStock;
use AtumMultiInventory\Inc\SelectableInventories;
use AtumMultiInventory\Inc\Settings;
use AtumMultiInventory\Inc\Upgrade;
use AtumMultiInventory\Integrations\ProductLevels;
use AtumMultiInventory\Integrations\Wpml;


class MultiInventory {

	/**
	 * The singleton instance holder
	 *
	 * @var MultiInventory
	 */
	private static $instance;

	/**
	 * The product types that are MI-compatible
	 *
	 * @var array
	 */
	private static $compatible_product_types = [
		'simple',
		'variation',
	];

	/**
	 * The product types that them or their children are MI-compatible
	 *
	 * @var array
	 */
	private static $compatible_parent_types = [ 'variable' ];

	/**
	 * The product types that them or their children are MI-compatible
	 *
	 * @since 1.3.4
	 *
	 * @var array
	 */
	private static $compatible_child_types = [ 'variation' ];


	/**
	 * MultiInventory singleton constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// WC Product Bundles compatibility.
		if ( class_exists( '\WC_Product_Bundle' ) ) {
			self::$compatible_product_types[] = 'bundle';
		}

		// WC Subscriptions compatibility.
		if ( class_exists( '\WC_Subscriptions' ) ) {
			self::$compatible_product_types  = array_merge( self::$compatible_product_types, [ 'subscription', 'subscription_variation' ] );
			self::$compatible_parent_types[] = 'variable-subscription';
		}

		// Load language files.
		load_plugin_textdomain( ATUM_MULTINV_TEXT_DOMAIN, FALSE, plugin_basename( ATUM_MULTINV_PATH ) . '/languages' ); // phpcs:ignore: WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found

		add_action( 'atum/after_init', array( $this, 'init' ) );

		// Register the widgets.
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		// Make the Multi-Inventory cache group, non persistent.
		wp_cache_add_non_persistent_groups( ATUM_MULTINV_TEXT_DOMAIN );

		// Load dependencies.
		$this->load_dependencies();

	}

	/**
	 * Check the add-on version and run the installation script if needed
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Check the add-on version and run the installation script if needed.
		$db_version = get_option( 'atum_multi_inventory_version' );
		if ( version_compare( ATUM_MULTINV_VERSION, $db_version, '!=' ) ) {
			new Upgrade( $db_version ?: '0.0.1' );
		}

		// Load PL integration if needed.
		if ( Addons::is_addon_active( 'product_levels' ) ) {
			ProductLevels::get_instance();
		}

		// Load WPML integration if needed.
		if ( class_exists( '\SitePress' ) && class_exists( '\woocommerce_wpml' ) ) {
			new Wpml();
		}

	}

	/**
	 * Load the add-on dependencies
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {

		Ajax::get_instance();
		Hooks::get_instance();
		ProductMetaBoxes::get_instance();
		ProductProps::get_instance();
		Orders::get_instance();
		Settings::get_instance();
		MultiPrice::get_instance();
		ListTables::get_instance();
		GeoPrompt::get_instance();
		MultiInventoryApi::get_instance();
		SelectableInventories::get_instance();
		InventoryShipping::get_instance();

		if ( 'yes' === AtumHelpers::get_option( 'mi_batch_tracking', 'no' ) ) {
			BatchTracking::get_instance();
		}

		// Table needed for the reserved stock feature was added in WC 4.3.
		if ( get_option( 'woocommerce_schema_version', 0 ) >= 430 ) {
			ReserveStock::get_instance();
		}

		// Load all the available shortcodes.
		AtumHelpers::load_psr4_classes( ATUM_MULTINV_PATH . 'classes/Shortcodes/', __NAMESPACE__ . '\\Shortcodes\\' );

	}

	/**
	 * Register widgets
	 *
	 * @since 1.0.0
	 */
	public function register_widgets() {
		register_widget( __NAMESPACE__ . '\Widgets\UserDestinationForm' );
	}

	/**
	 * Getter for the compatible_product_types prop
	 *
	 * @since 1.0.1
	 */
	public static function get_compatible_product_types() {

		return apply_filters( 'atum/multi_inventory/compatible_product_types', self::$compatible_product_types );
	}

	/**
	 * Getter for the compatible_product_primary_types prop
	 *
	 * @since 1.0.1
	 */
	public static function get_compatible_parent_types() {

		return apply_filters( 'atum/multi_inventory/compatible_parent_types', self::$compatible_parent_types );
	}

	/**
	 * Getter for the compatible_product_primary_types prop
	 *
	 * @since 1.0.1
	 */
	public static function get_compatible_types() {

		return array_merge( self::get_compatible_product_types(), self::get_compatible_parent_types() );
	}

	/**
	 * Getter for the compatible_product_primary_types prop
	 *
	 * @since 1.3.4
	 */
	public static function get_compatible_child_types() {

		return apply_filters( 'atum/multi_inventory/compatible_child_types', self::$compatible_child_types );
	}

	/**
	 * Check whether the passed product type is compatible with Multi Inventory
	 *
	 * @since 1.2.0.2
	 *
	 * @param string $product_type
	 *
	 * @return bool
	 */
	public static function is_mi_compatible_product_type( $product_type ) {

		return in_array( $product_type, self::get_compatible_types() );
	}

	/**
	 * Check whether the passed product type can have linked Inventories.
	 *
	 * @since 1.2.5.1
	 *
	 * @param string $product_type
	 *
	 * @return bool
	 */
	public static function can_have_assigned_mi_product_type( $product_type ) {

		return in_array( $product_type, self::get_compatible_product_types() );
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
	 * @return MultiInventory instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
