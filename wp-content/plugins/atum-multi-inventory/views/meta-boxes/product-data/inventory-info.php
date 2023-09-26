<?php
/**
 * View for the Inventories' Info UI within the inventory group
 *
 * @since 1.0.0
 *
 * @var \AtumMultiInventory\Models\Inventory $inventory
 * @var string $region_restriction_mode
 * @var bool   $is_variation
 * @var int    $loop
 * @var string $id_for_name
 * @var array  $regions
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals;
use AtumMultiInventory\Inc\Helpers;

?>
<div class="inventory-info">

	<div class="info-fields">

		<?php if ( 'no-restriction' !== $region_restriction_mode ) : ?>
		<div class="inventory-field region-field">
			<label><?php esc_html_e( 'Region:', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>

			<span class="atum-tooltip" title="<?php esc_attr_e( 'Edit Region', ATUM_MULTINV_TEXT_DOMAIN ) ?>">
				<i class="atum-edit-field atum-icon atmi-flag" data-content-id="edit-region-<?php echo esc_attr( $inventory->id ) ?>"></i>

				<?php $region_name = $is_variation ? "atum_mi[$loop][$id_for_name][_region]" : "atum_mi[$inventory->id][_region]"; ?>
				<input type="hidden" name="<?php echo esc_attr( $region_name ) ?>" value="<?php echo ( is_array( $inventory->region ) ? esc_attr( implode( ',', $inventory->region ) ) : '' ) ?>">

				<script type="text/template" id="edit-region-<?php echo esc_attr( $inventory->id ) ?>">

					<?php if ( 'shipping-zones' === $region_restriction_mode ) : ?>

						<?php if ( empty( $regions ) ) : ?>
							<div class="alert alert-info">
								<?php
								$url_params = array(
									'page' => 'wc-settings',
									'tab'  => 'shipping',
								);
								/* translators: the link to WC shipping zones setup */
								printf( wp_kses_post( __( "You have to create <a href='%s'>shipping zones</a> before assigning regions here.", ATUM_MULTINV_TEXT_DOMAIN ) ), esc_url( add_query_arg( $url_params, admin_url( 'admin.php' ) ) ) );
								?>
							</div>
						<?php else : ?>

							<div style="width: 200px">
								<select class="meta-value" multiple style="display: none;">
									<option value="-1"><?php esc_attr_e( 'Select Shipping Zone(s)', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
									<?php foreach ( $regions as $zone ) : ?>
										<option value="<?php echo esc_attr( $zone['id'] ) ?>"<?php selected( is_array( $inventory->region ) && in_array( $zone['id'], $inventory->region ), TRUE ) ?>><?php echo esc_attr( $zone['zone_name'] ) ?></option>
									<?php endforeach ?>
								</select>
							</div>

						<?php endif; ?>

					<?php else : ?>

						<div style="width: 200px">
							<select class="meta-value" multiple style="display: none;">
								<option value="-1"><?php esc_attr_e( 'Select Country(ies)', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
								<?php foreach ( $regions as $country_code => $country_name ) : ?>
									<option value="<?php echo esc_attr( $country_code ) ?>"<?php selected( is_array( $inventory->region ) && in_array( $country_code, $inventory->region ), TRUE ) ?>><?php echo esc_attr( $country_name ) ?></option>
								<?php endforeach ?>
							</select>
						</div>

					<?php endif; ?>

				</script>
			</span>

			<span class="field-label"><?php echo Helpers::get_region_labels( $inventory->region ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</div>
		<?php endif; ?>

		<div class="inventory-field location-field">
			<label><?php esc_html_e( 'Location:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

			<span class="atum-tooltip" title="<?php esc_attr_e( 'Edit Location', ATUM_MULTINV_TEXT_DOMAIN ) ?>">
				<i class="atum-edit-field atum-icon atmi-map-marker" data-content-id="edit-location-<?php echo esc_attr( $inventory->id ) ?>"></i>

				<?php $location_name = $is_variation ? "atum_mi[$loop][$id_for_name][_location]" : "atum_mi[$inventory->id][_location]"; ?>
				<input type="hidden" name="<?php echo esc_attr( $location_name ) ?>" value="<?php echo is_array( $inventory->location ) ? esc_attr( implode( ',', array_map( 'trim', $inventory->location ) ) ) : '' ?>">

				<script type="text/template" id="edit-location-<?php echo esc_attr( $inventory->id ) ?>">

					<?php if ( empty( $locations ) ) : ?>
						<div class="alert alert-info">
							<?php
							$url_params = array(
								'taxonomy'  => Globals::PRODUCT_LOCATION_TAXONOMY,
								'post_type' => 'product',
							);

							/* translators: link to the ATUM locations taxonomy page */
							printf( wp_kses_post( __( "You must create <a href='%s'>location terms</a> before assigning them here.", ATUM_MULTINV_TEXT_DOMAIN ) ), esc_url( add_query_arg( $url_params, admin_url( 'edit-tags.php' ) ) ) );
							?>
						</div>
					<?php else : ?>

						<div style="width: 200px">
							<?php
							wp_dropdown_categories( array(
								'taxonomy'          => Globals::PRODUCT_LOCATION_TAXONOMY,
								'hierarchical'      => TRUE,
								'class'             => 'meta-value atum-select-multiple',
								'show_option_none'  => __( 'Select Location(s)', ATUM_MULTINV_TEXT_DOMAIN ),
								'option_none_value' => '-1',
								'hide_empty'        => FALSE,
							) );
							?>
						</div>

					<?php endif; ?>

				</script>
			</span>

			<span class="field-label"><?php echo esc_html( Helpers::get_location_labels( $inventory->location ) ) ?></span>
		</div>

		<div class="inventory-field inventory-date-field">
			<label><?php esc_html_e( 'Inventory Date:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

			<?php $inventory_date = $inventory->inventory_date ? $inventory->inventory_date->date( 'Y-m-d H:i' ) : '' ?>
			<span class="input-group atum-datepicker" data-min-date="false" data-max-date="moment+1">
				<?php $inv_date_name = $is_variation ? "atum_mi[$loop][$id_for_name][_inventory_date]" : "atum_mi[$inventory->id][_inventory_date]"; ?>
				<input type="text" name="<?php echo esc_attr( $inv_date_name ) ?>" value="<?php echo esc_attr( $inventory_date ) ?>" style="display: none">
				<span class="input-group-addon">
					<i class="atum-icon atmi-calendar-full atum-tooltip" title="<?php esc_attr_e( 'Edit Inventory Date', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></i>
				</span>
			</span>

			<span class="field-label"><?php echo esc_html( $inventory_date ?: __( 'None', ATUM_MULTINV_TEXT_DOMAIN ) ) ?></span>
		</div>

		<div class="inventory-field bbe-date-field">
			<label><?php esc_html_e( 'BBE Date:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

			<?php $bbe_date = $inventory->bbe_date ? $inventory->bbe_date->date( 'Y-m-d H:i' ) : '' ?>
			<span class="input-group atum-datepicker" data-min-date="moment" data-max-date="false" data-keep-invalid="true">
				<?php $bbe_date_name = $is_variation ? "atum_mi[$loop][$id_for_name][_bbe_date]" : "atum_mi[$inventory->id][_bbe_date]"; ?>
				<input type="text" name="<?php echo esc_attr( $bbe_date_name ) ?>" value="<?php echo esc_attr( $bbe_date ) ?>" style="display: none">

				<span class="input-group-addon atum-tooltip" title="<?php esc_attr_e( 'Edit BBE Date (GMT)', ATUM_MULTINV_TEXT_DOMAIN ) ?>">
					<i class="atum-icon atmi-hourglass"></i>
				</span>
			</span>

			<span class="field-label"><?php echo esc_html( $bbe_date ?: __( 'None', ATUM_MULTINV_TEXT_DOMAIN ) ) ?></span>
		</div>

		<div class="inventory-field expiry-days-field"<?php if ( ! $bbe_date || 'yes' !== Helpers::get_product_expirable_inventories( $inventory->product_id ) ) echo ' style="display:none"' ?>>
			<label><?php esc_html_e( 'Expiry Days:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

			<span class="atum-tooltip" title="<?php esc_attr_e( 'Set the product as Out of Stock the specified number of days before the BBE date', ATUM_MULTINV_TEXT_DOMAIN ) ?>">
				<i class="atum-edit-field atum-icon atmi-clock" data-content-id="edit-expiry-days-<?php echo esc_attr( $inventory->id ) ?>"></i>

				<?php $expiry_days_name = $is_variation ? "atum_mi[$loop][$id_for_name][_expiry_days]" : "atum_mi[$inventory->id][_expiry_days]"; ?>
				<input type="hidden" name="<?php echo esc_attr( $expiry_days_name ) ?>" value="<?php echo esc_attr( $inventory->expiry_days ) ?>">

				<script type="text/template" id="edit-expiry-days-<?php echo esc_attr( $inventory->id ) ?>">
					<input type="number" step="1" min="0" class="meta-value" value="<?php echo esc_attr( $inventory->expiry_days ) ?>">
				</script>
			</span>

			<span class="field-label"><?php echo esc_html( $inventory->expiry_days ?: 0 ) ?></span>
		</div>

		<div class="inventory-field priority-field no-icon">
			<label><?php esc_html_e( 'Priority:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

			<span class="field-label"><?php echo esc_attr( $inventory->priority ) ?></span>
			<?php $priority_name = $is_variation ? "atum_mi[$loop][$id_for_name][_priority]" : "atum_mi[$inventory->id][_priority]"; ?>
			<input type="hidden" name="<?php echo esc_attr( $priority_name ) ?>" value="<?php echo esc_attr( $inventory->priority ) ?>">
		</div>

		<div class="inventory-field lot-field">
			<label><?php esc_html_e( 'LOT/Batch:', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

			<span class="atum-tooltip" title="<?php esc_attr_e( 'Edit LOT/Batch number', ATUM_MULTINV_TEXT_DOMAIN ) ?>">
				<i class="atum-edit-field atum-icon atmi-tag" data-content-id="edit-lot-<?php echo esc_attr( $inventory->id ) ?>"></i>

				<?php $lot_name = $is_variation ? "atum_mi[$loop][$id_for_name][_lot]" : "atum_mi[$inventory->id][_lot]"; ?>
				<input type="hidden" name="<?php echo esc_attr( $lot_name ) ?>" value="<?php echo esc_attr( $inventory->lot ) ?>">

				<script type="text/template" id="edit-lot-<?php echo esc_attr( $inventory->id ) ?>">
					<input type="text" class="meta-value" value="<?php echo esc_attr( $inventory->lot ) ?>">
				</script>
			</span>

			<span class="field-label"><?php echo esc_html( $inventory->lot ?: __( 'None', ATUM_MULTINV_TEXT_DOMAIN ) ) ?></span>
		</div>

	</div>

	<div class="controls-bar">
		<i class="clone-inventory atum-icon atmi-duplicate atum-tooltip" title="<?php esc_attr_e( 'Clone Inventory', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></i>
		<i class="clear-inventory atum-icon atmi-undo atum-tooltip" title="<?php esc_attr_e( 'Clear Inventory', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></i>
		<i class="remove-inventory atum-icon atmi-trash atum-tooltip" title="<?php esc_attr_e( 'Remove/Write Off Inventory', ATUM_MULTINV_TEXT_DOMAIN ) ?>"></i>
	</div>

</div>
