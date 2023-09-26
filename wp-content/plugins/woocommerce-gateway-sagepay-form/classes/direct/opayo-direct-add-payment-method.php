<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
    
	/**
	 * Process the payment form
	 */
	class Opayo_Direct_Add_Payment_Method extends WC_Gateway_Sagepay_Direct {

		public function __construct() {

			parent::__construct();

			$this->settings 	= get_option( 'woocommerce_sagepaydirect_settings' );

		}
	
		function add_payment_method() {

			try {
        	
				if( is_user_logged_in() ) {     
				
					$sage_card_type 		= isset($_POST[$this->id . '-card-type']) ? wc_clean($_POST[$this->id . '-card-type']) : '';
					$sage_card_number 		= isset($_POST[$this->id . '-card-number']) ? wc_clean($_POST[$this->id . '-card-number']) : '';
					$sage_card_cvc 			= isset($_POST[$this->id . '-card-cvc']) ? wc_clean($_POST[$this->id . '-card-cvc']) : '';
					$sage_card_expiry		= isset($_POST[$this->id . '-card-expiry']) ? wc_clean($_POST[$this->id . '-card-expiry']) : '';
					$sage_card_expiry_mon	= isset($_POST[$this->id . '-card-expiry-month']) ? wc_clean($_POST[$this->id . '-card-expiry-month']) : false;
					$sage_card_expiry_year	= isset($_POST[$this->id . '-card-expiry-year']) ? wc_clean($_POST[$this->id . '-card-expiry-year']) : false;

					// Format values
					$sage_card_number    	= str_replace( array( ' ', '-' ), '', $sage_card_number );
					// Allow for old template file which uses text box for card expiry date
					if( $sage_card_expiry ) {
						$sage_card_exp_month 	= $this->get_card_expiry_date( $sage_card_expiry, 'month' );
						$sage_card_exp_year 	= $this->get_card_expiry_date( $sage_card_expiry, 'year' );
					} else {
						$sage_card_exp_month 	= $sage_card_expiry_mon;
						$sage_card_exp_year 	= $this->get_card_expiry_date( $sage_card_expiry_year, 'year' );
					}

					$current_user   		= wp_get_current_user();
					$CardHolder 			= $current_user->billing_first_name . ' ' . $current_user->billing_last_name;

					// Set $cardholder for testing 3D Secure 2.0
					if( $this->get_status() != 'live' && $this->get_sagemagicvalue() != 'No Magic Value' ) {
						$CardHolder = $this->get_sagemagicvalue();
					}

					$data    = array(
		                "VPSProtocol"       => $this->get_vpsprotocol(),
		                "TxType"            => 'TOKEN',
		                "Vendor"            => $this->get_vendor(),
		                "Currency"          => get_woocommerce_currency(),
						"CardHolder" 		=> $CardHolder,
						"CardNumber" 		=> $sage_card_number,
						"ExpiryDate"		=> $sage_card_exp_month . $sage_card_exp_year,
						"CV2"				=> $sage_card_cvc,
						"CardType"			=> $this->cc_type( $sage_card_number, $sage_card_type ),
		            );

		            $card_form = array( 
		            	"sage_card_type" 		=> $sage_card_type ,
		            	"sage_card_number" 		=> substr( $sage_card_number, -4 ), 
		            	"sage_card_exp_month" 	=> $sage_card_exp_month, 
		            	"sage_card_exp_year" 	=> $sage_card_exp_year
		            );

		            $new_token = $this->get_new_payment_method( $data, $card_form );

		            if( $new_token ) {

		            	return array(
							'result'   => 'success',
							'redirect' => wc_get_endpoint_url( 'payment-methods' ),
						);

		            } else {
		            	throw new Exception( __( 'There was a problem adding your payment method', 'woocommerce-gateway-sagepay-form' ) );
		            }

		        } else {
		        	throw new Exception( __( 'Please login to add a new payment method', 'woocommerce-gateway-sagepay-form' ) );
		        }

			} catch( Exception $e ) {

	        	// Add the error message
				if( is_callable( 'wc_add_notice' ) ) {
					wc_add_notice( $e->getMessage(), 'error' );
				}

				// Redirect for a retry
                wp_redirect( wc_get_endpoint_url( 'payment-methods' ) );
                exit;

			}

		}

		/**
		 * [get_new_payment_method description]
		 * @param  [type] $data      [description]
		 * @param  [type] $card_form [description]
		 * @return [type]            [description]
		 */
		function get_new_payment_method( $data, $card_form ) {

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
					
					return $sageresult['Token'];

				}

			}

			return false;
		}

	} // End class
