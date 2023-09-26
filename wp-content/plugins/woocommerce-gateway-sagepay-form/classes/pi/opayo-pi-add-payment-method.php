<?php

    /**
     * WC_Gateway_Opayo_Pi_Add_Payment_Method class.
     *
     * @extends WC_Gateway_Opayo_Pi
     *
     * void abort release
     */
    class WC_Gateway_Opayo_Pi_Add_Payment_Method extends WC_Gateway_Opayo_Pi {

        public function __construct() {

            parent::__construct();

            $this->settings     = get_option( 'woocommerce_opayopi_settings' );

        }
    
        function add_payment_method() {

            try {
            
                if( is_user_logged_in() ) {     
/*                
                    $card_details = array(
                                "merchantSessionKey"        => wc_clean( $_POST['opayopi-merchantSessionKey'] ),
                                "cardholderName"            => $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
                                "cardNumber"                => isset( $_POST['opayopi-card-number'] ) ? wc_clean( $_POST['opayopi-card-number'] ) : NULL,
                                "expiryDate"                => isset( $_POST['opayopi-card-expiry'] ) ? wc_clean( $_POST['opayopi-card-expiry'] ) : NULL,
                                "securityCode"              => isset( $_POST['opayopi-card-cvc'] ) ? wc_clean( $_POST['opayopi-card-cvc'] ) : NULL,
                                "savecard"                  => isset( $_POST['wc-opayopi-new-payment-method'] ) ? wc_clean( $_POST['wc-opayopi-new-payment-method'] ) : false,
                                "token_id"                  => isset( $_POST['wc-opayopi-payment-token'] ) ? wc_clean( $_POST['wc-opayopi-payment-token'] ) : false,
                                "cardIdentifier"            => isset( $_POST['opayopi-cardIdentifier'] ) ? wc_clean( $_POST['opayopi-cardIdentifier'] ) : false,
                                "browserJavascriptEnabled"  => wc_clean( $_POST['browserJavascriptEnabled'] ) == 'true' ? 1 : 0,
                                "browserJavaEnabled"        => wc_clean( $_POST['browserJavaEnabled'] ) == 'true' ? 1 : 0,
                                "browserLanguage"           => isset( $_POST['browserLanguage'] ) && $_POST['browserLanguage'] != '' ? wc_clean( $_POST['browserLanguage'] ) : substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2),
                                "browserColorDepth"         => wc_clean( $_POST['browserColorDepth'] ),
                                "browserScreenHeight"       => wc_clean( $_POST['browserScreenHeight'] ),
                                "browserScreenWidth"        => wc_clean( $_POST['browserScreenWidth'] ),
                                "browserTZ"                 => wc_clean( $_POST['browserTZ'] ),
                                "browserUserAgent"          => isset( $_POST['browserUserAgent'] ) && $_POST['browserUserAgent'] != '' ? wc_clean( $_POST['browserUserAgent'] ) : $_SERVER['HTTP_USER_AGENT'],
                                "challengeWindowSize"       => $common_threeds::get_challenge_window_size( wc_clean( $_POST['browserScreenWidth'] ), wc_clean( $_POST['browserScreenHeight'] ) ),
                                "transType"                 => $this->get_transType( $order )
                            );
*/
//                    $new_token = $this->get_new_payment_method( $data, $card_form );


$card_details = array(
    "opayopi-card-number"           => 4929 0000 0000 6
    "opayopi-card-expiry"           => 12 / 26
    "opayopi-card-cvc"              => 123
    "merchantSessionKey"    => wc_clean( $_POST['opayopi-merchantSessionKey'] ),
    "browserJavaEnabled"            => null
    "browserJavascriptEnabled"      => true
    "browserLanguage"               => en-GB
    "browserColorDepth"             => 24
    "browserScreenHeight"           => 1117
    "browserScreenWidth"            => 1728
    "browserTZ"                     => 0
    "browserUserAgent"              => Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.2 Safari/605.1.15
    "woocommerce_add_payment_method" => 1
);





                    if( $new_token ) {

                        return array(
                            'result'   => 'success',
                            'redirect' => wc_get_endpoint_url( 'payment-methods' ),
                        );

                    } else {
                        throw new Exception( __( 'There was a problem adding your payment method', 'woocommerce-gateway-sagepay-form' ) . '<pre>' . print_r( $_REQUEST, TRUE ) . '</pre>' );
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

	} // END CLASS
