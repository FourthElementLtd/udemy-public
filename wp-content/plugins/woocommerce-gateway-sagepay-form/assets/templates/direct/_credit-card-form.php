<?php
/**
 * SagePay Credit Card Form
 *
 * This template can be overridden by copying it to yourtheme/sagepay/credit-card-form.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	wp_enqueue_script( 'wc-credit-card-form' );

	// Remove PayPal if there is a subscription product in the cart. 
	if ( ( class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) || isset( $_GET['change_payment_method'] ) ) {

		if ( ($key = array_search('PayPal', $cardtypes)) !== false ) {
		    unset( $cardtypes[$key] );
		}
	}

	$card_options = '<option value = "0">Card Type</option>';
	foreach ( $cardtypes as  $key => $value ) {
		$card_options .= '<option value="' . $value . '">' . $sage_cardtypes[$value] . '</option>';
	}

	$fields = array(
		'card-type-field' => '<p class="form-row form-row-wide not-for-token">
			<label for="' . $gateway_id . '-card-type">' . __( "Card Type", 'woocommerce-gateway-sagepay-form' ) . ' <span class="required">*</span></label>
        	<select id="' . $gateway_id . '-card-type" class="input-text wc-credit-card-form-card-type" name="' . $gateway_id . '-card-type" >' . $card_options . ' </select>
		</p>',
		'card-number-field' => '<p class="form-row form-row-wide not-for-paypal not-for-token">
			<label for="' . $gateway_id . '-card-number">' . __( "Card Number", 'woocommerce-gateway-sagepay-form' ) . ' <span class="required">*</span></label>
			<input id="' . $gateway_id . '-card-number" class="input-text wc-credit-card-form-card-number" type="tel" inputmode="numeric" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . $gateway_id . '-card-number" />
		</p>',
		'card-expiry-field' => '<p class="form-row form-row-first not-for-paypal not-for-token">
			<label for="' . $gateway_id . '-card-expiry">' . __( "Expiry (MM/YY)", 'woocommerce-gateway-sagepay-form' ) . ' <span class="required">*</span></label>
			<input id="' . $gateway_id . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="tel" inputmode="numeric" autocomplete="off" placeholder="MM / YY" name="' . $gateway_id . '-card-expiry" />
		</p>',
		'card-cvc-field' => '<p id="sage-card-cvc" class="form-row form-row-last not-for-paypal">
			<label for="' . $gateway_id . '-card-cvc">' . __( "Card Code", 'woocommerce-gateway-sagepay-form' ) . ' <span class="required">*</span></label>
			<input id="' . $gateway_id . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="tel" inputmode="numeric" autocomplete="off" placeholder="CVC" name="' . $gateway_id . '-card-cvc" />
		</p>'
	);

	?>
	<fieldset id = "sagepaydirect-cc-form" class="wc-payment-form">
<?php 			
		do_action( 'woocommerce_credit_card_form_before', $gateway_id ); 
		foreach( $fields as $field ) {
			echo $field;
		}
		do_action( 'woocommerce_credit_card_form_after', $gateway_id ); 
?>
		<div class="clear"></div>
	</fieldset>

