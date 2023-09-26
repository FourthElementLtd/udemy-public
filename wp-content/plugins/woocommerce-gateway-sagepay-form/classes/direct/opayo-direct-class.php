<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
    
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

            // Load the form fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            // Process the settings on save
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // Supports
            $this->supports = $this->get_opayo_direct_supports();

            // Order button text
           	$this->order_button_text    = isset( $this->settings['order_button_text'] ) && $this->settings['order_button_text'] != "" ? $this->settings['order_button_text'] : __( 'Pay securely with Opayo', 'woocommerce-gateway-sagepay-form' );

			// Scripts
			add_action( 'wp_enqueue_scripts', array( $this, 'sagepaydirect_scripts' ) );

			// WC version
			$this->wc_version = get_option( 'woocommerce_version' );

			// Hooks
			add_action( 'woocommerce_receipt_sagepaydirect', array($this, 'authorise_3dsecure') );
			
			// Show any stored error messages
			add_action( 'woocommerce_before_checkout_form', array( $this, 'show_errors' ) );
			add_action( 'woocommerce_subscription_details_table', array( $this, 'show_errors' ), 1 );

			// Make sure the cart empties!
			add_action( 'woocommerce_payment_complete', array( $this, 'clear_cart' ) );

			// Allow sites to remove the CVV box from the checkout form for token payments
			// If you use this filter you may need to modify the request sent to Opayo using $data = apply_filters( 'woocommerce_sagepay_direct_data', $data, $order );
			$this->override_opayo_cvv_requirement = apply_filters( 'override_opayo_cvv_requirement', TRUE );

			// Void payments
			add_action ( 'woocommerce_order_action_opayo_process_void', array( $this, 'process_void_payment' ) );

			// Capture authorised payments
			add_action ( 'woocommerce_order_action_opayo_process_payment', array( $this, 'process_pre_order_release_payment' ) );

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

			// Opayo urls
			if ( $this->get_status() == 'live' ) {
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

        } // END __construct


        /**
    	 * Payment form on checkout page
    	 */
		public function payment_fields() {

			/**
			 * Gateway ID (sagepaydirect)
			 * Allowed card types from settings
			 * All available card types
			 * Allow tokens?
			 * Allow gift aid?
			 * 
			 * @var array
			 */
			$args = array( 
				'gateway_id' 	 	=> $this->id,
				'cardtypes'	 	 	=> $this->get_cardtypes(),
				'sage_cardtypes' 	=> $this->set_cardtypes(),
				'tokens' 			=> $this->get_opayo_direct_supports( 'tokenization' ) && class_exists( 'WC_Payment_Token_CC' ) && is_checkout() && $this->get_saved_cards() == 'yes',
				'tokens_message'	=> $this->get_tokens_message(),
				'giftaid' 			=> $this->get_giftaid(),
				'giftaid_message'	=> $this->get_giftaid_message(),
				'description'		=> $this->get_description()
			);

			if ( is_add_payment_method_page() ) {
				$pay_button_text = __( 'Add Card', 'woocommerce-gateway-sagepay-form' );
			} else {
				$pay_button_text = '';
			}

			echo '<div id="sagepaydirect-payment-data">';

			// Show the payment method description
			echo apply_filters( 'wc_sagepaydirect_description', wp_kses_post( $args['description'] ) );

			// Add tokenization script
			if ( $args['tokens'] ) {
				// Add script to remove card fields if CVV required with tokens
				if( $this->override_opayo_cvv_requirement ) {
					$this->cvv_script();
				} else {
					$this->tokenization_script();
				}
				
				$this->saved_payment_methods();
			}

			// Add script to remove card fields if card type == PayPal
			$this->paypal_script();

			// Add GiftAid script to remove option if not in UK
			$this->giftaid_script();

			// Use our own payment fields
			$filename = apply_filters( 'woocommerce_sage_credit_card_filename', $this->get_template() . '_credit-card-form.php' );

			$template = wc_get_template( $filename, $args, '', SAGEPLUGINPATH . 'assets/templates/direct/' );

			// Additional fields required for Protocol 4.00
			echo $this->get_proctocol_4_script();

			// Close id="sagepaydirect-payment-data"
			echo '</div>';

		}
        
        /**
    	 * Validate the payment form
    	 */
		public function validate_fields() {

			include_once( 'opayo-direct-validate-class.php' );

    		$valid = new Opayo_Direct_Validate();

    		return $valid->validate(); 

		}
        
        /**
    	 * Process the payment form
    	 */
		public function process_payment( $order_id ) {

			if( isset( $_GET['change_payment_method'] ) ) {

				include_once( 'opayo-direct-change-payment-method.php' );
    			$process = new Opayo_Direct_Change_Payment_Process();
    			return $process->process_order( $order_id ); 

			} else {

				include_once( 'opayo-direct-process-class.php' );
    			$process = new Opayo_Direct_Process();
    			return $process->process_order( $order_id ); 

			}

		}

        /**
         * [authorise_3dsecure description]
         * @param  [type] $order_id [description]
         * @return [type]           [description]
         */
        function authorise_3dsecure( $order_id ) {

        	// Check for change_payment_method
			if( isset( $_GET['update_payment_method'] ) && is_numeric( $_GET['update_payment_method'] ) ) {
				$order_id = wc_clean( $_GET['update_payment_method'] );
				include_once( 'opayo-direct-threeds-change-payment-method-class.php' );
			} else {
				include_once( 'opayo-direct-threeds-class.php' );
			}

    		$threeds = new Opayo_Direct_Process_Threeds();

    		return $threeds->process_threeds( $order_id ); 

        }

        /**
    	 * Process the payment form
    	 */
		public function add_payment_method() {

			include_once( 'opayo-direct-add-payment-method.php' );

    		$add = new Opayo_Direct_Add_Payment_Method();

    		return $add->add_payment_method(); 

		}

        /**
         * [process_scheduled_subscription_payment description]
         * @param  [type] $amount_to_charge [description]
         * @param  [type] $order            [description]
         * @return [type]                   [description]
         */
        function process_scheduled_subscription_payment( $amount_to_charge, $order ) {

        	include_once( 'opayo-direct-subscriptions-class.php' );
	    	$response = new Sagepay_Direct_Subcription_Renewals( $amount_to_charge, $order );

	    	$response->process_scheduled_payment();

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
		 * Opayo Direct Refund Processing
		 * @param  Varien_Object $payment [description]
		 * @param  [type]        $amount  [description]
		 * @return [type]                 [description]
		 */
    	function process_refund( $order_id, $amount = NULL, $reason = '' ) {
        	
    		include_once( 'opayo-direct-refund-class.php' );

    		$refund = new Sagepay_Direct_Refund( $order_id, $amount, $reason );

    		return $refund->refund();

    	} // process_refund

        /**
         * [process_void_payment description]
         * @return [type] [description]
         */
        function process_void_payment( $order ) {
        	
    		include_once( 'opayo-direct-void-class.php' );

    		$response = new Sagepay_Direct_Void( $order );

    		return $response->void();

        }

        /**
         * [process_pre_order_payments description]
         * @return [type] [description]
         */
        function process_pre_order_release_payment( $order ) {

        	include_once( 'opayo-direct-preorder-release-class.php' );

    		$release = new Opayo_Direct_PreOrder_Release();

    		return $release->preorder_release( $order );

        }

        /**
         * [get_enabled description]
         * @return [type] [description]
         */
		function get_enabled() {
			return $this->settings['enabled'];
		}

		/**
		 * [get_title description]
		 * @return [type] [description]
		 */
		function get_title() {
			return $this->settings['title'];
		}

        /**
         * [get_icon description] Add selected card icons to payment method label, defaults to Visa/MC/Amex/Discover
         * @return [type] [description]
         */
        function get_icon() {
            return WC_Sagepay_Common_Functions::get_icon( $this->get_cardtypes(), false, false, $this->id );
        }

		/**
		 * [get_description description]
		 * @return [type] [description]
		 */
		function get_description() {

			$description = $this->settings['description'];

			// Add test card info to the description if in test mode
			if ( $this->get_status() != 'live' ) {
				$description .= ' ' . sprintf( __( '<br />TEST MODE ENABLED.<br />In test mode, you can use Visa card number 4929000000006 with any CVC and a valid expiration date or check the documentation (<a href="%s">Test card details for your test transactions</a>) for more card numbers.', 'woocommerce-gateway-sagepay-form' ), 'http://www.sagepay.co.uk/support/12/36/test-card-details-for-your-test-transactions' );
			}

			return trim( $description );

		}

		/**
		 * [get_vendor description]
		 * @return [type] [description]
		 */
		function get_vendor() {
			return $this->settings['vendor'];
		}

		/**
		 * [get_status description]
		 * @return [type] [description]
		 */
		function get_status() {
			return $this->settings['status'];
		}

		/**
		 * [get_txtype description]
		 * @return [type] [description]
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

			return $this->settings['txtype'];
		}

		/**
		 * [get_cvv description]
		 * @return [type] [description]
		 */
		function get_cvv() {
			return isset( $this->settings['applyavscv2'] ) ? $this->settings['applyavscv2'] : "0";
		}

		/**
		 * [get_cvv_script description]
		 * @return [type] [description]
		 */
		function get_cvv_script() {
			return true;
		}

		/**
		 * [get_cardtypes description]
		 * @return [type] [description]
		 */
		function get_cardtypes() {
			return !empty( $this->settings['cardtypes'] ) ? $this->settings['cardtypes'] : $this->set_cardtypes();
		}

		/**
		 * [get_secure description]
		 * @return [type] [description]
		 */
		function get_secure() {
			$secure = isset( $this->settings['3dsecure'] ) ? $this->settings['3dsecure'] : "0";

			if( $secure == '2' || $secure == '3' ) {
				$secure = '0';
			}

			return $secure;
		}

		/**
		 * [get_threeDSMethod description]
		 * @return [type] [description]
		 */
		function get_threeDSMethod() {
			return isset( $this->settings['threeDSMethod'] ) ? $this->settings['threeDSMethod'] : "1";
		}

		/**
		 * [get_threeDS_tracking description]
		 * @return [type] [description]
		 */
		function get_threeDS_tracking() {
			return isset( $this->settings['3dsecure_tracking'] ) && $this->settings['3dsecure_tracking'] == '1' ? true : false;
		}

		/**
		 * [get_secure_token description]
		 * @return [type] [description]
		 */
		function get_secure_token() {
			return isset( $this->settings['secure_token'] ) && $this->settings['secure_token'] == 'yes' ? true : false;
		}

		/**
		 * [get_accounttype description]
		 * @return [type] [description]
		 */
		function get_accounttype() {
			return "E";
		}

		/**
		 * [get_billingagreement description]
		 * @return [type] [description]
		 */
		function get_billingagreement() {
			return "0";
		}

		/**
		 * [get_debug description]
		 * @return [type] [description]
		 */
		function get_debug() {
			return isset( $this->settings['debug'] ) && $this->settings['debug'] == 'yes' ? true : false;
		}

		/**
		 * [get_notification description]
		 * @return [type] [description]
		 */
		function get_notification() {
			return isset( $this->settings['notification'] ) && strlen( $this->settings['notification'] ) !== 0 ? $this->settings['notification'] : get_bloginfo( 'admin_email' );
		}

		/**
		 * [get_sagelinebreak description]
		 * @return [type] [description]
		 */
		function get_sagelinebreak() {
			return isset( $this->settings['sagelinebreak'] ) ? $this->settings['sagelinebreak'] : "0";
		}

		/**
		 * [get_defaultpostcode description]
		 * @return [type] [description]
		 */
		function get_defaultpostcode() {
			return isset( $this->settings['defaultpostcode'] ) && strlen( $this->settings['defaultpostcode'] ) !== 0 ? $this->settings['defaultpostcode'] : $this->default_postcode;
		}

		/**
		 * [get_nullipaddress description]
		 * @return [type] [description]
		 */
		function get_nullipaddress() {
			return isset( $this->settings['nullipaddress'] ) && strlen( $this->settings['nullipaddress'] ) !== 0 ? $this->settings['nullipaddress'] : '86.145.79.226';
		}

		/**
		 * [get_vendortxcodeprefix description]
		 * @return [type] [description]
		 */
		function get_vendortxcodeprefix() {
			$vendortxcodeprefix = isset( $this->settings['vendortxcodeprefix'] ) ? $this->settings['vendortxcodeprefix'] : $this->default_vendortxcodeprefix;
			return str_replace( '-', '_', $vendortxcodeprefix );
		}

		/**
		 * [get_saved_cards description]
		 * @return [type] [description]
		 */
		function get_saved_cards() {
			return isset( $this->settings['tokens'] ) && $this->settings['tokens'] !== 'no' ? 'yes' : $this->default_tokens;
		}

		/**
		 * [get_tokens_message description]
		 * @return [type] [description]
		 */
		function get_tokens_message() {
			return isset( $this->settings['tokensmessage'] ) ? $this->settings['tokensmessage'] : $this->default_tokens_message;
		}

		/**
		 * [get_giftaid description]
		 * @return [type] [description]
		 */
		function get_giftaid() {
			return isset( $this->settings['giftaid'] ) && $this->settings['giftaid'] !== 'no' ? 'yes' : 'no';
		}

		/**
		 * [get_giftaid_message description]
		 * @return [type] [description]
		 */
		function get_giftaid_message() {
			return isset( $this->settings['giftaidmessage'] ) ? $this->settings['giftaidmessage'] : '';
		}

		/**
		 * [get_opayolink description]
		 * @return [type] [description]
		 */
		function get_opayolink() {
			return 0;
		}

		/**
		 * [get_opayologo description]
		 * @return [type] [description]
		 */
		function get_opayologo() {
			return 0;
		}

		/**
		 * [get_basketoption description]
		 * @return [type] [description]
		 */
		function get_basketoption() {
			return isset( $this->settings['basketoption'] ) ? $this->settings['basketoption'] : "1";;
		}

		/**
		 * [get_sagepaytransinfo description]
		 * @return [type] [description]
		 */
		function get_sagepaytransinfo() {
			return isset( $this->settings['sagepaytransinfo'] ) && $this->settings['sagepaytransinfo'] == true ? $this->settings['sagepaytransinfo'] : false;
		}

		/**
		 * [get_sagemagicvalue description]
		 * @return [type] [description]
		 */
		function get_sagemagicvalue() {
			return isset( $this->settings['sagemagicvalue'] ) ? $this->settings['sagemagicvalue'] : $this->default_sagemagicvalue;
		}

		/**
		 * [get_template description]
		 * @return [type] [description]
		 */
		function get_template() {
			return isset( $this->settings['template'] ) ? $this->settings['template'] : 'default';
		}

		/**
		 * [get_opayo_reporting_available description]
		 * @return [type] [description]
		 */
		function get_opayo_reporting_available() {
			return $this->is_opayo_reporting_available();
		}

		/**
		 * [get_vpsprotocol description]
		 * @return [type] [description]
		 */
		function get_vpsprotocol() {
			return '4.00';
		}

		/**
		 * [get_referrerid description]
		 * @return [type] [description]
		 */
		function get_referrerid() {
			return 'F4D0E135-F056-449E-99E0-EC59917923E1';
		}

		/**
		 * Gets the successurl.
		 *
		 * @return     <type>  The successurl.
		 */
		function get_successurl() {
			return WC()->api_request_url( 'WC_Gateway_Sagepay_Direct' );
		}

		/**
		 * Gets the browser language.
		 *
		 * @return     <type>  The browser language.
		 */
		function get_BrowserLanguage() {
			$BrowserLanguage = isset( $_POST['browserLanguage'] ) && $_POST['browserLanguage'] != '' ? wc_clean( $_POST['browserLanguage'] ) : $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			return substr( $BrowserLanguage, 0, 2 );
		}

		function get_BrowserUserAgent() {
			$BrowserUserAgent = isset( $_POST['browserUserAgent'] ) && $_POST['browserUserAgent'] != '' ? wc_clean( $_POST['browserUserAgent'] ) : $_SERVER['HTTP_USER_AGENT'];
			return $BrowserUserAgent;			
		}

		/**
		 * Gets the customer.
		 *
		 * @return     <type>  The customer.
		 */
		function get_customer() {
			return isset( $this->settings['customer'] ) ? $this->settings['customer'] : 'yes';
		}

		/**
		 * Gets the account information.
		 *
		 * @return     <type>  The account information.
		 */
		function get_acctInfo() {
			return isset( $this->settings['acctInfo'] ) ? $this->settings['acctInfo'] : 'yes';
		}

		/**
		 * Gets the merchant risk indicator.
		 *
		 * @return     <type>  The merchant risk indicator.
		 */
		function get_merchantRiskIndicator() {
			return isset( $this->settings['merchantRiskIndicator'] ) ? $this->settings['merchantRiskIndicator'] : 'yes';
		}

		/**
		 * Gets the customer xml.
		 *
		 * @param      <type>  $order  The order
		 *
		 * @return     string  The customer xml.
		 */
		function getCustomerXML( $order ) {

			$xml = '';

			if( 0 !== $order->get_user_id() ) {

                $orders_count_array = get_posts(
                            array(
                                'numberposts' => -1,
                                'meta_key'    => '_customer_user',
                                'meta_value'  => $order->get_user_id(),
                                'post_type'   => wc_get_order_types(),
                                'post_status' => array( 'wc-completed', 'wc-processing' )
                            )
                        );

                if( count( $orders_count_array ) === 0 ) {
                	$previousCust = '0';
                } else {
                	$previousCust = '1';
                }

                $xml  = "<customer>" . "\r\n";
                $xml .= "<previousCust>" . $previousCust . "</previousCust>" . "\r\n";
                $xml .= "<customerId>" . $order->get_user_id() . "</customerId>" . "\r\n";
                $xml .= "</customer>";

			}

			return $xml;

		}

		/**
		 * Gets three ds requestor authentication information.
		 *
		 * @param      <type>  $order  The order
		 *
		 * @return     string  Three ds requestor authentication information.
		 */
		function getThreeDSRequestorAuthenticationInfo( $order ) {

            $deliveryEmailAddress    = NULL;
            $deliveryTimeframe       = NULL;
            $shipIndicator           = NULL;

            $deliveryEmailAddressXML = '';
            $deliveryTimeframeXMO 	 = '';
            $shipIndicatorXML 		 = '';
            $preOrderPurchaseIndXML  = '';

            if ( $order->has_shipping_address() ) {

                $deliveryTimeframe  = "04";

                $billing_address = array(
                        $order->get_billing_address_1(),
                        $order->get_billing_address_2(),
                        $order->get_billing_city(),
                        $order->get_billing_state(),
                        $order->get_billing_postcode(),
                        $order->get_billing_country(),
                    );

                $shipping_address = array(
                        $order->get_shipping_address_1(),
                        $order->get_shipping_address_2(),
                        $order->get_shipping_city(),
                        $order->get_shipping_state(),
                        $order->get_shipping_postcode(),
                        $order->get_shipping_country(),
                    );

                if( MD5( json_encode($billing_address) ) === MD5( json_encode($shipping_address) ) ) {
                    $shipIndicator = "01";
                } else {
                    $shipIndicator = "03";
                }

            } else {

                if( $order->has_downloadable_item() ) {
                    $deliveryEmailAddress   = $order->get_billing_email();
                    $deliveryTimeframe      = "01";
                    $shipIndicator          = "05";
                }

            }
            
            $deliveryEmailAddress   = apply_filters( 'woocommerce_opayo_direct_get_merchantRiskIndicator_deliveryEmailAddress', $deliveryEmailAddress, $order );
            $deliveryTimeframe      = apply_filters( 'woocommerce_opayo_direct_get_merchantRiskIndicator_deliveryTimeframe', $deliveryTimeframe, $order );
            $shipIndicator          = apply_filters( 'woocommerce_opayo_direct_get_merchantRiskIndicator_shipIndicator', $shipIndicator, $order );
            $preOrderPurchaseInd 	= apply_filters( 'woocommerce_opayo_direct_get_merchantRiskIndicator_preOrderPurchaseInd', '01', $order );

            if( !is_null( $deliveryEmailAddress ) ) {
                $deliveryEmailAddressXML = '<deliveryEmailAddress>' . $deliveryEmailAddress . '</deliveryEmailAddress>' . "\r\n";
            }

            if( !is_null( $deliveryTimeframe ) ) {
                $deliveryTimeframeXMO = '<deliveryTimeframe>' . $deliveryTimeframe . '</deliveryTimeframe>' . "\r\n";
            }

            if( !is_null( $shipIndicator ) ) {
                $shipIndicatorXML  = '<shipIndicator>' . $shipIndicator . '</shipIndicator>' . "\r\n";
            }

            if( !is_null( $preOrderPurchaseInd ) ) {
                $preOrderPurchaseIndXML = '<preOrderPurchaseInd>' . $preOrderPurchaseInd . '</preOrderPurchaseInd>' . "\r\n";
            }

            // Bulid the XML
            $xml  = '<merchantRiskIndicator>' . "\r\n";
            $xml .= $deliveryEmailAddressXML;
            $xml .= $deliveryTimeframeXMO;
            $xml .= $preOrderPurchaseIndXML;
            $xml .= $shipIndicatorXML;
            $xml .= '</merchantRiskIndicator>';

            return $xml;              

		}

		/**
		 * Gets the account information.
		 *
		 * @param      <type>  $order  The order
		 *
		 * @return     <type>  The account information.
		 */
		function getAcctInfo( $order ) {

			$xml = '';

            // Length of time that the cardholder has had their online account with you.
            $xml = $this->get_chAccAgeInd( $xml, $order );

            // Date that the cardholder opened their online account with you.
            $xml = $this->get_chAccDate( $xml, $order );

            // Date that cardholder’s online account had a password change or account reset.
            // $xml = $this->get_chAccPwChange( $xml, $order );

            // Number of purchases with this cardholder account during the previous six months.
            $xml = $this->get_nbPurchaseAccount( $xml, $order );

            // Number of transactions (successful and abandoned) for this cardholder account with you, across all payment accounts in the previous 24 hours.
            $xml = $this->get_txnActivityDay( $xml, $order );

            // Number of transactions (successful and abandoned) for this cardholder account with you, across all payment accounts in the previous year.
            $xml = $this->get_txnActivityYear( $xml, $order );

            // Indicates if the Cardholder Name on the account is identical to the shipping Name used for this transaction.
            $xml = $this->get_shipNameIndicator( $xml, $order );

            // Bulid the XML
            $return  = '<acctInfo>' . "\r\n";
            $return .= $xml;
            $return .= '</acctInfo>';

            return $return;              
        }

        /**
         * Length of time that the cardholder has had their online account with you.
         * 
         * GuestCheckout            = No account (guest check-out)
         * CreatedDuringTransaction = Created during this transaction
         * LessThanThirtyDays       = Less than 30 days
         * ThirtyToSixtyDays        = 30-60 days
         * MoreThanSixtyDays        = More than 60 days
         *
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        function get_chAccAgeInd( $xml, $order ) {

            if( 0 !== $order->get_user_id() ) {

                // Get User Object
                $user_object = get_userdata( $order->get_user_id() );

                // Get user registration date and format it.
                $registered_date  = $user_object->user_registered;
                $datetime         = new DateTime( $registered_date );
                $registered_date  = $datetime->format('Ymd');
                $days_registered  = intval( ( strtotime( 'now' ) - strtotime( $registered_date ) ) / 86400 );

                $orders_today_array = get_posts(
                                            array(
                                                'numberposts' => -1,
                                                'meta_key'    => '_customer_user',
                                                'meta_value'  => $order->get_user_id(),
                                                'post_type'   => wc_get_order_types(),
                                                'post_status' => array( 'wc-completed', 'wc-processing' ),
                                                'date_query'  => array(
                                                    'after' => '1 day ago'
                                                )
                                            )
                                        );
                $orders_today = count( $orders_today_array );

                if ( $days_registered < 1 && 0 === $orders_today ) {
                    $chAccAgeInd = "02";
                } elseif ( $days_registered < 30 ) {
                    $chAccAgeInd = "03";
                } elseif ( $days_registered >= 30 && $days_registered <= 60 ) {
                    $chAccAgeInd = "04";
                } else {
                    $chAccAgeInd = "05";
                }
                
            } else {
                $chAccAgeInd = "01";
            }

            $xml .= "<chAccAgeInd>" . apply_filters( 'woocommerce_opayo_direct_get_chAccAgeInd', $chAccAgeInd, $xml, $order ) . "</chAccAgeInd>" . "\r\n";

            return $xml;              
        }

        /**
         * Date that the cardholder opened their online account with you.
         * Format yearmonthday eg 20210522
         * 
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        function get_chAccDate( $xml, $order ) {

            if( 0 !== $order->get_user_id() ) {
                // Get User Object
                $user_object = get_userdata( $order->get_user_id() );
                
                // Get user registration date and format it.
                $registered_date  = $user_object->user_registered;
                $datetime         = new DateTime( $registered_date );
                $registered_date  = $datetime->format('Y-m-d');

                $xml .= "<chAccDate>" . apply_filters( 'woocommerce_opayo_direct_get_chAccDate', $registered_date, $xml, $order ) . "</chAccDate>" . "\r\n";
            }

            return $xml;              
        }

        function get_chAccPwChange( $xml, $order ) {

            if( 0 !== $order->get_user_id() ) {
            	// Get the date that the user last updated their password 
                $date = get_user_meta( $order->get_user_id(), '_user_updated_password_date', TRUE );

                // Check if there is a date and include it.
                if( !empty( $date ) ) {
                	$xml .= "<chAccPwChange>" . apply_filters( 'woocommerce_opayo_direct_get_chAccPwChange', $registered_date, $xml, $order ) . "</chAccPwChange>" . "\r\n";

                	$days_since_password_update  = intval( ( strtotime( 'now' ) - strtotime( $date ) ) / 86400 );

                	if( $days_since_password_update <=1 ) {
                		$chAccPwChangeInd = "01";
                	} elseif ( $days_since_password_update < 30 ) {
	                    $chAccPwChangeInd = "03";
	                } elseif ( $days_since_password_update >= 30 && $days_since_password_update <= 60 ) {
	                    $chAccPwChangeInd = "04";
	                } else {
	                    $chAccPwChangeInd = "05";
	                }

	                $xml .= "<chAccPwChangeInd>" . apply_filters( 'woocommerce_opayo_direct_get_chAccPwChange', $chAccPwChangeInd, $xml, $order ) . "</chAccPwChangeInd>" . "\r\n";

	                $user_object = get_userdata( $order->get_user_id() );

                }
            }

            return $xml;              
        }

        /**
         * Number of purchases with this cardholder account during the previous six months
         * 
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        function get_nbPurchaseAccount( $xml, $order ) {

            if( 0 !== $order->get_user_id() ) {
                $orders_count_array = get_posts(
                                            array(
                                                'numberposts' => -1,
                                                'meta_key'    => '_customer_user',
                                                'meta_value'  => $order->get_user_id(),
                                                'post_type'   => wc_get_order_types(),
                                                'post_status' => array( 'wc-completed', 'wc-processing' ),
                                                'date_query'  => array(
                                                    'after' => '6 month ago'
                                                )
                                            )
                                        );
                if( !is_null( $orders_count_array ) ) {
                    $xml .= "<nbPurchaseAccount>" . apply_filters( 'woocommerce_opayo_direct_get_nbPurchaseAccount', count( $orders_count_array ), $xml, $order ) . "</nbPurchaseAccount>" . "\r\n";
                }
            }

            return $xml;              
        }

        /**
         * Number of transactions (successful and abandoned) for this cardholder account with you, across all payment accounts in the previous 24 hours.
         * 
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        function get_txnActivityDay( $xml, $order ) {

            if( 0 !== $order->get_user_id() ) {
                $orders = get_posts(
                                    array(
                                        'numberposts' => -1,
                                        'meta_key'    => '_customer_user',
                                        'meta_value'  => $order->get_user_id(),
                                        'post_type'   => wc_get_order_types(),
                                        'post_status' => array_keys( wc_get_order_statuses() ),
                                        'date_query'  => array(
                                            'after' => '1 day ago'
                                        )
                                    )
                                );
                if( !is_null( $orders ) ) {
                    $xml .= "<txnActivityDay>" . apply_filters( 'woocommerce_opayo_direct_get_txnActivityDay', count($orders), $xml, $order ) . "</txnActivityDay>" . "\r\n";
                }
            }

            return $xml;              

        }

        /**
         * Number of transactions (successful and abandoned) for this cardholder account with you, across all payment accounts in the previous year.
         * 
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        function get_txnActivityYear( $xml, $order ) {

            if( 0 !== $order->get_user_id() ) {
                $orders = get_posts(
                                    array(
                                        'numberposts' => -1,
                                        'meta_key'    => '_customer_user',
                                        'meta_value'  => $order->get_user_id(),
                                        'post_type'   => wc_get_order_types(),
                                        'post_status' => array_keys( wc_get_order_statuses() ),
                                        'date_query'  => array(
                                            'after' => '1 year ago'
                                        )
                                    )
                                );

                if( !is_null( $orders ) ) {
                    // Max 999
                    $count = count($orders);
                    if( count($orders) >= 999 ) {
                        $count = 999;
                    }
                    $xml .= "<txnActivityYear>" . apply_filters( 'woocommerce_opayo_direct_get_txnActivityYear', $count, $xml, $order ) . "</txnActivityYear>" . "\r\n";
                }
            }

            return $xml;

        }

        /**
         * Indicates if the Cardholder Name on the account is identical to the shipping Name used for this transaction.
         *
         * FullMatch    = Account Name identical to shipping Name
         * NoMatch      = Account Name different than shipping Name
         * 
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        function get_shipNameIndicator( $xml, $order ) {

            if ( $order->has_shipping_address() ) {

                if( md5( strtolower( $order->get_billing_first_name().$order->get_billing_last_name() ) ) === md5( strtolower( $order->get_shipping_first_name().$order->get_shipping_last_name() ) ) ) {
                    $shipNameIndicator = "01";
                } else {
                    $shipNameIndicator = "02";
                }

                $xml .= "<shipNameIndicator>" . apply_filters( 'woocommerce_opayo_direct_get_shipNameIndicator', $shipNameIndicator, $xml, $order ) . "</shipNameIndicator>" . "\r\n";

            }

            return $xml;

        }

		/**
		 * Check if this gateway is enabled
		 */
		function is_available() {

			if ( $this->get_enabled() == "yes" ) {

				if ( !$this->is_secure() && ! $this->get_status() == 'live' ) {
					return false;
				}

				// Required fields check
				if ( ! $this->get_vendor() ) {
					return false;
				}

				return true;

			}
			return false;

		}

		/**
		 * [opayo_reporting_available description]
		 * @return [type] [description]
		 */
		function is_opayo_reporting_available() {

			$reporting 	= get_option( 'woocommerce_opayo_reporting_options' );

			if ( $reporting === false ) {
				return false;
			}

			if ( $this->status == 'live' ) {
				if( isset( $reporting['live_opayo_reporting_username'] ) && $reporting['live_opayo_reporting_username']  != '' ) {
					return true;
				}

			} else {
				if( isset( $reporting['test_opayo_reporting_username'] ) && $reporting['test_opayo_reporting_username']  != '' ) {
					return true;
				}
			}

			return false;

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
		 * [get_opayo_direct_supports description]
		 * @return [type] [description]
		 */
		function get_opayo_direct_supports() {

			$supports = array(
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
           	if( $this->get_saved_cards() == 'yes' ) {
           		$supports[] = 'tokenization';
           	}

    		return $supports;
		}

		/**
		 * [$set_cardtypes description]
		 * Set up accepted card types for card type drop down
		 * From Version 3.3.0
		 * @var array
		 *
		 * When using the wc_sagepaydirect_cardtypes filter DO NOT change the Key, only change the Value.
		 */
		function set_cardtypes() {

			return apply_filters( 'wc_sagepaydirect_cardtypes', array(
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

		}

    	/**
    	 * Opayo has specific requirements for the credit card type field
    	 * @param  [type] $cardNumber   [description]
    	 * @param  [type] $card_details [description]
    	 * @return [type]               [Card Type]
    	 */
		function cc_type( $cardNumber, $card_details ) {
        	
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
    	 * Opayo has specific requirements for the credit card type field
    	 * @param  [type] $cardNumber   [description]
    	 * @param  [type] $card_details [description]
    	 * @return [type]               [Card Type]
    	 */
		function cc_type_name( $cc_type ) {
        	
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
    	 * [str_replace_assoc description]
    	 * @param  array  $replace [description]
    	 * @param  [type] $subject [description]
    	 * @return [type]          [description]
    	 */
		function str_replace_assoc( array $replace, $subject ) {
   			return str_replace( array_keys($replace), array_values($replace), $subject );   
		}
		// Scripts

		/**
		 * Enqueue scripts for the CC form.
		 */
		function sagepaydirect_scripts() {
        	
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		
			wp_enqueue_style( 'wc-sagepaydirect', SAGEPLUGINURL.'assets/css/checkout.css', array(), OPAYOPLUGINVERSION, false);

			if ( ! wp_script_is( 'wc-credit-card-form', 'registered' ) ) {
				wp_register_script( 'wc-credit-card-form', SAGEPLUGINURL.'assets/js/credit-card-form.js', array( 'jquery', 'jquery-payment' ), OPAYOPLUGINVERSION, true );
			}

			// 3D Secure 2
			wp_register_script( 'wc-3dsbrowser', SAGEPLUGINURL.'assets/js/3dsbrowser' . $suffix . '.js', array( 'jquery', 'jquery-payment' ), OPAYOPLUGINVERSION, true );

		}

		/**
		 * [get_proctocol_4_script description]
		 * @return [type] [description]
		 */
		function get_proctocol_4_script() {
			return "
<script type='text/javascript' language='javascript'>

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

	var sageform = document.getElementById( 'sagepaydirect-cc-form' );

	function createHiddenInput( form, name, value ) {

		var input = document.createElement('input');
		input.setAttribute( 'type', 'hidden' );
		input.setAttribute( 'name', name ); 
		input.setAttribute( 'value', value );
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

</script>";
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
				OPAYOPLUGINVERSION
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
				OPAYOPLUGINVERSION
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
				OPAYOPLUGINVERSION
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
				OPAYOPLUGINVERSION
			);
		}		

		/**
		 * Enqueues our Giftaid script to handle some of the new form options.
		 */
		public function giftaid_script() {

			if ( $this->get_giftaid()  == 'yes' ) {
				wp_enqueue_script(
					'sagepay-giftaid',
					SAGEPLUGINURL.'assets/js/giftaid.js',
					array( 'jquery' ),
					OPAYOPLUGINVERSION
				);				
			}

		}

		// Admin settings
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
			<table class="form-table">
			<?php
				// Generate the HTML for the settings form.
				$this->generate_settings_html();
			?>
			</table><!--/.form-table-->
			<?php

		} // END admin_options

		// Helper Functions
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
			if( get_post_meta( $order_id, '_created_via', TRUE ) == 'checkout' && $order->get_payment_method() == 'sagepaydirect' ) {
				WC()->cart->empty_cart();
			}
		}

    	/**
    	 * [get_card_expiry_date description]
    	 * @param  [type] $expiry_date [description]
    	 * @param  [type] $arg         [description]
    	 * @return [type]              [description]
    	 */
		public function get_card_expiry_date( $expiry_date, $arg ) {

			$expiry_date = str_replace( '/', ',', $expiry_date );
			
			// If there was no / then try again using the first space
			if ( strpos($expiry_date, ',' ) === false ) {
			    
			    if ( strpos( $expiry_date, ' ' ) !== false) {
				    $expiry_date = substr_replace( $expiry_date, ',', strpos( $expiry_date, ' ' ), strlen(' ') );
				}

			}

			// Remove any remaining spaces
			$expiry_date = str_replace( ' ', '', $expiry_date );

			// Explode on ,
			$expiry_date = explode( ',', $expiry_date );

			// If we don't have an array then use the first 2 characters and the last 2 characters to make an array
			if( count( $expiry_date ) === 1 ) {
				$expiry_date_month 	= substr( $expiry_date[0], 0, 2 );
				$expiry_date_year 	= substr( $expiry_date[0], -2 );

				$expiry_date = array(
									$expiry_date_month,
									$expiry_date_year
								);
			}

			if( $arg == 'month' ) {
				return str_pad( $expiry_date[0], 2, "0", STR_PAD_LEFT );
			}

			if( $arg == 'year' ) {
				return substr( $expiry_date[1], -2 );
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

			if ( strlen( $string ) > $limit ) {
				$string = substr( $string, 0, $limit );
			}
			
			return $string;
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
		 * [set_vendortxcode description]
		 * @param [type] $order_id     [description]
		 * @param [type] $VendorTxCode [description]
		 */
		function set_vendortxcode( $order_id, $VendorTxCode ) {
			update_post_meta( $order_id, '_VendorTxCode' , $VendorTxCode );
			update_post_meta( $order_id, '_RelatedVendorTxCode' , $VendorTxCode );
		}

		/**
		 * Return challenge window size
		 *
		 * 01 = 250 x 400
		 * 02 = 390 x 400
		 * 03 = 500 x 600
		 * 04 = 600 x 400
		 * 05 = Full screen
		 */
		function get_challenge_window_size( $width, $height ) {

			if( $width <= '250' ) {
				return '01';
			}

			if( $width <= '390' ) {
				return '02';
			}

			if( $width <= '500' ) {
				return '03';
			}

			if( $width <= '600' ) {
				return '04';
			}

			return '05';

		}

		/**
		 * [append_url description]
		 * @param  [type] $url [description]
		 * @return [type]      [description]
		 */
		function append_url( $url ) {

			// Set url tracking
            if( $this->get_threeDS_tracking() ) {
                $url = add_query_arg( array(
                    'utm_nooverride' => 1
                ), $url );
            }

            return $url;

		}

		/**
		 * [pareq_or_creq description]
		 * @param  [type] $sage_3dsecure [description]
		 * @return [type]                [description]
		 */
	    function pareq_or_creq( $sage_3dsecure ) {

	        // Get ready to set form fields for 3DS 1.0/2.0
	        if( isset( $sage_3dsecure['PAReq'] ) ) {
	            $p = array(
	                "field_name"    => "PaReq",
	                "field_value"   => $sage_3dsecure['PAReq']
	            );
	        } else {
	            $p = array(
	                "field_name"    => "creq",
	                "field_value"   => $sage_3dsecure['CReq']
	            );
	        }

	        return $p;

	    }

	    /**
	     * [md_or_vpstxid description]
	     * @param  [type] $sage_3dsecure [description]
	     * @return [type]                [description]
	     */
	    function md_or_vpstxid( $sage_3dsecure ) {

	        if( isset( $sage_3dsecure['MD'] ) ) {
	            $m = array(
	                "field_name" => "MD",
	                "field_value" => $sage_3dsecure['MD']
	            );
	        } else {
	            $m = array(
	                "field_name" => "VPSTxId",
	                "field_value" => $sage_3dsecure['VPSTxId']
	            );
	        }

	        return $m;
	    }

        /**
         * [add_order_note description]
         * @param [type] $message [description]
         * @param [type] $result  [description]
         * @param [type] $order   [description]
         */
        function add_order_note( $message, $result = NULL, $order = NULL ) {

        	$ordernote = '';

        	if( is_array($result) ) {
        		// Add plugin version to order notes
        		$result["Version"] = OPAYOPLUGINVERSION;

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

        		update_post_meta( $order_id, '_sageresult', $result );

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

		        		if( ( $trial_period && $trial_period != '')  || $order->get_total() == 0 ) {

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
		 * [save_token description]
		 * @param  [type] $token        [description]
		 * @param  [type] $card_type    [description]
		 * @param  [type] $last4        [description]
		 * @param  [type] $expiry_month [description]
		 * @param  [type] $expiry_year  [description]
		 * @return [type]               [description]
		 */
		function save_token( $sagetoken, $card_type, $last4, $expiry_month, $expiry_year ) {

			$token = new WC_Payment_Token_CC();

			$token->set_token( str_replace( array('{','}'),'',$sagetoken ) );
			$token->set_gateway_id( $this->id );
			$token->set_card_type( self::cc_type_name( self::cc_type( '', $card_type ) ) );
			$token->set_last4( $last4 );
			$token->set_expiry_month( $expiry_month );
			$token->set_expiry_year( 2000 + $expiry_year );
			$token->set_user_id( get_current_user_id() );

			$token->save();

		}

        /**
         * [verify_data description]
         * @param  [type] $data [description]
         * @return [type]       [description]
         */
        function verify_data( $data ) {

        	if( count($data) != count(array_filter($data, "strlen")) ) {
        		return false;
        	}

        	return true;
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
         * [opayo_wc_clean description]
         * @param  [type] $value [description]
         * @return [type]        [description]
         */
        function opayo_wc_clean( $value ) {

        	$value = wc_clean( $value );
        	$value = trim( $value );

        	$value = strlen( $value ) === 0 ? false : $value;

        	return $value;
        }
        /**
         * [remove_woocommerce_subscriptions_update_payment_via_pay_shortcode description]
         * @return [type] [description]
         */
        public function remove_woocommerce_subscriptions_update_payment_via_pay_shortcode() {
        	return false;
        }

		/**
		 * Send the info to Sage for processing
		 * https://test.sagepay.com/showpost/showpost.asp
		 */
        function sagepay_post( $data, $url ) {

        	if( $this->get_status() == 'developer' ) {
				$url = 'https://woocommerce-sagepay.com/posttest/postman.php';
        	}

        	// Debugging
        	if ( $this->get_debug() == true || $this->get_status() != 'live' ) {
        		$to_log['DATA'] = $data;
        		$to_log['URL'] 	= $url;
	  			WC_Sagepay_Common_Functions::sagepay_debug( $to_log, $this->id, __('Sent to Opayo : ', 'woocommerce-gateway-sagepay-form'), TRUE );
			}

			// Convert $data array to query string for Sage
        	if( is_array( $data) ) {
        		// Convert the $data array for Sage
	            $data = http_build_query( $data, '', '&', PHP_QUERY_RFC3986 );
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

				// Log Error
  				WC_Sagepay_Common_Functions::sagepay_debug( $res->get_error_message(), $this->id, __('Remote Post Error : ', 'woocommerce-gateway-sagepay-form'), FALSE );

			} else {

				// Debugging
				if ( $this->get_debug() == true || $this->get_status() != 'live' ) {
					WC_Sagepay_Common_Functions::sagepay_debug( $res['body'], $this->id, __('Opayo Direct Return : ', 'woocommerce-gateway-sagepay-form'), FALSE );
				}

			}

			return $res;

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
			$sagelinebreak 	= $this->sage_line_break( $this->get_sagelinebreak() );
            $results  		= preg_split( $sagelinebreak, $array );

            foreach( $results as $result ){ 

            	$value = explode( '=', $result, 2 );
                $response[trim($value[0])] = trim($value[1]);

            }

            return $response;

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

	} // END CLASS
