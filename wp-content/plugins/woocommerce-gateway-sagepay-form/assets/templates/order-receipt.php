<?php
/**
 * Checkout Order Receipt Template
 *
 * @version 4.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php 

/*
	Examples of filter usage: 
	add_filter( 'woocommerce_opayo_threeds_show_customer_message', 'remove_woocommerce_opayo_threeds_show_customer_message', 10, 2 );
	function remove_woocommerce_opayo_threeds_show_customer_message( $show, $order ) {
		return TRUE;
	}

	add_filter( 'woocommerce_opayo_threeds_customer_message', 'modify_woocommerce_opayo_threeds_customer_message', 10, 2 );
	function modify_woocommerce_opayo_threeds_customer_message( $message, $order ) {
		return "<h1>Your card issuer has requested additional authorisation. Please do not refresh this page.</h1>";
	}
 */

if( apply_filters( 'woocommerce_opayo_threeds_show_customer_message', TRUE, $order ) ) {
	_e( apply_filters( 'woocommerce_opayo_threeds_customer_message', "Your card issuer has requested additional authorisation. Please do not refresh this page.", 'woocommerce-gateway-sagepay-form', $order ) ); 
}
?>

<?php do_action( 'woocommerce_receipt_' . $order->get_payment_method(), $order->get_id() ); ?>

<div class="clear"></div>