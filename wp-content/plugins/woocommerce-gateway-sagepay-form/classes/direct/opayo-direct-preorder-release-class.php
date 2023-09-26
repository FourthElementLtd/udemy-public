<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

	/**
	 * Process the payment form
	 */
	class Opayo_Direct_PreOrder_Release extends WC_Gateway_Sagepay_Direct {

        private $order;

        public function __construct() {

            parent::__construct();

            $this->settings     = get_option( 'woocommerce_sagepaydirect_settings' );

        }

        /**
         * [release description]
         * @return [type] [description]
         */
        function preorder_release( $order ) {

            // woocommerce order instance
            $order_id   = $order->get_id();

            // AUTHORISED or DEFERRED?
            $_SagePayTransactionType = get_post_meta( $order_id, '_SagePayTxType', TRUE );

            // Set up the data array and send to Sage
            if ( isset($_SagePayTransactionType) && $_SagePayTransactionType == 'DEFERRED' ) {
                $return = $this->deferred_order( $order_id );
            } else {
                $return = $this->authorised_order( $order_id );
            }
            
            // Break up the returned array
            $result         = $return[0];
            $VendorTxCode   = $return[1];

            // Check $result for API errors
			if( is_wp_error( $result ) ) {

				$sageresult = $result->get_error_message();
				$this->failed_payment( $order_id, $result, $VendorTxCode );

			} else {
				// Process the result from Sage
				$sageresult = $this->sageresponse( $result['body'] );

	            if ( 'OK' != strtoupper( $sageresult['Status'] ) )  {
	                // Failed payment
	                $this->failed_payment( $order_id, $sageresult, $VendorTxCode );
	            } else {
	                // Successful payment   
	                $this->successful_payment( $order_id, $sageresult, $VendorTxCode );
	            }

	        }

        }

        /**
         * [authorised_order description]
         * @param  [type] $order_id [description]
         * @return [type]           [description]
         */
        function authorised_order( $order_id ) {

            $order          = wc_get_order( $order_id );
            $transaction    = get_post_meta( $order_id, '_sageresult', $order_id );

            $VendorTxCode   = 'Authorise-' . $order_id . '-' . time();

            // Fix for missing '_VendorTxCode'
            $_VendorTxCode          = get_post_meta( $order_id, '_VendorTxCode', true );
            $_RelatedVendorTxCode   = get_post_meta( $order_id, '_RelatedVendorTxCode', true );

            if ( !isset($_VendorTxCode) || $_VendorTxCode == '' ) {
                $_VendorTxCode = $_RelatedVendorTxCode;
            }

            $data    = array(
                "VPSProtocol"           => $transaction['VPSProtocol'],
                "TxType"                => "AUTHORISE",
                "Vendor"                => $this->get_vendor(),
                "VendorTxCode"          => $VendorTxCode,
                "Amount"                => $order->get_total(),
                "Currency"              => get_post_meta( $order_id, '_order_currency', true ),
                "Description"           => 'Payment for pre-order ' . $order_id,
                'RelatedVPSTxId'        => get_post_meta( $order_id, '_VPSTxId', true ),
                'RelatedVendorTxCode'   => $_VendorTxCode,
                'RelatedSecurityKey'    => get_post_meta( $order_id, '_SecurityKey', true ),
                'RelatedTxAuthNo'       => get_post_meta( $order_id, '_TxAuthNo', true ),
                'InitiatedType' 		=> 'MIT'
            );

            return array( $this->sagepay_post( $data, $this->authoriseURL ), $VendorTxCode );

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
                "ReleaseAmount"         => $order->get_total(),
            );

            return array( $this->sagepay_post( $data, $this->releaseURL ), $VendorTxCode );

        }

        /**
         * [successful_payment description]
         * @param  [type] $order_id [description]
         * @param  [type] $result   [description]
         * @return [type]           [description]
         */
        function successful_payment( $order_id, $result, $VendorTxCode ) {

            $order = wc_get_order( $order_id );
            
            $successful_ordernote = '';

            foreach ( $result as $key => $value ) {
                $successful_ordernote .= $key . ' : ' . $value . "\r\n";
            }

            $order->add_order_note( __('Payment completed', 'woocommerce-gateway-sagepay-form') . '<br />' . $successful_ordernote );

            // AUTHORISED or DEFERRED?
            $_SagePayTransactionType = get_post_meta( $order_id, '_SagePayTransactionType', TRUE );

            // Update the order meta for authorized payments. Not required for deferred payments.
            $this->update_order( $order_id, $result, $VendorTxCode );

            // Delete _SagePayDirectPaymentStatus
            delete_post_meta( $order_id, '_SagePayDirectPaymentStatus' );
            
            // complete the order
            $order->set_status( 
                ($order->needs_processing() ? 'processing' : 'completed'), 
                __('Payment completed', 'woocommerce-gateway-sagepay-form') . ( isset( $result['message'] ) ? '<br />Approval Msg: ' . $result['message'] : NULL ) . '<br />' 
            );
            
            // Set transaction ID for authorized payments, not required for deferred payments.
            if( isset($result['VPSTxId']) ) {
                $order->payment_complete( str_replace( array('{','}'), '', $result['VPSTxId'] ) );
                update_post_meta( $order_id, '_transaction_id', str_replace( array('{','}'), '', $result['VPSTxId'] ) );
            }

            // Save the order!
            $order->save();

            do_action( 'woocommerce_sagepay_direct_payment_complete', $result, $order );

        }

        /**
         * [update_order description]
         * @param  [type] $order_id     [description]
         * @param  [type] $result       [description]
         * @param  [type] $VendorTxCode [description]
         * @return [type]               [description]
         */
        function update_order( $order_id, $result, $VendorTxCode ) {

            $order = wc_get_order( $order_id );

            $result['VendorTxCode']         = $VendorTxCode;
            $result['RelatedVendorTxCode']  = $VendorTxCode;

            if( isset( $result['VPSTxId'] ) && $result['VPSTxId'] !="" ) {
                $result['RelatedVPSTxId'] = str_replace( array('{','}'),'',$result['VPSTxId'] );
            }

            if( isset( $result['SecurityKey'] ) && $result['SecurityKey'] !="" ) {
                $result['RelatedSecurityKey'] = $result['SecurityKey'];
            }

            if( isset( $result['TxAuthNo'] ) && $result['TxAuthNo'] !="" ) {
                $result['RelatedTxAuthNo'] = $result['TxAuthNo'];
            }

            // Only update the order if there is a transaction ID
            if( isset( $result['VPSTxId'] ) && $result['VPSTxId'] !="" ) {
                update_post_meta( $order_id, '_sageresult' , $result );
            }

            foreach ( $result as $key => $value ) {
                update_post_meta( $order_id, '_'.$key , $value );
            }

        }

        /**
         * [failed_payment description]
         * @param  [type] $order_id [description]
         * @param  [type] $result   [description]
         * @return [type]           [description]
         */
        function failed_payment( $order_id, $result, $VendorTxCode ) {

            $order = wc_get_order( $order_id );
            
            // Update the order meta  
            // $this->update_order( $order_id, $result, $VendorTxCode );

            $ordernote = '';

            foreach ( $result as $key => $value ) {
                $ordernote .= $key . ' : ' . $value . "\r\n";
            }

            $order->add_order_note( __('Payment capture failed. You can attempt to capture the order again by changing the order status to Authorised and using the "Capture authorised payment" option', 'woocommerce-gateway-sagepay-form') . '<br />' . $ordernote );
            $order->update_status('failed');
            $order->save();

        }

	} // End class
