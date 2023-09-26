<?php

    /**
     * Opayo_Pi_Subcription_Renewals class.
     *
     * @extends WC_Gateway_Opayo_Pi
     * Adds subscriptions support support.
     */
    class Opayo_Pi_Subcription_Renewals extends WC_Gateway_Opayo_Pi {

        private $amount_to_charge;
        private $order;

        public function __construct( $amount_to_charge, $order ) {

            parent::__construct();

            $this->amount_to_charge = $amount_to_charge;
            $this->order            = $order;

        }

        /**
         * process_scheduled_payment
         */
        function process_scheduled_payment() {

            $order = $this->order;

            if( !is_object( $order ) ) {
                $order = new WC_Order( $order );
            }

            // Get $order_id from order object
            $order_id = $order->get_id();

            // Get $subscription_id from order meta
            $subscription_id = get_post_meta( $order_id, '_subscription_renewal', TRUE );

            // Get referenceTransactionId from Subscription meta
            $referenceTransactionId = get_post_meta( $subscription_id, '_referenceTransactionId', TRUE );

            $data = array(  
                "transactionType"           => "Repeat",
                "referenceTransactionId"    => $referenceTransactionId,
                "vendorTxCode"              => WC_Sagepay_Common_Functions::build_vendortxcode( $order, $this->id, $this->vendortxcodeprefix ),
                "amount"                    => $this->get_total( $this->amount_to_charge ),
                "currency"                  => WC_Sagepay_Common_Functions::get_order_currency( $order ),
                "description"               => __( 'Repeat payment for subscription', 'woocommerce-gateway-sagepay-form' ) . ' ' . str_replace( '#' , '' , $subscription_id ),
                "customerFirstName"         => $order->get_billing_first_name(),
                "customerLastName"          => $order->get_billing_last_name(),
                "billingAddress"            => $this->get_billing_address( $order ),
                "customerEmail"             => $order->get_billing_email(),
                "customerPhone"             => $this->get_international_phone_format( $order ),
                "referrerId"                => $this->referrerid,
                "credentialType"            => array( 
                                                    "cofUsage"      => "Subsequent",
                                                    "initiatedType" => "MIT",
                                                    "mitType"       => "Unscheduled",
                                                ),
            );

            // Add shiping address if required
            if ( $order->needs_shipping_address() ) {
                $data["shippingDetails"] = $this->get_shipping_address( $order );
            }

            // Send $data to Opayo
            $result = $this->remote_post( $data, $this->transaction_url, NULL, 'Basic' );

            // Process the result
            if( isset( $result['status'] ) && in_array( $result['status'], array( 'Ok','Authenticated' ) ) ) {
                // Process renewal success
                $this->process_renewal_success( $result, $order, $subscription_id );
            } else {
                // Process renewal failure
                $this->process_renewal_failure( $result, $order, $subscription_id );
            }

        } // process scheduled subscription payment

        /**
         * process_renewal_success
         *
         * @param      <type>  $result           The result
         * @param      <type>  $data             The data
         * @param      <type>  $order            The order
         * @param      <type>  $order_id         The order identifier
         * @param      <type>  $subscription_id  The subscription identifier
         */
        function process_renewal_success( $result, $order, $subscription_id ) {

            // Get the subscription
            $subscription = wcs_get_subscription( $subscription_id );

            // Mark the payment complete, updates order and subscription
            $subscription->payment_complete( isset( $result['transactionId'] ) ? $result['transactionId'] : '' );

            // Update subscription _referenceTransactionId with new transactionId
            update_post_meta( $subscription_id, '_referenceTransactionId', $result['transactionId'] );

            // Add order note and update order meta
            WC_Gateway_Opayo_Pi::add_order_note( $result, $order, __('Payment completed', 'woocommerce-gateway-sagepay-form') );

            // Add $result to Subscription
            update_post_meta( $subscription_id , '_sageresult', $result );

        }

        /**
         * process_renewal_failure
         *
         * @param      <type>  $result           The result
         * @param      <type>  $data             The data
         * @param      <type>  $order            The order
         * @param      <type>  $order_id         The order identifier
         * @param      <type>  $subscription_id  The subscription identifier
         */
        function process_renewal_failure( $result, $order, $subscription_id ) {

            // Get the subscription
            $subscription = wcs_get_subscription( $subscription_id );

            // Mark the payment failed, updates order and subscription
            $subscription->payment_failed();

            // Add order note and update order meta
            WC_Gateway_Opayo_Pi::add_order_note( $result, $order, __('Payment failed', 'woocommerce-gateway-sagepay-form') );

            // Add $result to Subscription
            update_post_meta( $subscription_id , '_sageresult', $result );

        }

    }
