<?php
	/**
	 * Opayo_Pi_Payment_Fields
	 */
	class Opayo_Pi_Payment_Fields extends WC_Gateway_Opayo_Pi {

		public function __construct() {

			parent::__construct();

		}

        function fields() {
            
            // Allow for token checkbox    
            $display_tokenization = $this->supports( 'tokenization' ) && is_checkout();

            // Set the pay button text
            if ( is_add_payment_method_page() ) {
                $pay_button_text = __( 'Add Card', 'woocommerce-gateway-sagepay-form' );
            } else {
                $pay_button_text = '';
            }

            // Checkout card fields
            echo '<div id="opayopi-payment-data">';

            if ( $this->opayo_description && strlen( $this->opayo_description ) != 0 ) {
                echo apply_filters( 'wc_opayopi_description', wp_kses_post( $this->opayo_description ) );
            }

            // Add tokenization script
            if ( $display_tokenization && class_exists( 'WC_Payment_Token_CC' ) ) {
                $this->saved_payment_methods();
            }
            
            // Use our own payment fields
            $this->sagepay_credit_card_form();

            if ( $display_tokenization && class_exists( 'WC_Payment_Token_CC' ) ) {
                $this->save_payment_method_checkbox();
            }

            echo '</div>';

        }

        /**
         * Credit Card Fields.
         *
         * Core credit card form which gateways can used if needed.
         */
        function sagepay_credit_card_form() {

            wp_enqueue_script( 'wc-credit-card-form' );

            $merchantSessionKeyArray  = WC()->session->get('merchantSessionKeyArray');

            $merchantSessionKey       = $merchantSessionKeyArray["merchantSessionKey"];
            $merchantSessionKeyExpiry = $merchantSessionKeyArray["expiry"];

            $fields = array(
                'card-number-field' => '<p class="form-row form-row-wide not-for-token">
                    <label for="' . $this->id . '-card-number">' . __( "Card Number", 'woocommerce-gateway-sagepay-form' ) . ' <span class="required">*</span></label>
                    <input id="' . $this->id . '-card-number" class="input-text wc-credit-card-form-card-number" type="tel" inputmode="numeric" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . $this->id . '-card-number" />
                </p>',
                'card-expiry-field' => '<p class="form-row form-row-first not-for-token">
                    <label for="' . $this->id . '-card-expiry">' . __( "Expiry (MM/YY)", 'woocommerce-gateway-sagepay-form' ) . ' <span class="required">*</span></label>
                    <input id="' . $this->id . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="tel" inputmode="numeric" autocomplete="off" placeholder="MM / YY" name="' . $this->id . '-card-expiry" />
                </p>',
                'card-cvc-field' => '<p id="sage-card-cvc" class="form-row form-row-last">
                    <label for="' . $this->id . '-card-cvc">' . __( "Card Code", 'woocommerce-gateway-sagepay-form' ) . ' <span class="required">*</span></label>
                    <input id="' . $this->id . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="tel" inputmode="numeric" autocomplete="off" placeholder="CVC" name="' . $this->id . '-card-cvc" />
                </p>',
                'card-merchantSessionKey-field' => '<div id="sage-card-merchantSessionKey">
                    <input id="' . $this->id . '-merchantSessionKey" type="hidden" autocomplete="off" name="' . $this->id . '-merchantSessionKey" value="' . $merchantSessionKey . '"/>
                </div>'
            );

            // Allow fields to be filtered if required
            $fields = apply_filters( 'woocommerce_opayopi_credit_card_form_fields', $fields, $this );

            ?>
            <fieldset id = "opayopi-cc-form" class="wc-payment-form">
<?php           
                do_action( 'woocommerce_credit_card_form_before', $this->id ); 
                foreach( $fields as $field ) {
                    echo $field;
                }
                do_action( 'woocommerce_credit_card_form_after', $this->id ); 
?>
                <div class="clear"></div>
            </fieldset>
<?php

        }

        /**
         * Use a custom save_payment_method_checkbox to include a description from the settings
         * @return [type] [description]
         */
        public function save_payment_method_checkbox() {
            
            echo sprintf(
                '<p class="form-row woocommerce-SavedPaymentMethods-saveNew">
                    <input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;" />
                    <label for="wc-%1$s-new-payment-method" style="display:inline;">%2$s</label><br />
                    %3$s
                </p>',
                esc_attr( $this->id ),
                esc_html__( 'Save to Account', 'woocommerce-gateway-sagepay-form' ),
                apply_filters( 'wc_opayopi_tokens_message', wp_kses_post( $this->tokens_message ) )
            );
        }   

        /**
         * [get_icon description] Add selected card icons to payment method label, defaults to Visa/MC/Amex/Discover
         * @return [type] [description]
         */
        public function get_icon() {
            return WC_Sagepay_Common_Functions::get_icon( $this->cardtypes, $this->sagelink, $this->sagelogo, $this->id );
        }


	} // End class
