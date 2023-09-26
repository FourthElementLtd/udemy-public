<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

    /**
     * Sagepay_Direct_Subcription_Renewals class.
     *
     * @extends WC_Gateway_Sagepay_Direct
     * Adds subscriptions support support.
     */
    class Sagepay_Direct_Subcription_Renewals extends WC_Gateway_Sagepay_Direct {

        private $amount_to_charge;
        private $order;

        public function __construct( $amount_to_charge, $order ) {

            parent::__construct();

            $this->amount_to_charge = $amount_to_charge;
            $this->order            = $order;

        }

        /**
         * process scheduled subscription payment
         */
        function process_scheduled_payment() {

            $order = $this->order;

            if( !is_object( $order ) ) {
                $order = new WC_Order( $order );
            }

            // Let's log some Action Scheduler things
            // $this->log_action_scheduler( $_REQUEST, $order );

            // If the order is Processing or Completed we can bail.
            if ( !$order->needs_payment() ) {
                $order->add_order_note( __('Payment attempted for an order that has already been paid for. The process ended before an attempt was made.', 'woocommerce-gateway-sagepay-form') );
                return;
            }

            $order_id = $order->get_id();

            // Get parent order ID
            $subscriptions      = wcs_get_subscriptions_for_renewal_order( $order_id );

            if( $subscriptions && is_array( $subscriptions ) ) {
                
                foreach( $subscriptions as $subscription ) {

                    $parent_order      = $subscription->get_parent();

                    if( is_object( $parent_order ) ) {
                        $parent_order_id   = $parent_order->get_id();
                        $subscription_id   = $subscription->get_id();
                    } else {
                        return;
                    }

                }

                // Get the id of the subscription
                $subscription_id = key( $subscriptions );

                // Get the last successful renewal order id for this subscription 
                $previous_renewal_id = $this->get_previous_renewal_id( $subscription_id, $parent_order_id );

                // Set $previous_renewal_id = $parent_order_id if the last order does not have a '_RelatedVPSTxId'
                if( !get_post_meta( $previous_renewal_id, '_RelatedVPSTxId', true ) || get_post_meta( $previous_renewal_id, '_RelatedVPSTxId', true ) == '' ) {
                    $previous_renewal_id = $parent_order_id;
                }

                $VendorTxCode   = 'Renewal-' . $subscription_id . '-' . time();

                // SAGE Line 50 Fix
                $VendorTxCode   = str_replace( 'order_', '', $VendorTxCode );

                // Was this a free trial or £0 subscription payment?
                $free_trial = get_post_meta( $subscription_id, '_opayo_free_trial', TRUE );

                // Validate the free trial transaction details to make sure all of the required fields are available
                $validate_free_trial_transaction_details = $this->check_free_trial_transaction_details( $free_trial );

                // Get transaction details from previous orders
                $related_transaction_details = $this->get_related_transaction_details( $subscription_id, $previous_renewal_id, $parent_order_id );

                // Validate the previous transaction details to make sure all of the required fields are available
                $validate_related_transaction_details = $this->check_related_transaction_details( $related_transaction_details );

                // Get the token if available
                $token = NULL;
                $token = $this->get_subscription_token( $previous_renewal_id, $subscription_id, $parent_order_id, '_SagePayDirectToken' );

                if( $validate_free_trial_transaction_details ) {

                    $data    = array(
                        "VPSProtocol"           => $free_trial['VPSProtocol'],
                        "TxType"                => "AUTHORISE",
                        "Vendor"                => $this->get_vendor(),
                        "VendorTxCode"          => $VendorTxCode,
                        "Amount"                => $this->amount_to_charge,
                        "Currency"              => get_post_meta( $subscription_id, '_order_currency', true ),
                        "Description"           => 'Payment for Subscription ' . $order_id,
                        'RelatedVPSTxId'        => str_replace( array( '{', '}' ), '', $free_trial['VPSTxId'] ),
                        'RelatedVendorTxCode'   => $free_trial['VendorTxCode'],
                        'RelatedSecurityKey'    => $free_trial['SecurityKey'],
                        'InitiatedType'         => 'MIT'
                    );

                    // Transaction URL
                    $post_url = $this->authoriseURL;

                } elseif( $validate_related_transaction_details ) {

                    $data  = array(
                                'VPSProtocol'           => $this->get_vpsprotocol(),
                                'TxType'                => 'REPEAT',
                                'Vendor'                => urlencode( $this->get_vendor() ),
                                'VendorTxCode'          => $VendorTxCode,
                                'Amount'                => urlencode( $this->amount_to_charge ),
                                'Currency'              => $this->get_order_meta( $subscription_id, $previous_renewal_id, $parent_order_id, '_order_currency' ),
                                'Description'           => 'Repeat payment for subscription ' . $subscription_id,
                                'RelatedVPSTxId'        => $related_transaction_details["RelatedVPSTxId"],
                                'RelatedVendorTxCode'   => $related_transaction_details["RelatedVendorTxCode"],
                                'RelatedSecurityKey'    => $related_transaction_details["RelatedSecurityKey"],
                                'RelatedTxAuthNo'       => $related_transaction_details["RelatedTxAuthNo"],
                                'DeliverySurname'       => $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverysurname', $order->get_shipping_last_name(), $order ), 20 ),
                                'DeliveryFirstnames'    => $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryfirstname', $order->get_shipping_first_name(), $order ), 20 ),
                                'DeliveryAddress1'      => $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryaddress1', $order->get_shipping_address_1(), $order ), 50 ),
                                'DeliveryAddress2'      => $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryaddress2', $order->get_shipping_address_2(), $order ), 50 ),
                                'DeliveryCity'          => $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverycity', $order->get_shipping_city(), $order ), 40 ),
                                'DeliveryPostCode'      => $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverypostcode', $order->get_shipping_postcode(), $order ), 10 ),
                                'DeliveryCountry'       => apply_filters( 'woocommerce_sagepay_direct_deliverycountry', $order->get_shipping_country(), $order ),
                                'DeliveryState'         => $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverystate', WC_Sagepay_Common_Functions::sagepay_state( $order->get_shipping_country(), $order->get_shipping_state()  ), $order ), 2 ),
                                'DeliveryPhone'         => $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryphone', $order->get_billing_phone(), $order ), 10 ),
                                'COFUsage'              => 'SUBSEQUENT',
                                'InitiatedType'         => 'MIT',
                                'MITType'               => 'UNSCHEDULED',
                                'SchemeTraceID'         => $this->get_SchemeTraceID( $previous_renewal_id, $subscription_id, '_SchemeTraceID' )
                            );

                    // Transaction URL
                    $post_url = $this->repeatURL;

                } elseif( $token ) {

                    // Update renewal order with token
                    update_post_meta( $order_id, '_SagePayDirectToken', $token );

                    $sageresult     = $this->get_order_meta( $subscription_id, $previous_renewal_id, $subscription_id, '_sageresult' );

                    // make your query.
                    $data = array(
                        "Token"             =>  $token,
                        "StoreToken"        =>  "1",
                        "ApplyAVSCV2"       =>  "2",
                        "Apply3DSecure"     =>  "2",
                        "VPSProtocol"       =>  $this->get_vpsprotocol(),
                        "TxType"            =>  "PAYMENT",
                        "Vendor"            =>  $this->get_vendor(),
                        "VendorTxCode"      =>  $VendorTxCode,
                        "Amount"            =>  urlencode( $this->amount_to_charge ),
                        "Currency"          =>  $this->get_order_meta( $subscription_id, $previous_renewal_id, $parent_order_id, '_order_currency' ),
                        "Description"       =>   __( 'Repeat payment for subscription', 'woocommerce-gateway-sagepay-form' ) . ' ' . str_replace( '#' , '' , $subscription_id ),                        
                        "BillingSurname"    =>  $this->limit_length( $order->get_billing_last_name(), 20 ),
                        "BillingFirstnames" =>  $this->limit_length( $order->get_billing_first_name(), 20 ),
                        "BillingAddress1"   =>  $this->limit_length( $order->get_billing_address_1(), 50 ),
                        "BillingAddress2"   =>  $this->limit_length( $order->get_billing_address_2(), 50 ),
                        "BillingCity"       =>  $this->limit_length( $order->get_billing_city(), 40 ),
                        "BillingPostCode"   =>  $this->limit_length( $this->billing_postcode( $order->get_billing_postcode() ), 10 ),
                        "BillingCountry"    =>  $order->get_billing_country(),
                        "BillingState"      =>  $this->limit_length( WC_Sagepay_Common_Functions::sagepay_state( $order->get_billing_country(), $order->get_billing_state()  ), 20 ),
                        "BillingPhone"      =>  $this->limit_length( $order->get_billing_phone(), 20 ),
                        "DeliverySurname"   =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverysurname', $order->get_shipping_last_name(), $order ), 20 ),
                        "DeliveryFirstnames"=>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryfirstname', $order->get_shipping_first_name(), $order ), 20 ),
                        "DeliveryAddress1"  =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryaddress1', $order->get_shipping_address_1(), $order ), 50 ),
                        "DeliveryAddress2"  =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryaddress2', $order->get_shipping_address_2(), $order ), 50 ),
                        "DeliveryCity"      =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverycity', $order->get_shipping_city(), $order ), 40 ),
                        "DeliveryPostCode"  =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverypostcode', $order->get_shipping_postcode(), $order ), 10 ),
                        "DeliveryCountry"   =>  apply_filters( 'woocommerce_sagepay_direct_deliverycountry', $order->get_shipping_country(), $order ),
                        "DeliveryState"     =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverystate', WC_Sagepay_Common_Functions::sagepay_state( $order->get_shipping_country(), $order->get_shipping_state()  ), $order ), 20 ),
                        "DeliveryPhone"     =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryphone', $order->get_billing_phone(), $order ), 20 ),
                        "CustomerEMail"     =>  $order->get_billing_email(),
                        "AccountType"       =>  $this->get_accounttype(),
                        "BillingAgreement"  =>  $this->get_billingagreement(),
                        "ReferrerID"        =>  $this->get_referrerid(),
                        "Website"           =>  site_url(),
                        "COFUsage"          => 'SUBSEQUENT',
                        "InitiatedType"     => 'MIT',
                        "MITType"           => 'UNSCHEDULED',
                        "ApplyAVSCV2"       => "0"
                    );

                    unset( $data["Apply3DSecure"] );

                    if( $this->amount_to_charge <= 25 ) {
                        $data["Apply3DSecure"]      =  "2";
                        $data["ThreeDSExemptionIndicator"] = "01";
                    }

                    // Allow sites to bypass AVS/CV2 checks for renewals
                    if( apply_filters( 'woocommerce_sagepay_direct_applyavscvv_for_renewals', false ) ) {
                        $data["ApplyAVSCV2"]      =  "2";
                    }

                    // Transaction URL
                    $post_url = $this->purchaseURL;

                }

                // Add GirftAid to renewal if parent order used GiftAid
                if( $this->get_giftaid() === 'yes' ) {
                    $GiftAidPayment = $this->get_order_meta( $previous_renewal_id, $parent_order_id, '_GiftAidPayment' );
                    if( !empty( $GiftAidPayment ) ) {
                        $data['GiftAidPayment'] = $GiftAidPayment;
                    }
                }

                // Add the basket
                $basket = WC_Sagepay_Common_Functions::get_basket( $this->get_basketoption(), $order_id );
                if ( $basket != NULL ) {
                    if ( $this->get_basketoption() == 1 ) {
                        $data["Basket"] = $basket;
                    } elseif ( $this->get_basketoption() == 2 ) {
                        $data["BasketXML"] = $basket;
                    }
                }
/*
                // Cet CustomerXML object
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
*/
                // Verify Data
                $data = $this->verify_opayo_data( $data, $order );

                // Send the request to sage for processing
                $res = $this->sagepay_post( $data, $post_url );

                // Check $res for API errors
                if( is_wp_error( $res ) ) {
                    $result = $res->get_error_message();
                    // Process renewal failure
                    $this->process_renewal_failure( $result, $order, $order_id, $data, $previous_renewal_id, $subscription_id, $parent_order_id );
                } else {
                    $result = $this->sageresponse( $res['body'] );

                    // Add VendorTxCode to result
                    $result['VendorTxCode'] = $VendorTxCode;

                    // Delete any free trial data even if the transaction failed.
                    delete_post_meta( $subscription_id, '_opayo_free_trial' );

                    // Testing
                    // $result = apply_filters( 'woocommerce_opayo_direct_testing_process_scheduled_payment_result', $result, $order );

                    // Process the result
                    if ( 'OK' != $result['Status'] ) {
                        // Process renewal failure
                        $this->process_renewal_failure( $result, $order, $order_id, $data, $previous_renewal_id, $subscription_id, $parent_order_id );
                    } else {
                        // Process renewal success
                        $this->process_renewal_success( $result, $order, $order_id, $parent_order_id, $VendorTxCode, $subscription_id );
                    }

                }

            }

        } // process scheduled subscription payment

        /**
         * [verify_opayo_data description]
         * @param  [type] $data  [description]
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        function verify_opayo_data( $data, $order ) {

            $note_array = array(
                "VPSProtocol"   => isset( $data["VPSProtocol"] ) ? $data["VPSProtocol"] : '',
                "TxType"        => isset( $data["TxType"] ) ?  $data["TxType"] : ''
            );

            // always fails
            $verified   = false;
            $ordernote  = __('Renewal data verification failed', 'woocommerce-gateway-sagepay-form') . '<br />' . print_r( $note_array, TRUE );

            if( $data["VPSProtocol"] === '4.00' ) {
                $verified = true;
                $ordernote  = __('Renewal data verified', 'woocommerce-gateway-sagepay-form');
            }

            $order->add_order_note( $ordernote );

            // Remove empty values and return
            return array_filter( $data );
        }
 
        /**
         * [get_previous_renewal_id description]
         * @param  [type] $subscription_id [description]
         * @return [type]                  [description]
         */
        function get_previous_renewal_id( $subscription_id, $parent_order_id ) {
            global $wpdb;

            /**
             * Check for previous renewals for this subscription.
             */         
            $previous_renewals = $wpdb->get_results(  $wpdb->prepare( "
                                        SELECT * FROM {$wpdb->postmeta} pm
                                        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                                        WHERE pm.meta_key = '%s'
                                        AND pm.meta_value = '%s'
                                        AND p.post_status IN ( '%s','%s' )
                                        ORDER BY pm.post_id DESC
                                        LIMIT 1
                                    ", '_subscription_renewal', $subscription_id, 'wc-processing', 'wc-completed' ) 
            );

            /**
             * $previous_renewal_id is used to get the Sage transaction information from the last successful renewal.
             * 
             * Sage archives orders after 2 years, if we use the transaction information from the first order then 
             * orders will fail once the first order is archived.
             */
            if( isset( $previous_renewals[0]->post_id ) && '' != $previous_renewals[0]->post_id ) {
                $previous_renewal_id = $previous_renewals[0]->post_id;
            } else {
                $previous_renewal_id = $parent_order_id;
            }

            return $previous_renewal_id;

        }

        /**
         * [get_related_transaction_details description]
         * 
         * This method will get the Retlated information from the Subscription
         * The first order and every renewal will update the subscription with the releated details ready for the next renewal
         * 
         * If there is a payment method change then the related details will be removed from the subscription and the new token will be added to the subscription meta
         *
         * If the related details are not complete in the subscription then the token from the subscription meta is checked. 
         * If the stored token does not match a previously used token then it assumed to be a new token from a payment method change.
         * This token is then used to process the renewal
         *
         * If the related meta from the subscription is not complete and the stored token is not new then a last renewal order is checked for the related details
         *
         * If the related details from the last renewal are unavailabe or incomplete then check the parent order (first order placed as part of the subscription)
         *
         * Finally return NULL - this means a token will be used if available. 
         * This is a fallback : if there is an available token then is will be used and the related details will be added to the subcription ready for the next renewal.
         * 
         * @param  [type] $previous_renewal_id [description]
         * @param  [type] $parent_order_id     [description]
         * @return [type]                      [description]
         */
        function get_related_transaction_details( $subscription_id, $previous_renewal_id, $parent_order_id ) {

            // Use the Subscription ID to get the related values
            // Previous order data will be stored in the subscription, 
            // every renewal will update the subscription with the Related details required for the next renewal
            $sb_related = array();

            $sb_related["RelatedVPSTxId"]      = get_post_meta( $subscription_id, '_RelatedVPSTxId', TRUE );
            $sb_related["RelatedVendorTxCode"] = get_post_meta( $subscription_id, '_RelatedVendorTxCode', TRUE );
            $sb_related["RelatedSecurityKey"]  = get_post_meta( $subscription_id, '_RelatedSecurityKey', TRUE );
            $sb_related["RelatedTxAuthNo"]     = get_post_meta( $subscription_id, '_RelatedTxAuthNo', TRUE );

            // Remove empty values
            $sb_related = array_filter( $sb_related );

            if( $this->check_related_transaction_details ( $sb_related ) ) {
                if ( $this->get_debug() == true || $this->get_status() != 'live' ) {
                    $logging_data = array();
                    $logging_data['SubscriptionID']     = $subscription_id;
                    $logging_data['PreviousRenewalID']  = $previous_renewal_id;
                    $logging_data['ParentOrderID']      = $parent_order_id;
                    $logging_data = $logging_data + $sb_related;
                    WC_Sagepay_Common_Functions::sagepay_debug( $logging_data, 'renewal_' . $this->id, __('Renewal data from subscription used for renewal', 'woocommerce-gateway-sagepay-form'), TRUE );
                }
                return $sb_related;
            }

            // The Subscription does not have the necessary transaction details, check if there is a token available and compare it to the token usd in the last renewal
            // Only check if there is a token in the subscription, this will be the latest token used or set during a payment method change.
            // If there is a token and it does not match the token used previously then return NULL, this will force the token to be used in the process_scheduled_payment method
            // because get_related_transaction_details will be NULL
            $token          = $this->get_subscription_token( $previous_renewal_id, $subscription_id, $parent_order_id, '_SagePayDirectToken' );
            $compare_token  = $this->compare_token( $previous_renewal_id, $subscription_id, '_SagePayDirectToken' );

            if( $token && $compare_token ) {

                if ( $this->get_debug() == true || $this->get_status() != 'live' ) {
                    $logging_data = array();
                    $logging_data['SubscriptionID']     = $subscription_id;
                    $logging_data['PreviousRenewalID']  = $previous_renewal_id;
                    $logging_data['ParentOrderID']      = $parent_order_id;
                    $logging_data['Token'] = $token;
                    WC_Sagepay_Common_Functions::sagepay_debug( $logging_data, 'renewal_' . $this->id, __('Token used for renewal', 'woocommerce-gateway-sagepay-form'), TRUE );
                }

                return NULL;
            }

            // Use the Previous Renewal ID to get the related values, Subscription does not have enough stored details
            // Fallback if the renewal data is not availbale in the subscription. 
            $pr_related = array();

            $pr_related["RelatedVPSTxId"]      = get_post_meta( $previous_renewal_id, '_RelatedVPSTxId', TRUE );
            $pr_related["RelatedVendorTxCode"] = get_post_meta( $previous_renewal_id, '_RelatedVendorTxCode', TRUE );
            $pr_related["RelatedSecurityKey"]  = get_post_meta( $previous_renewal_id, '_RelatedSecurityKey', TRUE );
            $pr_related["RelatedTxAuthNo"]     = get_post_meta( $previous_renewal_id, '_RelatedTxAuthNo', TRUE );

            // Remove empty values
            $pr_related = array_filter( $pr_related );

            if( $this->check_related_transaction_details ( $pr_related ) ) {
                if ( $this->get_debug() == true || $this->get_status() != 'live' ) {
                    $logging_data = array();
                    $logging_data['SubscriptionID']     = $subscription_id;
                    $logging_data['PreviousRenewalID']  = $previous_renewal_id;
                    $logging_data['ParentOrderID']      = $parent_order_id;
                    $logging_data = $logging_data + $pr_related;

                    WC_Sagepay_Common_Functions::sagepay_debug( $pr_related, 'renewal_' . $this->id, __('Renewal data from previous renewal used for renewal', 'woocommerce-gateway-sagepay-form'), TRUE );
                }
                return $pr_related;
            }

            // Use the Parent Order ID to get the related values, Previous Renewal does not have enough stored details
            // This is a last resort, ideally this data is not used. Could be a fallback from historical subscriptions
            $po_related = array();

            $po_related["RelatedVPSTxId"]      = get_post_meta( $parent_order_id, '_RelatedVPSTxId', TRUE );
            $po_related["RelatedVendorTxCode"] = get_post_meta( $parent_order_id, '_RelatedVendorTxCode', TRUE );
            $po_related["RelatedSecurityKey"]  = get_post_meta( $parent_order_id, '_RelatedSecurityKey', TRUE );
            $po_related["RelatedTxAuthNo"]     = get_post_meta( $parent_order_id, '_RelatedTxAuthNo', TRUE );

            // Remove empty values
            $po_related = array_filter( $po_related );

            if( $this->check_related_transaction_details ( $po_related ) ) {
                if ( $this->get_debug() == true || $this->get_status() != 'live' ) {
                    $logging_data = array();
                    $logging_data['SubscriptionID']     = $subscription_id;
                    $logging_data['PreviousRenewalID']  = $previous_renewal_id;
                    $logging_data['ParentOrderID']      = $parent_order_id;
                    $logging_data = $logging_data + $po_related;

                    WC_Sagepay_Common_Functions::sagepay_debug( $po_related, 'renewal_' . $this->id, __('Renewal data from parent order used for renewal', 'woocommerce-gateway-sagepay-form'), TRUE );
                }
                return $po_related;
            }

            // Return something
            return NULL;

        }

        /**
         * [check_related_transaction_details description]
         * @param  [type] $related [description]
         * @return [type]          [description]
         */
        function check_related_transaction_details ( $related ) {

            if( !is_array( $related ) ) {
                return false;
            }

            if( !isset( $related["RelatedVPSTxId"] ) || is_null( $related["RelatedVPSTxId"] ) || $related["RelatedVPSTxId"] == '' ) {
                return false;
            }

            if( !isset( $related["RelatedVendorTxCode"] ) || is_null( $related["RelatedVendorTxCode"] ) || $related["RelatedVendorTxCode"] == '' ) {
                return false;
            }

            if( !isset( $related["RelatedSecurityKey"] ) || is_null( $related["RelatedSecurityKey"] ) || $related["RelatedSecurityKey"] == '' ) {
                return false;
            }

            if( !isset( $related["RelatedTxAuthNo"] ) || is_null( $related["RelatedTxAuthNo"] ) || $related["RelatedTxAuthNo"] == '' ) {
                return false;
            }

            return true;

        }

        /**
         * [check_free_trial_transaction_details description]
         * @param  [type] $free_trial [description]
         * @return [type]             [description]
         */
        function check_free_trial_transaction_details( $free_trial ) {

            if ( $this->get_debug() == true || $this->get_status() != 'live' ) {
                WC_Sagepay_Common_Functions::sagepay_debug( $free_trial, 'renewal_' . $this->id, __('Free Trial renewal data', 'woocommerce-gateway-sagepay-form'), TRUE );
            }

            if( !is_array( $free_trial ) ) {
               return false;
            }

            if( !isset( $free_trial["TxType"] ) || empty( $free_trial["TxType"] ) || !in_array( $free_trial['TxType'], array( 'AUTHENTICATE', 'AUTHENTICATED' ) ) ) {
                return false;
            }

            if( !isset( $free_trial["VendorTxCode"] ) || is_null( $free_trial["VendorTxCode"] ) || $free_trial["VendorTxCode"] == '' ) {
                return false;
            }

            if( !isset( $free_trial["VPSTxId"] ) || is_null( $free_trial["VPSTxId"] ) || $free_trial["VPSTxId"] == '' ) {
               return false;
            }

            if( !isset( $free_trial["SecurityKey"] ) || is_null( $free_trial["SecurityKey"] ) || $free_trial["SecurityKey"] == '' ) {
               return false;
            }

            return true;

        }

        /**
         * [process_renewal_failure description]
         * @param  [type] $result   [description]
         * @param  [type] $order    [description]
         * @param  [type] $order_id [description]
         * @param  [type] $data     [description]
         * @return [type]           [description]
         */
        function process_renewal_failure( $result, $order, $order_id, $data, $previous_renewal_id, $subscription_id, $parent_order_id ) {

            $content = 'There was a problem renewing this payment for order ' . $order_id . '. The Transaction ID is ' . isset( $data['RelatedVPSTxId'] ) ? $data['RelatedVPSTxId'] : '' . '. The API Request is <pre>' . 
                print_r( $data, TRUE ) . '</pre>. SagePay returned the error <pre>' . 
                print_r( $result['StatusDetail'], TRUE ) . '</pre> The full returned array is <pre>' . 
                print_r( $result, TRUE ) . '</pre>. ';

            $ordernote = '';
            foreach ( $result as $key => $value ) {
                $ordernote .= $key . ' : ' . $value . "\r\n";
            }

            $order->add_order_note( __('Payment failed', 'woocommerce-gateway-sagepay-form') . '<br />' . $ordernote );

            update_post_meta( $order_id, '_sageresult', $result );

            /**
             * Debugging
             */
            if ( !$this->get_debug() == true ) {
                WC_Sagepay_Common_Functions::sagepay_debug( $data, $this->id, __('Failed Renewal - Opayo Request : ', 'woocommerce-gateway-sagepay-form'), TRUE );
                WC_Sagepay_Common_Functions::sagepay_debug( $result, $this->id, __('Failed Renewal - Opayo Response : ', 'woocommerce-gateway-sagepay-form'), TRUE );
            }

            // Payment has failed. Check if we can try again
            $reattempt = get_post_meta( $subscription_id, '_opayo_subscription_reattempt', TRUE );

            $opayo_status_detail_code = isset( $result['StatusDetail'] ) ? substr( $result['StatusDetail'], 0, 4 ) : NULL;

            // Get the token from the first order
            $token = get_post_meta( $parent_order_id, '_SagePayDirectToken', TRUE );

            if( !empty( $token ) && empty( $reattempt ) && isset( $opayo_status_detail_code ) && in_array( $opayo_status_detail_code, array( '3370' ) ) ) {

                $order->add_order_note( __('Payment reattempted with token', 'woocommerce-gateway-sagepay-form') );

                // Reattempt Payment ONCE!

                // Update subscription so that the reattempt is not attempted again
                update_post_meta( $subscription_id, '_opayo_subscription_reattempt', TRUE );

                $data = $this->get_token_order_data( $token, $order, $subscription_id, $previous_renewal_id, $parent_order_id );

                $data["COFUsage"]               = 'SUBSEQUENT';
                $data["InitiatedType"]          = 'MIT';
                $data["MITType"]                = 'UNSCHEDULED';

                $data["ApplyAVSCV2"]            =  "0";
                unset( $data["Apply3DSecure"] );

                // Allow sites to bypass AVS/CV2 checks for renewals
                if( apply_filters( 'woocommerce_sagepay_direct_applyavscvv_for_renewals', false ) ) {
                    $data["ApplyAVSCV2"]      =  "2";
                }

                // Send the request to Opayo for processing
                $res = $this->sagepay_post( $data, $this->purchaseURL );

                // Order Note 
                $order->add_order_note( __('Payment reattempted using token', 'woocommerce-gateway-sagepay-form') );

                // Check $res for API errors
                if( !is_wp_error( $res ) ) {

                    $result = $this->sageresponse( $res['body'] );

                    // Add VendorTxCode to result
                    $result['VendorTxCode'] = $data['VendorTxCode'];

                    // Process the result
                    if ( 'OK' == $result['Status'] ) {
                        // Process renewal success
                        $this->process_renewal_success( $result, $order, $order_id, $parent_order_id, $VendorTxCode, $subscription_id );

                        // Stop here
                        exit();
                    } 

                }

            } elseif( empty( $token ) && empty( $reattempt ) && isset( $opayo_status_detail_code ) && in_array( $opayo_status_detail_code, array( '3370' ) ) ) {
                // Attempt to repeat using Protocol 3.00 and the last order details
                $data     = 'VPSProtocol=' . urlencode( '3.00' );
                $data    .= '&TxType=REPEAT';
                $data    .= '&Vendor=' . urlencode( $this->get_vendor() );
                $data    .= '&VendorTxCode=' . 'Renewal-' . $subscription_id . '-' . time();
                $data    .= '&Amount=' . urlencode( $this->amount_to_charge );
                $data    .= '&Currency=' . get_post_meta( $previous_renewal_id, '_order_currency', true );
                $data    .= '&Description=Repeat payment for order ' . $subscription_id;
                $data    .= '&RelatedVPSTxId=' . get_post_meta( $previous_renewal_id, '_RelatedVPSTxId', true );
                $data    .= '&RelatedVendorTxCode=' . get_post_meta( $previous_renewal_id, '_RelatedVendorTxCode', true );
                $data    .= '&RelatedSecurityKey=' . get_post_meta( $previous_renewal_id, '_RelatedSecurityKey', true );
                $data    .= '&RelatedTxAuthNo=' . get_post_meta( $previous_renewal_id, '_RelatedTxAuthNo', true );

                $res = $this->sagepay_post( $data, $this->repeatURL );

                // Order Note 
                $order->add_order_note( __('Payment reattempted using Protocol 3.00', 'woocommerce-gateway-sagepay-form') );

                // Check $res for API errors
                if( !is_wp_error( $res ) ) {

                    $result = $this->sageresponse( $res['body'] );

                    // Add VendorTxCode to result
                    $result['VendorTxCode'] = $data['VendorTxCode'];

                    // Process the result
                    if ( 'OK' == $result['Status'] ) {
                        // Process renewal success
                        $this->process_renewal_success( $result, $order, $order_id, $parent_order_id, $VendorTxCode, $subscription_id );

                        // Stop here
                        exit();
                    } 

                }

            }

            $order->update_status('failed');

            // Stop sending this email unless user wants it, bit pointless TBH
            $send_failure_email = apply_filters( 'woocommerce-opayo-direct-renewal-failure-send-email', false );

            if( $send_failure_email && isset( $result['Status'] ) ) {
               wp_mail( $this->get_notification() ,'Opayo Renewal Error ' . $result['Status'] . ' ' . time(), $content ); 
            }

            // WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, NULL );
            // Mark the Subscription as failed.
            $subscriptions = wcs_get_subscriptions_for_order( $order );
            if ( ! empty( $subscriptions ) ) {

                foreach ( $subscriptions as $subscription ) {
                    $subscription->payment_failed();
                }
                
            }

        }

        /**
         * [process_renewal_success description]
         * @param  [type] $result          [description]
         * @param  [type] $order           [description]
         * @param  [type] $order_id        [description]
         * @param  [type] $parent_order_id [description]
         * @param  [type] $VendorTxCode    [description]
         * @return [type]                  [description]
         */
        function process_renewal_success( $result, $order, $order_id, $parent_order_id, $VendorTxCode, $subscription_id ) {

            $subscriptions = wcs_get_subscriptions_for_order( $order );
            if ( ! empty( $subscriptions ) ) {
                foreach ( $subscriptions as $subscription ) {
                    $subscription->payment_complete();
                }
                // do_action( 'processed_subscription_payments_for_order', $order );
            }

            $successful_ordernote = '';
            foreach ( $result as $key => $value ) {
                $successful_ordernote .= $key . ' : ' . $value . "\r\n";
            }

            $order->add_order_note( __('Payment completed', 'woocommerce-gateway-sagepay-form') . '<br />' . $successful_ordernote );

            // Update the order with the full Sage result
            $result['VendorTxCode'] = $VendorTxCode;
            $this->set_order_meta( $order_id, NULL, $subscription_id, $result );

            // Delete any reattempt meta
            delete_post_meta( $subscription_id, '_opayo_subscription_reattempt' );
            
            // WC()->cart->empty_cart();
            $order->payment_complete( str_replace( array('{','}'),'',$result['VPSTxId'] ) );

            do_action( 'woocommerce_sagepay_direct_payment_complete', $result, $order );

        }

        /**
         * [deferred_order description]
         * @param  [type] $order_id [description]
         * @return [type]           [description]
         */
        function deferred_order( $order_id ) {

            $order          = wc_get_order( $order_id );
            $transaction    = get_post_meta( $order_id, '_sageresult', $order_id );

            // Fix for missing '_VendorTxCode'
            $VendorTxCode           = get_post_meta( $order_id, '_VendorTxCode', true );
            $_RelatedVendorTxCode   = get_post_meta( $order_id, '_RelatedVendorTxCode', true );

            if ( !isset($VendorTxCode) || $VendorTxCode == '' ) {
                $VendorTxCode = $_RelatedVendorTxCode;
            }

            $data    = array(
                "VPSProtocol"           => $transaction['VPSProtocol'],
                "TxType"                => "RELEASE",
                "Vendor"                => $this->get_vendor(),
                "VendorTxCode"          => $VendorTxCode,
                "VPSTxId"               => str_replace( array( '{', '}' ), '', get_post_meta( $order_id, '_VPSTxId', true ) ),
                "SecurityKey"           => get_post_meta( $order_id, '_SecurityKey', true ),
                "TxAuthNo"              => get_post_meta( $order_id, '_TxAuthNo', true ),
                "ReleaseAmount"         => $order_total,
            );

            // Send the request to sage for processing
            $res = $this->sagepay_post( $data, $this->releaseURL );

            // Check $res for API errors
            if( is_wp_error( $res ) ) {
                $result = $res->get_error_message();

                // Add VendorTxCode to result
                $result['VendorTxCode'] = $VendorTxCode;
            } else {
                $result = $this->sageresponse( $res['body'] );

                // Add VendorTxCode to result
                $result['VendorTxCode'] = $VendorTxCode;
            }

            return $result;

        }

        /**
         * [set_order_meta description]
         * @param [type] $order_id        [description]
         * @param [type] $parent_order_id [description]
         * @param [type] $result          [description]
         */
        function set_order_meta( $order_id, $parent_order_id, $subscription_id, $result ) {

            // Update the new order
            update_post_meta( $order_id, '_sageresult' , $result );

            // Add all of the info from sage as 
            if( is_array($result) ) {

                if( isset( $result['VPSTxId'] ) ) {
                    $result['VPSTxId'] = str_replace( array('{','}'),'',$result['VPSTxId'] );
                }

                if( isset( $result['Token'] ) ) {
                    $result['SagePayDirectToken'] = $result['Token'];
                    unset( $result['Token'] );
                }

                $result['RelatedVPSTxId']       = isset( $result['VPSTxId'] ) ? str_replace( array('{','}'),'',$result['VPSTxId'] ) : NULL;
                $result['RelatedSecurityKey']   = isset( $result['SecurityKey'] ) ? $result['SecurityKey'] : NULL;
                $result['RelatedTxAuthNo']      = isset( $result['TxAuthNo'] ) ? $result['TxAuthNo'] : NULL;
                $result['RelatedVendorTxCode']  = isset( $result['VendorTxCode'] ) ? $result['VendorTxCode'] : NULL;

                foreach ( $result as $key => $value ) {
                    update_post_meta( $order_id, '_'.$key , $value );
                }

                // Update the subscription if necessary
                if( NULL != $subscription_id ) {
                    delete_post_meta( $subscription_id, '_RelatedVPSTxId' );
                    delete_post_meta( $subscription_id, '_RelatedSecurityKey' );
                    delete_post_meta( $subscription_id, '_RelatedTxAuthNo' );
                    delete_post_meta( $subscription_id, '_RelatedVendorTxCode' );

                    update_post_meta( $subscription_id, '_RelatedVPSTxId', $result['RelatedVPSTxId'] );
                    update_post_meta( $subscription_id, '_RelatedSecurityKey', $result['RelatedSecurityKey'] );
                    update_post_meta( $subscription_id, '_RelatedTxAuthNo', $result['RelatedTxAuthNo'] );
                    update_post_meta( $subscription_id, '_RelatedVendorTxCode', $result['RelatedVendorTxCode'] );
                }

            }

        }

        /**
         * [get_order_meta description]
         * Get post meta.
         * @param  [type] $previous_renewal_id [description]
         * @param  [type] $parent_order_id     [description]
         * @param  [type] $meta_key            [description]
         * @return [type]                      [description]
         */
        function get_order_meta( $subscription_id, $previous_renewal_id, $parent_order_id, $meta_key ) {

            if( NULL != get_post_meta( $subscription_id, $meta_key, TRUE ) ) {
                return get_post_meta( $subscription_id, $meta_key, TRUE );
            }

            if( NULL != get_post_meta( $previous_renewal_id, $meta_key, TRUE ) ) {
                return get_post_meta( $previous_renewal_id, $meta_key, TRUE );
            }

            if( NULL != get_post_meta( $parent_order_id, $meta_key, TRUE ) ) {
                return get_post_meta( $parent_order_id, $meta_key, TRUE );
            }

            return NULL;

        }

        /**
         * [get_subscription_token description]
         * @param  [type] $previous_renewal_id [description]
         * @param  [type] $subscription_id     [description]
         * @param  [type] $meta_key            [description]
         * @return [type]                      [description]
         */
        function get_subscription_token( $previous_renewal_id, $subscription_id, $parent_order_id, $meta_key ) {

            // Subscription should have most up to date token value if payent method has been changed
            if( NULL != get_post_meta( $subscription_id, $meta_key, TRUE ) ) {
                return get_post_meta( $subscription_id, $meta_key, TRUE );
            }

            // Previous order will have last used token
            if( NULL != get_post_meta( $previous_renewal_id, $meta_key, TRUE ) ) {
                return get_post_meta( $previous_renewal_id, $meta_key, TRUE );
            }

            // Parent order id if al else fails
            if( NULL != get_post_meta( $parent_order_id, $meta_key, TRUE ) ) {
                return get_post_meta( $parent_order_id, $meta_key, TRUE );
            }

            return NULL;
        }

        /**
         * [compare_token description]
         * @param  [type] $previous_renewal_id [description]
         * @param  [type] $subscription_id     [description]
         * @param  [type] $meta_key            [description]
         * @return [type]                      [description]
         */
        function compare_token( $previous_renewal_id, $subscription_id, $meta_key ) {

            $subscription_token     = NULL;
            $previous_renewal_token = NULL;

            // Subscription should have most up to date token value if payent method has been changed
            if( NULL != get_post_meta( $subscription_id, $meta_key, TRUE ) ) {
                $subscription_token = get_post_meta( $subscription_id, $meta_key, TRUE );
            }

            // Previous order will have last used token
            if( NULL != get_post_meta( $previous_renewal_id, $meta_key, TRUE ) ) {
                $previous_renewal_token = get_post_meta( $previous_renewal_id, $meta_key, TRUE );
            }

            // Check if $subscription_token and $previous_renewal_token match and are not NULL
            // If they match then used the related transaction details.
            // If they do not match then the token from the subscription should be used
            if( $subscription_token == $previous_renewal_token && !is_null( $subscription_token ) && !is_null( $previous_renewal_token ) ) {
                return false;
            }

            // Tokens do not match, assume token stored in subscription is newer and force that token to be used.
            return true;
        }

        /**
         * [get_subscription_vpsprotocol description]
         * @param  [type] $previous_renewal_id [description]
         * @param  [type] $subscription_id     [description]
         * @param  [type] $meta_key            [description]
         * @return [type]                      [description]
         */
        function get_subscription_vpsprotocol( $previous_renewal_id, $subscription_id, $meta_key ) {

            // Subscription should have most up to date token value if payent method has been changed
            if( NULL != get_post_meta( $subscription_id, $meta_key, TRUE ) ) {
                return get_post_meta( $subscription_id, $meta_key, TRUE );
            }

            if( NULL != get_post_meta( $previous_renewal_id, $meta_key, TRUE ) ) {
                return get_post_meta( $previous_renewal_id, $meta_key, TRUE );
            }

            return NULL;

        }

        /**
         * [get_SchemeTraceID description]
         * @param  [type] $parent_order_id [description]
         * @return [type]                  [description]
         */
        function get_SchemeTraceID( $previous_renewal_id, $subscription_id, $meta_key ) {

            // Subscription should have most up to date token value if payent method has been changed
            if( NULL != get_post_meta( $subscription_id, $meta_key, TRUE ) ) {
                return get_post_meta( $subscription_id, $meta_key, TRUE );
            }

            // Previous order will have last used token
            if( NULL != get_post_meta( $previous_renewal_id, $meta_key, TRUE ) ) {
                return get_post_meta( $previous_renewal_id, $meta_key, TRUE );
            }
            
            return 'SP999999999';

        }

        function get_token_order_data( $token, $order, $subscription_id, $previous_renewal_id, $parent_order_id ) {

            // Update renewal order with token
            update_post_meta( $order->get_id(), '_SagePayDirectToken', $token );

            // make your query.
            $data = array(
                "Token"             =>  $token,
                "StoreToken"        =>  "1",
                "ApplyAVSCV2"       =>  "2",
                "Apply3DSecure"     =>  "2",
                "VPSProtocol"       =>  $this->get_vpsprotocol(),
                "TxType"            =>  "PAYMENT",
                "Vendor"            =>  $this->get_vendor(),
                "VendorTxCode"      =>  'Renewal-' . $subscription_id . '-' . time(),
                "Amount"            =>  urlencode( $this->amount_to_charge ),
                "Currency"          =>  $this->get_order_meta( $subscription_id, $previous_renewal_id, $parent_order_id, '_order_currency' ),
                "Description"       =>   __( 'Repeat payment for subscription', 'woocommerce-gateway-sagepay-form' ) . ' ' . str_replace( '#' , '' , $subscription_id ),                        
                "BillingSurname"    =>  $this->limit_length( $order->get_billing_last_name(), 20 ),
                "BillingFirstnames" =>  $this->limit_length( $order->get_billing_first_name(), 20 ),
                "BillingAddress1"   =>  $this->limit_length( $order->get_billing_address_1(), 50 ),
                "BillingAddress2"   =>  $this->limit_length( $order->get_billing_address_2(), 50 ),
                "BillingCity"       =>  $this->limit_length( $order->get_billing_city(), 40 ),
                "BillingPostCode"   =>  $this->limit_length( $this->billing_postcode( $order->get_billing_postcode() ), 10 ),
                "BillingCountry"    =>  $order->get_billing_country(),
                "BillingState"      =>  $this->limit_length( WC_Sagepay_Common_Functions::sagepay_state( $order->get_billing_country(), $order->get_billing_state()  ), 20 ),
                "BillingPhone"      =>  $this->limit_length( $order->get_billing_phone(), 20 ),
                "DeliverySurname"   =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverysurname', $order->get_shipping_last_name(), $order ), 20 ),
                "DeliveryFirstnames"=>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryfirstname', $order->get_shipping_first_name(), $order ), 20 ),
                "DeliveryAddress1"  =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryaddress1', $order->get_shipping_address_1(), $order ), 50 ),
                "DeliveryAddress2"  =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryaddress2', $order->get_shipping_address_2(), $order ), 50 ),
                "DeliveryCity"      =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverycity', $order->get_shipping_city(), $order ), 40 ),
                "DeliveryPostCode"  =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverypostcode', $order->get_shipping_postcode(), $order ), 10 ),
                "DeliveryCountry"   =>  apply_filters( 'woocommerce_sagepay_direct_deliverycountry', $order->get_shipping_country(), $order ),
                "DeliveryState"     =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverystate', WC_Sagepay_Common_Functions::sagepay_state( $order->get_shipping_country(), $order->get_shipping_state()  ), $order ), 2 ),
                "DeliveryPhone"     =>  $this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryphone', $order->get_billing_phone(), $order ), 20 ),
                "CustomerEMail"     =>  $order->get_billing_email(),
                "AllowGiftAid"      =>  $this->get_giftaid(),
                "AccountType"       =>  $this->get_accounttype(),
                "BillingAgreement"  =>  $this->get_billingagreement(),
                "ReferrerID"        =>  $this->get_referrerid(),
                "Website"           =>  site_url()
            );

            $post_url = $this->purchaseURL;

            // Protocol 4.00
            if( $this->get_vpsprotocol() == '4.00' ) {

                $data["COFUsage"]               = 'SUBSEQUENT';
                $data["InitiatedType"]          = 'MIT';
                $data["MITType"]                = 'UNSCHEDULED';

                $data["ApplyAVSCV2"]            =  "0";
                unset( $data["Apply3DSecure"] );

                if( $this->amount_to_charge <= 25 ) {
                    $data["Apply3DSecure"]      =  "2";
                    $data["ThreeDSExemptionIndicator"] = "01";
                }

                // Allow sites to bypass AVS/CV2 checks for renewals
                if( apply_filters( 'woocommerce_sagepay_direct_applyavscvv_for_renewals', false ) ) {
                    $data["ApplyAVSCV2"]      =  "2";
                }
                
            }

            return $data;
        }

        /**
         * Logs an action scheduler.
         *
         * @param      <type>  $request  The request
         */
        function log_action_scheduler( $request, $order ) {
            global $wpdb;

            $action_logs = $wpdb->get_results(

                $wpdb->prepare(
                    "
                        SELECT *
                        FROM $wpdb->actionscheduler_logs
                        WHERE action_id = %s
                    ",
                    $request['row_id']
                ), ARRAY_A
                
            );

            // Build Order Note
            $order_notes = '';
            foreach( $action_logs AS $action_log ) {

                $order_notes .= 
                        'Action ID : ' . $action_log['action_id'] . '<br />' .
                        'Message : ' . $action_log['message'] . '<br />' .
                        'Log time (local) : ' . $action_log['log_date_local'] . '<br />' .
                        '---<br />';
            }

            $order->add_order_note( __('Action Scheduler log', 'woocommerce-gateway-sagepay-form') . '<br />' . print_r( $order_notes, TRUE ) );        

        }
    }
