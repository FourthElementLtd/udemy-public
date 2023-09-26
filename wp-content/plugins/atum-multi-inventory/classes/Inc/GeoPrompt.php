<?php
/**
 * Class responsible of the Geo Prompt
 *
 * @package     AtumMultiInventory\Inc
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @since       1.0.0
 */

namespace AtumMultiInventory\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;

class GeoPrompt {

	/**
	 * The location cookie name
	 */
	const GEO_COOKIE_NAME = 'atum_location';

	/**
	 * The cookie duration in seconds
	 *
	 * @var int
	 */
	private static $cookie_duration;

	/**
	 * The singleton instance holder
	 *
	 * @var GeoPrompt
	 */
	private static $instance;

	/**
	 * GeoPrompt singleton constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Enqueue Geo Prompt scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 1 );

		// Append the lightbox div if required.
		add_action( 'wp_footer', array( $this, 'add_geo_prompt' ) );

		// Set the ATUM location cookie duration.
		self::$cookie_duration = apply_filters( 'atum/multi_inventory/cookie_location', MONTH_IN_SECONDS );

	}

	/**
	 * Append the Geop Promt if required.
	 *
	 * @since 1.0.0
	 */
	public function add_geo_prompt() {

		if ( ! $this->is_geoprompt_required() ) {
			return;
		}

		$required_fields = maybe_unserialize( AtumHelpers::get_option( 'mi_geoprompt_required_fields', [] ) );

		// At least one field should be shown in the form.
		if ( empty( $required_fields ) ) {
			return;
		}

		$title = esc_html( AtumHelpers::get_option( 'mi_geoprompt_title', '' ) );

		if ( $title ) {
			/* @noinspection PhpUnhandledExceptionInspection */
			$title = '<h2>' . Helpers::replace_text_tags( $title ) . '</h2>';
		}

		$subtitle = esc_html( AtumHelpers::get_option( 'mi_geoprompt_subtitle', '' ) );

		if ( $subtitle ) {
			/* @noinspection PhpUnhandledExceptionInspection */
			$subtitle = '<h3>' . Helpers::replace_text_tags( $subtitle ) . '</h3>';
		}

		$text = esc_textarea( AtumHelpers::get_option( 'mi_geoprompt_text', '' ) );
		/* @noinspection PhpUnhandledExceptionInspection */
		$text = Helpers::replace_text_tags( $text );

		// Get default region.
		/* @noinspection PhpUnhandledExceptionInspection */
		$atum_location   = Helpers::get_visitor_location();
		$default_country = $default_state = $default_postcode = '';

		if ( 'yes' === $required_fields['regions'] ) {

			if ( ! empty( $atum_location['country'] ) ) {
				$default_country = $atum_location['country'];
			}

			if ( ! empty( $atum_location['state'] ) ) {
				$default_state = $atum_location['state'];
			}
			elseif ( $default_country ) {

				$country_states = WC()->countries->get_states( $default_country );

				if ( ! empty( $country_states ) ) {
					// Get the first one as selected.
					$states_codes  = array_keys( $country_states );
					$default_state = array_shift( $states_codes );
				}

			}

		}

		if ( 'yes' === $required_fields['postcode'] && ! empty( $atum_location['postcode'] ) ) {
			$default_postcode = $atum_location['postcode'];
		}

		// Prepare the privacy field if needed.
		$privacy_text = '';

		if ( ! function_exists( 'wc_privacy_policy_page_id' ) || wc_privacy_policy_page_id() ) {
			$privacy_text = AtumHelpers::get_option( 'mi_geoprompt_privacy_text', __( 'I accept the [link]privacy policy[/link]', ATUM_MULTINV_TEXT_DOMAIN ) );
			$privacy_text = Helpers::replace_privacy_link_tags( $privacy_text );
		}

		ob_start();

		?>
		<script type="text/html" id="mi-geo-template">
			<div class="woocommerce-billing-fields featherlight-lightbox" id="geoprompt">
				<?php AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/geo-prompt/form-markup', compact( 'title', 'subtitle', 'text', 'required_fields', 'privacy_text', 'default_country', 'default_state', 'default_postcode' ) ); ?>
			</div>
		</script>
		<?php

		$content  = ob_get_clean();
		$content .= $this->get_dynamic_css();

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get the css needed for the geoprompt popup. It's separated from HTML to easily add logic for themes
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function get_dynamic_css() {

		// Styles.
		$border_radius = AtumHelpers::get_option( 'mi_geoprompt_border_radius' );
		$bg_color      = AtumHelpers::get_option( 'mi_geoprompt_bg_color' );
		$accent_color  = AtumHelpers::get_option( 'mi_geoprompt_accent_color' );
		$font_color    = AtumHelpers::get_option( 'mi_geoprompt_font_color' );

		return AtumHelpers::load_view_to_string( ATUM_MULTINV_PATH . 'views/geo-prompt/styles', compact( 'border_radius', 'bg_color', 'accent_color', 'font_color' ) );

	}

	/**
	 * Enqueue front scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		// Only in frontend and when needed.
		if ( ! is_admin() && $this->is_geoprompt_required() ) {

			wp_register_style( 'atum-icons', ATUM_URL . 'assets/css/atum-icons.css', array(), ATUM_VERSION );

			wp_register_style( 'atum-mi-geo-prompt', ATUM_MULTINV_URL . 'assets/css/atum-mi-geo-prompt.css', array( 'dashicons', 'atum-icons' ), ATUM_MULTINV_VERSION );
			wp_register_script( 'atum-mi-geo-prompt', ATUM_MULTINV_URL . 'assets/js/build/atum-mi-geo-prompt.js', array( 'jquery' ), ATUM_MULTINV_VERSION, TRUE );

			// Cookie duration in days
			// NOTE: less than a day is not currently supported by the JS component.
			$cookie_duration_in_days = self::$cookie_duration > 0 ? self::$cookie_duration / 60 / 60 / 24 : self::$cookie_duration;

			$vars = array(
				'countries'      => Helpers::get_regions( 'countries' ),
				'states'         => WC()->countries->get_states(),
				'cookieName'     => self::GEO_COOKIE_NAME,
				'cookieDuration' => $cookie_duration_in_days,
				'loggedIn'       => is_user_logged_in(),
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'errorSaving'    => __( "We couldn't update your profile address, please try again", ATUM_MULTINV_TEXT_DOMAIN ),
			);

			wp_localize_script( 'atum-mi-geo-prompt', 'atumMultGeoPromptVars', $vars );

			wp_enqueue_style( 'atum-mi-geo-prompt' );
			wp_enqueue_script( 'atum-mi-geo-prompt' );

		}

	}

	/**
	 * Is Geo Prompt_required?
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function is_geoprompt_required() {

		// Do not show the popup on the privacy page, so the visitor can read it first.
		if ( function_exists( 'wc_privacy_policy_page_id' ) ) {

			$wc_privacy_page_id = wc_privacy_policy_page_id();

			if ( $wc_privacy_page_id && is_page( $wc_privacy_page_id ) ) {
				return FALSE;
			}

		}
		else {

			global $wp;
			$privacy_page = AtumHelpers::get_option( 'mi_geoprompt_privacy_page' );

			if ( $privacy_page && trailingslashit( home_url( $wp->request ) ) === trailingslashit( $privacy_page ) ) {
				return FALSE;
			}

		}

		// Do not show the popup in the checkout page.
		if ( is_checkout() ) {
			return FALSE;
		}

		// Only available for Shipping Zones mode.
		if ( 'shipping-zones' !== Helpers::get_region_restriction_mode() ) {
			return FALSE;
		}

		// Check the exclusion rules (if any).
		$exclusion_rules = AtumHelpers::get_option( 'mi_geoprompt_exclusions' );

		if ( $exclusion_rules ) {

			$exclusion_rules = str_replace( "\r\n", '|', addcslashes( $exclusion_rules, '/' ) );
			$rules_array     = array_filter( explode( '|', $exclusion_rules ) );
			$home_url        = home_url( '/' );
			$request_url     = home_url( $_SERVER['REQUEST_URI'] );

			if ( in_array( '\/', $rules_array, TRUE ) ) {

				// Special case for the home page pattern (\/).
				if ( $home_url === $request_url ) {
					return FALSE;
				}

				// Remove the home page pattern to not match with other patterns.
				unset( $rules_array[ array_search( '\/', $rules_array ) ] );
				$exclusion_rules = implode( '|', $rules_array );

			}

			if ( $exclusion_rules ) {

				@preg_match( "/$exclusion_rules/", $request_url, $matches ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

				// Bypass the excluded pages according to exclusion patterns.
				if ( ! empty( $matches ) ) {
					return FALSE;
				}

			}

		}

		$mi_use_geoprompt             = AtumHelpers::get_option( 'mi_use_geoprompt', 'no' );
		$mi_geoprompt_required_fields = maybe_unserialize( AtumHelpers::get_option( 'mi_geoprompt_required_fields', [] ) );

		if ( ! self::is_user_localized() && 'yes' === $mi_use_geoprompt && isset( array_count_values( $mi_geoprompt_required_fields )['yes'] ) ) {
			return TRUE;
		}

		return FALSE;

	}

	/**
	 * Check the required fields, the user meta values (if is_user_logged_in) or the $_COOKIE['atum_location'] (if set)
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_user_localized() {

		$required_fields = maybe_unserialize( AtumHelpers::get_option( 'mi_geoprompt_required_fields', array() ) );

		if ( is_user_logged_in() ) {

			$user_id = get_current_user_id();

			if ( array_key_exists( 'regions', $required_fields ) && 'yes' === $required_fields['regions'] ) {

				// Only check country, don't need state, only with it it's a valid region.
				if ( ! self::validate_required_field( 'country', get_user_meta( $user_id, 'shipping_country', TRUE ) ) ) {
					return FALSE;
				}

			}

			if (
				array_key_exists( 'postcode', $required_fields ) && 'yes' === $required_fields['postcode']
				&& ! self::validate_required_field( 'postcode', get_user_meta( $user_id, 'shipping_postcode', TRUE ) )
			) {
				return FALSE;
			}

			return TRUE;

		}
		elseif ( ! empty( $_COOKIE[ self::GEO_COOKIE_NAME ] ) ) {

			$atum_location = self::get_location_cookie();

			if ( is_null( $atum_location ) ) {
				return FALSE;
			}

			$return = TRUE;

			if ( function_exists( 'wc_privacy_policy_page_id' ) && wc_privacy_policy_page_id() && ( empty( $atum_location['acceptGpdr'] ) || ! $atum_location['acceptGpdr'] ) ) {

				$privacy_text = AtumHelpers::get_option( 'mi_geoprompt_privacy_text', __( 'I accept the [link]privacy policy[/link]', ATUM_MULTINV_TEXT_DOMAIN ) );

				// When the privacy link text is left empty, means that the privacy page is enabled but the user
				// doesn't want to show the privacy checkbox anyway.
				if ( $privacy_text ) {
					$return = FALSE;
				}

			}

			if ( array_key_exists( 'regions', $required_fields ) && 'yes' === $required_fields['regions'] ) {

				if (
					empty( $atum_location['region'] ) || ! self::validate_required_field( 'country', substr( $atum_location['region'], 0, 2 ) ) ||
					! self::validate_required_field( 'region', $atum_location['region'] )
				) {
					$return = FALSE;
				}

			}

			if (
				array_key_exists( 'postcode', $required_fields ) && 'yes' === $required_fields['postcode'] &&
				( empty( $atum_location['postcode'] ) || ! self::validate_required_field( 'postcode', $atum_location['postcode'] ) )
			) {
				$return = FALSE;
			}

			if ( ! $return ) {
				self::remove_location_cookie();
			}

			return $return;

		}

		return FALSE;

	}

	/**
	 * Validate Required Fields (country , state, postcode).
	 * So we can extend this function to validate states inside countries, or validate postcodes in most common countries, etc
	 *
	 * @since 1.0.0
	 *
	 * @param string $field  country || state || postcode.
	 * @param string $value
	 *
	 * @return bool valid
	 */
	private static function validate_required_field( $field, $value ) {

		if ( is_null( $value ) ) {
			return FALSE;
		}
		if ( empty( $value ) ) {
			return FALSE;
		}

		switch ( $field ) {

			case 'country':
				if ( strlen( $value ) !== 2 ) {
					return FALSE;
				}

				if ( ! array_key_exists( $value, Helpers::get_regions( 'countries' ) ) ) {
					return FALSE;
				}

				break;

			case 'region':
				// No state needed.
				if ( FALSE === strpos( $value, ':' ) ) {
					return TRUE;
				}

				list( $country, $state ) = explode( ':', $value );

				if ( WC()->countries->get_states( $country ) ) {

					if ( $value && isset( $state ) ) {
						return TRUE;
					}
					else {
						return FALSE;
					}

				}
				else {

					// No states defined, all states are valid.
					return TRUE;
				}

				break;

			case 'postcode':
				// 3: minimum known post code length.
				if ( strlen( $value ) < 3 ) {
					return FALSE;
				}

				break;

			default:
				return FALSE;

		}

		return TRUE;

	}

	/**
	 * Sets the location cookie with the location settings passed
	 *
	 * @since 1.0.0
	 *
	 * @param array $cookie_value   It can have region (country and/or state) and/or postcode values.
	 */
	public static function set_location_cookie( $cookie_value ) {

		$customer_location = array_merge( array(
			'region'   => '',
			'postcode' => '',
		), $cookie_value );

		$encoded_location = wp_json_encode( $customer_location );

		setrawcookie( self::GEO_COOKIE_NAME, rawurlencode( $encoded_location ), time() + self::$cookie_duration, '/' );
		$_COOKIE[ self::GEO_COOKIE_NAME ] = $encoded_location;

	}

	/**
	 * Removes the location cookie if it's no longer needed
	 *
	 * @since 1.0.0
	 */
	public static function remove_location_cookie() {
		if ( ! headers_sent() ) {
			setcookie( self::GEO_COOKIE_NAME, '', - 1 );
		}
	}

	/**
	 * Returns the location cookie decoded
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_location_cookie() {

		if ( ! empty( $_COOKIE[ self::GEO_COOKIE_NAME ] ) ) {
			return json_decode( stripslashes( urldecode( $_COOKIE[ self::GEO_COOKIE_NAME ] ) ), TRUE );
		}

		return [];

	}


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_MULTINV_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return GeoPrompt instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
