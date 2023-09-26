<?php
    /**
     * Need to load wp-load.php so that we can use all of the
     * WordPress / WooCommerce / Subscriptions functions
     */
    $parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
    require_once( $parse_uri[0] . 'wp-load.php' );

    $MD             = WC()->session->get( 'MD' );
    $PAReq          = WC()->session->get( 'PAReq' );
    $ACSURL         = WC()->session->get( 'ACSURL' );
    $TermURL        = WC()->session->get( 'TermURL' );
    $Complete3d     = WC()->session->get( 'Complete3d' );
    $VendorTxCode   = WC()->session->get( 'VendorTxCode' );

    // alphanumeric unlimited length base64 encoded    
    if ( base64_decode( @$_REQUEST['PaRes'], true ) ) {
        $pares = $_REQUEST['PaRes'];
    } else {
        $pares = "";
    }
        
    // alphanumeric max 35 chars
    if( preg_match( '/^[A-Z0-9]{1,35}$/i', @$_REQUEST['MD'] ) ) {
        $md = $_REQUEST['MD'];
    } else {
        $md = '';    
    }
    
    $page = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">' .
            '<html><head>' .
            '<script type="text/javascript"> function OnLoadEvent() { document.form.submit(); }</script>' .
            '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />' .
            '<title>3D-Secure Redirect</title></head>' .
            '<body OnLoad="OnLoadEvent();">' .
            '<form name="form" action="'. $Complete3d .'" method="POST"  target="_top" >' .
            '<input type="hidden" name="PARes" value="' . $pares . '"/>' .
            '<input type="hidden" name="MD" value="' . $md . '"/>' .
            '<input type="hidden" name="VendorTxCode" value="' . $VendorTxCode . '"/>' .
            '<noscript>' .
            '<center><p>Please click button below to Authenticate your card</p><input type="submit" value="Go"/></p></center>' .
            '</noscript>' .
            '</form></body></html>';
            
    echo $page;
?>
