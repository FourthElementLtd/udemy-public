<?php

    /**
     * WC_Gateway_Opayo_Reporting class.
     */
    class WC_Gateway_Opayo_Reporting {

		public static $id 					 = "opayo_reporting";

	    public static $testurl 				 = "https://test.sagepay.com/access/access.htm";
		public static $liveurl 				 = "https://live.sagepay.com/access/access.htm";

		public static $sagepay_payment_methods  		= "sagepayform, sagepaydirect, opayopi";
		public static $sagepay_payment_methods_array  	= array( 'sagepayform', 'sagepaydirect', 'opayopi' );

		public static $opayo_thirdman_action  	 = "OK, HOLD, REJECT";

		// List of txstateid used to update order status to cancelled
		public static $cancel_txstateids 		 = array( '8', '11', '18', '22' );

		// Arrays of acceptable values
		public static $cvvAddress_success 		 = array( 'MATCHED' );
		public static $cvvAddress_check 		 = array( 'NOTPROVIDED','NOTCHECKED','PARTIAL' );
		public static $cvvAddress_fail 		 	 = array( 'NOTMATCHED' );

		public static $thressds_success 		 = array( 'OK','AUTHENTICATED' );
        public static $thressds_check 			 = array( 'INCOMPLETE','NOTCHECKED','ERROR','ATTEMPTONLY','NOAUTH','CANTAUTH','MALFORMED','INVALID','NOTAVAILABLE' );                    
        public static $thressds_fail 		 	 = array( 'NOTAUTHED' );
        

    	/**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {

			// Action scheduler
			add_action( 'init' , array( __CLASS__,'opayo_reporting_get_transaction_report') );
			add_action( 'woocommerce_opayo_reporting_get_transaction_report', array( __CLASS__, 'action_scheduler_opayo_reporting_get_transaction_report' ), 10, 2 );

			// Override Fraud Status order action
			add_filter( 'woocommerce_order_actions', array( __CLASS__, 'opayo_reporting_order_actions' ) );
			add_action( 'woocommerce_order_action_opayo_override_fraud_status', array( __CLASS__, 'update_order_override_fraud_status' ) );

        } // END __construct

        /**
         * opayo_reporting
         *
         * Make the API request
         * 
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        public static function opayo_reporting( $order ) {

        	$settings 	= get_option( 'woocommerce_opayo_reporting_options' );
        	$output		= NULL;
        	$order_id 	= $order->get_id();
        	$url 		= WC_Gateway_Opayo_Reporting::get_url( $order );

        	$transaction_detail_xml 	= WC_Gateway_Opayo_Reporting::get_transaction_detail_xml( 'getTransactionDetail', $order );
        	$xml 						= $transaction_detail_xml['xml'];
        	$signature 					= $transaction_detail_xml['signature'];

	        $output = WC_Gateway_Opayo_Reporting::sagepay_post( $xml, $url );

	        if( $output ) {

		        $xml 	= simplexml_load_string( $output['body'], "SimpleXMLElement", LIBXML_NOCDATA );
				$json 	= json_encode($xml);
				$array 	= json_decode($json,TRUE);

				if( self::developer() ) {
					$array = apply_filters( 'override_opayo_reporting_report_output', $array, $order );
				}

		        return $array;

		    }

		    return NULL;

		}

        /**
         * getFraudScreenDetail_reporting
         *
         * Make the API request
         * 
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        public static function getFraudScreenDetail_reporting( $order ) {

        	$output		= NULL;
        	$order_id 	= $order->get_id();

        	$t3maction  = WC_Gateway_Opayo_Reporting::getFraudScreenDetail_action( $order_id );

        	if( isset( $t3maction ) && in_array( $t3maction, array( 'OK', 'HOLD', 'REJECT' ) ) ) {

	        	$url 		= WC_Gateway_Opayo_Reporting::get_url( $order );

	        	$getFraudScreenDetail_xml 	= WC_Gateway_Opayo_Reporting::getFraudScreenDetail_xml( 'getFraudScreenDetail', $order );
	        	$xml 						= $getFraudScreenDetail_xml['xml'];
	        	$signature 					= $getFraudScreenDetail_xml['signature'];

		        $output = WC_Gateway_Opayo_Reporting::sagepay_post( $xml, $url );

		        if( $output ) {

			        $xml 	= simplexml_load_string( $output['body'], "SimpleXMLElement", LIBXML_NOCDATA );
					$json 	= json_encode($xml);
					$array 	= json_decode($json,TRUE);

			        return $array;

			    }

			}

		    return NULL;

		}

		/**
		 * [validate_account description]
		 * @param  [type] $order [description]
		 * @return [type]        [description]
		 */
		public static function validate_account( $order ) {

			$payment_method = $order->get_payment_method();
			$settings 		= get_option( 'woocommerce_opayo_reporting_options' );
			$url 			= WC_Gateway_Opayo_Reporting::get_url( $order );

			$vendor_details = array( 
					'vendor' 	=> WC_Gateway_Opayo_Reporting::get_vendor_name( $payment_method, $settings ),
					'user' 		=> WC_Gateway_Opayo_Reporting::get_user_name( $payment_method, $settings ),
					'password' 	=> WC_Gateway_Opayo_Reporting::get_password( $payment_method, $settings )
				);

			$content  = '<command>version</command>';
			$content .= '<vendor>' . $vendor_details['vendor'] . '</vendor>';
			$content .= '<user>' . $vendor_details['user'] . '</user>';

			$signature = WC_Gateway_Opayo_Reporting::get_xml_signature( $content, $vendor_details );
			
			$xml  = '';
			$xml .= '<vspaccess>';
			$xml .= $content;
			$xml .= '<signature>' . md5( $signature ) . '</signature>';
			$xml .= '</vspaccess>';

			$output = WC_Gateway_Opayo_Reporting::sagepay_post( $xml, $url );

			if( $output ) {

				// Log the result
		        $response 	= simplexml_load_string( $output['body'], "SimpleXMLElement", LIBXML_NOCDATA );
				$json 		= json_encode( $response );
				$array 		= json_decode( $json, TRUE );

				if( isset( $array['errorcode'] ) && $array['errorcode'] == '0000' ) {
					return true;
				}

			}

			return false;

		}

		/**
		 * [validate_api_details description]
		 * @param  [type] $payment_method [description]
		 * @param  [type] $settings       [description]
		 * @return [type]                 [description]
		 */
		public static function validate_api_details( $order ) {

			$payment_method 	= $order->get_payment_method();
			$settings 			= get_option( 'woocommerce_opayo_reporting_options' );

			$vendor_name 		= WC_Gateway_Opayo_Reporting::get_vendor_name( $payment_method, $settings );
			$user_name 			= WC_Gateway_Opayo_Reporting::get_user_name( $payment_method, $settings );
			$password 			= WC_Gateway_Opayo_Reporting::get_password( $payment_method, $settings );

			if( !isset($vendor_name) || strlen($vendor_name) == 0 ) {
				return false;
			}

			if( !isset($user_name) || strlen($user_name) == 0 ) {
				return false;
			}

			if( !isset($password) || strlen($password) == 0 ) {
				return false;
			}

			// Validate the account with Opayo
			$validate_account 	= WC_Gateway_Opayo_Reporting::validate_account( $order );

			if( !$validate_account ) {
				return false;
			}

			return true;

		}

		/**
		 * [opayo_reporting_get_transaction_report description]
		 * @return NULL
		 */
		public static function opayo_reporting_get_transaction_report() {

			$settings 		= get_option( 'woocommerce_opayo_reporting_options' );
			$schedule_time 	= isset( $settings['opayo_reporting_action_scheduler_time'] ) ? $settings['opayo_reporting_action_scheduler_time'] : 3600;

			// Update the order status if necessary 
			if( isset( $settings['opayo_reporting_action_scheduler'] ) && $settings['opayo_reporting_action_scheduler'] == 'yes' ) {
				
				$next = WC()->queue()->get_next( 'woocommerce_opayo_reporting_get_transaction_report' );

				if ( ! $next ) {
					WC()->queue()->cancel_all( 'woocommerce_opayo_reporting_get_transaction_report' );
					WC()->queue()->schedule_recurring( time()+$schedule_time, $schedule_time, 'woocommerce_opayo_reporting_get_transaction_report' );
				}
			}
			 
		}

		/**
		 * [action_scheduler_opayo_reporting_get_transaction_report description]
		 * @param  [type] $args  [description]
		 * @param  string $group [description]
		 * @return [type]        [description]
		 */
		public static function action_scheduler_opayo_reporting_get_transaction_report( $args = NULL, $group = '' ) {
            global $wpdb;

            $counter 			= 0;
			$valid_api_details 	= true;
			$settings 			= get_option( 'woocommerce_opayo_reporting_options' );

			// Show the meta box if Fraud checks are enabled.
			if( $valid_api_details ) {

	            $reporting_order_status_array = apply_filters( 'woocommerce_opayo_reporting_query_order_status_array', "wc-on-hold, wc-pending, wc-processing, wc-authorised" );

				$args =	array(
							'numberposts' => -1,
							'post_type'   => 'shop_order',
							'post_status' => $reporting_order_status_array,
							'meta_query'  => array(
								'key'    => '_payment_method',
								'value'  => WC_Gateway_Opayo_Reporting::$sagepay_payment_methods,
								'compare'=> 'IN'
							)
						);

	        	$check = get_posts( $args );

	        	WC_Gateway_Opayo_Reporting::log( count( $check ) . ' orders found' );

	        	if( isset($check) && !empty($check) ) {

					$args =	array(
							'numberposts' => 100,
							'post_type'   => 'shop_order',
							'post_status' => $reporting_order_status_array,
							'meta_query'  => array(
								'key'    => '_payment_method',
								'value'  => WC_Gateway_Opayo_Reporting::$sagepay_payment_methods,
								'compare'=> 'IN'
							)
						);

					$results = get_posts( $args );

					// Process each order
	                foreach ( $results as $result ) {

	                	$counter++;

	                	$order_id = $result->ID;

	                	// Get the order
	                    $order = new WC_Order( $order_id );

	                    // Order paid with Opayo?
	                    if( in_array( $order->get_payment_method(), WC_Gateway_Opayo_Reporting::$sagepay_payment_methods_array ) ) {

		                    $sageresult = get_post_meta( $order_id, '_sageresult', TRUE );
							$output 	= WC_Gateway_Opayo_Reporting::opayo_reporting( $order, $sageresult );

							// For testing purposes
							$output = apply_filters( 'woocommerce_opayo_reporting_testing_action_scheduler_opayo_reporting_get_transaction_report', $output, $order, $sageresult );

							update_post_meta( $order_id, '_opayo_reporting_output', $output );

							// Update order with Thirdman Action
							WC_Gateway_Opayo_Reporting::update_order_thirdman_action( $order, $output );
		                    
		                    // Update the order status if necessary 
							if( isset( $settings['opayo_reporting_update_status'] ) && $settings['opayo_reporting_update_status'] == 'yes' ) {
								WC_Gateway_Opayo_Reporting::update_order_status( $order, $output );
							}

						}
					
	                }

	            } else {
	            	WC_Gateway_Opayo_Reporting::log( 'No orders found' );
	            }

	        }

		}

		/**
		 * [update_order_status description]
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function update_order_status( $order, $output ) {

			$settings 				= get_option( 'woocommerce_opayo_reporting_options' );
			$valid_order_statuses 	= !empty( $settings['opayo_reporting_valid_order_statuses'] ) && is_array( $settings['opayo_reporting_valid_order_statuses'] ) ? $settings['opayo_reporting_valid_order_statuses'] : NUll;

			// Allow filtering 
			$opayo_reporting_update_order_status_on_cancel 				= apply_filters( 'opayo_reporting_update_order_status_on_cancel', true, $order, $output );
			$opayo_reporting_update_order_status_on_t3maction_hold 		= apply_filters( 'opayo_reporting_update_order_status_on_t3maction_hold', true, $order, $output );
			$opayo_reporting_update_order_status_on_t3maction_reject 	= apply_filters( 'opayo_reporting_update_order_status_on_t3maction_reject', true, $order, $output );

			$override_fraud_status 										= WC_Gateway_Opayo_Reporting::get_override_fraud_status( $order );
				
			// Check 
			// Use get_post_status because $order->get_status() returns order status without wc- but wc_get_order_statuses() return order statuses with wc- :/
			if( is_null( $valid_order_statuses ) || in_array( get_post_status( $order->get_id() ), $valid_order_statuses ) ) {

				// Update orders to fraud-screen order status
				if( isset( $output['t3maction'] ) && $output['t3maction'] == 'HOLD' && $opayo_reporting_update_order_status_on_t3maction_hold && !$override_fraud_status ) {
					// Update the order status
	                $order->update_status( 'fraud-screen', _x( 'Opayo Thirdman Action ', 'woocommerce-gateway-sagepay-form' ) . $output['t3maction'] . _x( '. Login to MySagePay and check this order before shipping.', 'woocommerce-gateway-sagepay-form' ) );
	                $order->save();
				}

				// Update orders to fraud-screen order status
				if( isset( $output['t3maction'] ) && $output['t3maction'] == 'REJECT' && $opayo_reporting_update_order_status_on_t3maction_reject && !$override_fraud_status ) {
					// Update the order status
	                $order->update_status( 'fraud-screen', _x( 'Opayo Thirdman Action ', 'woocommerce-gateway-sagepay-form' ) . $output['t3maction'] . _x( '. Login to MySagePay and check this order before shipping.', 'woocommerce-gateway-sagepay-form' ) );
	                $order->save();
				}

			}

			// Cancel orders that have been cancelled in MySagePay
			if( isset( $output['txstateid'] ) && in_array( $output['txstateid'], WC_Gateway_Opayo_Reporting::$cancel_txstateids ) && $opayo_reporting_update_order_status_on_cancel && !$override_fraud_status ) {
				// Update the order status
                $order->update_status('cancelled');
                $order->save();
			}

			// Refund orders that have been refunded in MySagePay
			if( isset( $output['refunded'] ) && in_array( $output['refunded'], array( 'YES' ) ) ) {
				$order->update_status( 'refunded', _x( 'Order refunded in MySagePay', 'woocommerce-gateway-sagepay-form' ) );
                $order->save();
			}

		}

		/**
		 * [update_order_thirdman_score description]
		 * @param  [type] $order [description]
		 * @return [type]           [description]
		 */
		public static function update_order_thirdman_action( $order, $output ) {

			$order_id = $order->get_id();

			if( isset( $output['t3maction'] ) ) {
				update_post_meta( $order_id, '_opayo_thirdman_action', $output['t3maction'] );
			}

		}

		/**
		 * [opayo_reporting_order_actions description]
		 * @param  [type] $orderactions [description]
		 * @return [type]               [description]
		 */
	    public static function opayo_reporting_order_actions( $orderactions ) {
	        global $post;
	        $id     = $post->ID;
	        $order  = new WC_Order( $id );

	        $payment_method = $order->get_payment_method();

	        // New method using an Order Status
	        if ( in_array( $payment_method, WC_Gateway_Opayo_Reporting::$sagepay_payment_methods_array ) && $order->get_status() === 'fraud-screen' ) {
	            $orderactions['opayo_override_fraud_status'] = __('Override fraud status, choose new status from dropdown', 'woocommerce-gateway-sagepay-form');
	        }

	        return array_unique( $orderactions);
	    }

	    /**
	     * [update_order_override_fraud_status description]
	     * @param  [type] $order [description]
	     * @return [type]        [description]
	     */
	    public static function update_order_override_fraud_status( $order ) {

	    	$order_id = $order->get_id();

	    	// Update the order status
	    	$order_status = wc_clean( wp_unslash( $_POST['order_status'] ) );

	    	// Only update if a new order status has been chosen
	    	if( $order_status !== 'wc-fraud-screen' ) {
	    		// Clean the order status
		    	$order_status = wc_clean( wp_unslash( $_POST['order_status'] ) );

		    	// add the override flag
		    	update_post_meta( $order_id, '_override_fraud_status', 1 );

		    	// Update the order status
		    	$order->update_status( $order_status, '', true );
		    	// Save the order
	            $order->save();
	    	}

	    }

        /**
         * sagepay_post
         *
         * Post to Sage
         * 
         * @param  [type] $data [description]
         * @param  [type] $url  [description]
         * @return [type]       [description]
         */
        public static function sagepay_post( $data, $url ) {

        	$res  = wp_remote_post( 
        				$url, array(
							'method' 		=> 'POST',
							'timeout' 		=> 45,
							'redirection' 	=> 5,
							'httpversion' 	=> '1.0',
							'blocking' 		=> true,
							'headers' 		=> array('Content-Type'=> 'application/x-www-form-urlencoded'),
							'body' 			=> "XML=".$data,
							'cookies' 		=> array()
						)
					);

			if( is_wp_error( $res ) ) {
				WC_Gateway_Opayo_Reporting::log( $res->get_error_message() );
            } else {
                return $res;
            }

            return NULL;

        }

        /**
         * log
         *
         * Log things
         * 
         * @param  [type] $to_log [description]
         * @return [type]         [description]
         */
		public static function log( $to_log ) {

			if( !isset( $logger ) ) {
                $logger      = new stdClass();
                $logger->log = new WC_Logger();
            }

            $logger->log->add( WC_Gateway_Opayo_Reporting::$id, print_r( $to_log, TRUE ) );

		}

		// Getters
		
		/**
		 * [get_transaction_id description]
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function get_transaction_id( $order_id ) {

			$sageresult = get_post_meta( $order_id, '_sageresult', TRUE );
			$RelatedVPSTxId = get_post_meta( $order_id, '_RelatedVPSTxId', TRUE );

			if( isset($sageresult) && isset($sageresult['fraudid']) ) {
				return str_replace( array('{','}'),'',$sageresult['fraudid'] );
			} elseif( isset($sageresult) && isset($sageresult['VPSTxId']) ) {
				return str_replace( array('{','}'),'',$sageresult['VPSTxId'] );
			} elseif( isset($sageresult) && isset($sageresult['transactionId']) ) {
				return str_replace( array('{','}'),'',$sageresult['transactionId'] );
			} elseif( isset($RelatedVPSTxId) && strlen( $RelatedVPSTxId ) !== 0 ) {
				return str_replace( array('{','}'),'',$RelatedVPSTxId );
			}

			return NULL;
		}

		/**
		 * [get_t3mid description]
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function get_t3mid( $order_id ) {

			$reporting_output = get_post_meta( $order_id, '_opayo_reporting_output', TRUE );

			if( isset($reporting_output) && isset($reporting_output['t3mid']) ) {
				return $reporting_output['t3mid'];
			}

			return NULL;
		}

		/**
		 * [get_override_fraud_status description]
		 * @param  [type] $order [description]
		 * @return [type]        [description]
		 */
		public static function get_override_fraud_status( $order ) {

			$order_id = $order->get_id();

			$override = get_post_meta( $order_id, '_override_fraud_status', TRUE );

			if( isset($override) && strlen($override) != 0 ) {
				return true;
			}

			return false;

		}
		
        /**
         * get_transaction_detail_xml
         *
         * Create the XML to send to Sage
         * 
         * @param  [type] $command [description]
         * @param  [type] $params  [description]
         * @return [type]          [description]
         *
         * https://www.sagepay.co.uk/file/1186/download-document/reportingandapiprotocol102v0.5.pdf
         *
         */
		public static function get_transaction_detail_xml( $command, $order ) {
			$settings 		= get_option( 'woocommerce_opayo_reporting_options' );

			$order_id 		= $order->get_id();
			$payment_method = $order->get_payment_method();

			$vpstxid = WC_Gateway_Opayo_Reporting::get_transaction_id( $order_id );

			$vendor_details = array( 
					'vendor' 	=> WC_Gateway_Opayo_Reporting::get_vendor_name( $payment_method, $settings ),
					'user' 		=> WC_Gateway_Opayo_Reporting::get_user_name( $payment_method, $settings ),
					'password' 	=> WC_Gateway_Opayo_Reporting::get_password( $payment_method, $settings )
				);

			$content  = '<command>' . $command . '</command>';
			$content .= '<vendor>' . $vendor_details['vendor'] . '</vendor>';
			$content .= '<user>' . $vendor_details['user'] . '</user>';
			$content .= '<vpstxid>' . $vpstxid . '</vpstxid>';

			$signature = WC_Gateway_Opayo_Reporting::get_xml_signature( $content, $vendor_details );
			
			$xml  = '';
			$xml .= '<vspaccess>';
			$xml .= $content;
			$xml .= '<signature>' . md5( $signature ) . '</signature>';
			$xml .= '</vspaccess>';

			return array( 
					'xml' 		=> $xml, 
					'signature' => $signature 
				);

		}

        /**
         * getFraudScreenDetail_xml
         *
         * Create the XML to send to Sage
         * 
         * @param  [type] $command [description]
         * @param  [type] $params  [description]
         * @return [type]          [description]
         *
         * https://www.sagepay.co.uk/file/1186/download-document/reportingandapiprotocol102v0.5.pdf
         *
         */
		public static function getFraudScreenDetail_xml( $command, $order ) {

			$order_id 		= $order->get_id();
			$payment_method = $order->get_payment_method();

			$vpstxid 		= $order->get_transaction_id();

			if( !is_null( $t3mid ) ) {

				$settings = get_option( 'woocommerce_opayo_reporting_options' );

				$vendor_details = array( 
						'vendor' 	=> WC_Gateway_Opayo_Reporting::get_vendor_name( $payment_method, $settings ),
						'user' 		=> WC_Gateway_Opayo_Reporting::get_user_name( $payment_method, $settings ),
						'password' 	=> WC_Gateway_Opayo_Reporting::get_password( $payment_method, $settings )
					);

				$content  = '<command>' . $command . '</command>';
				$content .= '<vendor>' . $vendor_details['vendor'] . '</vendor>';
				$content .= '<user>' . $vendor_details['user'] . '</user>';
				$content .= '<vpstxid>' . $vpstxid . '</vpstxid>';

				$signature = WC_Gateway_Opayo_Reporting::get_xml_signature( $content, $vendor_details );
				
				$xml  = '';
				$xml .= '<vspaccess>';
				$xml .= $content;
				$xml .= '<signature>' . md5( $signature ) . '</signature>';
				$xml .= '</vspaccess>';

				return array( 
						'xml' 		=> $xml, 
						'signature' => $signature 
					);
			}

			return NULL;

		}

        /**
         * get_xml_signature
         *
         * Build the XML signature
         * 
         * @param  [type] $command [description]
         * @param  [type] $params  [description]
         * @return [type]          [description]
         */
		public static function get_xml_signature( $content, $vendor_details ) {

			$xml  = $content;
			$xml .= '<password>' . $vendor_details['password'] . '</password>';

			return $xml;
		}

		/**
		 * [get_vendor_name description]
		 * @param  [type] $payment_method [description]
		 * @param  [type] $settings       [description]
		 * @return [type]                 [description]
		 */
		public static function get_vendor_name( $payment_method, $settings ) {

			$vendor_name = NULL;

			if( !in_array( $payment_method, WC_Gateway_Opayo_Reporting::$sagepay_payment_methods_array ) ) {
				return NULL;
			}

			$payment_method_settings = get_option( 'woocommerce_' . $payment_method . '_settings' );

			$vendor_name = $payment_method_settings['vendor'];

			return $vendor_name;
		}

		/**
		 * [get_user_name description]
		 * @param  [type] $payment_method [description]
		 * @param  [type] $settings       [description]
		 * @return [type]                 [description]
		 */
		public static function get_user_name( $payment_method, $settings ) {

			$user_name = NULL;

			if( !in_array( $payment_method, WC_Gateway_Opayo_Reporting::$sagepay_payment_methods_array ) ) {
				return NULL;
			}

			$payment_method_settings = get_option( 'woocommerce_' . $payment_method . '_settings' );

			if( $payment_method_settings['status'] == 'live' && isset( $settings['live_opayo_reporting_username'] ) ) {
				return $settings['live_opayo_reporting_username'];
			} elseif( isset( $settings['test_opayo_reporting_username'] ) ) {
				return $settings['test_opayo_reporting_username'];
			}

			return NULL;
		}

		/**
		 * [get_password description]
		 * @param  [type] $payment_method [description]
		 * @param  [type] $settings       [description]
		 * @return [type]                 [description]
		 */
		public static function get_password( $payment_method, $settings ) {

			$password = NULL;

			if( !in_array( $payment_method, WC_Gateway_Opayo_Reporting::$sagepay_payment_methods_array ) ) {
				return NULL;
			}

			$payment_method_settings = get_option( 'woocommerce_' . $payment_method . '_settings' );

			if( $payment_method_settings['status'] == 'live' && isset( $settings['live_opayo_reporting_password'] ) ) {
				$password = $settings['live_opayo_reporting_password'];
			} elseif( isset( $settings['test_opayo_reporting_password'] ) ) {
				$password = $settings['test_opayo_reporting_password'];
			}

			return WC_Gateway_Opayo_Reporting::decrypt_value( $password );
		}

		/**
		 * [get_url description]
		 * @param  [type] $order [description]
		 * @return [type]        [description]
		 */
		public static function get_url( $order ) {

			$payment_method = $order->get_payment_method();
			$settings 		= get_option( 'woocommerce_opayo_reporting_options' );

			$payment_method_settings = get_option( 'woocommerce_' . $payment_method . '_settings' );

			if( $payment_method_settings['status'] == 'live' ) {
				return WC_Gateway_Opayo_Reporting::$liveurl;
			} else {
				return WC_Gateway_Opayo_Reporting::$testurl;
			}

		}

		/**
		 * [get_command description]
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function get_command( $order_id ) {

			$command = 'getTransactionDetail';

			return $command;
		}

		/**
		 * [get_encryption_key description]
		 * @return [type] [description]
		 */
		public static function get_encryption_key() {

			$key = NONCE_SALT;

			if( !isset( $key ) || strlen( $key ) > 1 ) {
				$key = 'hax|y3_P-Y[Ybj~%jN_7JDro_!yaS #D23hax|y3_P-Y[Ybj~%jN_7JDro_!yaS #D23';
			}

			return $key;
		}

		/**
		 * [encrypt_value description]
		 * @param  [type] $value [description]
		 * @return [type]        [description]
		 */
		public static function encrypt_value( $value ) {
			$key = WC_Gateway_Opayo_Reporting::get_encryption_key();
			return openssl_encrypt( $value, 'AES-128-CBC', $key, NULL, substr($key, -16) );
		}

		/**
		 * [decrypt_value description]
		 * @param  [type] $value [description]
		 * @return [type]        [description]
		 */
		public static function decrypt_value( $value ) {
			$key = WC_Gateway_Opayo_Reporting::get_encryption_key();
			return openssl_decrypt( $value, 'AES-128-CBC', $key, NULL, substr($key, -16) );
		}

		/**
		 * [get_thirdman_action description]
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function get_thirdman_action( $order_id ) {

			$t3maction 	= NULL;

			$t3maction 	= get_post_meta( $order_id, '_opayo_thirdman_action', TRUE );

			if( empty( $t3action ) || is_null( $tmaction ) ) {
				$output 	= get_post_meta( $order_id, '_opayo_reporting_output', TRUE );
				if( isset( $output['t3action'] ) ) {
					$t3maction 	= $output['t3action'];
				}
			}

			return $t3maction ;
		}

		/**
		 * [getFraudScreenDetail_action description]
		 * @param  [type] $order_id [description]
		 * @return [type]           [description]
		 */
		public static function getFraudScreenDetail_action( $order_id ) {

			$action 	= NULL;

			$action 	= get_post_meta( $order_id, '_opayo_thirdman_action', TRUE );

			if( empty( $t3action ) || is_null( $tmaction ) ) {
				$output 	= get_post_meta( $order_id, '_opayo_reporting_output', TRUE );
				if( isset( $output['t3action'] ) ) {
					$action 	= $output['t3action'];
				}
			}

			return $action ;
		}

		public static function developer() {
			// Developer overrides
			return apply_filters( 'opayo_reporting_developer_overide', false );
		}

	} // End class
	
	$WC_Gateway_Opayo_Reporting = new WC_Gateway_Opayo_Reporting;
