<?php
    /**
     * Need to load wp-load.php so that we can use all of the
     * WordPress / WooCommerce / Subscriptions functions
     */
    $parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
    require_once( $parse_uri[0] . 'wp-load.php' );

    $MD         = WC()->session->get( 'MD' );
    $PAReq      = WC()->session->get( 'PAReq' );
    $ACSURL     = WC()->session->get( 'ACSURL' );
    $TermURL    = WC()->session->get( 'TermURL' );

    $page  = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">' .
             '<html><head>' .
             '<script type="text/javascript"> function OnLoadEvent() { document.form.submit(); }</script>' .
             '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . 
             '<title>3D-Secure Redirect</title>' .             
             '</head>' . 
             '<body OnLoad="OnLoadEvent();">';
    if( isset(  $_SESSION['msg'] ) ) {
        $page .= '<div style="text-align: center; font-family: sans-serif; font-size: 2em; line-height: 1.5; color: #777;" >' . $_SESSION['msg'] . '</div>';
    }
    $page .= '<form name="form" action="' . $ACSURL . '" method="POST" >' .
             '<input type="hidden" name="PaReq" value="' . $PAReq . '"/>' .
             '<input type="hidden" name="MD" value="' . $MD . '"/>' .
             '<input type="hidden" name="TermUrl" value="' . $TermURL . '"/>' .
             '<noscript>' .
             '<center><p>Please click button below to Authenticate your card</p><input type="submit" value="Go"/></p></center>' .
             '</noscript>' .
             '</form></body></html>';

    echo $page;
    
    WC()->session->set( "PAReq", "" );
?>