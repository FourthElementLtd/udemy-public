<?php
/**
 * Extend WooCommerce Subscriptions Payment Tokens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Opayo_Payment_Tokens extends WCS_Payment_Tokens {

    public static function update_subscription_token( $subscription, $new_token, $old_token ) {
    	
    }

}

$GLOBALS['WC_Opayo_Payment_Tokens'] = new WC_Opayo_Payment_Tokens();