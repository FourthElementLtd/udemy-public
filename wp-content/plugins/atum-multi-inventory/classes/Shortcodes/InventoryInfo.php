<?php
/**
 * The shortcode used for displaying Multi-Inventory info in shop products
 *
 * @package         AtumMultiInventory
 * @subpackage      Shortcodes
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.0.0
 */

namespace AtumMultiInventory\Shortcodes;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Inc\Helpers;
use AtumMultiInventory\Models\Inventory;


class InventoryInfo {

	/**
	 * The singleton instance holder
	 *
	 * @var InventoryInfo
	 */
	private static $instance;

	/**
	 * The shortcode name
	 *
	 * @var string
	 */
	private $name = ATUM_PREFIX . 'mi_product_info';

	/**
	 * The sanitized data attribute
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * The sanitized with_labels attribute
	 *
	 * @var bool
	 */
	private $with_labels;

	/**
	 * The sanitized class attribute
	 *
	 * @var string
	 */
	private $class;

	/**
	 * The sanitized no_info_message attribute
	 *
	 * @var string
	 */
	private $no_info_message;

	/**
	 * The sanitized date_format attribute
	 *
	 * @var string
	 */
	private $date_format;


	/**
	 * InventoryInfo singleton constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Add the shortcode to WP.
		add_shortcode( $this->name, array( $this, 'render' ) );

	}

	/**
	 * Render the shortcode
	 *
	 * @since 1.0.0
	 *
	 * @param array  $atts    The shortcode attributes.
	 * @param string $content The content between the shortcodes tags.
	 *
	 * @return string The HTML content for this shortcode.
	 *
	 * @throws \Exception
	 */
	public function render( $atts, $content ) {

		/**
		 * Used attributes
		 *
		 * @var int          $product_id      If passed will display the data for the specified product, if not will try to get the displayed product.
		 * @var string|array $data            A comma-separated list (or array) of data elements to display (defaults to all).
		 * @var string       $with_labels     Whether to show the labels for each data row.
		 * @var string       $no_info_message The message that will be shown when the product has no info available.
		 * @var string       $date_format     The format used for dates. Defaults to configured WP's format.
		 * @var string       $class           Any CSS class to be added to the data block
		 */
		extract( shortcode_atts( array(
			'product_id'      => '',
			'data'            => 'inventory_date,bbe_date,region,location,lot',
			'with_labels'     => 'yes',
			'no_info_message' => __( 'This product has no inventory info available.', ATUM_MULTINV_TEXT_DOMAIN ),
			'date_format'     => get_option( 'date_format' ),
			'class'           => '',
		), $atts, $this->name ) );

		if ( ! $product_id ) {
			$product_id = get_the_ID();
		}
		
		$product_id = apply_filters( 'atum/multi_inventory/product_id', $product_id );
		$product    = wc_get_product( $product_id );

		// This shortcode can only be used for products with Multi-Inventory enabled.
		if ( 'yes' !== Helpers::get_product_multi_inventory_status( $product ) || ! Helpers::is_product_multi_inventory_compatible( $product ) ) {
			return $this->no_info_message( $no_info_message );
		}

		if ( ! is_array( $data ) ) {
			$data = array_map( 'esc_attr', array_map( 'trim', explode( ',', $data ) ) );
		}

		// Initialize props.
		$this->data            = $data;
		$this->with_labels     = 'yes' === $with_labels;
		$this->class           = sanitize_html_class( $class );
		$this->no_info_message = $no_info_message;
		$this->date_format     = esc_attr( $date_format );

		// Non-variable products.
		if ( ! $product instanceof \WC_Product_Variable ) {
			return $this->get_view( $product_id );
		}
		// Variable products.
		else {

			$variations = $product->get_children();

			if ( empty( $variations ) ) {
				return $this->no_info_message( $no_info_message );
			}

			$wrapper_id = uniqid( ATUM_PREFIX . 'mi' );
			$output     = '<div id="' . $wrapper_id . '">';

			foreach ( $variations as $variation_id ) {
				$output .= '<div class="mi-variation-info" style="display: none" data-id="' . $variation_id . '">' . $this->get_view( $variation_id ) . '</div>';
			}

			$output .= $this->variations_script( $wrapper_id ) . '</div>';

			return $output;

		}

	}

	/**
	 * Extract data from the specified inventory
	 *
	 * @since 1.0.0
	 *
	 * @param Inventory $inventory
	 *
	 * @return array
	 */
	private function extract_data( $inventory ) {

		// Defaults.
		$filtered_data = array(
			'inventory_date' => '',
			'bbe_date'       => '',
			'locations'      => array(),
			'regions'        => array(),
			'lot'            => '',
		);

		// Get the inventory date.
		if ( in_array( 'inventory_date', $this->data, TRUE ) ) {
			$filtered_data['inventory_date'] = $inventory->inventory_date;
		}

		// Get the BBE date.
		if ( in_array( 'bbe_date', $this->data, TRUE ) ) {
			$filtered_data['bbe_date'] = $inventory->bbe_date;
		}

		// Get the location names.
		$cur_locations = $inventory->location;
		if ( in_array( 'location', $this->data, TRUE ) && ! empty( $cur_locations ) ) {
			$filtered_data['locations'] = $cur_locations;
		}

		// Get the region names.
		$cur_regions = $inventory->region;
		if ( in_array( 'region', $this->data, TRUE ) && ! empty( $cur_regions ) ) {
			$filtered_data['regions'] = $cur_regions;
		}

		// Get the LOT number.
		if ( in_array( 'lot', $this->data, TRUE ) ) {
			$filtered_data['lot'] = $inventory->lot;
		}

		return $filtered_data;

	}

	/**
	 * Get the shortcode output for the specified product
	 *
	 * @since 1.0.0
	 *
	 * @param int $product_id
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	private function get_view( $product_id ) {

		$inventories = Helpers::get_product_inventories_sorted( $product_id );

		if ( empty( $inventories ) ) {
			return $this->no_info_message( $this->no_info_message );
		}

		$filtered_data = array();

		// Get the first inventory with stock and use its data.
		foreach ( $inventories as $inventory ) {

			if ( ! $inventory->is_sellable() ) {
				continue;
			}

			if ( in_array( $inventory->stock_status, [ 'instock', 'onbackorder' ], TRUE ) ) {
				$filtered_data = $this->extract_data( $inventory );
				break;
			}
		}

		$args = array_merge(
			array(
				'data'        => $this->data,
				'with_labels' => $this->with_labels,
				'date_format' => $this->date_format,
				'class'       => $this->class,
			),
			$filtered_data
		);

		return AtumHelpers::load_view_to_string( ATUM_MULTINV_PATH . 'views/shortcodes/inventory-info', $args );

	}

	/**
	 * Prepare the No Info message
	 *
	 * @since 1.0.0
	 *
	 * @param string $message
	 *
	 * @return string
	 */
	private function no_info_message( $message ) {

		return '<div class="atum-mi-no-info">' . $message . '</div>';
	}

	/**
	 * The inline script used for updating the shortcode content when changing the variation
	 *
	 * @since 1.0.0
	 *
	 * @param string $wrapper_id
	 *
	 * @return string
	 */
	private function variations_script( $wrapper_id ) {

		ob_start();
		// phpcs:disable
		?>
		<script type="text/javascript">
			jQuery(function ($) {

				var $variationsForm        = $('form.variations_form'),
				    $variationsSelect      = $variationsForm.find('select'),
				    productVariations      = $variationsForm.data('product_variations'),
				    $variationsInfoWrapper = $('#<?php echo esc_attr( $wrapper_id ) ?>');

				$variationsForm.on('woocommerce_update_variation_values', function () {

					var selected = $variationsSelect.val();

					if (selected === '') {
						$variationsInfoWrapper.find('.mi-variation-info').hide();
						return;
					}

					$.each(productVariations, function (index, elem) {

						if (typeof elem.attributes !== 'undefined' && $.inArray(selected, Object.values(elem.attributes)) > -1) {
							$variationsInfoWrapper.find('.mi-variation-info:visible').hide();
							$variationsInfoWrapper.find('.mi-variation-info[data-id="' + elem.variation_id + '"]').show();
						}

					});

				});

			});
		</script>
		<?php
		// phpcs:enable

		return ob_get_clean();

	}


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_VERSION ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_VERSION ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return InventoryInfo instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
