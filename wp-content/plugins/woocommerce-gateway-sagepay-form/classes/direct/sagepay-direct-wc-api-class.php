<?php
    /**
     * Process Response for SagePay Direct
     */
    class Sagepay_Direct_Api extends WC_Gateway_Sagepay_Direct {

        public function __construct() {

            parent::__construct();

        }

        function process_api() {

            global $wpdb;

            $settings   = get_option( 'woocommerce_sagepaydirect_settings' );

            // Drop out if SagePay Direct is not enabled
            if( !isset( $settings['enabled'] ) || $settings['enabled'] != "yes" ) {
                wp_die( "Sage Request Failure<br />" . 'Access denied', "Sage Failure", array( 'response' => 200 ) );
                exit;
            }

            if ( isset( $_GET["vtx"] ) ) {

                $vtx = wc_clean( $_GET["vtx"] );

                // Check if we have created an invoice before this order
                $stored_value = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_VendorTxCode' AND meta_value = %s;", $vtx ) );

                if ( null !== $stored_value ) {

                    $order_id   = $stored_value->post_id;
                    $order      = wc_get_order( $order_id );

                    // PayPal
                    if( isset( $settings['enabled'] ) && isset( $settings['cardtypes'] ) && $settings['enabled'] == "yes" && ( $key = array_search('PayPal', $settings['cardtypes']) ) !== false ) {

                        $sageresult = $this->process_response( $_POST, $order );

                        if( in_array( $_POST['Status'], array( 'OK', 'PAYPALOK' ) ) ) {
                            $redirect = $this->get_return_url( $order );
                        } else {
                            $redirect = $sageresult['redirect'];
                        }

                        wp_redirect( $redirect );
                        exit;

                    } else {
                        wp_die( "Opayo Request Failure<br />" . 'Check the WooCommerce SagePay Settings for error messages', "Opayo Failure", array( 'response' => 200 ) );
                    }

                } else {
                    wp_die( "Opayo Request Failure<br />" . 'Check the WooCommerce SagePay Settings for error messages', "Opayo Failure", array( 'response' => 200 ) );
                }
                
            } elseif ( isset( $_GET["threedsecure"] ) ) {

                $threedsecure = wc_clean( $_GET["threedsecure"] );

                $stored_value = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_VendorTxCode' AND meta_value = %s;", $threedsecure ) );

                if ( null !== $stored_value ) {

                    $order_id   = $stored_value->post_id;
                    $order      = wc_get_order( $order_id );

                    // Debugging
                    if ( $this->debug == true || $this->status != 'live' ) {
                        $_REQUEST['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
                        WC_Sagepay_Common_Functions::sagepay_debug( $_REQUEST, $this->id . '_' . $order_id, __('3D Secure Return : ', 'woocommerce-gateway-sagepay-form'), FALSE );
                    }

                    // Make sure order has not been paid for.
                    if ( !$this->opayo_needs_payment( $order ) ) {

                        $order->add_order_note( __( 'Opayo Status 00 : ', 'woocommerce-gateway-sagepay-form' ) . $order->get_status() );
                        // Redirect customer to thank you page
                        $redirect_url = $this->append_url( $order->get_checkout_order_received_url() );

                        unset( $_POST['CRes'] );
                        unset( $_POST['PARes'] );

                        WC()->session->set( "sage_3ds", "" );
                        delete_post_meta( $order_id, '_sage_3ds' );

                        $this->opayo_redirect( $redirect_url );

                    }

                    $sage_3dsecure  = WC()->session->get( "sage_3ds" );

                    if( !isset( $sage_3dsecure['Status'] ) ) {
                        $sage_3dsecure  = get_post_meta( $order_id, '_sage_3ds', TRUE );
                    }

                    try {

                        // set the URL that will be posted to.
                        $url = $this->callbackURL;

                        if( ( isset( $_POST['CRes'] ) && $_POST['CRes'] != '' ) || ( isset( $_POST['cres'] ) && $_POST['cres'] != '' ) ) {

                            $key = 'CRes';

                            if( isset($_POST['CRes']) ) {
                                $value = $_POST['CRes'];
                            } else {
                                $value = $_POST['cres'];
                            }

                            // Store the CRes value
                            // update_post_meta( $order_id, '_opayo_callback_value', $value );

                            // Set the data for Sage
                            $data      = array(
                                $key => $value
                            );

                            $data['VPSTxId'] = isset( $sage_3dsecure['VPSTxId'] ) ? $sage_3dsecure['VPSTxId'] : $_POST['VPSTxId'];

                        }

                        // Fallback for 3DS 1.0
                        if( ( isset( $_POST['PARes'] ) && $_POST['PARes'] != '' ) || ( isset( $_POST['PaRes'] ) && $_POST['PaRes'] != '' ) ) {

                            $key = 'PARes';

                            if( isset($_POST['PARes']) ) {
                                $value = $_POST['PARes'];
                            } else {
                                $value = $_POST['PaRes'];
                            }

                            // Store the PARes value
                            // update_post_meta( $order_id, '_opayo_callback_value', $value );

                            // Set the data for Opayo
                            $data      = array(
                                $key => $value
                            );
                            
                            $data['MD'] = isset( $sage_3dsecure['MD'] ) ? $sage_3dsecure['MD'] : $_POST['MD'];

                        }

                        /**
                         * Send $data to Opayo
                         * @var [type]
                        */
                        $sageresult = $this->sagepay_post( $data, $url );

                        if( isset( $sage_3dsecure['change_payment_method'] ) && class_exists( 'WC_Subscriptions' ) ) {

                            WC()->session->set( "sage_3ds", "" );
                            delete_post_meta( $order_id, '_sage_3ds' );

                            // Move the Opayo Result Status to Status to OpayoStatus
                            $sageresult['OpayoStatus']  = $sageresult['Status'];

                            // Set Status to CHANGEPAYMENTMETHODNEWTOKEN so we can process this as a new token payment method change
                            $sageresult['Status']       = 'CHANGEPAYMENTMETHODNEWTOKEN';

                            // Procee the result in Sagepay Direct Response class
                            $this->process_response( $sageresult, $order );
                            // Stops here.
                        }

                        if( isset( $sageresult['Status']) && $sageresult['Status']!= '' ) {

                            // Successful 3D Secure
                            $sageresult   = $this->process_response( $sageresult, $order );

                            switch( strtoupper( $sageresult['Status'] ) ) {
                                case 'OK':
                                case 'REGISTERED':
                                case 'AUTHENTICATED':

                                    // Temporary order note
                                    $order->add_order_note( __( 'Opayo Status 01: ', 'woocommerce-gateway-sagepay-form' ) . $sageresult['Status'] . ' ' . $order->get_checkout_order_received_url() );

                                    // Redirect customer to thank you page
                                    $redirect_url = $this->append_url( $order->get_checkout_order_received_url() );
                                    
                                break;
                            default :
                                if ( !$this->opayo_needs_payment( $order ) ) {
                                    $order->add_order_note( __( 'Opayo Status 02A: ', 'woocommerce-gateway-sagepay-form' ) . $order->get_status() );
                                    // Redirect customer to thank you page
                                    $redirect_url = $this->append_url( $order->get_checkout_order_received_url() );
                                } else {
                                    $redirect_url = $this->append_url( wc_get_page_permalink('checkout') );
                                    $order->add_order_note( __( 'Opayo Status 02B: ', 'woocommerce-gateway-sagepay-form' ) . $order->get_status() );
                                    throw new Exception( __('Payment error, please try again. Your card has not been charged.' , 'woocommerce_sagepayform' ) );                                
                                }
                            }

                        } else {

                            // No status from Opayo
                            if ( !$this->opayo_needs_payment($order) ) {
                                $order->add_order_note( __( 'Opayo Status 03: ', 'woocommerce-gateway-sagepay-form' ) . $order->get_status() );
                                // Redirect customer to thank you page
                                $redirect_url = $this->append_url( $order->get_checkout_order_received_url() );
                            } else {
                                $redirect_url = $this->append_url( wc_get_page_permalink('checkout') );
                                $order->add_order_note( __( 'Opayo Status 04: ', 'woocommerce-gateway-sagepay-form' ) . $order->get_status() );
                                throw new Exception( __('Payment error, please try again. Your card has not been charged.' , 'woocommerce_sagepayform' ) );                                
                            }

                        }

                    } catch( Exception $e ) {
                        $this->sagepay_message( $e->getMessage(), 'error', $order_id );
                    }

                    unset( $_POST['CRes'] );
                    unset( $_POST['PARes'] );

                    WC()->session->set( "sage_3ds", "" );
                    delete_post_meta( $order_id, '_sage_3ds' );

                    // JavaScript Redirect
                    $this->opayo_redirect( $redirect_url );
                    // Stops here.

                } else {
                    wp_die( "Opayo Request Failure<br />" . 'Check the WooCommerce SagePay Settings for error messages', "Opayo Failure", array( 'response' => 200 ) );
                }


            } elseif ( isset( $_GET['threedsecureform'] ) ) {

                ob_start();

                $order_id       = $_GET['threedsecureform'];
                $iframe_args    = get_post_meta( $order_id, '_iframe_args', TRUE );

                // iFrame Method
                $form  = '<p>Your card issuer has requested additional authorisation for this transaction, please wait while you are redirected.</p>';
                $form .= '<form id="submitForm" method="post" action="' . $iframe_args['ACSURL'] . '">';
                $form .= '<input type="hidden" name="' . $iframe_args['name_one'] . '" value="' . $iframe_args['value_one'] . '"/>';
                $form .= '<input type="hidden" name="' . $iframe_args['name_two'] . '" value="' . $iframe_args['value_two'] . '"/>';
                $form .= '<input type="hidden" id="termUrl" name="TermUrl" value="' . $iframe_args['termUrl'] . '"/>';
                $form .= '<noscript><p>Authenticate your card</p><p><input type="submit" value="Submit"></p></noscript>';
                $form .= '<script>document.getElementById("submitForm").submit();</script>';
                $form .= '</form>';

                // Load custom wp_die class
                include('opayo-die-class.php');
                new WC_Opayo_Die();
                 
                wp_die( $form, "Additional Authorisation", array( 'response' => 200 ) );

                ob_end_flush();

            } else {
                wp_die( "Opayo Request Failure<br />" . 'Check the WooCommerce Opayo Settings for error messages', "Opayo Failure", array( 'response' => 200 ) );
            }          

        }

    } // End class
