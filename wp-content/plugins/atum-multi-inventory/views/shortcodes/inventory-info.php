<?php
/**
 * View for the Inventory Info Shortcode
 *
 * @since 1.0.0
 *
 * @var array        $data
 * @var \WC_DateTime $inventory_date
 * @var \WC_DateTime $bbe_date
 * @var array        $locations
 * @var array        $regions
 * @var string       $lot
 * @var bool         $with_labels
 * @var string       $date_format
 * @var string       $product_type
 * @var string       $class
 */

use AtumMultiInventory\Inc\Helpers;

?>
<div class="atum-inventory-info<?php if ( $class ) echo esc_attr( " $class" ) ?>">
	<?php foreach ( $data as $data_key ) : ?>

		<?php
		switch ( $data_key ) :

			case 'inventory_date':
				if ( ! empty( $inventory_date ) ) : ?>
					<div class="mi-info-row">
						<?php if ( $with_labels ) : ?>
						<span class="mi-info-label"><?php esc_html_e( 'Inventory Date:', ATUM_MULTINV_TEXT_DOMAIN ) ?></span>
						<?php endif; ?>

						<span class="mi-info-text"><?php echo esc_html( $inventory_date->date( $date_format ) ) ?></span>
					</div>
				<?php endif;

				break;

			case 'bbe_date':
				if ( ! empty( $bbe_date ) ) : ?>
					<div class="mi-info-row">
						<?php if ( $with_labels ) : ?>
						<span class="mi-info-label"><?php esc_html_e( 'BBE Date:', ATUM_MULTINV_TEXT_DOMAIN ) ?></span>
						<?php endif; ?>

						<span class="mi-info-text"><?php echo esc_html( $bbe_date->date( $date_format ) ) ?></span>
					</div>
				<?php endif;

				break;

			case 'region':
				if ( ! empty( $regions ) ) : ?>
					<div class="mi-info-row">
						<?php if ( $with_labels ) : ?>
						<span class="mi-info-label"><?php echo esc_html( _n( 'Region:', 'Regions:', count( $regions ), ATUM_MULTINV_TEXT_DOMAIN ) ) ?></span>
						<?php endif; ?>

						<?php $region_labels = Helpers::get_region_labels( $regions ) ?>
						<span class="mi-info-text"><?php echo esc_html( $region_labels ) ?></span>
					</div>
				<?php endif;

				break;

			case 'location':
				if ( ! empty( $locations ) ) :
					if ( $with_labels ) : ?>
					<span class="mi-info-label"><?php echo esc_html( _n( 'Location:', 'Locations:', count( $locations ), ATUM_MULTINV_TEXT_DOMAIN ) ) ?></span>
					<?php endif; ?>

					<?php $location_labels = Helpers::get_location_labels( $locations ) ?>
					<span class="mi-info-text"><?php echo esc_html( $location_labels ) ?></span>

				<?php endif;

				break;

			case 'lot':
				if ( ! empty( $lot ) ) : ?>
					<div class="mi-info-row">
						<?php if ( $with_labels ) : ?>
						<span class="mi-info-label"><?php esc_html_e( 'LOT/Batch number:', ATUM_MULTINV_TEXT_DOMAIN ) ?></span>
						<?php endif; ?>

						<span class="mi-info-text"><?php echo esc_html( $lot ) ?></span>
					</div>
				<?php endif;

				break;

		endswitch;
		?>

	<?php endforeach; ?>
</div>
