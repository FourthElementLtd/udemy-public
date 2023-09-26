<?php
    /**
     * Process Response for SagePay Direct
     */
    class Sagepay_Direct_Response extends WC_Gateway_Sagepay_Direct {

        private $sageresult;
        private $order;

        public function __construct( $sageresult, $order ) {

            parent::__construct();

            $this->sageresult   = $sageresult;
            $this->order        = $order;
            $this->settings     = get_option( 'woocommerce_sagepaydirect_settings' );

        }

        function process() {

            // Settings
            $settings = $this->settings;

            // Drop out if SagePay Direct is not enabled
            if( !isset( $settings['enabled'] ) || $settings['enabled'] != "yes" ) {
                wp_die( "Sage Request Failure<br />" . 'Access denied', "Sage Failure", array( 'response' => 200 ) );
                exit;
            }

            // woocommerce order instance
            $order = $this->order;

            // Make sure we've got the WooCommerce order object
            if( !is_object( $order ) ) {
                $order = wc_get_order( $order );
            }

            // Get the Order ID
            $order_id   = $order->get_id();

            // Get the result from Opayo
            $sageresult = $this->sageresult;

            switch( strtoupper( $sageresult['Status'] ) ) {
                case 'OK':
                case 'REGISTERED':
                case 'AUTHENTICATED':

                    // Add the sanitized card details to $sageresult
                    $_SagePaySantizedCardDetails = get_post_meta( $order_id, '_SagePaySantizedCardDetails', TRUE );

                    if( isset($_SagePaySantizedCardDetails) && $_SagePaySantizedCardDetails != '' ) {

                        // Unset the ExpiryDate from Sage, make sure $sageresult is nice and tidy
                        unset( $sageresult['ExpiryDate'] );

                        // Add the card details to $sageresult
                        $sageresult['CardNumber']   = $_SagePaySantizedCardDetails['CardNumber'];
                        $sageresult['ExpiryDate']   = $_SagePaySantizedCardDetails['ExpiryDate'];
                        $sageresult['CardType']     = $_SagePaySantizedCardDetails['CardType'];

                        // Add GiftAidPayment for using in renewals.
                        if( isset( $_SagePaySantizedCardDetails['GiftAidPayment'] ) && $_SagePaySantizedCardDetails['GiftAidPayment'] == '1' ) {
                            $sageresult['GiftAidPayment'] = $_SagePaySantizedCardDetails['GiftAidPayment'];
                        }
                 
                    }

                    // Add Order notes to Admin email
                    if( $this->sagepaytransinfo ) {
                        update_post_meta( $order_id, '_sageresult' , $sageresult );
                    }

                    // Add Order Note
                    $this->add_order_note( __('Payment completed', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

                    // Update Order Meta
                    $this->update_order_meta( $sageresult, $order_id );

                    // Update Subscription Meta
                    $this->update_subscription_meta_maybe( $sageresult, $order_id );

                    // Update transaction type
                    $_SagePayTransactionType = get_post_meta( $order_id, '_SagePayTxType', TRUE );

                    // Allow plugins to hook in to successful payment
                    do_action( 'woocommerce_sagepay_direct_payment_complete', $sageresult, $order );

                    // Clean up Order Meta
                    $delete_card_details = apply_filters( 'opayo_delete_sanitized_card_details', true, $order_id );
                    if( $delete_card_details ) {
                        delete_post_meta( $order_id, '_SagePaySantizedCardDetails' );
                    }

                    // Clean up the order meta
                    delete_post_meta( $order_id, '_sage_3dsecure' );
                    delete_post_meta( $order_id, '_iframe_args' );

                    $sageresult['result']   = 'success';
                    $sageresult['redirect'] = $this->append_url( $order->get_checkout_order_received_url() );

                    $this->opayo_payment_complete( $order, $sageresult );

                    return $sageresult;

                break;

                case 'PAYPALOK':

                    // Empty Cart
                    if( is_callable( 'wc_empty_cart' ) ) {
                        wc_empty_cart(); 
                    }

                    $paypalok_result = $this->send_paypal_complete( $sageresult, $order );

                break;

                case '3DAUTH':

                    // This order requires 3D Secure authentication
                    WC()->session->set( "sage_3ds", "" );
                    delete_post_meta( $order_id, '_sage_3ds' );
                     
                    if( strtoupper( $sageresult['3DSecureStatus'] ) == 'OK' ) {

                        $sage_3ds                   = $sageresult;
                        $sage_3ds["TermURL"]        = $this->append_url( $order->get_checkout_payment_url( true ) );
                        $sage_3ds["Complete3d"]     = $this->append_url( $order->get_checkout_payment_url( true ) );
                        $sage_3ds['VendorTxCode']   = get_post_meta( $order_id, '_VendorTxCode', TRUE );

                        if( isset( $sageresult['change_payment_method'] ) && $sageresult['change_payment_method'] != "" ) {
                            $sage_3ds['change_payment_method'] = $sageresult['change_payment_method'];
                        }

                        // Set the session variables for 3D Direct
                        WC()->session->set( "sage_3ds", $sage_3ds );
                        // Order meta for failed session
                        update_post_meta( $order_id, '_sage_3ds', $sage_3ds );

                        // Go to the pay page for 3d securing
                        $sageresult['result']   = 'success';
                        $sageresult['redirect'] = $this->append_url( $order->get_checkout_payment_url( true ) );

                    }

                    return $sageresult;
                
                break;

                case 'PPREDIRECT':

                    // Go to paypal
                    $sageresult['result']   = 'success';
                    $sageresult['redirect'] = $sageresult['PayPalRedirectURL'];
                    
                    // Temporary order note
                    $temporary_result = array( 
                                        'Order Status: ' => $order->get_status(),
                                        'Opayo Status: ' => $sageresult['Status'],
                                        'Redirect URL: ' => $sageresult['redirect']
                                    );
                    
                    $this->add_order_note( __('End of Opayo "PPREDIRECT" process', 'woocommerce-gateway-sagepay-form'), $temporary_result, $order );

                    return $sageresult;
                
                break;

                case 'EXISTINGTOKEN':

                    // Add Order Note
                    $this->add_order_note( __('Payment completed', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

                    update_post_meta( $order_id, '_SagePayDirectToken' , str_replace( array('{','}'),'',$sageresult['Token'] ) );

                    // Update Order Meta
                    $this->update_order_meta( $sageresult, $order_id );

                    $order->payment_complete();

                    // Temporary order note
                    $temporary_result = array( 
                                        'Order Status: ' => $order->get_status(),
                                        'Opayo Status: ' => $sageresult['Status'],
                                        'Redirect URL: ' => $this->append_url( $order->get_checkout_order_received_url() )
                                    );
                    
                    $this->add_order_note( __('End of Opayo "EXISTINGTOKEN" process', 'woocommerce-gateway-sagepay-form'), $temporary_result, $order );

                    wp_redirect( $this->append_url( $order->get_checkout_order_received_url() ) ); 
                    exit;
                
                break;

                case 'CHANGEPAYMENTMETHODEXISTINGTOKEN':

                    // Subscription ID
                    $subscription_id    = $order_id;

                    // Set return URL, return customer to My Account -> View Subscription
                    $return_url = wc_get_endpoint_url( 'view-subscription', $subscription_id, wc_get_page_permalink( 'myaccount' ) );

                    if( isset( $sageresult['Token'] ) && $sageresult['Token'] != '' ) {
                        // Get parent order ID
                        $subscription       = new WC_Subscription( $order_id );
                        $parent_order       = $subscription->get_parent();
                        $parent_order_id    = $subscription->get_parent_id();

                        // Old payment method
                        $old_method = get_post_meta( $subscription_id, '_payment_method', TRUE );

                        // Payment method change has been successfull, if the old payment method was PayPal then it needs to be cancelled at PayPal
                        if( class_exists( 'WCS_PayPal_Status_Manager' ) && $old_method == 'paypal') {
                            $payal_subscription_cancelled = WCS_PayPal_Status_Manager::cancel_subscription( $subscription );
                        }

                        // No need to do this, the subscription has the token information
                        // Update Parent Order with new token info
                        // update_post_meta( $parent_order_id, '_SagePayDirectToken' , str_replace( array('{','}'),'',$sageresult['Token'] ) );

                        // Update Subscription with token info
                        update_post_meta( $subscription_id, '_SagePayDirectToken' , str_replace( array('{','}'),'',$sageresult['Token'] ) );

                        // Delete related transaction details from subscription to force the new token to be used
                        delete_post_meta( $subscription_id, '_RelatedVPSTxId' );
                        delete_post_meta( $subscription_id, '_RelatedSecurityKey' );
                        delete_post_meta( $subscription_id, '_RelatedTxAuthNo' );
                        delete_post_meta( $subscription_id, '_RelatedVendorTxCode' );

                        // Set the data needed to release this amount later
                        $opayo_free_trial = array( 
                            "TxType"        => "DEFERRED",
                            "VPSProtocol"   => $sageresult['VPSProtocol'],
                            "VPSTxId"       => $sageresult['VPSTxId'],
                            "SecurityKey"   => $sageresult['SecurityKey'],
                            "TxAuthNo"      => $sageresult['TxAuthNo'],
                        );

                        update_post_meta( $subscription_id, '_opayo_free_trial', $opayo_free_trial );

                        // Create message for customer
                        $this->sagepay_message( ( __('Your payment method has been updated.', 'woocommerce-gateway-sagepay-form') ) , 'success', $subscription_id );

                    } else {

                        // Create message for customer
                        $this->sagepay_message( ( __('Your payment method has not updated, please try again.', 'woocommerce-gateway-sagepay-form') ) , 'error', $subscription_id );
                    }

                    // Add a note to the subscription
                    if( isset( $sageresult['StatusDetail'] ) && $sageresult['StatusDetail'] !== '' ) {
                        $order->add_order_note( $sageresult['StatusDetail'] );
                    }

                    // JavaScript Redirect
                    $this->opayo_redirect( $return_url );
                    // Stops here.
                
                break;

                case 'CHANGEPAYMENTMETHODNEWTOKEN' :

                    // Subscription ID
                    $subscription_id        = $order_id;
                    $sageresult['Token']    = get_post_meta( $order_id, '_SagePayDirectToken', TRUE );

                    // Set return URL, return customer to My Account -> View Subscription
                    $return_url = wc_get_endpoint_url( 'view-subscription', $subscription_id, wc_get_page_permalink( 'myaccount' ) );

                    if( isset( $sageresult['Token'] ) && $sageresult['Token'] != '' && isset( $sageresult['OpayoStatus'] ) && in_array( $sageresult['OpayoStatus'], array('OK','AUTHENTICATED') ) ) {
                        
                        // Get parent order ID
                        $subscription       = new WC_Subscription( $order_id );
                        $parent_order       = $subscription->get_parent();
                        $parent_order_id    = $subscription->get_parent_id();

                        // Old payment method
                        $old_method = get_post_meta( $subscription_id, '_payment_method', TRUE );

                        // Update payment method
                        update_post_meta( $subscription_id, '_payment_method', 'sagepaydirect' );

                        // Update VPSProtocol
                        if( isset( $sageresult['VPSProtocol'] ) ) {
                            update_post_meta( $subscription_id, '_VPSProtocol', $sageresult['VPSProtocol'] );
                        }

                        // Delete related transaction details from subscription to force the new token to be used for the next renewal
                        delete_post_meta( $subscription_id, '_RelatedVPSTxId' );
                        delete_post_meta( $subscription_id, '_RelatedSecurityKey' );
                        delete_post_meta( $subscription_id, '_RelatedTxAuthNo' );
                        delete_post_meta( $subscription_id, '_RelatedVendorTxCode' );

                        // Update Order Meta in Subscription
                        $this->update_order_meta( $sageresult, $subscription_id );

                        // Set the data needed to release this amount later
                        $opayo_free_trial = array( 
                            "TxType"        => "AUTHENTICATE",
                            "VPSProtocol"   => isset( $sageresult['VPSProtocol'] ) ? $sageresult['VPSProtocol'] : '',
                            "VPSTxId"       => isset( $sageresult['VPSTxId'] ) ? $sageresult['VPSTxId'] : '',
                            "SecurityKey"   => isset( $sageresult['SecurityKey'] ) ? $sageresult['SecurityKey'] : '',
                            "TxAuthNo"      => isset( $sageresult['TxAuthNo'] ) ? $sageresult['TxAuthNo'] : '',
                        );

                        update_post_meta( $subscription_id, '_opayo_free_trial', $opayo_free_trial );

                        // Payment method change has been successfull, if the old payment method was PayPal then it needs to be cancelled at PayPal
                        if( class_exists( 'WCS_PayPal_Status_Manager' ) && $old_method == 'paypal') {
                            $payal_subscription_cancelled = WCS_PayPal_Status_Manager::cancel_subscription( $subscription );
                        }

                        // Create message for customer
                        $this->sagepay_message( ( __('Your payment method has been updated.', 'woocommerce-gateway-sagepay-form') ) , 'success', $subscription_id );

                    } else {

                        // Remove the failed token from the Subscription
                        delete_post_meta( $order_id, '_SagePayDirectToken' );

                        // Create message for customer
                        $this->sagepay_message( ( __('Your payment method has not updated, please try again.', 'woocommerce-gateway-sagepay-form') ) , 'error', $subscription_id );

                    }

                    // Add a note to the subscription
                    if( isset( $sageresult['StatusDetail'] ) && $sageresult['StatusDetail'] !== '' ) {
                        $order->add_order_note( $sageresult['StatusDetail'] );
                    }

                    // JavaScript Redirect
                    $this->opayo_redirect( $return_url );
                    // Stops here.
                
                break;

                case 'INVALID':

                    // Add a test to make sure the order is not paid for, fallback for 5036 message
                    if ( !$this->opayo_needs_payment( $order ) ) {
                        
                        // Add Order Note
                        $this->add_order_note( __('Payment redirection failed. INVALID', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

                        $return_url = $this->append_url( $order->get_checkout_order_received_url() );

                        // JavaScript Redirect
                        $this->opayo_redirect( $return_url );
                        // Stops here.

                    } else {

                        $update_order_status = apply_filters( 'woocommerce_opayo_direct_failed_order_status', 'pending', $order, $sageresult );
                      
                        // Add Order Note
                        $this->add_order_note( __('Payment failed', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

                        // Create message for customer
                        $this->sagepay_message( ( __('Payment error. Please try again, your card has not been charged.', 'woocommerce-gateway-sagepay-form') . ': ' . $sageresult['StatusDetail'] ) , 'error', $order_id );
                        
                        // Update the order status
                        $order->update_status( $update_order_status );

                        // Clean up Order Meta
                        delete_post_meta( $order_id, '_sage_3dsecure' );

                        // Clear session variables
                        WC()->session->set( "sage_3ds", "" );

                        $sageresult['result']   = 'success';
                        $sageresult['redirect'] = wc_get_checkout_url();

                        // Soft Decline
                        if( isset( $sageresult['DeclineCode'] ) && in_array( $sageresult['DeclineCode'], array('65','1A') ) ) {
                            update_post_meta( $order_id, '_opayo_soft_decline', $sageresult['DeclineCode'] );
                        }

                    }

                    return $sageresult;

                break;

                case 'NOTAUTHED':
                case 'MALFORMED':
                case 'ERROR':

                    $update_order_status = apply_filters( 'woocommerce_opayo_direct_failed_order_status', 'pending', $order, $sageresult );
                  
                    // Add Order Note
                    $this->add_order_note( __('Payment failed', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

                    // Create message for customer
                    $this->sagepay_message( ( __('Payment error. Please try again, your card has not been charged.', 'woocommerce-gateway-sagepay-form') . ': ' . $sageresult['StatusDetail'] ) , 'error', $order_id );
                    
                    // Update the order status
                    $order->update_status( $update_order_status );

                    // Clean up Order Meta
                    delete_post_meta( $order_id, '_sage_3dsecure' );

                    // Clear session variables
                    WC()->session->set( "sage_3ds", "" );

                    $sageresult['result']   = 'success';
                    $sageresult['redirect'] = wc_get_checkout_url();

                    // Soft Decline
                    if( isset( $sageresult['DeclineCode'] ) && in_array( $sageresult['DeclineCode'], array('65','1A') ) ) {
                        update_post_meta( $order_id, '_opayo_soft_decline', $sageresult['DeclineCode'] );
                    }

                    return $sageresult;

                break;

                case 'REJECTED':

                    // Add Order Note
                    $this->add_order_note( __('Payment failed, there was a problem with 3D Secure or Address Verification', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

                    // Create message for customer
                    $this->sagepay_message( (__('Payment error.<br />There was a problem when verifying your card, please check your details and try again.<br />Your card has not been charged.', 'woocommerce-gateway-sagepay-form') ) , 'error', $order_id );

                    // Clean up Order Meta
                    delete_post_meta( $order_id, '_sage_3dsecure' );

                    // Clear session variables
                    WC()->session->set( "sage_3ds", "" );
                
                    $sageresult['result']   = 'success';
                    $sageresult['redirect'] = wc_get_checkout_url();

                    return $sageresult;

                break;

                default :

                    // Should never get here. 
                    WC_Sagepay_Common_Functions::sagepay_debug( $sageresult, 'sagepay_order_cannot_be_paid', 'Logging "default" Order : ' . $order_id, FALSE );

                    // Temporary order note
                    $sageresult['Order Status: '] = $order->get_status();
                    $this->add_order_note( __('Processing response from Opayo failed', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

                    if ( $this->opayo_needs_payment( $order ) ) {                   
                        // JavaScript Redirect
                        $this->opayo_redirect( $this->get_return_url( $order ) );
                        // Stops here.
                    } else {
                        // JavaScript Redirect
                        $this->opayo_redirect( $order->get_checkout_order_received_url() );
                        // Stops here.
                    }
            }

        }

        /**
         * [send_paypal_complete description]
         * @param  [type] $sageresult [description]
         * @param  [type] $order      [description]
         * @return [type]             [description]
         */
        function send_paypal_complete( $sageresult, $order ) {

            // make your query.
            $data    = array(
                "VPSProtocol"       =>  $this->vpsprotocol,
                "TxType"            =>  'COMPLETE',
                "VPSTxId"           =>  $sageresult['VPSTxId'],
                "Amount"            =>  $order->get_total(),
                "Accept"            =>  'YES'
            );

            $result = $this->sagepay_post( $data, $this->paypalcompletion );

            // Add Order Note
            $this->add_order_note( __('PayPal Transaction Complete', 'woocommerce-gateway-sagepay-form'), $result, $order );

            $response = new Sagepay_Direct_Response( $result , $order );
            return $response->process();
            
        }

        /**
         * [get_payment_method_title description]
         * @param  [type] $order_id [description]
         * @return [type]           [description]
         */
        function get_payment_method_title( $order_id ) {

            $payment_method_title = get_post_meta( $order_id, '_payment_method_title', TRUE );

            $payment_method_title = apply_filters( 'woocommerce_sagepay_direct_payment_method_title', $payment_method_title, $order_id );

            return $payment_method_title;

        }

        /**
         * [opayo_payment_complete description]
         * @param  [type] $order      [description]
         * @param  [type] $sagereuslt [description]
         * @return [type]             [description]
         */
        function opayo_payment_complete( $order, $sageresult ) {

            $order_id = $order->get_id();

            // Update transaction type
            $_SagePayTransactionType = get_post_meta( $order_id, '_SagePayTxType', TRUE );

            // Clean VPSTxId
            $clean_VPSTxId = isset( $sageresult['VPSTxId'] ) ? str_replace( array('{','}'),'',$sageresult['VPSTxId'] ) : NULL;

            // Set the transaction ID
            if( !is_null( $clean_VPSTxId ) ) {
                update_post_meta( $order_id, '_transaction_id', $clean_VPSTxId );
            }

            if ( class_exists('WC_Pre_Orders') && WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) && WC_Pre_Orders_Order::order_will_be_charged_upon_release( $order_id ) ) {
                // mark order as pre-ordered / reduce order stock
                WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );
            } elseif ( class_exists( 'WC_Subscriptions' ) && wcs_is_subscription( $order_id ) ) {
                // Update subscription 
                $order->payment_complete();

            } elseif ( isset( $sageresult['FraudResponse'] ) && ( $sageresult['FraudResponse'] === 'DENY' || $sageresult['FraudResponse'] === 'CHALLENGE' ) ) {

                // Mark for fraud screening
                $order->update_status( 'fraud-screen', _x( 'Sage Fraud Response ', 'woocommerce-gateway-sagepay-form' ) . $sageresult['FraudResponse'] . _x( '. Login to MySagePay and check this order before shipping.', 'woocommerce-gateway-sagepay-form' ) );
                
            } elseif ( $sageresult['Status'] === 'AUTHENTICATED' || $sageresult['Status'] === 'REGISTERED' || ( isset($_SagePayTransactionType) && $_SagePayTransactionType == 'DEFERRED' ) ) {

                $order->payment_complete();

                $order->update_status( 'authorised', _x( 'Payment authorised, you will need to capture this payment before shipping. Use the "Capture Authorised Payment" option in the "Order Actions" dropdown.<br /><br />', 'woocommerce-gateway-sagepay-form' ) );

            } else {
                $order->payment_complete();
            }

            // Empty Cart
            if( is_callable( 'wc_empty_cart' ) ) {
                wc_empty_cart();
            }

        }

    } // End class
