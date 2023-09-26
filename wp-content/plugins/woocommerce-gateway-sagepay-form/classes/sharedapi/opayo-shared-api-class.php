<?php
	/**
	 * Shared API
	 */
	class WC_Gateway_Opayo_Shared_API extends WC_Gateway_Sagepay_Direct { {
		
		/**
		 * https://developer-eu.elavon.com/docs/opayo-shared-api
		 */
		public function __construct() {

			parent::__construct();

		}

		/**
		 * https://developer-eu.elavon.com/docs/opayo-shared-api/release
		 *
		 * VPSProtocol
		 * TxType
		 * Vendor
		 * VendorTxCode
		 * VPSTxId
		 * SecurityKey
		 * TxAuthNo
		 * ReleaseAmount
		 *
		 * https://test.sagepay.com/gateway/service/release.vsp
		 * https://live.sagepay.com/gateway/service/release.vsp
		 * 
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function release( $order_id ) {

			$order_id 	= $order->get_id();
			$result 	= get_post_meta( $order_id, '_sageresult', TRUE );

			$data = array(
						 'VPSProtocol' => '4.00',
						 'TxType' => 'RELEASE',
						 'Vendor' => 
						 'VendorTxCode' => 
						 'VPSTxId' =>
						 'SecurityKey' =>	
						 'TxAuthNo' =>
						 'ReleaseAmount' =>

						);

		}

		/**
		 * https://developer-eu.elavon.com/docs/opayo-shared-api/abort
		 *
		 * VPSProtocol
		 * TxType
		 * Vendor
		 * VendorTxCode
		 * VPSTxId
		 * SecurityKey
		 * TxAuthNo
		 *
		 * https://test.sagepay.com/gateway/service/abort.vsp
		 * https://live.sagepay.com/gateway/service/abort.vsp
		 *
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function abort( $order_id ) {

			$order_id 	= $order->get_id();
			$result 	= get_post_meta( $order_id, '_sageresult', TRUE );

			$data = array(
						 'VPSProtocol' => '4.00',
						 'TxType' => 'ABORT',
						 'Vendor' => 
						 'VendorTxCode' => 
						 'VPSTxId' =>
						 'SecurityKey' =>	
						 'TxAuthNo' =>						
						);

		}

		/**
		 * https://developer-eu.elavon.com/docs/opayo-shared-api/refund
		 *
		 * VPSProtocol
		 * TxType
		 * Vendor
		 * VendorTxCode
		 * Amount
		 * Currency
		 * Description
		 * RelatedVPSTxId
		 * RelatedVendorTxCode
		 * RelatedSecurityKey
		 * RelatedTxAuthNo
		 * VendorData
		 *
		 * https://test.sagepay.com/gateway/service/refund.vsp
		 * https://live.sagepay.com/gateway/service/refund.vsp
		 * 
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function refund( $order_id ) {

			$order_id 	= $order->get_id();
			$result 	= get_post_meta( $order_id, '_sageresult', TRUE );

			$data = array(
						 'VPSProtocol' => '4.00',
						 'TxType' => 'REFUND',
						 'Vendor' => 
						 'VendorTxCode' => 
						 'Amount' => 
						 'Currency' => 
						 'Description' => 
						 'RelatedVPSTxId' => 
						 'RelatedVendorTxCode' => 
						 'RelatedSecurityKey' => 
						 'RelatedTxAuthNo' => 
						 'VendorData' => 						
						);

		}

		/**
		 * https://developer-eu.elavon.com/docs/opayo-shared-api/repeat-and-repeatdeferred
		 *
		 * VPSProtocol
		 * TxType
		 * Vendor
		 * VendorTxCode
		 * Amount
		 * Currency
		 * Description
		 * RelatedVPSTxId
		 * RelatedVendorTxCode
		 * RelatedSecurityKey
		 * RelatedTxAuthNo
		 * CV2
		 * DeliverySurname
		 * DeliveryFirstnames
		 * DeliveryAddress1
		 * DeliveryAddress2
		 * DeliveryCity
		 * DeliveryPostCode
		 * DeliveryCountry
		 * DeliveryState
		 * DeliveryPhone
		 * BasketXML
		 * COFUsage
		 * InitiatedType
		 * MITType
		 *
		 * https://test.sagepay.com/gateway/service/repeat.vsp
		 * https://live.sagepay.com/gateway/service/repeat.vsp
		 * 
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function repeat( $order_id ) {

			$order_id 	= $order->get_id();
			$result 	= get_post_meta( $order_id, '_sageresult', TRUE );

			$data = array(
						 'VPSProtocol' => '4.00',
						 'TxType' => 'REPEAT',
						 'Vendor' => 
						 'VendorTxCode' => 
						 'Amount' =>
						 'Currency' =>
						 'Description' =>
						 'RelatedVPSTxId' =>
						 'RelatedVendorTxCode' =>
						 'RelatedSecurityKey' =>
						 'RelatedTxAuthNo' =>
						 'CV2' =>
						 'DeliverySurname' =>
						 'DeliveryFirstnames' =>
						 'DeliveryAddress1' =>
						 'DeliveryAddress2' =>
						 'DeliveryCity' =>
						 'DeliveryPostCode' =>
						 'DeliveryCountry' =>
						 'DeliveryState' =>
						 'DeliveryPhone' =>
						 'BasketXML' =>
						 'COFUsage' =>
						 'InitiatedType' =>
						 'MITType' =>						
						);

		}

		/**
		 * https://developer-eu.elavon.com/docs/opayo-shared-api/void
		 *
		 * VPSProtocol
		 * TxType
		 * Vendor
		 * VendorTxCode
		 * VPSTxId
		 * SecurityKey
		 * TxAuthNo
		 *
		 * https://test.sagepay.com/gateway/service/void.vsp
		 * https://live.sagepay.com/gateway/service/void.vsp
		 * 
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function void( $order_id ) {

			$order_id 	= $order->get_id();
			$result 	= get_post_meta( $order_id, '_sageresult', TRUE );

			$data = array(
						 'VPSProtocol' => '4.00',
						 'TxType' => 'VOID',
						 'Vendor' => 
						 'VendorTxCode' => 
						 'VPSTxId' =>
						 'SecurityKey' =>	
						 'TxAuthNo' =>						
						);

		}

		/**
		 * https://developer-eu.elavon.com/docs/opayo-shared-api/authorise
		 *
		 * VPSProtocol
		 * TxType
		 * Vendor
		 * VendorTxCode
		 * Amount
		 * Description
		 * RelatedVPSTxId
		 * RelatedVendorTxCode
		 * RelatedSecurityKey
		 * RelatedTxAuthNo
		 * ApplyAVSCV2
		 * VendorData
		 *
		 * https://test.sagepay.com/gateway/service/authorise.vsp
		 * https://live.sagepay.com/gateway/service/authorise.vsp
		 * 
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function authorise( $order_id ) {

			$order_id 	= $order->get_id();
			$result 	= get_post_meta( $order_id, '_sageresult', TRUE );

			$data = array(
						 'VPSProtocol' => '4.00',
						 'TxType' => 'AUTHORISE',
						 'Vendor' => 
						 'VendorTxCode' =>
						 'Amount' =>
						 'Description' =>
						 'RelatedVPSTxId' =>
						 'RelatedVendorTxCode' =>
						 'RelatedSecurityKey' =>
						 'RelatedTxAuthNo' =>
						 'ApplyAVSCV2' =>
						 'VendorData ' =>						
						);

		}

		/**
		 * https://developer-eu.elavon.com/docs/opayo-shared-api/cancel
		 *
		 * VPSProtocol
		 * TxType
		 * Vendor
		 * VendorTxCode
		 * VPSTxId
		 * SecurityKey
		 *
		 * https://test.sagepay.com/gateway/service/cancel.vsp
		 * https://live.sagepay.com/gateway/service/cancel.vsp
		 * 
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function cancel( $order ) {

			$order_id 	= $order->get_id();
			$result 	= get_post_meta( $order_id, '_sageresult', TRUE );

			$data = array(
						 'VPSProtocol' => '4.00',
						 'TxType' => 'CANCEL',
						 'Vendor' => 
						 'VendorTxCode' => 
						 'VPSTxId' =>
						 'SecurityKey' =>							
						);
			
		}

		/**
		 * https://developer-eu.elavon.com/docs/opayo-shared-api/removetoken
		 *
		 * VPSProtocol
		 * TxType
		 * Vendor
		 * Token
		 *
		 * https://test.sagepay.com/gateway/service/removetoken.vsp
		 * https://live.sagepay.com/gateway/service/removetoken.vsp
		 * 
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function removetoken( $vendor, $token ) {

			$data = array(
						 'VPSProtocol' => '4.00',
						 'TxType' => 'REMOVETOKEN',
						 'Vendor' => 
						 'Token' => 							
						);



		}

		public static function post( $data, $url ) {

		}

	} // End class