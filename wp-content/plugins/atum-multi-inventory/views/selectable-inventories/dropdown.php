<?php
/**
 * View for Selectable Inventories UI (dropdown mode).
 *
 * @since 1.3.7
 *
 * @var \WC_Product                            $product
 * @var \AtumMultiInventory\Models\Inventory[] $inventories
 * @var string                                 $title
 * @var string                                 $no_option
 * @var array                                  $excluded
 * @var string                                 $input_name
 */

defined( 'ABSPATH' ) || die;

if ( empty( $inventories ) ) :
	return;
endif;

ob_start();

// Get the available inventory options.
foreach ( $inventories as $inventory ) : ?>

	<?php if ( ! empty( $excluded ) && is_array( $excluded ) && in_array( $inventory->id, $excluded ) ) :
		continue;
	endif;?>

	<?php if ( ( 'instock' !== $inventory->stock_status || ( 'yes' === $inventory->manage_stock && $inventory->stock_quantity <= 0 ) ) && 'no' === $inventory->backorders ) :
		continue;
	endif; ?>

	<option value="<?php echo absint( $inventory->id ) ?>" data-max="<?php echo 'no' === $inventory->backorders ? esc_attr( $inventory->stock_quantity ) : '-1' ?>">
		<?php echo esc_html( $inventory->name ); ?> (<?php echo 'yes' === $inventory->manage_stock && 'no_amount' !== get_option( 'woocommerce_stock_format' ) ? esc_html( $inventory->stock_quantity ) : esc_attr_e( 'In Stock', ATUM_MULTINV_TEXT_DOMAIN ) ?>)
	</option>

<?php endforeach;

$inventory_options = ob_get_clean();

// Only show up the dropdown if there are inventory options available.
if ( trim( $inventory_options ) ) : ?>

	<div class="atum-select-mi">

		<label for="atum-select-mi">
			<?php if ( isset( $title ) ) : ?>
				<?php echo esc_html( $title ) ?>
			<?php else : ?>
				<?php esc_html_e( 'Select nearest or alternative warehouse that has stock. Note the more local the choice, the quicker you will receive it.', ATUM_MULTINV_TEXT_DOMAIN ); ?>
			<?php endif; ?>
		</label>

		<select name="<?php echo esc_attr( ! empty( $input_name ) ? $input_name : 'atum[select-mi]' ) ?>" id="atum-select-mi">

			<?php if ( ! empty( $no_option ) ) : ?>
			<option value=""><?php echo esc_html( $no_option ) ?></option>
			<?php endif; ?>

			<?php echo $inventory_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</select>

	</div>

<?php endif;
