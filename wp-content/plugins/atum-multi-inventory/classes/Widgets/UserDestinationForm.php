<?php
/**
 * Widget to display the user destination form in a sidebar
 *
 * @package     AtumMultiInventory/Widgets
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @since       1.0.0
 */

namespace AtumMultiInventory\Widgets;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Inc\GeoPrompt;
use AtumMultiInventory\Inc\Helpers;


class UserDestinationForm extends \WP_Widget {

	/**
	 * The restriction mode set in Settings
	 *
	 * @var bool
	 */
	private $current_restriction_mode;

	/**
	 * UserDestinationForm constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->current_restriction_mode = Helpers::get_region_restriction_mode();

		// Widget settings.
		$widget_ops = apply_filters( 'atum/multi_inventory/widgets/user_destination_form_settings', array(
			'classname'   => 'atum-mi-widget',
			'description' => __( "Displays a form to ask users for their shipping destination. Used by ATUM Multi-Inventory add-on when the 'Region Restriction Mode' is set to 'Shipping Zones'.", ATUM_MULTINV_TEXT_DOMAIN ),
		) );

		parent::__construct(
			'atum-mi-user-destination-form-widget',
			__( '(ATUM) User Destination Form', ATUM_MULTINV_TEXT_DOMAIN ),
			$widget_ops
		);

	}

	/**
	 * Echo the widget content
	 *
	 * @since 1.0.0
	 *
	 * @param array $args     Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget.
	 *
	 * @throws \Exception
	 */
	public function widget( $args, $instance ) {

		// If the restriction mode is not set to "Shipping Zones", do not show the widget.
		if ( 'shipping-zones' !== $this->current_restriction_mode ) {
			return;
		}

		// Do not show on checkout page.
		if ( is_checkout() ) {
			return;
		}

		// The form must have at least one field or won't be shown.
		if ( ! is_array( $instance['required_fields'] ) || ! in_array( 'yes', array_values( $instance['required_fields'] ) ) ) {
			return;
		}

		// TODO: IF THE USER IS LOGGED IN AND WE HAVE ALL THE SHIPPING DATA, SHOULD WE HIDE THE WIDGET?
		do_action( 'atum/multi_inventory/widgets/before_user_destination_form', $args, $instance );

		/**
		 * Variable definitions
		 *
		 * @var string $before_widget
		 * @var string $before_title
		 * @var string $after_title
		 * @var string $after_widget
		 */
		extract( $args );

		// Prepare the fields.
		if ( $instance['title'] ) {
			$instance['title'] = $before_title . Helpers::replace_text_tags( $instance['title'] ) . $after_title;
		}

		if ( $instance['subtitle'] ) {
			$instance['subtitle'] = '<h4>' . Helpers::replace_text_tags( $instance['subtitle'] ) . '</h4>';
		}

		$instance['text']            = Helpers::replace_text_tags( $instance['text'] );
		$instance['success_message'] = __( 'Your destination preference was saved. Thank You!', ATUM_MULTINV_TEXT_DOMAIN );

		// Prepare the privacy field.
		$privacy_page             = ! function_exists( 'wc_privacy_policy_page_id' ) && ! empty( $instance['privacy_page'] ) ? esc_url( $instance['privacy_page'] ) : '';
		$instance['privacy_text'] = Helpers::replace_privacy_link_tags( $instance['privacy_text'], $privacy_page );

		$atum_location               = Helpers::get_visitor_location();
		$instance['default_country'] = $instance['default_state'] = $instance['default_postcode'] = '';

		if ( 'yes' === $instance['required_fields']['regions'] ) {

			if ( ! empty( $atum_location['country'] ) ) {
				$instance['default_country'] = $atum_location['country'];
			}

			if ( ! empty( $atum_location['state'] ) ) {
				$instance['default_state'] = $atum_location['state'];
			}
			elseif ( $instance['default_country'] ) {

				$country_states = WC()->countries->get_states( $instance['default_country'] );

				if ( ! empty( $country_states ) ) {
					// Get the first one as selected.
					$states_codes              = array_keys( $country_states );
					$instance['default_state'] = array_shift( $states_codes );
				}

			}

		}

		if ( 'yes' === $instance['required_fields']['postcode'] && ! empty( $atum_location['postcode'] ) ) {
			$instance['default_postcode'] = $atum_location['postcode'];
		}

		// Check if we have all the requested info and the "show_only_once" option is set to "yes".
		if ( 'yes' === $instance['show_only_once'] ) {

			if (
				( 'yes' === $instance['required_fields']['postcode'] && $instance['default_postcode'] ) ||
				( 'yes' === $instance['required_fields']['regions'] && $instance['default_country'] )
			) {
				return;
			}

		}

		echo $before_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/geo-prompt/form-markup', $instance );

		echo $after_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		do_action( 'atum/multi_inventory/widgets/after_user_destination_form', $args, $instance );

		$this->enqueue_scripts();

	}

	/**
	 * Update a particular instance
	 *
	 * This function should check that $new_instance is set correctly. The newly-calculated
	 * value of `$instance` should be returned. If false is returned, the instance won't be saved/updated
	 *
	 * @since 1.0.0
	 *
	 * @param array $new_instance New settings for this instance as input by the user via {@see WP_Widget::form()}.
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array Settings to save or bool false to cancel saving
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['required_fields'] = array(
			'regions'  => isset( $new_instance['required_fields'] ) && is_array( $new_instance['required_fields'] ) && in_array( 'regions', $new_instance['required_fields'] ) ? 'yes' : 'no',
			'postcode' => isset( $new_instance['required_fields'] ) && is_array( $new_instance['required_fields'] ) && in_array( 'postcode', $new_instance['required_fields'] ) ? 'yes' : 'no',
		);

		$instance['title']        = esc_html( $new_instance['title'] );
		$instance['subtitle']     = esc_html( $new_instance['subtitle'] );
		$instance['text']         = esc_html( $new_instance['text'] );
		$instance['privacy_text'] = esc_html( $new_instance['privacy_text'] );

		if ( isset( $new_instance['privacy_page'] ) ) {
			$instance['privacy_page'] = esc_url( $new_instance['privacy_page'] );
		}

		$instance['show_only_once'] = isset( $new_instance['show_only_once'] ) && 'yes' === $new_instance['show_only_once'] ? 'yes' : 'no';

		return $instance;

	}

	/**
	 * Output the update settings form
	 *
	 * @since 1.0.0
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {

		$defaults = array(
			'required_fields' => array(
				'regions'  => 'yes',
				'postcode' => 'yes',
			),
			'title'           => __( 'Hello!', ATUM_MULTINV_TEXT_DOMAIN ),
			'subtitle'        => __( 'Select your region, please', ATUM_MULTINV_TEXT_DOMAIN ),
			'text'            => __( 'We have detected your location and we set your nearest region to [country]. If your shipping address is not within this zone, please select one below.', ATUM_MULTINV_TEXT_DOMAIN ),
			'privacy_link'    => '',
			'privacy_text'    => __( 'I accept the [link]privacy policy[/link]', ATUM_MULTINV_TEXT_DOMAIN ),
			'privacy_page'    => '',
			'show_only_once'  => 'no',
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/widgets/user-destination-form-settings', array(
			'instance'                 => $instance,
			'widget'                   => $this,
			'current_restriction_mode' => $this->current_restriction_mode,
		) );

	}

	/**
	 * Enqueue the widget scripts
	 *
	 * @since 1.0.0
	 */
	private function enqueue_scripts() {

		wp_register_style( 'atum-icons', ATUM_URL . 'assets/css/atum-icons.css', array(), ATUM_MULTINV_VERSION );
		wp_register_style( 'atum-mi-widgets', ATUM_MULTINV_URL . 'assets/css/atum-mi-widgets.css', array( 'dashicons', 'atum-icons' ), ATUM_MULTINV_VERSION );
		wp_enqueue_style( 'atum-mi-widgets' );

		if ( ! wp_script_is( 'atum-mi-geo-prompt', 'registered' ) ) {

			wp_register_script( 'atum-mi-geo-prompt', ATUM_MULTINV_URL . 'assets/js/build/atum-mi-geo-prompt.js', array( 'jquery' ), ATUM_MULTINV_VERSION, TRUE );

			$vars = array(
				'countries'   => Helpers::get_regions( 'countries' ),
				'states'      => WC()->countries->get_states(),
				'cookieName'  => GeoPrompt::GEO_COOKIE_NAME,
				'loggedIn'    => is_user_logged_in(),
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'errorSaving' => __( "We couldn't update your profile address, please try again", ATUM_MULTINV_TEXT_DOMAIN ),
			);

			wp_localize_script( 'atum-mi-geo-prompt', 'atumMultGeoPromptVars', $vars );
			wp_enqueue_script( 'atum-mi-geo-prompt' );

		}

	}

}
