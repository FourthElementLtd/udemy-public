<?php

    /**
     * WC_Gateway_Sagepay_Form class.
     *
     * @extends WC_Payment_Gateway
     */
    class WC_Gateway_Sagepay_Form extends WC_Payment_Gateway {
        /**
         * [$sage_cardtypes description]
         * Set up accepted card types for card type drop down
         * From Version 3.3.0
         * @var array
         */
        var $sage_cardtypes = array(
                    'MasterCard'        => 'MasterCard',
                    'MasterCard Debit'  => 'MasterCard Debit',
                    'Visa'              => 'Visa',
                    'Visa Debit'        => 'Visa Debit',
                    'Discover'          => 'Discover',
                    'American Express'  => 'American Express',
                    'Maestro'           => 'Maestro',
                    'JCB'               => 'JCB',
                    'Laser'             => 'Laser',
                    'PayPal'            => 'PayPal'
                );
        /**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {

            $this->id                   = 'sagepayform';
            $this->method_title         = __( 'Opayo Form', 'woocommerce-gateway-sagepay-form' );
            $this->method_description   = $this->sagepay_system_status();
            $this->icon                 = apply_filters( 'wc_sagepayform_icon', '' );
            $this->has_fields           = false;
            $this->liveurl              = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
            $this->testurl              = 'https://test.sagepay.com/gateway/service/vspform-register.vsp';

            $this->successurl           = WC()->api_request_url( get_class( $this ) );

            // Default values
            $this->default_enabled              = 'no';
            $this->default_title                = __( 'Credit Card via Opayo', 'woocommerce-gateway-sagepay-form' );
            $this->default_description          = __( 'Pay via Credit / Debit Card with Opayo secure card processing.', 'woocommerce-gateway-sagepay-form' );
            $this->default_order_button_text    = __( 'Pay securely with Opayo', 'woocommerce-gateway-sagepay-form' );
            $this->default_status               = 'testing';
            $this->default_cardtypes            = '';
            $this->default_protocol             = '4.00';
            $this->default_vendor               = '';
            $this->default_vendorpwd            = '';
            $this->default_testvendorpwd        = '';
            $this->default_simvendorpwd         = '';
            $this->default_email                = get_bloginfo('admin_email');
            $this->default_sendemail            = '1';
            $this->default_txtype               = 'PAYMENT';
            $this->default_allow_gift_aid       = 'yes';
            $this->default_apply_avs_cv2        = '0';
            $this->default_apply_3dsecure       = '0';
            $this->default_debug                = false;
            $this->default_sagelink             = 0;
            $this->default_sagelogo             = 0;
            $this->default_vendortxcodeprefix   = 'wc_';

            $this->default_enablesurcharges     = 'no';
            $this->default_VISAsurcharges       = '';
            $this->default_DELTAsurcharges      = '';
            $this->default_UKEsurcharges        = '';
            $this->default_MCsurcharges         = '';
            $this->default_MCDEBITsurcharges    = '';
            $this->default_MAESTROsurcharges    = '';
            $this->default_AMEXsurcharges       = '';
            $this->default_DCsurcharges         = '';
            $this->default_JCBsurcharges        = '';
            $this->default_LASERsurcharges      = '';

            // ReferrerID
            $this->referrerid           = 'F4D0E135-F056-449E-99E0-EC59917923E1';

            // Maximum field lengths
            $this->VendorTxCode_length          = 40;
            $this->Currency_length              = 3;
            $this->Description_length           = 100;
            $this->SuccessURL_length            = 2000;
            $this->FailureURL_length            = 2000;
            $this->CustomerName_length          = 100;
            $this->CustomerEMail_length         = 100;
            $this->VendorEMail_length           = 255;
            $this->EmailMessage_length          = 7500;
            $this->BillingSurname_length        = 20;
            $this->BillingFirstnames_length     = 20;
            $this->BillingAddress1_length       = 100;
            $this->BillingAddress2_length       = 100;
            $this->BillingCity_length           = 40;
            $this->BillingPostCode_length       = 10;
            $this->BillingCountry_length        = 2;
            $this->BillingState_length          = 2;
            $this->BillingPhone_length          = 20;
            $this->DeliverySurname_length       = 20;
            $this->DeliveryFirstnames_length    = 20;
            $this->DeliveryAddress1_length      = 100;
            $this->DeliveryAddress2_length      = 100;
            $this->DeliveryCity_length          = 40;
            $this->DeliveryPostCode_length      = 10;
            $this->DeliveryCountry_length       = 2;
            $this->DeliveryState_length         = 2;
            $this->DeliveryPhone_length         = 20;
            $this->Basket_length                = 7500;
            $this->BasketXML_length             = 20000;
            $this->CustomerXML_length           = 2000;
            $this->SurchargeXML_length          = 800;
            $this->VendorData_length            = 200;
            $this->ReferrerID_length            = 40;

            // Load the form fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            // Get setting values
            $this->enabled              = isset( $this->settings['enabled'] ) && $this->settings['enabled'] == 'yes' ? 'yes' : $this->default_enabled;
            
            // Disable if openssl_encrypt or mcrypt_encrypt are not installed
            if( function_exists('openssl_encrypt') || function_exists('mcrypt_encrypt') ) {
                $this->enabled = $this->enabled;
            } else {
                $this->enabled = $this->default_enabled;
            }
            
            $this->title                = isset( $this->settings['title'] ) ? $this->settings['title'] : $this->default_title;
            $this->description          = isset( $this->settings['description'] ) ? $this->settings['description'] : $this->default_description;
            $this->order_button_text    = isset( $this->settings['order_button_text'] ) ? $this->settings['order_button_text'] : $this->default_order_button_text;
            $this->status               = isset( $this->settings['status'] ) ? $this->settings['status'] : $this->default_status;
            $this->cardtypes            = isset( $this->settings['cardtypes'] ) ? $this->settings['cardtypes'] : $this->default_cardtypes;
            $this->protocol             = $this->default_protocol;
            $this->vendor               = isset( $this->settings['vendor'] ) ? $this->settings['vendor'] : $this->default_vendor;
            $this->vendorpwd            = isset( $this->settings['vendorpwd'] ) ? $this->settings['vendorpwd'] : $this->default_vendorpwd;
            $this->testvendorpwd        = isset( $this->settings['testvendorpwd'] ) ? $this->settings['testvendorpwd'] : $this->default_testvendorpwd;
            $this->email                = isset( $this->settings['email'] ) ? $this->settings['email'] : $this->default_email;
            $this->sendemail            = isset( $this->settings['sendemail'] ) ? $this->settings['sendemail'] : $this->default_sendemail;
            $this->txtype               = isset( $this->settings['txtype'] ) ? $this->settings['txtype'] : $this->default_txtype;
            $this->allow_gift_aid       = isset( $this->settings['allow_gift_aid'] ) && $this->settings['allow_gift_aid'] == 'yes' ? 1 : 0;
            $this->apply_avs_cv2        = isset( $this->settings['apply_avs_cv2'] ) ? $this->settings['apply_avs_cv2'] : $this->default_apply_avs_cv2;
            $this->apply_3dsecure       = isset( $this->settings['apply_3dsecure'] ) ? $this->settings['apply_3dsecure'] : $this->default_apply_3dsecure;
            $this->debug                = isset( $this->settings['debugmode'] ) && $this->settings['debugmode'] == 'yes' ? true : $this->default_debug;
            $this->sagelink             = isset( $this->settings['sagelink'] ) && $this->settings['sagelink'] == 'yes' ? '1' : $this->default_sagelink;
            $this->sagelogo             = isset( $this->settings['sagelogo'] ) && $this->settings['sagelogo'] == 'yes' ? '1' : $this->default_sagelogo;
            $this->vendortxcodeprefix   = isset( $this->settings['vendortxcodeprefix'] ) ? $this->settings['vendortxcodeprefix'] : $this->default_vendortxcodeprefix;

            $this->enablesurcharges     = isset( $this->settings['enablesurcharges'] ) && $this->settings['enablesurcharges'] == 'yes' ? 'yes' : $this->default_enablesurcharges;
            $this->VISAsurcharges       = isset( $this->settings['visasurcharges'] ) ? $this->settings['visasurcharges'] : $this->default_VISAsurcharges;
            $this->DELTAsurcharges      = isset( $this->settings['visadebitsurcharges'] ) ? $this->settings['visadebitsurcharges'] : $this->default_DELTAsurcharges;
            $this->UKEsurcharges        = isset( $this->settings['visaelectronsurcharges'] ) ? $this->settings['visaelectronsurcharges'] : $this->default_UKEsurcharges;
            $this->MCsurcharges         = isset( $this->settings['mcsurcharges'] ) ? $this->settings['mcsurcharges'] : $this->default_MCsurcharges;
            $this->MCDEBITsurcharges    = isset( $this->settings['mcdebitsurcharges'] ) ? $this->settings['mcdebitsurcharges'] : $this->default_MCDEBITsurcharges;
            $this->MAESTROsurcharges    = isset( $this->settings['maestrosurcharges'] ) ? $this->settings['maestrosurcharges'] : $this->default_MAESTROsurcharges;
            $this->AMEXsurcharges       = isset( $this->settings['amexsurcharges'] ) ? $this->settings['amexsurcharges'] : $this->default_AMEXsurcharges;
            $this->DCsurcharges         = isset( $this->settings['dinerssurcharges'] ) ? $this->settings['dinerssurcharges'] : $this->default_DCsurcharges;
            $this->JCBsurcharges        = isset( $this->settings['jcbsurcharges'] ) ? $this->settings['jcbsurcharges'] : $this->default_JCBsurcharges;
            $this->LASERsurcharges      = isset( $this->settings['lasersurcharges'] ) ? $this->settings['lasersurcharges'] : $this->default_LASERsurcharges;

            // Check $this->apply_3dsecure for 3D Secure 2.0
            if( $this->apply_3dsecure == '2' || $this->apply_3dsecure == '3' ) {
                $this->apply_3dsecure = '0';
            }

            $this->link                 = 'http://www.sagepay.co.uk/support/online-shoppers/about-sage-pay';

            $this->basketoption         = isset( $this->settings['basketoption'] ) ? $this->settings['basketoption'] : "1";

            // Make sure $this->vendortxcodeprefix is clean
            $this->vendortxcodeprefix = str_replace( '-', '_', $this->vendortxcodeprefix );
            
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // Check this is enabled 
            if( $this->enabled == 'yes' ) {
                /**
                 *  API
                 *  woocommerce_api_{lower case class name}
                 */
                add_action( 'woocommerce_api_wc_gateway_sagepay_form', array( $this, 'check_sagepay_response' ) );

                add_action( 'valid_sagepayform_request', array( $this, 'successful_request' ) );
                add_action( 'woocommerce_receipt_sagepayform', array( $this, 'receipt_page' ) );

                // Void order action
                add_action ( 'woocommerce_order_action_opayo_form_void_order', array( $this, 'opayo_form_void_order' ) );

            }

            // Supports
            $this->supports = array(
                                    'products'
                            );

            $woocommerce_opayo_reporting_options   = get_option( 'woocommerce_opayo_reporting_options' );
            if( isset( $woocommerce_opayo_reporting_options['live_opayo_reporting_username'] ) || isset( $woocommerce_opayo_reporting_options['test_opayo_reporting_username'] ) ){
               $this->supports[] = 'refunds';
            }

            // Logs
            if ( $this->debug ) {
                $this->log = new WC_Logger();
            }

            // WC version
            $this->wc_version = get_option( 'woocommerce_version' );

            // Add test card info to the description if in test mode
            if ( $this->status != 'live' ) {
                $this->description .= ' ' . sprintf( __( '<br /><br />TEST MODE ENABLED.<br />In test mode, you can use Visa card number 4929000000006 with any CVC and a valid expiration date or check the documentation (<a href="%s">Test card details for your test transactions</a>) for more card numbers.<br /><br />3D Secure password is "password"', 'woocommerce-gateway-sagepay-form' ), 'http://www.sagepay.co.uk/support/12/36/test-card-details-for-your-test-transactions' );
                $this->description  = trim( $this->description );
            }

            // Set array for FraudStatus checks
            $fraud_status_array = apply_filters( 'opayo_form_fraud_status_array', array( 'DENY', 'CHALLENGE' ) );

            if ( $this->status == 'live' ) {
                // LIVE
                $this->refundURL      = 'https://live.sagepay.com/gateway/service/refund.vsp';
                $this->voidURL        = 'https://live.sagepay.com/gateway/service/void.vsp';
            } else {
                // TEST
                $this->refundURL      = 'https://test.sagepay.com/gateway/service/refund.vsp';
                $this->voidURL        = 'https://test.sagepay.com/gateway/service/void.vsp';
            }

        } // END __construct

        /**
         * init_form_fields function.
         *
         * @access public
         * @return void
         */
        function init_form_fields() {

            include ( SAGEPLUGINPATH . 'assets/php/sagepay-form-admin.php' );

            $display_surcharges = apply_filters( 'woocommerce_sagepayform_display_surcharges', false );

            if( $display_surcharges ) {
                $settings_form_fields = $settings_form_fields + $surcharge_form_fields;
            }

            $this->form_fields = apply_filters( 'woocommerce_opayo_form_developer_settings', $settings_form_fields );

        } // END init_form_fields

        /**
         * Returns the plugin's url without a trailing slash
         */
        public function get_plugin_url() {

            return str_replace( '/classes', '/', untrailingslashit( plugins_url( '/', __FILE__ ) ) );

        }

        /**
         * [get_icon description] Add selected card icons to payment method label, defaults to Visa/MC/Amex/Discover
         * @return [type] [description]
         */
        public function get_icon() {
            return WC_Sagepay_Common_Functions::get_icon( $this->cardtypes, $this->sagelink, $this->sagelogo, $this->id );
        }

        /**
         * Generate the form button
         */
        public function generate_sagepay_form( $order_id ) {
            global $woocommerce;

            if ( $this->status == 'testing' ) {
                $sagepayform_adr = $this->testurl;
            } else {
                $sagepayform_adr = $this->liveurl;
            }

            // Post Test Only
            if( $this->status == 'showpost' ) {
                $sagepayform_adr = 'https://test.sagepay.com/showpost/showpost.asp';
            }

            if ( $this->status == 'testing' && $this->testvendorpwd ) {
                $vendorpwd = $this->testvendorpwd;
            } else {
                $vendorpwd = $this->vendorpwd;
            }

            $order      = new WC_Order( $order_id );
            $order_key  = $order->get_order_key();

            wc_enqueue_js('
                jQuery("body").block({
                    message: "<img src=\"' . esc_url( apply_filters( 'woocommerce_ajax_loader_url', $woocommerce->plugin_url() . '/assets/images/select2-spinner.gif' ) ) . '\" alt=\"Redirecting&hellip;\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to Opayo to make payment.', 'woocommerce-gateway-sagepay-form').'",
                    overlayCSS:
                    {
                        background: "#fff",
                        opacity: 0.6
                    },
                    css: {
                        padding:        20,
                        textAlign:      "center",
                        color:          "#555",
                        border:         "3px solid #aaa",
                        backgroundColor:"#fff",
                        cursor:         "wait",
                        lineHeight:     "32px"
                    }
                });
                jQuery("#submit_sagepayform_payment_form").click();
            ');

            $sagepayform  = '<input type="hidden" name="VPSProtocol" value="' . $this->protocol . '" />';

            $sagepayform .= '<input type="hidden" name="TxType" value="' . $this->txtype . '" />';
            $sagepayform .= '<input type="hidden" name="Vendor" value="' . $this->vendor . '" />';

            // Build VendorTXCode
            $vendortxcode = WC_Sagepay_Common_Functions::build_vendortxcode( $order, $this->id, $this->vendortxcodeprefix );

            // Set $cardholder for testing 3D Secure 2.0
            $CustomerName = WC_Sagepay_Common_Functions::clean_sagepay_args( $order->get_billing_first_name() . ' ' .  $order->get_billing_last_name() );

            // Setup the billing and shipping states ready for checking
            $billing_state          = $order->get_billing_state();
            $billing_country        = $order->get_billing_country();
            $shipping_state         = WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_state' );
            $shipping_country       = apply_filters( 'woocommerce_sagepay_form_deliverycountry', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_country' ), $order );

            $successurl             = add_query_arg( array(
                                                        'opayo_key' => $order_key,
                                                        'opayo_id'  => $order_id
                                                    ), $this->successurl );

            // Bring it all together into one string
            $data = array(
                'VendorTxCode'      => substr( $vendortxcode, 0, $this->VendorTxCode_length ),
                'Amount'            => $order->get_total(),
                'Currency'          => $order->get_currency(),
                'Description'       => __( 'Order', 'woocommerce-gateway-sagepay-form' ) . ' ' . str_replace( '#' , '' , $order->get_order_number() ),
                'SuccessURL'        => apply_filters( 'woocommerce_sagepayform_successurl', $successurl, $order_id ),
                'FailureURL'        => apply_filters( 'woocommerce_sagepayform_cancelurl', $successurl, $order_id, $order_key ),
                'CustomerName'      => $CustomerName,
                'CustomerEMail'     => $order->get_billing_email(),
                'VendorEMail'       => $this->email,
                'SendEMail'         => $this->sendemail,

                // Billing Address info
                'BillingFirstnames' => substr( $order->get_billing_first_name(), 0, $this->BillingFirstnames_length ),
                'BillingSurname'    => substr( $order->get_billing_last_name(), 0, $this->BillingSurname_length ),
                'BillingAddress1'   => substr( $order->get_billing_address_1(), 0, $this->BillingAddress1_length ),
                'BillingAddress2'   => substr( $order->get_billing_address_2(), 0, $this->BillingAddress2_length ),
                'BillingCity'       => $this->city( substr( $order->get_billing_city(), 0, $this->BillingCity_length ) ),
                'BillingState'      => substr( WC_Sagepay_Common_Functions::sagepay_state( $billing_country, $billing_state ), 0, $this->BillingState_length ),
                'BillingPostCode'   => substr( $order->get_billing_postcode(), 0, $this->BillingPostCode_length ),
                'BillingCountry'    => substr( $billing_country, 0, $this->BillingCountry_length ),
                'BillingPhone'      => substr( $this->get_international_phone_format( $order ), 0, $this->BillingPhone_length ),
                'CustomerEMail'     => substr( $order->get_billing_email(), 0, $this->CustomerEMail_length ),

                // Shipping Address info
                'DeliveryFirstnames'=> substr( apply_filters( 'woocommerce_sagepay_form_deliveryfirstname', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_first_name', $order ) ), 0, $this->DeliveryFirstnames_length ),
                'DeliverySurname'   => substr( apply_filters( 'woocommerce_sagepay_form_deliverysurname', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_last_name', $order ) ), 0, $this->DeliverySurname_length ),
                'DeliveryAddress1'  => substr( apply_filters( 'woocommerce_sagepay_form_deliveryaddress1', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_address_1', $order ) ), 0, $this->DeliveryAddress1_length ),
                'DeliveryAddress2'  => substr( apply_filters( 'woocommerce_sagepay_form_deliveryaddress2', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_address_2', $order ) ), 0, $this->DeliveryAddress2_length ),
                'DeliveryCity'      => substr( apply_filters( 'woocommerce_sagepay_form_deliverycity', $this->city( WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_city', $order ) ) ), 0, $this->DeliveryCity_length ),
                'DeliveryState'     => substr( apply_filters( 'woocommerce_sagepay_form_deliverystate', WC_Sagepay_Common_Functions::sagepay_state( $shipping_country, $shipping_state ), $order ), 0, $this->DeliveryState_length ),
                'DeliveryPostCode'  => substr( apply_filters( 'woocommerce_sagepay_form_deliverypostcode', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_postcode' ), $order ), 0, $this->DeliveryPostCode_length ),
                'DeliveryCountry'   => substr( $shipping_country, 0, $this->DeliveryCountry_length ),
                'DeliveryPhone'     => substr( apply_filters( 'woocommerce_sagepay_form_deliveryphone', $this->get_international_phone_format( $order ), $order ), 0, $this->DeliveryPhone_length ),

                // Settings
                'AllowGiftAid'      => $this->allow_gift_aid,
                'ApplyAVSCV2'       => $this->apply_avs_cv2,
                'Apply3DSecure'     => $this->apply_3dsecure,
                
                'ReferrerID'        => $this->referrerid,
                'BillingAgreement'  => 0,

                'TransType'         => apply_filters( 'opayo_form_custom_field_transtype', '01', $order ),
                'VendorData'        => apply_filters( 'opayo_form_custom_field_vendordata', '', $order ),
            );

            $basket = WC_Sagepay_Common_Functions::get_basket( $this->basketoption, $order_id );

            if ( $basket != NULL ) {

                if ( $this->basketoption == 1 ) {
                    $data["Basket"] = $basket;
                } elseif ( $this->basketoption == 2 ) {
                    $data["BasketXML"] = $basket;
                }

            }

            /**
             * Setup the surcharges if necessary
             * Use woocommerce_sagepayform_apply_surcharges if you want to make surcharges conditional, for example, only apply surcharges to US customers
             */
            $apply_surcharges   = apply_filters( 'woocommerce_sagepayform_apply_surcharges', true, $order, $data );
            $surchargexml       = NULL; 
            if ( $this->enablesurcharges === 'yes' && $apply_surcharges ) {
                $cardtypes = array(
                                    'VISAsurcharges',
                                    'DELTAsurcharges',
                                    'UKEsurcharges',
                                    'MCsurcharges',
                                    'MCDEBITsurcharges',
                                    'MAESTROsurcharges',
                                    'AMEXsurcharges',
                                    'DCsurcharges',
                                    'JCBsurcharges',
                                    'LASERsurcharges'
                                    );

                $surchargexml = '<surcharges>' . "\r\n";
                
                // Set up arrays for str_replace
                $surchargeType = array('F','P');
                $surchargeTypeReplacement = array('fixed','percentage');
                
                foreach ( $cardtypes as $cardtype ) :
                
                    if ( $this->$cardtype != '' ) {
                        
                        $surchargevalue = explode( '|',$this->$cardtype );
                        
                        $surchargexml .= '<surcharge>' . "\r\n";
                        $surchargexml .= '<paymentType>' . str_replace( 'surcharges','',$cardtype ) . '</paymentType>' . "\r\n";
                        $surchargexml .= '<' . str_replace($surchargeType,$surchargeTypeReplacement,$surchargevalue[0]). '>' . 
                                                $surchargevalue[1] . 
                                         '</' .str_replace($surchargeType,$surchargeTypeReplacement,$surchargevalue[0]). '>' . "\r\n";
                        $surchargexml .= '</surcharge>' . "\r\n";

                    }
                
                endforeach;
                
                $surchargexml .= '</surcharges>' . "\r\n";

                // Filter the surchargeXML before it is added to the transaction
                $surchargexml = apply_filters( 'woocommerce_sagepayform_modify_surcharges', $surchargexml, $order, $data, $cardtypes );
                
            }

            // SurchargeXML
            if( isset( $surchargexml ) ) { 
                 $data["surchargeXML"] = $surchargexml;
            }

            // Get CustomerXML object
            $CustomerXML = apply_filters( 'woocommerce_opayo_direct_get_CustomerXML_xml', $this->getCustomerXML( $order ), $order );
            if( !empty( $CustomerXML ) && $this->get_customer() === 'yes' ) {
                $data['CustomerXML'] = $CustomerXML;
            }

            // Get AcctInfo object
            $AcctInfoXML = apply_filters( 'woocommerce_opayo_direct_get_AcctInfo_xml', $this->getAcctInfo( $order ), $order );
            if( !empty( $AcctInfoXML ) && $this->get_acctInfo() === 'yes' ) {
                $data['AcctInfoXML'] = $AcctInfoXML;
            }

            // Get MerchantRiskIndicatorXML object
            $MerchantRiskIndicatorXML = apply_filters( 'woocommerce_opayo_direct_get_merchantRiskIndicator_xml', $this->getThreeDSRequestorAuthenticationInfo( $order ), $order );
            if( !empty( $MerchantRiskIndicatorXML ) && $this->get_merchantRiskIndicator() === 'yes' ) {
                $data['MerchantRiskIndicatorXML'] = $MerchantRiskIndicatorXML;
            }

            // Filter the args if necessary, use with caution
            $data = apply_filters( 'woocommerce_sagepay_form_data', $data, $order );

            $sage_pay_args = array();

            // Remove empty values but leave in 0
            // $data = array_filter( $data, 'strlen' );

            foreach( $data as $param => $value ) {
                
                // Remove all the non-english things
                // $value = strtr( $value, WC_Sagepay_Common_Functions::unwanted_array() );

                if( function_exists( 'mb_convert_encoding' ) ) {
                    $value = mb_convert_encoding( $value, 'ISO-8859-1', 'UTF-8' );
                } elseif (function_exists( 'iconv' ) ) {
                    $value = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);
                }

                $sage_pay_args[$param] = $value;

            }

            /**
             * Debugging
             */
            if ( $this->debug == true ) {
                WC_Sagepay_Common_Functions::sagepay_debug( $sage_pay_args, $this->id, __('Sent to Opayo : ', 'woocommerce-gateway-sagepay-form'), TRUE );
            }

            $sagepaycrypt_b64  = WC_Sagepay_Common_Functions::encrypt( $this->array_to_attributes ( $sage_pay_args ), $vendorpwd );

            $sagepaycrypt      = '<input type="hidden" name="Crypt" value="' . $sagepaycrypt_b64 . '" />';
            
            // This is the form. 
            $form = '<form action="' . $sagepayform_adr . '" method="post" id="sagepayform_payment_form">
                    ' . $sagepayform . '
                    ' . $sagepaycrypt . '
                    <input type="submit" class="button-alt" id="submit_sagepayform_payment_form" value="' . __( 'Pay via Opayo', 'woocommerce-gateway-sagepay-form' ) . '" /> 
                    <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce-gateway-sagepay-form' ) . '</a>
                    </form>';

            return $form;

        }

        /**
         * process_payment function.
         *
         * @access public
         * @param mixed $order_id
         * @return void
         */
        function process_payment( $order_id ) {
            $order = new WC_Order( $order_id );

            return array(
                'result'    => 'success',
                'redirect'  => $order->get_checkout_payment_url( true )
            );
            
        }

        /**
         * receipt_page function.
         *
         * @access public
         * @param mixed $order
         * @return void
         */
        function receipt_page( $order ) {
            echo '<p>' . __( 'Thank you for your order, please click the button below to pay with Opayo.', 'woocommerce-gateway-sagepay-form' ) . '</p>';
            echo $this->generate_sagepay_form( $order );
        }

        /**
         * check_sagepay_response function.
         *
         * @access public
         * @return void
         */
        function check_sagepay_response() {

            @ob_clean();

            if ( isset( $_GET["crypt"] ) ) {

                if ( $this->status == 'testing' && $this->testvendorpwd ) {
                    $vendorpwd = $this->testvendorpwd;
                } else {
                    $vendorpwd = $this->vendorpwd;
                }

                $opayo_key                          = isset( $_GET["opayo_key"] ) ? $_GET["opayo_key"] : NULL;
                $opayo_id                           = isset( $_GET["opayo_id"] ) ? $_GET["opayo_id"] : NULL;
                $crypt                              = $_GET["crypt"];

                $sagepay_return_data                = WC_Sagepay_Common_Functions::decrypt( $crypt, $vendorpwd );
                $sagepay_return_values              = WC_Sagepay_Common_Functions::getTokens( $sagepay_return_data );

                $sagepay_return_values['opayo_key'] = wc_clean( $opayo_key );
                $sagepay_return_values['opayo_id']  = wc_clean( $opayo_id );

                /**
                 * Debugging
                 */
                if ( $this->debug == true ) {
                    WC_Sagepay_Common_Functions::sagepay_debug( $sagepay_return_values, $this->id, __('Opayo Return : ', 'woocommerce-gateway-sagepay-form'), FALSE );
                }

                if ( isset( $sagepay_return_values['VPSTxId'] ) ) {
                    do_action( "valid_sagepayform_request", $sagepay_return_values );
                }

            } else {

                wp_die( "Sage Request Failure<br />" . 'Check the WooCommerce Opayo Settings for error messages', "Opayo Failure", array( 'response' => 200 ) );
            }
        }


        /**
         * successful_request function.
         *
         * @access public
         * @param mixed $sagepay_return_values
         * @return void
         */
        function successful_request( $sagepay_return_values ) {
            
            $ordernotes      = '';
            $accepted_status = array( 'OK', 'NOTAUTHED', 'MALFORMED', 'INVALID', 'ABORT', 'REJECTED', 'AUTHENTICATED', 'REGISTERED', 'ERROR' );
            $order_key_array = array( $this->vendortxcodeprefix, 'wc_', 'order_', 'wc_order_', 'order_wc_' );

            // Add plugin version to order notes
            $sagepay_return_values["Version"] = OPAYOPLUGINVERSION;

            // Custom holds post ID
            if ( ! empty( $sagepay_return_values['Status'] ) && ! empty( $sagepay_return_values['VendorTxCode'] ) ) {

                if ( ! in_array( $sagepay_return_values['Status'], $accepted_status ) ) {
                    echo "<p>" . $sagepay_return_values['Status'] . " NOT FOUND!</p>";
                    exit;
                }

                if( isset( $sagepay_return_values['opayo_key'] ) && !is_null( $sagepay_return_values['opayo_key'] ) ) {

                    // Get the order_key from the Opayo return
                    $order_key_from_sage    = $sagepay_return_values['opayo_key'];

                    // Get the order ID from the order key from the Opayo return
                    $order_id_by_order_key  = wc_get_order_id_by_order_key( $order_key_from_sage );

                    // Get the order ID from the Opayo return
                    $order_id_from_sage     = $sagepay_return_values['opayo_id'];

                } else {

                    // Split $sagepay_return_values['VendorTxCode']
                    $VendorTxCode           = explode( '-', $sagepay_return_values['VendorTxCode'] );

                    // Get the order_key from VendorTxCode
                    $order_key_from_sage    = $this->get_order_key( $sagepay_return_values['VendorTxCode'] );

                    // Get the order ID from the order key from VendorTxCode
                    $order_id_by_order_key  = wc_get_order_id_by_order_key( $order_key_from_sage );

                    // Get the order ID from VendorTxCode
                    $order_id_from_sage     = $VendorTxCode[1];

                }

                // Get the order
                $order                  = new WC_Order( (int) $order_id_from_sage );

                // Get order_key from order
                $order_key              = $order->get_order_key();
                
                // Stop if the order keys don't match
                if ( $order_key !== $order_key_from_sage ) {
                    echo "<p>" . $order_key . " AND " . $order_key_from_sage . " DO NOT MATCH!</p>";
                    exit;
                }

                // Stop if the order ids don't match
                if ( $order_id_from_sage !== $order_id_by_order_key ) {
                    echo "<p>" . $order_id_from_sage . " AND " . $order_id_by_order_key . " DO NOT MATCH!</p>";
                    exit;
                }

                $order_id       = $order_id_from_sage;
                $order_status   = $order->get_status();

                if ( $order_status !== 'completed' && $order_status !== 'processing' ) {

                    // Check developer tools
                    if ( $this->status == 'testing' ) {
                        $sagepay_return_values = $this->check_developer_tools( $sagepay_return_values );
                    }

                    // We are here so lets check status and do actions
 
                    switch ( strtolower( $sagepay_return_values['Status'] ) ) {
                        case 'ok' :
                            // Payment completed

                            // Add order meta
                            $this->update_order_meta_checks( $order_id, $sagepay_return_values );

                            // Add Surcharge maybe
                            $this->maybe_update_order_add_surcharge( $order_id, $order, $sagepay_return_values );

                            // Add order notes
                            $message = __('Payment completed', 'woocommerce-gateway-sagepay-form');
                            $this->update_order( $order_id, $order, $sagepay_return_values, $message, false );

                            // Empty the cart
                            WC()->cart->empty_cart();

                            // Complete the payment, trigger emails etc
                            $order->payment_complete( $sagepay_return_values['VPSTxId'] );

                            do_action( 'woocommerce_sagepay_form_payment_complete', $sagepay_return_values, $order );

                            // Redirect the customer to Thank You page
                            $redirect_url = $this->get_return_url( $order );

                        break;
                        case 'notauthed' :
                            // Add order meta
                            $this->update_order_meta_checks( $order_id, $sagepay_return_values );
                            
                            // Update order status
                            $message = __( 'Payment %s via Opayo.', 'woocommerce-gateway-sagepay-form' );
                            $this->update_order( $order_id, $order, $sagepay_return_values, $message, 'failed' );

                            // Create message for customer
                            $this->opayo_message( ( __('Payment error. Please try again, your card has not been charged', 'woocommerce-gateway-sagepay-form') . ': ' . $sagepay_return_values['StatusDetail'] ) , 'error', $order_id );
                            
                            // Redirect the customer to Thank You page
                            $redirect_url = wc_get_checkout_url();

                        break;
                        case 'authenticated' :
                            // Payment authorized
                            
                            // Add order meta
                            $this->update_order_meta_checks( $order_id, $sagepay_return_values );

                            // Add Surcharge maybe
                            $this->maybe_update_order_add_surcharge( $order_id, $order, $sagepay_return_values );

                            // Add order notes
                            $message = __('Opayo payment authenticated - No funds have been collected at this time, please log in to MySagePay to collect the funds', 'woocommerce-gateway-sagepay-form');
                            $this->update_order( $order_id, $order, $sagepay_return_values, $message, false );

                            // Empty the cart
                            WC()->cart->empty_cart();

                            // Complete the payment, trigger emails etc
                            $order->payment_complete( $sagepay_return_values['VPSTxId'] );

                            // Redirect the customer to Thank You page
                            $redirect_url = $this->get_return_url( $order );

                        break;
                        case 'registered' :
                            // Add order meta
                            $this->update_order_meta_checks( $order_id, $sagepay_return_values );

                            // Update order status
                            $message = __( 'Payment %s via Opayo.<br />SagePay payment registered - 3D Secure check failed', 'woocommerce-gateway-sagepay-form' );
                            $this->update_order( $order_id, $order, $sagepay_return_values, $message, 'failed' );

                            // Create message for customer
                            $this->opayo_message( ( __('Payment error. Please try again, your card has not been charged', 'woocommerce-gateway-sagepay-form') . ': ' . $sagepay_return_values['StatusDetail'] ) , 'error', $order_id );
                            
                            // Redirect the customer to Thank You page
                            $redirect_url = wc_get_checkout_url();

                        break;
                        case 'malformed' :
                        case 'invalid' :
                        case 'abort' :
                        case 'rejected' :
                        case 'error' :
                            // Add order meta
                            $this->update_order_meta_checks( $order_id, $sagepay_return_values );

                            // Failed order, update order status
                            $message = __( 'Payment %s via Opayo.', 'woocommerce-gateway-sagepay-form' );
                            $this->update_order( $order_id, $order, $sagepay_return_values, $message, 'failed' );

                            // Create message for customer
                            $this->opayo_message( ( __('Payment error. Please try again, your card has not been charged', 'woocommerce-gateway-sagepay-form') . ': ' . $sagepay_return_values['StatusDetail'] ) , 'error', $order_id );
                            
                            // Redirect the customer to Thank You page
                            $redirect_url = wc_get_checkout_url();

                        break;
                    }
                }

                // Maybe set Fraud Response from Opayo
                if( isset( $sagepay_return_values['FraudResponse'] ) && in_array( $sagepay_return_values['FraudResponse'], $fraud_status_array ) ) {
                    $message = sprintf( __( 'Opayo Fraud Response %s via SagePay. Login to MySagePay and check this order before shipping.', 'woocommerce-gateway-sagepay-form' ), wc_clean( $sagepay_return_values['FraudResponse'] ) );
                    $this->update_order( $order_id, $order, $sagepay_return_values, $message, 'fraud-screen' );
                }

                wp_redirect( $redirect_url );
                exit;
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
         * [get_order_key description]
         * @param  [type] $VendorTXCode [description]
         * @return [type]               [description]
         */
        function get_order_key( $VendorTXCode ) {

            $order_key_array = array( $this->vendortxcodeprefix, 'wc_', 'order_', 'wc_order_', 'order_wc_' );
            $VendorTxCode    = explode( '-', $VendorTXCode );

            $order_key = str_replace( $order_key_array,'',$VendorTxCode[0] );

            return 'wc_order_' . $order_key;

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
            //    $phone_number = $calling_code . preg_replace( '/^0/', '', $order->get_billing_phone() );
            }

            return $phone_number;

        }

        /**
         * [update_order_meta_checks description]
         * @param  [type] $order_id              [description]
         * @param  [type] $sagepay_return_values [description]
         * @return [type]                        [description]
         */
        function update_order_meta_checks( $order_id, $sagepay_return_values ) {

            update_post_meta( $order_id, '_sageresult' , $sagepay_return_values );

            if( isset($sagepay_return_values['VPSTxId']) ) {
               update_post_meta( $order_id, '_VPSTxId' , str_replace( array('{','}'),'',$sagepay_return_values['VPSTxId'] ) ); 
            }
            
            if( isset($sagepay_return_values['TxAuthNo']) ) {
               update_post_meta( $order_id, '_TxAuthNo' , $sagepay_return_values['TxAuthNo'] );
            }
            
            if( isset($sagepay_return_values['AVSCV2']) ) {
               update_post_meta( $order_id, '_AVSCV2' , $sagepay_return_values['AVSCV2'] );
            }
            
            if( isset($sagepay_return_values['AddressResult']) ) {
               update_post_meta( $order_id, '_AddressResult' , $sagepay_return_values['AddressResult'] );
            }
            
            if( isset($sagepay_return_values['PostCodeResult']) ) {
               update_post_meta( $order_id, '_PostCodeResult' , $sagepay_return_values['PostCodeResult'] );
            }
            
            if( isset($sagepay_return_values['CV2Result']) ) {
               update_post_meta( $order_id, '_CV2Result' , $sagepay_return_values['CV2Result'] );
            }
            
            if( isset($sagepay_return_values['3DSecureStatus']) ) {
               update_post_meta( $order_id, '_3DSecureStatus' , $sagepay_return_values['3DSecureStatus'] );
            }

            if( isset( $sagepay_return_values['AddressStatus'] ) ) {
                update_post_meta( $order_id, '_AddressResult' , $sagepay_return_values['AddressStatus'] );
            }

            if( isset( $sagepay_return_values['PayerStatus'] ) ) {
                update_post_meta( $order_id, '_PayerStatus' , $sagepay_return_values['PayerStatus'] );
            }
                    
        }

        /**
         * [maybe_update_order_add_surcharge description]
         * @param  [type] $order_id              [description]
         * @param  [type] $order                 [description]
         * @param  [type] $sagepay_return_values [description]
         * @return [type]                        [description]
         */
        function maybe_update_order_add_surcharge( $order_id, $order, $sagepay_return_values ) {

            // Add fee to order if there is a SagePay surcharge
            if ( isset($sagepay_return_values['Surcharge']) ) {

                $item_id = wc_add_order_item( $order_id, array(
                    'order_item_name'       => 'SagePay Surcharge',
                    'order_item_type'       => 'fee'
                ) );

                // Add line item meta
                if ( $item_id ) {

                    wc_add_order_item_meta( $item_id, '_tax_class', '' );
                    wc_add_order_item_meta( $item_id, '_line_total', $sagepay_return_values['Surcharge'] );
                    wc_add_order_item_meta( $item_id, '_line_tax', '' );

                }

                // Update order total to include surcharge
                $old_order_total = $order->get_total();
                $order->set_total( $old_order_total + $sagepay_return_values['Surcharge'] );

                // Save the order
                $order->save();

                $ordernotes .= '<br /><br />Order total updated';
                $ordernotes .= '<br />Surcharge : '     . $sagepay_return_values['Surcharge'];

            } // Add fee to order if there is a SagePay surcharge

        }

        /**
         * [update_order description]
         * @param  [type]  $order_id              [description]
         * @param  [type]  $order                 [description]
         * @param  [type]  $sagepay_return_values [description]
         * @param  [type]  $message               [description]
         * @param  boolean $status                [description]
         * @return [type]                         [description]
         */
        function update_order( $order_id, $order, $sagepay_return_values, $message = NULL, $status = false ) {

            $ordernotes = '';

            // Add plugin version to order notes
            $sagepay_return_values["Version"] = OPAYOPLUGINVERSION;

            foreach ( $sagepay_return_values as $key => $value ) {
                $ordernotes .= $key . ' : ' . $value . "\r\n";
            }

            if( $status ) {
                $order->update_status('failed', sprintf( __( 'Payment %s via SagePay.', 'woocommerce-gateway-sagepay-form' ), wc_clean( $sagepay_return_values['Status'] ) ) );

                $order->update_status('failed', sprintf( $message, wc_clean( $sagepay_return_values['Status'] ) ) . '<br />' . $ordernotes );

            } else {
                $order->add_order_note( $message . '<br />' . $ordernotes );
            }

        }

        /**
         * [sagepay_system_status description]
         * @return [type] [description]
         */
        function sagepay_system_status() {

            $description = __( 'Opayo Form works by sending the user to <a href="http://www.opayo.com">Opayo</a> to enter their payment information.', 'woocommerce-gateway-sagepay-form' );
            return $description;

        }

        /**
         * base64Decode function.
         *
         * @access public
         * @param mixed $scrambled
         * @return void
         */
        function base64Decode( $scrambled )   {
            // Initialise output variable
            $output = "";

            // Fix plus to space conversion issue
            $scrambled = str_replace( " ", "+", $scrambled );

            // Do decoding
            $output = base64_decode( $scrambled );

            // Return the result
            return $output;
        }

        /**
         * simpleXor function.
         *
         * @access public
         * @param mixed $text
         * @param mixed $key
         * @return void
         */
        function simpleXor( $text, $key ) {
            // Initialise key array
            $key_ascii_array = array();

            // Initialise output variable
            $output = "";

            // Convert $key into array of ASCII values
            for ( $i = 0; $i < strlen( $key ); $i++ ) {
                $key_ascii_array[ $i ] = ord( substr( $key, $i, 1 ) );
            }

            // Step through string a character at a time
            for ( $i = 0; $i < strlen( $text ); $i++ ) {
                // Get ASCII code from string, get ASCII code from key (loop through with MOD), XOR the
                // two, get the character from the result
                $output .= chr( ord( substr( $text, $i, 1 ) ) ^ ( $key_ascii_array[ $i % strlen( $key ) ] ) );
            }

            // Return the result
            return $output;
        }

        /**
         * [check_developer_tools description]
         * @param  [type] $return [description]
         * @return [type]         [description]
         */
        function check_developer_tools( $return ) {

            $settings = get_option( 'woocommerce_sagepayform_settings' );

            if( isset( $settings['force_status'] ) && $settings['force_status'] !== 'default' ) {
                $return['Status'] = $settings['force_status'];
            }

            if( isset( $settings['force_fraud_response'] ) && $settings['force_fraud_response'] !== 'default' ) {
                $return['FraudResponse'] = $settings['force_fraud_response'];
            }

            return $return;

        }

        /**
         * Opayo Form Refund Processing
         * @param  [type]        $order_id [description]
         * @param  [type]        $amount   [description]
         * @param  [type]        $reason   [description]
         * @return [type]                  [description]
         */
        function process_refund( $order_id, $amount = NULL, $reason = '' ) {

            $order          = new WC_Order( $order_id );

            $payment_method = $order->get_payment_method();

            if( isset( $payment_method ) && $payment_method == 'sagepayform' ) {

                $order                      = new WC_Order( $order_id );
                $_opayo_reporting_output    = get_post_meta( $order_id, '_opayo_reporting_output', TRUE );

                $VendorTxCode   = 'Refund-' . $order_id . '-' . time();

                // SAGE Line 50 Fix
                $VendorTxCode   = str_replace( 'order_', '', $VendorTxCode );

                $data = array( 
                    'VPSProtocol'           => $this->protocol,
                    'TxType'                => 'REFUND',
                    'Vendor'                => $this->settings['vendor'],
                    'VendorTxCode'          => $VendorTxCode,
                    'Amount'                => $amount,
                    'Currency'              => $order->get_currency(),
                    'Description'           => 'Refund for order ' . $order_id,
                    'RelatedVPSTxId'        => $_opayo_reporting_output['vpstxid'],
                    'RelatedVendorTxCode'   => $_opayo_reporting_output['vendortxcode'],
                    'RelatedSecurityKey'    => $_opayo_reporting_output['securitykey'],
                    'RelatedTxAuthNo'       => get_post_meta( $order_id, '_TxAuthNo', TRUE ),
                );

                $result = $this->sagepay_post( $data, $this->refundURL );

                $result = $this->return_array( $result );

                if ( !isset( $result['Status'] ) || 'OK' !== $result['Status'] ) {

                    $order->add_order_note( __('Refund failed', 'woocommerce-gateway-sagepay-form') . '<br />' . $result['StatusDetail'] );

                    /**
                     * Debugging
                     */
                    WC_Sagepay_Common_Functions::sagepay_debug( $result, $this->id, __('SagePay Response : ', 'woocommerce-gateway-sagepay-form'), TRUE );

                    return new WP_Error( 'error', __('Refund failed ', 'woocommerce-gateway-sagepay-form')  . "\r\n" . $result['StatusDetail'] );

                } else {

                    $ordernote = '';

                    foreach ( $result as $key => $value ) {
                        $ordernote .= $key . ' : ' . $value . "\r\n";
                    }

                    $order->add_order_note( __('Refund successful', 'woocommerce-gateway-sagepay-form') . '<br />' . 
                                            __('Refund Amount : ', 'woocommerce-gateway-sagepay-form') . $amount . '<br />' .
                                            __('Refund Reason : ', 'woocommerce-gateway-sagepay-form') . $reason . '<br />' .
                                            __('Full return from SagePay', 'woocommerce-gateway-sagepay-form') . '<br />' .
                                            $ordernote );

                    return true;
            
                }

            }

        } // process_refund

        /**
         * Opayo Form Void
         * @param  [type]        $order_id [description]
         * @param  [type]        $amount   [description]
         * @param  [type]        $reason   [description]
         * @return [type]                  [description]
         */
        function opayo_form_void_order( $order ) {

            $payment_method = $order->get_payment_method();

            if( isset( $payment_method ) && $payment_method == 'sagepayform' ) {

                $order_id = $order->get_id();

                $_opayo_reporting_output    = get_post_meta( $order_id, '_opayo_reporting_output', TRUE );

                $data = array( 
                    'VPSProtocol'    => $this->protocol,
                    'TxType'         => 'VOID',
                    'Vendor'         => $this->settings['vendor'],
                    'VendorTxCode'   => $_opayo_reporting_output['vendortxcode'],
                    'VPSTxId'        => $_opayo_reporting_output['vpstxid'],
                    'SecurityKey'    => $_opayo_reporting_output['securitykey'],
                    'TxAuthNo'       => get_post_meta( $order_id, '_TxAuthNo', TRUE ),
                );

                $result = $this->sagepay_post( $data, $this->voidURL );

                $result = $this->return_array( $result );

                if ( !isset( $result['Status'] ) || 'OK' !== $result['Status'] ) {

                    $order->add_order_note( __('Void failed', 'woocommerce-gateway-sagepay-form') . '<br />' . print_r( $result, TRUE ) );

                    /**
                     * Debugging
                     */
                    WC_Sagepay_Common_Functions::sagepay_debug( $result, $this->id, __('Opayo Response : ', 'woocommerce-gateway-sagepay-form'), TRUE );

                } else {

                    $ordernote = '';

                    foreach ( $result as $key => $value ) {
                        $ordernote .= $key . ' : ' . $value . "\r\n";
                    }

                    $order->add_order_note( __('Void successful', 'woocommerce-gateway-sagepay-form') . '<br />' .
                                            __('Full return from Opayo', 'woocommerce-gateway-sagepay-form') . '<br />' .
                                            $ordernote );

                    $order->update_status( 'cancelled', _x( 'The order has been voided.', 'woocommerce-gateway-sagepay-form' ) );
                    $order->save();
            
                }

            }

        } // opayo_form_void_order

        /**
         * Send the info to Sage for processing
         * https://test.sagepay.com/showpost/showpost.asp
         */
        function sagepay_post( $data, $url ) {

            // Debugging
            if ( $this->debug == true || $this->status != 'live' ) {
                $to_log['DATA'] = $data;
                $to_log['URL']  = $url;
                WC_Sagepay_Common_Functions::sagepay_debug( $to_log, $this->id, __('Sent to Opayo : ', 'woocommerce-gateway-sagepay-form'), TRUE );
            }

            // Convert $data array to query string for Sage
            if( is_array( $data) ) {
                // Convert the $data array for Sage
                $data = http_build_query( $data, '', '&', PHP_QUERY_RFC3986 );
            }

            if ( $this->debug == true || $this->status != 'live' ) {
                WC_Sagepay_Common_Functions::sagepay_debug( $data, $this->id, __('Sent to Opayo : ', 'woocommerce-gateway-sagepay-form'), TRUE );
            }

            $params = array(
                            'method'        => 'POST',
                            'timeout'       => apply_filters( 'woocommerce_opayo_post_timeout', 45 ),
                            'httpversion'   => '1.1',
                            'headers'       => array('Content-Type'=> 'application/x-www-form-urlencoded'),
                            'body'          => $data,
                            // 'sslverify'  => false
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

                return $res['body'];

            }

        }

        /**
         * [return_array description]
         * @param  [type] $result [description]
         * @return [type]         [description]
         */
        function return_array( $result ) {

            $results = preg_split("/\r\n|\n|\r/", $result );

            $return  = array();

            foreach( $results AS $res ) {
                $res = explode("=", $res );
                $return[$res[0]] = $res[1];
            }

            return $return;

        }

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
         * [array_to_attributes description]
         * @param  [type] $array_attributes [description]
         * @return [type]                   [description]
         */
        function array_to_attributes( $array_attributes ) {

            $attributes_str = NULL;
            foreach ( $array_attributes as $attribute => $value )
            {

                $attributes_str .= "$attribute=$value&";

            }

            // Remove trailing & and return
            return rtrim($attributes_str, "&");
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
            $deliveryTimeframeXMO    = '';
            $shipIndicatorXML        = '';
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
            
            $deliveryEmailAddress   = apply_filters( 'woocommerce_opayo_form_get_merchantRiskIndicator_deliveryEmailAddress', $deliveryEmailAddress, $order );
            $deliveryTimeframe      = apply_filters( 'woocommerce_opayo_form_get_merchantRiskIndicator_deliveryTimeframe', $deliveryTimeframe, $order );
            $shipIndicator          = apply_filters( 'woocommerce_opayo_form_get_merchantRiskIndicator_shipIndicator', $shipIndicator, $order );
            $preOrderPurchaseInd    = apply_filters( 'woocommerce_opayo_form_get_merchantRiskIndicator_preOrderPurchaseInd', '01', $order );

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

            $xml .= "<chAccAgeInd>" . apply_filters( 'woocommerce_opayo_form_get_chAccAgeInd', $chAccAgeInd, $xml, $order ) . "</chAccAgeInd>" . "\r\n";

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

                // Check that the returned date is positive
                $registered_date_strtotime  = ( new DateTime( $user_object->user_registered ) )->getTimestamp();

                if( preg_match('/^[1-9]\d*$/',$registered_date_strtotime) ) {
                    $xml .= "<chAccDate>" . apply_filters( 'woocommerce_opayo_form_get_chAccDate', $registered_date, $xml, $order ) . "</chAccDate>" . "\r\n";
                }
                
            }

            return $xml;              
        }

        function get_chAccPwChange( $xml, $order ) {

            if( 0 !== $order->get_user_id() ) {
                // Get the date that the user last updated their password 
                $date = get_user_meta( $order->get_user_id(), '_user_updated_password_date', TRUE );

                // Check if there is a date and include it.
                if( !empty( $date ) ) {
                    $xml .= "<chAccPwChange>" . apply_filters( 'woocommerce_opayo_form_get_chAccPwChange', $registered_date, $xml, $order ) . "</chAccPwChange>" . "\r\n";

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

                    $xml .= "<chAccPwChangeInd>" . apply_filters( 'woocommerce_opayo_form_get_chAccPwChange', $chAccPwChangeInd, $xml, $order ) . "</chAccPwChangeInd>" . "\r\n";

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
                    $xml .= "<nbPurchaseAccount>" . apply_filters( 'woocommerce_opayo_form_get_nbPurchaseAccount', count( $orders_count_array ), $xml, $order ) . "</nbPurchaseAccount>" . "\r\n";
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
                    $xml .= "<txnActivityDay>" . apply_filters( 'woocommerce_opayo_form_get_txnActivityDay', count($orders), $xml, $order ) . "</txnActivityDay>" . "\r\n";
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
                    $xml .= "<txnActivityYear>" . apply_filters( 'woocommerce_opayo_form_get_txnActivityYear', $count, $xml, $order ) . "</txnActivityYear>" . "\r\n";
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

                $xml .= "<shipNameIndicator>" . apply_filters( 'woocommerce_opayo_form_get_shipNameIndicator', $shipNameIndicator, $xml, $order ) . "</shipNameIndicator>" . "\r\n";

            }

            return $xml;

        }

    } // END CLASS
