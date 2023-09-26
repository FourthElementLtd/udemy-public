<?php
/**
 * View for the User Destination Form widget settings.
 *
 * @since 1.0.0
 *
 * @var \AtumMultiInventory\Widgets\UserDestinationForm $widget
 * @var array                                           $instance
 * @var bool                                            $current_restriction_mode
 */

defined( 'ABSPATH' ) || die;

if ( 'shipping-zones' !== $current_restriction_mode ) : ?>
	<p style="background: #EFAF00;color: #FFF;padding: 10px;">
		<?php esc_html_e( "This widget only works with the 'Shipping Zones' restriction mode.", ATUM_MULTINV_TEXT_DOMAIN ) ?>
	</p>
<?php endif ?>
<div>
	<p>
		<?php esc_html_e( 'Required Fields', ATUM_MULTINV_TEXT_DOMAIN ) ?><br>

		<label>
			<input class="multi-checkbox" type="checkbox" name="<?php echo esc_attr( $widget->get_field_name( 'required_fields' ) ) ?>[]" value="regions"
				<?php checked( ! empty( $instance['required_fields']['regions'] ) && 'yes' === $instance['required_fields']['regions'], TRUE ) ?>> <?php esc_html_e( 'Country/State', ATUM_MULTINV_TEXT_DOMAIN ) ?>
		</label>

		<label>
			<input class="multi-checkbox" type="checkbox" name="<?php echo esc_attr( $widget->get_field_name( 'required_fields' ) ) ?>[]" value="postcode"
				<?php checked( ! empty( $instance['required_fields']['postcode'] ) && 'yes' === $instance['required_fields']['postcode'], TRUE ) ?>> <?php esc_html_e( 'Postal Code', ATUM_MULTINV_TEXT_DOMAIN ) ?>
		</label>
	</p>

	<small><?php esc_html_e( 'What information do you need to know to work with region restriction mode? (Choose one at least to show the form)', ATUM_MULTINV_TEXT_DOMAIN ) ?></small>
</div>
<p>
	<label for="<?php echo esc_attr( $widget->get_field_id( 'title' ) ) ?>"><?php esc_html_e( 'Title', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>
	<input type="text" class="widefat" id="<?php echo esc_attr( $widget->get_field_id( 'title' ) ) ?>" name="<?php echo esc_attr( $widget->get_field_name( 'title' ) ) ?>" value="<?php echo esc_attr( $instance['title'] ) ?>"/>
	<small><?php esc_html_e( 'Set the widget title. You can use [br] to insert a line break', ATUM_MULTINV_TEXT_DOMAIN ) ?></small>
</p>
<p>
	<label for="<?php echo esc_attr( $widget->get_field_id( 'subtitle' ) ) ?>"><?php esc_html_e( 'Subtitle', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>
	<input type="text" class="widefat" id="<?php echo esc_attr( $widget->get_field_id( 'subtitle' ) ) ?>" name="<?php echo esc_attr( $widget->get_field_name( 'subtitle' ) ) ?>" value="<?php echo esc_attr( $instance['subtitle'] ) ?>"/>
	<small><?php esc_html_e( 'Set the widget subtitle. You can use [br] to insert a line break', ATUM_MULTINV_TEXT_DOMAIN ) ?></small>
</p>
<p>
	<label for="<?php echo esc_attr( $widget->get_field_id( 'text' ) ) ?>"><?php esc_html_e( 'Text', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>
	<textarea class="widefat" name="<?php echo esc_attr( $widget->get_field_name( 'text' ) ) ?>" id="<?php echo esc_attr( $widget->get_field_id( 'text' ) ) ?>" cols="50" rows="5"><?php echo esc_textarea( $instance['text'] ) ?></textarea>
	<small><?php esc_html_e( "Set the widget description text. You can use the tag [country] to display the user's geolocalized country on this message", ATUM_MULTINV_TEXT_DOMAIN ) ?></small>
</p>
<p>
	<label for="<?php echo esc_attr( $widget->get_field_id( 'privacy_text' ) ) ?>"><?php esc_html_e( 'Privacy Link Text', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>
	<input type="text" class="widefat" id="<?php echo esc_attr( $widget->get_field_id( 'privacy_text' ) ) ?>" name="<?php echo esc_attr( $widget->get_field_name( 'privacy_text' ) ) ?>" value="<?php echo esc_attr( $instance['privacy_text'] ) ?>"/>

	<?php
	$privacy_text_desc = '';

	if ( function_exists( 'wc_privacy_policy_page_id' ) ) :
		$privacy_text_desc = __( 'We have detected that you set a Privacy Page in WooCommerce settings.', ATUM_MULTINV_TEXT_DOMAIN ) . '<br>';
	endif;

	$privacy_text_desc .= __( "Please write here the form's privacy text. If you set the tags [link] and [/link] around a word, only this will be a link to your privacy page. If not, the whole text will be a link", ATUM_MULTINV_TEXT_DOMAIN );
	$privacy_text_desc .= '<br>' . __( 'Leave blank to not add the confirmation checkbox', ATUM_MULTINV_TEXT_DOMAIN );
	?>
	<small><?php echo wp_kses_post( $privacy_text_desc ); ?></small>
</p>

<?php if ( ! function_exists( 'wc_privacy_policy_page_id' ) ) : ?>
<p>
	<label for="<?php echo esc_attr( $widget->get_field_id( 'privacy_page' ) ) ?>"><?php esc_html_e( 'Privacy Page URL', ATUM_MULTINV_TEXT_DOMAIN ) ?></label>
	<input type="text" class="widefat" id="<?php echo esc_attr( $widget->get_field_id( 'privacy_page' ) ) ?>" name="<?php echo esc_attr( $widget->get_field_name( 'privacy_page' ) ) ?>" value="<?php echo esc_attr( $instance['privacy_page'] ) ?>"/>
	<small><?php esc_html_e( "Enter the URL to your privacy page. Please note that the Geo Prompt PopUp won't show in this page", ATUM_MULTINV_TEXT_DOMAIN ) ?></small>
</p>
<?php endif; ?>

<p>
	<label for="<?php echo esc_attr( $widget->get_field_id( 'show_only_once' ) ) ?>">
		<input type="checkbox" id="<?php echo esc_attr( $widget->get_field_id( 'show_only_once' ) ) ?>" name="<?php echo esc_attr( $widget->get_field_name( 'show_only_once' ) ) ?>" value="yes"<?php checked( $instance['show_only_once'], 'yes' ) ?>>
		<?php esc_html_e( 'Show only once', ATUM_MULTINV_TEXT_DOMAIN ) ?>
	</label><br>
	<small><?php esc_html_e( 'Hide the widget once the visitor entered his/her destination for the first time', ATUM_MULTINV_TEXT_DOMAIN ); ?></small>
</p>
