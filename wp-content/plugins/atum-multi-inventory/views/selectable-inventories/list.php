<?php
/**
 * View for Selectable Inventories UI (list mode).
 *
 * @since 1.3.7
 *
 * @var \WC_Product                            $product
 * @var \AtumMultiInventory\Models\Inventory[] $inventories
 */

defined( 'ABSPATH' ) || die;

use AtumMultiInventory\Inc\MultiPrice;
use AtumMultiInventory\Inc\Helpers;

if ( empty( $inventories ) ) :
	return;
endif;

$has_multi_price = Helpers::has_multi_price( $product );
?>

<div class="atum-select-mi-list">

	<div class="atum-select-mi-list__title">
		<?php esc_html_e( 'Select Location:', ATUM_MULTINV_TEXT_DOMAIN ); ?>
	</div>

	<?php foreach ( $inventories as $index => $inventory ) : ?>

		<?php if ( ( 'instock' !== $inventory->stock_status || ( 'yes' === $inventory->manage_stock && $inventory->stock_quantity <= 0 ) ) && 'no' === $inventory->backorders ) :
			continue;
		endif; ?>

		<?php do_action( 'atum/multi_inventory/selectable_list/before_selectable_inventory', $inventory ) ?>

		<div class="atum-select-mi-list__item">

			<div class="atum-select-mi-list__item__header">
				<span>
					<i class="atum-icon atmi-arrow-child"></i>
					<i class="atum-icon atmi-multi-inventory"></i>
				</span>

				<?php echo esc_html( $inventory->name ) ?>
			</div>

			<?php if ( $has_multi_price ) : ?>

				<?php
				if ( '' === $inventory->price ) :
					$price = apply_filters( 'atum/multi_inventory/selectable_inventories/empty_price_html', '', $inventory );
				elseif ( $inventory->is_on_sale() ) :
					$price = wc_format_sale_price( MultiPrice::get_inventory_price_to_display( $inventory, [ 'price' => $inventory->regular_price ] ), MultiPrice::get_inventory_price_to_display( $inventory ) ) . MultiPrice::get_inventory_price_suffix( $inventory, $product );
				else :
					$price = wc_price( MultiPrice::get_inventory_price_to_display( $inventory ) ) . MultiPrice::get_inventory_price_suffix( $inventory, $product );
				endif;

				echo '<div class="atum-select-mi-list__item__price">' . $price . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php endif; ?>

			<?php if ( 'instock' === $inventory->stock_status && 'no_amount' !== get_option( 'woocommerce_stock_format' ) ) : ?>
			<p class="stock in-stock">
				<?php
				/* translators: the number of items in stock */
				printf( esc_html__( '%s in stock', ATUM_MULTINV_TEXT_DOMAIN ), $inventory->stock_quantity ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</p>
			<?php endif; ?>

			<div class="quantity">
				<input type="number" class="input-text qty text" step="1" min="0" max="<?php echo 'no' === $inventory->backorders ? esc_attr( $inventory->stock_quantity ) : ''; ?>" name="atum[select-mi][<?php echo absint( $inventory->id ) ?>]" value="<?php echo esc_attr( ! $index ? 1 : 0 ) ?>" inputmode="numeric">
			</div>

		</div>

		<?php do_action( 'atum/multi_inventory/selectable_list/after_selectable_inventory', $inventory ) ?>

	<?php endforeach; ?>

</div>

<?php if ( $has_multi_price ) : ?>
	<p class="atum-select-mi-list__multi-price price"></p>
<?php endif; ?>
