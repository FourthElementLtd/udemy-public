<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
    
	/**
	 * Process the payment form
	 */
	class Opayo_Direct_Process extends WC_Gateway_Sagepay_Direct {

		public function __construct() {

			parent::__construct();

			$this->settings 	= get_option( 'woocommerce_sagepaydirect_settings' );

		}
		
		/**
		 * { function_description }
		 *
		 * @param      <type>     $order_id  The order identifier
		 *
		 * @throws     Exception  (description)
		 */
		function process_order( $order_id ) {

			try {

				// Get the WooCommerce order object
				$order = wc_get_order( $order_id );

				// Create VendorTxCode
				$vendortxcode = WC_Sagepay_Common_Functions::build_vendortxcode( $order, $this->id, $this->get_vendortxcodeprefix() );

				// Add the VendorTxCode to the order meta
    			$this->set_vendortxcode( $order_id, $vendortxcode );

				$opayo_card_type 		= isset($_POST[$this->id . '-card-type']) ? wc_clean($_POST[$this->id . '-card-type']) : '';
				$opayo_card_number 		= isset($_POST[$this->id . '-card-number']) ? wc_clean($_POST[$this->id . '-card-number']) : '';
				$opayo_card_cvc 		= isset($_POST[$this->id . '-card-cvc']) ? wc_clean($_POST[$this->id . '-card-cvc']) : '';
				$opayo_card_expiry		= isset($_POST[$this->id . '-card-expiry']) ? wc_clean($_POST[$this->id . '-card-expiry']) : false;
				$opayo_card_expiry_mon	= isset($_POST[$this->id . '-card-expiry-month']) ? wc_clean($_POST[$this->id . '-card-expiry-month']) : false;
				$opayo_card_expiry_year	= isset($_POST[$this->id . '-card-expiry-year']) ? wc_clean($_POST[$this->id . '-card-expiry-year']) : false;
				$opayo_card_save_token	= isset($_POST['wc-sagepaydirect-new-payment-method']) && $_POST['wc-sagepaydirect-new-payment-method'] === 'true' ? wc_clean($_POST['wc-sagepaydirect-new-payment-method']) : false;
				$opayo_card_token 		= isset($_POST['wc-sagepaydirect-payment-token']) ? wc_clean($_POST['wc-sagepaydirect-payment-token']) : false;
				$opayo_gift_aid_payment = isset($_POST['wc-sagepaydirect-gift-aid']) ? wc_clean($_POST['wc-sagepaydirect-gift-aid']) : false;

				// Set $cardholder for testing 3D Secure 2.0
				$cardholder = WC_Sagepay_Common_Functions::clean_sagepay_args( $order->get_billing_first_name() . ' ' .  $order->get_billing_last_name() );

				if( $this->get_status() != 'live' && $this->get_sagemagicvalue() != 'No Magic Value' ) {
					$cardholder = $this->get_sagemagicvalue();
				}
				
				// Get the base fields needed for all transactions
				$common_fields = $this->common_fields( $order, $order_id );

				// Get the transaction specific fields
				if( isset( $opayo_card_type ) && strtoupper($opayo_card_type) == 'PAYPAL' ) {

					// Just a card number transaction
					$transaction_type = array( 
						"CardHolder" 	=>	$cardholder,
						"CardType"		=>	$this->cc_type( $opayo_card_number, $opayo_card_type ),
						"ApplyAVSCV2" 	=>	$this->get_cvv(),
						"Apply3DSecure" =>	$this->get_secure(),
					);

					$paypal_successurl = add_query_arg( 'vtx', $vendortxcode, $this->append_url( $order->get_checkout_payment_url( true ) ) );
					$transaction_type["PayPalCallbackURL"] = apply_filters( 'woocommerce_sagepaydirect_successurl', $paypal_successurl, $order_id );

					$sage_3dsecure['Complete3d'] = $this->append_url( $order->get_checkout_payment_url( true ) );

					if( $this->get_billingagreement() == "1" ) {
						$transaction_type["BillingAgreement"] =	$this->billingagreement;
					}

					// Add some order notes for tracing problems
					$order->add_order_note( sprintf( __( 'Payment method: %s', 'woocommerce-gateway-sagepay-form' ), strtoupper($opayo_card_type) ) );

				} elseif ( $this->get_saved_cards() == 'yes' && is_numeric( $opayo_card_token ) ) {
					// Existing token transaction

					$token = new WC_Payment_Token_CC();
					$token = WC_Payment_Tokens::get( $opayo_card_token );

					// Get Customer ID
					$customer_id = $order->get_customer_id();

					if ( $token && $token->get_user_id() == $customer_id ) {

						$transaction_type = array( 
							"CardHolder" 	=> $cardholder,
							"Token" 		=> str_replace( array('{','}'),'',$token->get_token() ),
							"StoreToken" 	=> "1",
							"ApplyAVSCV2"	=> strlen( $opayo_card_cvc ) === 0 ? '2' : '0',
							"CV2" 			=> $opayo_card_cvc,
							"Apply3DSecure"	=> $this->get_secure(),
							"InitiatedType" => 'CIT',
							"COFUsage" 		=> 'SUBSEQUENT',
						);

						// Update the order meta with the token
						update_post_meta( $order_id, '_SagePayDirectToken' , $transaction_type['Token'] );

						// Add some order notes for tracing problems
						$order->add_order_note( sprintf( __( 'Payment method: %s', 'woocommerce-gateway-sagepay-form' ), 'Existing token' ) );

					} else {
						throw new Exception( __( 'This payment method is invalid, please try a different payment method.', 'woocommerce-gateway-sagepay-form' ) );
					}

				} else {

					// Set $new_token to false;
					$new_token = false;

					// Clean up card number
					$opayo_card_number    	= str_replace( array( ' ', '-' ), '', $opayo_card_number );

					// Allow for old template file which uses text box for card expiry date
					if( $opayo_card_expiry ) {
						$opayo_card_exp_month 	= $this->get_card_expiry_date( $opayo_card_expiry, 'month' );
						$opayo_card_exp_year 	= $this->get_card_expiry_date( $opayo_card_expiry, 'year' );
					} else {
						$opayo_card_exp_month 	= $opayo_card_expiry_mon;
						$opayo_card_exp_year 	= $this->get_card_expiry_date( $opayo_card_expiry_year, 'year' );
					}

					// New token required, saving the card details
					if ( $this->get_saved_cards() == 'yes' && $opayo_card_save_token ) {

						$data    = array(
			                "VPSProtocol"       => $this->get_vpsprotocol(),
			                "TxType"            => 'TOKEN',
			                "Vendor"            => $this->get_vendor(),
			                "Currency"          => WC_Sagepay_Common_Functions::get_order_currency( $order ),
							"CardHolder" 		=> $cardholder,
							"CardNumber" 		=> $opayo_card_number,
							"ExpiryDate"		=> $opayo_card_exp_month . $opayo_card_exp_year,
							"CV2"				=> $opayo_card_cvc,
							"CardType"			=> $this->cc_type( $opayo_card_number, $opayo_card_type ),
			            );

			            $card_form = array( 
			            	"sage_card_type" 		=> $opayo_card_type ,
			            	"sage_card_number" 		=> substr( $opayo_card_number, -4 ), 
			            	"sage_card_exp_month" 	=> $opayo_card_exp_month, 
			            	"sage_card_exp_year" 	=> $opayo_card_exp_year
			            );

			            $new_token = $this->get_new_token( $data, $order, $card_form );
			        }

			        // Check if we have a valide $new_token
			        if( $new_token ) {

			        	// Add some order notes for tracing problems
						$order->add_order_note( sprintf( __( 'Payment method: %s', 'woocommerce-gateway-sagepay-form' ), 'New token' ) );

						// This is now a token transaction!
			            $transaction_type = array( 
							"CardHolder" 	=> $cardholder,
							"Token" 		=> $new_token,
							"StoreToken" 	=> "1",
							"ApplyAVSCV2"	=> strlen( $opayo_card_cvc ) === 0 ? '2' : '0',
							"CV2" 			=> $opayo_card_cvc,
							"Apply3DSecure"	=> $this->get_secure(),
							"InitiatedType" => 'CIT',
							"COFUsage" 		=> 'SUBSEQUENT',
						);

					} else {

						// Just a card number transaction
						$transaction_type = array( 
							"CardHolder" 	=>	$cardholder,
							"CardNumber" 	=>	$opayo_card_number,
							"ExpiryDate"	=>	$opayo_card_exp_month . $opayo_card_exp_year,
							"CV2"			=>	$opayo_card_cvc,
							"CardType"		=>	$this->cc_type( $opayo_card_number, $opayo_card_type ),
							"ApplyAVSCV2" 	=>	$this->get_cvv(),
							"Apply3DSecure" =>	$this->get_secure()
						);

						// Add some order notes for tracing problems
						$order->add_order_note( sprintf( __( 'Payment method: %s', 'woocommerce-gateway-sagepay-form' ), $transaction_type['CardType'] ) );

			        	// add_filter( 'opayo_direct_force_saved_card', 'opayo_direct_force_saved_card_true', 10, 2 );
			        	// function opayo_direct_force_saved_card_true( $save_card, $order ) {
			        	// 	return true;
			        	// }
			        	$force_saved_card = apply_filters( 'opayo_direct_force_saved_card', strpos( $order->get_checkout_payment_url( true ), 'subscription_renewal' ), $order );

			        	// Protocol 4.00
						if( ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $order ) ) || $force_saved_card ) {
							$transaction_type["InitiatedType"] 	= 'CIT';
							$transaction_type["COFUsage"] 		= 'FIRST';
							$transaction_type["MITType"] 		= 'UNSCHEDULED';
						}

					}

				}

				// Combine everything, ready to send to Opayo!
				$data = $common_fields + $transaction_type;

				if( $data['BillingCountry'] == 'GB' && $opayo_gift_aid_payment && $this->settings['giftaid'] == 'yes' ) {
					$data['GiftAidPayment'] = '1';
				}

				// Force basket type to non-XML if using PayPal - PayPal transactions fail if using XML basket.
				$basketoption = ( isset( $data["CardType"] ) && strtoupper( $data["CardType"] ) == 'PAYPAL' ) ? 1 : $this->get_basketoption();

				// Add the basket
				$basket = WC_Sagepay_Common_Functions::get_basket( $basketoption, $order_id );
				if ( $basket != NULL ) {
					if ( $basketoption == 1 ) {
						$data["Basket"] = $basket;
					} elseif ( $basketoption == 2 ) {
						$data["BasketXML"] = $basket;
					}
				}

				// Get CustomerXML object
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

				// Filter the args if necessary, use with caution
            	$data = apply_filters( 'woocommerce_sagepay_direct_data', $data, $order );

            	/**
				 * Store TxType for future checking
				 * This will be useful for checking Authenticated, Sale, Authorized
				 */
				update_post_meta( $order_id, '_SagePayTxType' , $data['TxType'] );

				// Send $data to Opayo
				$result = $this->sagepay_post( $data, $this->purchaseURL );

				// Check $result for API errors
				if( is_wp_error( $result ) ) {
					$sageresult = $result->get_error_message();
					throw new Exception( __( 'Processing error <pre>' . print_r( $sageresult, TRUE ) . '</pre>', 'woocommerce-gateway-sagepay-form' ) );
				} else {
					$sageresult = $this->sageresponse( $result['body'] );

					// Testing
					// $sageresult['Status'] = 'ERROR';
					// $sageresult['StatusDetail'] = '3079:IP Address Error';
					
					// Add some order notes for tracing problems
					$order->add_order_note( sprintf( __( 'Opayo Status: %s', 'woocommerce-gateway-sagepay-form' ), $sageresult['Status'] ) );

					switch( strtoupper( $sageresult['Status'] ) ) {
		                case 'OK':
		                case 'REGISTERED':
		                case 'AUTHENTICATED':

		                	// Store the result array from Opayo as early as possible
	                        $this->update_order_meta( $sageresult, $order_id );

	                        // Set the order status as early as possible
                    		$order->payment_complete( $sageresult['VPSTxId'] );

	                        // Maybe update the subscription 
	                        $this->update_subscription_meta_maybe( $sageresult, $order_id );

                    		// Add the order note
                    		$this->add_order_note( __('Payment successful', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

                    		$TransactionType = get_post_meta( $order_id, '_SagePayTxType', TRUE );

							if ( class_exists('WC_Pre_Orders') && WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) && WC_Pre_Orders_Order::order_will_be_charged_upon_release( $order_id ) ) {
						        // mark order as pre-ordered / reduce order stock
						        WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );
						    } elseif ( isset( $sageresult['FraudResponse'] ) && ( $sageresult['FraudResponse'] === 'DENY' || $sageresult['FraudResponse'] === 'CHALLENGE' ) ) {
						        // Mark for fraud screening
						        $order->update_status( 'fraud-screen', _x( 'Opayo Fraud Response ', 'woocommerce-gateway-sagepay-form' ) . $sageresult['FraudResponse'] . _x( '. Login to MySagePay and check this order before shipping.', 'woocommerce-gateway-sagepay-form' ) );
						    } elseif ( $sageresult['Status'] === 'AUTHENTICATED' || $sageresult['Status'] === 'REGISTERED' || ( isset($TransactionType) && $TransactionType == 'DEFERRED' ) ) {
						        $order->update_status( 'authorised', _x( 'Payment authorised, you will need to capture this payment before shipping. Use the "Capture Authorised Payment" option in the "Order Actions" dropdown.<br /><br />', 'woocommerce-gateway-sagepay-form' ) );
						    }

	                        $sageresult['result']   = 'success';
	                        $sageresult['redirect'] = $this->append_url( $order->get_checkout_order_received_url() );

	                        return $sageresult;

		                break;

						case '3DAUTH':

                        	$sage_3ds 					= array();
                            $sage_3ds                   = $sageresult;
                            $sage_3ds["TermURL"]        = $this->append_url( $order->get_checkout_payment_url( true ) );
                            $sage_3ds["Complete3d"]     = $this->append_url( $order->get_checkout_payment_url( true ) );
                            $sage_3ds['VendorTxCode']   = get_post_meta( $order_id, '_VendorTxCode', TRUE );

                            if( isset( $sageresult['change_payment_method'] ) && $sageresult['change_payment_method'] != "" ) {
                                $sage_3ds['change_payment_method'] = $sageresult['change_payment_method'];
                            }

                            // Use add_post_meta - can't be overwritten see :)
                            add_post_meta( $order_id, '_sage_3ds', $sage_3ds, TRUE );

                            // Go to the pay page for 3d securing
                            $sageresult['result']   = 'success';
                            $sageresult['redirect'] = $this->append_url( $order->get_checkout_payment_url( true ) );
                            $sageresult['redirect'] = add_query_arg( 'process_threedsecure', true, $sageresult['redirect'] );

		                    return $sageresult;
		                
		                break;

		                case 'PPREDIRECT':

		                    // Go to paypal
		                    $sageresult['result']   = 'success';
		                    $sageresult['redirect'] = $sageresult['PayPalRedirectURL'];
		                    
		                    // Add order note
		                    $this->add_order_note( __('Payment authorised at PayPal. Sending to Opayo for completion ', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

		                    return $sageresult;
		                
		                break;

		                case 'INVALID':
		                case 'NOTAUTHED':
		                case 'MALFORMED':
		                case 'ERROR':

		                	if( isset($sageresult['StatusDetail']) ) {
			                	$opayo_status_detail_code = substr( $sageresult['StatusDetail'], 0, 4 );
			                	// Log the Client IP Address
			                	if( $opayo_status_detail_code == '3079' ) {
			                		$order->add_order_note( sprintf( __( 'Client IP Address: %s', 'woocommerce-gateway-sagepay-form'), $data['ClientIPAddress'] ) );
			                	}
		                	}

	                        $update_order_status = apply_filters( 'woocommerce_opayo_direct_failed_order_status', 'failed', $order, $sageresult );
	                      
	                        // Add Order Note
	                        $this->add_order_note( __('Payment failed', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

	                        // Update the order status
	                        $order->update_status( $update_order_status );

	                        // Soft Decline
	                        if( isset( $sageresult['DeclineCode'] ) && in_array( $sageresult['DeclineCode'], array('65','1A') ) ) {
	                            update_post_meta( $order_id, '_opayo_soft_decline', $sageresult['DeclineCode'] );
	                        }

	                        // Update Order Meta
	                        $this->update_order_meta( $sageresult, $order_id );

		                    throw new Exception( __('Payment error. Please try again, your card has not been charged.', 'woocommerce-gateway-sagepay-form') . ': ' . $sageresult['StatusDetail'] );

		                break;

		                case 'REJECTED':

		                    $update_order_status = apply_filters( 'woocommerce_opayo_direct_failed_order_status', 'failed', $order, $sageresult );

		                    // Add Order Note
		                    $this->add_order_note( __('Payment failed, there was a problem with 3D Secure or Address Verification', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

		                    // Update the order status
		                    $order->update_status( $update_order_status );

		                    // Update Order Meta
		                    $this->update_order_meta( $sageresult, $order_id );

		                    throw new Exception( __('Payment error.<br />A problem when verifying your card, please check your details and try again.<br />Your card has not been charged.', 'woocommerce-gateway-sagepay-form') . ': ' . $sageresult['StatusDetail'] );

		                break;

		                default :

	                        $update_order_status = apply_filters( 'woocommerce_opayo_direct_failed_order_status', 'failed', $order, $sageresult );
	                      
	                        // Add Order Note
	                        $this->add_order_note( __('Payment failed', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

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

		/**
		 * { function_description }
		 *
		 * @param      <type>     $order     The order
		 * @param      <type>     $order_id  The order identifier
		 *
		 * @return     array|int  ( description_of_the_return_value )
		 */
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
				"BillingPostCode"	=>	$this->limit_length( $this->billing_postcode( $order->get_billing_postcode() ), 10 ),
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
				"ClientIPAddress" 			=> $common_threeds::check_ipaddress( $order->get_customer_ip_address(), $this->get_nullipaddress() ),
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
        	$end["BrowserLanguage"]			= $this->get_BrowserLanguage();
        	$end["BrowserUserAgent"]		= $this->get_BrowserUserAgent();
        	
        	$end["ThreeDSNotificationURL"] 	= add_query_arg( 'threedsecure', $vendortxcode, $order->get_checkout_payment_url( true ) );
        	$end["ChallengeWindowSize"] 	= $this->get_challenge_window_size( $end["BrowserScreenWidth"], $end["BrowserScreenHeight"] );

        	// Customiseable fields
			$end['TransType']  = apply_filters( 'opayo_direct_custom_field_transtype', '01', $order );
			$end['VendorData'] = apply_filters( 'opayo_direct_custom_field_vendordata', '', $order );
			$end['Reference']  = apply_filters( 'opayo_direct_custom_field_reference', $order->get_order_number(), $order );

			$data = $start + $billing_shipping + $end;

			// Temporarily add IP address info to order notes
			$order->add_order_note( __( 'Customer IP Address: ' . $order->get_customer_ip_address() . '<br />ClientIPAddress: ' . $data['ClientIPAddress'], 'woocommerce-gateway-sagepay-form' ) );

			return $data;

		}

		/**
		 * Gets the new token.
		 *
		 * @param      <type>     $data       The data
		 * @param      <type>     $order      The order
		 * @param      <type>     $card_form  The card form
		 *
		 * @throws     Exception  (description)
		 *
		 * @return     bool       The new token.
		 */
		function get_new_token( $data, $order, $card_form ) {

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
					// Successful token
					$this->save_token( $sageresult['Token'], $card_form["sage_card_type"], $card_form["sage_card_number"], $card_form["sage_card_exp_month"], $card_form["sage_card_exp_year"] );
					
					// Update Subscription with new token info
					update_post_meta( $order->get_id(), '_SagePayDirectToken' , str_replace( array('{','}'),'',$sageresult['Token'] ) );

					return $sageresult['Token'];

				} else {
					// Add Order Note
	                $order->add_order_note( sprintf( __( 'An attempt was made to create a new token, attempt failed: %s', 'woocommerce-gateway-sagepay-form' ), $sageresult ) );
				}

			}

			return false;
		}

	} // End class
