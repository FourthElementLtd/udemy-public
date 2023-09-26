<?php
	/**
	 * Refunds for SagePay Direct
	 */
	class Sagepay_Direct_Process extends WC_Gateway_Sagepay_Direct {

		private $order_id;

		public function __construct( $order_id ) {

			parent::__construct();

			$this->order_id 	= $order_id;
			$this->settings 	= get_option( 'woocommerce_sagepaydirect_settings' );

		}
	
		function process() {

			// woocommerce order instance
           	$order  	= wc_get_order( $this->order_id );
           	$order_id 	= $this->order_id;

           	$sage_card_type 		= isset($_POST[$this->id . '-card-type']) ? wc_clean($_POST[$this->id . '-card-type']) : '';
			$sage_card_number 		= isset($_POST[$this->id . '-card-number']) ? wc_clean($_POST[$this->id . '-card-number']) : '';
			$sage_card_cvc 			= isset($_POST[$this->id . '-card-cvc']) ? wc_clean($_POST[$this->id . '-card-cvc']) : '';
			$sage_card_expiry		= isset($_POST[$this->id . '-card-expiry']) ? wc_clean($_POST[$this->id . '-card-expiry']) : false;
			$sage_card_expiry_mon	= isset($_POST[$this->id . '-card-expiry-month']) ? wc_clean($_POST[$this->id . '-card-expiry-month']) : false;
			$sage_card_expiry_year	= isset($_POST[$this->id . '-card-expiry-year']) ? wc_clean($_POST[$this->id . '-card-expiry-year']) : false;
			$sage_card_save_token	= isset($_POST['wc-sagepaydirect-new-payment-method']) ? wc_clean($_POST['wc-sagepaydirect-new-payment-method']) : false;
			$sage_card_token 		= isset($_POST['wc-sagepaydirect-payment-token']) ? wc_clean($_POST['wc-sagepaydirect-payment-token']) : false;
			$sage_gift_aid 			= isset($_POST['wc-sagepaydirect-gift-aid']) ? wc_clean($_POST['wc-sagepaydirect-gift-aid']) : false;

			// Format values
			$sage_card_number    	= str_replace( array( ' ', '-' ), '', $sage_card_number );

			if( $sage_card_expiry ) {
				$sage_card_exp_month 	= $this->get_card_expiry_date( $sage_card_expiry, 'month' );
				$sage_card_exp_year 	= $this->get_card_expiry_date( $sage_card_expiry, 'year' );
			} elseif( $sage_card_expiry_mon > 0 && $sage_card_expiry_year > 0 ) {
				$sage_card_exp_month 	= $sage_card_expiry_mon;
				$sage_card_exp_year 	= $this->get_card_expiry_date( $sage_card_expiry_year, 'year' );		
			} else {
				$sage_card_exp_month 	= '';
				$sage_card_exp_year  	= '';
			}

			// Build array of transaction variables
			$card_form = array( 
							"sage_card_number" 		=> $sage_card_number,
							"sage_card_exp_month"	=> $sage_card_exp_month,
							"sage_card_exp_year" 	=> $sage_card_exp_year,
							"sage_card_cvc" 		=> $sage_card_cvc,
							"sage_card_type" 		=> $sage_card_type,
							"sage_card_save_token" 	=> $sage_card_save_token,
							"sage_card_token"		=> $sage_card_token,
							"sage_gift_aid_payment"	=> $sage_gift_aid,
						);

			// Protocol 4 variables
			if( $this->vpsprotocol == '4.00' ) {
			    $card_form['browserJavaEnabled'] 		= isset( $_POST['browserJavaEnabled'] ) ? wc_clean(  $_POST['browserJavaEnabled'] ) : '';
			    $card_form['browserJavascriptEnabled'] 	= isset( $_POST['browserJavascriptEnabled'] ) ? wc_clean(  $_POST['browserJavascriptEnabled'] ) : '';
			    $card_form['browserLanguage'] 			= isset( $_POST['browserLanguage'] ) ? wc_clean(  $_POST['browserLanguage'] ) : '';
			    $card_form['browserColorDepth'] 		= isset( $_POST['browserColorDepth'] ) ? wc_clean(  $_POST['browserColorDepth'] ) : '';
			    $card_form['browserScreenHeight'] 		= isset( $_POST['browserScreenHeight'] ) ? wc_clean(  $_POST['browserScreenHeight'] ) : '';
			    $card_form['browserScreenWidth'] 		= isset( $_POST['browserScreenWidth'] ) ? wc_clean(  $_POST['browserScreenWidth'] ) : '';
			    $card_form['browserTZ'] 				= isset( $_POST['browserTZ'] ) ? wc_clean(  $_POST['browserTZ'] ) : '';
			    $card_form['browserUserAgent'] 			= isset( $_POST['browserUserAgent'] ) ? wc_clean(  $_POST['browserUserAgent'] ) : '';
			}

			// Force tokens in certain scenarios - $0 order and Protocol 4
			if( $order->get_total() == 0 && $this->vpsprotocol == '4.00' ) {
				$this->saved_cards 					= 'yes';
				$card_form['sage_card_save_token'] 	= TRUE;
			}

			// Force token for Subscriptions payments and Protocol 4.00
			if( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $order ) && $this->vpsprotocol == '4.00' ) {
				$this->saved_cards 					= 'yes';
				$card_form['sage_card_save_token'] 	= TRUE;
			}	

			// Lets deal with a change payment method using an existing token
			if( isset( $_GET['change_payment_method'] ) && class_exists( 'WC_Subscriptions' ) && isset( $card_form['sage_card_token'] ) && $card_form['sage_card_token'] !== 'new' && $card_form['sage_card_token'] != '' && $this->saved_cards == 'yes' ) {

				$token = new WC_Payment_Token_CC();
				$token = WC_Payment_Tokens::get( $card_form['sage_card_token'] );

				if( $token ) {

					// Build the data for Opayo
	           		$data 		= $this->build_query( $order, $card_form, 'existing_token' );

	           		// Send $data to Sage
	           		$payment 	= $this->sagepay_post( $data, $this->purchaseURL );

	           		// Process the response from Opayo 
	           		$sageresult = $this->process_response( $payment, $order );

	           		// Maybe process through 3D Secure
					if( isset( $sageresult['Status'] ) && $sageresult['Status'] == '3DAUTH' ) {

						// Add the subscription ID that needs the payment method changing to the result from Opayo
						$sageresult['change_payment_method'] = wc_clean( $_GET['change_payment_method'] );

						// Process the result from Opayo
						$this->process_response( $sageresult, $order );

						// Send through 3D Secure
						$sageresult = self::authorise_3dsecure( $order_id );

						// Stops Here.
					}

				} else {
					$sageresult = array( 
						"VPSProtocol" 	=> $this->vpsprotocol,
						"TxType" 		=> "EXISTINGTOKEN",
						"Token" 		=> "",
						"Status" 		=> "CHANGEPAYMENTMETHODEXISTINGTOKEN",
						"StatusDetail" 	=> __( 'An attempt was made to use an existing token. The existing token is not valid.', 'woocommerce-gateway-sagepay-form' )
					);
				}

				// Process sageresult
				$this->process_response( $sageresult, $order );
				// Everything stops here.
			}

			// Lets deal with a change payment method using a new token
			if( isset( $_GET['change_payment_method'] ) && class_exists( 'WC_Subscriptions' ) && $this->saved_cards == 'yes' ) {

				// Build $data for new totken
				$data = $this->build_query( $order, $card_form, 'new_token' );

				// Send the new card details to Opayo and get a token
				$sageresult = $this->sagepay_post( $data, $this->addtokenURL );

				if( isset( $sageresult['Status'] ) && $sageresult['Status'] === 'OK' ) {
					// Successful token
					self::save_token( $sageresult['Token'], $card_form["sage_card_type"], substr( $card_form["sage_card_number"], -4 ), $card_form["sage_card_exp_month"], $card_form["sage_card_exp_year"] );
					
					// Update Subscription with new token info
					update_post_meta( $order_id, '_SagePayDirectToken' , str_replace( array('{','}'),'',$sageresult['Token'] ) );

					// Send the new card details to Opayo and create a £0 order
					$data 		= $this->build_query( $order, $card_form, 'standard' );
					$sageresult = $this->sagepay_post( $data, $this->purchaseURL );

					// Maybe process through 3D Secure
					if( isset( $sageresult['Status'] ) && $sageresult['Status'] == '3DAUTH' ) {

						// Add the subscription ID that needs the payment method changing to the result from Opayo
						$sageresult['change_payment_method'] = wc_clean( $_GET['change_payment_method'] );

						// Process the result from Opayo
						$this->process_response( $sageresult, $order );

						// Send through 3D Secure
						$sageresult = self::authorise_3dsecure( $order_id );

						// Stops Here.
					}
				} else {
					// Token creation failed
					$sageresult = array( 
						"VPSProtocol" 	=> $this->vpsprotocol,
						"TxType" 		=> "EXISTINGTOKEN",
						"Token" 		=> "",
						"Status" 		=> "CHANGEPAYMENTMETHODEXISTINGTOKEN",
						"StatusDetail" 	=> __( 'An attempt was made to create a new token, attempt failed. Customer redirected to try again.', 'woocommerce-gateway-sagepay-form' )
					);

					// Process the result from Opayo
					$this->process_response( $sageresult, $order );
				}

				// Everything stops here.
				exit;

			}

           	if( $order->get_total() == 0 && $sage_card_token !== false && $sage_card_token !== 'new' && $this->saved_cards == 'yes' ) {
           		// If the order has a 0 AND we are using an existing token
				
				// Build the data for Sage
           		// $data 		= $this->build_query( $order, $card_form, 'update_token' );

           		// Send $data to Sage
           		// $sageresult = $this->process_response( $data, $order );

           		// Build the data for Sage
           		$data 		= $this->build_query( $order, $card_form, 'existing_token' );

           		// Send $data to Sage
           		$payment 	= $this->sagepay_post( $data, $this->purchaseURL );

           		// Process the response from Sage 
           		$sageresult = $this->process_response( $payment, $order );

			} elseif( $sage_card_token !== false && $sage_card_token !== 'new' && $this->saved_cards == 'yes' ) {
				// Using an existing token
				
				// Build the data for Sage
           		$data 		= $this->build_query( $order, $card_form, 'existing_token' );

           		// Send $data to Sage
           		$payment 	= $this->sagepay_post( $data, $this->purchaseURL );

           		// Process the response from Sage 
           		$sageresult = $this->process_response( $payment, $order );

           	} else {
   				// Just an order, using a card, nothing fancy.
           		if( ( $card_form['sage_card_save_token'] && $card_form['sage_card_type'] != 'PayPal' ) || $order->get_total() == 0 ) {
           			// Customer is saving these card details for using later or Free Trial Subscription, create a token
           			 
           			// Build the data for Sage
	           		$data 		= $this->build_query( $order, $card_form, 'new_token' );
	           		
					// Send $data to Sage
					$sageresult = $this->sagepay_post( $data, $this->addtokenURL );

					// Save the new token
					if( strtoupper( $sageresult['Status'] ) === 'OK' ) {

						self::save_token( $sageresult['Token'], $card_form["sage_card_type"], substr( $card_form["sage_card_number"], -4 ), $card_form["sage_card_exp_month"], $card_form["sage_card_exp_year"] );
					
						// Update Parent Order with new token info
						update_post_meta( $order_id, '_SagePayDirectToken' , str_replace( array('{','}'),'',$sageresult['Token'] ) );

						// Add order note
						$order->add_order_note( __( 'Customer saved card details', 'woocommerce-gateway-sagepay-form' ) );

					} else {

						// Add order note
						$order->add_order_note( __( 'Customer attempted to save card details. Attempt failed. ', 'woocommerce-gateway-sagepay-form' ) . '<br />' . $sageresult['Detail'] );

					}

           		}

           		// Build the data for Sage
           		$data = $this->build_query( $order, $card_form, 'standard' );

				// Send $data to Sage
				$sageresult = $this->sagepay_post( $data, $this->purchaseURL );

				// Process the response from Sage
				$sageresult = $this->process_response( $sageresult, $order );

			}

			return array(
    	       		'result'	=> $sageresult['result'],
    	       		'redirect'	=> $sageresult['redirect']
    	    	);

		}

	} // End class
