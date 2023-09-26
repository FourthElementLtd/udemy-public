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

            	// Temporary order note
		        // $order->add_order_note( sprintf( __( 'Form encoding: %s', 'woocommerce-gateway-sagepay-form' ), $_SERVER['CONTENT_TYPE'] ) );

            	if( isset( $_REQUEST['threedsecure'] ) ) {

            		$threedsecure = wc_clean( $_REQUEST["threedsecure"] );
                	$stored_value = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_VendorTxCode' AND meta_value = %s;", $threedsecure ) );

	                if ( null !== $stored_value && $order_id == $stored_value->post_id ) {

		        		// Get the stored values from the order
		        		$opayoresult = get_post_meta( $order_id, '_sage_3ds', TRUE );

		        		if( isset( $_REQUEST["cres"] ) ) {

		        			$data = array(
	                            "CRes" 		=> $_REQUEST['cres'],
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

							// Add some order notes for tracing problems
							$order->add_order_note( sprintf( __( 'API failure: %s', 'woocommerce-gateway-sagepay-form' ), $result->get_error_message() ) );

							throw new Exception( __( 'Processing error <pre>' . print_r( $sageresult, TRUE ) . '</pre>', 'woocommerce-gateway-sagepay-form' ) );
						
						} else {
							// Process the response from Opayo
							$sageresult = $this->sageresponse( $result['body'] );

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

		                    		// Sucess, redirect customer!
		                    		wp_redirect( $this->append_url( $order->get_checkout_order_received_url() ) );
                					exit;

				                break;

				                case 'INVALID':
				                case 'NOTAUTHED':
				                case 'MALFORMED':
				                case 'ERROR':

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

				                    throw new Exception( __('Payment error.<br />A problem occured when verifying your card, please check your details and try again.<br />Your card has not been charged.', 'woocommerce-gateway-sagepay-form') . ': ' . $sageresult['StatusDetail'] );

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

			        // Add some order notes for tracing problems
					$order->add_order_note( sprintf( __( 'Processing 3D Secure, directing customer to: %s', 'woocommerce-gateway-sagepay-form' ), $sage_3dsecure['ACSURL'] ) );

		            $form  = '<p>Your card issuer has requested additional authorisation for this transaction, please wait while you are redirected.</p>';
		            $form .= '<form id="submitForm" method="post" action="' . $iframe_args['ACSURL'] . '">';
		            $form .= '<input type="hidden" name="' . $iframe_args['name_one'] . '" value="' . $iframe_args['value_one'] . '"/>';
		            $form .= '<input type="hidden" name="' . $iframe_args['name_two'] . '" value="' . $iframe_args['value_two'] . '"/>';
		            $form .= '<input type="hidden" id="termUrl" name="TermUrl" value="' . $iframe_args['termUrl'] . '"/>';
		            $form .= '<noscript><p>You are seeing this message because JavaScript is disabled in your browser. Please click to authenticate your card</p><p><input type="submit" value="Submit"></p></noscript>';
		            $form .= '<script>document.getElementById("submitForm").submit();</script>';
		            $form .= '</form>';

		            echo $form;

		            return;

		        } elseif( isset( $_GET["vtx"] ) ) {

            		$vtx = wc_clean( $_GET["vtx"] );

            		$settings   = get_option( 'woocommerce_sagepaydirect_settings' );

            		// Check is the returned value matched the order
            		$stored_value = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_VendorTxCode' AND meta_value = %s;", $vtx ) );

	                if ( null !== $stored_value ) {

	                    $order_id   = $stored_value->post_id;
	                    $order      = wc_get_order( $order_id );

	                    // Empty Cart
	                    if( is_callable( 'wc_empty_cart' ) ) {
	                        wc_empty_cart(); 
	                    }

	                    // PayPal
	                    if( isset( $settings['enabled'] ) && isset( $settings['cardtypes'] ) && $settings['enabled'] == "yes" && ( $key = array_search('PayPal', $settings['cardtypes']) ) !== false ) {

	                        if( in_array( $_POST['Status'], array( 'OK', 'PAYPALOK' ) ) ) {

					            // make your query.
					            $data    = array(
					                "VPSProtocol"       =>  $this->get_vpsprotocol(),
					                "TxType"            =>  'COMPLETE',
					                "VPSTxId"           =>  wc_clean( $_POST['VPSTxId'] ),
					                "Amount"            =>  $order->get_total(),
					                "Accept"            =>  'YES'
					            );

					            $result = $this->sagepay_post( $data, $this->paypalcompletion );

					            // Check $result for API errors
								if( is_wp_error( $result ) ) {
									$sageresult = $result->get_error_message();

									// Add some order notes for tracing problems
									$order->add_order_note( sprintf( __( 'Processing PayPal API error: %s', 'woocommerce-gateway-sagepay-form' ), $sageresult ) );

									throw new Exception( __( 'Processing error <pre>' . print_r( $sageresult, TRUE ) . '</pre>', 'woocommerce-gateway-sagepay-form' ) );
								} else {
									$sageresult = $this->sageresponse( $result['body'] );

									// Add some order notes for tracing problems
									$order->add_order_note( sprintf( __( 'Processing PayPal: %s', 'woocommerce-gateway-sagepay-form' ), $sageresult['Status'] ) );

	        						switch( strtoupper( $sageresult['Status'] ) ) {
						                case 'OK':
						                case 'REGISTERED':
						                case 'AUTHENTICATED':

						            	// Store the result array from Opayo as early as possible
				                        $this->update_order_meta( $sageresult, $order_id );

			                    		// Set the order status as early as possible
			                    		$order->payment_complete( $sageresult['VPSTxId'] );

			                    		// Add the order note
			                    		$this->add_order_note( __('PayPal payment successful', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

			                    		// Redirect for a retry
	                					wp_redirect( $this->append_url( $order->get_checkout_order_received_url() ) );
	                					exit;

	                				default :

				                        $update_order_status = apply_filters( 'woocommerce_opayo_direct_failed_order_status', 'failed', $order, $sageresult );
				                      
				                        // Add Order Note
				                        $this->add_order_note( __('Payment failed', 'woocommerce-gateway-sagepay-form'), $sageresult, $order );

				                        // Update the order status
				                        $order->update_status( $update_order_status );

				                        // Update Order Meta
				                        $this->update_order_meta( $sageresult, $order_id );

					                    throw new Exception( __('Payment error. Please try again, your card has not been charged.', 'woocommerce-gateway-sagepay-form') . ': ' . $sageresult['StatusDetail'] );

						            }

			                    }

	                        } else {
	                            $redirect = $sageresult['redirect'];
	                        }

	                        wp_redirect( $redirect );
	                        exit;

	                    } else {
	                    	// We should not be here :/
	        				throw new Exception( __( "Opayo Request Failure<br />Check the WooCommerce Opayo Settings for error messages", 'woocommerce-gateway-sagepay-form' ) );
	                    }

	                }

		        } else {
		        	// We should not be here :/
		        	$order->add_order_note( __( '3D Secure could not be validated', 'woocommerce-gateway-sagepay-form' ) );
		        	throw new Exception( __( 'There was an error. Your card has not been charged, please try again. 3D Secure could not be validated', 'woocommerce-gateway-sagepay-form' ) );
		        }

	        } catch( Exception $e ) {

	        	// Clear any stored values, necessary for the retries
	    		delete_post_meta( $order_id, '_sage_3ds' );
	    		delete_post_meta( $order_id, '_VendorTxCode' );
	    		delete_post_meta( $order_id, '_RelatedVendorTxCode' );

				// Add the error message
				if( is_callable( 'wc_add_notice' ) ) {
				    do_action( 'woocommerce_set_cart_cookies',  true );
				    wc_add_notice( $e->getMessage(), 'error' );
				}

				// Redirect for a retry
				if (is_wc_endpoint_url('order-pay')) {
				    wp_safe_redirect( $order->get_checkout_payment_url() );
				} else {
				    wp_safe_redirect(wc_get_checkout_url());
				}

				exit;

			}
		}

	} // End class
