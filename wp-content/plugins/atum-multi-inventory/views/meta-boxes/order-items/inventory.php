<?php
/**
 * View for the Inventories' Header UI within the Order items
 *
 * @since 1.0.1
 *
 * @var \WC_Order              $order
 * @var object                 $order_item_inventory
 * @var int                    $order_type_table_id
 * @var \WC_Order_Item_Product $item
 * @var \WC_Product            $product
 * @var int                    $item_id
 * @var int                    $class_line_delete
 * @var string                 $currency
 */

defined( 'ABSPATH' ) || die;

use AtumMultiInventory\Inc\Helpers;
use Atum\Inc\Globals;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\PurchaseOrders\PurchaseOrders;

$reduced_stock = 0;

if ( empty( $order_item_inventory ) ) :
	$total        = $subtotal = $refund_qty = $refund_total = 0;
	$qty          = isset( $product ) && $product instanceof \WC_Product ? $product->get_min_purchase_quantity() : 1;
	$inventory    = Helpers::get_inventory( 0 );
	$item_id      = '{{item_id}}';
	$inventory_id = '{{inventory_id}}';
	$extra_data   = array(
		'name'         => $inventory->name,
		'sku'          => $inventory->sku,
		'supplier_sku' => $inventory->supplier_sku,
	);
else :
	// WC product bundles compatibility.
	$is_bundle_item = class_exists( '\WC_Product_Bundle' ) && wc_pb_is_bundled_order_item( $item ) ? TRUE : FALSE;
	$inventory_id   = absint( $order_item_inventory->inventory_id );
	$total          = ! $is_bundle_item || ! empty( floatval( $item->get_total() ) ) ? $order_item_inventory->total : 0;
	$subtotal       = ! $is_bundle_item || ! empty( floatval( $item->get_subtotal() ) ) ? $order_item_inventory->subtotal : 0;
	$qty            = floatval( $order_item_inventory->qty );
	$refund_qty     = empty( $order_item_inventory->refund_qty ) ? 0 : floatval( $order_item_inventory->refund_qty );
	$refund_total   = empty( $order_item_inventory->refund_total ) ? 0 : floatval( $order_item_inventory->refund_total );
	$inventory      = Helpers::get_inventory( $inventory_id );
	$reduced_stock  = (int) $order_item_inventory->reduced_stock;
	$extra_data     = maybe_unserialize( $order_item_inventory->extra_data );

	if ( empty( $extra_data ) ) :
		$extra_data = array(
			'name'         => $inventory->name,
			'sku'          => $inventory->sku,
			'supplier_sku' => $inventory->supplier_sku,
		);
	endif;
endif;

if ( $inventory->managing_stock() ) :
	$stock_available = wc_format_decimal( floatval( $inventory->stock_quantity ), Globals::get_stock_decimals() );
else :
	$stock_available = 'instock' === $inventory->stock_status ? '&infin;' : '--';
endif;

$is_atum_order = $order instanceof AtumOrderModel;

?>
<div class="order-item-inventory collapsed" data-inventory_id="<?php echo esc_attr( $inventory_id ) ?>" data-is_main="<?php echo $inventory->is_main() ? 'true' : 'false' ?>">

	<div class="inventory-header">

		<div class="inventory-name">
			<span class="mi-text">
				<?php echo esc_html( $extra_data['name'] ) ?>

				<?php if ( isset( $extra_data['sku'] ) && $extra_data['sku'] ) : ?>
					(<?php echo esc_html( $extra_data['sku'] ) ?>)
				<?php endif; ?>
			</span>

			<?php if ( isset( $extra_data['supplier_sku'] ) && $extra_data['supplier_sku'] ) : ?>
				<div class="atum-inventory-supplier-sku"><strong><?php esc_html_e( 'Supplier SKU:', ATUM_MULTINV_TEXT_DOMAIN ) ?></strong> <?php echo esc_html( $extra_data['supplier_sku'] ) ?></div>
			<?php endif; ?>

			<div class="edit atum-mi-controls">
				<?php if ( 2 === $order_type_table_id ) : ?>
					<button type="button" class="button set-mi-purchase-price"><?php esc_attr_e( 'Set purchase price', ATUM_MULTINV_TEXT_DOMAIN ); ?></button>
				<?php endif; ?>
			</div>
		</div>

		<div class="numeric stock">
			<div class="edit" style="display: none;">
				<span class="label"><?php esc_html_e( 'Stock Available:', ATUM_MULTINV_TEXT_DOMAIN ) ?></span>
				<span class="stock_available"><?php echo esc_attr( $stock_available ) ?></span>

				<?php if ( version_compare( WC()->version, '3.5.0', '>=' ) ) : // For WC 3.5.0+ only.

					if ( ! $is_atum_order && ! $reduced_stock ) : ?>
						<span class="woocommerce-help-tip atum-tooltip" data-tip="<?php esc_attr_e( 'The order you are editing has not been deducted from the Available Stock yet. Deduction happens after the status changes to processing or completed.', ATUM_MULTINV_TEXT_DOMAIN ); ?>"></span>
					<?php elseif ( 2 === $order_type_table_id && $order->get_status() !== PurchaseOrders::FINISHED ) : ?>
						<span class="atum-help-tip atum-tooltip" data-tip="<?php esc_attr_e( 'The PO you are editing has not been added to the Available Stock yet. The addition happens after the status changes to received.', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></span>
					<?php endif;

				endif; ?>
			</div>
		</div>

		<div class="item_cost">
			<div class="view">
				<?php echo wc_price( $qty ? ( $total / $qty ) : 0, [ 'currency' => $currency ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>

		<div class="quantity">

			<div class="view">
				<small class="times">&times;</small>
				<?php echo $qty; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				if ( $refund_qty ) :
					echo '<small class="refunded">-' . $refund_qty . '</small>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				endif;
				?>
			</div>
			<div class="edit" style="display: none;">
				<input type="number" step="<?php echo esc_attr( apply_filters( 'woocommerce_quantity_input_step', '1', $product ) ); ?>" min="0" autocomplete="off" name="oi_inventory_qty[<?php echo $item_id; ?>][<?php echo $inventory_id; ?>]" placeholder="0" value="<?php echo $qty; ?>" data-qty="<?php echo $qty; ?>" size="4" class="quantity oi_inventory_qty"/><?php // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>

			<div class="refund" style="display: none;">
				<input type="number" step="<?php echo esc_attr( apply_filters( 'woocommerce_quantity_input_step', '1', $product ) ); ?>" autocomplete="off" name="oi_inventory_refund_qty[<?php echo $item_id; ?>][<?php echo $inventory_id; ?>]" placeholder="0" size="4" class="oi_inventory_refund_qty"/><?php // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>

		</div>

		<div class="line_cost">

			<div class="view">
				<?php echo wc_price( $total, array( 'currency' => $currency ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<?php
				// The ATUM orders don't need refunds and/or discounts.
				if ( ! $is_atum_order ) :

					if ( $subtotal !== $total ) :
						/* translators: %s: discount amount */
						echo '<span class="wc-order-item-discount">' . sprintf( esc_html__( '%s discount', ATUM_MULTINV_TEXT_DOMAIN ), wc_price( wc_format_decimal( $subtotal - $total, '' ), array( 'currency' => $currency ) ) ) . '</span>';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					endif;

					if ( $refund_total ) :
						echo '<small class="refunded">-' . wc_price( $refund_total, array( 'currency' => $currency ) ) . '</small>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					endif;

				endif; ?>
			</div>

			<div class="edit" style="display: none;">
				<div class="split-input">
					<div class="input">
						<label><?php esc_html_e( 'Pre-discount:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>
						<input type="text" name="oi_inventory_subtotal[<?php echo $item_id ?>][<?php echo $inventory_id ?>]" placeholder="0" value="<?php echo esc_attr( wc_format_localized_price( $subtotal ) ) ?>" class="line_subtotal oi_inventory_subtotal wc_input_price" data-subtotal="<?php echo $subtotal ?>"><?php // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>

					<div class="input">
						<label><?php esc_html_e( 'Total:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>
						<input type="text" name="oi_inventory_total[<?php echo $item_id ?>][<?php echo $inventory_id ?>]" placeholder="0" value="<?php echo esc_attr( wc_format_localized_price( $total ) ) ?>" class="line_total oi_inventory_total wc_input_price" data-tip="<?php esc_html_e( 'Discounts', ATUM_MULTINV_TEXT_DOMAIN ) ?>" data-total="<?php echo $total ?>"><?php // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>
			</div>

			<div class="refund" style="display: none;">
				<input type="text" name="oi_inventory_refund[<?php echo $item_id ?>][<?php echo $inventory_id ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ) ?>" class="oi_inventory_refund_total wc_input_price"><?php // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</div>

		<div class="atum-line-actions">
			<button class="toggle-indicator tips" data-tip="<?php esc_attr_e( 'Expand/Collapse info', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></button>

			<div class="edit" style="display: none;">
				<button class="atum-icon atmi-cross-circle delete-line tips<?php echo esc_attr( $class_line_delete ) ?>" data-tip="<?php esc_attr_e( 'Delete', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></button>
			</div>
		</div>

	</div>

	<?php require 'inventory-info.php' ?>

	<?php do_action( 'atum/multi_inventory/after_order_item_inventory', $item, $inventory ); ?>

</div>
