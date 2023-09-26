<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
    
	/**
	 * Validate the payment form
	 */
	class Opayo_Direct_Validate extends WC_Gateway_Sagepay_Direct {

		public function __construct() {

			parent::__construct();

			$this->settings 	= get_option( 'woocommerce_sagepaydirect_settings' );

		}
	
		function validate() {

			try {

				$sage_card_type 		= isset($_POST[$this->id . '-card-type']) ? wc_clean($_POST[$this->id . '-card-type']) : '';
				$sage_card_number 		= isset($_POST[$this->id . '-card-number']) ? wc_clean($_POST[$this->id . '-card-number']) : '';
				$sage_card_cvc 			= isset($_POST[$this->id . '-card-cvc']) ? wc_clean($_POST[$this->id . '-card-cvc']) : '';
				$sage_card_expiry		= isset($_POST[$this->id . '-card-expiry']) ? wc_clean($_POST[$this->id . '-card-expiry']) : false;
				$sage_card_expiry_mon	= isset($_POST[$this->id . '-card-expiry-month']) ? wc_clean($_POST[$this->id . '-card-expiry-month']) : false;
				$sage_card_expiry_year	= isset($_POST[$this->id . '-card-expiry-year']) ? wc_clean($_POST[$this->id . '-card-expiry-year']) : false;
				$sage_card_save_token	= isset($_POST['wc-sagepaydirect-new-payment-method']) ? wc_clean($_POST['wc-sagepaydirect-new-payment-method']) : false;
				$sage_card_token 		= isset($_POST['wc-sagepaydirect-payment-token']) ? wc_clean($_POST['wc-sagepaydirect-payment-token']) : false;

				/**
				 * Check if we need to validate card form
				 */
				if( strtoupper($sage_card_type) == 'PAYPAL' ) {
					// No validation required for PayPal
					return true;

				} elseif ( $sage_card_token === false || $sage_card_token === 'new' ) {
					// Normal card transaction
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

					// Validate values
					if ( empty( $sage_card_type ) || ctype_digit( $sage_card_type ) || !in_array( $sage_card_type, $this->get_cardtypes() ) ) {
						throw new Exception( __( 'Please choose a card type', 'woocommerce-gateway-sagepay-form' ) );
					}

					if ( ( $this->override_opayo_cvv_requirement && !ctype_digit( $sage_card_cvc ) ) || ( $this->override_opayo_cvv_requirement && strlen( $sage_card_cvc ) < 3 ) || ( $this->override_opayo_cvv_requirement && strlen( $sage_card_cvc ) > 4 ) ) {
						throw new Exception( __( 'Card security code is invalid (only digits are allowed)', 'woocommerce-gateway-sagepay-form' ) );
					}

					if ( !ctype_digit( $sage_card_exp_month ) || $sage_card_exp_month > 12 || $sage_card_exp_month < 1 ) {
						throw new Exception( __( 'Card expiration month is invalid', 'woocommerce-gateway-sagepay-form' ) );
					}	

					if ( !ctype_digit( $sage_card_exp_year ) || $sage_card_exp_year < date('y') || strlen($sage_card_exp_year) != 2 ) {
						throw new Exception( __( 'Card expiration year is invalid', 'woocommerce-gateway-sagepay-form' ) );
					}

					if ( empty( $sage_card_number ) || ! ctype_digit( $sage_card_number ) ) {
						throw new Exception( __( 'Card number is invalid', 'woocommerce-gateway-sagepay-form' ) );
					}

					return true;

				} elseif( $this->get_cvv_script() && $sage_card_token !== false && $this->override_opayo_cvv_requirement ) {

					// Token transaction requiring the CVV number
					if ( !ctype_digit( $sage_card_cvc ) || strlen( $sage_card_cvc ) < 3  || strlen( $sage_card_cvc ) > 4 ) {
						throw new Exception( __( 'Card security code is invalid (only digits are allowed)', 'woocommerce-gateway-sagepay-form' ) );
					}
					return true;

				} else {

					return true;
					
				}

			} catch( Exception $e ) {

				if( is_callable( 'wc_add_notice' ) ) {
					wc_add_notice( $e->getMessage(), 'error' );
				}
				return false;

			}

		}

	} // End class
