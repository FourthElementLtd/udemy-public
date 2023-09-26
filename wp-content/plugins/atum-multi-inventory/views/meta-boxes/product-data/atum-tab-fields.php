<?php
/**
 * View for the Multi-Inventory's fields within the ATUM's Product Data meta box
 *
 * @since 1.0.0
 *
 * @var string $field_visibility
 * @var bool   $is_variation
 * @var int    $loop
 * @var string $multi_inventory
 * @var string $inventory_sorting_mode
 * @var string $inventory_iteration
 * @var string $expirable_inventories
 * @var string $price_per_inventory
 * @var string $selectable_inventories
 * @var string $selectable_inventories_mode
 * @var string $product_type
 */

defined( 'ABSPATH' ) || die;

?>
<div class="options_group <?php echo esc_attr( $field_visibility ) ?>">

	<h4 class="atum-section-title"><?php esc_html_e( 'Multi-Inventory Settings', ATUM_MULTINV_TEXT_DOMAIN ) ?></h4>

	<p class="form-field _multi_inventory_field">
		<label for="multi_inventory"><?php esc_attr_e( 'Multi-Inventory', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

		<?php $name = ! $is_variation ? 'multi_inventory' : "variation_atum_tab[multi_inventory][$loop]"; ?>
		<span class="multi_inventory_buttons btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">
			<label class="btn btn-gray<?php if ( 'global' === $multi_inventory ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $multi_inventory, 'global' ) ?> value="global"> <?php esc_attr_e( 'Global', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'yes' === $multi_inventory ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $multi_inventory, 'yes' ) ?> value="yes"> <?php esc_attr_e( 'Yes', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'no' === $multi_inventory ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $multi_inventory, 'no' ) ?> value="no"> <?php esc_attr_e( 'No', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>
		</span>

		<?php echo wc_help_tip( esc_attr__( 'Enable/Disable the multi-inventory at product level. This will override the global setting.', ATUM_MULTINV_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>

	<p class="form-field _inventory_sorting_mode_field">
		<label for="inventory_sorting_mode"><?php esc_attr_e( 'Sorting Mode', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

		<?php $name = ! $is_variation ? 'inventory_sorting_mode' : "variation_atum_tab[inventory_sorting_mode][$loop]"; ?>
		<span class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">
			<label class="btn btn-gray<?php if ( 'global' === $inventory_sorting_mode ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $inventory_sorting_mode, 'global' ) ?> value="global"> <?php esc_attr_e( 'Global', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'fifo' === $inventory_sorting_mode ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $inventory_sorting_mode, 'fifo' ) ?> value="fifo"> <?php esc_attr_e( 'FIFO', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'lifo' === $inventory_sorting_mode ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $inventory_sorting_mode, 'lifo' ) ?> value="lifo"> <?php esc_attr_e( 'LIFO', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'bbe' === $inventory_sorting_mode ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $inventory_sorting_mode, 'bbe' ) ?> value="bbe"> <?php esc_attr_e( 'BBE/Exp.', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'manual' === $inventory_sorting_mode ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $inventory_sorting_mode, 'manual' ) ?> value="manual"> <?php esc_attr_e( 'Manual', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>
		</span>

		<?php echo wc_help_tip( __( 'Default global setting for the inventory sorting mode.<ul><li>FIFO: First added sells first</li><li>LIFO: Last added sells first</li><li>BBE: Shortest lifespan sells first</li><li>Manual: Set your priorities manually</li>', ATUM_MULTINV_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>

	<p class="form-field _inventory_iteration_field">
		<label for="inventory_iteration"><?php esc_attr_e( 'Inventory Iteration', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

		<?php $name = ! $is_variation ? 'inventory_iteration' : "variation_atum_tab[inventory_iteration][$loop]"; ?>
		<span class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">
			<label class="btn btn-gray<?php if ( 'global' === $inventory_iteration ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $inventory_iteration, 'global' ) ?> value="global"> <?php esc_attr_e( 'Global', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'use_next' === $inventory_iteration ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $inventory_iteration, 'use_next' ) ?> value="use_next"> <?php esc_attr_e( 'Use next in priority order', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'out_of_stock' === $inventory_iteration ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $inventory_iteration, 'out_of_stock' ) ?> value="out_of_stock"> <?php esc_attr_e( 'Show out of stock', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>
		</span>

		<?php echo wc_help_tip( esc_attr__( 'What to do when the first selling inventory is out of stock?', ATUM_MULTINV_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>

	<p class="form-field _expirable_inventories_field">
		<label for="expirable_inventories"><?php esc_attr_e( 'Expirable Inventories', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

		<?php $name = ! $is_variation ? 'expirable_inventories' : "variation_atum_tab[expirable_inventories][$loop]"; ?>
		<span class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">
			<label class="btn btn-gray<?php if ( 'global' === $expirable_inventories ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $expirable_inventories, 'global' ) ?> value="global"> <?php esc_attr_e( 'Global', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'yes' === $expirable_inventories ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $expirable_inventories, 'yes' ) ?> value="yes"> <?php esc_attr_e( 'Yes', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'no' === $expirable_inventories ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $expirable_inventories, 'no' ) ?> value="no"> <?php esc_attr_e( 'No', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>
		</span>

		<?php echo wc_help_tip( esc_attr__( "Set the inventories as 'Out of Stock' when reaching their BBE dates (if set)", ATUM_MULTINV_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>

	<?php
	$show_price_per_inventory = TRUE;
	if ( class_exists( '\WC_Subscriptions' ) && in_array( $product_type, [ 'variable-subscription', 'subscription' ], TRUE ) ) :
		$show_price_per_inventory = FALSE;
	endif;
	?>

	<?php if ( $show_price_per_inventory ) : ?>
	<p class="form-field _price_per_inventory_field">
		<label for="price_per_inventory"><?php esc_attr_e( 'Price per Inventory', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

		<?php $name = ! $is_variation ? 'price_per_inventory' : "variation_atum_tab[price_per_inventory][$loop]"; ?>
		<span class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">
			<label class="btn btn-gray<?php if ( 'global' === $price_per_inventory ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $price_per_inventory, 'global' ) ?> value="global"> <?php esc_attr_e( 'Global', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'yes' === $price_per_inventory ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $price_per_inventory, 'yes' ) ?> value="yes"> <?php esc_attr_e( 'Yes', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'no' === $price_per_inventory ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $price_per_inventory, 'no' ) ?> value="no"> <?php esc_attr_e( 'No', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>
		</span>

		<?php echo wc_help_tip( esc_attr__( 'Allow distinct inventories to have distinct prices', ATUM_MULTINV_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>
	<?php endif; ?>

	<p class="form-field _selectable_inventories_field">
		<label for="selectable_inventories"><?php esc_attr_e( 'Selectable Inventories', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

		<?php $name = ! $is_variation ? 'selectable_inventories' : "variation_atum_tab[selectable_inventories][$loop]"; ?>
		<span class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">
			<label class="btn btn-gray<?php if ( 'global' === $selectable_inventories ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $selectable_inventories, 'global' ) ?> value="global"> <?php esc_attr_e( 'Global', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'yes' === $selectable_inventories ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $selectable_inventories, 'yes' ) ?> value="yes"> <?php esc_attr_e( 'Yes', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'no' === $selectable_inventories ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $selectable_inventories, 'no' ) ?> value="no"> <?php esc_attr_e( 'No', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>
		</span>

		<?php echo wc_help_tip( esc_attr__( 'Enable this option to allow users to choose the inventories they want to purchase within product the page and cart.', ATUM_MULTINV_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>

	<p class="form-field _selectable_inventories_mode_field">
		<label for="selectable_inventories_mode"><?php esc_attr_e( 'Inventory Selection Mode', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>

		<?php $name = ! $is_variation ? 'selectable_inventories_mode' : "variation_atum_tab[selectable_inventories_mode][$loop]"; ?>
		<span class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">
			<label class="btn btn-gray<?php if ( 'global' === $selectable_inventories_mode ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $selectable_inventories_mode, 'global' ) ?> value="global"> <?php esc_attr_e( 'Global', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'yes' === $selectable_inventories_mode ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $selectable_inventories_mode, 'dropdown' ) ?> value="dropdown"> <?php esc_attr_e( 'Dropdown', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>

			<label class="btn btn-gray<?php if ( 'no' === $selectable_inventories_mode ) echo ' active' ?>">
				<input type="radio" name="<?php echo esc_attr( $name ) ?>" autocomplete="off"<?php checked( $selectable_inventories_mode, 'list' ) ?> value="list"> <?php esc_attr_e( 'List', ATUM_MULTINV_TEXT_DOMAIN ) ?>
			</label>
		</span>

		<?php echo wc_help_tip( esc_attr__( 'Select between a dropdown or a list for selecting inventories within the product page.', ATUM_MULTINV_TEXT_DOMAIN ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>

</div>
