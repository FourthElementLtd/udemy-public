<?php
/**
 * View for the Inventories' UI within the WooCommerce's order items
 *
 * @since 1.0.0
 *
 * @var \WC_Product            $product
 * @var \WC_Order_Item_Product $item
 * @var array                  $order_item_inventories
 */

defined( 'ABSPATH' ) || die;

$total_qty = 0;

if ( ! empty( $order_item_inventories ) ) :

	foreach ( $order_item_inventories as $order_item_inventory ) :

		$data = maybe_unserialize( $order_item_inventory->extra_data );

		if ( $data['sku'] ) : ?>
			<br>
			<span class="atum-order-item-inventory-sku" style="color: #888; font-size: 12px;">
				<?php esc_html_e( 'SKU:', ATUM_MULTINV_TEXT_DOMAIN ) ?> <?php echo esc_html( $data['sku'] ) ?>
			</span>
		<?php endif;

		if ( $data['supplier_sku'] ) : ?>
			<br>
			<span class="atum-order-item-inventory-sku" style="color: #888; font-size: 12px;">
				<?php esc_html_e( 'Supplier SKU:', ATUM_MULTINV_TEXT_DOMAIN ) ?> <?php echo esc_html( $data['supplier_sku'] ) ?>
			</span>
		<?php endif;

	endforeach;

endif;
