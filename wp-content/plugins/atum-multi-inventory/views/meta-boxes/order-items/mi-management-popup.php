<?php
/**
 * View for the MI management popup for WooCommerce's order items
 *
 * @since 1.0.1
 *
 * @var \WC_Product            $product
 * @var int                    $item_id
 * @var bool                   $has_multi_price
 * @var \WC_Order_Item_Product $item
 */

defined( 'ABSPATH' ) || die;

use AtumMultiInventory\Inc\Helpers;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\Suppliers\Supplier;

$product_inventories = Helpers::get_product_inventories_sorted( $product->get_id() );
$restriction_enabled = 'no-restriction' === Helpers::get_region_restriction_mode() ? FALSE : TRUE;

$item_subtotal = $item->get_subtotal();
$item_discount = $item_subtotal - $item->get_total();

$order = $item->get_order();
/**
 * Variable definition
 *
 * @var PurchaseOrder $atum_order
 */
$atum_order = AtumHelpers::get_atum_order_model( $order->get_id(), FALSE );

$required_supplier = $atum_order instanceof PurchaseOrder && ! $atum_order->has_multiple_suppliers() ? $atum_order->get_supplier()->id : FALSE;

?>
<script type="text/template" id="product-inventories-<?php echo absint( $item_id ) ?>">
	<div class="order-item-mi-management">

		<div class="note">
			<?php esc_attr_e( 'NOTE: Select the inventories you want to add to the order.', ATUM_MULTINV_TEXT_DOMAIN ) ?>
		</div>

		<div class="table-legend">

			<span class="product-name">
				<?php echo esc_html( $product->get_name() ) ?>
			</span>

		</div>

		<form>
			
			<table class="widefat">
				<thead>
				<tr>
					<th>
						<input type="checkbox" value="select-all">
					</th>
					<th>
						<?php esc_html_e( 'Stock Name', ATUM_MULTINV_TEXT_DOMAIN ) ?>
					</th>

					<?php if ( $restriction_enabled ) : ?>
					<th>
						<?php esc_html_e( 'Region', ATUM_MULTINV_TEXT_DOMAIN ) ?>
					</th>
					<?php endif; ?>

					<th>
						<?php esc_html_e( 'Location', ATUM_MULTINV_TEXT_DOMAIN ) ?>
					</th>

					<th>
						<?php esc_html_e( 'Supplier', ATUM_MULTINV_TEXT_DOMAIN ) ?>
					</th>
					<th class="numeric">
						<?php esc_html_e( 'Cost', ATUM_MULTINV_TEXT_DOMAIN ) ?>
					</th>

					<?php if ( $item_discount ) : ?>
					<th class="numeric">
						<?php esc_html_e( 'Discounted', ATUM_MULTINV_TEXT_DOMAIN ) ?>
					</th>
					<?php endif; ?>

					<th class="numeric">
						<?php esc_html_e( 'Stock Available', ATUM_MULTINV_TEXT_DOMAIN ) ?>
					</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $product_inventories as $inventory ) :

					$disabled         = $required_supplier && (string) $required_supplier !== (string) $inventory->supplier_id ? 'disabled="disabled"' : '';
					$tr_class         = $required_supplier && (string) $required_supplier !== (string) $inventory->supplier_id ? 'class="invalid"' : '';
					$price            = apply_filters( 'atum/multi_inventory/meta_boxes/order-items/management-popup/price', $has_multi_price ? wc_get_price_excluding_tax( $product, [ 'price' => $inventory->price ] ) : wc_get_price_excluding_tax( $product ), $has_multi_price, $inventory, $product );
					$price_discounted = ( $item_subtotal && $price ) ? ( $price * ( 1 - $item_discount / $item_subtotal ) ) : $price; // We assume that the discount applied to the line item inventories is proportionate for each item and price.
					
					if ( $inventory->managing_stock() ) :
						$stock_available = floatval( $inventory->stock_quantity );
					else :
						$stock_available = 'instock' === $inventory->stock_status ? '&infin;' : '--';
					endif;
					
					?>
					<tr data-inventory_id="<?php echo absint( $inventory->id ) ?>" data-is_main="<?php echo $inventory->is_main() ? 'true' : 'false'; ?>" <?php echo $tr_class;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<td class="select">
							<input type="checkbox" name="select[<?php echo absint( $inventory->id ) ?>]" value="<?php echo absint( $inventory->id ) ?>" <?php echo $disabled;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						</td>

						<td class="name">
							<span class="tips" data-tip="<?php echo esc_attr( $inventory->name ) ?>">
								<?php echo esc_html( $inventory->name ) ?>
							</span>
							
							<?php if ( $inventory->is_expired() ) :
								/* translators: the inventory expiration date */ ?>
								<i class="expired atum-icon atmi-hourglass tips" data-tip="<?php printf( esc_attr__( 'This inventory expired on %s', ATUM_MULTINV_TEXT_DOMAIN ), esc_attr( $inventory->bbe_date ) ) ?>"></i>
							<?php endif; ?>
						</td>

						<?php if ( $restriction_enabled ) : ?>
						<td class="region">
							<?php $region_labels = Helpers::get_region_labels( $inventory->region ); ?>

							<span class="tips" data-tip="<?php echo esc_html( wp_strip_all_tags( $region_labels ) ) ?>">
								<?php echo esc_html( wp_strip_all_tags( $region_labels ) ) ?>
							</span>
						</td>
						<?php endif; ?>

						<td class="location">
							<?php $location_labels = Helpers::get_location_labels( $inventory->location ); ?>

							<span class="tips" data-tip="<?php echo esc_attr( $location_labels ) ?>">
								<?php echo esc_html( wp_strip_all_tags( $location_labels ) ) ?>
							</span>
						</td>

						<td class="supplier">
							<span>
							<?php if ( $inventory->supplier_id ) : ?>
								<?php $supplier = new Supplier( $inventory->supplier_id ); ?>
								<?php echo esc_html( $supplier->name ) ?>
							<?php else : ?>
								---
							<?php endif; ?>
							</span>
						</td>

						<td class="numeric cost" data-price="<?php echo esc_attr( $price ) ?>">
							<?php echo wp_kses_post( wc_price( $price ) ) ?>
						</td>

						<?php if ( $item_discount ) : ?>
						<td class="numeric discounted" data-price="<?php echo esc_attr( $price_discounted ) ?>">
							<?php echo wp_kses_post( wc_price( $price_discounted ) ) ?>
						</td>
						<?php endif; ?>

						<td class="numeric stock-available">
							<span><?php echo $stock_available; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>

							<input type="hidden" name="region" value="<?php echo esc_html( wp_strip_all_tags( Helpers::get_region_labels( $inventory->region ) ) ) ?>">
							<input type="hidden" name="location" value="<?php echo esc_html( wp_strip_all_tags( Helpers::get_location_labels( $inventory->location ) ) ) ?>">
							<input type="hidden" name="inventory-date" value="<?php echo esc_html( $inventory->inventory_date ? $inventory->inventory_date->date( 'Y-m-d H:i' ) : __( 'None', ATUM_MULTINV_TEXT_DOMAIN ) ) ?>">
							<input type="hidden" name="bbe-date" value="<?php echo esc_html( $inventory->bbe_date ? $inventory->bbe_date->date( 'Y-m-d H:i' ) : __( 'None', ATUM_MULTINV_TEXT_DOMAIN ) ) ?>">
							<input type="hidden" name="expiry-days" value="<?php echo esc_html( $inventory->expiry_days ?: 0 ) ?>">
							<input type="hidden" name="lot" value="<?php echo esc_html( $inventory->lot ?: __( 'None', ATUM_MULTINV_TEXT_DOMAIN ) ) ?>">
							<?php if ( ! $item_discount ) : ?>
								<input type="hidden" class="discounted" data-price="<?php echo esc_attr( $price_discounted ) ?>" value="<?php echo esc_attr( $price_discounted ) ?>">
							<?php endif; ?>
						</td>
					
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

		</form>
		<div class="after-item-inventory">
			<div class="need-help">
				<?php
				$documentation_link = Helpers::get_documentation_link( get_the_ID() );
				/* translators: Link to the plugin's help page */
				echo wp_kses_post( sprintf( __( 'Need help? <a href="%s" target="_blank">Read the documentation here.</a>', ATUM_MULTINV_TEXT_DOMAIN ), $documentation_link ) )
				?>
			</div>
		</div>
	</div>
</script>
