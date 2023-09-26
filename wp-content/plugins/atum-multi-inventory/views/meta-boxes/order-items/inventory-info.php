<?php
/**
 * View for the Inventories' Info UI within the Order items
 *
 * @since 1.0.1
 *
 * @var \AtumMultiInventory\Models\Inventory $inventory
 * @var string                               $region_restriction_mode
 * @var object                               $order_item_inventory
 * @var int                                  $order_type_table_id
 * @var \WC_Order_Item_Product               $item
 */

defined( 'ABSPATH' ) || die;

use AtumMultiInventory\Inc\Helpers;

if ( ! isset( $region_restriction_mode ) ) {
	$region_restriction_mode = Helpers::get_region_restriction_mode();
}

?>
<div class="inventory-info">
	<div class="info-fields-wrapper">

		<div class="info-fields">

			<h6><?php esc_html_e( 'Inventory Details', ATUM_MULTINV_TEXT_DOMAIN ) ?></h6>

			<?php if ( 'no-restriction' !== $region_restriction_mode ) : ?>
			<div class="inventory-field region-field">
				<i class="atum-icon atmi-flag tips" data-tip="<?php esc_attr_e( 'Regions', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></i>
				<label><?php esc_html_e( 'Regions:', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>

				<span class="field-label region"><?php echo esc_html( wp_strip_all_tags( Helpers::get_region_labels( $inventory->region ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</div>
			<?php endif; ?>

			<div class="inventory-field location-field">
				<i class="atum-icon atmi-map-marker tips" data-tip="<?php esc_attr_e( 'Locations', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></i>
				<label><?php esc_html_e( 'Locations:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

				<span class="field-label location"><?php echo esc_html( wp_strip_all_tags( Helpers::get_location_labels( $inventory->location ) ) ) ?></span>
			</div>

			<div class="inventory-field inventory-date-field">
				<i class="atum-icon atmi-calendar-full tips" data-tip="<?php esc_attr_e( 'Inventory Date', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></i>
				<label><?php esc_html_e( 'Inventory Date:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

				<span class="field-label inventory-date"><?php echo esc_html( $inventory->inventory_date ? $inventory->inventory_date->date( 'Y-m-d H:i' ) : __( 'None', ATUM_MULTINV_TEXT_DOMAIN ) ) ?></span>
			</div>

			<div class="inventory-field bbe-date-field">
				<i class="atum-icon atmi-hourglass tips" data-tip="<?php esc_attr_e( 'BBE Date', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></i>
				<label><?php esc_html_e( 'BBE Date:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

				<span class="field-label bbe-date"><?php echo esc_html( $inventory->bbe_date ? $inventory->bbe_date->date( 'Y-m-d H:i' ) : __( 'None', ATUM_MULTINV_TEXT_DOMAIN ) ) ?></span>
			</div>

			<div class="inventory-field expiry-days-field">
				<i class="atum-icon atmi-clock tips" data-tip="<?php esc_attr_e( 'Expiry Days', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></i>
				<label><?php esc_html_e( 'Expiry Days:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

				<span class="field-label expiry-days"><?php echo esc_html( $inventory->expiry_days ?: 0 ) ?></span>
			</div>

			<div class="inventory-field lot-field">
				<i class="atum-icon atmi-tag tips" data-tip="<?php esc_attr_e( 'LOT/Batch number', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></i>
				<label><?php esc_html_e( 'LOT/Batch:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

				<span class="field-label lot"><?php echo esc_html( $inventory->lot ?: __( 'None', ATUM_MULTINV_TEXT_DOMAIN ) ) ?></span>
			</div>

		</div>

		<?php do_action( 'atum/multi_inventory/after_order_item_inventory_info', $inventory, $order_item_inventory, $item, $order_type_table_id ); ?>

	</div>
</div>
