<?php
/**
 * View for the Add Inventory modal's JS template
 *
 * @since 1.4.7
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Inc\Helpers;

?>

<script type="text/template" id="add-inventory-modal">
	<div class="atum-modal-content">

		<div class="note">
			<?php esc_html_e( 'Create a new inventory for this product.', ATUM_MULTINV_TEXT_DOMAIN ) ?><br>
		</div>

		<hr>

		<form>
			<fieldset>
				<h3><?php esc_html_e( 'Inventory Details', ATUM_MULTINV_TEXT_DOMAIN ); ?></h3>

				<div class="input-group">
					<label for="inventory-name"><?php esc_html_e( 'Name', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>
					<input type="text" name="name" id="inventory-name" value="" placeholder="<?php esc_attr_e( 'Type the inventory name', ATUM_MULTINV_TEXT_DOMAIN ) ?>" required>
				</div>

				<?php $region_restriction = Helpers::get_region_restriction_mode() ?>
				<?php if ( 'no-restriction' !== $region_restriction ) : ?>
					<div class="input-group">
						<label for="inventory-region"><?php esc_html_e( 'Region', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>

						<?php $regions = Helpers::get_regions( $region_restriction ) ?>
						<select name="region" id="inventory-region">
							<!--option value=""><?php esc_html_e( 'Select Region...', ATUM_MULTINV_TEXT_DOMAIN ); ?></option-->

							<?php
							if ( 'shipping-zones' === $region_restriction ) : ?>

									<option value="-1"><?php esc_attr_e( 'Select Shipping Zone(s)', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
									<?php foreach ( $regions as $zone ) : ?>
										<option value="<?php echo esc_attr( $zone['id'] ) ?>"><?php echo esc_attr( $zone['zone_name'] ) ?></option>
									<?php endforeach ?>

							<?php elseif ( 'countries' === $region_restriction ) : ?>

									<option value="-1"><?php esc_attr_e( 'Select Country(ies)', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
									<?php foreach ( $regions as $country_code => $country_name ) : ?>
										<option value="<?php echo esc_attr( $country_code ) ?>"><?php echo esc_attr( $country_name ) ?></option>
									<?php endforeach ?>

							<?php endif;?>
						</select>
					</div>
				<?php endif; ?>

				<?php
				$locations = get_terms( array(
					'taxonomy'   => AtumGlobals::PRODUCT_LOCATION_TAXONOMY,
					'hide_empty' => FALSE,
				) ) ?>
				<div class="input-group">

					<label for="inventory-location"><?php esc_html_e( 'Location', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>

					<?php if ( empty( $locations ) ) : ?>

						<div class="alert alert-info">
							<?php
							$url_params = array(
								'taxonomy'  => AtumGlobals::PRODUCT_LOCATION_TAXONOMY,
								'post_type' => 'product',
							);

							/* translators: link to the edit terms page */
							printf( wp_kses_post( __( "You must create <a href='%s'>location terms</a> before assigning them here.", ATUM_MULTINV_TEXT_DOMAIN ) ), esc_url( add_query_arg( $url_params, admin_url( 'edit-tags.php' ) ) ) ); ?>
						</div>

					<?php else : ?>

						<select name="location" id="inventory-location">
							<option value=""><?php esc_html_e( 'Select location...', ATUM_MULTINV_TEXT_DOMAIN ); ?></option>

							<?php foreach ( $locations as $location ) : ?>
								<option value="<?php echo esc_attr( $location->term_id ) ?>"><?php echo esc_html( $location->name ) ?></option>
							<?php endforeach; ?>
						</select>

					<?php endif; ?>
				</div>

				<div class="input-group">
					<label for="inventory-bbe_date"><?php esc_html_e( 'BBE date', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>

					<span class="date-field with-icon">
						<input type="text" name="bbe_date" id="inventory-bbe_date" class="atum-datepicker"
							placeholder="<?php esc_attr_e( 'Pick a BBE date', ATUM_MULTINV_TEXT_DOMAIN ); ?>" value=""
							data-min-date="moment" data-max-date="false" data-keep-invalid="true" data-date-format="YYYY-MM-DD HH:mm">
					</span>
				</div>

				<div class="input-group">
					<label for="inventory-lot"><?php esc_html_e( 'LOT/Batch', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>
					<input type="text" name="lot" id="inventory-lot" placeholder="<?php esc_attr_e( 'Type the LOT/Batch number', ATUM_MULTINV_TEXT_DOMAIN ); ?>" value="">
				</div>

			</fieldset>
			<fieldset>
				<h3><?php esc_html_e( 'Stock Details', ATUM_MULTINV_TEXT_DOMAIN ); ?></h3>

				<div class="input-group">
					<label for="inventory-sku"><?php esc_html_e( 'SKU', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>
					<input type="text" name="sku" id="inventory-sku" placeholder="<?php esc_attr_e( 'Type the SKU', ATUM_MULTINV_TEXT_DOMAIN ); ?>" value="">
				</div>

				<div class="input-group">
					<label for="inventory-manage-stock"><?php esc_html_e( 'Manage stock?', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>

					<select name="manage_stock" id="inventory-manage_stock">
						<option value="yes"><?php esc_html_e( 'Yes', ATUM_MULTINV_TEXT_DOMAIN ); ?></option>
						<option value="no"><?php esc_html_e( 'No', ATUM_MULTINV_TEXT_DOMAIN ); ?></option>
					</select>
				</div>

				<div class="input-group manage-stock__enabled">
					<label for="inventory-stock_quantity"><?php esc_html_e( 'Stock quantity', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>
					<input type="number" name="stock_quantity" id="inventory-stock_quantity" value="0" min="0" step="<?php echo ( AtumGlobals::get_stock_decimals() > 0 ? 'any' : 1 ) ?>">
				</div>

				<div class="input-group manage-stock__enabled">
					<label for="inventory-backorders"><?php esc_html_e( 'Allow backorders?', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>

					<select name="backorders" id="inventory-backorders">
						<option value="no"><?php esc_html_e( 'Do not allow', ATUM_MULTINV_TEXT_DOMAIN ); ?></option>
						<option value="notify"><?php esc_html_e( 'Allow, but notify customer', ATUM_MULTINV_TEXT_DOMAIN ); ?></option>
						<option value="yes"><?php esc_html_e( 'Allow', ATUM_MULTINV_TEXT_DOMAIN ); ?></option>
					</select>
				</div>

				<div class="input-group manage-stock__disabled" style="display: none">
					<label for="inventory-stock-status"><?php esc_html_e( 'Stock status', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>

					<select name="stock_status" id="inventory-stock-status" disabled>
						<option value="instock"><?php esc_html_e( 'In stock', ATUM_MULTINV_TEXT_DOMAIN ); ?></option>
						<option value="outofstock"><?php esc_html_e( 'Out of stock', ATUM_MULTINV_TEXT_DOMAIN ); ?></option>
						<option value="onbackorder"><?php esc_html_e( 'On backorder', ATUM_MULTINV_TEXT_DOMAIN ); ?></option>
					</select>
				</div>

				<?php if ( 'yes' === AtumHelpers::get_option( 'out_stock_threshold', 'no' ) ) : ?>
				<div class="input-group manage-stock__enabled">
					<label for="inventory-out_stock_threshold"><?php esc_html_e( 'Out of stock threshold', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>
					<input type="number" name="out_stock_threshold" id="inventory-out_stock_threshold" value="0" min="0" step="<?php echo ( AtumGlobals::get_stock_decimals() > 0 ? 'any' : 1 ) ?>">
				</div>
				<?php endif; ?>

				<div class="input-group">
					<label for="inventory-supplier"><?php esc_html_e( 'Supplier', ATUM_MULTINV_TEXT_DOMAIN ); ?></label>

					<?php echo AtumHelpers::suppliers_dropdown( '', TRUE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="input-group">
					<label for="inventory-supplier_sku"><?php esc_html_e( "Supplier's SKU", ATUM_MULTINV_TEXT_DOMAIN ); ?></label>
					<input type="text" name="supplier_sku" id="inventory-supplier_sku" placeholder="<?php esc_attr_e( "Type the supplier's SKU", ATUM_MULTINV_TEXT_DOMAIN ); ?>" value="">
				</div>

			</fieldset>
		</form>

	</div>
</script>
