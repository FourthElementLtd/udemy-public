<?php

    /**
     * WC_Gateway_Sagepay_Direct class.
     *
     * @extends WC_Payment_Gateway_CC
     */
    class WC_Gateway_Sagepay_Direct extends WC_Payment_Gateway_CC {

    	var $default_tokens 				= 'no';
		var $default_tokens_message			= '';
		var $default_vendortxcodeprefix 	= 'wc_';
		var $default_postcode 			 	= '00000';

		var $failed_3d_secure_status		= array( 'NOTAUTHED', 'REJECTED', 'MALFORMED', 'INVALID', 'ERROR' );

		var $strict_3d_secure_status 		= array( 'OK' );
		var $relaxed_3d_secure_status 		= array( 'OK', 'ATTEMPTONLY', 'INCOMPLETE', 'NOAUTH', 'CANTAUTH' );

		var $default_sagemagicvalue 		= 'SUCCESSFUL';

        /**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {

            $this->id                   = 'sagepaydirect';
            $this->method_title         = __( 'Opayo Direct', 'woocommerce-gateway-sagepay-form' );
            $this->method_description   = __( 'Opayo Direct', 'woocommerce-gateway-sagepay-form' );
            $this->icon                 = apply_filters( 'wc_sagepaydirect_icon', '' );
            $this->has_fields           = true;

            $this->sagelinebreak 		= '0';

            $this->successurl 			= WC()->api_request_url( 'WC_Gateway_Sagepay_Direct' );

            $this->sagepay_version 		= OPAYOPLUGINVERSION;

	    	/**
	    	 * [$sage_cardtypes description]
	    	 * Set up accepted card types for card type drop down
	    	 * From Version 3.3.0
	    	 * @var array
	    	 *
	    	 * When using the wc_sagepaydirect_cardtypes filter DO NOT change the Key, only change the Value.
	    	 */
			$this->sage_cardtypes = apply_filters( 'wc_sagepaydirect_cardtypes', array(
        		'MasterCard'		=> __( 'MasterCard', 'woocommerce-gateway-sagepay-form' ),
				'MasterCard Debit'	=> __( 'MasterCard Debit', 'woocommerce-gateway-sagepay-form' ),
				'Visa'				=> __( 'Visa', 'woocommerce-gateway-sagepay-form' ),
				'Visa Debit'		=> __( 'Visa Debit', 'woocommerce-gateway-sagepay-form' ),
				'Discover'			=> __( 'Discover', 'woocommerce-gateway-sagepay-form' ),
				'Diners Club'		=> __( 'Diners Club', 'woocommerce-gateway-sagepay-form' ),
				'American Express' 	=> __( 'American Express', 'woocommerce-gateway-sagepay-form' ),
				'Maestro'			=> __( 'Maestro', 'woocommerce-gateway-sagepay-form' ),
				'JCB'				=> __( 'JCB', 'woocommerce-gateway-sagepay-form' ),
				'Laser'				=> __( 'Laser', 'woocommerce-gateway-sagepay-form' ),
				'PayPal'			=> __( 'PayPal', 'woocommerce-gateway-sagepay-form' ),
			) );

            // Load the form fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            // Get setting values
            $this->enabled					= $this->settings['enabled'];
            $this->title					= $this->settings['title'];
            $this->description				= $this->settings['description'];
            $this->vendor 					= $this->settings['vendor'];
            $this->status					= $this->settings['status'];
			$this->txtype					= $this->settings['txtype'];
			$this->cvv						= isset( $this->settings['applyavscv2'] ) ? $this->settings['applyavscv2'] : "0";
			$this->cvv_script				= true;
			$this->cardtypes				= !empty( $this->settings['cardtypes'] ) ? $this->settings['cardtypes'] : $this->sage_cardtypes;
			$this->secure					= isset( $this->settings['3dsecure'] ) ? $this->settings['3dsecure'] : "0";
			$this->threeDSMethod			= apply_filters( 'sagepay_direct_3dsmethod_iframe', FALSE );
			$this->threeDSMethod			= isset( $this->settings['threeDSMethod'] ) ? $this->settings['threeDSMethod'] : 1;
			$this->threeDS_tracking			= isset( $this->settings['3dsecure_tracking'] ) && $this->settings['3dsecure_tracking'] == '1' ? true : false;
			$this->secure_token				= isset( $this->settings['secure_token'] ) && $this->settings['secure_token'] == 'yes' ? true : false;
			$this->allowgiftaid 			= "0";
			$this->accounttype 				= "E";
			$this->billingagreement 		= "0";
			$this->debug					= isset( $this->settings['debug'] ) && $this->settings['debug'] == 'yes' ? true : false;
			$this->notification 			= isset( $this->settings['notification'] ) ? $this->settings['notification'] : get_bloginfo( 'admin_email' );
			$this->sagelinebreak			= isset( $this->settings['sagelinebreak'] ) ? $this->settings['sagelinebreak'] : "0";
			$this->defaultpostcode			= isset( $this->settings['defaultpostcode'] ) ? $this->settings['defaultpostcode'] : $this->default_postcode;
            $this->vendortxcodeprefix   	= isset( $this->settings['vendortxcodeprefix'] ) ? $this->settings['vendortxcodeprefix'] : $this->default_vendortxcodeprefix;

			$this->saved_cards 				= isset( $this->settings['tokens'] ) && $this->settings['tokens'] !== 'no' ? 'yes' : $this->default_tokens;
			$this->tokens_message 			= isset( $this->settings['tokensmessage'] ) ? $this->settings['tokensmessage'] : $this->default_tokens_message;

			$this->giftaid 					= isset( $this->settings['giftaid'] ) && $this->settings['giftaid'] !== 'no' ? 'yes' : 'no';
			$this->giftaid_message 			= isset( $this->settings['giftaidmessage'] ) ? $this->settings['giftaidmessage'] : '';

			$this->log_header 				= isset( $this->settings['log_header'] ) && $this->settings['log_header'] == '1' ? true : false;

			$this->sagelink					= 0;
            $this->sagelogo					= 0;

            $this->basketoption				= isset( $this->settings['basketoption'] ) ? $this->settings['basketoption'] : "1";

            // Setting to include transaction information in Admin email
            $this->sagepaytransinfo     	= isset( $this->settings['sagepaytransinfo'] ) && $this->settings['sagepaytransinfo'] == true ? $this->settings['sagepaytransinfo'] : false;

			// Make sure $this->vendortxcodeprefix is clean
            $this->vendortxcodeprefix 		= str_replace( '-', '_', $this->vendortxcodeprefix );

            // Magic value for testing 3D Secure 2.0
            $this->sagemagicvalue 			= isset( $this->settings['sagemagicvalue'] ) ? $this->settings['sagemagicvalue'] : $this->default_sagemagicvalue;

            // Template file name
            $this->template					= isset( $this->settings['template'] ) ? $this->settings['template'] : 'default';

           	// Sage urls
            if ( $this->status == 'live' ) {
            	// LIVE
				$this->purchaseURL 		= apply_filters( 'woocommerce_sagepay_direct_live_purchaseURL', 'https://live.sagepay.com/gateway/service/vspdirect-register.vsp' );
				$this->voidURL 			= apply_filters( 'woocommerce_sagepay_direct_live_voidURL', 'https://live.sagepay.com/gateway/service/void.vsp' );
				$this->refundURL 		= apply_filters( 'woocommerce_sagepay_direct_live_refundURL', 'https://live.sagepay.com/gateway/service/refund.vsp' );
				$this->releaseURL 		= apply_filters( 'woocommerce_sagepay_direct_live_releaseURL', 'https://live.sagepay.com/gateway/service/release.vsp' );
				$this->repeatURL 		= apply_filters( 'woocommerce_sagepay_direct_live_repeatURL', 'https://live.sagepay.com/gateway/service/repeat.vsp' );
				$this->testurlcancel	= apply_filters( 'woocommerce_sagepay_direct_live_testurlcancel', 'https://live.sagepay.com/gateway/service/cancel.vsp' );
				$this->authoriseURL 	= apply_filters( 'woocommerce_sagepay_direct_live_authoriseURL', 'https://live.sagepay.com/gateway/service/authorise.vsp' );
				$this->callbackURL 		= apply_filters( 'woocommerce_sagepay_direct_live_callbackURL', 'https://live.sagepay.com/gateway/service/direct3dcallback.vsp' );
				// Standalone Token Registration
				$this->addtokenURL		= apply_filters( 'woocommerce_sagepay_direct_live_addtokenURL', 'https://live.sagepay.com/gateway/service/directtoken.vsp' );
				// Removing a Token
				$this->removetokenURL	= apply_filters( 'woocommerce_sagepay_direct_live_removetokenURL', 'https://live.sagepay.com/gateway/service/removetoken.vsp' );
				// PayPal
				$this->paypalcompletion = apply_filters( 'woocommerce_sagepay_direct_live_paypalcompletion', 'https://live.sagepay.com/gateway/service/complete.vsp' );
			} else {
				// TEST
				$this->purchaseURL 		= apply_filters( 'woocommerce_sagepay_direct_test_purchaseURL', 'https://test.sagepay.com/gateway/service/vspdirect-register.vsp' );
				$this->voidURL 			= apply_filters( 'woocommerce_sagepay_direct_test_voidURL', 'https://test.sagepay.com/gateway/service/void.vsp' );
				$this->refundURL 		= apply_filters( 'woocommerce_sagepay_direct_test_refundURL', 'https://test.sagepay.com/gateway/service/refund.vsp' );
				$this->releaseURL 		= apply_filters( 'woocommerce_sagepay_direct_test_releaseURL', 'https://test.sagepay.com/gateway/service/release.vsp' );
				$this->repeatURL 		= apply_filters( 'woocommerce_sagepay_direct_test_repeatURL', 'https://test.sagepay.com/gateway/service/repeat.vsp' );
				$this->testurlcancel	= apply_filters( 'woocommerce_sagepay_direct_test_testurlcancel', 'https://test.sagepay.com/gateway/service/cancel.vsp' );
				$this->authoriseURL 	= apply_filters( 'woocommerce_sagepay_direct_test_authoriseURL', 'https://test.sagepay.com/gateway/service/authorise.vsp' );
				$this->callbackURL 		= apply_filters( 'woocommerce_sagepay_direct_test_callbackURL', 'https://test.sagepay.com/gateway/service/direct3dcallback.vsp' );
				// Standalone Token Registration
				$this->addtokenURL		= apply_filters( 'woocommerce_sagepay_direct_test_addtokenURL', 'https://test.sagepay.com/gateway/service/directtoken.vsp' );
				// Removing a Token
				$this->removetokenURL	= apply_filters( 'woocommerce_sagepay_direct_test_removetokenURL', 'https://test.sagepay.com/gateway/service/removetoken.vsp' );
				// PayPal
				$this->paypalcompletion = apply_filters( 'woocommerce_sagepay_direct_test_paypalcompletion', 'https://test.sagepay.com/gateway/service/complete.vsp' );
			}

			// 3D iframe
            $this->iframe_3d_callback   = esc_url( SAGEPLUGINURL . 'assets/pages/3dcallback.php' );
            $this->iframe_3d_redirect   = esc_url( SAGEPLUGINURL . 'assets/pages/3dredirect.php' );

            // Set vpsprotocol
            $this->vpsprotocol			= isset( $this->settings['vpsprotocol'] ) ? $this->settings['vpsprotocol'] : '4.00';

            // Check $this->secure for 3D Secure 2.0
            if( ( $this->secure == '2' || $this->secure == '3' ) && $this->vpsprotocol == '4.00' ) {
				$this->secure = '0';
			}

            // ReferrerID
            $this->referrerid 			= 'F4D0E135-F056-449E-99E0-EC59917923E1';

            // Supports
            $this->supports 			= array(
            									'products',
            									'refunds',
												'subscriptions',
								                'subscription_cancellation', 
								                'subscription_suspension', 
								                'subscription_reactivation',
								                'subscription_amount_changes',
								                'subscription_date_changes',
								                'subscription_payment_method_change',
								                'subscription_payment_method_change_customer',
								                'subscription_payment_method_change_admin',
								                'multiple_subscriptions',
            									'pre-orders'
										);

           	// Unset tokenisation if tokens option is "no"
           	if( $this->saved_cards == 'yes' ) {
           		$this->supports[] = 'tokenization';
           	}

			// Add test card info to the description if in test mode
			if ( $this->status != 'live' ) {
				$this->description .= ' ' . sprintf( __( '<br />TEST MODE ENABLED.<br />In test mode, you can use Visa card number 4929000000006 with any CVC and a valid expiration date or check the documentation (<a href="%s">Test card details for your test transactions</a>) for more card numbers.', 'woocommerce-gateway-sagepay-form' ), 'http://www.sagepay.co.uk/support/12/36/test-card-details-for-your-test-transactions' );
				$this->description  = trim( $this->description );
			}

			// SSL Check
			$sagepaydirect_ssl_nag_dismissed = get_option( 'sagepaydirect-ssl-nag-dismissed' );
			if( empty( $sagepaydirect_ssl_nag_dismissed ) || $sagepaydirect_ssl_nag_dismissed != '1' ) {
				add_action( 'admin_notices', array( $this, $this->id . '_ssl_check') );
			}

			// Scripts
			add_action( 'wp_enqueue_scripts', array( $this, $this->id . '_scripts' ) );

			// WC version
			$this->wc_version = get_option( 'woocommerce_version' );

			// Add 'Authorised' to needs_payment
			// $valid_order_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( 'pending', 'failed' ), $this );
			add_filter( 'woocommerce_valid_order_statuses_for_payment', array( $this, 'needs_payment' ), 10, 2 );

			// Remove Pay button from My Account page for 'Authorized' orders
			// apply_filters( 'woocommerce_my_account_my_orders_actions', $actions, $order );
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'remove_authorized_my_account' ), 10, 2 );

			// Hooks
			add_action( 'woocommerce_api_wc_gateway_sagepay_direct', array( $this, 'check_sagepaydirect_response' ) );
			
			add_action( 'woocommerce_receipt_' . $this->id, array($this, 'authorise_3dsecure') );

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

			// Capture authorised payments
			add_action ( 'woocommerce_order_action_opayo_process_payment', array( $this, 'process_pre_order_release_payment' ) );

			// Void payments
			add_action ( 'woocommerce_order_action_opayo_process_void', array( $this, 'process_void_payment' ) );

            // Pre-Orders
            if ( class_exists( 'WC_Pre_Orders_Order' ) ) {
                add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array( $this, 'process_pre_order_release_payment' ) );
            }

            // Subscriptions
            if ( class_exists( 'WC_Subscriptions_Order' ) ) {
                
                add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'process_scheduled_subscription_payment' ), 10, 2 );
                add_filter( 'wcs_renewal_order_meta_query', array( $this, 'remove_renewal_order_meta' ), 10, 3 );

                // display the credit card used for a subscription in the "My Subscriptions" table
                add_filter( 'woocommerce_my_subscriptions_payment_method', array( $this, 'maybe_render_subscription_payment_method' ), 10, 2 );

                add_action( 'woocommerce_subscriptions_changed_failing_payment_method_sagepaydirect', array( $this, 'update_failing_payment_method' ), 10, 3 );

                // Turn off "Update all subscriptions" option
                add_filter( "woocommerce_subscriptions_update_payment_via_pay_shortcode", array( $this, "remove_woocommerce_subscriptions_update_payment_via_pay_shortcode" ) );

                // 
                add_filter( 'woocommerce_subscription_update_subscription_token', array( $this, 'update_subscription_token' ), 10, 4 );
				
				add_action( 'woocommerce_subscription_token_changed', array( $this, 'subscription_token_changed' ), 10, 3 );

            }

            // Redirect paid orders
            add_action( 'before_woocommerce_pay', array( $this, 'redirect_paid_orders' ) );
			
			// Show any stored error messages
			add_action( 'woocommerce_before_checkout_form', array( $this, 'show_errors' ) );
			add_action( 'woocommerce_subscription_details_table', array( $this, 'show_errors' ), 1 );

			// Make sure the cart empties!
			add_action( 'woocommerce_payment_complete', array( $this, 'clear_cart' ) );

			// Allow sites to remove the CVV box from the checkout form for token payments
			// If you use this filter you may need to modify the request sent to Opayo using $data = apply_filters( 'woocommerce_sagepay_direct_data', $data, $order );
			$this->override_opayo_cvv_requirement = apply_filters( 'override_opayo_cvv_requirement', TRUE );

        } // END __construct

		/**
		 * Check if this gateway is enabled
		 */
		public function is_available() {

			if ( $this->enabled == "yes" ) {

				if ( !$this->is_secure() && ! $this->status == 'live' ) {
					return false;
				}

				// Required fields check
				if ( ! $this->vendor ) {
					return false;
				}

				return true;

			}
			return false;

		}

		/**
    	 * Payment form on checkout page
    	 */
		public function payment_fields() {

			// Clear out the session variables for 3D Direct
			WC()->session->set( "MD", '' );
        	WC()->session->set( "ACSURL", '' );
        	WC()->session->set( "PAReq", '' );
        	WC()->session->set( "TermURL", '' );
        	WC()->session->set( "Complete3d", '' );

			// Load script for 3DS 2.0
			$this->threeds_script();
                	
			$display_tokenization = $this->supports( 'tokenization' ) && is_checkout() && $this->saved_cards == 'yes';

			if ( is_add_payment_method_page() ) {
				$pay_button_text = __( 'Add Card', 'woocommerce-gateway-sagepay-form' );
			} else {
				$pay_button_text = '';
			}

			echo '<div id="sagepaydirect-payment-data">';

			if ( $this->description ) {
				echo apply_filters( 'wc_sagepaydirect_description', wp_kses_post( $this->description ) );
			}

			// Add tokenization script
			if ( $display_tokenization && class_exists( 'WC_Payment_Token_CC' ) ) {
				// Add script to remove card fields if CVV required with tokens
				if( ( $this->cvv_script && $this->override_opayo_cvv_requirement ) || ( $this->vpsprotocol == '4.00' && $this->override_opayo_cvv_requirement ) ) {
					$this->cvv_script();
				} else {
					$this->tokenization_script();
				}
				
				$this->saved_payment_methods();
			}

			// Add script to remove card fields if card type == PayPal
			$this->paypal_script();
			
			// Use our own payment fields
			$filename = apply_filters( 'woocommerce_sage_credit_card_filename', $this->template . '_credit-card-form.php' );

			$args = array( 
				'gateway_id' 	 => $this->id,
				'cardtypes'	 	 => $this->cardtypes,
				'sage_cardtypes' => $this->sage_cardtypes
			);

			$template = wc_get_template( $filename, $args, '', SAGEPLUGINPATH . 'assets/templates/direct/' );

			if ( $this->giftaid == 'yes' ) {
				$this->giftaid_checkbox();
			}

			if ( $display_tokenization && class_exists( 'WC_Payment_Token_CC' ) ) {
				$this->save_payment_method_checkbox();
			}

			echo '</div>';

			if( $this->vpsprotocol == '4.00' ) {
?>
				<script type="text/javascript" language="javascript">

				    var browserUserAgent = function () {
				        return (navigator.userAgent || null);
				    };

				    var browserLanguage = function () {
				        return (navigator.language || navigator.userLanguage || navigator.browserLanguage || navigator.systemLanguage || 'en-gb');
				    };

				    var browserColorDepth = function () {
				    	var acceptedValues = [1,4,8,15,16,24,32,48];
				        if (screen.colorDepth || window.screen.colorDepth) {

				            colorDepth = (screen.colorDepth || window.screen.colorDepth);
				            var returnValue = acceptedValues.indexOf( colorDepth );

				            if( returnValue >= 0 ) {
				            	return colorDepth;
				            }

				            // Fallback	
				            return 32;
				            
				        }
				        return 32;
				    };

				    var browserScreenHeight = function () {
				        if (window.screen.height) {
				            return new String(window.screen.height);
				        }
				        return null;
				    };

				    var browserScreenWidth = function () {
				        if (window.screen.width) {
				            return new String(window.screen.width);
				        }
				        return null;
				    };

				    var browserTZ = function () {
				        return new String(new Date().getTimezoneOffset());
				    };

				    var browserJavaEnabled = function () {
				        return (navigator.javaEnabled() || null);
				    };

				    var browserJavascriptEnabled = function () {
				        return (true);
				    };

					var sageform = document.getElementById( "sagepaydirect-cc-form" );

					function createHiddenInput( form, name, value ) {

						var input = document.createElement("input");
						input.setAttribute( "type", "hidden" );
						input.setAttribute( "name", name ); 
						input.setAttribute( "value", value );
						form.appendChild( input);

					}

					if ( sageform != null ) {

				        createHiddenInput( sageform, 'browserJavaEnabled', browserJavaEnabled() );
				        createHiddenInput( sageform, 'browserJavascriptEnabled', browserJavascriptEnabled() );
				        createHiddenInput( sageform, 'browserLanguage', browserLanguage() );
				        createHiddenInput( sageform, 'browserColorDepth', browserColorDepth() );
				        createHiddenInput( sageform, 'browserScreenHeight', browserScreenHeight() );
				        createHiddenInput( sageform, 'browserScreenWidth', browserScreenWidth() );
				        createHiddenInput( sageform, 'browserTZ', browserTZ() );
				        createHiddenInput( sageform, 'browserUserAgent', browserUserAgent() );

					}

				</script>
<?php
			}


		}

		/**
		 * Use a custom giftaid_checkbox to include a description from the settings
		 * @return [type] [description]
		 */
		public function giftaid_checkbox() {

			$this->giftaid_script();
        	
			echo sprintf(
				'<p class="form-row woocommerce-giftaid">
					<input id="wc-%1$s-gift-aid" name="wc-%1$s-gift-aid" type="checkbox" value="true" style="width:auto;" />
					<label for="wc-%1$s-gift-aid" style="display:inline;">%2$s</label><br />
					%3$s
				</p>',
				esc_attr( $this->id ),
				esc_html__( 'Gift Aid', 'woocommerce' ),
				apply_filters( 'wc_sagepaydirect_giftaid_message', wp_kses_post( $this->giftaid_message ) )
			);
		}

		/**
		 * Use a custom save_payment_method_checkbox to include a description from the settings
		 * @return [type] [description]
		 */
		public function save_payment_method_checkbox() {
        	
			echo sprintf(
				'<p class="form-row woocommerce-SavedPaymentMethods-saveNew">
					<input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;" />
					<label for="wc-%1$s-new-payment-method" style="display:inline;">%2$s</label><br />
					%3$s
				</p>',
				esc_attr( $this->id ),
				esc_html__( 'Save to Account', 'woocommerce' ),
				apply_filters( 'wc_sagepaydirect_tokens_message', wp_kses_post( $this->tokens_message ) )
			);
		}

		/**
    	 * Validate the payment form
    	 */
		public function validate_fields() {
        	
			try {

				$sage_card_type 		= isset($_POST[$this->id . '-card-type']) ? wc_clean($_POST[$this->id . '-card-type']) : '';
				$sage_card_number 		= isset($_POST[$this->id . '-card-number']) ? wc_clean($_POST[$this->id . '-card-number']) : '';
				$sage_card_cvc 			= isset($_POST[$this->id . '-card-cvc']) ? wc_clean($_POST[$this->id . '-card-cvc']) : '';
				$sage_card_expiry		= isset($_POST[$this->id . '-card-expiry']) ? wc_clean($_POST[$this->id . '-card-expiry']) : false;
				$sage_card_expiry_mon	= isset($_POST[$this->id . '-card-expiry-month']) ? wc_clean($_POST[$this->id . '-card-expiry-month']) : false;
				$sage_card_expiry_year	= isset($_POST[$this->id . '-card-expiry-year']) ? wc_clean($_POST[$this->id . '-card-expiry-year']) : false;
				$sage_card_save_token	= isset($_POST['wc-sagepaydirect-new-payment-method']) ? wc_clean($_POST['wc-sagepaydirect-new-payment-method']) : false;
				$sage_card_token 		= isset($_POST['wc-sagepaydirect-payment-token']) ? wc_clean($_POST['wc-sagepaydirect-payment-token']) : false;

				/**
				 * Check if we need to validate card form
				 */
				if( strtoupper($sage_card_type) == 'PAYPAL' ) {
					// No validation required for PayPal
					return true;

				} elseif ( $sage_card_token === false || $sage_card_token === 'new' ) {
					// Normal card transaction
					// Format values
					$sage_card_number    	= str_replace( array( ' ', '-' ), '', $sage_card_number );

					// Allow for old template file which uses text box for card expiry date
					if( $sage_card_expiry ) {
						$sage_card_exp_month 	= $this->get_card_expiry_date( $sage_card_expiry, 'month' );
						$sage_card_exp_year 	= $this->get_card_expiry_date( $sage_card_expiry, 'year' );
					} else {
						$sage_card_exp_month 	= $sage_card_expiry_mon;
						$sage_card_exp_year 	= $this->get_card_expiry_date( $sage_card_expiry_year, 'year' );
					}

					// Validate values
					if ( empty( $sage_card_type ) || ctype_digit( $sage_card_type ) || !in_array( $sage_card_type, $this->cardtypes ) ) {
						throw new Exception( __( 'Please choose a card type', 'woocommerce-gateway-sagepay-form' ) );
					}

					if ( ( $this->override_opayo_cvv_requirement && !ctype_digit( $sage_card_cvc ) ) || ( $this->override_opayo_cvv_requirement && strlen( $sage_card_cvc ) < 3 ) || ( $this->override_opayo_cvv_requirement && strlen( $sage_card_cvc ) > 4 ) ) {
						throw new Exception( __( 'Card security code is invalid (only digits are allowed)', 'woocommerce-gateway-sagepay-form' ) );
					}

					if ( !ctype_digit( $sage_card_exp_month ) || $sage_card_exp_month > 12 || $sage_card_exp_month < 1 ) {
						throw new Exception( __( 'Card expiration month is invalid', 'woocommerce-gateway-sagepay-form' ) );
					}	

					if ( !ctype_digit( $sage_card_exp_year ) || $sage_card_exp_year < date('y') || strlen($sage_card_exp_year) != 2 ) {
						throw new Exception( __( 'Card expiration year is invalid', 'woocommerce-gateway-sagepay-form' ) );
					}

					if ( empty( $sage_card_number ) || ! ctype_digit( $sage_card_number ) ) {
						throw new Exception( __( 'Card number is invalid', 'woocommerce-gateway-sagepay-form' ) );
					}

					return true;

				} elseif( $this->cvv_script && $sage_card_token !== false && $this->override_opayo_cvv_requirement ) {

					// Token transaction requiring the CVV number
					if ( !ctype_digit( $sage_card_cvc ) || strlen( $sage_card_cvc ) < 3  || strlen( $sage_card_cvc ) > 4 ) {
						throw new Exception( __( 'Card security code is invalid (only digits are allowed)', 'woocommerce-gateway-sagepay-form' ) );
					}
					return true;

				} else {

					return true;
					
				}

			} catch( Exception $e ) {

				if( is_callable( 'wc_add_notice' ) ) {
					wc_add_notice( $e->getMessage(), 'error' );
				}
				return false;

			}

		}

		/**
		 * Process the payment and return the result
		 */
		public function process_payment( $order_id ) {

        	$order  = wc_get_order( $order_id ); 

			// Let's make sure the order hasn't been paid for already!
        	if ( !$this->opayo_needs_payment( $order ) ) {
				
                // Add Order Note
                $this->add_order_note( __('Payment redirection failed. process_payment', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

                $return_url = $this->append_url( $order->get_checkout_order_received_url() );

                $this->opayo_redirect( $return_url );

            }

    		include_once( 'sagepay-direct-process-class.php' );
			$response = new Sagepay_Direct_Process( $order_id );

			$processed = $response->process();

			return $processed;
	
		}

        /**
         * Authorise 3D Secure payments
         * 
         * @param int $order_id
         */
        function authorise_3dsecure( $order_id ) {

        	$order  = wc_get_order( $order_id ); 

			// Let's make sure the order hasn't been paid for already!
        	if ( !$this->opayo_needs_payment( $order ) ) {
				
                // Add Order Note
                $this->add_order_note( __('Payment redirection failed. authorise_3dsecure', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

                $return_url = $this->append_url( $order->get_checkout_order_received_url() );

                $this->opayo_redirect( $return_url );

            }

    		include_once( 'sagepay-direct-3dsecure-protocol4-class.php' );
        	$response = new Sagepay_Direct_3DSecure_4( $order_id );

        	$result = $response->authorise();

        } // end auth_3dsecure

        /**
         * process_response
         *
         * take the reponse from Sage and do some magic things.
         * 
         * @param  [type] $sageresult [description]
         * @param  [type] $order      [description]
         * @return [type]             [description]
         */
        function process_response( $sageresult, $order ) {

			// Let's make sure the order hasn't been paid for already!
        	if ( !$this->opayo_needs_payment( $order ) ) {
				
                // Add Order Note
                $this->add_order_note( __('Payment redirection failed. process_response', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

                $return_url = $this->append_url( $order->get_checkout_order_received_url() );

                $this->opayo_redirect( $return_url );

            }

        	include_once( 'sagepay-direct-response-class.php' );
    		$response = new Sagepay_Direct_Response( $sageresult, $order );

    		$order_id = $order->get_id();
    		
    		// Clean up Order Meta
    		$delete_card_details = apply_filters( 'opayo_delete_sanitized_card_details', true, $order_id );
    		if( $delete_card_details ) {
            	delete_post_meta( $order_id, '_SagePaySantizedCardDetails' );
            }
            
            delete_post_meta( $order_id, '_sage_3dsecure' );

            $return = $response->process();

            return $return;

        }

        /**
         * [process_scheduled_subscription_payment description]
         * @param  [type] $amount_to_charge [description]
         * @param  [type] $order            [description]
         * @return [type]                   [description]
         */
        function process_scheduled_subscription_payment( $amount_to_charge, $order ) {

        	// Let's make sure the order hasn't been paid for already!
        	if ( $this->opayo_needs_payment($order) ) {
				include_once( 'sagepay-direct-subscriptions-class.php' );
	    		$response = new Sagepay_Direct_Subcription_Renewals( $amount_to_charge, $order );

	    		$response->process_scheduled_payment();
	    	}

        }
        
        /**
         * [remove_renewal_order_meta description]
         * @param  [type] $order_meta_query  [description]
         * @param  [type] $original_order_id [description]
         * @param  [type] $renewal_order_id  [description]
         * @param  [type] $new_order_role    [description]
         * @return [type]                    [description]
         */
        public function remove_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role = NULL ) {
            if ( 'parent' == $new_order_role ) {
                $order_meta_query .= " AND `meta_key` NOT IN ( '_VPSTxId', '_SecurityKey', '_TxAuthNo', '_RelatedVPSTxId', '_RelatedSecurityKey', '_RelatedTxAuthNo', '_CV2Result', '_3DSecureStatus' ) ";
            }
            return $order_meta_query;
        }

        /**
         * Update the customer_id for a subscription after using SagePay to complete a payment to make up for
         * an automatic renewal payment which previously failed.
         *
         * @access public
         * @param WC_Order $original_order The original order in which the subscription was purchased.
         * @param WC_Order $renewal_order The order which recorded the successful payment (to make up for the failed automatic payment).
         * @param string $subscription_key A subscription key of the form created by @see WC_Subscriptions_Manager::get_subscription_key()
         * @return void
         */
        public function update_failing_payment_method( $original_order, $renewal_order, $subscription_key ) {

            update_post_meta( $original_order->get_id(), '_SagePayDirectToken', get_post_meta( $new_renewal_order->get_id(), '_SagePayDirectToken', true ) );

            update_post_meta( $original_order->get_id(), '_RelatedVPSTxId', get_post_meta( $new_renewal_order->get_id(), '_RelatedVPSTxId', true ) );
            update_post_meta( $original_order->get_id(), '_RelatedVendorTxCode', get_post_meta( $new_renewal_order->get_id(), '_RelatedVendorTxCode', true ) );
            update_post_meta( $original_order->get_id(), '_RelatedSecurityKey', get_post_meta( $new_renewal_order->get_id(), '_RelatedSecurityKey', true ) );
            update_post_meta( $original_order->get_id(), '_RelatedTxAuthNo', get_post_meta( $new_renewal_order->get_id(), '_RelatedTxAuthNo', true ) );

        }

		/**
         * build_query
         *
         * Build query for SagePay
         * 
         * @param  [type] $order 		 [description]
         * @param  [type] $card_form     [description]
         * @return [type]             	 [description]
         */
		function build_query( $order, $card_form, $requirement = 'standard' ) {
        	
    		include_once( 'sagepay-direct-request-class.php' );

    		$request = new Sagepay_Direct_Request( $order, $card_form, $requirement );

    		return $request->build_request();

		}

		/**
		 * Send the info to Sage for processing
		 * https://test.sagepay.com/showpost/showpost.asp
		 */
        function sagepay_post( $data, $url ) {

        	if( isset( $this->log_header ) && $this->log_header ) {
				WC_Sagepay_Common_Functions::sagepay_debug( $_SERVER, 'Opayo_SERVER', __('Logging SERVER : ', 'woocommerce-gateway-sagepay-form'), TRUE );
			}

        	if( $this->status == 'developer' ) {
				$url = 'https://woocommerce-sagepay.com/posttest/postman.php';
        	}

        	// Debugging
        	if ( $this->debug == true || $this->status != 'live' ) {
        		$to_log['DATA'] = $data;
        		$to_log['URL'] 	= $url;
	  			WC_Sagepay_Common_Functions::sagepay_debug( $to_log, $this->id, __('Sent to Opayo : ', 'woocommerce-gateway-sagepay-form'), TRUE );
			}

			// Convert $data array to query string for Sage
        	if( is_array( $data) ) {
        		// Convert the $data array for Sage
	            $data = http_build_query( $data, '', '&' );
        	}

        	$params = array(
							'method' 		=> 'POST',
							'timeout' 		=> apply_filters( 'woocommerce_opayo_post_timeout', 45 ),
							'httpversion' 	=> '1.1',
							'headers' 		=> array('Content-Type'=> 'application/x-www-form-urlencoded'),
							'body' 			=> $data,
							// 'sslverify' 	=> false
						);

			$res = wp_remote_post( $url, $params );

			if( is_wp_error( $res ) ) {

				// Debugging
  				if ( $this->debug == true || $this->status != 'live' ) {
  					WC_Sagepay_Common_Functions::sagepay_debug( $res->get_error_message(), $this->id, __('Remote Post Error : ', 'woocommerce-gateway-sagepay-form'), FALSE );
				}

			} else {

				// Debugging
				if ( $this->debug == true || $this->status != 'live' ) {
					WC_Sagepay_Common_Functions::sagepay_debug( $res['body'], $this->id, __('Opayo Direct Return : ', 'woocommerce-gateway-sagepay-form'), FALSE );
				}

				return $this->sageresponse( $res['body'] );

			}

        }

        /**
         * [add_order_note description]
         * @param [type] $message [description]
         * @param [type] $result  [description]
         * @param [type] $order   [description]
         */
        function add_order_note( $message, $result, $order ) {
        	
        	$ordernote = '';

        	if( is_array($result) ) {

				foreach ( $result as $key => $value ) {
					$ordernote .= $key . ' : ' . $value . "\r\n";
				}

			} else {
				$ordernote = $result;
			}    	 

			$order->add_order_note( $message . '<br />' . $ordernote );

		}

		/**
		 * update_order_meta
		 * 
		 * Update order meta
		 * 
		 * @param  [type] $result 	[description]
		 * @param  [type] $order_id [description]
		 */
		function update_order_meta( $result, $order_id ) {
        	
			// Add all of the info from sage as 
        	if( is_array($result) ) {

        		if( isset( $result['Token'] ) ) {
        			$result['SagePayDirectToken'] = $result['Token'];
        			unset( $result['Token'] );
        		}

        		$result['RelatedVPSTxId'] 		= isset( $result['VPSTxId'] ) ? str_replace( array('{','}'),'',$result['VPSTxId'] ) : NULL;
        		$result['RelatedSecurityKey'] 	= isset( $result['SecurityKey'] ) ? $result['SecurityKey'] : NULL;
        		$result['RelatedTxAuthNo'] 	  	= isset( $result['TxAuthNo'] ) ? $result['TxAuthNo'] : NULL;

				foreach ( $result as $key => $value ) {
					update_post_meta( $order_id, '_'.$key , $value );
				}

			}

		}

		/**
		 * [update_subscription_meta_maybe description]
		 * @param  [type] $result   [description]
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		function update_subscription_meta_maybe( $result, $order_id ) {

			$order = wc_get_order( $order_id );

			// Update Subscription with result from Opayo if necessary
			if( class_exists( 'WC_Subscriptions' ) ) {

				// Get the $SagePayDirectToken from the order
				$SagePayDirectToken = get_post_meta( $order_id, '_SagePayDirectToken', TRUE );

				// Get the $RelatedVendorTxCode from the order
				$RelatedVendorTxCode = get_post_meta( $order_id, '_RelatedVendorTxCode', TRUE );

				// Get the subscriptions for this order
				$subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => array( 'parent' ) ) );

				if( count( $subscriptions ) >= 1 ) {

					foreach( $subscriptions as $subscription ) {

						$subscription_id = $subscription->get_id();

						if( isset( $SagePayDirectToken ) && $SagePayDirectToken != '' ) {
		        			update_post_meta( $subscription_id, '_SagePayDirectToken', $SagePayDirectToken );
		        		}

		        		if( isset( $result['VPSTxId'] ) ) {
		        			update_post_meta( $subscription_id, '_RelatedVPSTxId', str_replace( array('{','}'),'',$result['VPSTxId'] ) );
		        		}

		        		if( isset( $RelatedVendorTxCode ) && $RelatedVendorTxCode != '' ) {
		        			update_post_meta( $subscription_id, '_RelatedVendorTxCode', $RelatedVendorTxCode );
		        		}

		        		if( isset( $result['SecurityKey'] ) ) {
		        			update_post_meta( $subscription_id, '_RelatedSecurityKey', $result['SecurityKey'] );
		        		}

		        		if( isset( $result['TxAuthNo'] ) ) {
		        			update_post_meta( $subscription_id, '_RelatedTxAuthNo', $result['TxAuthNo'] );
		        		}

                        // Free Trial check
		        		$trial_period = get_post_meta( $subscription_id, '_trial_period', TRUE );

		        		if( $trial_period && $trial_period != '' && $order->get_total() == 0 ) {

		        			// Set the data needed to release this amount later
	                        $opayo_free_trial = array( 
	                            "TxType"        => "AUTHENTICATE",
	                            "VendorTxCode"  => $RelatedVendorTxCode,
	                            "VPSProtocol"   => isset( $result['VPSProtocol'] ) ? $result['VPSProtocol'] : '',
	                            "VPSTxId"       => isset( $result['VPSTxId'] ) ? $result['VPSTxId'] : '',
	                            "SecurityKey"   => isset( $result['SecurityKey'] ) ? $result['SecurityKey'] : '',
	                            "TxAuthNo"      => isset( $result['TxAuthNo'] ) ? $result['TxAuthNo'] : '',
	                        );
	                        
	                        update_post_meta( $subscription_id, '_opayo_free_trial', $opayo_free_trial );

	                    }

	                    // Syncronised subscription check
	                    $synced_sub = get_post_meta( $subscription_id, '_contains_synced_subscription', TRUE );

	                    if( $synced_sub && $synced_sub != '' && $order->get_total() == 0 ) {

		        			// Set the data needed to release this amount later
	                        $opayo_free_trial = array( 
	                            "TxType"        => "AUTHENTICATE",
	                            "VendorTxCode"  => $RelatedVendorTxCode,
	                            "VPSProtocol"   => isset( $result['VPSProtocol'] ) ? $result['VPSProtocol'] : '',
	                            "VPSTxId"       => isset( $result['VPSTxId'] ) ? $result['VPSTxId'] : '',
	                            "SecurityKey"   => isset( $result['SecurityKey'] ) ? $result['SecurityKey'] : '',
	                            "TxAuthNo"      => isset( $result['TxAuthNo'] ) ? $result['TxAuthNo'] : '',
	                        );
	                        
	                        update_post_meta( $subscription_id, '_opayo_free_trial', $opayo_free_trial );

	                    }
					}
				}

			}

		}

		/**
		 * sagepay_message
		 * 
		 * return checkout messages / errors
		 * 
		 * @param  [type] $message [description]
		 * @param  [type] $type    [description]
		 * @return [type]          [description]
		 */
		function sagepay_message( $message, $type, $order_id = NULL ) {
        	global $woocommerce;
			if( is_callable( 'wc_add_notice') ) {
				if( $order_id ) {
					update_post_meta( $order_id, '_sagepay_errors', array( 'message'=>$message, 'type'=>$type ) );
				} else {
					wc_add_notice( $message, $type );
				}
			}

		}

		/**
		 * sageresponse
		 *
		 * take response from Sage and process it into an array
		 * 
		 * @param  [type] $array [description]
		 * @return [type]        [description]
		 */
		function sageresponse( $array ) {
        	
			$response 		= array();
			$sagelinebreak 	= $this->sage_line_break( $this->sagelinebreak );
            $results  		= preg_split( $sagelinebreak, $array );

            foreach( $results as $result ){ 

            	$value = explode( '=', $result, 2 );
                $response[trim($value[0])] = trim($value[1]);

            }

            return $response;

		}

    	/**
    	 * Sage has specific requirements for the credit card type field
    	 * @param  [type] $cardNumber   [description]
    	 * @param  [type] $card_details [description]
    	 * @return [type]               [Card Type]
    	 */
		public static function cc_type( $cardNumber, $card_details ) {
        	
			$replace = array(
							'VISAELECTRON' 					=> 'UKE',
							'VISAPURCHASING'				=> 'VISA',
							'VISADEBIT' 					=> 'DELTA',
							'VISACREDIT' 					=> 'VISA',
							'MASTERCARDDEBIT' 				=> 'MCDEBIT',
							'MASTERCARDCREDIT' 				=> 'MC',
							'MasterCard Debit'				=> 'MCDEBIT',
							'MasterCard Credit'				=> 'MC',
							'MasterCard'					=> 'MC',
							'Visa Debit'					=> 'DELTA',
							'Visa Credit'					=> 'VISA',
							'Visa'							=> 'VISA',
							'Discover'						=> 'DC',
							'Diners Club'					=> 'DC',
							'American Express' 				=> 'AMEX',
							'Maestro'						=> 'MAESTRO',
							'JCB'							=> 'JCB',
							'Laser'							=> 'LASER',
							'PayPal'						=> 'PAYPAL'
			);

			$replace = apply_filters( 'woocommerce_sagepay_direct_cardtypes_array', $replace );

			// Clean up the card_details in to Sage format
			$card_details = self::str_replace_assoc( $replace,$card_details );

			return $card_details;

    	}

    	/**
    	 * Sage has specific requirements for the credit card type field
    	 * @param  [type] $cardNumber   [description]
    	 * @param  [type] $card_details [description]
    	 * @return [type]               [Card Type]
    	 */
		public static function cc_type_name( $cc_type ) {
        	
			$replace = array(
							'UKE' 		=> 'Electron',
							'DELTA' 	=> 'Visa Debit',
							'VISA' 		=> 'Visa Credit',
							'VISA'		=> 'Visa',
							'MCDEBIT' 	=> 'Mastercard Debit',
							'MC'	 	=> 'MasterCard Credit',
							'MC' 		=> 'Mastercard',
							'DC'		=> 'Discover',
							'DC'		=> 'Diners Club',
							'AMEX' 		=> 'AMEX',
							'MAESTRO'	=> 'Maestro',
							'JCB'		=> 'JCB',
							'LASER'		=> 'Laser',
							'PAYPAL'	=> 'PayPal'
			);

			$replace = apply_filters( 'woocommerce_sagepay_direct_cardnames_array', $replace );

			// Clean up the card_details in to Sage format
			$cc_type_name = self::str_replace_assoc( $replace, strtoupper($cc_type) );

			return $cc_type_name;

    	}

    	/**
    	 * [get_card_expiry_date description]
    	 * @param  [type] $expiry_date [description]
    	 * @param  [type] $arg         [description]
    	 * @return [type]              [description]
    	 */
		public function get_card_expiry_date( $expiry_date, $arg ) {

			$expiry_date = str_replace( array( '/', ' ' ), '', $expiry_date );
			
			if( $arg == 'month' ) {
				return str_pad( substr( $expiry_date, 0, -2 ), 2, "0", STR_PAD_LEFT );
			}

			if( $arg == 'year' ) {
				return substr( $expiry_date, -2 );
			}
			
		}

        /**
         * Load the settings fields.
         *
         * @access public
         * @return void
         */
        function init_form_fields() {	
			include ( SAGEPLUGINPATH . 'assets/php/sagepay-direct-admin.php' );
		}

		/**
 		 * Admin Panel Options
		 * [admin_options description]
		 * @return [type]
		 */
		public function admin_options() {

			?>
	    	<h3><?php _e('Opayo Direct', 'woocommerce-gateway-sagepay-form'); ?></h3>

			<div id="opayo-direct">
				<h3><?php _e('Opayo Protocol 4.00 is live.', 'woocommerce_worlday'); ?></h3>
				<p><?php _e('You should ensure that 3D Secure is turned on in MySagePay before switching to Proctocol 4.', 'woocommerce-gateway-sagepay-form'); ?></p>
				<p><?php _e('Read more <a href="https://docs.woocommerce.com/document/sagepay-form/#section-21" target="_blank">HERE</a>', 'woocommerce-gateway-sagepay-form' ); ?></p>
			</div>
				
			<table class="form-table">
			<?php
				// Generate the HTML for the settings form.
				$this->generate_settings_html();
			?>
			</table><!--/.form-table-->
			<?php

		} // END admin_options
		/**
		 * Check if SSL is enabled and notify the user
	 	 */
		function sagepaydirect_ssl_check() {

			if( $this->enabled == "yes" ) {
	     
		    	if ( !$this->is_secure() ) {
		     		echo '<div class="error notice sagepaydirect-ssl-nag is-dismissible"><p>'. __( 'SagePay Direct is enabled and your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate before going live.', 'woocommerce-gateway-sagepay-form' ) .'</p></div>';
		    	}

		    }

		}

		/**
		 * Enqueue scripts for the CC form.
		 */
		function sagepaydirect_scripts() {
        	
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		
			wp_enqueue_style( 'wc-sagepaydirect', SAGEPLUGINURL.'assets/css/checkout.css', array(), $this->sagepay_version, false);

			if ( ! wp_script_is( 'wc-credit-card-form', 'registered' ) ) {
				wp_register_script( 'wc-credit-card-form', SAGEPLUGINURL.'assets/js/credit-card-form.js', array( 'jquery', 'jquery-payment' ), $this->sagepay_version, true );
			}

			// 3D Secure 2
			wp_register_script( 'wc-3dsbrowser', SAGEPLUGINURL.'assets/js/3dsbrowser' . $suffix . '.js', array( 'jquery', 'jquery-payment' ), $this->sagepay_version, true );

		}

		/**
		 * Enqueues our tokenization script to handle some of the new form options.
		 * @since 2.6.0
		 */
		public function tokenization_script() {
			wp_enqueue_script(
				'sagepay-tokenization-form',
				SAGEPLUGINURL.'assets/js/tokenization-form.js',
				array( 'jquery' ),
				$this->sagepay_version
			);

			wp_localize_script(
				'sagepay-tokenization-form', 'wc_tokenization_form_params', array(
					'is_registration_required' => WC()->checkout()->is_registration_required(),
					'is_logged_in'             => is_user_logged_in(),
				)
			);

		}

		/**
		 * Enqueues our PayPal script to handle some of the new form options.
		 */
		public function paypal_script() {
			wp_enqueue_script(
				'sagepay-paypal',
				SAGEPLUGINURL.'assets/js/paypal-cardtype.js',
				array( 'jquery' ),
				$this->sagepay_version
			);
		}

		/**
		 * Enqueues our tokenization script to handle some of the new form options, leaves CVV field in place.
		 * @since 3.13.0
		 */
		public function cvv_script() {
			wp_enqueue_script(
				'sagepay-tokenization-form-cvv',
				SAGEPLUGINURL.'assets/js/tokenization-form-cvv.js',
				array( 'jquery' ),
				$this->sagepay_version
			);

			wp_localize_script(
				'sagepay-tokenization-form-cvv', 'wc_tokenization_form_params', array(
					'is_registration_required' => WC()->checkout()->is_registration_required(),
					'is_logged_in'             => is_user_logged_in(),
				)
			);
		}

		/**
		 * Enqueues our 3D Secure 2 script.
		 */
		public function threeds_script() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script(
				'sagepay-3dsbrowser',
				SAGEPLUGINURL.'assets/js/3dsbrowser' . $suffix . '.js',
				array( 'jquery', 'jquery-payment' ),
				$this->sagepay_version
			);
		}		

		/**
		 * Enqueues our Giftaid script to handle some of the new form options.
		 */
		public function giftaid_script() {
			wp_enqueue_script(
				'sagepay-giftaid',
				SAGEPLUGINURL.'assets/js/giftaid.js',
				array( 'jquery' ),
				$this->sagepay_version
			);
		}

		/**
		 * [get_icon description] Add selected card icons to payment method label, defaults to Visa/MC/Amex/Discover
		 * @return [type] [description]
		 */
		public function get_icon() {
			return WC_Sagepay_Common_Functions::get_icon( $this->cardtypes, $this->sagelink, $this->sagelogo, $this->id );
		}

		/**
		 * SagePay Direct Refund Processing
		 * @param  Varien_Object $payment [description]
		 * @param  [type]        $amount  [description]
		 * @return [type]                 [description]
		 */
    	function process_refund( $order_id, $amount = NULL, $reason = '' ) {
        	
    		include_once( 'sagepay-direct-refund-class.php' );

    		$refund = new Sagepay_Direct_Refund( $order_id, $amount, $reason );

    		return $refund->refund();

    	} // process_refund
    	
		/**
		 * @return bool
		 */
		function is_session_started() {
    		
    		if ( php_sapi_name() !== 'cli' ) {
        		
        		if ( version_compare(phpversion(), '5.4.0', '>=') ) {
            		return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
        		} else {
            		return session_id() === '' ? FALSE : TRUE;
        		}
    		
    		}
    		
    		return FALSE;
		
		}

		public static function str_replace_assoc( array $replace, $subject ) {
   			return str_replace( array_keys($replace), array_values($replace), $subject );   
		}

		/**
		 * Set a default postcode for Elavon users
		 */
		function billing_postcode( $postcode ) {
			if ( '' != $postcode ) {
				return $postcode;
			} else {
				return isset( $this->sdefaultpostcode ) && $this->sdefaultpostcode != '' ? $this->defaultpostcode : $this->default_postcode;;
			}
		}

		/**
		 * Set a default city if city field is empty
		 */
		function city( $city ) {
			if ( '' != $city ) {
				return $city;
			} else {
				return ' ';
			}
		}

		/**
		 * Set billing or shipping state
		 */
		function get_state( $country, $billing_or_shipping, $order ) {

			if ( $billing_or_shipping == 'billing' ) {
            	
            	if ( $country == 'US' ) {
            		return  $order->billing_state;
            	} else {
            		return '';
            	}

            } elseif ( $billing_or_shipping == 'shipping' ) {
            	
            	if ( $country == 'US' ) {
            		return  $order->shipping_state;
            	} else {
            		return '';
            	}

            }

		}

		/**
		 * [sage_line_break description]
		 * Set line break
		 */
		function sage_line_break ( $sage_line_break ) {
			
			switch ( $sage_line_break ) {
    			case '0' :
        			$line_break = '/$\R?^/m';
        			break;
    			case '1' :
        			$line_break = PHP_EOL;
        			break;
    			case '2' :
        			$line_break = '#\n(?!s)#';
        			break;
        		case '3' :
        			$line_break = '#\r(?!s)#';
        			break;
    			default:
       				$line_break = '/$\R?^/m';
			}

			return $line_break;
		
		}

		/**
		 * Check IP Address, set to Protocol 3.00 if IP address is not IPv4
		 */
		function get_vpsprotocol_from_ipaddress( $vpsprotocol ) {

			$ipaddresses = $this->get_ipaddresses();

			// Remove IPv6 addresses, Opayo does not support IPv6 yet
	        foreach( $ipaddresses as $lable => $ipaddress ) {
	        	if ( !filter_var( $ipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
					$cleaned_ipaddresses[] = $this->isValidIP( $ipaddress );
				}
	        }

	        // IPv4 IP Address present, return $vpsprotocol
	        if( isset( $cleaned_ipaddresses[0] ) ) {
	        	return $vpsprotocol;
	        }

	        // No IPv4 IP Address, set VPS Protocol to 3.00
	        return '3.00';

		}

		/**
		 * Get IP Address
		 */
		function get_ipaddress() {

			$ipaddresses = $this->get_ipaddresses();

			// Remove IPv6 addresses, Opayo does not support IPv6 yet
	        foreach( $ipaddresses as $lable => $ipaddress ) {
	        	if ( !filter_var( $ipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
					$cleaned_ipaddresses[] = $this->isValidIP( $ipaddress );
				}
	        }

	        // IPv4 IP Address present, return $vpsprotocol
	        if( isset( $cleaned_ipaddresses[0] ) ) {
	        	return $cleaned_ipaddresses[0];
	        }

	        return NULL;

		}

		/**
		 * [get_ipaddresses description]
		 * @return [type] [description]
		 */
	    function get_ipaddresses() {
	        $ipaddresses = array();

	        if( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
	            $ipaddresses['HTTP_CF_CONNECTING_IP'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
	        } 

	        if ( isset($_SERVER['HTTP_CLIENT_IP'] ) ) {
	            $ipaddresses['HTTP_CLIENT_IP'] = $_SERVER['HTTP_CLIENT_IP'];
	        }

	        if ( isset($_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
	            $ipaddresses['HTTP_X_FORWARDED_FOR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
	        }

	        if ( isset($_SERVER['HTTP_X_FORWARDED'] ) ) {
	            $ipaddresses['HTTP_X_FORWARDED'] = $_SERVER['HTTP_X_FORWARDED'];
	        }

	        if ( isset($_SERVER['HTTP_FORWARDED_FOR'] ) ) {
	            $ipaddresses['HTTP_FORWARDED_FOR'] = $_SERVER['HTTP_FORWARDED_FOR'];
	        }

	        if ( isset($_SERVER['HTTP_FORWARDED'] ) ) {
	            $ipaddresses['HTTP_FORWARDED'] = $_SERVER['HTTP_FORWARDED'];
	        }

	        if ( isset($_SERVER['REMOTE_ADDR'] ) ) {
	            $ipaddresses['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
	        }
	
			// Testing
	        // $ipaddresses['REMOTE_ADDR'] = "2001:0db8:85a3:0000:0000:8a2e:0370:7334,7334";

	        // Validate IP Addresses
	        foreach( $ipaddresses as $lable => $ipaddress ) {
	        	$ipaddresses[ $lable ] = $this->isValidIP( $ipaddress );
	        }

	        return $ipaddresses;

	    }

	    /**
	     * [isValidIP description]
	     * @param  [type]  $ipaddress [description]
	     * @return boolean            [description]
	     */
	    function isValidIP( $ipaddress ) {

	        // If the IP address is valid send it back
	        if( filter_var( $ipaddress, FILTER_VALIDATE_IP ) ) {
	            return $ipaddress;
	        }

	        // Clean up the IP6 address
	        if ( strpos( $ipaddress, ':' ) !== false ) {

	            // Make an array of the chunks
	            $ip = explode( ":", $ipaddress );

	            // Only the first 8 chunks count
	            $ip = array_slice( $ip, 0, 8 );

	            // Make sure each chunk is 4 characters long and only contains letters and numbers
	            foreach( $ip as &$value ) {
	                $value = substr( $value, 0, 4 );
	                $value = preg_replace( '/\W/', '', $value );
	            }

	            unset( $value );

	            // Combine the chunks and return the IP6 address
	            return implode( ":", $ip );

	        }

	        // Clean up the IP4 address
	        if ( strpos( $ipaddress, '.' ) !== false ) {

	            // Make an array of the chunks
	            $ip = explode( ".", $ipaddress );

	            // Only the first 4 chunks count
	            $ip = array_slice( $ip, 0, 4 );

	            // Make sure each chunk is 3 characters long and only contains numbers
	            foreach( $ip as &$value ) {
	                $value = substr( $value, 0, 3 );
	                $value = preg_replace( '/\D/', '', $value );
	            }

	            unset( $value );

	            // Combine the chunks and return the IP4 address
	            return implode( ".", $ip );

	        }

	        // Fallback
	        return $ipaddress;
	    }

        /**
         * sagepay_register_token
         * Send transaction for token registration, no money will be taken this time.
         * 
         * @return [type] [description]
         */
        function sagepay_register_token( $CardHolder, $CardNumber, $ExpiryDate, $CV2, $CardType ) {
        	
            /**
             * Sent to : 
             * https://test.sagepay.com/gateway/service/directtoken.vsp
             * https://live.sagepay.com/gateway/service/directtoken.vsp
             * 
             * requires : 
             * VPSProtocol => 3.00
             * TxType => TOKEN
             * Vendor => From settings
             * Currency => GBP
             * Cardholder => From form
             * CardNumber => From form
             * ExpiryDate => From form
             * CV2 => From form
             * CardType => From form
             *
             * Returns :
             * VPSProtocol => 3.00
             * TxType => TOKEN
             * Status => (OK, MALFORMED, INVALID, ERROR)
             * StatusDetail => ''
             */

            $data    = array(
                "VPSProtocol"       => $this->vpsprotocol,
                "TxType"            => 'TOKEN',
                "Vendor"            => $this->vendor,
                "Currency"          => get_woocommerce_currency(),
                "Cardholder"        => $CardHolder,
                "CardNumber"        => $CardNumber,
                "ExpiryDate"        => $ExpiryDate,
                "CV2"               => $CV2,
                "CardType"          => self::cc_type( $CardNumber, $CardType ),
            );

            $sageresult = $this->sagepay_post( $data, $this->addtokenURL );

            return $sageresult;

        }

		/**
		 * Add payment method via account screen.
		 * @since 3.0.0
		 */
		public function add_payment_method() {
        	
			if( is_user_logged_in() ) {     
			
				$sage_card_type 		= isset($_POST[$this->id . '-card-type']) ? wc_clean($_POST[$this->id . '-card-type']) : '';
				$sage_card_number 		= isset($_POST[$this->id . '-card-number']) ? wc_clean($_POST[$this->id . '-card-number']) : '';
				$sage_card_cvc 			= isset($_POST[$this->id . '-card-cvc']) ? wc_clean($_POST[$this->id . '-card-cvc']) : '';
				$sage_card_expiry		= isset($_POST[$this->id . '-card-expiry']) ? wc_clean($_POST[$this->id . '-card-expiry']) : '';
				$sage_card_expiry_mon	= isset($_POST[$this->id . '-card-expiry-month']) ? wc_clean($_POST[$this->id . '-card-expiry-month']) : false;
				$sage_card_expiry_year	= isset($_POST[$this->id . '-card-expiry-year']) ? wc_clean($_POST[$this->id . '-card-expiry-year']) : false;

				// Format values
				$sage_card_number    	= str_replace( array( ' ', '-' ), '', $sage_card_number );
				// Allow for old template file which uses text box for card expiry date
				if( $sage_card_expiry ) {
					$sage_card_exp_month 	= $this->get_card_expiry_date( $sage_card_expiry, 'month' );
					$sage_card_exp_year 	= $this->get_card_expiry_date( $sage_card_expiry, 'year' );
				} else {
					$sage_card_exp_month 	= $sage_card_expiry_mon;
					$sage_card_exp_year 	= $this->get_card_expiry_date( $sage_card_expiry_year, 'year' );
				}

				$current_user   		= wp_get_current_user();
				$CardHolder 			= $current_user->billing_first_name . ' ' . $current_user->billing_last_name;

				$sage_add_card_error 	= false;

				$register_token = NULL;
				
				if( $this->saved_cards == 'yes' ) {
					// New payment method using a token
					
					// Create a new token
					$register_token = $this->sagepay_register_token( $CardHolder, $sage_card_number, $sage_card_exp_month . $sage_card_exp_year, $sage_card_cvc, $sage_card_type );

					if ( isset( $register_token ) && $register_token['Status'] === 'OK' ) {
						// Token creation successful
						self::save_token( $register_token['Token'], $sage_card_type, substr( $sage_card_number, -4 ), $sage_card_exp_month, $sage_card_exp_year );
						return array(
							'result'   => 'success',
							'redirect' => wc_get_endpoint_url( 'payment-methods' ),
						);

					} else {
						// Token creation failed
						wc_add_notice( __( 'There was a problem adding the card. ' . $register_token['StatusDetail'], 'woocommerce-gateway-sagepay-form' ), 'error' );
						return;
					}

				} else {
					// Saved cards are not allowed
					wc_add_notice( __( 'There was a problem adding the card. ' . $register_token['StatusDetail'], 'woocommerce-gateway-sagepay-form' ), 'error' );
					return;
				}

			} else {
				wc_add_notice( __( 'There was a problem adding the card. Please make sure you are logged in.', 'woocommerce-gateway-sagepay-form' ), 'error' );
				return;
			}

		}

		/**
		 * Use the txtype from settings unless the order contains a pre-order or the order value is 0
		 *
		 * @param  {[type]} $order_id [description]
		 * @param  {[type]} $amount   [description]
		 * @return {[type]}           [description]
		 */
		function get_txtype( $order_id, $amount ) {

			// Paying for a "Pay Later" Pre Order
			if( isset( $_GET['pay_for_order'] ) && $_GET['pay_for_order'] == true && class_exists( 'WC_Pre_Orders' ) && WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) ) {
				return 'PAYMENT';
			}
        	
			if( class_exists( 'WC_Pre_Orders' ) && WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) && WC_Pre_Orders_Order::order_will_be_charged_upon_release( $order_id ) ) {
				return 'AUTHENTICATE';
			}
			
			if( $amount == 0 ) {
				return 'AUTHENTICATE';
			}

			return $this->txtype;
			

		}

		/**
		 * Get the transaction value.
		 * Set to 0.01 if the order value is 0
		 *
		 * @param  {[type]} $order_id [description]
		 * @param  {[type]} $amount   [description]
		 * @return {[type]}           [description]
		 */
		function get_amount( $order, $amount ) {

			// Add to account for Free Trial Subscriptions
			if( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $order ) && $amount == 0 ) {

				$order_id = $order->get_id();

				$subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => array( 'parent', 'renewal' ) ) );

				if( count( $subscriptions ) >= 1 ) {

					foreach ( $subscriptions as $subscription ) {
						$amount = $amount + $subscription->get_total();
					}

				}

			}	
        	
			if( $amount == 0 ) {
				return 0.01;
			} else {
				return $amount;
			}

		}

		/**
		 * [save_token description]
		 * @param  [type] $token        [description]
		 * @param  [type] $card_type    [description]
		 * @param  [type] $last4        [description]
		 * @param  [type] $expiry_month [description]
		 * @param  [type] $expiry_year  [description]
		 * @return [type]               [description]
		 */
		public static function save_token( $sagetoken, $card_type, $last4, $expiry_month, $expiry_year ) {

			$token = new WC_Payment_Token_CC();

			$token->set_token( str_replace( array('{','}'),'',$sagetoken ) );
			$token->set_gateway_id( 'sagepaydirect' );
			$token->set_card_type( self::cc_type_name( self::cc_type( '', $card_type ) ) );
			$token->set_last4( $last4 );
			$token->set_expiry_month( $expiry_month );
			$token->set_expiry_year( 2000 + $expiry_year );
			$token->set_user_id( get_current_user_id() );

			$token->save();

		}

        /**
         * [process_pre_order_payments description]
         * @return [type] [description]
         */
        function process_pre_order_release_payment( $order ) {

        	include_once( 'sagepay-direct-release-class.php' );

    		$response = new Sagepay_Direct_Release( $order );

    		return $response->release();

        }

        /**
         * [process_void_payment description]
         * @return [type] [description]
         */
        function process_void_payment( $order ) {
        	
    		include_once( 'sagepay-direct-void-class.php' );

    		$response = new Sagepay_Direct_Void( $order );

    		return $response->void();

        }

        /**
         * [maybe_render_subscription_payment_method description]
         * @param  [type] $payment_method_to_display [description]
         * @param  [type] $subscription              [description]
         * @return [type]                            [description]
         */
	    public function maybe_render_subscription_payment_method( $payment_method_to_display, $subscription ) {

	    	// bail for other payment methods
            if ( $this->id != $subscription->get_payment_method() ) {
                return $payment_method_to_display;
            }

            if( is_object( $subscription ) ) {

	            $sage_token     = get_post_meta( $subscription->get_id(), '_SagePayDirectToken', true );
	            $sage_token_id  = $this->get_token_id( $sage_token );

	            $token = new WC_Payment_Token_CC();
	            $token = WC_Payment_Tokens::get( $sage_token_id );

	            if( $token ) {
	                $payment_method_to_display = sprintf( __( 'Via %s card ending in %s', 'woocommerce-gateway-sagepay-form' ), $token->get_card_type(), $token->get_last4() );
	            }

	        }

            return $payment_method_to_display;
 
	    }
		
		/**
		 * [update_subscription_token description]
		 * @param  [type] $updated      [description]
		 * @param  [type] $subscription [description]
		 * @param  [type] $new_token    [description]
		 * @param  [type] $old_token    [description]
		 * @return [type]               [description]
		 */
		public function update_subscription_token( $updated, $subscription, $new_token, $old_token ) {
			
			$new_token_payment_method 	= $new_token->get_gateway_id();
			if( $new_token_payment_method == $this->id ) {
				$updated = true;
			}
			return $updated;
		}
		
		/**
		 * [subscription_token_changed description]
		 * @param  [type] $subscription [description]
		 * @param  [type] $new_token    [description]
		 * @param  [type] $old_token    [description]
		 * @return [type]               [description]
		 */
		public function subscription_token_changed( $subscription, $new_token, $old_token ) {
			
			$new_token_id 				= $new_token->get_id();
			$new_token_token 			= $new_token->get_token();
			$new_token_payment_method 	= $new_token->get_gateway_id();
			
			if( $new_token_payment_method == $this->id ) {

				update_post_meta( $subscription->get_id(), '_SagePayDirectToken', $new_token_token );
				update_post_meta( $subscription->get_id(), '_payment_method', $this->id );
				update_post_meta( $subscription->get_id(), '_payment_method_title', $this->title );

				$notice = sprintf( __( 'Your previous payment method (%s card ending in %s) has been updated to %s card ending in %s.', 'woocommerce-gateway-sagepay-form' ), $old_token->get_card_type(), $old_token->get_last4(), $new_token->get_card_type(), $new_token->get_last4() );

				wc_add_notice( $notice, 'success' );
				
			}
			
		}

        /**
         * Get the Token ID from the database using the token from Sage
         * @param  [type] $token [description]
         * @return [type]        [description]
         */
        function get_token_id( $token ) {
            global $wpdb;

            $token = str_replace( array('{','}'),'',$token );

            if ( $token ) {
            	
                $tokens = $wpdb->get_row( $wpdb->prepare(
                    "SELECT token_id FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token = %s",
                    $token
                ) );

                if( $tokens ) {
                	return $tokens->token_id;
                } else {
                	return NULL;
                }
            }

        }

        /**
         * check_sagepaydirect_response function.
         * For PayPal transactions
         *
         * @access public
         * @return void
         */
        function check_sagepaydirect_response() {

    		include_once( 'sagepay-direct-wc-api-class.php' );

    		$api = new Sagepay_Direct_API();

    		return $api->process_api();

        }

		/**
		 * [is_secure description]
		 * @return boolean [description]
		 */
		function is_secure() {

			if ( function_exists( 'wc_checkout_is_https' ) && !wc_checkout_is_https() ) {
				return false;
			}

			return true;

		}

		/**
		 * [needs_payment description]
		 * @param  [type] $needs_payment [description]
		 * @param  [type] $woocommerce   [description]
		 * @return [type]                [description]
		 */
		function needs_payment( $needs_payment, $woocommerce ) {
        	 $needs_payment[] = 'authorised';
        	 return $needs_payment;
        }

        /**
         * [remove_authorized_my_account description]
         * @param  [type] $actions [description]
         * @param  [type] $order   [description]
         * @return [type]          [description]
         */
        function remove_authorized_my_account( $actions, $order ) {

			if( $order->get_status() == 'authorised' ) {
				unset( $actions['pay'] );
			}

			return $actions;
        }

        /**
         * [remove_woocommerce_subscriptions_update_payment_via_pay_shortcode description]
         * @return [type] [description]
         */
        public function remove_woocommerce_subscriptions_update_payment_via_pay_shortcode() {
        	return false;
        }

        /**
         * [redirect_paid_orders that are stuck on the order-pay page]
         */
        function redirect_paid_orders() {
        	global $wp;

        	// Ignore add payment method and change payment methog
        	if( is_add_payment_method_page() || isset( $_GET['change_payment_method'] ) || isset( $_REQUEST['cres'] ) || isset( $_REQUEST['PaRes'] )) {
        		return;
        	}

        	$order_id = absint( $wp->query_vars['order-pay'] );

        	if( $order_id ) {

        		$order    = wc_get_order( $order_id );

	        	$order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';

	        	if ( !$this->opayo_needs_payment($order) && hash_equals( $order->get_order_key(), $order_key ) ) {
	        		
	        		$redirect_url = $order->get_checkout_order_received_url();

					// Set url tracking
	                if( $this->threeDS_tracking ) {
	                    $redirect_url = add_query_arg( array(
	                        'utm_nooverride' => 1
	                    ), $redirect_url );
	                }

	                $this->opayo_redirect( $redirect_url );
				}

			}

        }
		
		/**
		 * [show_errors description]
		 * @param  [type] $checkout [description]
		 * @return [type]           [description]
		 */
		function show_errors( $checkout ) {

			// Get the Order ID
			$order_id = absint( WC()->session->get( 'order_awaiting_payment' ) );

			if( $order_id == 0 && class_exists( 'WC_Subscriptions' ) && method_exists( $checkout, 'get_id' ) ) {
				$order_id = $checkout->get_id();
			}

			if( $order_id != 0 ) {

				$errors = get_post_meta( $order_id, '_sagepay_errors', TRUE );

				if( ! empty( $errors ) ) {
					wc_print_notice( $errors['message'], $errors['type'] );
				}

				// Make sure to delete the error message immediatley after showing it.
				// 
				// DON'T delete the message if the customer created an account during checkout
				// WooCommerce reloads the checkout after creating the account so the message will disappear :/ 
				$reload_checkout = WC()->session->get( 'reload_checkout' ) ? WC()->session->get( 'reload_checkout' ) : NULL;

				if( is_null($reload_checkout) ) {
					delete_post_meta( $order_id, '_sagepay_errors' );
				}

				delete_post_meta( $order_id, '_opayo_callback_value' );
			}
		}

		/**
		 * [clear_cart description]
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		function clear_cart( $order_id ) {

			$order = wc_get_order( $order_id );
			if( !$this->opayo_needs_payment($order) && get_post_meta( $order_id, '_created_via', TRUE ) == 'checkout' && $order->get_payment_method() == 'sagepaydirect' ) {
				WC()->cart->empty_cart();
			}
		}

		/**
		 * Limit length of an arg.
		 *
		 * @param  string  $string Argument to limit.
		 * @param  integer $limit Limit size in characters.
		 * @return string
		 */
		function limit_length( $string, $limit = 127 ) {

			$str_limit = $limit - 3;
			if ( function_exists( 'mb_strimwidth' ) ) {
				if ( mb_strlen( $string ) > $limit ) {
					$string = mb_strimwidth( $string, 0, $str_limit ) . '...';
				}
			} else {
				if ( strlen( $string ) > $limit ) {
					$string = substr( $string, 0, $str_limit ) . '...';
				}
			}
			
			return $string;
		}

		/**
		 * [opayo_needs_payment description]
		 * @param  [type] $order [description]
		 * @return [type]        [description]
		 */
		function opayo_needs_payment( $order ) {

			if( !$order->needs_payment() || $order->get_status() == 'authorised' ) {
				return false;
			}

			return true;
		}

		function append_url ( $url ) {

			// Set url tracking
            if( $this->threeDS_tracking ) {
                $url = add_query_arg( array(
                    'utm_nooverride' => 1
                ), $url );
            }

            return $url;

		}

		function opayo_redirect( $return_url ) {
			$redirect_script = '<p>If you are not automatically redirected <a href="'.$return_url.'" target="_top">please click here to complete your authorisation</a></p>
		                        <script>
		                            top.location.href = "' . $return_url . '";
		                        </script>';
		    echo $redirect_script;
		    exit;
		}
	} // END CLASS
