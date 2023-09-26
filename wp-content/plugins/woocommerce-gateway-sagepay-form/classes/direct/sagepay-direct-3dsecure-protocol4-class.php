<?php
/**
* 3D Secure for SagePay Direct
*/
class Sagepay_Direct_3DSecure_4 extends WC_Gateway_Sagepay_Direct {

    private $order_id;

    public function __construct( $order_id ) {

    parent::__construct();

        $this->order_id   = $order_id;
        $this->settings   = get_option( 'woocommerce_sagepaydirect_settings' );

    }

    function authorise() {
        global $woocommerce;

        // woocommerce order instance
        $order_id = $this->order_id;
        $order    = wc_get_order( $order_id );

        // Delete _opayo_callback_value
        delete_post_meta( $order_id, '_opayo_callback_value' );

        $sage_3dsecure  = WC()->session->get( "sage_3ds" );

        if( !isset( $sage_3dsecure['Status'] ) ) {
            $sage_3dsecure  = get_post_meta( $order_id, '_sage_3ds', TRUE );
        }

        $key      = 'CRes';
        $value    = '';

        $redirect_url = $this->get_return_url( $order );

        // Get ready to set form fields for 3DS 1.0/2.0
        $p = $this->pareq_or_creq ( $sage_3dsecure );
        $m = $this->md_or_vpstxid ( $sage_3dsecure );

        $sage_3dsecure['Complete3d'] = add_query_arg( 'threedsecure', get_post_meta( $order_id, '_VendorTxCode', TRUE ), $this->successurl );

        $iframe_args = array( 
                            "name_one"      => $p["field_name"],
                            "value_one"     => $p["field_value"],
                            "name_two"      => $m["field_name"],
                            "value_two"     => $m["field_value"],
                            "termUrl"       => $sage_3dsecure['Complete3d'],
                            "ACSURL"        => $sage_3dsecure['ACSURL'],
                        );

        update_post_meta( $order_id, '_iframe_args', $iframe_args );

        $iframe_url = add_query_arg( 'threedsecureform', $order_id, $this->successurl );

        // Log data sent for 3DS
        if ( $this->debug == true || $this->status != 'live' ) {
            WC_Sagepay_Common_Functions::sagepay_debug( $iframe_args, $this->id, __('3D Secure form data : ', 'woocommerce-gateway-sagepay-form'), TRUE );
        }

        if( !isset($this->threeDSMethod) || $this->threeDSMethod === "1" ) {

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

        } else {

            // iFrame Method
            $form  = '<p>Your card issuer has requested additional authorisation for this transaction, please wait while you are redirected.</p>';
            $form .= '<form id="submitForm" method="post" action="' . $iframe_args['ACSURL'] . '">';
            $form .= '<input type="hidden" name="' . $iframe_args['name_one'] . '" value="' . $iframe_args['value_one'] . '"/>';
            $form .= '<input type="hidden" name="' . $iframe_args['name_two'] . '" value="' . $iframe_args['value_two'] . '"/>';
            $form .= '<input type="hidden" id="termUrl" name="TermUrl" value="' . $iframe_args['termUrl'] . '"/>';
            $form .= '<noscript><p>Authenticate your card</p><p><input type="submit" value="Submit"></p></noscript>';
            $form .= '<script>document.getElementById("submitForm").submit();</script>';
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

        }

    }

    function pareq_or_creq ( $sage_3dsecure ) {

        // Get ready to set form fields for 3DS 1.0/2.0
        if( isset( $sage_3dsecure['PAReq'] ) ) {
            $p = array(
                "field_name"    => "PaReq",
                "field_value"   => $sage_3dsecure['PAReq']
            );
        } else {
            $p = array(
                "field_name"    => "creq",
                "field_value"   => $sage_3dsecure['CReq']
            );
        }

        return $p;

    }

    function md_or_vpstxid ( $sage_3dsecure ) {

        if( isset( $sage_3dsecure['MD'] ) ) {
            $m = array(
                "field_name" => "MD",
                "field_value" => $sage_3dsecure['MD']
            );
        } else {
            $m = array(
                "field_name" => "VPSTxId",
                "field_value" => $sage_3dsecure['VPSTxId']
            );
        }

        return $m;
    }

} // End class
