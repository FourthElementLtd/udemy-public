<?php
/**
 * WPML multilingual integration class for Multi-Inventory
 * As MI works almost all the time with Orders, and they only store original products, it won't need WPML adapt except when saving metaboxes
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

use Atum\Integrations\Wpml as AtumWpml;
use AtumMultiInventory\Inc\Hooks;
use AtumMultiInventory\Inc\ProductProps;
use AtumMultiInventory\Models\Inventory;
use AtumMultiInventory\MultiInventory;


class Wpml extends AtumWpml {

	/**
	 * Whether had the action assigned a function at the beginning
	 *
	 * @since 1.2.0
	 *
	 * @var bool
	 */
	private $had_action = FALSE;

	/**
	 * Modifies the sync stcik filter hook's name if needed (if WCML version > 4.5.0)
	 *
	 * @since 1.2.1.7
	 *
	 * @var string
	 */
	private $sync_stock_filter_mod;

	/**
	 * The ids of the product currently been saved and its translations.
	 *
	 * @since 1.3.4
	 *
	 * @var array
	 */
	private $current_saving_product_translations;

	/**
	 * Register the WPML Atum Multi-Inventory hooks
	 *
	 * @since 1.0.0
	 */
	public function register_hooks() {

		$this->sync_stock_filter_mod = defined( 'WCML_VERSION' ) && version_compare( WCML_VERSION, '4.5.0', '>' ) ? '_hook' : '';

		// Get the original product id for a product.
		add_filter( 'atum/multi_inventory/product_id', array( $this, 'get_original_product_id' ) );
		
		// Prevent WPML to change the product's stock.
		add_action( 'atum/multi_inventory/before_product_set_stock', array( $this, 'remove_product_sync_stock_filter' ), 10, 2 );
		add_action( 'atum/multi_inventory/product_set_stock', array( $this, 'add_product_sync_stock_filter' ), 10, 2 );
		add_action( 'atum/multi_inventory/before_variation_set_stock', array( $this, 'remove_variation_sync_stock_filter' ), 10, 2 );
		add_action( 'atum/multi_inventory/variation_set_stock', array( $this, 'add_variation_sync_stock_filter' ), 10, 2 );

		// Disable get_stock_quantity when WC is updating the stock.
		add_action( 'woocommerce_product_set_stock', array( $this, 'remove_get_stock_quantity' ), 9 );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'remove_get_stock_quantity' ), 9 );

		// Add translations to the product loops MI queries.
		add_filter( 'atum/multi_inventory/product_loop_mi_restrictions', array( $this, 'change_loop_mi_restrictions_query' ), 10, 2 );
		add_filter( 'atum/multi_inventory/product_loop_parent_mi_restrictions', array( $this, 'change_loop_parent_mi_restrictions_query' ), 10, 2 );

		if ( is_admin() ) {
			$this->register_admin_hooks();
		}
	}

	/**
	 * Register the WPML Atum Multi-Inventory admin side hooks
	 *
	 * @since 1.2.1
	 */
	public function register_admin_hooks() {

		// replace the multi Inventory UI.
		add_filter( 'atum/multi_inventory/can_add_mi_ui', array( $this, 'maybe_remove_mi_ui' ), 10, 2 );

		// Allow/disallow mi metabox data saving.
		add_filter( 'atum/multi_inventory/can_save_mi_metabox_data', array( $this, 'maybe_save_mi' ), 10, 2 );

		// Hide product's WC inventory fields in translations to prevent misunderstandings.
		add_action( 'atum/multi_inventory/localized_vars', array( $this, 'add_mi_localize_hiding_variable' ) );
	}

	/**
	 * Remove WPML's original sync stock filter and sync stock
	 *
	 * @since 1.1.1.1
	 *
	 * @param \WC_Product $product
	 * @param Inventory   $inventory
	 */
	public function remove_product_sync_stock_filter( $product, $inventory ) {

		$this->had_action = has_action( 'woocommerce_product_set_stock', array( $this->wpml->sync_product_data, "sync_product_stock{$this->sync_stock_filter_mod}" ) );

		if ( $this->had_action ) {
			remove_action( 'woocommerce_product_set_stock', array( $this->wpml->sync_product_data, "sync_product_stock{$this->sync_stock_filter_mod}" ) );
		}

		$this->sync_translations_main_inventory_stock( $product, $inventory );
	}
	
	/**
	 * Sync translations main inventory stock (the other ones have no translation)
	 *
	 * @since 1.1.1.1
	 *
	 * @param \WC_Product $product
	 * @param Inventory   $inventory
	 */
	public function sync_translations_main_inventory_stock( $product, $inventory ) {
		
		if ( $inventory->is_main() ) {
			
			$product_id = $product->get_id();
			/* @noinspection PhpUndefinedMethodInspection */
			$translations = self::$sitepress->get_element_translations( $product_id );
			
			foreach ( $translations as $translation ) {

				if ( $product_id !== (int) $translation->element_id ) {
					$translation_product = wc_get_product( $translation->element_id );
					wc_update_product_stock( $translation_product, $inventory->stock_quantity );
				}

			}

		}

	}

	/**
	 * Add WPML's original sync stock filter
	 *
	 * @since 1.1.1.1
	 *
	 * @param \WC_Product $product
	 * @param Inventory   $inventory
	 */
	public function add_product_sync_stock_filter( $product, $inventory ) {

		if ( $this->had_action ) {
			add_action( 'woocommerce_product_set_stock', array( $this->wpml->sync_product_data, "sync_product_stock{$this->sync_stock_filter_mod}" ) );
		}

	}
	
	/**
	 * Remove WPML's original sync stock filter and sync stock
	 *
	 * @since 1.1.1.1
	 *
	 * @param \WC_Product $product
	 * @param Inventory   $inventory
	 */
	public function remove_variation_sync_stock_filter( $product, $inventory ) {

		$this->had_action = has_action( 'woocommerce_variation_set_stock', array( $this->wpml->sync_product_data, "sync_product_stock{$this->sync_stock_filter_mod}" ) );

		if ( $this->had_action ) {
			remove_action( 'woocommerce_variation_set_stock', array( $this->wpml->sync_product_data, "sync_product_stock{$this->sync_stock_filter_mod}" ) );
		}

		$this->sync_translations_main_inventory_stock( $product, $inventory );
	}
	
	/**
	 * Add WPML's original sync stock filter
	 *
	 * @since 1.1.1.1
	 *
	 * @param \WC_Product $product
	 * @param Inventory   $inventory
	 */
	public function add_variation_sync_stock_filter( $product, $inventory ) {

		if ( $this->had_action ) {
			add_action( 'woocommerce_variation_set_stock', array( $this->wpml->sync_product_data, "sync_product_stock{$this->sync_stock_filter_mod}" ) );
		}
	}

	/**
	 * Removes the get_stock_quantity filter when WC is saving the product (== saving the Main Inventory) only if for the saving product.
	 * As the product's stock may depend on another product's stock, the filter cannot be fully removed.
	 *
	 * @since 1.2.0
	 *
	 * @param \WC_Product $product
	 */
	public function remove_get_stock_quantity( $product ) {

		remove_filter( 'woocommerce_product_get_stock_quantity', array( ProductProps::get_instance(), 'get_stock_quantity' ), PHP_INT_MAX );
		remove_filter( 'woocommerce_product_variation_get_stock_quantity', array( ProductProps::get_instance(), 'get_stock_quantity' ), PHP_INT_MAX );

		$this->current_saving_product_translations = $this->get_product_translations_ids( $product->get_id() );

		add_filter( 'woocommerce_product_get_stock_quantity', array( $this, 'get_stock_quantity' ), PHP_INT_MAX, 2 );
		add_filter( 'woocommerce_product_variation_get_stock_quantity', array( $this, 'get_stock_quantity' ), PHP_INT_MAX, 2 );

	}

	/**
	 * Hack the WC's get prop method if not saving the current product.
	 *
	 * @since 1.3.4
	 *
	 * @param float       $stock
	 * @param \WC_Product $product_data
	 *
	 * @return float
	 */
	public function get_stock_quantity( $stock, $product_data ) {

		if ( ! in_array( $product_data->get_id(), $this->current_saving_product_translations ) ) {
			return ProductProps::get_instance()->get_stock_quantity( $stock, $product_data );
		}

		return $stock;

	}

	/**
	 * Replace the Multi Inventory content if current product is a translation
	 *
	 * @since 1.2.1
	 *
	 * @param bool $show
	 * @param int  $product_id
	 *
	 * @return bool
	 */
	public function maybe_remove_mi_ui( $show, $product_id ) {

		$product_id = (int) $product_id;
		$product    = wc_get_product( $product_id );

		if ( $product instanceof \WC_Product && self::get_original_product_id( $product_id ) !== $product_id && ! in_array( $product->get_type(), MultiInventory::get_compatible_parent_types() ) ) {

			$show = FALSE;

			?>
			<div class="options-group translated-mi-product multi-inventory-panel">
				<div class="alert alert-warning">
					<h3>
						<i class="atum-icon atmi-warning"></i>
						<?php esc_html_e( 'Multi Inventories can not be edited within translations', ATUM_MULTINV_TEXT_DOMAIN ) ?>
					</h3>

					<p><?php esc_html_e( 'You must edit original product instead.', ATUM_MULTINV_TEXT_DOMAIN ) ?></p>
				</div>
			</div>
			<?php
		}

		return $show;

	}

	/**
	 * Prevent save metabox data if current product is a translation
	 *
	 * @since 1.2.1
	 *
	 * @param bool $save
	 * @param int  $product_id
	 *
	 * @return bool
	 */
	public function maybe_save_mi( $save, $product_id ) {

		$product_id = (int) $product_id;

		if ( self::get_original_product_id( $product_id ) !== $product_id ) {
			$save = FALSE;
		}

		return $save;

	}

	/**
	 * Add param to localize MI variables to hide the WC inventory fields
	 *
	 * @since 1.3.4
	 *        
	 * @param array $vars
	 *                   
	 * @return array
	 */
	public function add_mi_localize_hiding_variable( $vars ) {
		
		global $pagenow;

		$is_edit_product     = 'post.php' === $pagenow && isset( $_GET['post'] ) && 'product' === get_post_type( $_GET['post'] );
		$is_original_product = isset( $_GET['post'] ) && ! is_array( $_GET['post'] ) && $this->wpml->products->is_original_product( $_GET['post'] );
		$is_new_product      = 'post-new.php' === $pagenow && isset( $_GET['source_lang'] ) && isset( $_GET['post_type'] ) && 'product' === $_GET['post_type'];

		if ( ( $is_edit_product && ! $is_original_product ) || $is_new_product ) {

			$vars['isTranslation'] = TRUE;
		}
		
		return $vars;

	}

	/**
	 * Add translation relations to the MI restrictions queries used for the frontend products loop.
	 *
	 * @since 1.4.4
	 *
	 * @param string $query
	 * @param string $common_join
	 *
	 * @return string
	 */
	public function change_loop_mi_restrictions_query( $query, $common_join ) {

		global $wpdb;

		$inv_table = $wpdb->prefix . Inventory::INVENTORIES_TABLE;

		return "SELECT DISTINCT prlg.ID FROM $inv_table ati
			LEFT JOIN $wpdb->posts pr ON ati.product_id = pr.ID 
			LEFT JOIN {$wpdb->prefix}icl_translations tr ON pr.ID = tr.element_id AND CONCAT('post_', pr.post_type) = tr.element_type 
			LEFT JOIN {$wpdb->prefix}icl_translations trlg ON tr.trid = trlg.trid AND trlg.language_code = '{$this->current_language}'
			LEFT JOIN $wpdb->posts prlg ON trlg.element_id = prlg.ID
			$common_join ";

	}

	/**
	 * Add translation relations to the MI restrictions queries used for the frontend products loop.
	 *
	 * @since 1.4.4
	 *
	 * @param string $parent_query
	 * @param string $common_join
	 *
	 * @return string
	 */
	public function change_loop_parent_mi_restrictions_query( $parent_query, $common_join ) {

		global $wpdb;

		$inv_table = $wpdb->prefix . Inventory::INVENTORIES_TABLE;

		return "SELECT DISTINCT prlg.ID FROM $inv_table ati
			LEFT JOIN $wpdb->posts pr ON ati.product_id = pr.ID 
			LEFT JOIN {$wpdb->prefix}icl_translations tr ON pr.ID = tr.element_id AND CONCAT('post_', pr.post_type) = tr.element_type 
			LEFT JOIN {$wpdb->prefix}icl_translations trlg ON tr.trid = trlg.trid AND trlg.language_code = '{$this->current_language}'
			LEFT JOIN $wpdb->posts prlg ON trlg.element_id = prlg.ID
			$common_join ";

	}

}
