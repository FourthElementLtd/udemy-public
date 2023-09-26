<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
    
	/**
	 * Refunds for SagePay Direct
	 */
	class Sagepay_Direct_Void extends WC_Gateway_Sagepay_Direct {
		private $order;

		public function __construct( $order ) {

			parent::__construct();

			$this->order 		= $order;
			$this->settings 	= get_option( 'woocommerce_sagepaydirect_settings' );

		}
	
		function void() {

        	// woocommerce order instance
           	$order  	 = $this->order;
            $order_id    = $order->get_id();

    		$transaction = $this->get_meta_item( '_sageresult', $order );

			// API Request for voids
            $data    = array(
                "VPSProtocol"   => isset( $transaction['VPSProtocol'] ) ? $transaction['VPSProtocol'] : $this->vpsprotocol,
                "TxType"        => 'VOID',
                "Vendor"        => $this->settings['vendor'],
                "VPSTxId"       => $this->get_RelatedVPSTxId( $order ),
				"VendorTxCode"  => $this->get_RelatedVendorTxCode( $order ),
				"SecurityKey"  	=> $this->get_RelatedSecurityKey( $order ),
				"TxAuthNo"      => $this->get_RelatedTxAuthNo( $order ),
            );

			$result = $this->sagepay_post( $data, $this->voidURL );

			// Check $result for API errors
			if( is_wp_error( $result ) ) {
				return new WP_Error( 'error', __('Void failed ', 'woocommerce-gateway-sagepay-form')  . "\r\n" . $result->get_error_message() );
			} else {
				$sageresult = $this->sageresponse( $result['body'] );

				if ( 'OK' != $sageresult['Status'] ) {

						$content = 'There was a problem voiding this payment for order ' . $order_id . '. The Transaction ID is ' . $data['VPSTxId'] . '. The API Request is <pre>' . 
							print_r( $data, TRUE ) . '</pre>. Opayo returned the error <pre>' . 
							print_r( $sageresult['StatusDetail'], TRUE ) . '</pre> The full returned array is <pre>' . 
							print_r( $sageresult, TRUE ) . '</pre>. ';
						
						wp_mail( $this->get_notification() ,'Opayo Void Error ' . $sageresult['Status'] . ' ' . time(), $content );

						$order->add_order_note( __('Void failed', 'woocommerce-gateway-sagepay-form') . '<br />' . 
											$sageresult['StatusDetail'] );

					/**
					 * Debugging
					 */
			  		WC_Sagepay_Common_Functions::sagepay_debug( $content, $this->id, __('Opayo Response : ', 'woocommerce-gateway-sagepay-form'), TRUE );

					return new WP_Error( 'error', __('Void failed ', 'woocommerce-gateway-sagepay-form')  . "\r\n" . $sageresult['StatusDetail'] );

				} else {

					$refund_ordernote = '';

					foreach ( $sageresult as $key => $value ) {
						$refund_ordernote .= $key . ' : ' . $value . "\r\n";
					}

					$order->add_order_note( __('Void successful', 'woocommerce-gateway-sagepay-form') . '<br />' . 
											__('Full return from Opayo', 'woocommerce-gateway-sagepay-form') . '<br />' .
											$refund_ordernote );
					$order->update_status( 'cancelled', _x( 'The order has been voided.', 'woocommerce-gateway-sagepay-form' ) );

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
