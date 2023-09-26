<?php

    /**
     * Pi_Subcription_Renewals class.
     *
     * @extends WC_Gateway_Opayo_Pi
     * Adds subscriptions support support.
     */
    class Pi_Subcription_Renewals extends WC_Gateway_Opayo_Pi {

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
                "referenceTransactionId"    => get_post_meta( $order_id, '_referenceTransactionId', TRUE ),
                "vendorTxCode"              => WC_Sagepay_Common_Functions::build_vendortxcode( $order, $this->id, $this->vendortxcodeprefix ),
                "amount"                    => $order->get_total() * 100,
                "currency"                  => WC_Sagepay_Common_Functions::get_order_currency( $order ),
                "description"               =>  __( 'Payment for Subscription', 'woocommerce-gateway-sagepay-form' ) . ' ' . str_replace( '#' , '' , $subscription_id ),
                "credentialType"            => array(
                                                    "cofUsage"      => "Subsequent",
                                                    "initiatedType" => "MIT",
                                                    "mitType"       => "Unscheduled"
                                                ),                 
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

        }

    }
