<?php
/**
 * View for Geo Prompt markup.
 *
 * @since 1.0.0
 *
 * @var string $title
 * @var string $subtitle
 * @var string $text
 * @var array  $required_fields
 * @var string $privacy_text
 * @var string $default_country
 * @var string $default_state
 * @var string $default_postcode
 * @var string $success_message
 */

defined( 'ABSPATH' ) || die;

?>
<div class="geo-wrapper">

	<div class="geo-form-wrapper">

		<?php if ( $title ) : ?>
			<?php echo wp_kses_post( $title ) ?>
		<?php endif; ?>

		<?php if ( $subtitle ) : ?>
			<?php echo wp_kses_post( $subtitle ) ?>
		<?php endif; ?>

		<?php if ( $text ) : ?>
			<p><?php echo wp_kses_post( $text ) ?></p>
		<?php endif; ?>

		<form class="geo-form" method="post" action="" data-nonce="<?php echo wp_create_nonce( 'atum-geo-prompt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">

			<?php if ( ! empty( $required_fields['regions'] ) && 'yes' === $required_fields['regions'] ) : ?>
				<div class="form-group">

					<label>
						<?php esc_attr_e( 'Select your country', ATUM_MULTINV_TEXT_DOMAIN ) ?>
						<abbr class="required" title="<?php esc_html_e( 'Required', ATUM_MULTINV_TEXT_DOMAIN ) ?>">*</abbr>
					</label>

					<select class="wc-enhanced-select atum-enhanced-select form-control region" style="width: 100%">
						<option value=""><?php esc_html_e( 'Country...', ATUM_MULTINV_TEXT_DOMAIN ) ?></option>
						<?php WC()->countries->country_dropdown_options( $default_country, $default_state ?: '*' ); ?>
					</select>

				</div>
			<?php endif; ?>

			<?php if ( ! empty( $required_fields['postcode'] ) && 'yes' === $required_fields['postcode'] ) : ?>

				<div class="form-group">

					<label>
						<?php esc_html_e( 'Add your postal code', ATUM_MULTINV_TEXT_DOMAIN ) ?>
						<abbr class="required" title="required">*</abbr>
					</label>

					<input type="text" class="form-control postcode" value="<?php echo esc_attr( $default_postcode ) ?>" placeholder="<?php esc_attr_e( 'Postal Code...', ATUM_MULTINV_TEXT_DOMAIN ) ?>">

				</div>

			<?php endif; ?>

			<?php if ( $privacy_text ) : ?>
				<div class="form-group">
					<input type="checkbox" class="accept-policy" value="yes" required> <?php echo $privacy_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>

			<button type="submit" class="btn btn-main"<?php if ( $privacy_text ) echo ' disabled="disabled"' ?>><?php esc_attr_e( 'Save', ATUM_MULTINV_TEXT_DOMAIN ) ?></button>

			<div class="mi-error"><?php esc_html_e( 'All fields are required!', ATUM_MULTINV_TEXT_DOMAIN ) ?></div>

			<?php if ( ! empty( $success_message ) ) : ?>
				<div class="mi-success"><?php echo esc_html( $success_message ) ?></div>
			<?php endif; ?>

		</form>

	</div>

</div>
