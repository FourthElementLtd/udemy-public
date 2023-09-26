<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
    
	/**
	 * Refunds for SagePay Direct
	 */
	class Sagepay_Direct_Refund extends WC_Gateway_Sagepay_Direct {

		private $order_id;
		private $amount;
		private $reason;

		public function __construct( $order_id, $amount, $reason ) {

			parent::__construct();

			$this->order_id 	= $order_id;
			$this->amount 		= $amount;
			$this->reason 		= $reason;
			$this->settings 	= get_option( 'woocommerce_sagepaydirect_settings' );

		}
	
		function refund() {

    		$order 		 	= new WC_Order( $this->order_id );
    		$transaction 	= $this->get_meta_item( '_sageresult', $order );

			$VendorTxCode 	= 'Refund-' . $this->order_id . '-' . time();

            // SAGE Line 50 Fix
            $VendorTxCode 	= str_replace( 'order_', '', $VendorTxCode );

			// New API Request for refunds
            $data    = array(
                "VPSProtocol"       	=> isset( $transaction['VPSProtocol'] ) ? $transaction['VPSProtocol'] : $this->vpsprotocol,
                "TxType"            	=> 'REFUND',
                "Vendor"            	=> $this->settings['vendor'],
                "VendorTxCode"      	=> $VendorTxCode,
                "Amount"        		=> $this->amount,
                "Currency"        		=> WC_Sagepay_Common_Functions::get_order_currency( $order ),
                "Description"       	=> 'Refund for order ' . $this->order_id,
                'RelatedVPSTxId'       	=> $this->get_RelatedVPSTxId( $order ),
				'RelatedVendorTxCode'  	=> $this->get_RelatedVendorTxCode( $order ),
				'RelatedSecurityKey'   	=> $this->get_RelatedSecurityKey( $order ),
				'RelatedTxAuthNo'      	=> $this->get_RelatedTxAuthNo( $order ),
            );

			$result = $this->sagepay_post( $data, $this->refundURL );

			// Check $result for API errors
			if( is_wp_error( $result ) ) {
				return new WP_Error( 'error', __('Refund failed ', 'woocommerce-gateway-sagepay-form')  . "\r\n" . $result->get_error_message() );
			} else {
				$sageresult = $this->sageresponse( $result['body'] );

				if ( 'OK' != $sageresult['Status'] ) {

						$content = 'There was a problem refunding this payment for order ' . $this->order_id . '. The Transaction ID is ' . $data['RelatedVPSTxId'] . '. The API Request is <pre>' . 
							print_r( $data, TRUE ) . '</pre>. SagePay returned the error <pre>' . 
							print_r( $sageresult['StatusDetail'], TRUE ) . '</pre> The full returned array is <pre>' . 
							print_r( $sageresult, TRUE ) . '</pre>. ';
						
						wp_mail( $this->get_notification() ,'SagePay Refund Error ' . $sageresult['Status'] . ' ' . time(), $content );

						$order->add_order_note( __('Refund failed', 'woocommerce-gateway-sagepay-form') . '<br />' . 
											$sageresult['StatusDetail'] );

					/**
					 * Debugging
					 */
			  		WC_Sagepay_Common_Functions::sagepay_debug( $content, $this->id, __('SagePay Response : ', 'woocommerce-gateway-sagepay-form'), TRUE );

					return new WP_Error( 'error', __('Refund failed ', 'woocommerce-gateway-sagepay-form')  . "\r\n" . $sageresult['StatusDetail'] );

				} else {

					$refund_ordernote = '';

					foreach ( $sageresult as $key => $value ) {
						$refund_ordernote .= $key . ' : ' . $value . "\r\n";
					}

					$order->add_order_note( __('Refund successful', 'woocommerce-gateway-sagepay-form') . '<br />' . 
											__('Refund Amount : ', 'woocommerce-gateway-sagepay-form') . $this->amount . '<br />' .
											__('Refund Reason : ', 'woocommerce-gateway-sagepay-form') . $this->reason . '<br />' .
											__('Full return from SagePay', 'woocommerce-gateway-sagepay-form') . '<br />' .
											$refund_ordernote );

					return true;
			
				}

			}

		}

		function get_meta_item( $meta, $order ) {
			return $order->get_meta( $meta, true );
		}

		function get_RelatedVPSTxId( $order ) {

			// Refund meta
			$refund_transaction_details = $this->get_meta_item( '_sagepay_refund_transaction_details', $order );

			if( isset( $refund_transaction_details ) && $refund_transaction_details != '' ) {
				return $refund_transaction_details['VPSTxId'];
			}

            $VPSTxId 			= $this->get_meta_item( '_VPSTxId', $order );
            $RelatedVPSTxId 	= $this->get_meta_item( '_RelatedVPSTxId', $order );

            if ( !isset( $VPSTxId ) || $VPSTxId == '' ) {
            	$VPSTxId = $RelatedVPSTxId;
            }

            return $VPSTxId;
		}

		function get_RelatedVendorTxCode( $order ) {

			// Refund meta
			$refund_transaction_details = $this->get_meta_item( '_sagepay_refund_transaction_details', $order );

			if( isset( $refund_transaction_details ) && $refund_transaction_details != '' ) {
				return $refund_transaction_details['VendorTxCode'];
			}

            $VendorTxCode 			= $this->get_meta_item( '_VendorTxCode', $order );
            $RelatedVendorTxCode 	= $this->get_meta_item( '_RelatedVendorTxCode', $order );

            if ( !isset( $VendorTxCode ) || $VendorTxCode == '' ) {
            	$VendorTxCode = $RelatedVendorTxCode;
            }

            return $VendorTxCode;
		}

		function get_RelatedSecurityKey( $order ) {

			// Refund meta
			$refund_transaction_details = $this->get_meta_item( '_sagepay_refund_transaction_details', $order );

			if( isset( $refund_transaction_details ) && $refund_transaction_details != '' ) {
				return $refund_transaction_details['SecurityKey'];
			}

            $SecurityKey 			= $this->get_meta_item( '_SecurityKey', $order );
            $RelatedSecurityKey 	= $this->get_meta_item( '_RelatedSecurityKey', $order );

            if ( !isset( $SecurityKey ) || $SecurityKey == '' ) {
            	$SecurityKey = $RelatedSecurityKey;
            }

            return $SecurityKey;
		}

		function get_RelatedTxAuthNo( $order ) {

			// Refund meta
			$refund_transaction_details = $this->get_meta_item( '_sagepay_refund_transaction_details', $order );

			if( isset( $refund_transaction_details ) && $refund_transaction_details != '' ) {
				return $refund_transaction_details['TxAuthNo'];
			}

            $TxAuthNo 			= $this->get_meta_item( '_TxAuthNo', $order );
            $RelatedTxAuthNo 	= $this->get_meta_item( '_RelatedTxAuthNo', $order );

            if ( !isset( $TxAuthNo ) || $TxAuthNo == '' ) {
            	$TxAuthNo = $RelatedTxAuthNo;
            }

            return $TxAuthNo;
		}

	} // End class
