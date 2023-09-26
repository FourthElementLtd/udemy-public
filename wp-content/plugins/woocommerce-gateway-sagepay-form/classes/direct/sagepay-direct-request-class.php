<?php
	/**
	 * Create request for SagePay Direct
	 */
	class Sagepay_Direct_Request extends WC_Gateway_Sagepay_Direct {

		private $order;
		private $card_form;

		public function __construct( $order, $card_form, $requirement = 'standard' ) {

			parent::__construct();

			$this->settings 			= get_option( 'woocommerce_sagepaydirect_settings' );

			$this->order 				= $order;
			$this->card_form 			= $card_form;
			$this->requirement 			= $requirement;

			$this->sage_card_number 		= $this->card_form['sage_card_number'];
			$this->sage_card_exp_month 		= $this->card_form['sage_card_exp_month'];
			$this->sage_card_exp_year 		= $this->card_form['sage_card_exp_year'];
			$this->sage_card_cvc 			= $this->card_form['sage_card_cvc'];
			$this->sage_card_type 			= $this->card_form['sage_card_type'];
			$this->sage_card_save_token 	= $this->card_form['sage_card_save_token'];
			$this->sage_card_token 			= $this->card_form['sage_card_token'];
			$this->sage_gift_aid_payment 	= $this->card_form['sage_gift_aid_payment'];

			if( $this->vpsprotocol == '4.00' ) {

				$this->browserJavaEnabled 		= $this->card_form['browserJavaEnabled'];
				$this->browserJavascriptEnabled = $this->card_form['browserJavascriptEnabled'];
				$this->browserLanguage 			= $this->card_form['browserLanguage'];
				$this->browserColorDepth 		= $this->card_form['browserColorDepth'];
				$this->browserScreenHeight 		= $this->card_form['browserScreenHeight'];
				$this->browserScreenWidth 		= $this->card_form['browserScreenWidth'];
				$this->browserTZ 				= $this->card_form['browserTZ'];
				$this->browserUserAgent 		= $this->card_form['browserUserAgent'];

			}

			$this->open_salt  = !is_null( AUTH_SALT ) ? AUTH_SALT : 'J$+;oD=Ttuw`k7A*:QR+|(QS$3jaX:F15=T#97=T3aAV_8X-y?^f1CEPjHB!,|!D';
			$this->close_salt = !is_null( NONCE_SALT ) ? NONCE_SALT : '}2$:u3q)t$7_=e-E7:LQp_@z|^_Tgc(+f7q]EC:|zu&=`_<|$tfU[G]EFPrC:9+l';
			
		}
		
		/**
		 * [build_request description]
		 * @return [type] [description]
		 */
		function build_request() {

			$order = $this->order;

			// woocommerce order instance
			if ( !is_object($order) ) {
				$order  = wc_get_order( $order );
			}

            $order_id  = $order->get_id();

            // Set testing or live status
            update_post_meta( $order_id, '_sagepay_status', $this->status );

			// Set $registered_token to false
			$registered_token = false;

			// Set $cardholder for testing 3D Secure 2.0
			$cardholder = WC_Sagepay_Common_Functions::clean_sagepay_args( $order->get_billing_first_name() . ' ' .  $order->get_billing_last_name() );

			if( $this->status != 'live' && $this->sagemagicvalue != 'No Magic Value' ) {
				$cardholder = $this->sagemagicvalue;
			}

			switch( $this->requirement ) {
				case 'new_token' :

					$data    = array(
		                "VPSProtocol"       => $this->vpsprotocol,
		                "TxType"            => 'TOKEN',
		                "Vendor"            => $this->vendor,
		                "Currency"          => WC_Sagepay_Common_Functions::get_order_currency( $order ),
						"CardHolder" 		=> $cardholder,
						"CardNumber" 		=> $this->sage_card_number,
						"ExpiryDate"		=> $this->sage_card_exp_month . $this->sage_card_exp_year,
						"CV2"				=> $this->sage_card_cvc,
						"CardType"			=> $this->cc_type( $this->sage_card_number, $this->sage_card_type ),
		            );

		            return $data;

				break;

				case 'update_token' :

					$token = new WC_Payment_Token_CC();
					$token = WC_Payment_Tokens::get( $this->sage_card_token );

					// Get Customer ID
					$customer_id = $order->get_customer_id();

					if ( $token && $token->get_user_id() == $customer_id) {

						$data = array (
							'Status' 		=> 'EXISTINGTOKEN',
							'StatusDetail' 	=> __('Order updated with new token', 'woocommerce-gateway-sagepay-form'),
							'Token' 		=>	str_replace( array('{','}'),'',$token->get_token() )
						);

					} else {

						$data = array (
							'Status' 		=> 'ERROR',
							'StatusDetail' 	=> __('Customer attempeted to pay with an existing token. The token is not available.', 'woocommerce-gateway-sagepay-form'),
							'Token' 		=> NULL
						);

					}

					return $data;

				break;

				case 'existing_token' :

					$token = new WC_Payment_Token_CC();
					$token = WC_Payment_Tokens::get( $this->sage_card_token );

					// Get Customer ID
					$customer_id = $order->get_customer_id();

					if ( $token && $token->get_user_id() == $customer_id ) {

						// Get the basic $data array for the order 
						$data = $this->get_transaction_data( $order_id );

						// Add / Modify as required for token payment
						$data["CardHolder"] 	= $cardholder;
						$data["Token"] 			= str_replace( array('{','}'),'',$token->get_token() );
						$data["StoreToken"] 	= "1";

						$data["ApplyAVSCV2"]	= '0';
						$data["CV2"] 			= $this->sage_card_cvc;

						$data["Apply3DSecure"] 	= $this->secure;

						// Protocol 4.00
						if( $this->vpsprotocol == '4.00' ) {
							$data["InitiatedType"] 	= 'CIT';
							$data["COFUsage"] 		= 'SUBSEQUENT';
						}

						// Update the order meta with the token
						update_post_meta( $order_id, '_SagePayDirectToken' , $data['Token'] );

					} else {

						$data = array (
							'Status' 		=> 'ERROR',
							'StatusDetail' 	=> __('Customer attempted to pay with an existing token. The token is not available.', 'woocommerce-gateway-sagepay-form'),
							'Token' 		=> NULL

						);

						return $data;

					}

				break;

				case 'new_method' :

            		// Get the basic $data array for the order 
					$data = $this->get_transaction_data( $order_id );

					$data["CardHolder"] 	=	$cardholder;
					$data["CardNumber"] 	=	$this->sage_card_number;
					$data["ExpiryDate"]		=	$this->sage_card_exp_month . $this->sage_card_exp_year;
					$data["CV2"]			=	$this->sage_card_cvc;
					$data["CardType"]		=	$this->cc_type( $this->sage_card_number, $this->sage_card_type );
					$data["ApplyAVSCV2"] 	=	$this->cvv;
					$data["Apply3DSecure"] 	=	$this->secure;

				break;

				case 'standard' :

            		// Get the basic $data array for the order 
					$data = $this->get_transaction_data( $order_id );

					$data["CardHolder"] 	=	$cardholder;
					$data["CardNumber"] 	=	$this->sage_card_number;
					$data["ExpiryDate"]		=	$this->sage_card_exp_month . $this->sage_card_exp_year;
					$data["CV2"]			=	$this->sage_card_cvc;
					$data["CardType"]		=	$this->cc_type( $this->sage_card_number, $this->sage_card_type );
					$data["ApplyAVSCV2"] 	=	$this->cvv;
					$data["Apply3DSecure"] 	=	$this->secure;

				break;

			}

			// PayPalCallbackURL
			if( isset( $data["CardType"] ) && strtoupper($data["CardType"]) == 'PAYPAL' ) {
				$paypal_successurl = add_query_arg( 'vtx', $data["VendorTxCode"], $this->successurl );
				$data["PayPalCallbackURL"] = apply_filters( 'woocommerce_sagepaydirect_successurl', $paypal_successurl, $order_id );

				// Unset card detail fields
				unset( $data["CardHolder"] );
				unset( $data["CardNumber"] );
				unset( $data["ExpiryDate"] );
				unset( $data["CV2"] );

				if( $this->billingagreement == "1" ) {
					$data["BillingAgreement"] =	$this->billingagreement;
				}
				
			}

			// Force basket type to non-XML if using PayPal - PayPal transactions fail if using XML basket.
			$this->basketoption = ( isset( $data["CardType"] ) && strtoupper( $data["CardType"] ) == 'PAYPAL' ) ? 1 : $this->basketoption;

			// Add the basket
			$basket = WC_Sagepay_Common_Functions::get_basket( $this->basketoption, $order_id );
			if ( $basket != NULL ) {
				if ( $this->basketoption == 1 ) {
					$data["Basket"] = $basket;
				} elseif ( $this->basketoption == 2 ) {
					$data["BasketXML"] = $basket;
				}
			}

			// Filter the args if necessary, use with caution
            $data = apply_filters( 'woocommerce_sagepay_direct_data', $data, $order );

			/**
			 * Store TxType for future checking
			 * This will be useful for checking Authenticated, Sale, Authorized
			 */
			update_post_meta( $order_id, '_SagePayTxType' , $data['TxType'] );

			// Delete any other details
			delete_post_meta( $order_id, '_SagePaySantizedCardDetails' );

			/**
			 * Store sanitized card details
			 */
			if( $this->sage_card_number != '' ) {

				$_SagePaySantizedCardDetails = array(
						"CardNumber" 		=>	'XXXX-XXXX-XXXX-'.substr( $this->sage_card_number,-4 ),
						"ExpiryDate"		=>	$this->sage_card_exp_month . $this->sage_card_exp_year,
						"CardType"			=>	$this->sage_card_type
					);

				// Add gift aid to card details so we can use it later if needed
				if( $data['BillingCountry'] == 'GB' && $this->sage_gift_aid_payment && $this->settings['giftaid'] == 'yes' ) {
					$_SagePaySantizedCardDetails['GiftAidPayment'] = '1';
				}

				// Add the new details
				update_post_meta( $order_id, '_SagePaySantizedCardDetails' , $_SagePaySantizedCardDetails );

			}

			// Soft Decline : https://developer-eu.elavon.com/docs/opayo-direct/sca-exemptions/soft-declines
			// If _opayo_soft_decline is set then Opayo has received a decline code of 65 (Mastercard) or 1A (Visa, Diners, Discover)
			// Resubmit the transaction but force a 3D Secure challenge
			$soft_decline = get_post_meta( $order_id, '_opayo_soft_decline', TRUE );
			if( isset( $soft_decline ) && $soft_decline != '' && in_array( $soft_decline, array('65','1A') ) ) {
				$data["Apply3DSecure"] = 1;
			}
			// Delete the _opayo_soft_decline post_meta
			delete_post_meta( $order_id, '_opayo_soft_decline' );

			// Remove empty values but leave in 0
			$data = array_filter( $data, 'strlen' );

			return $data;

		}

		/**
		 * [get_transaction_data description]
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		function get_transaction_data( $order_id ) {

			$order = wc_get_order( $order_id );

			// Set VPSProtocol - Protocol 4 requires a IPv4 IP Address, if customer is using IPv6 then switch to Protocol 3.00
			$this->vpsprotocol = $this->get_vpsprotocol_from_ipaddress( $this->vpsprotocol );
			
			// Build a VendorTxCode
    		$VendorTxCode = WC_Sagepay_Common_Functions::build_vendortxcode( $order, $this->id, $this->vendortxcodeprefix );

    		// Add the VendorTxCode to the order meta
    		$this->set_vendortxcode( $order_id, $VendorTxCode );

			$start = array(
				"VPSProtocol"		=>	$this->vpsprotocol,
				"TxType"			=>	$this->get_txtype( $order_id, $order->get_total() ),
				"Vendor"			=>	$this->vendor,
				"VendorTxCode" 		=>	$VendorTxCode,
				"Amount" 			=>	$this->get_amount( $order, $order->get_total() ),
				"Currency"			=>	WC_Sagepay_Common_Functions::get_order_currency( $order ),
				"Description"		=>	 __( 'Order', 'woocommerce-gateway-sagepay-form' ) . ' ' . str_replace( '#' , '' , $order->get_order_number() )
			);

			$billing_shipping = array(
				"BillingSurname"	=>	$this->limit_length( $order->get_billing_last_name(), 20 ),
				"BillingFirstnames" =>	$this->limit_length( $order->get_billing_first_name(), 20 ),
				"BillingCompany" 	=>	$this->limit_length( $order->get_billing_company(), 20 ),
				"BillingAddress1"	=>	$this->limit_length( $order->get_billing_address_1(), 50 ),
				"BillingAddress2"	=>	$this->limit_length( $order->get_billing_address_2(), 50 ),
				"BillingCity"		=>	$this->limit_length( $this->city( $order->get_billing_city() ), 40 ),
				"BillingPostCode"	=>	$this->limit_length( $this->billing_postcode( $order->get_billing_postcode() ), 10 ) ,
				"BillingCountry"	=>	$order->get_billing_country(),
				"BillingState"		=>	$this->limit_length( WC_Sagepay_Common_Functions::sagepay_state( $order->get_billing_country(), $order->get_billing_state() ), 2 ),
				"BillingPhone"		=>	$this->limit_length( $order->get_billing_phone(), 20 ),
				"DeliverySurname" 	=>	$this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverysurname', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_last_name' ), $order ), 20 ),
				"DeliveryFirstnames"=>	$this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryfirstname', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_first_name' ), $order ), 20 ),
				"DeliveryAddress1" 	=>	$this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryaddress1', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_address_1' ), $order ), 50 ),
				"DeliveryAddress2" 	=>	$this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryaddress2', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_address_2' ), $order ), 50 ),
				"DeliveryCity" 		=>	$this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverycity', $this->city( WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_city' ) ), $order ), 40 ),
				"DeliveryPostCode" 	=>	$this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverypostcode', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_postcode' ), $order ), 10 ),
				"DeliveryCountry" 	=>	apply_filters( 'woocommerce_sagepay_direct_deliverycountry', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_country' ), $order ),
				"DeliveryState" 	=>	$this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliverystate', 
														WC_Sagepay_Common_Functions::sagepay_state( 
															apply_filters( 'woocommerce_sagepay_direct_deliverycountry', WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_country' ), $order ), 
															WC_Sagepay_Common_Functions::check_shipping_address( $order, 'shipping_state' ) 
														), 
														$order ), 2 ),
				"DeliveryPhone" 	=>	$this->limit_length( apply_filters( 'woocommerce_sagepay_direct_deliveryphone', $order->get_billing_phone(), $order ), 20 ),
			);

			$billing_shipping = WC_Sagepay_Common_Functions::clean_args( $billing_shipping );

			$end = array(
				"CustomerEMail" 	=>	$order->get_billing_email(),
				"ClientIPAddress" 	=>	$this->get_ipaddress(),
				"AccountType" 		=>	$this->accounttype,
				"ReferrerID" 		=>	$this->referrerid,
				"Website" 			=>	site_url(),
				"Crypt" 			=>  MD5( $this->open_salt . $order->get_order_key() . $this->close_salt ),
			);

			// Protocol 4.00
			if( $this->vpsprotocol == '4.00' ) {

				$end["BrowserJavascriptEnabled"] 	= $this->browserJavascriptEnabled == 'true' ? 1 : 0;

				if( $end["BrowserJavascriptEnabled"] ) {
					$end["BrowserJavaEnabled"] 		= $this->browserJavaEnabled == 'true' ? 1 : 0;
					$end["BrowserColorDepth"] 		= $this->browserColorDepth;
	        		$end["BrowserScreenHeight"] 	= $this->browserScreenHeight;
	        		$end["BrowserScreenWidth"]		= $this->browserScreenWidth;
	        		$end["BrowserTZ"] 				= $this->browserTZ;
	        	}

	        	$end["BrowserAcceptHeader"]		= isset( $_SERVER['HTTP_ACCEPT'] ) ? $_SERVER['HTTP_ACCEPT'] : null;
	        	$end["BrowserLanguage"]			= isset( $this->browserLanguage ) && $this->browserLanguage != '' ? $this->browserLanguage : substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) ;
	        	$end["BrowserUserAgent"]		= isset( $this->browserUserAgent ) && $this->browserUserAgent != '' ? $this->browserUserAgent : $_SERVER['HTTP_USER_AGENT'];
	        	
	        	// $end["ThreeDSNotificationURL"] 	= $order->get_checkout_payment_url( true );
	        	$end["ThreeDSNotificationURL"] 	= add_query_arg( 'threedsecure', $start['VendorTxCode'], $this->successurl );
	        	$end["ChallengeWindowSize"] 	= $this->get_challenge_window_size( $this->browserScreenWidth, $this->browserScreenHeight );


	        	// If card details need to be saved regardless then set this to true
	        	// add_filter( 'opayo_direct_force_saved_card', 'opayo_direct_force_saved_card_true', 10, 2 );
	        	// function opayo_direct_force_saved_card_true( $save_card, $order ) {
	        	// 	return true;
	        	// }
	        	$force_saved_card 				= apply_filters( 'opayo_direct_force_saved_card', strpos( $order->get_checkout_payment_url( true ), 'subscription_renewal' ), $order );

	        	// Protocol 4.00
				if( ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $order ) ) || $force_saved_card ) {
					$end["InitiatedType"] 	= 'CIT';
					$end["COFUsage"] 		= 'FIRST';
					$end["MITType"] 		= 'UNSCHEDULED';
				}

			}

			// Customiseable fields
			$end['TransType'] = apply_filters( 'opayo_direct_custom_field_transtype', '01', $order );
			$end['VendorData'] = apply_filters( 'opayo_direct_custom_field_vendordata', '', $order );

			$data = $start + $billing_shipping + $end;

			if( $data['BillingCountry'] == 'GB' && $this->sage_gift_aid_payment && $this->settings['giftaid'] == 'yes' ) {
				$data['GiftAidPayment'] = '1';
			}

			return $data;
		}

		/**
		 * [get_meta_item description]
		 * @param  [type] $meta  [description]
		 * @param  [type] $order [description]
		 * @return [type]        [description]
		 */
		function get_meta_item( $meta, $order ) {
			return $order->get_meta( $meta, true );
		}

		/**
		 * [set_vendortxcode description]
		 * @param [type] $order_id     [description]
		 * @param [type] $VendorTxCode [description]
		 */
		function set_vendortxcode( $order_id, $VendorTxCode ) {
			update_post_meta( $order_id, '_VendorTxCode' , $VendorTxCode );
			update_post_meta( $order_id, '_RelatedVendorTxCode' , $VendorTxCode );
		}

		/**
		 * Return challenge window size
		 *
		 * 01 = 250 x 400
		 * 02 = 390 x 400
		 * 03 = 500 x 600
		 * 04 = 600 x 400
		 * 05 = Full screen
		 */
		function get_challenge_window_size( $width, $height ) {

			if( $width <= '250' ) {
				return '01';
			}

			if( $width <= '390' ) {
				return '02';
			}

			if( $width <= '500' ) {
				return '03';
			}

			if( $width <= '600' ) {
				return '04';
			}

			return '05';

		}

	} // End class
