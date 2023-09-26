<?php
/**
 * View for the Inventories' UI within the Variation Products' Data meta box
 *
 * @since 1.0.0
 *
 * @var \AtumMultiInventory\Models\Inventory|array $inventories
 * @var \AtumMultiInventory\Models\MainInventory   $main_inventory
 * @var string $region_restriction_mode
 * @var array  $regions
 * @var array  $locations
 * @var int    $loop
 * @var bool   $is_variation
 * @var string $field_visibility
 */

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;

$view_args = compact( 'region_restriction_mode', 'regions', 'locations', 'loop', 'is_variation' );
?>
<div class="multi-inventory-panel <?php echo esc_attr( $field_visibility ) ?>" data-nonce="<?php echo wp_create_nonce( 'atum-mi-ui-nonce' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">

	<?php if ( $is_variation ) : ?>
		<h2 class="atum-section-title">
			<i class="atum-icon atmi-multi-inventory"></i>
			<?php esc_html_e( 'Multi-Inventory', ATUM_MULTINV_TEXT_DOMAIN ) ?>
		</h2>
	<?php endif; ?>

	<section class="multi-inventory-fields">

		<div class="main-controls">
			<button type="button" class="toggle-checkboxes btn btn-main"><?php esc_attr_e( 'Select All', ATUM_MULTINV_TEXT_DOMAIN ) ?></button>
			<button type="button" class="toggle-expanded btn btn-main"><?php esc_attr_e( 'Expand All', ATUM_MULTINV_TEXT_DOMAIN ) ?></button>

			<?php do_action( 'atum/multi_inventory/meta_boxes/product_data/after_action_buttons' ); ?>

			<div class="bulk-actions">
				<select class="mi-bulk-action wc-enhanced-select atum-enhanced-select" style="width: auto;">
					<option value=""><?php esc_attr_e( 'Bulk Actions', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
					<option value="clone"><?php esc_attr_e( 'Clone Selected', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
					<option value="clear"><?php esc_attr_e( 'Clear Selected', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
					<option value="write-off"><?php esc_attr_e( 'Write Off Selected', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
					<option value="unwrite-off"><?php esc_attr_e( 'Un-write Off Selected', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
					<option value="remove"><?php esc_attr_e( 'Remove Selected', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
				</select>

				<button type="button" class="apply-bulk btn btn-warning"><?php esc_attr_e( 'Apply', ATUM_MULTINV_TEXT_DOMAIN ) ?></button>
			</div>
		</div>

		<?php
		// Load the main inventory.
		AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/meta-boxes/product-data/inventory-group', array_merge(
			$view_args,
			array(
				'inventory' => $main_inventory,
				'is_main'   => TRUE,
			)
		) );

		$view_args['is_main'] = FALSE;

		// Load the rest of inventories.
		foreach ( $inventories as $inventory ) :
			AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/meta-boxes/product-data/inventory-group', array_merge( $view_args, compact( 'inventory' ) ) );
		endforeach; ?>

		<button type="button" class="add-inventory btn btn-success btn-sm btn-block">
			<i class="atum-icon atmi-plus-circle"></i>
			<?php esc_attr_e( 'Add New Inventory', ATUM_MULTINV_TEXT_DOMAIN ) ?>
		</button>
	</section>

</div>
