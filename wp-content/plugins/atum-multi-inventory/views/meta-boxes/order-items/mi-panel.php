<?php
/**
 * View for the Inventories' UI within the WooCommerce's order items
 *
 * @since 1.0.0
 *
 * @var \WC_Order              $order
 * @var int                    $item_id
 * @var \WC_Product            $product
 * @var \WC_Order_Item_Product $item
 * @var array                  $order_item_inventories
 * @var string                 $region_restriction_mode
 * @var array                  $locations
 * @var string                 $action
 * @var string                 $data_prefix
 * @var string                 $currency
 * @var bool                   $has_multi_price
 * @var bool                   $has_reduced_stock
 */

defined( 'ABSPATH' ) || die;

$total_qty         = 0;
$class_line_delete = '';
?>
<tr class="order-item-mi-panel<?php echo $has_multi_price ? ' multi-price' : '' ?>" data-reduced-stock="<?php echo $has_reduced_stock ? 'true' : 'false' ?>" data-sort-ignore="true" data-<?php echo esc_attr( $data_prefix ) ?>order_item_id="<?php echo $item_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"<?php echo apply_filters( 'atum/product_levels/views/order-items/mi-panel/extra_atts', '', $item_id, $product, $order ); ?>>
	<td colspan="100">
		<div class="order-item-mi-wrapper">
			
			<?php if ( ! empty( $order_item_inventories ) ) :

				if ( 1 === count( $order_item_inventories ) ) :
					$class_line_delete = ' hidden';
				endif;

				foreach ( $order_item_inventories as $order_item_inventory ) :
					require 'inventory.php';
				endforeach; ?>
			
			<?php endif; ?>

			<div class="after-item-inventory edit" style="display: none;">
				<div class="inventory-actions">
					<button class="add-inventory btn btn-main btn-sm btn-block"><i class="atum-icon atmi-plus-circle"></i> <?php esc_html_e( 'ADD INVENTORY', ATUM_MULTINV_TEXT_DOMAIN ) ?></button>
				</div>
			</div>

			<?php require 'mi-management-popup.php' ?>

			<?php do_action( 'atum/multi_inventory/after_order_item_mi_panel', $item, $order_item_inventories ); ?>

		</div>
	</td>
</tr>
