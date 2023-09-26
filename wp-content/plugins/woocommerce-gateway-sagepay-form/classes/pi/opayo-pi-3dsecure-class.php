<?php

/**
 * WC_Gateway_Opayo_Pi_3DSecure class.
 *
 * @extends WC_Gateway_Opayo_Pi
 */
class WC_Gateway_Opayo_Pi_3DSecure extends WC_Gateway_Opayo_Pi {

    private $order_id;

    public function __construct( $order_id ) {

    parent::__construct();

        $this->order_id   = $order_id;
        $this->settings   = get_option( 'woocommerce_opayopi_settings' );

    }

    function authorise() {
        global $woocommerce;

        // woocommerce order instance
        $order_id = $this->order_id;
        $order    = wc_get_order( $order_id );

        $opayo_3dsecure  = WC()->session->get( "opayo_3ds" );

        if( !isset( $opayo_3dsecure['Status'] ) ) {
            $opayo_3dsecure  = get_post_meta( $order_id, '_opayo_3ds', TRUE );
        }

            if ( isset($_POST['PARes']) || isset($_POST['PaRes']) ) {

                // set the URL that will be posted to.
                $url = str_replace( '<transactionId>', $_POST['MD'], $this->callbackURL );

                // it could be PARes or PaRes #sigh
                if( isset($_POST['PARes']) ) {
                    $pares = $_POST['PARes'];
                } else {
                    $pares = $_POST['PaRes'];
                }

                $data = array(
                    "paRes" => $pares
                );

                // Send $data to Sage
                $result = $this->remote_post( $data, $url, NULL, 'Basic' );

                if( isset( $result['status'] ) && $result['status'] == 'Authenticated' ) {
                    // Get the full status of the transaction
                    $remote_get_url = str_replace( '<transactionId>', $_POST['MD'], $this->retrieve_url );
                    $result = $this->remote_get( $remote_get_url, NULL, 'Basic' );

                    $this->successful_payment( $result, $order, __('Payment completed', 'woocommerce-gateway-sagepay-form') );
                    wp_redirect( $this->get_return_url( $order ) );
                    exit;
                }

                if( isset( $result['status'] ) && $result['status'] !== 'Authenticated' ) {
                    throw new Exception( __('Payment failed.<br />Please check your billing address and card details, including the CVC number on the back of the card.<br />Your card has not been charged', 'woocommerce-gateway-sagepay-form')  );
                    wp_redirect( $order->get_checkout_payment_url() );
                    exit;
                }

            }

            $transactionId  = WC()->session->get( "transactionId" );
            $acsUrl         = WC()->session->get( "acsUrl" );
            $paReq          = WC()->session->get( "paReq" );

            $form = '<form id="submitForm" method="post" action="' . $acsUrl . '">
                        <input type="hidden" name="PaReq" value="' . $paReq . '"/>
                        <input type="hidden" name="transactionId" value="' . $transactionId . '"/>
                        <input type="hidden" id="termUrl" name="TermUrl" value="' . $order->get_checkout_payment_url( true ) . '"/>
                        <script>
                            document.getElementById("submitForm").submit();
                        </script>
                    </form>';

            echo $form;


} // End class
