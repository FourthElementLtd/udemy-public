<?php

    /**
     * WC_Gateway_Opayo_Pi_AddOns class.
     *
     * @extends WC_Gateway_Opayo_Pi
     * Adds subscriptions support support.
     */
    class WC_Gateway_Opayo_Pi_AddOns extends WC_Gateway_Opayo_Pi {

        /**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {
            parent::__construct();

            // Subscriptions
            if ( class_exists( 'WC_Subscriptions_Order' ) ) {
                
                add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'woocommerce_process_scheduled_subscription_payment' ), 10, 2 );
                add_filter( 'wcs_renewal_order_meta_query', array( $this, 'remove_renewal_order_meta' ), 10, 3 );

            }

        }

        /**
         * process scheduled subscription payment for Subscriptions 2.0
         */
        function woocommerce_process_scheduled_subscription_payment( $amount_to_charge, $order ) {

            if( !is_object( $order ) ) {
                $order = new WC_Order( $order );
            }

            $order_id   = $order->get_id();

            // Get parent order ID
            $subscriptions      = wcs_get_subscriptions_for_renewal_order( $order_id );
            foreach( $subscriptions as $subscription ) {

                $parent_order      = $subscription->get_parent();

                $parent_order_id   = $parent_order->get_id();
                $subscription_id   = $subscription->get_id();

            }

            // Get the id of the subscription
            $subscription_id = key( $subscriptions );

            $VendorTxCode   = 'Renewal-' . $order_id . '-' . time();

            // SAGE Line 50 Fix
            $VendorTxCode   = str_replace( 'order_', '', $VendorTxCode );

            // New API Request for repeat
            $data = array(  
                "transactionType"           => $this->get_txtype( $order ),
                "referenceTransactionId"    => get_post_meta( $subscription_id, '_referenceTransactionId', TRUE ),
                "vendorTxCode"              => WC_Sagepay_Common_Functions::build_vendortxcode( $order, $this->id, $this->vendortxcodeprefix ),
                "amount"                    => $order->get_total() * 100,
                "currency"                  => WC_Sagepay_Common_Functions::get_order_currency( $order ),
                "description"               =>  __( 'Payment for Subscription', 'woocommerce-gateway-sagepay-form' ) . ' ' . str_replace( '#' , '' , $subscription_id ),
                "credentialType"            => array(
                                                    "cofUsage"      => "Subsequent",
                                                    "initiatedType" => "MIT",
                                                    "mitType"       => "Unscheduled"
                                                )                 
                "referrerId"                => $this->referrerid
            );

            // Add shiping address if required
            if ( $order->needs_shipping_address() ) {
                $data["shippingAddress"] = $this->get_shipping_address( $order );
            }

            $result = $this->remote_post( $data, $this->transaction_url, NULL, 'Basic' );

            if ( 'OK' != $result['Status'] ) {

                $content = 'There was a problem renewing this payment for order ' . $order_id . '. The Transaction ID is ' . $api_request['RelatedVPSTxId'] . '. The API Request is <pre>' . 
                    print_r( $api_request, TRUE ) . '</pre>. Opayo returned the error <pre>' . 
                    print_r( $result['StatusDetail'], TRUE ) . '</pre> The full returned array is <pre>' . 
                    print_r( $result, TRUE ) . '</pre>. ';
                    
                wp_mail( $this->notification ,'Opayo Renewal Error ' . $result['Status'] . ' ' . time(), $content );

                WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );

            } else {

                WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );

                /**
                 * Update the renewal order with the transaction info from Sage 
                 * and update the original order with the renewal order 
                 */
                $renewal_orders = WC_Subscriptions_Renewal_Order::get_renewal_orders( $order_id );
                $renewal_order  = end( array_values($renewal_orders) );
                $this->add_notes_scheduled_subscription_order( $result, $renewal_order, $order_id, $VendorTxCode );
            }

        } // process scheduled subscription payment

        /**
         * Update the renewal order with the transaction info from Sage 
         * and update the original order with the renewal order transaction information.
         */
        private function add_notes_scheduled_subscription_order( $sageresult, $order_id, $original_order_id, $VendorTxCode ) {

            $order = new WC_Order( $order_id );

            /**
             * Successful payment
             */
            $successful_ordernote = '';

            foreach ( $sageresult as $key => $value ) {
                $successful_ordernote .= $key . ' : ' . $value . "\r\n";
            }

            $order->add_order_note( __('Payment completed', 'woocommerce-gateway-sagepay-form') . '<br />' . $successful_ordernote );

            update_post_meta( $order_id, '_transaction_id', str_replace( array('{','}'),'',$sageresult['VPSTxId'] ) );
            update_post_meta( $order_id, '_VPSTxId' , str_replace( array('{','}'),'',$sageresult['VPSTxId'] ) );
            update_post_meta( $order_id, '_SecurityKey' , $sageresult['SecurityKey'] );
            update_post_meta( $order_id, '_TxAuthNo' , $sageresult['TxAuthNo'] );
            delete_post_meta( $order_id, '_CV2Result' );
            delete_post_meta( $order_id, '_3DSecureStatus' );

            // update the original order with the renewal order transaction information
            update_post_meta( $original_order_id, '_RelatedVPSTxId' , str_replace( array('{','}'),'',$sageresult['VPSTxId'] ) );
            update_post_meta( $original_order_id, '_RelatedVendorTxCode' , $VendorTxCode );
            update_post_meta( $original_order_id, '_RelatedSecurityKey' , $sageresult['SecurityKey'] );
            update_post_meta( $original_order_id, '_RelatedTxAuthNo' , $sageresult['TxAuthNo'] );

        }

        /**
         * Don't transfer Stripe customer/token meta when creating a parent renewal order.
         *
         * @access public
         * @param array $order_meta_query MySQL query for pulling the metadata
         * @param int $original_order_id Post ID of the order being used to purchased the subscription being renewed
         * @param int $renewal_order_id Post ID of the order created for renewing the subscription
         * @param string $new_order_role The role the renewal order is taking, one of 'parent' or 'child'
         * @return void
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
            update_post_meta( $original_order->id, '_OpayoPiToken', get_post_meta( $new_renewal_order->id, '_OpayoPiToken', true ) );

        }

        /**
         * Render the payment method used for a subscription in the "My Subscriptions" table
         *
         * @param string $payment_method_to_display the default payment method text to display
         * @param array $subscription_details the subscription details
         * @param WC_Order $order the order containing the subscription
         * @return string the subscription payment method
         */
        public function maybe_render_subscription_payment_method( $payment_method_to_display, $subscription ) {
            // bail for other payment methods
            if ( $this->id != $subscription->payment_method || ! $subscription->customer_user ) {
                return $payment_method_to_display;
            }

            $sage_token     = get_post_meta( $subscription->order->id, '_OpayoPiToken', true );
            $sage_token_id  = $this->get_token_id( $sage_token );

            $token = new WC_Payment_Token_CC();
            $token = WC_Payment_Tokens::get( $sage_token_id );

            if( $token ) {
                $payment_method_to_display = sprintf( __( 'Via %s card ending in %s', 'woocommerce-gateway-sagepay-form' ), $token->get_card_type(), $token->get_last4() );
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

            $id = NULL;

            if ( $token ) {
                $tokens = $wpdb->get_row( $wpdb->prepare(
                    "SELECT token_id FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token = %s",
                    $token
                ) );
            }

            return $tokens->token_id;
        }

    }
