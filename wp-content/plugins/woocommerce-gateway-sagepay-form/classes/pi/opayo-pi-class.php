<?php

    /**
     * WC_Gateway_Opayo_Pi class.
     *
     * @extends WC_Payment_Gateway
     */
    class WC_Gateway_Opayo_Pi extends WC_Payment_Gateway {
        /**
         * [$cardtypes description]
         * Set up accepted card types for card type drop down
         * @var array
         */
        var $cardtypes = array(
                                'MasterCard'        => 'MasterCard',
                                'MasterCard Debit'  => 'MasterCard Debit',
                                'Visa'              => 'Visa',
                                'Visa Debit'        => 'Visa Debit',
                                'Discover'          => 'Discover',
                                'American Express'  => 'American Express',
                                'Maestro'           => 'Maestro',
                                'JCB'               => 'JCB',
                                'Laser'             => 'Laser'
                            );

        /**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {

            // Always false for production
            $this->unittests                         = apply_filters( 'opayo_pi_unit_tests', false );

            $this->id                                = 'opayopi';
            $this->method_title                      = __( 'Opayo Pi', 'woocommerce-gateway-sagepay-form' );
            $this->method_description                = $this->pi_payment_method_description();
            $this->icon                              = apply_filters( 'wc_opayopi_icon', '' );
            $this->has_fields                        = false;

            $this->sagepay_version                   = OPAYOPLUGINVERSION;

            $this->default_title                     = __( 'Credit Card via Opayo', 'woocommerce-gateway-sagepay-form' );
            $this->default_description               = __( 'Pay via Credit / Debit Card with Opayo secure card processing.', 'woocommerce-gateway-sagepay-form' );
            $this->default_order_button_text         = __( 'Pay securely with Opayo', 'woocommerce-gateway-sagepay-form' );
            $this->default_status                    = 'testing';
            $this->default_VendorName                = 'sandbox';
            $this->default_Integration_Key           = '';
            $this->default_Integration_Password      = '';
            $this->default_Test_Integration_Key      = 'hJYxsw7HLbj40cB8udES8CDRFLhuJ8G54O6rDpUXvE6hYDrria';
            $this->default_Test_Integration_Password = 'o2iHSrFybYMZpmWOQMuhsXP52V4fBtpuSDshrKDSWsBY1OiN6hwd9Kb12z4j5Us5u';
            $this->default_txtype                    = 'Payment';
            $this->default_cardtypes                 = $this->cardtypes;
            $this->default_tokens                    = 'no';
            $this->default_tokens_message            = '';
            $this->default_debug                     = 'no';
            $this->default_notification              = get_bloginfo('admin_email');
            $this->default_vendortxcodeprefix        = 'wc_';
            $this->default_checkout_form             = 'woocommerce';
            $this->default_pimagicvalue              = 'NO';

            $this->default_nullipaddress             = WC_Sagepay_Common_Functions::get_icanhazip();
            
            // Load the form fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            $this->enabled                           = $this->settings['enabled'];
            $this->title                             = isset( $this->settings['title'] ) ? $this->settings['title'] : $this->default_title;
            $this->description                       = isset( $this->settings['description'] ) && strlen( $this->settings['description'] ) != 0 ? $this->settings['description'] : $this->default_description;
            $this->order_button_text                 = isset( $this->settings['order_button_text'] ) ? $this->settings['order_button_text'] : $this->default_order_button_text;
            $this->status                            = isset( $this->settings['status'] ) ? $this->settings['status'] : $this->default_status;
            $this->vendor                            = isset( $this->settings['vendor'] ) ? $this->settings['vendor'] : $this->default_VendorName;
            $this->Live_Integration_Key              = isset( $this->settings['Integration_Key'] ) ? $this->settings['Integration_Key'] : $this->default_Integration_Key;
            $this->Live_Integration_Password         = isset( $this->settings['Integration_Password'] ) ? $this->settings['Integration_Password'] : $this->default_Integration_Password;
            $this->Test_Integration_Key              = isset( $this->settings['Test_Integration_Key'] ) ? $this->settings['Test_Integration_Key'] : $this->default_Test_Integration_Key;
            $this->Test_Integration_Password         = isset( $this->settings['Test_Integration_Password'] ) ? $this->settings['Test_Integration_Password'] : $this->default_Test_Integration_Password;
            $this->txtype                            = isset( $this->settings['txtype'] ) ? $this->settings['txtype'] : $this->default_txtype;
            $this->cardtypes                         = isset( $this->settings['cardtypes'] ) ? $this->settings['cardtypes'] : $this->default_cardtypes;
            $this->store_tokens                      = isset( $this->settings['tokens'] ) && $this->settings['tokens'] == 'yes' ? true : false;
            $this->tokens_message                    = '';
            $this->debug                             = isset( $this->settings['debug'] ) && $this->settings['debug'] == 'yes' ? true : false;
            $this->notification                      = isset( $this->settings['notification'] ) ? $this->settings['notification'] : $this->default_notification;
            $this->vendortxcodeprefix                = isset( $this->settings['vendortxcodeprefix'] ) ? $this->settings['vendortxcodeprefix'] : $this->default_vendortxcodeprefix;
            $this->checkout_form                     = isset( $this->settings['checkout_form'] ) ? $this->settings['checkout_form'] : $this->default_checkout_form;
            $this->pimagicvalue                      = isset( $this->settings['pimagicvalue'] ) ? $this->settings['pimagicvalue'] : $this->default_pimagicvalue;

            $this->cvv_script                        = apply_filters( 'woocommerce_sagepay_pi_use_cvv_for_token_payments', TRUE );
            $this->sagelink                          = false;
            $this->sagelogo                          = false;

            $this->nullipaddress                     = isset( $this->settings['nullipaddress'] ) ? $this->settings['nullipaddress'] : $this->default_nullipaddress;

            // ReferrerID
            $this->referrerid                        = 'F4D0E135-F056-449E-99E0-EC59917923E1';

            // Make sure $this->vendortxcodeprefix is clean
            $this->vendortxcodeprefix = str_replace( '-', '_', $this->vendortxcodeprefix );

            // Set the integration key and password for Live/Test
            if ( $this->status == 'live' ) {
                $this->Integration_Key      = $this->Live_Integration_Key;
                $this->Integration_Password = $this->Live_Integration_Password;
            } else {
                $this->Integration_Key      = $this->Test_Integration_Key;
                $this->Integration_Password = $this->Test_Integration_Password;
            }

            // URLS
            if( $this->status == 'live' ) {
                $this->merchant_session_keys_url    = 'https://pi-live.sagepay.com/api/v1/merchant-session-keys';
                $this->card_identifiers_url         = 'https://pi-live.sagepay.com/api/v1/card-identifiers';
                $this->transaction_url              = 'https://pi-live.sagepay.com/api/v1/transactions';
                $this->callbackURL                  = 'https://pi-live.sagepay.com/api/v1/transactions/<transactionId>/3d-secure';
                $this->callbackURLTwo               = 'https://pi-live.sagepay.com/api/v1/transactions/<transactionId>/3d-secure-challenge';
                $this->retrieve_url                 = 'https://pi-live.sagepay.com/api/v1/transactions/<transactionId>';
                $this->instructions_url             = 'https://pi-live.sagepay.com/api/v1/transactions/<transactionId>/instructions';
            } else {
                $this->merchant_session_keys_url    = 'https://pi-test.sagepay.com/api/v1/merchant-session-keys';
                $this->card_identifiers_url         = 'https://pi-test.sagepay.com/api/v1/card-identifiers';
                $this->transaction_url              = 'https://pi-test.sagepay.com/api/v1/transactions';
                $this->callbackURL                  = 'https://pi-test.sagepay.com/api/v1/transactions/<transactionId>/3d-secure';
                $this->callbackURLTwo               = 'https://pi-test.sagepay.com/api/v1/transactions/<transactionId>/3d-secure-challenge';
                $this->retrieve_url                 = 'https://pi-test.sagepay.com/api/v1/transactions/<transactionId>';
                $this->instructions_url             = 'https://pi-test.sagepay.com/api/v1/transactions/<transactionId>/instructions';
            }

            // Supports
            $this->supports = array(
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
            if( $this->store_tokens ) {
                $this->supports[] = 'tokenization';
            }

            // Logs
            if ( $this->debug ) {
                $this->log = new WC_Logger();
            }

            // WC version
            $this->wc_version = get_option( 'woocommerce_version' );

            $this->opayo_description = $this->get_checkout_description();

            // Set URLs for loading script files from Sage.
            $this->checkout_test_script_url = 'https://pi-test.sagepay.com/api/v1/js/sagepay.js';
            $this->checkout_live_script_url = 'https://pi-live.sagepay.com/api/v1/js/sagepay.js';
            $this->dropin_test_script_url   = 'https://pi-test.sagepay.com/api/v1/js/sagepay-dropin.js';
            $this->dropin_live_script_url   = 'https://pi-live.sagepay.com/api/v1/js/sagepay-dropin.js';

            // Set checkout script.
            $this->checkout_script_url      = $this->status != 'live' ? $this->checkout_test_script_url : $this->checkout_live_script_url;
            $this->dropin_script_url        = $this->status != 'live' ? $this->dropin_test_script_url : $this->dropin_live_script_url;

            if( isset( $this->checkout_form ) && $this->checkout_form == 'dropin' ) {
                $this->checkout_script = $this->dropin_script_url;
            } else {
                $this->checkout_script = $this->checkout_script_url;
            }

            // WooCommerce payment gateway API
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // Check this is enabled 
            if( $this->enabled == 'yes' ) {
                /**
                 *  API
                 *  woocommerce_api_{lower case class name}
                 */
                add_action( 'woocommerce_api_wc_gateway_opayo_pi', array( $this, 'check_response' ) );
                add_action( 'woocommerce_receipt_opayopi', array($this, 'authorise_3dsecure') );

                // Capture authorised payments
                add_action ( 'woocommerce_order_action_opayopi_process_payment', array( $this, 'process_instruction' ) );

                // Pre-Orders
                if ( class_exists( 'WC_Pre_Orders_Order' ) ) {
                    add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array( $this, 'process_instruction' ) );
                }

                // Subscriptions
                if ( class_exists( 'WC_Subscriptions_Order' ) ) {
                    add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'process_scheduled_subscription_payment' ), 10, 2 );
                }

                // Show any stored error messages
                add_action( 'woocommerce_before_checkout_form', array( $this, 'show_errors' ) );
                add_action( 'woocommerce_subscription_details_table', array( $this, 'show_errors' ), 1 );

                // Scripts
                if( is_checkout() ) {
                    add_action( 'wp_enqueue_scripts', array( $this, 'opayopi_scripts' ), 1002 );
                }

            }

        } // END __construct

        /**
         * init_form_fields function.
         *
         * @access public
         * @return void
         */
        function init_form_fields() {
            include ( SAGEPLUGINPATH . 'assets/php/opayo-pi-admin.php' );
        }

        /**
         * [get_icon description] Add selected card icons to payment method label, defaults to Visa/MC/Amex/Discover
         * @return [type] [description]
         */
        public function get_icon() {
            return WC_Sagepay_Common_Functions::get_icon( $this->cardtypes, $this->sagelink, $this->sagelogo, $this->id );
        }

        /**
         * [get_checkout_description description]
         * @return [type] [description]
         */
        public function get_checkout_description() {

            $description = '<p class="woocommerce_opayo_pi_description">' . $this->description . '</p>';

            // Add test card info to the description if in test mode
            if ( $this->status != 'live' ) {
                $description .= sprintf( __( '<p class="woocommerce_opayo_pi_description">TEST MODE ENABLED.<br />In test mode, you can use Visa card number 4929000000006 with any CVC and a valid expiration date or check the documentation (<a href="%s">Test card details for your test transactions</a>) for more card numbers.</p>', 'woocommerce-gateway-sagepay-form' ), 'https://www.opayo.co.uk/support/12/36/test-card-details-for-your-test-transactions' );
            }

            return $description;

        }

        /**
         * [opayopi_scripts description]
         * @return [type] [description]
         */
        public function opayopi_scripts() {

            wp_enqueue_script( 'wc-opayopi-dropin', $this->checkout_script, array(), $this->sagepay_version, true );

            if( isset( $this->checkout_form ) && $this->checkout_form == 'dropin' ) {
                wp_enqueue_script( 'opayo_pi_dropin_checkout', SAGEPLUGINURL . '/assets/js/pi/opayopi-dropin.js', array('jquery'), $this->sagepay_version, true );
                wp_localize_script( 'opayo_pi_dropin_checkout', 'opayopi_params', array('merchantSessionKeyArray' => WC()->session->get('merchantSessionKeyArray') ) );
            } else {
                if ( wp_script_is( 'wc-credit-card-form', 'registered' ) ) {
                    wp_deregister_script( 'wc-credit-card-form' );
                    wp_register_script( 'wc-credit-card-form', SAGEPLUGINURL.'assets/js/pi/format-fields.js', array( 'jquery', 'jquery-payment' ), OPAYOPLUGINVERSION, true );
                }
            }

        }

        /**
         * Payment form on checkout page
         * http://integrations.sagepay.co.uk/content/getting-started-integrate-using-drop-checkout
         */
        public function payment_fields() {

            try {

                // Allow for token checkbox    
                $display_tokenization = $this->supports( 'tokenization' ) && is_checkout();

                // Always create a new Opayo Session, sessions only last 5 mins.
                $merchantSessionKeyArray  = $this->get_merchantSessionKey();
                WC()->session->set( 'merchantSessionKeyArray', $merchantSessionKeyArray );

                // Check for errors
                if( isset( $merchantSessionKeyArray['errors'] ) ) {
                    throw new Exception( __( 'There was an error. Please contact the site administrator.<br /><br />The error message is : ', 'woocommerce-gateway-sagepay-form' ) . $merchantSessionKeyArray['errors'][0]['description'] );
                }

                // Check for errors
                if( isset( $merchantSessionKeyArray["description"] ) && isset( $merchantSessionKeyArray["code"] ) ) {
                    throw new Exception( __( 'There was an error. Please contact the site administrator.<br /><br />The error message is : ', 'woocommerce-gateway-sagepay-form' ) . $merchantSessionKeyArray["description"] );
                }

                $merchantSessionKey       = $merchantSessionKeyArray["merchantSessionKey"];
                $merchantSessionKeyExpiry = $merchantSessionKeyArray["expiry"];

                // Load the scripts after the session has been sucessfully created. 
                $this->opayopi_scripts();

                if( isset( $this->checkout_form ) && $this->checkout_form == 'dropin' ) {

                    // Opayo Dropin checkout form
                    include_once( 'opayo-pi-dropin-payment-fields-class.php' );
                    $payment_fields = new Opayo_Pi_Dropin_Payment_Fields();
                    $payment_fields->fields();

                } else {

                    // WooCommerce checkout form

                    // Add tokenization script
                    if ( $display_tokenization && class_exists( 'WC_Payment_Token_CC' ) ) {
                        // Add script to remove card fields if CVV required with tokens
                        if( $this->cvv_script ) {
                            $this->cvv_script();
                        } else {
                            $this->tokenization_script();
                        }
                    }

                    include_once( 'opayo-pi-payment-fields-class.php' );
                    $payment_fields = new Opayo_Pi_Payment_Fields();
                    $payment_fields->fields();

                }

                // Additional fields required for Protocol 4.00
                echo $this->get_proctocol_4_script();

            } catch( Exception $e ) {
                // Display any errors
                echo ( $e->getMessage() );

                // Clear the merchantSessionKeyArray session variable
                WC()->session->set( 'merchantSessionKeyArray', false );
                return;
            }

        }

        /**
         * process_payment function.
         *
         * @access public
         * @param mixed $order_id
         * @return void
         */
        function process_payment( $order_id ) {

            // Get the WooCommerce order object
            $order = new WC_Order( $order_id );

            // Clear session and order meta
            WC()->session->set( 'merchantSessionKeyArray', false );
            WC()->session->set( "opayo_3ds", false );
            delete_post_meta( $order_id, '_opayo_3ds' );
            delete_post_meta( $order_id, '_opayo_cardIdentifier' );
            delete_post_meta( $order_id, '_save_card' );

            $save_card = false;

            // Start the common 3D Secure functions class
            $common_threeds = new WC_Opayo_Common_Threeds_Functions();

            $card_details = array(
                                "merchantSessionKey"        => wc_clean( $_POST['opayopi-merchantSessionKey'] ),
                                "cardholderName"            => $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
                                "cardNumber"                => isset( $_POST['opayopi-card-number'] ) ? wc_clean( $_POST['opayopi-card-number'] ) : NULL,
                                "expiryDate"                => isset( $_POST['opayopi-card-expiry'] ) ? wc_clean( $_POST['opayopi-card-expiry'] ) : NULL,
                                "securityCode"              => isset( $_POST['opayopi-card-cvc'] ) ? wc_clean( $_POST['opayopi-card-cvc'] ) : NULL,
                                "savecard"                  => isset( $_POST['wc-opayopi-new-payment-method'] ) ? wc_clean( $_POST['wc-opayopi-new-payment-method'] ) : false,
                                "token_id"                  => isset( $_POST['wc-opayopi-payment-token'] ) ? wc_clean( $_POST['wc-opayopi-payment-token'] ) : false,
                                "cardIdentifier"            => isset( $_POST['opayopi-cardIdentifier'] ) ? wc_clean( $_POST['opayopi-cardIdentifier'] ) : false,
                                "browserJavascriptEnabled"  => wc_clean( $_POST['browserJavascriptEnabled'] ) == 'true' ? 1 : 0,
                                "browserJavaEnabled"        => wc_clean( $_POST['browserJavaEnabled'] ) == 'true' ? 1 : 0,
                                "browserLanguage"           => isset( $_POST['browserLanguage'] ) && $_POST['browserLanguage'] != '' ? wc_clean( $_POST['browserLanguage'] ) : substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2),
                                "browserColorDepth"         => wc_clean( $_POST['browserColorDepth'] ),
                                "browserScreenHeight"       => wc_clean( $_POST['browserScreenHeight'] ),
                                "browserScreenWidth"        => wc_clean( $_POST['browserScreenWidth'] ),
                                "browserTZ"                 => wc_clean( $_POST['browserTZ'] ),
                                "browserUserAgent"          => isset( $_POST['browserUserAgent'] ) && $_POST['browserUserAgent'] != '' ? wc_clean( $_POST['browserUserAgent'] ) : $_SERVER['HTTP_USER_AGENT'],
                                "challengeWindowSize"       => $common_threeds::get_challenge_window_size( wc_clean( $_POST['browserScreenWidth'] ), wc_clean( $_POST['browserScreenHeight'] ) ),
                                "transType"                 => $this->get_transType( $order )
                            );

            // Get the transaction data
            $data = $this->get_common_transaction_data( $card_details, $order );

            // Get the payment method array
            $paymentmethod          = $this->get_transaction_data( $card_details, $order );
            
            // End the process in the event of an error
            if( isset( $paymentmethod['error'] ) && $paymentmethod['error'] != '' ) {
                $result = array( 
                    'status'        => "ERROR",
                    'statusDetail'  => $paymentmethod['error']
                );
                return $this->process_result( $result, $order, $order_id );
            }

            $data["paymentMethod"]  = $paymentmethod;

            // Send $data to Opayo
            $result = $this->remote_post( $data, $this->transaction_url, NULL, 'Basic' ); 

            // Check for errors
            if( isset( $result['errors'] ) ) {

                $message = $result['errors'][0]['description'] . ":" . $result['errors'][0]['property'];
                $order->add_order_note( $message );
                throw new Exception( __( 'There was an error processing your payment. Please check your details and try again.<br /><br />The error message is : ', 'woocommerce-gateway-sagepay-form' ) . print_r( $message, TRUE ) );

                // Clear session and order meta
                WC()->session->set( 'merchantSessionKeyArray', false );
                WC()->session->set( "opayo_3ds", false );
                delete_post_meta( $order_id, '_opayo_3ds' );

                exit;
            }

            // Add plugin version to order notes
            $result["Version"] = OPAYOPLUGINVERSION;

            // Verify Opayo Return
            if( isset( $result['status'] ) && strtoupper( $result['status'] ) == 'OK' ) {
                $result = $this->verify_opayo( $order_id, $result );

            }

            // Clear the merchantSessionKeyArray session variable
            WC()->session->set( 'merchantSessionKeyArray', false );

            // Process the result from Opayo
            return $this->process_result( $result, $order, $order_id );
            
        }

        /**
         * [authorise_3dsecure description]
         * @param  [type] $order_id [description]
         * @return [type]           [description]
         */
        function authorise_3dsecure( $order_id ) {

            $order = new WC_Order( $order_id );

            $opayo_3ds  = get_post_meta( $order_id, '_opayo_3ds', TRUE );

            if( !isset( $opayo_3ds['status'] ) ) {
                $opayo_3ds  = WC()->session->get( "opayo_3ds" );
            }

            $transactionId = isset( $opayo_3ds['transactionId'] ) ? $opayo_3ds['transactionId'] : $_REQUEST['transactionId'];

            if ( isset($_REQUEST['PaRes']) || isset($_REQUEST['cres']) ) {

                if ( isset($_REQUEST['PaRes']) ) {

                    $transactionId = isset( $opayo_3ds['MD'] ) ? $opayo_3ds['MD'] : $_REQUEST['MD'];

                    // set the URL that will be posted to.
                    $url = str_replace( '<transactionId>', $transactionId, $this->callbackURL );

                    $data = array(
                        "paRes" => $_REQUEST['PaRes']
                    );

                }

                if ( isset($_REQUEST['cres']) ) {

                    // set the URL that will be posted to.
                    $url = str_replace( '<transactionId>', $transactionId, $this->callbackURLTwo );

                    $data = array(
                        "cRes" => $_REQUEST['cres']
                    );

                }

                // Send $data to Opayo
                $result = $this->remote_post( $data, $url, NULL, 'Basic' );

                // Verify the result
                $result = $this->verify_opayo( $order_id, $result );

                if( isset( $result['status'] ) && in_array( $result['status'], array( 'Ok','Authenticated' ) ) ) {

                    $this->successful_payment( $result, $order, __('Payment completed', 'woocommerce-gateway-sagepay-form') );

                    wp_redirect( $this->get_return_url( $order ) );
                    exit;

                } else {
                    // Update order
                    $this->failed_payment( $result, $order, __('Payment failed', 'woocommerce-gateway-sagepay-form') );
                    // Note for customer
                    $this->opayo_message( ( __('Payment error. Please try again, your card has not been charged', 'woocommerce-gateway-sagepay-form') . ': ' . $result['statusDetail'] ) , 'error', $order_id );
                    // Redirect for a retry
                    wp_redirect( wc_get_checkout_url() );
                    exit;

                }

            }

            $transactionId  = $opayo_3ds["transactionId"];
            $acsUrl         = $opayo_3ds["acsUrl"];
            $paReq          = isset( $opayo_3ds["paReq"] ) ? $opayo_3ds["paReq"] : NULL;
            $cReq           = isset( $opayo_3ds["cReq"] ) ? $opayo_3ds["cReq"] : NULL;

            if( !is_null( $cReq ) ) {
                $p = array(
                    "field_name"  => "creq",
                    "field_value" => $cReq
                );

                $m = array(
                    "field_name"  => "threeDSSessionData",
                    "field_value" => $transactionId
                );
            } else {
                $p = array(
                    "field_name"  => "PaReq",
                    "field_value" => $paReq
                );

                $m = array(
                    "field_name"  => "MD",
                    "field_value" => $transactionId
                );
            }

            $form = '<form id="submitForm" method="post" action="' . $acsUrl . '">
                        <input type="hidden" name="' . $p['field_name'] . '" value="' . $p['field_value'] . '"/>
                        <input type="hidden" name="' . $m['field_name'] . '" value="' . $m['field_value'] . '"/>
                        <input type="hidden" id="termUrl" name="TermUrl" value="' . $order->get_checkout_payment_url( true ) . '"/>
                        <noscript><p>You are seeing this message because JavaScript is disabled in your browser. Please click to authenticate your card</p><p><input type="submit" value="Submit"></p></noscript>
                        <script>
                           document.getElementById("submitForm").submit();
                        </script>
                    </form>';

            $this->pi_debug( $form, $this->id, __('3D Secure Form ', 'woocommerce-gateway-sagepay-form'), FALSE );

            echo $form;
            
        }

        /**
         * check_opayo_response function.
         *
         * @access public
         * @return void
         */
        function process_result( $result, $order, $order_id ) {

            if( isset( $result['status'] ) ) {

                switch( strtoupper( $result['status'] ) ) {

                    case 'OK':
                        $this->successful_payment( $result, $order, __('Payment completed', 'woocommerce-gateway-sagepay-form') );

                        $result['result']   = 'success';
                        $result['redirect'] = $this->get_return_url( $order );
                        
                        return $result;

                    break;

                    case '3DAUTH':
                        /**
                         * This order requires 3D Secure authentication
                         */                 
                        // Set the session variables for 3D Secure
                        WC()->session->set( "opayo_3ds", $result );

                        // Fall back if session not available
                        update_post_meta( $order_id, '_opayo_3ds', $result );

                        /**
                         * go to the pay page for 3d securing
                         */
                        $result['result']   = 'success';
                        $result['redirect'] = $order->get_checkout_payment_url( true );
                        
                        return $result;

                    break;

                    case 'NOTAUTHED':
                    case "REJECTED":
                    case "MALFORMED": 
                    case "INALID":
                    case "ERROR":
                        // Update order
                        $this->failed_payment( $result, $order, __('Payment failed', 'woocommerce-gateway-sagepay-form') );

                        // Create message for customer
                        $this->opayo_message( ( __('Payment error. Please try again, your card has not been charged', 'woocommerce-gateway-sagepay-form') . ': ' . $result['statusDetail'] ) , 'error', $order_id );
                        
                        $result['result']   = 'success';
                        $result['redirect'] = wc_get_checkout_url();

                        return $result;
                    break;

                    default:
                        // Update order
                        $this->failed_payment( $result, $order, __('Payment failed', 'woocommerce-gateway-sagepay-form') );

                        // Create message for customer
                        $this->opayo_message( ( __('Payment error. Please try again, your card has not been charged', 'woocommerce-gateway-sagepay-form') . ': ' . $result['statusDetail'] ) , 'error', $order_id );
                        
                        $result['result']   = 'success';
                        $result['redirect'] = wc_get_checkout_url();

                        return $result;
                }

            } else {
                // Update order
                $this->failed_payment( $result, $order, __('Payment failed', 'woocommerce-gateway-sagepay-form') );

                // Create message for customer
                $this->opayo_message( ( __('Payment error. Please try again, your card has not been charged', 'woocommerce-gateway-sagepay-form') . ': ' . $result['statusDetail'] ) , 'error', $order_id );
                
                $result['result']   = 'success';
                $result['redirect'] = wc_get_checkout_url();

                return $result;
            }

        }

        /**
         * successful_payment function.
         *
         * @access public
         * @param mixed $sagepay_return_values
         * @return void
         */
        function successful_payment( $result, $order, $message = NULL ) {

            $ordernote  = array();
            $order_note = '';

            $order_id   = $order->get_id();

            if( !is_array( $result ) ) {
                $result = json_decode( $result, TRUE );
            }

            // Maybe store the token for future use
            $store_card = get_post_meta( $order_id, '_save_card', TRUE );
            if( isset( $store_card ) && $store_card === 'yes' ) {
                $ccIdentifier = get_post_meta( $order_id, '_opayo_cardIdentifier', TRUE );
                $this->store_card( $ccIdentifier, $result, $order_id );
            }

            // Clear session and order meta
            WC()->session->set( 'merchantSessionKeyArray', false );
            WC()->session->set( "opayo_3ds", false );
            delete_post_meta( $order_id, '_opayo_3ds' );
            delete_post_meta( $order_id, '_opayo_cardIdentifier' );
            delete_post_meta( $order_id, '_save_card' );

            // Add order note
            self::add_order_note( $result, $order, $message );

            if ( class_exists('WC_Pre_Orders') && WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) && WC_Pre_Orders_Order::order_will_be_charged_upon_release( $order_id ) ) {
                // mark order as pre-ordered / reduce order stock
                WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );
            } else {
                // Complete the order
                $order->payment_complete( isset( $result['transactionId'] ) ? $result['transactionId'] : '' );

                if( isset( $result['transactionType'] ) && in_array( $result['transactionType'], array('Deferred','Authenticate') ) ) {
                    $order->update_status( 'authorised', _x( 'Payment authorised, you will need to capture this payment before shipping. Use the "Capture Authorised Payment" option in the "Order Actions" dropdown.<br /><br />', 'woocommerce-gateway-sagepay-form' ) );       
                }
            }

            // Update Subscription with transactionID, maybe
            $this->update_subscription_meta_maybe( $result, $order );

        }

        /**
         * [failed_payment description]
         * @param  [type] $result  [description]
         * @param  [type] $order   [description]
         * @param  [type] $message [description]
         * @return [type]          [description]
         */
        function failed_payment( $result, $order, $message = NULL ) {

            $ordernote  = array();
            $order_note = '';

            $order_id   = $order->get_id();

            if( !is_array( $result ) ) {
                $result = json_decode( $result, TRUE );
            }

            // Add order note
            self::add_order_note( $result, $order, $message );

            // Update order status
            $order->update_status( 'failed' );

            // Clear session and order meta
            WC()->session->set( 'merchantSessionKeyArray', false );
            WC()->session->set( "opayo_3ds", false );
            delete_post_meta( $order_id, '_opayo_3ds' );
            delete_post_meta( $order_id, '_opayo_cardIdentifier' );
            delete_post_meta( $order_id, '_save_card' );

        }

        /**
         * [add_payment_method description]
         */
        public function add_payment_method() {

            include_once( 'opayo-pi-add-payment-method.php' );

            $add = new WC_Gateway_Opayo_Pi_Add_Payment_Method();

            return $add->add_payment_method(); 

        }

        /**
         * [update_subscription_meta_maybe description]
         * @param  [type] $result   [description]
         * @param  [type] $order_id [description]
         * @return [type]           [description]
         */
        function update_subscription_meta_maybe( $result, $order ) {

            $order_id = $order->get_id();

            // Update Subscription with result from Opayo if necessary
            if( class_exists( 'WC_Subscriptions' ) ) {

                // Get the subscriptions for this order
                $subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => array( 'parent' ) ) );

                if( count( $subscriptions ) >= 1 ) {

                    foreach( $subscriptions as $subscription ) {

                        $subscription_id = $subscription->get_id();

                        update_post_meta( $subscription_id, '_referenceTransactionId', $result['transactionId'] );

                    }
                }

            }

        }

        /**
         * Gets the transaction data.
         *
         * @param      <type>     $card_details  The card details
         * @param      <type>     $order         The order
         *
         * @throws     Exception  (description)
         *
         * @return     <type>     The transaction data.
         */
        function get_transaction_data( $card_details, $order ) {

            $order_id = $order->get_id();

            if( isset( $card_details['cardIdentifier'] ) && strlen( $card_details['cardIdentifier'] ) !== 0 ) {

                // Paying with Dropin checkout

                // Check for errors
                if( isset( $card_details['cardIdentifier']['errors'] ) ) {              

                    return array(
                            "error" => $card_details['cardIdentifier']['errors']
                                );

                }

                // Store the cardIdentifier for future verification
                update_post_meta( $order_id, '_opayo_cardIdentifier', $card_details['cardIdentifier'] );

                return array(
                            "card" => array(
                                        "merchantSessionKey"  => $card_details['merchantSessionKey'],
                                        "cardIdentifier"      => $card_details['cardIdentifier'],
                                        "save"                => $this->get_save_card( $card_details, $order )
                                    ),
                                );
            }

            // Paying with the WooCommerce checkout form.
            $ccIdentifier = NULL;

            // Check if we have a viable token
            if( isset( $card_details["token_id"] ) && is_numeric( $card_details["token_id"] ) ) {
                // Paying with existing token?
                $ccIdentifier = $this->get_opayopi_token( $card_details["token_id"] );
            }

            if( isset( $ccIdentifier ) && strlen( $ccIdentifier ) !== 0 ) {
                // Paying with existing token

                // Link a reusable card identifier with a security code
                $url = $this->card_identifiers_url . "/<cardIdentifier>/security-code";
                $url = str_replace( "<cardIdentifier>", $ccIdentifier, $url );

                $data = array(
                            "securityCode" => $card_details['securityCode'],
                            );

                $link  = $this->remote_post( $data, $url, $card_details['merchantSessionKey'], 'Bearer' );

                // Store the cardIdentifier for future verification
                update_post_meta( $order_id, '_opayo_cardIdentifier', $ccIdentifier );

                return array(
                            "card" => array(
                                        "merchantSessionKey"  => $card_details['merchantSessionKey'],
                                        "cardIdentifier"      => $ccIdentifier,
                                        "reusable"            => true
                                    ),
                                );

            }

            if( isset($card_details['cardNumber']) && strlen($card_details['cardNumber']) !== 0 && isset($card_details['expiryDate']) && strlen($card_details['expiryDate']) !== 0 && isset($card_details['securityCode']) && strlen($card_details['securityCode']) !== 0 ) {

                // Paying with a card
                $cardIdentifier = $this->get_cardIdentifier( $card_details );

                // Check for errors
                if( isset( $cardIdentifier['errors'] ) ) {

                    return array(
                            "error" => $cardIdentifier['errors'][0]['description']
                                );
                }

                if( isset( $cardIdentifier["cardIdentifier"] ) && strlen( $cardIdentifier["cardIdentifier"] ) !== 0 ) {

                    // Store the cardIdentifier for future verification
                    update_post_meta( $order_id, '_opayo_cardIdentifier', $cardIdentifier["cardIdentifier"] );

                    return array(
                                "card" => array(
                                            "merchantSessionKey"  => $card_details['merchantSessionKey'],
                                            "cardIdentifier"      => $cardIdentifier["cardIdentifier"],
                                            "save"                => $this->get_save_card( $card_details, $order )
                                        ),
                                    );

                } else {

                    return array(
                            "error" => $cardIdentifier['description']
                                );

                }

            }

        }

        /**
         * Gets the common transaction data.
         *
         * @param      <type>  $card_details  The card details
         * @param      <type>  $order         The order
         *
         * @return     array   The common transaction data.
         */
        function get_common_transaction_data( $card_details, $order ) {

            // Temporary order note
            // $order_total_for_note       = $order->get_total();
            // $transaction_value_for_note = $this->get_total( $order->get_total() );
            // $order->add_order_note( 'Order Total : ' . $order_total_for_note . '<br />Transaction Total : ' . $transaction_value_for_note );

            // Start the common 3D Secure functions class
            $common_threeds = new WC_Opayo_Common_Threeds_Functions();

            $description = __( 'Order', 'woocommerce-gateway-sagepay-form' ) . ' ' . str_replace( '#' , '' , $order->get_order_number() );
            $description = apply_filters( 'woocommerce_opayo_pi_order_description', $description, $order );

            $data = array(  
                "transactionType"   => $this->get_txtype( $order ),
                "vendorTxCode"      => WC_Sagepay_Common_Functions::build_vendortxcode( $order, $this->id, $this->vendortxcodeprefix ),
                "amount"            => $this->get_total( $order->get_total() ),
                "currency"          => WC_Sagepay_Common_Functions::get_order_currency( $order ),
                "description"       => $description,
                "customerFirstName" => $order->get_billing_first_name(),
                "customerLastName"  => $order->get_billing_last_name(),
                "billingAddress"    => $this->get_billing_address( $order ),
                "entryMethod"       => "Ecommerce",
                "apply3DSecure"     => "UseMSPSetting",
                "applyAvsCvcCheck"  => "UseMSPSetting",
                "customerEmail"     => $order->get_billing_email(),
                "customerPhone"     => $this->get_international_phone_format( $order ),
                "referrerId"        => $this->referrerid,
            );

            // Add shiping address if required
            if ( $order->needs_shipping_address() ) {
                $data["shippingDetails"] = $this->get_shipping_address( $order );
            }

            $data["strongCustomerAuthentication"] = array(
                "website"                   => home_url(),
                "notificationURL"           => $order->get_checkout_payment_url( true ),
                "browserIP"                 => $common_threeds::get_ipaddress( $this->nullipaddress ),
                "browserAcceptHeader"       => isset( $_SERVER['HTTP_ACCEPT'] ) ? $_SERVER['HTTP_ACCEPT'] : "text/html, application/json",
                "browserJavascriptEnabled"  => $card_details['browserJavascriptEnabled'],
                "browserJavaEnabled"        => $card_details['browserJavaEnabled'],
                "browserLanguage"           => $card_details['browserLanguage'],
                "browserColorDepth"         => $card_details['browserColorDepth'],
                "browserScreenHeight"       => $card_details['browserScreenHeight'],
                "browserScreenWidth"        => $card_details['browserScreenWidth'],
                "browserTZ"                 => $card_details['browserTZ'],
                "browserUserAgent"          => $card_details['browserUserAgent'],
                "challengeWindowSize"       => $card_details['challengeWindowSize'],
                "transType"                 => $card_details['transType'],
            );

            // Maybe add acctID
            $data["strongCustomerAuthentication"] = $common_threeds::get_acctID( $data["strongCustomerAuthentication"], $order );

            // Maybe add acctInfo
            $data = $common_threeds::get_acctInfo( $data, $order );

            // Maybe add merchantRiskIndicator
            $data = $common_threeds::get_merchantRiskIndicator( $data, $order );

            // Maybe add threeDSRequestorAuthenticationInfo
            $data = $common_threeds::get_threeDSRequestorAuthenticationInfo( $data, $order );

            // Maybe add credentialType for a new token
            if( isset( $_POST['wc-opayopi-new-payment-method'] ) ) {
                $data["credentialType"] = $this->get_credentialType_New( $order );
            }

            // Check order type, Normal, Subscription or PreOrder
            $order_tyoe = $this->get_order_type( $order );

            // Maybe add credentialType for a subscription order or pre-order
            if ( !isset( $data["credentialType"] ) && in_array( $order_tyoe, array( 'Subscription', 'PreOrder' ) ) ) {
                $data["credentialType"] = $this->get_credentialType_New( $order );
            }

            // Maybe add credentialType if using an existing token
            if( isset( $card_details["token_id"] ) && is_numeric( $card_details["token_id"] ) ) {
                $data["credentialType"] = $this->get_credentialType_ReUse( $order );
            }

            if( in_array( $order_tyoe, array( 'Subscription', 'PreOrder' ) ) ) {
                $data["InitiatedType"]   = 'CIT';
                $data["COFUsage"]        = 'FIRST';
                $data["MITType"]         = 'UNSCHEDULED';
            }

            return apply_filters( 'woocommerce_opayo_pi_data', $data, $order );

        }

        /**
         * [get_save_card description]
         * @param  [type] $save_card [description]
         * @param  [type] $order     [description]
         * @return [type]            [description]
         */
        function get_save_card( $card_details, $order ) {

            $order_id = $order->get_id();
            
            // Set $save based on the checkbox on the checkout form.
            $save = isset( $card_details['savecard'] ) ? $card_details['savecard'] : false;

            // Make sure we save the card if the cart contains a Pre-Order
            if ( class_exists('WC_Pre_Orders') && WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) && WC_Pre_Orders_Order::order_will_be_charged_upon_release( $order_id ) ) {
                $save = true;
            }

            // Make sure we save the card if the cart contains a Subscription
            if ( function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order ) ) ) {
                $save = true;
            }

            // Update the order meta
            if( $save ) {
                add_post_meta( $order_id, '_save_card', 'yes', TRUE );     
            }  

            return $save;
        }

        /**
         * [get_merchantSessionKey description]
         * @return [type] [description]
         */
        function get_merchantSessionKey() {

            $key        = $this->Integration_Key;
            $password   = $this->Integration_Password;
            $vendor     = $this->vendor;

            if( $this->unittests == 1 ) {
                $key = 'XXXXXXXXXX';
            }

            if( $this->unittests == 2 ) {
                $password = 'XXXXXXXXXX';
            }

            if( $this->unittests == 3 ) {
                $vendor = 'XXXXXXXXXX';
            }

            $Basic_authentication_key = base64_encode( $key . ':' . $password );
            $data = array( 
                        "vendorName" => $vendor 
                    );

            $merchantSessionKeyArray  = $this->remote_post( $data, $this->merchant_session_keys_url, NULL, 'Basic' );

            return $merchantSessionKeyArray;

        }

        /**
         * Gets the proctocol 4 script.
         *
         * @return     string  The proctocol 4 script.
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

                var sageform = document.getElementById( 'opayopi-cc-form' );

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
         * [get_cardIdentifier description]
         * @param  [type] $merchantSessionKey [description]
         * @return [type]                     [description]
         */
        function get_cardIdentifier( $card_details = NULL ) {

            if( $this->unittests == 4 ) {
                $card_details['merchantSessionKey'] = 'XXXXXXXXXX';
            }

            $merchantSessionKey = $card_details['merchantSessionKey'];
            $cardholderName     = $this->get_cardHolder_name( $card_details );
            $cardNumber         = $this->get_clean_card_number( $card_details['cardNumber'] );
            $expiryDate         = $this->get_clean_expiry_date( $card_details['expiryDate'] );
            $securityCode       = $card_details['securityCode'];

            $data = array( 
                        "cardDetails" => array( 
                                            "cardholderName"  => $cardholderName,
                                            "cardNumber"      => $cardNumber,
                                            "expiryDate"      => $expiryDate,
                                            "securityCode"    => $securityCode
                                        )
                    );

            $cardIdentifier  = $this->remote_post( $data, $this->card_identifiers_url, $merchantSessionKey, 'Bearer' );

            return $cardIdentifier;

        }

        /**
         * Gets the opayopi token.
         *
         * @param      <type>  $token_id  The token identifier
         *
         * @return     <type>  The opayopi token.
         */
        function get_opayopi_token( $token_id ) {

            $token = new WC_Payment_Token_CC();
            $token = WC_Payment_Tokens::get( $token_id  );

            if( $token ) {
                return $token->get_token();
            }

            return NULL;
        }

        /**
         * [get_txtype description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        function get_txtype( $order ) {

            $order_id = $order->get_id();

            // Paying for a "Pay Later" Pre Order
            if( isset( $_GET['pay_for_order'] ) && $_GET['pay_for_order'] == true && class_exists( 'WC_Pre_Orders' ) && WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) ) {
                return 'Payment';
            }
            
            if( class_exists( 'WC_Pre_Orders' ) && WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) && WC_Pre_Orders_Order::order_will_be_charged_upon_release( $order_id ) ) {
                return 'Deferred';
            } else {
                return $this->txtype;
            }

        }

        /**
         * Gets the transaction type.
         *
         * @param      <type>  $order  The order
         *
         * @return     string  The transaction type.
         */
        function get_transType( $order ) {
            return apply_filters( 'woocommerce_opayo_pi_transType', 'GoodsAndServicePurchase', $order );
        }

        /**
         * Gets the card holder name.
         *
         * @param      <type>  $card_details  The card details
         *
         * @return     <type>  The card holder name.
         */
        function get_cardHolder_name( $card_details ) {

            if( $this->status != 'live' && $this->pimagicvalue != 'NO' ) {
                return $this->pimagicvalue;
            }

            return $card_details["cardholderName"];

        }

        /**
         * [get_billing_address description]
         * @param  [type] $order     [description]
         * @return [type]            [description]
         */
        function get_billing_address( $order ) {

            $billingAddress = array(
                                "address1"      => $order->get_billing_address_1(),
                                "city"          => $order->get_billing_city(),
                                "postalCode"    => $order->get_billing_postcode(),
                                "country"       => $order->get_billing_country(),
                            );

            if( 'US' === $order->get_billing_country() ) {
                $billingAddress["state"] = $order->get_billing_state();
            }

            return $billingAddress;
        }

        /**
         * [get_shipping_address description]
         * @param  [type] $order     [description]
         * @return [type]            [description]
         */
        function get_shipping_address( $order ) {

            $shippingAddress = array(
                                "recipientFirstName"    => $order->get_shipping_first_name(),
                                "recipientLastName"     => $order->get_shipping_last_name(),
                                "shippingAddress1"      => $order->get_shipping_address_1(),
                                "shippingCity"          => $order->get_shipping_city(),
                                "shippingPostalCode"    => $order->get_shipping_postcode(),
                                "shippingCountry"       => $order->get_shipping_country(),
                            );

            if( 'US' === $order->get_shipping_country() ) {
                $shippingAddress["shippingState"] = $order->get_shipping_state();
            }

            return $shippingAddress;
        }

        /**
         * [get_international_phone_format description]
         * @param  [type] $order     [description]
         * @return [type]            [description]
         */
        function get_international_phone_format( $order ) {

            $phone_number = wc_sanitize_phone_number( $order->get_billing_phone() );

            $calling_code = WC()->countries->get_country_calling_code( $order->get_billing_country() );
            $calling_code = is_array( $calling_code ) ? $calling_code[0] : $calling_code;

            if ( $calling_code ) {
                $phone_number = $calling_code . preg_replace( '/^0/', '', $order->get_billing_phone() );
            }

            return $phone_number;

        }

        /**
         * [get_credentialType description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        function get_credentialType_New( $order ) {

            $credentialType = array(
                                "cofUsage"      => "First",
                                "initiatedType" => "CIT"
                            );

            return $credentialType;

        }

        /**
         * [get_credentialType_ReUse description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        function get_credentialType_ReUse( $order ) {

            $credentialType = array(
                                "cofUsage"      => "Subsequent",
                                "initiatedType" => "MIT",
                                "mitType"       => "Unscheduled"
                            );

            return $credentialType;

        }

        /**
         * Gets the order type.
         * Normal, Subscription or PreOrder
         *
         * @param      <type>  $order  The order
         */
        function get_order_type( $order ) {

            $order_id   = $order->get_id();

            $order_type = "Normal";

            if( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $order ) ) {
                return "Subscription";
            }

            if( class_exists('WC_Pre_Orders') && WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) && WC_Pre_Orders_Order::order_will_be_charged_upon_release( $order_id ) ) {
                return "PreOrder";
            }

            return $order_type;

        }

        /**
         * [store_card description]
         * @param  [type] $ccIdentifier [description]
         * @param  [type] $masked_card  [description]
         * @return [type]               [description]
         */
        function store_card( $ccIdentifier, $result, $order_id ) {

            // Set the token variables
            $ccIdentifier   = $result['paymentMethod']['card']['cardIdentifier'];
            $cardType       = $result['paymentMethod']['card']['cardType'];
            $lastFourDigits = $result['paymentMethod']['card']['lastFourDigits'];
            $expiryDate     = $result['paymentMethod']['card']['expiryDate'];

            // Format the expiry date
            $expiry_month   = substr( $expiryDate, 0, 2 );
            $expiry_year    = 2000 + substr( $expiryDate, -2 );

            // New token object
            $token = new WC_Payment_Token_CC();

            // Store the token
            $token->set_token( $ccIdentifier );
            $token->set_gateway_id( $this->id );
            $token->set_card_type( $cardType );
            $token->set_last4( $lastFourDigits );
            $token->set_expiry_month( $expiry_month );
            $token->set_expiry_year( $expiry_year );
            $token->set_user_id( get_current_user_id() );

            $token->save();

        }

        /**
         * [add_order_note description]
         * @param [type] $result  [description]
         * @param [type] $order   [description]
         * @param [type] $message [description]
         */
        function add_order_note( $result, $order = NULL, $message = NULL ) {

            // Check for $order object
            if( !is_object($order) ) {
                return;
            }

            // Order ID
            $order_id = $order->get_id();

            // Get order note
            $order_note = '';
            $ordernote = self::get_order_note( $result );

            if( !is_null( $message ) ) {
                $message = $message . '<br />';
            }
            // Make the order note
            if( !empty( $ordernote ) ) {
                foreach ( $ordernote as $key => $value ) {
                    $order_note .= $key . ' : ' . $value . "\r\n";
                }
            }

            $order->add_order_note( $message . $order_note );

            // Add transaction information to 
            update_post_meta( $order_id , '_sageresult', $result );

            // Security Checks
            if( isset( $result['avsCvcCheck']['status'] ) ) {
                update_post_meta( $order_id, '_AVSCV2' , strtoupper( $result['avsCvcCheck']['status'] ) );
            }

            if( isset( $result['avsCvcCheck']['address'] ) ) {
                update_post_meta( $order_id, '_AddressResult' , strtoupper( $result['avsCvcCheck']['address'] ) );
            }

            if( isset( $result['avsCvcCheck']['postalCode'] ) ) {
                update_post_meta( $order_id, '_PostCodeResult' , strtoupper( $result['avsCvcCheck']['postalCode'] ) );
            }

            if( isset( $result['avsCvcCheck']['securityCode'] ) ) {
                update_post_meta( $order_id, '_CV2Result' , strtoupper( $result['avsCvcCheck']['securityCode'] ) );
            }

            if( isset( $result['3DSecure']['status'] ) ) {
                update_post_meta( $order_id, '_3DSecureStatus' , strtoupper( $result['3DSecure']['status'] ) );
            }

        }

        /**
         * [get_order_note description]
         * @param  [type] $result [description]
         * @return [type]         [description]
         */
        function get_order_note( $result ) {

            $ordernote = array();

            if( isset( $result['status'] ) ){
                $ordernote['status'] = $result['status'];
            }

            if( isset( $result['statusCode'] ) ) {
                $ordernote['statusCode'] = $result['statusCode'];
            }

            if( isset( $result['statusDetail'] ) ) {
                $ordernote['statusDetail'] = $result['statusDetail'];
            }

            if( isset( $result['transactionId'] ) ) {
                $ordernote['transactionId'] = $result['transactionId'];
            }

            if( isset( $result['transactionType'] ) ) {
                $ordernote['transactionType'] = $result['transactionType'];
            }

            if( isset( $result['retrievalReference'] ) ) {
                $ordernote['retrievalReference'] = $result['retrievalReference'];
            }

            if( isset( $result['bankResponseCode'] ) ) {
                $ordernote['bankResponseCode'] = $result['bankResponseCode'];
            }

            if( isset( $result['bankAuthorisationCode'] ) ) {
                $ordernote['bankAuthorisationCode'] = $result['bankAuthorisationCode'];
            }

            if( isset( $result['paymentMethod']['card'] ) ) {
                $ordernote['cardType']          = isset($result['paymentMethod']['card']['cardType']) ? $result['paymentMethod']['card']['cardType'] : '';
                $ordernote['lastFourDigits']    = isset($result['paymentMethod']['card']['lastFourDigits']) ? $result['paymentMethod']['card']['lastFourDigits'] : '';
                $ordernote['expiryDate']        = isset($result['paymentMethod']['card']['expiryDate']) ? $result['paymentMethod']['card']['expiryDate'] : '';
            }

            if( isset( $result['amount'] ) ) {
                $ordernote['totalAmount']       = $result['amount']['totalAmount'];
                $ordernote['saleAmount']        = $result['amount']['saleAmount'];
                $ordernote['surchargeAmount']   = $result['amount']['surchargeAmount'];
            }

            if( isset( $result['currency'] ) ) {
                $ordernote['currency'] = $result['currency'];
            }

            if( isset( $result['avsCvcCheck'] ) ) {
                $ordernote['avsCvcCheckStatus']         = $result['avsCvcCheck']['status'];
                $ordernote['avsCvcCheckAddress']        = $result['avsCvcCheck']['address'];
                $ordernote['avsCvcCheckPostalCode']     = $result['avsCvcCheck']['postalCode'];
                $ordernote['avsCvcCheckSecurityCode']   = $result['avsCvcCheck']['securityCode'];
            }

            if( isset( $result['3DSecure'] ) ) {
                $ordernote['3DSecureStatus'] = $result['3DSecure']['status'];
            }

            // Add plugin version to order notes
            $ordernote["Version"] = OPAYOPLUGINVERSION;

            return $ordernote;

        }

        /**
         * [remote_post description]
         * @param  [type] $data          [description]
         * @param  [type] $url           [description]
         * @param  [type] $authorization [description]
         * @param  [type] $auth_method   [description]
         * @return [type]                [description]
         */
        function remote_post( $data, $url, $authorization = NULL, $auth_method = NULL ) {

            include_once( 'opayo-pi-remote-post-class.php' );

            $result = new Opayo_Pi_Remote_Post( $data, $url, $authorization, $auth_method );

            return $result->post();

        }

        /**
         * [remote_get description]
         * @param  [type] $url           [description]
         * @param  [type] $authorization [description]
         * @param  [type] $auth_method   [description]
         * @return [type]                [description]
         */
        function remote_get( $url, $authorization = NULL, $auth_method = NULL ) {
            global $wp_version;

            if ( is_null( $authorization ) ) {
                $authorization = base64_encode( $this->Integration_Key . ':' . $this->Integration_Password );
            }

            if( !is_null( $auth_method ) ) {
                $headers = array(
                    "Authorization" => $auth_method . " " . $authorization
                );
            }

            $args = array(
                        'timeout'     => 150,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
                        'blocking'    => true,
                        'headers'     => $headers,
                        'cookies'     => array(),
                        'body'        => null,
                        'compress'    => false,
                        'decompress'  => true,
                        'sslverify'   => true,
                        'stream'      => false,
                        'filename'    => null
                    );          

            $return = wp_remote_get( $url, $args );

            return wp_remote_retrieve_body( $return );

        }

        /**
         * [get_clean_card_number description]
         * @param  [type] $card_number [description]
         * @return [type]              [description]
         */
        function get_clean_card_number( $card_number ) {

            return str_replace( array( ' ', '-' ), '', $card_number );

        }

        /**
         * [get_clean_expiry_date description]
         * @param  [type] $expiry_date [description]
         * @return [type]              [description]
         */
        function get_clean_expiry_date ( $expiry_date ) {

            $expiry_date = preg_replace( '/[^0-9]/', '', $expiry_date);

            $year   = substr( $expiry_date, -2 );
            $month  = substr( $expiry_date, 0, -2 );

            // $expiry_date    = array_map( 'trim', explode( '/', $expiry_date ) );
            $month          = str_pad( $month, 2, "0", STR_PAD_LEFT );
            // $year           = $expiry_date[1];

            return $month . $year;

        }

        /**
         * Returns the plugin's url without a trailing slash
         */
        public function get_plugin_url() {

            return str_replace( '/classes/pi', '/', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
            
        }

        /**
         * [pi_payment_method_description description]
         * @return [type] [description]
         */
        function pi_payment_method_description() {

            $description = __( 'Take payments securly with Opayo Pi', 'woocommerce-gateway-sagepay-form' );
            return $description;

        }

        /**
         * Enqueues our SagePay Pi checkout script.
         * @since 5.0.0
         */
        public function checkout_script() {
            if( is_checkout() ) {
                wp_enqueue_script( 'opayo-pi-checkout', $this->checkout_script_url, array( 'jquery' ), SAGEVERSION );
            }
            
        }

        /**
         * Enqueues our tokenization script to handle some of the new form options.
         * @since 2.6.0
         */
        public function tokenization_script() {
            wp_enqueue_script(
                'opayo-tokenization-form',
                SAGEPLUGINURL.'assets/js/tokenization-form.js',
                array( 'jquery' ),
                OPAYOPLUGINVERSION
            );

            wp_localize_script(
                'opayo-tokenization-form-cvv', 'wc_tokenization_form_params', array(
                    'is_registration_required' => WC()->checkout()->is_registration_required(),
                    'is_logged_in'             => is_user_logged_in(),
                )
            );
        }

        /**
         * Enqueues our tokenization script to handle some of the new form options, leaves CVV field in place.
         * @since 3.13.0
         */
        public function cvv_script() {
            wp_enqueue_script(
                'opayo-tokenization-form-cvv',
                SAGEPLUGINURL.'assets/js/tokenization-form-cvv.js',
                array( 'jquery' ),
                OPAYOPLUGINVERSION
            );

            wp_localize_script(
                'opayo-tokenization-form-cvv', 'wc_tokenization_form_params', array(
                    'is_registration_required' => WC()->checkout()->is_registration_required(),
                    'is_logged_in'             => is_user_logged_in(),
                )
            );
        } 

        /**
         * Opayo Pi Refund Processing
         * @param  [type]        $order_id [description]
         * @param  [type]        $amount   [description]
         * @param  [type]        $reason   [description]
         * @return [type]                  [description]
         */
        function process_refund( $order_id, $amount = NULL, $reason = '' ) {

            $order          = new WC_Order( $order_id );

            $payment_method = $order->get_payment_method();

            if( isset( $payment_method ) && $payment_method == 'opayopi' ) {
            
                include_once( 'opayo-pi-refunds-class.php' );
                $refund = new WC_Gateway_Opayo_Pi_Refunds( $order_id, $amount, $reason );

                return $refund->refund();

            }

        } // process_refund

        /**
         * Opayo Pi Instruction Processing
         * @param  Varien_Object $payment [description]
         * @param  [type]        $amount  [description]
         * @return [type]                 [description]
         */
        function process_instruction( $order, $instruction = 'release' ) {

            $payment_method = $order->get_payment_method();

            if( isset( $payment_method ) && $payment_method === 'opayopi' ) {

                include_once( 'opayo-pi-instructions-class.php' );
                $instruction = new WC_Gateway_Opayo_Pi_Instructions( $order, $instruction );

                return $instruction->instruction();                   

            }

        } // process_instruction

        /**
         * opayo_message
         * 
         * return checkout messages / errors
         * 
         * @param  [type] $message [description]
         * @param  [type] $type    [description]
         * @return [type]          [description]
         */
        function opayo_message( $message, $type, $order_id = NULL ) {

            global $woocommerce;
            if( is_callable( 'wc_add_notice') ) {
                if( $order_id ) {
                    update_post_meta( $order_id, '_opayo_errors', array( 'message'=>$message, 'type'=>$type ) );
                } else {
                    wc_add_notice( $message, $type );
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

                $errors = get_post_meta( $order_id, '_opayo_errors', TRUE );

                if( ! empty( $errors ) ) {
                    wc_print_notice( $errors['message'], $errors['type'] );
                }

                // Make sure to delete the error message immediatley after showing it.
                // 
                // DON'T delete the message if the customer created an account during checkout
                // WooCommerce reloads the checkout after creating the account so the message will disappear :/ 
                $reload_checkout = WC()->session->get( 'reload_checkout' ) ? WC()->session->get( 'reload_checkout' ) : NULL;

                if( is_null($reload_checkout) ) {
                    delete_post_meta( $order_id, '_opayo_errors' );
                }

            }
        }

        /**
         * [process_scheduled_subscription_payment description]
         * @param  [type] $amount_to_charge [description]
         * @param  [type] $order            [description]
         * @return [type]                   [description]
         */
        function process_scheduled_subscription_payment( $amount_to_charge, $order ) {

            include_once( 'opayo-pi-subscriptions-class.php' );
            $response = new Opayo_Pi_Subcription_Renewals( $amount_to_charge, $order );

            $response->process_scheduled_payment();

        }

        /**
         * [get_total description]
         * @param  [type] $total [description]
         * @return [type]        [description]
         */
        function get_total( $total ) {
            $total = $total * 100;
            $total = explode( '.', $total );
            return intval( $total[0] );
        }

        /**
         * [verify_opayo description]
         * @param  [type] $order_id [description]
         * @param  [type] $result   [description]
         * @return [type]           [description]
         */
        function verify_opayo( $order_id, $result ) {

            $stored_cardIdentifier      = get_post_meta( $order_id, '_opayo_cardIdentifier', TRUE );
            $returned_cardIdentifier    = $result['paymentMethod']['card']['cardIdentifier'];

            if( isset($stored_cardIdentifier) && strlen($stored_cardIdentifier) != 0 && isset( $returned_cardIdentifier ) && $stored_cardIdentifier == $returned_cardIdentifier ) {
                // stored_cardIdentifier matches returned_cardIdentifier, do nothing
            } else {
                // The stored cardIdentifier and returned cardIdentifier do not match, set the $result['status'] to INVALID
                $result['status'] = "INVALID";
            }

            return $result;
        }

        /**
         * [pi_debug description]
         * @param  Array   $tolog   contents for log
         * @param  String  $id      payment gateway ID
         * @param  String  $message additional message for log
         * @param  boolean $start   is this the first log entry for this transaction
         */
        public static function pi_debug( $tolog, $id, $message = NULL, $start = FALSE ) {

            if( !isset( $logger ) ) {
                $logger      = new stdClass();
                $logger->log = new WC_Logger();
            }

            /**
             * If this is the start of the logging for this transaction add the header
             */
            if( $start ) {

                $logger->log->add( $id, __('', 'woocommerce-gateway-sagepay-form') );
                $logger->log->add( $id, __('=============================================', 'woocommerce-gateway-sagepay-form') );
                $logger->log->add( $id, __('', 'woocommerce-gateway-sagepay-form') );
                $logger->log->add( $id, __('Opayo Log', 'woocommerce-gateway-sagepay-form') );
                $logger->log->add( $id, __('' .date('d M Y, H:i:s'), 'woocommerce-gateway-sagepay-form') );
                $logger->log->add( $id, __('', 'woocommerce-gateway-sagepay-form') );

            }

            /**
             * Make sure we mask the card number
             */
            if( isset( $tolog["cardDetails"]["cardNumber"] ) && $tolog["cardDetails"]["cardNumber"] != '' ) {
                $tolog["cardDetails"]["cardNumber"] = substr( $tolog["cardDetails"]["cardNumber"], 0, 4 ) . str_repeat( "*", strlen($tolog["cardDetails"]["cardNumber"]) - 8 ) . substr( $tolog["cardDetails"]["cardNumber"], -4 );
            }

            /**
             * Unset the CV2 number
             */
            if( isset( $tolog['cardDetails']["securityCode"] ) ) {
                $tolog['cardDetails']["securityCode"] = "***";
            }

            $logger->log->add( $id, __('=============================================', 'woocommerce-gateway-sagepay-form') );
            $logger->log->add( $id, $message );
            $logger->log->add( $id, print_r( $tolog, TRUE ) );
            $logger->log->add( $id, __('=============================================', 'woocommerce-gateway-sagepay-form') );

        }

    } // END CLASS
