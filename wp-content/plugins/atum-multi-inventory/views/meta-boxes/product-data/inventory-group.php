<?php
/**
 * View for the Inventories' UI within the WC Product Data meta box
 *
 * @since 1.0.0
 * @see woocommerce/includes/admin/meta-boxes/views/html-product-data-inventory.php
 *
 * @var \AtumMultiInventory\Models\Inventory $inventory
 * @var bool $is_variation
 * @var int  $loop
 */

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Inc\Helpers;
use Atum\MetaBoxes\ProductDataMetaBoxes;
use AtumMultiInventory\Inc\Hooks as MiHooks;
use AtumMultiInventory\Models\MainInventory;

// Set the current inventory as global to be able to edit the ATUM extra fields' atts with filters.
global $atum_mi_inventory;
$atum_mi_inventory = $inventory;

$id_for_name    = ( ! is_numeric( $inventory->id ) && strpos( $inventory->id, 'new_' ) !== 0 && ( 'main' !== $inventory->id ) ) ? "new_{$inventory->id}" : $inventory->id;
$variation_post = $is_variation ? get_post( $inventory->product_id ) : NULL;
$is_expired     = $inventory->is_expired();
$is_main        = $inventory instanceof MainInventory;
$status_class   = $is_expired ? 'outofstock' : $inventory->stock_status;

do_action( 'atum/multi_inventory/before_inventory' );
?>
<div class="inventory-group <?php echo esc_attr( $status_class ) ?><?php if ( $is_expired ) echo ' expired' ?><?php if ( $is_main ) echo ' main' ?><?php if ( 'yes' === $inventory->write_off ) echo ' write-off' ?>" data-id="<?php echo esc_attr( $inventory->id ) ?>">

	<h3 class="inventory-name collapsed">
		<input type="checkbox" name="inventory-group[]" class="inventory-selector">

		<span class="editable" contentEditable="true" title="<?php esc_attr_e( 'Click to edit name', ATUM_MULTINV_TEXT_DOMAIN ) ?>"><?php echo esc_attr( $inventory->name ) ?></span>

		<span class="inventory-status">
			<span class="inventory-stock-status">
				<?php if ( 'yes' === $inventory->write_off ) :
					esc_attr_e( 'Write-Off', ATUM_MULTINV_TEXT_DOMAIN );
				elseif ( 'outofstock' === $inventory->stock_status ) :
					esc_attr_e( 'Out of Stock', ATUM_MULTINV_TEXT_DOMAIN );
				elseif ( 'onbackorder' === $inventory->stock_status ) :
					esc_attr_e( 'On backorder', ATUM_MULTINV_TEXT_DOMAIN );
				else :
					esc_attr_e( 'In Stock', ATUM_MULTINV_TEXT_DOMAIN );
				endif; ?>
			</span>

			<?php
			$stock_class     = $inventory->managing_stock() ? '' : ' hidden';
			$inventory_stock = wc_stock_amount( $inventory->stock_quantity );
			echo wp_kses_post( "<span class='inv-stock-amount$stock_class'>($inventory_stock)</span>" );
			
			?>
		</span>

		<?php if ( $is_main ) : ?>
			<i class="main atum-icon atmi-store atum-tooltip" data-tip="<?php esc_attr_e( 'This is the Main inventory', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></i>
		<?php endif; ?>

		<?php if ( $is_expired ) : ?>
			<i class="expired atum-icon atmi-hourglass atum-tooltip" data-tip="<?php esc_attr_e( 'This inventory has expired', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></i>
		<?php endif; ?>

		<button class="toggle-indicator"></button>
	</h3>

	<div class="inventory-fields" style="display: none">

		<?php require 'inventory-info.php' ?>

		<div class="options_group">
			<?php
			//
			// SKU Field.
			// ----------!
			if ( wc_product_sku_enabled() ) :

				$sku_id   = $is_variation ? "_sku_{$id_for_name}{$loop}" : "_sku_$id_for_name";
				$sku_name = $is_variation ? "atum_mi[$loop][$id_for_name][_sku]" : "atum_mi[$id_for_name][_sku]";

				$sku_params = array(
					'id'          => $sku_id,
					'name'        => $sku_name,
					'value'       => $inventory->sku,
					'label'       => '<abbr title="' . esc_attr__( 'Stock Keeping Unit', ATUM_MULTINV_TEXT_DOMAIN ) . '">' . esc_html__( 'SKU', ATUM_MULTINV_TEXT_DOMAIN ) . '</abbr>',
					'desc_tip'    => TRUE,
					'description' => __( 'SKU refers to a Stock-keeping unit, a unique identifier for each distinct product and service that can be purchased.', ATUM_MULTINV_TEXT_DOMAIN ),
				);

				// Sync the main inventory field with the related WC field.
				if ( $is_main ) :
					$sku_params['custom_attributes']['data-sync'] = $is_variation ? "#variable_sku{$loop}" : '#_sku';
				endif;

				woocommerce_wp_text_input( $sku_params );

			endif;

			do_action( 'woocommerce_product_options_sku', $inventory->id );

			if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) :

				$manage_stock_id   = $is_variation ? "_manage_stock_{$id_for_name}{$loop}" : "_manage_stock_$id_for_name";
				$manage_stock_name = $is_variation ? "atum_mi[$loop][$id_for_name][_manage_stock]" : "atum_mi[$id_for_name][_manage_stock]";

				$hidden_classes = AtumHelpers::get_option_group_hidden_classes( FALSE );
				//
				// Manage Stock Field.
				// -------------------!
				$manage_stock_params = array(
					'id'            => $manage_stock_id,
					'name'          => $manage_stock_name,
					'value'         => $inventory->manage_stock ?: 'no',
					'wrapper_class' => "show_if_simple show_if_variable manage_mi_stock $hidden_classes",
					'label'         => __( 'Manage stock?', ATUM_MULTINV_TEXT_DOMAIN ),
					'description'   => __( 'Enable stock management at inventory level', ATUM_MULTINV_TEXT_DOMAIN ),
				);

				if ( $is_expired && 'yes' === Helpers::get_product_expirable_inventories( $inventory->product_id ) ) :
					$manage_stock_params['custom_attributes']['disabled'] = 'disabled';
					$manage_stock_params['description']                   = $manage_stock_params['description'] . '<br><span class="alert alert-danger"><small>' . __( "The WC's Manage stock was disabled due to inventory expiration", ATUM_MULTINV_TEXT_DOMAIN ) . '</small></span>';
					$manage_stock_params['class']                         = 'checkbox mi_expired';
				endif;

				if ( $is_main ) :
					$manage_stock_params['custom_attributes']['data-sync'] = $is_variation ? '[name="variable_manage_stock[' . $loop . ']"]' : '#_manage_stock';
				endif;

				woocommerce_wp_checkbox( $manage_stock_params );

				do_action( 'woocommerce_product_options_stock', $inventory->id ); ?>

				<?php
				if ( $is_expired || $inventory->expired_stock ) :

					//
					// Expired Stock Field.
					// ---------------------!
					$stock_id   = $is_variation ? "_expired_stock_{$id_for_name}{$loop}" : "_expired_stock_$id_for_name";
					$stock_name = $is_variation ? "atum_mi[$loop][$id_for_name][_expired_stock]" : "atum_mi[$id_for_name][_expired_stock]";

					$expired_params = array(
						'id'                => $stock_id,
						'name'              => $stock_name,
						'value'             => wc_stock_amount( $inventory->expired_stock ),
						'label'             => __( 'Expired stock', ATUM_MULTINV_TEXT_DOMAIN ),
						'desc_tip'          => TRUE,
						'description'       => __( 'Expired stock. This is the expired stock amount for this inventory.', ATUM_MULTINV_TEXT_DOMAIN ),
						'type'              => 'number',
						'custom_attributes' => array(
							'step'     => 'any',
							'disabled' => 'disabled',
						),
						'data_type'         => 'stock',
					);

					if ( $is_main ) :
						$expired_params['custom_attributes']['data-sync'] = $is_variation ? "#variable_expired_stock{$loop}" : '#_expired_stock';
					endif;

					woocommerce_wp_text_input( $expired_params );

				endif; ?>

				<div class="inventory_stock_fields show_if_simple show_if_variable <?php echo esc_attr( $hidden_classes ) ?>">

					<?php
					//
					// Stock Quantity Field.
					// ---------------------!
					$stock_id   = $is_variation ? "_stock_{$id_for_name}{$loop}" : "_stock_$id_for_name";
					$stock_name = $is_variation ? "atum_mi[$loop][$id_for_name][_stock_quantity]" : "atum_mi[$id_for_name][_stock_quantity]";

					$stock_params = array(
						'id'                => $stock_id,
						'name'              => $stock_name,
						'value'             => wc_stock_amount( $inventory->stock_quantity ),
						'label'             => __( 'Stock quantity', ATUM_MULTINV_TEXT_DOMAIN ),
						'desc_tip'          => TRUE,
						'description'       => __( 'Stock quantity. If this is a variable product this value will be used to control stock for all variations, unless you define stock at variation level.', ATUM_MULTINV_TEXT_DOMAIN ),
						'type'              => 'number',
						'custom_attributes' => array(
							'step' => 'any',
						),
						'data_type'         => 'stock',
					);

					if ( $is_main ) :
						$stock_params['custom_attributes']['data-sync'] = $is_variation ? "#variable_stock{$loop}" : '#_stock';
					endif;

					woocommerce_wp_text_input( $stock_params );

					$orig_stock_name = $is_variation ? "atum_mi[$loop][$id_for_name][_original_stock]" : "atum_mi[$id_for_name][_original_stock]"; ?>
					<input type="hidden" name="<?php echo esc_attr( $orig_stock_name ) ?>" value="<?php echo esc_attr( wc_stock_amount( $inventory->stock_quantity ) ) ?>" />

					<?php do_action( 'atum/multi_inventory/after_stock_quantity_field', $inventory, $loop, $id_for_name ) ?>

					<?php
					//
					// Allow Backorders Field.
					// -----------------------!
					$backorders_id   = $is_variation ? "_backorders_{$id_for_name}{$loop}" : "_backorders_$id_for_name";
					$backorders_name = $is_variation ? "atum_mi[$loop][$id_for_name][_backorders]" : "atum_mi[$id_for_name][_backorders]";

					$backorders_params = array(
						'id'            => $backorders_id,
						'name'          => $backorders_name,
						'value'         => $inventory->backorders,
						'wrapper_class' => '_backorders_field backorders_mi',
						'label'         => __( 'Allow backorders?', ATUM_MULTINV_TEXT_DOMAIN ),
						'options'       => wc_get_product_backorder_options(),
						'desc_tip'      => TRUE,
						'description'   => __( 'If managing stock, this controls whether or not backorders are allowed. If enabled, stock quantity can go below 0.', ATUM_MULTINV_TEXT_DOMAIN ),
					);

					if ( $is_main ) :
						$backorders_params['custom_attributes']['data-sync'] = $is_variation ? "#variable_backorders{$loop}" : '#_backorders';
					endif;

					woocommerce_wp_select( $backorders_params );

					// By now these hooks are only used by Product Levels to display its custom fields
					// and we only need them on the Main Inventory.
					if ( ! Addons::is_addon_active( 'product_levels' ) || $is_main ) :

						if ( ! $is_variation ) :
							do_action( 'woocommerce_product_options_stock_fields', $inventory );
						else :
							do_action( 'woocommerce_variation_options_inventory', $loop, array(), $variation_post );
						endif;

					endif;
					?>

				</div>

			<?php endif;

			//
			// Stock Status Field.
			// -------------------!
			$stock_status_id   = $is_variation ? "_stock_status_{$id_for_name}{$loop}" : "_stock_status_$id_for_name";
			$stock_status_name = $is_variation ? "atum_mi[$loop][$id_for_name][_stock_status]" : "atum_mi[$id_for_name][_stock_status]";

			$stock_status_params = array(
				'id'            => $stock_status_id,
				'name'          => $stock_status_name,
				'value'         => $inventory->stock_status,
				'wrapper_class' => 'inventory_stock_status_field hide_if_variable hide_if_external',
				'label'         => __( 'Stock status', ATUM_MULTINV_TEXT_DOMAIN ),
				'options'       => wc_get_product_stock_status_options(),
				'desc_tip'      => TRUE,
				'description'   => __( 'Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.', ATUM_MULTINV_TEXT_DOMAIN ),
			);

			if ( $is_expired ) :
				$stock_status_params['custom_attributes']['disabled'] = 'disabled';
			endif;

			if ( $is_main ) :
				$stock_status_params['custom_attributes']['data-sync'] = $is_variation ? "#variable_stock_status{$loop}" : '#_stock_status';
			endif;

			woocommerce_wp_select( $stock_status_params );

			do_action( 'woocommerce_product_options_stock_status', $inventory->product_id );
			?>
		</div>

		<?php
		//
		// Sold Individually Field.
		// ------------------------!
		if ( ! $is_variation ) : ?>
		<div class="options_group show_if_simple show_if_variable <?php echo esc_attr( $hidden_classes ) ?>">
			<?php
			$sold_indiv_id   = $is_variation ? "_sold_individually_{$id_for_name}{$loop}" : "_sold_individually_$id_for_name";
			$sold_indiv_name = $is_variation ? "atum_mi[$loop][$id_for_name][_sold_individually]" : "atum_mi[$id_for_name][_sold_individually]";

			$sold_individually_params = array(
				'id'            => $sold_indiv_id,
				'name'          => $sold_indiv_name,
				'value'         => $inventory->sold_individually ?: 'no',
				'wrapper_class' => "_sold_individually_field show_if_simple show_if_variable $hidden_classes",
				'label'         => __( 'Sold individually', ATUM_MULTINV_TEXT_DOMAIN ),
				'description'   => __( 'Enable this to only allow one of this item to be bought in a single order', ATUM_MULTINV_TEXT_DOMAIN ),
			);

			if ( $is_main ) :
				$sold_individually_params['custom_attributes']['data-sync'] = '#_sold_individually';
			endif;

			woocommerce_wp_checkbox( $sold_individually_params );

			do_action( 'woocommerce_product_options_sold_individually', $inventory->product_id );
			?>
		</div>
		<?php endif;

		// Add the ATUM's custom fields.
		// NOTE: We cannot run the action 'woocommerce_product_options_inventory_product_data' again as would cause an infinite loop.
		$atum_product_meta_boxes = ProductDataMetaBoxes::get_instance();
		$mi_hooks                = MiHooks::get_instance();

		// Add filters to edit the ATUM's custom fields.
		add_filter( 'atum/load_view_args/meta-boxes/product-data/supplier-fields', array( $mi_hooks, 'supplier_fields_view_args' ) );
		add_filter( 'atum/load_view_args/meta-boxes/product-data/out-stock-threshold-field', array( $mi_hooks, 'out_stock_threshold_field_view_args' ) );
		add_filter( 'atum/load_view_args/meta-boxes/product-data/purchase-price-field', array( $mi_hooks, 'purchase_price_field_view_args' ) ); ?>

		<?php if ( $is_main ) :
			add_filter( 'atum/views/meta_boxes/supplier_fields/supplier_extra_atts', array( $mi_hooks, 'supplier_field_extra_atts' ), 10, 3 );
			add_filter( 'atum/views/meta_boxes/supplier_fields/supplier_sku_extra_atts', array( $mi_hooks, 'supplier_sku_field_extra_atts' ), 10, 3 );
			add_filter( 'atum/views/meta_boxes/out_stock_threshold_field_extra_atts', array( $mi_hooks, 'out_stock_threshold_field_extra_atts' ), 10, 3 );
			add_filter( 'atum/views/meta_boxes/purchase_price_field_extra_atts', array( $mi_hooks, 'purchase_price_field_extra_atts' ), 10, 3 );
		endif; ?>

		<div class="options_group">
			<?php
			//
			// Out of Stock Threshold Field.
			// -----------------------------!
			if ( $is_variation ) :
				$atum_product_meta_boxes->add_out_stock_threshold_field( $loop, array(), $variation_post );
			else :
				$atum_product_meta_boxes->add_out_stock_threshold_field();
			endif; ?>
		</div>

		<div class="options_group">
			<?php
			//
			// Supplier Fields.
			// ----------------!
			if ( $is_variation ) :
				$atum_product_meta_boxes->add_product_supplier_fields( $loop, array(), $variation_post );
			else :
				$atum_product_meta_boxes->add_product_supplier_fields();
			endif; ?>
		</div>

		<?php

		$show_price = TRUE;
		if ( class_exists( '\WC_Subscriptions' ) ) :
			$product      = AtumHelpers::get_atum_product( $inventory->product_id );
			$product_type = $product->get_type();

			if ( 'variation' === $product->get_type() ) :
				$product      = AtumHelpers::get_atum_product( $product->get_parent_id() );
				$product_type = $product->get_type();
			endif;

			if ( in_array( $product_type, [ 'variable-subscription', 'subscription' ] ) ) :
				$show_price = FALSE;
			endif;
		endif;

		if ( $show_price ) : ?>
		<div class="options_group inventory-pricing">
			<?php
			//
			// Regular Price Field.
			// --------------------!
			$regular_price_id   = $is_variation ? "_regular_price_{$id_for_name}{$loop}" : "_regular_price_$id_for_name";
			$regular_price_name = $is_variation ? "atum_mi[$loop][$id_for_name][_regular_price]" : "atum_mi[$id_for_name][_regular_price]";

			$regular_price_params = array(
				'id'        => $regular_price_id,
				'name'      => $regular_price_name,
				'value'     => $inventory->regular_price,
				'label'     => __( 'Regular price', ATUM_MULTINV_TEXT_DOMAIN ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'data_type' => 'price',
			);

			if ( $is_main ) :
				$regular_price_params['custom_attributes']['data-sync'] = $is_variation ? "#variable_regular_price_{$loop}" : '#_regular_price';
			endif;

			woocommerce_wp_text_input( $regular_price_params );

			//
			// Sale Price Field.
			// -----------------!
			$sale_price_id   = $is_variation ? "_sale_price_{$id_for_name}{$loop}" : "_sale_price_$id_for_name";
			$sale_price_name = $is_variation ? "atum_mi[$loop][$id_for_name][_sale_price]" : "atum_mi[$id_for_name][_sale_price]";

			$sale_price_params = array(
				'id'          => $sale_price_id,
				'name'        => $sale_price_name,
				'value'       => $inventory->sale_price,
				'data_type'   => 'price',
				'label'       => __( 'Sale price', ATUM_MULTINV_TEXT_DOMAIN ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'description' => '<a href="#" class="sale_schedule">' . __( 'Schedule', ATUM_MULTINV_TEXT_DOMAIN ) . '</a>',
			);

			if ( $is_main ) :
				$sale_price_params['custom_attributes']['data-sync'] = $is_variation ? "#variable_sale_price{$loop}" : '#_sale_price';
			endif;

			woocommerce_wp_text_input( $sale_price_params );

			//
			// Sale Price Dates Fields.
			// ------------------------!
			$sale_price_dates_from_id   = $is_variation ? "_date_on_sale_from_{$id_for_name}{$loop}" : "_date_on_sale_from_$id_for_name";
			$sale_price_dates_from_name = $is_variation ? "atum_mi[$loop][$id_for_name][_date_on_sale_from]" : "atum_mi[$id_for_name][_date_on_sale_from]";

			$sale_date_from = '';
			if ( $inventory->date_on_sale_from ) :
				$sale_date_from = $inventory->date_on_sale_from instanceof \WC_DateTime ? $inventory->date_on_sale_from : new \WC_DateTime( "@{$inventory->date_on_sale_from}", new DateTimeZone( 'UTC' ) );
			endif;

			$sale_price_dates_from      = $sale_date_from ? $sale_date_from->date_i18n() : '';
			$sale_price_dates_from_sync = '';

			if ( $is_main ) :
				$sale_price_dates_from_sync = ' data-sync="' . ( $is_variation ? "#variable_sale_price_dates_from{$loop}" : '#_sale_price_dates_from' ) . '"';
			endif;

			$sale_price_dates_to_id   = $is_variation ? "_date_on_sale_to_{$id_for_name}{$loop}" : "_date_on_sale_to_$id_for_name";
			$sale_price_dates_to_name = $is_variation ? "atum_mi[$loop][$id_for_name][_date_on_sale_to]" : "atum_mi[$id_for_name][_date_on_sale_to]";

			$sale_date_to = '';
			if ( $inventory->date_on_sale_to ) :
				$sale_date_to = $inventory->date_on_sale_to instanceof \WC_DateTime ? $inventory->date_on_sale_to : new \WC_DateTime( "@{$inventory->date_on_sale_to}", new DateTimeZone( 'UTC' ) );
			endif;

			$sale_price_dates_to      = $sale_date_to ? $sale_date_to->date_i18n() : '';
			$sale_price_dates_to_sync = '';

			if ( $is_main ) :
				$sale_price_dates_to_sync = ' data-sync="' . ( $is_variation ? "#variable_sale_price_dates_to{$loop}" : '#_sale_price_dates_to' ) . '"';
			endif;
			?>
			<p class="form-field sale_price_dates_fields">
				<label for="<?php echo esc_attr( $sale_price_dates_from_id ) ?>"><?php esc_html_e( 'Sale price dates', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

				<input type="text" class="sale_price_dates_from" name="<?php echo esc_attr( $sale_price_dates_from_name ) ?>" id="<?php echo esc_attr( $sale_price_dates_from_id ) ?>" value="<?php echo esc_attr( $sale_price_dates_from ) ?>"
					placeholder="<?php echo esc_html( _x( 'From&hellip;', 'placeholder', ATUM_MULTINV_TEXT_DOMAIN ) ) ?> YYYY-MM-DD" maxlength="10"
					pattern="<?php echo esc_attr( apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ) ?>"
					<?php echo $sale_price_dates_from_sync; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

				<input type="text" class="sale_price_dates_to" name="<?php echo esc_attr( $sale_price_dates_to_name ) ?>" id="<?php echo esc_attr( $sale_price_dates_to_id ) ?>" value="<?php echo esc_attr( $sale_price_dates_to ) ?>"
					placeholder="<?php echo esc_html( _x( 'To&hellip;', 'placeholder', ATUM_MULTINV_TEXT_DOMAIN ) ) ?>  YYYY-MM-DD" maxlength="10"
					pattern="<?php echo esc_attr( apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ) ?>"
					<?php echo $sale_price_dates_to_sync; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

				<a href="#" class="description cancel_sale_schedule"><?php esc_html_e( 'Cancel', ATUM_MULTINV_TEXT_DOMAIN ) ?></a><?php echo wc_help_tip( esc_html__( 'The sale will end at the beginning of the set date.', ATUM_MULTINV_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</p>

			<?php
			//
			// Purchase Price Field.
			// ---------------------!
			if ( $is_variation ) :
				$atum_product_meta_boxes->add_purchase_price_field( $loop, array(), $variation_post );
			else :
				$atum_product_meta_boxes->add_purchase_price_field();
			endif; ?>
		</div>
		<?php endif; ?>

		<?php
		//
		// Shipping class field.
		// ---------------------!
		?>
		<div class="options_group">
			<?php
			$shipping_class_id   = $is_variation ? "_shipping_class_{$id_for_name}{$loop}" : "_shipping_class_$id_for_name";
			$shipping_class_name = $is_variation ? "atum_mi[$loop][$id_for_name][_shipping_class]" : "atum_mi[$id_for_name][_shipping_class]";

			$args = array(
				'taxonomy'         => 'product_shipping_class',
				'hide_empty'       => 0,
				'show_option_none' => __( 'No shipping class', ATUM_MULTINV_TEXT_DOMAIN ),
				'name'             => $shipping_class_name,
				'id'               => $shipping_class_id,
				'selected'         => $inventory->shipping_class,
				'class'            => 'select short',
				'orderby'          => 'name',
				'echo'             => 0,
			);
			?>
			<p class="form-field">
				<label for="<?php echo esc_attr( $shipping_class_id ) ?>"><?php esc_html_e( 'Shipping class', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>

				<?php
				$shipping_class_dropdown = wp_dropdown_categories( $args );

				if ( $is_main ) :
					$data_sync_selector      = $is_variation ? "[id='variable_shipping_class[{$loop}]']" : '#product_shipping_class'; // NOTE: as the variation ID has brackets on its name, the # is not valid here.
					$shipping_class_dropdown = str_replace( '<select', '<select data-sync="' . $data_sync_selector . '"', $shipping_class_dropdown );
				endif;

				echo $shipping_class_dropdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>

				<?php echo wc_help_tip( __( 'Shipping classes can be used by certain shipping methods to charge an extra costs when using specific inventories.', ATUM_MULTINV_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</p>
		</div>

		<?php
		// Remove the filters to not apply on the fields out of MI UI.
		remove_filter( 'atum/load_view_args/meta-boxes/product-data/supplier-fields', array( $mi_hooks, 'supplier_fields_view_args' ) );
		remove_filter( 'atum/load_view_args/meta-boxes/product-data/out-stock-threshold-field', array( $mi_hooks, 'out_stock_threshold_field_view_args' ) );
		remove_filter( 'atum/load_view_args/meta-boxes/product-data/purchase-price-field', array( $mi_hooks, 'purchase_price_field_view_args' ) );

		// Remove these filters to not apply to non-main inventories.
		remove_filter( 'atum/views/meta_boxes/supplier_fields/supplier_extra_atts', array( $mi_hooks, 'supplier_field_extra_atts' ) );
		remove_filter( 'atum/views/meta_boxes/supplier_fields/supplier_sku_extra_atts', array( $mi_hooks, 'supplier_sku_field_extra_atts' ) );
		remove_filter( 'atum/views/meta_boxes/out_stock_threshold_field_extra_atts', array( $mi_hooks, 'out_stock_threshold_field_extra_atts' ) );
		remove_filter( 'atum/views/meta_boxes/purchase_price_field_extra_atts', array( $mi_hooks, 'purchase_price_field_extra_atts' ) );

		do_action( 'atum/multi_inventory/after_inventory_fields', $inventory );
		?>

	</div>

	<?php $inv_name_name = $is_variation ? "atum_mi[$loop][$id_for_name][_inventory_name]" : "atum_mi[$id_for_name][_inventory_name]"; ?>
	<input class="inventory-name-input" type="hidden" value="<?php echo esc_attr( $inventory->name ) ?>" name="<?php echo esc_attr( $inv_name_name ) ?>" data-allow-clear="no">

	<?php if ( $is_main ) : ?>
		<?php $is_main_name = $is_variation ? "atum_mi[$loop][$id_for_name][_is_main]" : "atum_mi[$id_for_name][_is_main]"; ?>
		<input class="is-main-input" type="hidden" value="yes" name="<?php echo esc_attr( $is_main_name ) ?>" data-allow-clear="no">
	<?php endif; ?>

</div>
