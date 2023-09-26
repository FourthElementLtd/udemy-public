<?php
/**
 * ATUM Multi-Inventory
 *
 * @link              https://www.stockmanagementlabs.com/
 * @since             1.0.0
 * @package           AtumMultiInventory
 *
 * @wordpress-plugin
 * Plugin Name:          ATUM Multi-Inventory
 * Plugin URI:           https://www.stockmanagementlabs.com/addons/atum-multi-inventory
 * Description:          Create as many inventories per product as you wish!
 * Version:              1.5.6
 * Author:               Stock Management Labs™
 * Author URI:           https://www.stockmanagementlabs.com/
 * Contributors:         Be Rebel Studio - https://berebel.io
 * Requires at least:    5.0
 * Tested up to:         5.7.1
 * Requires PHP:         5.6
 * WC requires at least: 3.6.0
 * WC tested up to:      5.2.2
 * Text Domain:          atum-multi-inventory
 * Domain Path:          /languages
 * License:              ©2021 Stock Management Labs™
 */

defined( 'ABSPATH' ) || die;

if ( ! defined( 'ATUM_MULTINV_VERSION' ) ) {
	define( 'ATUM_MULTINV_VERSION', '1.5.6' );
}

if ( ! defined( 'ATUM_MULTINV_URL' ) ) {
	define( 'ATUM_MULTINV_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'ATUM_MULTINV_PATH' ) ) {
	define( 'ATUM_MULTINV_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ATUM_MULTINV_TEXT_DOMAIN' ) ) {
	define( 'ATUM_MULTINV_TEXT_DOMAIN', 'atum-multi-inventory' );
}

if ( ! defined( 'ATUM_MULTINV_BASENAME' ) ) {
	define( 'ATUM_MULTINV_BASENAME', plugin_basename( __FILE__ ) );
}

class AtumMultiInventoryAddon {

	/**
	 * The required minimum version of ATUM
	 */
	const MINIMUM_ATUM_VERSION = '1.8.9';

	/**
	 * The required minimum version of PHP
	 */
	const MINIMUM_PHP_VERSION = '5.6';

	/**
	 * The required minimum version of Woocommerce
	 */
	const MINIMUM_WC_VERSION = '3.6.0';

	/**
	 * The required minimum version of WordPress
	 */
	const MINIMUM_WP_VERSION = '5.0';

	/**
	 * The add-on name
	 */
	const ADDON_NAME = 'Multi-Inventory';


	/**
	 * AtumMultiInventoryAddon constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Activation tasks.
		register_activation_hook( __FILE__, array( __CLASS__, 'activated' ) );

		// Uninstallation tasks.
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

		// Check the PHP AND ATUM minimum version required for ATUM Multi-Inventory.
		add_action( 'plugins_loaded', array( $this, 'check_dependencies_minimum_versions' ) );

		// Registrate the add-on to ATUM.
		add_filter( 'atum/addons/setup', array( $this, 'register' ) );

	}

	/**
	 * Register the add-on to ATUM
	 *
	 * @since 1.0.0
	 *
	 * @param array $installed  The array of installed add-ons.
	 *
	 * @return array
	 */
	public function register( $installed ) {

		$installed['multi_inventory'] = array(
			'name'        => self::ADDON_NAME,
			'description' => __( 'Create as many inventories per product as you wish!', ATUM_MULTINV_TEXT_DOMAIN ),
			'addon_url'   => 'https://www.stockmanagementlabs.com/addons/atum-multi-inventory/',
			'version'     => ATUM_MULTINV_VERSION,
			'basename'    => ATUM_MULTINV_BASENAME,
			'bootstrap'   => array( $this, 'bootstrap' ),
		);

		return $installed;

	}

	/**
	 * Bootstrap the add-on
	 *
	 * @since 1.0.0
	 */
	public function bootstrap() {

		$bootstrapped = FALSE;

		// Check minimum versions for install ATUM Product Levels.
		if ( $this->check_minimum_versions() ) {

			$bootstrapped = TRUE;

			/* @noinspection PhpIncludeInspection */
			require_once ATUM_MULTINV_PATH . 'vendor/autoload.php';
			\AtumMultiInventory\MultiInventory::get_instance();

		}

		return $bootstrapped;

	}

	/**
	 * Just trigger a hook that other add-ons can use to do some actions when Mi is enabled
	 *
	 * @since 1.2.2.1
	 */
	public static function activated() {
		do_action( 'atum/multi_inventory/activated', ATUM_MULTINV_VERSION );
	}

	/**
	 * Uninstallation checks (this will run only once at plugin uninstallation)
	 *
	 * @since 1.0.0
	 */
	public static function uninstall() {

		global $wpdb;

		$settings = get_option( 'atum_settings' );

		if ( $settings && ! empty( $settings ) && 'yes' === $settings['delete_data'] ) {

			/* @noinspection PhpIncludeInspection */
			require_once ATUM_MULTINV_PATH . 'vendor/autoload.php';
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$inventories_table         = $wpdb->prefix . 'atum_inventories';
			$inventories_meta_table    = $wpdb->prefix . 'atum_inventory_meta';
			$inventory_locations_table = $wpdb->prefix . 'atum_inventory_locations';
			$inventory_regions_table   = $wpdb->prefix . 'atum_inventory_regions';
			$inventory_orders_table    = $wpdb->prefix . 'atum_inventory_orders';

			// Delete the ATUM MI tables in db.
			$wpdb->query( "DROP TABLE IF EXISTS $inventories_table" ); // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->query( "DROP TABLE IF EXISTS $inventories_meta_table" ); // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->query( "DROP TABLE IF EXISTS $inventory_locations_table" ); // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->query( "DROP TABLE IF EXISTS $inventory_regions_table" ); // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->query( "DROP TABLE IF EXISTS $inventory_orders_table" ); // phpcs:ignore WordPress.DB.PreparedSQL

			// Delete all the post meta related to ATUM MI.
			/* @deprecated These props are not meta since version 1.3.4 */
			$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_multi_inventory' OR  meta_key LIKE '_inventory_sorting_mode' OR  meta_key LIKE '_expirable_inventories' OR  meta_key LIKE '_price_per_inventory' OR  meta_key LIKE '_inventory_iteration'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// Delete the ATUM MI options.
			$options_to_delete = [ 'atum_multi_inventory_version', 'widget_atum-mi-user-destination-form-widget' ];
			foreach ( $options_to_delete as $option ) {
				delete_option( $option );
			}

		}

	}

	/**
	 * Check minimum versions for install ATUM Multi-Inventory.
	 *
	 * @since 1.0.5
	 *
	 * @return bool
	 */
	public function check_minimum_versions() {

		global $wp_version;

		$minimum_version = TRUE;
		$message         = '';

		// Check ATUM minimum version.
		if ( version_compare( ATUM_VERSION, self::MINIMUM_ATUM_VERSION, '<' ) ) {

			/* translators: The ATUM version */
			$message         = sprintf( __( 'The Multi-Inventory add-on requires at least the %s version of ATUM. Please update it.', ATUM_MULTINV_TEXT_DOMAIN ), self::MINIMUM_ATUM_VERSION );
			$minimum_version = FALSE;

		}
		// Check the WordPress minimum version required for ATUM Product Levels.
		elseif ( version_compare( $wp_version, self::MINIMUM_WP_VERSION, '<' ) ) {

			/* translators: First one is the WP minimum version and second is the updates page URL  */
			$message         = sprintf( __( "The Multi-Inventory add-on requires the WordPress %1\$s version or greater. Please <a href='%2\$s'>update it</a>.", ATUM_MULTINV_TEXT_DOMAIN ), self::MINIMUM_WP_VERSION, esc_url( self_admin_url( 'update-core.php?force-check=1' ) ) );
			$minimum_version = FALSE;

		}
		// Check that Woocommerce is activated.
		elseif ( ! function_exists( 'wc' ) ) {

			$message         = __( 'The Multi-Inventory requires WooCommerce to be activated.', ATUM_MULTINV_TEXT_DOMAIN );
			$minimum_version = FALSE;

		}
		// Check the Woocommerce minimum version required for Multi-Inventory.
		elseif ( version_compare( wc()->version, self::MINIMUM_WC_VERSION, '<' ) ) {

			/* translators: First one is the WooCommerce minimium version and second is the updates page URL */
			$message         = sprintf( __( "The Multi-Inventory add-on requires the WooCommerce %1\$s version or greater. Please <a href='%2\$s'>update it</a>.", ATUM_MULTINV_TEXT_DOMAIN ), self::MINIMUM_WC_VERSION, esc_url( self_admin_url( 'update-core.php?force-check=1' ) ) );
			$minimum_version = FALSE;

		}

		if ( ! $minimum_version ) {
			\Atum\Components\AtumAdminNotices::add_notice( $message, 'error' );
		}

		return $minimum_version;

	}

	/**
	 * Check PHP minimum version and if ATUM is install, for install ATUM Multi-Inventory.
	 *
	 * @since 1.0.5
	 */
	public function check_dependencies_minimum_versions() {

		$minimum_version = TRUE;
		$message         = '';

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed = get_plugins();

		// Check PHP minimum version.
		if ( version_compare( phpversion(), self::MINIMUM_PHP_VERSION, '<' ) ) {

			/* translators: The minimum required PHP version */
			$message         = sprintf( __( 'ATUM Multi-Inventory requires PHP version %s or greater. Please, update or contact your hosting provider.', ATUM_MULTINV_TEXT_DOMAIN ), self::MINIMUM_PHP_VERSION );
			$minimum_version = FALSE;

		}
		// Check if ATUM is installed.
		elseif ( ! isset( $installed['atum-stock-manager-for-woocommerce/atum-stock-manager-for-woocommerce.php'] ) ) {

			/* translators: The URL to the plugins installation page */
			$message         = sprintf( __( "The Multi-Inventory add-on requires the ATUM Inventory Management for WooCommerce plugin. Please <a href='%s'>install it</a>.", ATUM_MULTINV_TEXT_DOMAIN ), admin_url( 'plugin-install.php?s=atum&tab=search&type=term' ) );
			$minimum_version = FALSE;

		}
		// Check if ATUM is active.
		elseif ( ! defined( 'ATUM_VERSION' ) ) {

			/* translators: The URL to the plugins page */
			$message         = sprintf( __( "The Multi-Inventory add-on requires the ATUM Inventory Management for WooCommerce plugin. Please enable it from <a href='%s'>plugins page</a>.", ATUM_MULTINV_TEXT_DOMAIN ), admin_url( 'plugins.php' ) );
			$minimum_version = FALSE;

		}

		if ( ! $minimum_version ) {

			// We cannot use the AtumAdminNotices here because ATUM could be not enabled.
			add_action( 'admin_notices', function () use ( $message ) {
				?>
				<div class="atum-notice notice notice-error">
					<p>
						<strong>
							<?php echo wp_kses_post( $message ); ?>
						</strong>
					</p>
				</div>
				<?php
			} );

		}

	}

}

// Instantiate the add-on.
new AtumMultiInventoryAddon();
