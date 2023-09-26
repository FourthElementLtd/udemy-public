<?php

    /**
     * WC_Gateway_Opayo_Pi_PayPal class.
     *
     * @extends WC_Payment_Gateway_CC
     */
    class WC_Gateway_Opayo_Pi_PayPal extends WC_Payment_Gateway_CC {

        /**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {

            $this->id                   = 'opayopipaypal';
            $this->method_title         = __( 'PayPal', 'woocommerce-gateway-sagepay-form' );
            $this->method_description   = __( 'PayPal', 'woocommerce-gateway-sagepay-form' );
            $this->icon                 = apply_filters( 'wc_opayopipaypal', '' );
            $this->has_fields           = true;

            $this->sagelinebreak 		= '0';

            $this->successurl 			= WC()->api_request_url( 'WC_Gateway_Sagepay_Direct' );

            $this->sagepay_version 		= OPAYOPLUGINVERSION;

            // Load the form fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            // Get setting values
            $this->enabled				= $this->settings['enabled'];
            $this->title				= $this->settings['title'];
            $this->description			= $this->settings['description'];
            $this->has_fields           = false;

           	// Sage urls
            if ( $this->status == 'live' ) {
            	// LIVE
				$this->purchaseURL 		= apply_filters( 'woocommerce_sagepay_direct_live_purchaseURL', 'https://live.sagepay.com/gateway/service/vspdirect-register.vsp' );
				// PayPal
				$this->paypalcompletion = apply_filters( 'woocommerce_sagepay_direct_live_paypalcompletion', 'https://live.sagepay.com/gateway/service/complete.vsp' );
			} else {
				// TEST
				$this->purchaseURL 		= apply_filters( 'woocommerce_sagepay_direct_test_purchaseURL', 'https://test.sagepay.com/gateway/service/vspdirect-register.vsp' );
				// PayPal
				$this->paypalcompletion = apply_filters( 'woocommerce_sagepay_direct_test_paypalcompletion', 'https://test.sagepay.com/gateway/service/complete.vsp' );
			}

            // ReferrerID
            $this->referrerid 			= 'F4D0E135-F056-449E-99E0-EC59917923E1';

			// WC version
			$this->wc_version = get_option( 'woocommerce_version' );

			// Remove Pay button from My Account page for 'Authorized' orders
			// apply_filters( 'woocommerce_my_account_my_orders_actions', $actions, $order );
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'remove_authorized_my_account' ), 10, 2 );

			// Hooks
			add_action( 'woocommerce_api_wc_gateway_sagepay_direct', array( $this, 'check_sagepaydirect_response' ) );
			
			add_action( 'woocommerce_receipt_' . $this->id, array($this, 'authorise_3dsecure') );

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );


        } // END __construct

		/**
		 * Check if this gateway is enabled
		 */
		public function is_available() {

			if ( $this->enabled == "yes" ) {

				return true;

			}

			return false;

		}

		/**
		 * Process the payment and return the result
		 */
		public function process_payment( $order_id ) {

    		include_once( 'sagepay-direct-process-class.php' );
			$response = new Sagepay_Direct_Process( $order_id );

			$processed = $response->process();

			return $processed;
	
		}

        /**
         * Authorise 3D Secure payments
         * 
         * @param int $order_id
         */
        function authorise_3dsecure( $order_id ) {


    		include_once( 'sagepay-direct-3dsecure-protocol4-class.php' );
        	$response = new Sagepay_Direct_3DSecure_4( $order_id );

        	$result = $response->authorise();

        } // end auth_3dsecure

        /**
         * process_response
         *
         * take the reponse from Sage and do some magic things.
         * 
         * @param  [type] $sageresult [description]
         * @param  [type] $order      [description]
         * @return [type]             [description]
         */
        function process_response( $sageresult, $order ) {

        	include_once( 'sagepay-direct-response-class.php' );
    		$response = new Sagepay_Direct_Response( $sageresult, $order );

    		$order_id = $order->get_id();
    		
    		// Clean up Order Meta
    		$delete_card_details = apply_filters( 'opayo_delete_sanitized_card_details', true, $order_id );
    		if( $delete_card_details ) {
            	delete_post_meta( $order_id, '_SagePaySantizedCardDetails' );
            }
            
            delete_post_meta( $order_id, '_sage_3dsecure' );

            $return = $response->process();

            return $return;

        }

		/**
         * build_query
         *
         * Build query for SagePay
         * 
         * @param  [type] $order 		 [description]
         * @param  [type] $card_form     [description]
         * @return [type]             	 [description]
         */
		function build_query( $order, $card_form, $requirement = 'standard' ) {
        	
    		include_once( 'sagepay-direct-request-class.php' );

    		$request = new Sagepay_Direct_Request( $order, $card_form, $requirement );

    		return $request->build_request();

		}

		/**
		 * Send the info to Sage for processing
		 * https://test.sagepay.com/showpost/showpost.asp
		 */
        function sagepay_post( $data, $url ) {

        	if( isset( $this->log_header ) && $this->log_header ) {
				WC_Sagepay_Common_Functions::sagepay_debug( $_SERVER, 'Opayo_SERVER', __('Logging SERVER : ', 'woocommerce-gateway-sagepay-form'), TRUE );
			}

        	if( $this->status == 'developer' ) {
				$url = 'https://woocommerce-sagepay.com/posttest/postman.php';
        	}

        	// Debugging
        	if ( $this->debug == true || $this->status != 'live' ) {
        		$to_log['DATA'] = $data;
        		$to_log['URL'] 	= $url;
	  			WC_Sagepay_Common_Functions::sagepay_debug( $to_log, $this->id, __('Sent to Opayo : ', 'woocommerce-gateway-sagepay-form'), TRUE );
			}

			// Convert $data array to query string for Sage
        	if( is_array( $data) ) {
        		// Convert the $data array for Sage
	            $data = http_build_query( $data, '', '&' );
        	}

        	$params = array(
							'method' 		=> 'POST',
							'timeout' 		=> apply_filters( 'woocommerce_opayo_post_timeout', 45 ),
							'httpversion' 	=> '1.1',
							'headers' 		=> array('Content-Type'=> 'application/x-www-form-urlencoded'),
							'body' 			=> $data,
							// 'sslverify' 	=> false
						);

			$res = wp_remote_post( $url, $params );

			if( is_wp_error( $res ) ) {

				// Debugging
  				if ( $this->debug == true || $this->status != 'live' ) {
  					WC_Sagepay_Common_Functions::sagepay_debug( $res->get_error_message(), $this->id, __('Remote Post Error : ', 'woocommerce-gateway-sagepay-form'), FALSE );
				}

			} else {

				// Debugging
				if ( $this->debug == true || $this->status != 'live' ) {
					WC_Sagepay_Common_Functions::sagepay_debug( $res['body'], $this->id, __('Opayo Direct Return : ', 'woocommerce-gateway-sagepay-form'), FALSE );
				}

				return $this->sageresponse( $res['body'] );

			}

        }

		/**
		 * sagepay_message
		 * 
		 * return checkout messages / errors
		 * 
		 * @param  [type] $message [description]
		 * @param  [type] $type    [description]
		 * @return [type]          [description]
		 */
		function sagepay_message( $message, $type, $order_id = NULL ) {
        	global $woocommerce;
			if( is_callable( 'wc_add_notice') ) {
				if( $order_id ) {
					update_post_meta( $order_id, '_sagepay_errors', array( 'message'=>$message, 'type'=>$type ) );
				} else {
					wc_add_notice( $message, $type );
				}
			}

		}

		/**
		 * sageresponse
		 *
		 * take response from Sage and process it into an array
		 * 
		 * @param  [type] $array [description]
		 * @return [type]        [description]
		 */
		function sageresponse( $array ) {
        	
			$response 		= array();
			$sagelinebreak 	= $this->sage_line_break( $this->sagelinebreak );
            $results  		= preg_split( $sagelinebreak, $array );

            foreach( $results as $result ){ 

            	$value = explode( '=', $result, 2 );
                $response[trim($value[0])] = trim($value[1]);

            }

            return $response;

		}

        /**
         * check_sagepaydirect_response function.
         * For PayPal transactions
         *
         * @access public
         * @return void
         */
        function check_sagepaydirect_response() {

    		include_once( 'sagepay-direct-wc-api-class.php' );

    		$api = new Sagepay_Direct_API();

    		return $api->process_api();

        }
        
        /**
         * Load the settings fields.
         *
         * @access public
         * @return void
         */
        function init_form_fields() {	
			include ( SAGEPLUGINPATH . 'assets/php/sagepay-direct-admin.php' );
		}

		/**
		 * [get_icon description] Add selected card icons to payment method label, defaults to Visa/MC/Amex/Discover
		 * @return [type] [description]
		 */
		public function get_icon() {
			return WC_Sagepay_Common_Functions::get_icon( $this->cardtypes, $this->sagelink, $this->sagelogo, $this->id );
		}

		/**
		 * Set a default postcode for Elavon users
		 */
		function billing_postcode( $postcode ) {
			if ( '' != $postcode ) {
				return $postcode;
			} else {
				return isset( $this->sdefaultpostcode ) && $this->sdefaultpostcode != '' ? $this->defaultpostcode : $this->default_postcode;;
			}
		}

		/**
		 * Set a default city if city field is empty
		 */
		function city( $city ) {
			if ( '' != $city ) {
				return $city;
			} else {
				return ' ';
			}
		}

		/**
		 * Set billing or shipping state
		 */
		function get_state( $country, $billing_or_shipping, $order ) {

			if ( $billing_or_shipping == 'billing' ) {
            	
            	if ( $country == 'US' ) {
            		return  $order->billing_state;
            	} else {
            		return '';
            	}

            } elseif ( $billing_or_shipping == 'shipping' ) {
            	
            	if ( $country == 'US' ) {
            		return  $order->shipping_state;
            	} else {
            		return '';
            	}

            }

		}

		/**
		 * [sage_line_break description]
		 * Set line break
		 */
		function sage_line_break ( $sage_line_break ) {
			
			switch ( $sage_line_break ) {
    			case '0' :
        			$line_break = '/$\R?^/m';
        			break;
    			case '1' :
        			$line_break = PHP_EOL;
        			break;
    			case '2' :
        			$line_break = '#\n(?!s)#';
        			break;
        		case '3' :
        			$line_break = '#\r(?!s)#';
        			break;
    			default:
       				$line_break = '/$\R?^/m';
			}

			return $line_break;
		
		}

		/**
		 * Check IP Address, set to Protocol 3.00 if IP address is not IPv4
		 */
		function get_vpsprotocol_from_ipaddress( $vpsprotocol ) {

			$ipaddresses = $this->get_ipaddresses();

			// Remove IPv6 addresses, Opayo does not support IPv6 yet
	        foreach( $ipaddresses as $lable => $ipaddress ) {
	        	if ( !filter_var( $ipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
					$cleaned_ipaddresses[] = $this->isValidIP( $ipaddress );
				}
	        }

	        // IPv4 IP Address present, return $vpsprotocol
	        if( isset( $cleaned_ipaddresses[0] ) ) {
	        	return $vpsprotocol;
	        }

	        // No IPv4 IP Address, set VPS Protocol to 3.00
	        return '3.00';

		}

		/**
		 * Get IP Address
		 */
		function get_ipaddress() {

			$ipaddresses = $this->get_ipaddresses();

			// Remove IPv6 addresses, Opayo does not support IPv6 yet
	        foreach( $ipaddresses as $lable => $ipaddress ) {
	        	if ( !filter_var( $ipaddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
					$cleaned_ipaddresses[] = $this->isValidIP( $ipaddress );
				}
	        }

	        // IPv4 IP Address present, return $vpsprotocol
	        if( isset( $cleaned_ipaddresses[0] ) ) {
	        	return $cleaned_ipaddresses[0];
	        }

	        return NULL;

		}

		/**
		 * [get_ipaddresses description]
		 * @return [type] [description]
		 */
	    function get_ipaddresses() {
	        $ipaddresses = array();

	        if( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
	            $ipaddresses['HTTP_CF_CONNECTING_IP'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
	        } 

	        if ( isset($_SERVER['HTTP_CLIENT_IP'] ) ) {
	            $ipaddresses['HTTP_CLIENT_IP'] = $_SERVER['HTTP_CLIENT_IP'];
	        }

	        if ( isset($_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
	            $ipaddresses['HTTP_X_FORWARDED_FOR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
	        }

	        if ( isset($_SERVER['HTTP_X_FORWARDED'] ) ) {
	            $ipaddresses['HTTP_X_FORWARDED'] = $_SERVER['HTTP_X_FORWARDED'];
	        }

	        if ( isset($_SERVER['HTTP_FORWARDED_FOR'] ) ) {
	            $ipaddresses['HTTP_FORWARDED_FOR'] = $_SERVER['HTTP_FORWARDED_FOR'];
	        }

	        if ( isset($_SERVER['HTTP_FORWARDED'] ) ) {
	            $ipaddresses['HTTP_FORWARDED'] = $_SERVER['HTTP_FORWARDED'];
	        }

	        if ( isset($_SERVER['REMOTE_ADDR'] ) ) {
	            $ipaddresses['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
	        }
	
			// Testing
	        // $ipaddresses['REMOTE_ADDR'] = "2001:0db8:85a3:0000:0000:8a2e:0370:7334,7334";

	        // Validate IP Addresses
	        foreach( $ipaddresses as $lable => $ipaddress ) {
	        	$ipaddresses[ $lable ] = $this->isValidIP( $ipaddress );
	        }

	        return $ipaddresses;

	    }

	    /**
	     * [isValidIP description]
	     * @param  [type]  $ipaddress [description]
	     * @return boolean            [description]
	     */
	    function isValidIP( $ipaddress ) {

	        // If the IP address is valid send it back
	        if( filter_var( $ipaddress, FILTER_VALIDATE_IP ) ) {
	            return $ipaddress;
	        }

	        // Clean up the IP6 address
	        if ( strpos( $ipaddress, ':' ) !== false ) {

	            // Make an array of the chunks
	            $ip = explode( ":", $ipaddress );

	            // Only the first 8 chunks count
	            $ip = array_slice( $ip, 0, 8 );

	            // Make sure each chunk is 4 characters long and only contains letters and numbers
	            foreach( $ip as &$value ) {
	                $value = substr( $value, 0, 4 );
	                $value = preg_replace( '/\W/', '', $value );
	            }

	            unset( $value );

	            // Combine the chunks and return the IP6 address
	            return implode( ":", $ip );

	        }

	        // Clean up the IP4 address
	        if ( strpos( $ipaddress, '.' ) !== false ) {

	            // Make an array of the chunks
	            $ip = explode( ".", $ipaddress );

	            // Only the first 4 chunks count
	            $ip = array_slice( $ip, 0, 4 );

	            // Make sure each chunk is 3 characters long and only contains numbers
	            foreach( $ip as &$value ) {
	                $value = substr( $value, 0, 3 );
	                $value = preg_replace( '/\D/', '', $value );
	            }

	            unset( $value );

	            // Combine the chunks and return the IP4 address
	            return implode( ".", $ip );

	        }

	        // Fallback
	        return $ipaddress;
	    }

	} // END CLASS
