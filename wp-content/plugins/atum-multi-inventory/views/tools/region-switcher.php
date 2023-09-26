<?php
/**
 * Region switcher tools
 *
 * @since 1.0.0
 *
 * @var array $field_atts
 * @var array $regions_from
 * @var array $regions_to
 */

defined( 'ABSPATH' ) || die;

?>
<div class="tool-fields-wrapper">
	<div class="repeatable-row">

		<div class="tool-fields-from">
			<select id="<?php echo esc_attr( $field_atts['id'] ) ?>_select_from" class="select-from" style="width: 200px" data-placeholder="<?php esc_attr_e( 'Switch from...', ATUM_MULTINV_TEXT_DOMAIN ) ?>">
				<option value=""></option>
				<?php foreach ( $regions_from as $region_from ) : ?>
					<option value="<?php echo esc_attr( implode( '|', array_keys( $region_from ) ) ) ?>"><?php echo esc_attr( implode( '|', $region_from ) ) ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="tool-fields-to">
			<select class="select-to atum-select-multiple" multiple style="width: 200px" data-placeholder="<?php esc_attr_e( 'Switch to...', ATUM_MULTINV_TEXT_DOMAIN ) ?>">
				<option value=""></option>
				<?php foreach ( $regions_to as $region_to_id => $region_to_label ) : ?>
					<option value="<?php echo esc_attr( $region_to_id ) ?>"><?php echo esc_attr( $region_to_label ) ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<button type="button" class="btn btn-success add-row">
			Add
		</button>

	</div>

	<input type="hidden" id="<?php echo esc_attr( $field_atts['id'] ) ?>" value="">
</div>
