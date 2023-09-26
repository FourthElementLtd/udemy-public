<?php
/**
 * WC_CSP_Restrict_Payment_Gateways class
 *
 * @author   SomewhereWarm <info@somewherewarm.com>
 * @package  WooCommerce Conditional Shipping and Payments
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restrict Payment Gateways.
 *
 * @class    WC_CSP_Restrict_Payment_Gateways
 * @version  1.8.0
 */
class WC_CSP_Restrict_Payment_Gateways extends WC_CSP_Restriction implements WC_CSP_Checkout_Restriction {

	public function __construct() {

		$this->id                               = 'payment_gateways';
		$this->title                            = __( 'Payment Gateways', 'woocommerce-conditional-shipping-and-payments' );
		$this->description                      = __( 'Restrict the available payment gateways based on product-related constraints.', 'woocommerce-conditional-shipping-and-payments' );
		$this->validation_types                 = array( 'checkout' );
		$this->has_admin_product_fields         = true;
		$this->supports_multiple                = true;
		$this->has_admin_global_fields          = true;
		$this->method_title                     = __( 'Payment Gateway Restrictions', 'woocommerce-conditional-shipping-and-payments' );
		$this->restricted_key                   = 'gateways';
		$this->before_payment_gateways_template = 0;
		$this->after_payment_gateways_template  = 0;

		// Filter payment gateways when restrictions apply.
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'exclude_payment_gateways' ) );

		// Save global settings.
		add_action( 'woocommerce_update_options_restrictions_' . $this->id, array( $this, 'update_global_restriction_data' ) );

		// Initialize global settings.
		$this->init_form_fields();

		// Display shipping method options.
		add_action( 'woocommerce_csp_admin_payment_gateway_option', array( $this, 'payment_gateway_option' ), 10, 3 );

		// Shows a woocommerce error on the 'woocommerce_review_order_before_cart_contents' hook when payment gateway restrictions apply.
		add_action( 'woocommerce_review_order_before_cart_contents', array( $this, 'excluded_payment_gateways_notice' ) );

		// Display notice after each excluded gateway.
		add_filter( 'woocommerce_gateway_description', array( $this, 'add_notice_after_excluded_payment_gateway' ), 100, 2 );

		// Check if payment gateways template has started/ended rendering.
		add_action( 'woocommerce_before_template_part', array( $this, 'before_payment_gateways_template' ), 10, 4 );
		add_action( 'woocommerce_after_template_part', array( $this, 'after_payment_gateways_template' ), 10, 4 );

	}

	/**
	 * Declare 'admin_global_fields' type, generated by 'generate_admin_global_fields_html'.
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'admin_global_fields' => array(
				'type' => 'admin_global_fields'
			)
		);
	}

	/**
	 * Display payment gateway options.
	 *
	 * @since  1.8.0
	 *
	 * @param  string              $gateway_id
	 * @param  WC_Payment_Gateway  $gateway
	 * @param  array               $gateways
	 * @return void
	 */
	public function payment_gateway_option( $gateway_id, $gateway, $gateways ) {
		echo '<option value="' . esc_attr( $gateway_id ) . '" ' . selected( in_array( $gateway_id, $gateways ), true, false ) . '>' . $gateway->get_title() . '</option>';
	}

	/**
	 * Generates the 'admin_global_fields' field type, which is based on metaboxes.
	 *
	 * @return string
	 */
	public function generate_admin_global_fields_html() {
		?><p>
			<?php echo __( 'Restrict the payment gateways available at checkout. Complex rules can be created by adding multiple restrictions. Each individual restriction becomes active when all defined conditions match.', 'woocommerce-conditional-shipping-and-payments' ); ?>
		</p><?php

		$this->get_admin_global_metaboxes_html();
	}

	/**
	 * Display admin options.
	 *
	 * @param  int     $index
	 * @param  array   $options
	 * @param  string  $field_type
	 * @return string
	 */
	public function get_admin_fields_html( $index, $options = array(), $field_type = 'global' ) {

		$description           = '';
		$gateways              = array();
		$message               = '';
		$show_excluded         = false;
		$show_excluded_notices = false;

		if ( isset( $options[ 'description' ] ) ) {
			$description = $options[ 'description' ];
		}

		if ( isset( $options[ 'gateways' ] ) ) {
			$gateways = $options[ 'gateways' ];
		}

		if ( isset( $options[ 'message' ] ) ) {
			$message = $options[ 'message' ];
		}

		if ( isset( $options[ 'show_excluded' ] ) && $options[ 'show_excluded' ] === 'yes' ) {
			$show_excluded = true;
		}

		if ( isset( $options[ 'show_excluded_notices' ] ) && $options[ 'show_excluded_notices' ] === 'yes' ) {
			$show_excluded_notices = true;
		}

		$payment_gateways = WC()->payment_gateways->payment_gateways();

		?>
		<div class="woocommerce_restriction_form">
			<div class="sw-form-field">
				<label>
					<?php _e( 'Short Description', 'woocommerce-conditional-shipping-and-payments' ); ?>
				</label>
				<div class="sw-form-content">
					<input class="short_description" name="restriction[<?php echo $index; ?>][description]" id="restriction_<?php echo $index; ?>_short_description" placeholder="<?php _e( 'Optional short description for this rule&hellip;', 'woocommerce-conditional-shipping-and-payments' ); ?>" value="<?php echo $description; ?>"/>
				</div>
			</div>
			<div class="sw-form-field">
				<label><?php _e( 'Exclude Gateways', 'woocommerce-conditional-shipping-and-payments' ); ?></label>
				<div class="sw-form-content">
					<select name="restriction[<?php echo $index; ?>][gateways][]" class="multiselect sw-select2" data-wrap="yes" multiple="multiple" data-placeholder="<?php _e( 'Select Payment Gateways&hellip;', 'woocommerce-conditional-shipping-and-payments' ); ?>">
						<?php
							foreach ( $payment_gateways as $key => $val ) {
								do_action( 'woocommerce_csp_admin_payment_gateway_option', $key, $val, $gateways, $field_type );

							}
						?>
					</select>
				</div>
			</div>
			<div class="sw-form-field sw-form-field--checkbox">
				<label>
					<?php _e( 'Show Excluded', 'woocommerce-conditional-shipping-and-payments' ); ?>
				</label>
				<div class="sw-form-content">
					<input type="checkbox" class="checkbox show_excluded_in_checkout" name="restriction[<?php echo $index; ?>][show_excluded]" <?php echo $show_excluded ? 'checked="checked"' : ''; ?>>
					<?php echo WC_CSP_Core_Compatibility::wc_help_tip( __( 'By default, excluded payment gateways are removed from the list of gateways available during checkout. Select this option if you prefer to show excluded gateways in the checkout options and display a restriction notice when customers attempt to complete an order using an excluded gateway.', 'woocommerce-conditional-shipping-and-payments' ) ); ?>
				</div>
			</div>
			<div class="sw-form-field show-excluded-checked" style="<?php echo false === $show_excluded ? 'display:none;' : ''; ?>">
				<label>
					<?php _e( 'Custom Notice', 'woocommerce-conditional-shipping-and-payments' ); ?>
					<?php

						if ( $field_type === 'global' ) {
							$tiptip = __( 'Defaults to:<br/>&quot;Unfortunately, your order cannot be checked out via {excluded_gateway}. To complete your order, please select an alternative payment method.&quot;<br/>When conditions are defined, resolution instructions are added to the default message.', 'woocommerce-conditional-shipping-and-payments' );
						} else {
							$tiptip = __( 'Defaults to:<br/>&quot;Unfortunately, {product} cannot be checked out via {excluded_gateway}. To complete your order, please select an alternative payment method, or remove {product} from your cart.&quot;<br/>When conditions are defined, resolution instructions are added to the default message.', 'woocommerce-conditional-shipping-and-payments' );
						}
					?>
				</label>
				<div class="sw-form-content">
					<textarea class="custom_message" name="restriction[<?php echo $index; ?>][message]" id="restriction_<?php echo $index; ?>_message" placeholder="" rows="2" cols="20"><?php echo $message; ?></textarea>
					<?php
						echo WC_CSP_Core_Compatibility::wc_help_tip( $tiptip );

						if ( $field_type === 'global' ) {
							$tip = __( 'Custom notice to display when attempting to place an order while this restriction is active. You may include <code>{excluded_gateway}</code> and have it substituted by the selected payment gateway title.', 'woocommerce-conditional-shipping-and-payments' );
						} else {
							$tip = __( 'Custom notice to display when attempting to place an order while this restriction is active. You may include <code>{product}</code> and <code>{excluded_gateway}</code> and have them substituted by the actual product title and the selected payment gateway title.', 'woocommerce-conditional-shipping-and-payments' );
						}
						echo '<span class="description">' . $tip . '</span>';
					?>
				</div>
			</div>
			<div class="sw-form-field sw-form-field--checkbox show-excluded-checked" style="<?php echo false === $show_excluded ? 'display:none;' : ''; ?>">
				<label>
					<?php _e( 'Show Static Notices', 'woocommerce-conditional-shipping-and-payments' ); ?>
				</label>
				<div class="sw-form-content">
					<input type="checkbox" class="checkbox show_excluded_notices_in_checkout" name="restriction[<?php echo $index; ?>][show_excluded_notices]" <?php echo $show_excluded_notices ? 'checked="checked"' : ''; ?>>
					<?php echo WC_CSP_Core_Compatibility::wc_help_tip( __( 'By default, when <strong>Show Excluded</strong> is enabled, a notice is displayed when customers attempt to place an order using a restricted payment method. Select this option if you also want to display a static notice under each restricted payment method.', 'woocommerce-conditional-shipping-and-payments' ) ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Display a short summary of the restriction's settings.
	 *
	 * @param  array  $options
	 * @return string
	 */
	public function get_options_description( $options ) {

		if ( ! empty( $options[ 'description' ] ) ) {
			return $options[ 'description' ];
		}

		$gateway_descriptions = array();
		$gateways             = array();

		if ( isset( $options[ 'gateways' ] ) ) {
			$gateways = $options[ 'gateways' ];
		}

		$payment_gateways = WC()->payment_gateways->payment_gateways();

		foreach ( $gateways as $key ) {

			$gateway_description = '';

			if ( isset( $payment_gateways[ $key ] ) ) {
				$payment_gateway     = $payment_gateways[ $key ];
				$gateway_description = is_callable( array( $payment_gateway, 'get_method_title' ) ) ? $payment_gateway->get_method_title() : $payment_gateway->method_title;
			}

			/**
			 * Filter the default description.
			 *
			 * @since  1.4.0
			 *
			 * @param  string  $gateway_description
			 * @param  string  $gateway_id
			 * @param  array   $gateways
			 */
			$gateway_description = apply_filters( 'woocommerce_csp_admin_payment_gateway_option_description', $gateway_description, $key, $payment_gateways );

			if ( $gateway_description ) {
				$gateway_descriptions[] = $gateway_description;
			}
		}

		return trim( implode( ', ', $gateway_descriptions ), ' ,' );
	}

	/**
	 * Display options on the global Restrictions write-panel.
	 *
	 * @param  int    $index    restriction fields array index
	 * @param  string $options  metabox options
	 * @return string
	 */
	function get_admin_global_fields_html( $index, $options = array() ) {

		$this->get_admin_fields_html( $index, $options, 'global' );
	}

	/**
	 * Display options on the product Restrictions write-panel.
	 *
	 * @param  int    $index    restriction fields array index
	 * @param  string $options  metabox options
	 * @return string
	 */
	function get_admin_product_fields_html( $index, $options = array() ) {
		?><div class="restriction-description">
			<?php echo __( 'Restrict the payment gateways available during checkout when an order contains this product.', 'woocommerce-conditional-shipping-and-payments' ); ?>
		</div><?php

		$this->get_admin_fields_html( $index, $options, 'product' );
	}

	/**
	 * Validate, process and return product options.
	 *
	 * @param  array  $posted_data
	 * @return array
	 */
	public function process_admin_fields( $posted_data ) {

		$processed_data = array();

		$processed_data[ 'gateways' ] = array();

		if ( ! empty( $posted_data[ 'gateways' ] ) ) {
			$processed_data[ 'gateways' ] = array_map( 'stripslashes', $posted_data[ 'gateways' ] );
		} else {
			return false;
		}

		if ( isset( $posted_data[ 'show_excluded' ] ) ) {
			$processed_data[ 'show_excluded' ] = 'yes';
		}

		if ( isset( $posted_data[ 'show_excluded_notices' ] ) ) {
			$processed_data[ 'show_excluded_notices' ] = 'yes';
		}

		if ( ! empty( $posted_data[ 'message' ] ) ) {
			$processed_data[ 'message' ] = wp_kses_post( stripslashes( $posted_data[ 'message' ] ) );
		}

		if ( ! empty( $posted_data[ 'description' ] ) ) {
			$processed_data[ 'description' ] = strip_tags( stripslashes( $posted_data[ 'description' ] ) );
		}

		return $processed_data;
	}

	/**
	 * Validate, process and return product metabox options.
	 *
	 * @param  array  $posted_data
	 * @return array
	 */
	public function process_admin_product_fields( $posted_data ) {

		$processed_data = $this->process_admin_fields( $posted_data );

		if ( ! $processed_data ) {

			WC_Admin_Meta_Boxes::add_error( sprintf( __( 'Restriction #%s was not saved. Before saving a &quot;Payment Gateways&quot; restriction, remember to add at least one payment gateway to the exclusions list.', 'woocommerce-conditional-shipping-and-payments' ), $posted_data[ 'index' ] ) );
			return false;
		}

		return $processed_data;
	}

	/**
	 * Validate, process and return global settings.
	 *
	 * @param  array  $posted_data
	 * @return array
	 */
	public function process_admin_global_fields( $posted_data ) {

		$processed_data = $this->process_admin_fields( $posted_data );

		if ( ! $processed_data ) {

			WC_CSP_Admin_Notices::add_notice( sprintf( __( 'Restriction #%s was not saved. Before saving a &quot;Payment Gateways&quot; restriction, remember to add at least one payment gateway to the exclusions list.', 'woocommerce-conditional-shipping-and-payments' ), $posted_data[ 'index' ] ), 'error', true );
			return false;
		}

		return $processed_data;
	}

	/**
	 * Check if payment gateways template has started rendering.
	 *
	 * @param  string  $template_name
	 * @param  string  $template_path
	 * @param  string  $located
	 * @param  array   $args
	 *
	 * @return void
	 */
	public function before_payment_gateways_template( $template_name, $template_path, $located, $args ) {
		if ( 'checkout/payment.php' === $template_name ) {
			$this->before_payment_gateways_template++;
		}
	}

	/**
	 * Check if payment gateways template is done rendering.
	 *
	 * @param  string  $template_name
	 * @param  string  $template_path
	 * @param  string  $located
	 * @param  array   $args
	 *
	 * @return void
	 */
	public function after_payment_gateways_template( $template_name, $template_path, $located, $args ) {
		if ( 'checkout/payment.php' === $template_name ) {
			$this->after_payment_gateways_template++;
		}
	}

	/**
	 * Check if payment gateways template is currently rendering.
	 *
	 * @return boolean
	 */
	public function is_payment_gateways_template() {
		return $this->before_payment_gateways_template > $this->after_payment_gateways_template;
	}

	/**
	 * Shows a woocommerce error on the 'woocommerce_review_order_before_cart_contents' hook when payment gateway restrictions apply.
	 *
	 * @return void
	 */
	public function excluded_payment_gateways_notice() {

		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			return;
		}

		if ( ! apply_filters( 'woocommerce_csp_validate_payment_gateway_on_update_order_review', false ) ) {
			return;
		}

		$result = $this->validate_checkout( array() );

		if ( $result->has_messages() ) {
			foreach ( $result->get_messages() as $message ) {
				wc_add_notice( $message[ 'text' ], $message[ 'type' ] );
			}
		}
	}

	/**
	 * Render notice after excluded gateways.
	 *
	 * @since  1.7.2
	 * @param  string  $rate
	 * @param  string  $id
	 */
	public function add_notice_after_excluded_payment_gateway( $description, $id ) {

		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			return $description;
		}

		// Only add notice after payment gateway template has rendered.
		if ( ! $this->is_payment_gateways_template() ) {
			return $description;
		}

		$result = $this->validate_checkout( array(
			'check_gateway' => $id
		) );

		if ( $result->has_messages() ) {

			/**
			 * 'woocommerce_csp_restricted_payment_gateway_notice_classes' filter.
			 *
			 * @since  1.7.0
			 *
			 * @param  int  $id
			 */
			$classes = apply_filters( 'woocommerce_csp_restricted_payment_gateway_notice_classes', array( 'woocommerce-info', 'csp-payment-gateway-notice' ), $id );

			foreach ( $result->get_messages() as $message ) {
				$description .= '<div class="' . implode( ' ', $classes ) . '" style="margin: 1em 0 0;">' . $message[ 'text' ] . '</div>';
			}
		}

		return $description;
	}

	/**
	 * Filter payment gateways when restrictions apply.
	 *
	 * @param  array    $gateways
	 * @param  bool  $bypass
	 * @return array
	 */
	public function exclude_payment_gateways( $gateways, $bypass = false ) {

		if ( ! $bypass && ! is_checkout() && ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			return $gateways;
		}

		$args = array();
		$maps = array();

		if ( is_checkout_pay_page() ) {

			global $wp;

			if ( isset( $wp->query_vars[ 'order-pay' ] ) && ( $order = wc_get_order( (int) $wp->query_vars[ 'order-pay' ] ) ) ) {

				$args[ 'order' ]        = $order;
				$args[ 'include_data' ] = true;
			}
		}

		/* ----------------------------------------------------------------- */
		/* Product Restrictions
		/* ----------------------------------------------------------------- */

		if ( ! empty( $args[ 'order' ] ) ) {

			$order_items = $args[ 'order' ]->get_items( 'line_item' );

			if ( ! empty( $order_items ) ) {

				foreach ( $order_items as $order_item ) {

					$product_restriction_data = $this->get_product_restriction_data( $order_item[ 'product_id' ] );
					$map                      = $this->get_matching_rules_map( $product_restriction_data, $gateways, array_merge( $args, array( 'order_item_data' => $order_item ) ) );

					if ( ! empty( $map ) ) {
						$maps[] = $map;
					}
				}
			}

		} else {

			$cart_contents = WC()->cart->get_cart();

			if ( ! empty( $cart_contents ) ) {

				foreach ( $cart_contents as $cart_item_key => $cart_item_data ) {

					$product = $cart_item_data[ 'data' ];

					$product_restriction_data = $this->get_product_restriction_data( $product );
					$map                      = $this->get_matching_rules_map( $product_restriction_data, $gateways, array_merge( $args, array( 'cart_item_data' => $cart_item_data ) ) );

					if ( ! empty( $map ) ) {
						$maps[] = $map;
					}
				}
			}
		}

		/* ----------------------------------------------------------------- */
		/* Global Restrictions
		/* ----------------------------------------------------------------- */

		$global_restriction_data = $this->get_global_restriction_data();
		$maps[]                  = $this->get_matching_rules_map( $global_restriction_data, $gateways, $args );

		// Unset gateways.
		$ids_to_exclude = $this->get_unique_exclusion_ids( $maps );

		foreach ( $ids_to_exclude as $id ) {
			unset( $gateways[ $id ] );
		}

		return $gateways;
	}

	/**
	 * Generate map data for each active rule.
	 *
	 * @since  1.4.0
	 *
	 * @param  array  $payload
	 * @param  array  $restriction
	 * @param  bool   $include_data
	 * @return array
	 */
	protected function generate_rules_map_data( $payload, $restriction, $include_data ) {

		$data = array();

		foreach ( $payload as $gateway_id => $gateway ) {

			if ( $include_data && $this->is_restricted( $gateway, $restriction[ $this->restricted_key ] ) ) {
				$data[] = $gateway_id;
			}
		}

		return $data;
	}

	/**
	 * True if a gateway is excluded.
	 *
	 * @since  1.4.0
	 *
	 * @param  WC_Payment_Gateway  $gateway_id
	 * @param  array               $restricted_gateways
	 * @return bool
	 */
	private function is_restricted( $gateway, $restricted_gateways ) {
		return apply_filters( 'woocommerce_csp_payment_gateway_restricted', in_array( $gateway->id, $restricted_gateways ), $gateway, $restricted_gateways );
	}

	/**
	 * Validate order checkout and return WC_CSP_Check_Result object.
	 *
	 * @param  array  $posted
	 * @return WC_CSP_Check_Result
	 */
	public function validate_checkout( $posted ) {

		$result = new WC_CSP_Check_Result();
		$args   = array(
			'context'      => 'validation',
			'include_data' => true
		);

		$cart_contents      = WC()->cart->get_cart();
		$chosen_gateway     = WC()->session->get( 'chosen_payment_method' );
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		if ( empty( $available_gateways ) || ! $chosen_gateway || ! isset( $available_gateways[ $chosen_gateway ] ) ) {
			return $result;
		}

		if ( isset( $posted[ 'check_gateway' ] ) && isset( $available_gateways[ $posted[ 'check_gateway' ] ] ) ) {

			$chosen_gateway     = $posted[ 'check_gateway' ];
			$available_gateways = array( $posted[ 'check_gateway' ] => $available_gateways[ $chosen_gateway ] );

			$args[ 'context' ]       = 'check';
			$args[ 'check_gateway' ] = $chosen_gateway;

			unset( $args[ 'include_data' ] );
		}

		/* ----------------------------------------------------------------- */
		/* Product Restrictions
		/* ----------------------------------------------------------------- */

		if ( ! empty( $cart_contents ) ) {
			foreach ( $cart_contents as $cart_item_key => $cart_item_data ) {

				$product = $cart_item_data[ 'data' ];

				$product_restriction_data = $this->get_product_restriction_data( $product );
				$product_rules_map        = $this->get_matching_rules_map( $product_restriction_data, array( $chosen_gateway => $available_gateways[ $chosen_gateway ] ), array_merge( $args, array( 'cart_item_data' => $cart_item_data ) ) );

				foreach ( $product_rules_map as $rule_index => $excluded_gateway_ids ) {

					if ( ! empty( $excluded_gateway_ids ) ) {
						$result->add( 'payment_gateway_excluded_by_product_restriction', $this->get_resolution_message( $product_restriction_data[ $rule_index ], 'product', array_merge( $args, array( 'cart_item_data' => $cart_item_data ) ) ) );
					}
				}
			}
		}

		/* ----------------------------------------------------------------- */
		/* Global Restrictions
		/* ----------------------------------------------------------------- */

		// Grab global restrictions.
		$global_restriction_data = $this->get_global_restriction_data();
		$global_rules_map        = $this->get_matching_rules_map( $global_restriction_data, array( $chosen_gateway => $available_gateways[ $chosen_gateway ] ), $args );

		foreach ( $global_rules_map as $rule_index => $excluded_gateway_ids ) {

			if ( ! empty( $excluded_gateway_ids ) ) {
				$result->add( 'payment_gateway_excluded_by_global_restriction', $this->get_resolution_message( $global_restriction_data[ $rule_index ], 'global', $args ) );
			}
		}

		return $result;
	}

	/**
	 * Generate resolution message.
	 *
	 * @since  1.7.7
	 *
	 * @param  array   $restriction
	 * @param  string  $context
	 * @param  array   $args
	 * @return string
	 */
	protected function get_resolution_message_content( $restriction, $context, $args = array() ) {

		$message            = '';
		$chosen_gateway     = isset( $args[ 'check_gateway' ] ) ? $args[ 'check_gateway' ] : WC()->session->get( 'chosen_payment_method' );
		$available_gateways = isset( $args[ 'available_gateways' ] ) ? $args[ 'available_gateways' ] : WC()->payment_gateways->get_available_payment_gateways();
		$message_context    = isset( $args[ 'context' ] ) && 'check' === $args[ 'context' ] ? 'check' : 'validation';

		/**
		 * Filter title.
		 *
		 * @since  1.4.0
		 *
		 * @param  string              $title
		 * @param  WC_Payment_Gateway  $gateway
		 */
		$restricted_gateway_title = apply_filters( 'woocommerce_csp_restricted_payment_gateway_title', $available_gateways[ $chosen_gateway ]->title, $chosen_gateway );

		if ( 'product' === $context ) {

			$product = $args[ 'cart_item_data' ][ 'data' ];

			if ( ! empty( $restriction[ 'message' ] ) ) {

				$message 	= str_replace( array( '{product}', '{excluded_gateway}' ), array( '&quot;%1$s&quot;', '%2$s' ), $restriction[ 'message' ] );
				$resolution = '';

			} else {

				$conditions_resolution = $this->get_conditions_resolution( $restriction, $args );

				if ( $conditions_resolution ) {

					if ( 'check' === $message_context ) {
						$resolution = sprintf( __( 'To choose &quot;%1$s&quot;, please %2$s. Otherwise, please remove &quot;%3$s&quot; from your cart.', 'woocommerce-conditional-shipping-and-payments' ), $available_gateways[ $chosen_gateway ]->title, $conditions_resolution, $product->get_title() );
					} else {
						$resolution = sprintf( __( 'To purchase &quot;%3$s&quot; via &quot;%1$s&quot;, please %2$s. Otherwise, select an alternative payment method, or remove &quot;%3$s&quot; from your cart.', 'woocommerce-conditional-shipping-and-payments' ), $available_gateways[ $chosen_gateway ]->title, $conditions_resolution, $product->get_title() );
					}

				} else {

					if ( 'check' === $message_context ) {
						$resolution = '';
					} else {
						$resolution = sprintf( __( 'To complete your order, please select an alternative payment method, or remove &quot;%1$s&quot; from your cart.', 'woocommerce-conditional-shipping-and-payments' ), $product->get_title() );
					}
				}

				if ( 'check' === $message_context ) {
					$message = __( '&quot;%1$s&quot; cannot be checked out via &quot;%2$s&quot;. %3$s', 'woocommerce-conditional-shipping-and-payments' );
				} else {
					$message = __( 'Unfortunately, &quot;%1$s&quot; cannot be checked out via &quot;%2$s&quot;. %3$s', 'woocommerce-conditional-shipping-and-payments' );
				}
			}

			$message = sprintf( $message, $product->get_title(), $restricted_gateway_title, $resolution );

		} elseif ( 'global' === $context ) {

			if ( ! empty( $restriction[ 'message' ] ) ) {

				$message 	= str_replace( '{excluded_gateway}', '%1$s', $restriction[ 'message' ] );
				$resolution = '';

			} else {

				$conditions_resolution = $this->get_conditions_resolution( $restriction, $args );

				if ( $conditions_resolution ) {

					if ( 'check' === $message_context ) {
						$resolution = sprintf( __( 'To choose &quot;%1$s&quot;, please %2$s.', 'woocommerce-conditional-shipping-and-payments' ), $available_gateways[ $chosen_gateway ]->title, $conditions_resolution );
					} else {
						$resolution = sprintf( __( 'To complete your order via &quot;%1$s&quot;, please %2$s. Otherwise, choose an alternative payment method.', 'woocommerce-conditional-shipping-and-payments' ), $available_gateways[ $chosen_gateway ]->title, $conditions_resolution );
					}

				} else {

					if ( 'check' === $message_context ) {
						$resolution = '';
					} else {
						$resolution = __( 'To complete your order, please select an alternative payment method.', 'woocommerce-conditional-shipping-and-payments' );
					}
				}

				if ( 'check' === $message_context ) {
					$message = __( 'This order cannot be checked out via &quot;%1$s&quot;. %2$s', 'woocommerce-conditional-shipping-and-payments' );
				} else {
					$message = __( 'Unfortunately, your order cannot be checked out via &quot;%1$s&quot;. %2$s', 'woocommerce-conditional-shipping-and-payments' );
				}
			}

			$message = sprintf( $message, $restricted_gateway_title, $resolution );
		}

		return $message;
	}
}
