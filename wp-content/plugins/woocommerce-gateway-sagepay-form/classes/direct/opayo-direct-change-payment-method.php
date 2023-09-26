<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
    
	/**
	 * Process the payment form
	 */
	class Opayo_Direct_Change_Payment_Process extends WC_Gateway_Sagepay_Direct {

		public function __construct() {

			parent::__construct();

			$this->settings 	= get_option( 'woocommerce_sagepaydirect_settings' );

		}
	
		function process_order( $order_id ) {

			try {

				$order_id = wc_clean( $_GET['change_payment_method'] );

				// Get the WooCommerce order object
				$order = wc_get_order( $order_id );

				// Create VendorTxCode
				$vendortxcode = WC_Sagepay_Common_Functions::build_vendortxcode( $order, $this->id, $this->get_vendortxcodeprefix() );

				// Add the VendorTxCode to the order meta
    			$this->set_vendortxcode( $order_id, $vendortxcode );

				$opayo_card_type 		= isset($_POST[$this->id . '-card-type']) ? $this->opayo_wc_clean($_POST[$this->id . '-card-type']) : false;
				$opayo_card_number 		= isset($_POST[$this->id . '-card-number']) ? $this->opayo_wc_clean($_POST[$this->id . '-card-number']) : false;
				$opayo_card_cvc 		= isset($_POST[$this->id . '-card-cvc']) ? $this->opayo_wc_clean($_POST[$this->id . '-card-cvc']) : false;
				$opayo_card_expiry		= isset($_POST[$this->id . '-card-expiry']) ? $this->opayo_wc_clean($_POST[$this->id . '-card-expiry']) : false;
				$opayo_card_expiry_mon	= isset($_POST[$this->id . '-card-expiry-month']) ? $this->opayo_wc_clean($_POST[$this->id . '-card-expiry-month']) : false;
				$opayo_card_expiry_year	= isset($_POST[$this->id . '-card-expiry-year']) ? $this->opayo_wc_clean($_POST[$this->id . '-card-expiry-year']) : false;
				$opayo_card_save_token	= isset($_POST['wc-sagepaydirect-new-payment-method']) ? $this->opayo_wc_clean($_POST['wc-sagepaydirect-new-payment-method']) : false;
				$opayo_card_token 		= isset($_POST['wc-sagepaydirect-payment-token']) ? $this->opayo_wc_clean($_POST['wc-sagepaydirect-payment-token']) : false;

				// Set $cardholder for testing 3D Secure 2.0
				$cardholder = WC_Sagepay_Common_Functions::clean_sagepay_args( $order->get_billing_first_name() . ' ' .  $order->get_billing_last_name() );

				if( $this->get_status() != 'live' && $this->get_sagemagicvalue() != 'No Magic Value' ) {
					$cardholder = $this->get_sagemagicvalue();
				}

				$payment_form = array(
									"cardholder" 				=> $cardholder,
									"vendortxcode" 				=> $vendortxcode,
									"opayo_card_type" 			=> $opayo_card_type,
									"opayo_card_number" 		=> $opayo_card_number,
									"opayo_masked_card" 		=> substr( $opayo_card_number, -4 ),
									"opayo_card_cvc" 			=> $opayo_card_cvc,
									"opayo_card_expiry" 		=> $opayo_card_expiry,
									"opayo_card_expiry_mon" 	=> $opayo_card_expiry_mon,
									"opayo_card_expiry_year" 	=> $opayo_card_expiry_year,
									"opayo_card_save_token" 	=> $opayo_card_save_token,
									"opayo_card_token" 			=> $opayo_card_token, 
								);

				// Format values
				$payment_form["opayo_card_number"] = str_replace( array( ' ', '-' ), '', $payment_form["opayo_card_number"] );

				// Allow for old template file which uses text box for card expiry date
				if( $payment_form["opayo_card_expiry"] ) {
					$payment_form["opayo_card_expiry_mon"] 	= $this->get_card_expiry_date( $payment_form["opayo_card_expiry"], 'month' );
					$payment_form["opayo_card_expiry_year"] = $this->get_card_expiry_date( $payment_form["opayo_card_expiry"], 'year' );
				} else {
					// $payment_form["opayo_card_expiry_mon"] 	= $payment_form["opayo_card_expiry_mon"];
					$payment_form["opayo_card_expiry_year"] = $this->get_card_expiry_date( $payment_form["opayo_card_expiry_year"], 'year' );
				}

				if( isset( $payment_form["opayo_card_token"] ) && is_numeric( $payment_form["opayo_card_token"] ) ) {
					// Existing Token!
					
					$token = new WC_Payment_Token_CC();
					$token = WC_Payment_Tokens::get( $payment_form["opayo_card_token"] );

					// Get Customer ID
					$customer_id = $order->get_customer_id();

					if ( $token && $token->get_user_id() == $customer_id ) {

						// Get the basic $data array for the order 
						$data = $this->common_fields( $order, $order_id );

						// Add / Modify as required for token payment
						$data["CardHolder"] 	= $payment_form["cardholder"];
						$data["Amount"] 		= $this->get_amount( $order, $order->get_total() );
						$data["Token"] 			= str_replace( array('{','}'),'',$token->get_token() );
						$data["StoreToken"] 	= "1";

						$data["ApplyAVSCV2"]	= strlen( $payment_form["opayo_card_cvc"] ) === 0 ? '2' : '0';
						$data["CV2"] 			= $payment_form["opayo_card_cvc"];

						$data["Apply3DSecure"] 	= $this->get_secure();

						$data["InitiatedType"] 	= 'CIT';
						$data["COFUsage"] 		= 'SUBSEQUENT';

					} else {

						// Add Order Note
	                	$order->add_order_note( __( 'Customer attempted to pay with an existing token. The token is not available.', 'woocommerce-gateway-sagepay-form' ) );

						throw new Exception( __( 'There was an error. The payment method cannot be changed. Pease try again with a different method', 'woocommerce-gateway-sagepay-form' ) );

					}

				} elseif( isset( $payment_form["opayo_card_token"] ) && $payment_form["opayo_card_token"] === 'new' ) {
					// New Token!

					// Format values
					$payment_form["opayo_card_number"] = str_replace( array( ' ', '-' ), '', $payment_form["opayo_card_number"] );

					// Allow for old template file which uses text box for card expiry date
					if( $payment_form["opayo_card_expiry"] ) {
						$sage_card_exp_month 	= $this->get_card_expiry_date( $payment_form["opayo_card_expiry"], 'month' );
						$sage_card_exp_year 	= $this->get_card_expiry_date( $payment_form["opayo_card_expiry"], 'year' );
					} else {
						$sage_card_exp_month 	= $payment_form["opayo_card_expiry_mon"];
						$sage_card_exp_year 	= $this->get_card_expiry_date( $payment_form["opayo_card_expiry_year"], 'year' );
					}
					
					$data    = array(
		                "VPSProtocol"       => $this->get_vpsprotocol(),
		                "TxType"            => 'TOKEN',
		                "Vendor"            => $this->get_vendor(),
		                "Currency"          => get_woocommerce_currency(),
		                "Amount" 			=> $this->get_amount( $order, $order->get_total() ),
						"CardHolder" 		=> $payment_form["cardholder"],
						"CardNumber" 		=> $payment_form["opayo_card_number"],
						"ExpiryDate"		=> $payment_form["opayo_card_expiry_mon"] . $payment_form["opayo_card_expiry_year"],
						"CV2"				=> $payment_form["opayo_card_cvc"],
						"CardType"			=> $this->cc_type( $payment_form["opayo_card_number"], $payment_form["opayo_card_type"] ),
		            );

		            $card_form = array( 
		            	"sage_card_type" 		=> $payment_form["opayo_card_type"] ,
		            	"sage_card_number" 		=> substr( $payment_form["opayo_card_number"], -4 ), 
		            	"sage_card_exp_month" 	=> $sage_card_exp_month, 
		            	"sage_card_exp_year" 	=> $sage_card_exp_year
		            );

		            $new_token = $this->get_new_payment_method( $data );

		            if( $new_token ) {

		            	// Get the basic $data array for the order 
						$data = $this->common_fields( $order, $order_id );

						// Add / Modify as required for token payment
						$data["CardHolder"] 	= $payment_form["cardholder"];
						$data["Token"] 			= str_replace( array('{','}'),'',$new_token );
						$data["StoreToken"] 	= "1";

						$data["ApplyAVSCV2"]	= strlen( $payment_form["opayo_card_cvc"] ) === 0 ? '2' : '0';
						$data["CV2"] 			= $payment_form["opayo_card_cvc"];

						$data["Apply3DSecure"] 	= $this->get_secure();

						$data["InitiatedType"] 	= 'CIT';
						$data["COFUsage"] 		= 'SUBSEQUENT';
						$data["Amount"] 		= get_post_meta( $order_id, '_order_total', TRUE );

		            } else {
		            	throw new Exception( __( 'There was a problem adding your payment method', 'woocommerce-gateway-sagepay-form' ) );
		            }

				} else {
					// No token ;/
					// Get the basic $data array for the order 
					$data = $this->common_fields( $order, $order_id );

					$transaction_type     = array(
		                "TxType"            => 'AUTHENTICATE',
		                "Amount" 			=> get_post_meta( $order_id, '_order_total', TRUE ),
						"CardHolder" 		=> $payment_form["cardholder"],
						"CardNumber" 		=> $payment_form["opayo_card_number"],
						"ExpiryDate"		=> $payment_form["opayo_card_expiry_mon"] . $payment_form["opayo_card_expiry_year"],
						"CV2"				=> $payment_form["opayo_card_cvc"],
						"CardType"			=> $this->cc_type( $payment_form["opayo_card_number"], $payment_form["opayo_card_type"] ),
						"InitiatedType" 	=> 'CIT',
						"COFUsage" 			=> 'FIRST',
		            );

		            $data = $transaction_type + $data;

		        }

				// Filter the args if necessary, use with caution
            	$data = apply_filters( 'woocommerce_sagepay_direct_data', $data, $order );

				// Send $data to Opayo
				$result = $this->sagepay_post( $data, $this->purchaseURL );

				// Check $result for API errors
				if( is_wp_error( $result ) ) {
					$sageresult = $result->get_error_message();
					throw new Exception( __( 'Processing error <pre>' . print_r( $sageresult, TRUE ) . '</pre>', 'woocommerce-gateway-sagepay-form' ) );
				} else {
					$sageresult = $this->sageresponse( $result['body'] );

					// Testing
					// $sageresult['Status'] = 'OK';

					switch( strtoupper( $sageresult['Status'] ) ) {
		                case 'OK':
		                case 'REGISTERED':
		                case 'AUTHENTICATED':

		                	// Old payment method
	                        $old_method = get_post_meta( $order_id, '_payment_method', TRUE );

	                        // Remove the token, just in case
				            delete_post_meta( $order_id, '_SagePayDirectToken' );

	                        // Payment method change has been successfull, if the old payment method was PayPal then it needs to be cancelled at PayPal
	                        if( class_exists( 'WCS_PayPal_Status_Manager' ) && $old_method == 'paypal') {
	                        	$subscription       			= new WC_Subscription( $order_id );
	                            $payal_subscription_cancelled 	= WCS_PayPal_Status_Manager::cancel_subscription( $subscription );
	                        }

		                	// Update the order meta with the token, if there is one
		                	if( isset( $opayoresult["Token"] ) && strlen( $opayoresult["Token"] ) != 0) {

		                		if( $opayoresult["opayo_card_token"] === 'new' ) {
			                		// Save the new token
									$this->save_token( 
											$opayoresult["Token"], 
											$opayoresult["opayo_card_type"], 
											$opayoresult["opayo_masked_card"], 
											$opayoresult["opayo_card_expiry_mon"], 
											$opayoresult["opayo_card_expiry_year"]
										);
								}

								update_post_meta( $order_id, '_SagePayDirectToken' , $opayoresult["Token"] );

							}

							if( isset( $sageresult['Status'] ) && $sageresult['Status'] === 'AUTHENTICATED' ) {
								// Set the data needed to release this amount later
		                        $opayo_free_trial = array( 
		                            "TxType"        => "AUTHENTICATED",
		                            "VPSProtocol"   => isset( $sageresult['VPSProtocol'] ) ? $sageresult['VPSProtocol'] : '',
				                    "VPSTxId"  		=> isset( $sageresult['VPSTxId'] ) ? $sageresult['VPSTxId'] : '',
		                            "VendorTxCode"  => isset( $data['VendorTxCode'] ) ? $data['VendorTxCode'] : '',
		                            "SecurityKey"   => isset( $sageresult['SecurityKey'] ) ? $sageresult['SecurityKey'] : ''
		                        );

		                        update_post_meta( $order_id, '_opayo_free_trial', $opayo_free_trial );

		                    }

	                    	// Delete related transaction details from subscription to force the new method
                    		delete_post_meta( $order_id, '_RelatedVPSTxId' );
                    		delete_post_meta( $order_id, '_RelatedSecurityKey' );
                    		delete_post_meta( $order_id, '_RelatedTxAuthNo' );
                    		delete_post_meta( $order_id, '_RelatedVendorTxCode' );

							// Delete _sage_3ds
		                    delete_post_meta( $order_id, '_sage_3ds' );

							// Create success message for customer
                        	if( is_callable( 'wc_add_notice') ) {
								wc_add_notice( __('Your payment method has been updated.', 'woocommerce-gateway-sagepay-form'), 'success' );
							}

							// Redirect customer
							wp_redirect( wc_get_endpoint_url( 'view-subscription', $order_id, wc_get_page_permalink( 'myaccount' ) ) );
                			exit;

		            	break;

						case '3DAUTH':

							// Old payment method
	                        $old_method = get_post_meta( $order_id, '_payment_method', TRUE );

	                        // Payment method change has been successfull, if the old payment method was PayPal then it needs to be cancelled at PayPal
	                        if( class_exists( 'WCS_PayPal_Status_Manager' ) && $old_method == 'paypal') {
	                        	$subscription       			= new WC_Subscription( $order_id );
	                            $payal_subscription_cancelled 	= WCS_PayPal_Status_Manager::cancel_subscription( $subscription );

	                            // If we don't do this then WC Subs will hold onto PayPal and the 3D Secure process won't happen :/
	                            update_post_meta( $order_id, '_payment_method', 'sagepaydirect' );
	                        }

							$sage_3ds 							= array();
                            $sage_3ds                   		= $sageresult;
                            $sage_3ds["TermURL"]        		= $this->append_url( $order->get_checkout_payment_url( true ) );
                            $sage_3ds["Complete3d"]     		= $this->append_url( $order->get_checkout_payment_url( true ) );
                            $sage_3ds['VendorTxCode']   		= get_post_meta( $order_id, '_VendorTxCode', TRUE );
							$sage_3ds['Token']   				= isset( $data['Token'] ) ? $data['Token'] : NULL;
                            $sage_3ds['change_payment_method'] 	= $order_id;

                            // Add the card details so we can save them later
                            $sage_3ds['opayo_card_type'] 		= $payment_form["opayo_card_type"];
                            $sage_3ds['opayo_masked_card'] 		= $payment_form["opayo_masked_card"];
                            $sage_3ds['opayo_card_expiry_mon'] 	= $payment_form["opayo_card_expiry_mon"];
                            $sage_3ds['opayo_card_expiry_year'] = $payment_form["opayo_card_expiry_year"];
                            $sage_3ds['opayo_card_token'] 		= $payment_form["opayo_card_token"];
                            $sage_3ds['TxType'] 				= $data['TxType'];

                            // Use add_post_meta - can't be overwritten see :)
                            add_post_meta( $order_id, '_sage_3ds', $sage_3ds, TRUE );

                            // Go to the pay page for 3d securing
                            $sageresult['result']   = 'success';
                            $sageresult['redirect'] = $this->append_url( $order->get_checkout_payment_url( true ) );
                            $sageresult['redirect'] = add_query_arg( 'process_threedsecure', true, $sageresult['redirect'] );
                            $sageresult['redirect'] = add_query_arg( 'update_payment_method', $order_id, $sageresult['redirect'] );

                            // Redirect customer for 3D Secure processing
							return $sageresult;

						break;

		                default :

	                        $update_order_status = apply_filters( 'woocommerce_opayo_direct_failed_order_status', 'failed', $order, $sageresult );
	                      
	                        // Add Order Note
	                        $order->add_order_note( sprintf( __( 'Payment failed: %s', 'woocommerce-gateway-sagepay-form' ), $sageresult ) );

	                        // Update the order status
	                        $order->update_status( $update_order_status );

	                        // Soft Decline
	                        if( isset( $sageresult['DeclineCode'] ) && in_array( $sageresult['DeclineCode'], array('65','1A') ) ) {
	                            update_post_meta( $order_id, '_opayo_soft_decline', $sageresult['DeclineCode'] );
	                        }

	                        // Update Order Meta
	                        $this->update_order_meta( $sageresult, $order_id );

		                    throw new Exception( __('Payment error. Please try again, your card has not been charged.', 'woocommerce-gateway-sagepay-form') . ': ' . $sageresult['StatusDetail'] );

		            }

				}

			} catch( Exception $e ) {

				// Clear any stored values, necessary for the retries
	    		delete_post_meta( $order_id, '_sage_3ds' );
	    		delete_post_meta( $order_id, '_VendorTxCode' );
	    		delete_post_meta( $order_id, '_RelatedVendorTxCode' );

	        	// Add the error message
				if( is_callable( 'wc_add_notice' ) ) {
					wc_add_notice( $e->getMessage(), 'error' );
				}
				return false;

			}

		}

		function common_fields( $order, $order_id ) {

			// Start the common 3D Secure functions class
            $common_threeds = new WC_Opayo_Common_Threeds_Functions();

            // Get the $vendortxcode from the order meta
			$vendortxcode = get_post_meta( $order_id, '_VendorTxCode', TRUE );

			$start = array(
				"VPSProtocol"		=>	$this->get_vpsprotocol(),
				"TxType"			=>	$this->get_txtype( $order_id, $order->get_total() ),
				"Vendor"			=>	$this->get_vendor(),
				"VendorTxCode" 		=>	$vendortxcode,
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
				"CustomerEMail" 			=> $order->get_billing_email(),
				"ClientIPAddress" 			=> $common_threeds::get_ipaddress( $this->get_nullipaddress() ),
				"AccountType" 				=> $this->get_accounttype(),
				"ReferrerID" 				=> $this->get_referrerid(),
				"Website" 					=> site_url(),
				"BrowserJavascriptEnabled" 	=> wc_clean( $_POST['browserJavascriptEnabled'] ) == 'true' ? 1 : 0
			);

			if( $end["BrowserJavascriptEnabled"] ) {
				$end["BrowserJavaEnabled"] 		= wc_clean( $_POST['browserJavaEnabled'] ) == 'true' ? 1 : 0;
				$end["BrowserColorDepth"] 		= wc_clean( $_POST['browserColorDepth'] );
        		$end["BrowserScreenHeight"] 	= wc_clean( $_POST['browserScreenHeight'] );
        		$end["BrowserScreenWidth"]		= wc_clean( $_POST['browserScreenWidth'] );
        		$end["BrowserTZ"] 				= wc_clean( $_POST['browserTZ'] );
        	}

        	$end["BrowserAcceptHeader"]		= isset( $_SERVER['HTTP_ACCEPT'] ) ? $_SERVER['HTTP_ACCEPT'] : null;
        	$end["BrowserLanguage"]			= isset( $_POST['browserLanguage'] ) && $_POST['browserLanguage'] != '' ? wc_clean( $_POST['browserLanguage'] ) : substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) ;
        	$end["BrowserUserAgent"]		= isset( $_POST['browserUserAgent'] ) && $_POST['browserUserAgent'] != '' ? wc_clean( $_POST['browserUserAgent'] ) : $_SERVER['HTTP_USER_AGENT'];
        	
        	$end["ThreeDSNotificationURL"] 	= add_query_arg( array( 'threedsecure' => $vendortxcode, 'update_payment_method' => $order_id ), $order->get_checkout_payment_url( true ) );
        	$end["ChallengeWindowSize"] 	= $this->get_challenge_window_size( $end["BrowserScreenWidth"], $end["BrowserScreenHeight"] );

        	// Customiseable fields
			$end['TransType'] = apply_filters( 'opayo_direct_custom_field_transtype', '01', $order );
			$end['VendorData'] = apply_filters( 'opayo_direct_custom_field_vendordata', '', $order );

			$data = $start + $billing_shipping + $end;

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

			return $data;

		}

		/**
		 * [get_new_payment_method description]
		 * @param  [type] $data      [description]
		 * @param  [type] $card_form [description]
		 * @return [type]            [description]
		 */
		function get_new_payment_method( $data ) {

			// Send the new card details to Opayo and get a token
			$result = $this->sagepay_post( $data, $this->addtokenURL );

			// Check $result for API errors
			if( is_wp_error( $result ) ) {
				$sageresult = $result->get_error_message();
				throw new Exception( __( 'Processing error <pre>' . print_r( $sageresult, TRUE ) . '</pre>', 'woocommerce-gateway-sagepay-form' ) );
			} else {
				$sageresult = $this->sageresponse( $result['body'] );

				// Testing
				// $sageresult['Status'] = 'INVALID';

				if( isset( $sageresult['Status'] ) && $sageresult['Status'] === 'OK' ) {
					
					return $sageresult['Token'];

				}

			}

			return false;
		}

	} // End class
