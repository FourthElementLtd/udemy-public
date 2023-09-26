<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
    
	/**
	 * Process the payment form
	 */
	class Opayo_Direct_Process_Threeds extends WC_Gateway_Sagepay_Direct {

		public function __construct() {

			parent::__construct();

			$this->settings 	= get_option( 'woocommerce_sagepaydirect_settings' );

		}
	
		function process_threeds( $order_id ) {
        	global $wpdb;

	        // woocommerce order object
	        $order    = wc_get_order( $order_id );

            try {

            	if( isset( $_REQUEST['threedsecure'] ) ) {

            		$threedsecure = wc_clean( $_REQUEST["threedsecure"] );
                	$stored_value = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_VendorTxCode' AND meta_value = %s;", $threedsecure ) );

	                if ( null !== $stored_value && $order_id == $stored_value->post_id ) {

		        		// Get the stored values from the order
		        		$opayoresult = get_post_meta( $order_id, '_sage_3ds', TRUE );

		        		if( isset( $_REQUEST["cres"] ) ) {

			        		$data = array(
	                            "CRes" 		=> wc_clean( $_REQUEST["cres"] ),
	                            "VPSTxId" 	=> $opayoresult["VPSTxId"]
	                        );

		        		} else {

			        		$data = array(
	                            "PARes" 	=> wc_clean( $_REQUEST["PaRes"] ),
	                            "MD" 	=> $opayoresult["MD"]
	                        );

		        		}

		        		// Send the 3D Secure response to Opayo
		        		$result = $this->sagepay_post( $data, $this->callbackURL );

		        		// Check $result for API errors
						if( is_wp_error( $result ) ) {

							$sageresult = $result->get_error_message();
							throw new Exception( __( 'Processing error <pre>' . print_r( $sageresult, TRUE ) . '</pre>', 'woocommerce-gateway-sagepay-form' ) );
						
						} else {
							// Process the response from Opayo
							$sageresult = $this->sageresponse( $result['body'] );

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
				                            "VendorTxCode"  => isset( $opayoresult['VendorTxCode'] ) ? $opayoresult['VendorTxCode'] : '',
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

				                default :

			                        $update_order_status = apply_filters( 'woocommerce_opayo_direct_failed_order_status', 'failed', $order, $sageresult );
			                      
			                        // Add Order Note
			                        $order->add_order_note( __('Payment failed', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

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

						exit;

		        	} else {
		        		// Order ID can not be verified against stored _VendorTxCode
		        		throw new Exception( __( 'There was an error. Your card has not been charged, please try again.', 'woocommerce-gateway-sagepay-form' ) );
		        	}

				} elseif( isset( $_GET['process_threedsecure'] ) ) {

					$sage_3dsecure  = get_post_meta( $order_id, '_sage_3ds', TRUE );

			        $key      = 'CRes';
			        $value    = '';

			        $redirect_url = $this->get_return_url( $order );

			        // Get ready to set form fields for 3DS 1.0/2.0
			        $p = $this->pareq_or_creq ( $sage_3dsecure );
			        $m = $this->md_or_vpstxid ( $sage_3dsecure );

			        $sage_3dsecure['Complete3d'] = $this->append_url( $order->get_checkout_payment_url( true ) );
			        $sage_3dsecure['Complete3d'] = add_query_arg( 'threedsecure', get_post_meta( $order_id, '_VendorTxCode', TRUE ), $sage_3dsecure['Complete3d'] );

			        $iframe_args = array( 
			                            "name_one"      => $p["field_name"],
			                            "value_one"     => $p["field_value"],
			                            "name_two"      => $m["field_name"],
			                            "value_two"     => $m["field_value"],
			                            "termUrl"       => $sage_3dsecure['Complete3d'],
			                            "ACSURL"        => $sage_3dsecure['ACSURL'],
			                        );
/*
			        $iframe_url = $sage_3dsecure['Complete3d'];

// Remove iFrame option temporarily
			        $display_method = $this->get_threeDSMethod();

			        if( isset( $display_method ) && $display_method === "0" ) {

			        	// iFrame Method
			            $form  = '<p>Your card issuer has requested additional authorisation for this transaction, please wait while you are redirected.</p>';
			            $form .= '<form id="submitForm" method="post" action="' . $iframe_args['ACSURL'] . '">';
			            $form .= '<input type="hidden" name="' . $iframe_args['name_one'] . '" value="' . $iframe_args['value_one'] . '"/>';
			            $form .= '<input type="hidden" name="' . $iframe_args['name_two'] . '" value="' . $iframe_args['value_two'] . '"/>';
			            $form .= '<input type="hidden" id="termUrl" name="TermUrl" value="' . $iframe_args['termUrl'] . '"/>';
			            $form .= '<noscript><p>Authenticate your card</p><p><input type="submit" value="Submit"></p></noscript>';
			            // $form .= '<script>document.getElementById("submitForm").submit();</script>';
			            $form .= '</form>';

			            $redirect_page = 
			                '<!--Non-IFRAME browser support-->' .
			                '<html><head><title>3D Secure Verification</title></head>' . 
			                '<body>' .
			                $form . 
			                '</body></html>';

			            $iframe_page = 
			                '<noscript><h3>You are seeing this message because JavaScript is disabled in your browser. Please consider enabling JavaScript for this website before continuing. Please do not refresh the page.</h3></noscript>' .
			                '<iframe src=\''. $iframe_url .'\' name=\'3diframe\' width=\'100%\' height=\'500px\' frameBorder=\'0\' sandbox=\'allow-top-navigation allow-scripts allow-forms allow-same-origin\'>' .
			                $redirect_page .
			                '</iframe>';
			                
			                
			            echo $iframe_page;
			            // Use return for iFrame method to make sure website footer shows
			            return;

			        } else {
*/
			            $form  = '<p>Your card issuer has requested additional authorisation for this transaction, please wait while you are redirected.</p>';
			            $form .= '<form id="submitForm" method="post" action="' . $iframe_args['ACSURL'] . '">';
			            $form .= '<input type="hidden" name="' . $iframe_args['name_one'] . '" value="' . $iframe_args['value_one'] . '"/>';
			            $form .= '<input type="hidden" name="' . $iframe_args['name_two'] . '" value="' . $iframe_args['value_two'] . '"/>';
			            $form .= '<input type="hidden" id="termUrl" name="TermUrl" value="' . $iframe_args['termUrl'] . '"/>';
			            $form .= '<noscript><p>You are seeing this message because JavaScript is disabled in your browser. Please click to authenticate your card</p><p><input type="submit" value="Submit"></p></noscript>';
			            $form .= '<script>document.getElementById("submitForm").submit();</script>';
			            $form .= '</form>';

			            echo $form;

			            exit;

//			        }

		        } else {
		        	// We should not be here :/
		        	throw new Exception( __( 'There was an error. Your card has not been charged, please try again. 3D Secure could not be validated', 'woocommerce-gateway-sagepay-form' ) );
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

				// Redirect for a retry
                wp_redirect( wc_get_endpoint_url( 'view-subscription', $order_id, wc_get_page_permalink( 'myaccount' ) ) );
                exit;

			}
		}

	} // End class
