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
 * Version 2.0 since version 4.9.0
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

	$months_array = array(
						'0'  => __( "Month", 'woocommerce-gateway-sagepay-form' ),
						'01' => __( "01", 'woocommerce-gateway-sagepay-form' ),
						'02' => __( "02", 'woocommerce-gateway-sagepay-form' ),
						'03' => __( "03", 'woocommerce-gateway-sagepay-form' ),
						'04' => __( "04", 'woocommerce-gateway-sagepay-form' ),
						'05' => __( "05", 'woocommerce-gateway-sagepay-form' ),
						'06' => __( "06", 'woocommerce-gateway-sagepay-form' ),
						'07' => __( "07", 'woocommerce-gateway-sagepay-form' ),
						'08' => __( "08", 'woocommerce-gateway-sagepay-form' ),
						'09' => __( "09", 'woocommerce-gateway-sagepay-form' ),
						'10' => __( "10", 'woocommerce-gateway-sagepay-form' ),
						'11' => __( "11", 'woocommerce-gateway-sagepay-form' ),
						'12' => __( "12", 'woocommerce-gateway-sagepay-form' )
					);

	$years_array = array(
						'0'  		=> __( "Year", 'woocommerce-gateway-sagepay-form' ),
						date('Y' ) => date( 'Y' ),
						date('Y', strtotime('+1 year') ) => date('Y', strtotime('+1 year') ),
						date('Y', strtotime('+2 year') ) => date('Y', strtotime('+2 year') ),
						date('Y', strtotime('+3 year') ) => date('Y', strtotime('+3 year') ),
						date('Y', strtotime('+4 year') ) => date('Y', strtotime('+4 year') ),
						date('Y', strtotime('+5 year') ) => date('Y', strtotime('+5 year') ),
						date('Y', strtotime('+6 year') ) => date('Y', strtotime('+6 year') ),
						date('Y', strtotime('+7 year') ) => date('Y', strtotime('+7 year') ),
						date('Y', strtotime('+8 year') ) => date('Y', strtotime('+8 year') ),
						date('Y', strtotime('+9 year') ) => date('Y', strtotime('+9 year') ),
						date('Y', strtotime('+10 year') ) => date('Y', strtotime('+10 year') ),
						date('Y', strtotime('+11 year') ) => date('Y', strtotime('+11 year') ),
						date('Y', strtotime('+12 year') ) => date('Y', strtotime('+12 year') ),
						date('Y', strtotime('+13 year') ) => date('Y', strtotime('+13 year') ),
						date('Y', strtotime('+14 year') ) => date('Y', strtotime('+14 year') ),
						date('Y', strtotime('+15 year') ) => date('Y', strtotime('+15 year') ),
						date('Y', strtotime('+16 year') ) => date('Y', strtotime('+16 year') ),
						date('Y', strtotime('+17 year') ) => date('Y', strtotime('+17 year') ),
						date('Y', strtotime('+18 year') ) => date('Y', strtotime('+18 year') ),
						date('Y', strtotime('+19 year') ) => date('Y', strtotime('+19 year') ),
						date('Y', strtotime('+20 year') ) => date('Y', strtotime('+20 year') ),
					);

	$card_options = '<option value = "0">Card Type</option>';
	foreach ( $cardtypes as  $key => $value ) {
		$card_options .= '<option value="' . $value . '">' . $sage_cardtypes[$value] . '</option>';
	}

	$month_dropdown = '';
	foreach ( $months_array as  $key => $value ) {
		$month_dropdown .= '<option value="' . $key . '">' . $value . '</option>';
	}

	$year_dropdown = '';
	foreach ( $years_array as  $key => $value ) {
		$year_dropdown .= '<option value="' . $key . '">' . $value . '</option>';
	}

	$fields = array(
		'card-type-field' => 
			'<div class="form-row form-row-wide not-for-token">
				<label for="' . $gateway_id . '-card-type">' . __( "Card Type", 'woocommerce-gateway-sagepay-form' ) . ' <span class="required">*</span></label>
	        	<select id="' . $gateway_id . '-card-type" class="input-text wc-credit-card-form-card-type" name="' . $gateway_id . '-card-type" >' . $card_options . ' </select>
			</div>',
		'card-number-field' => 
			'<div class="form-row form-row-wide not-for-paypal not-for-token">
				<label for="' . $gateway_id . '-card-number">' . __( "Card Number", 'woocommerce-gateway-sagepay-form' ) . ' <span class="required">*</span></label>
				<input id="' . $gateway_id . '-card-number" class="input-text wc-credit-card-form-card-number" type="tel" inputmode="numeric" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . $gateway_id . '-card-number" />
			</div>',
		'card-expiry-field' => 
			'<div class="form-row form-row-first not-for-paypal not-for-token wc-credit-card-form-card-expiry-date-wrapper">
				<label for="' . $gateway_id . '-card-expiry">' . __( "Expiry Date", 'woocommerce-gateway-sagepay-form' ) . ' <span class="required">*</span></label>
				<select id="' . $gateway_id . '-card-expiry-month" class="input-text wc-credit-card-form-card-expiry-date" name="' . $gateway_id . '-card-expiry-month" >' . $month_dropdown . ' </select>
		       	<select id="' . $gateway_id . '-card-expiry-year" class="input-text wc-credit-card-form-card-expiry-date" name="' . $gateway_id . '-card-expiry-year" >' . $year_dropdown . ' </select>
			</div>',
		'card-cvc-field' => '<div id="sage-card-cvc" class="form-row form-row-last not-for-paypal">
			<label for="' . $gateway_id . '-card-cvc">' . __( "Card Code", 'woocommerce-gateway-sagepay-form' ) . ' <span class="required">*</span></label>
			<input id="' . $gateway_id . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="tel" inputmode="numeric" autocomplete="off" placeholder="CVC" name="' . $gateway_id . '-card-cvc" />
		</div>'
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

