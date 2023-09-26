<?php
    /**
     * Opayo_Pi_Dropin_Payment_Fields
     */
    class Opayo_Pi_Dropin_Payment_Fields extends WC_Gateway_Opayo_Pi {

        public function __construct() {

            parent::__construct();

        }

        function fields() {

            // Load the Card Form script from WooCommerce
            wp_enqueue_script( 'wc-credit-card-form' );
            
            // Set the pay button text
            if ( is_add_payment_method_page() ) {
                $pay_button_text = __( 'Add Card', 'woocommerce-gateway-sagepay-form' );
            } else {
                $pay_button_text = '';
            }

            // Checkout card fields
            echo '<div id="opayopi-payment-data">';

            if ( $this->description ) {
                echo apply_filters( 'wc_opayopi_description', wp_kses_post( $this->description ) );
            }
/*
            // Add tokenization script
            if ( $display_tokenization && class_exists( 'WC_Payment_Token_CC' ) ) {
                // Add script to remove card fields if CVV required with tokens
                if( $this->cvv_script ) {
                    $this->cvv_script();
                } else {
                    $this->tokenization_script();
                }
                
                $this->saved_payment_methods();
            }
*/            
            // Use Opayo DropIn payment fields
            $this->sagepay_credit_card_form();

            echo '</div>';

        }

        /**
         * Dropin Card Form
         */
        function sagepay_credit_card_form() {
?>
            <fieldset id="opayopi-cc-form" class="wc-payment-form">
                <div id="woocommerce-sp-container"></div>
<?php           
                do_action( 'woocommerce_credit_card_form_before', $this->id );

                do_action( 'woocommerce_credit_card_form_after', $this->id ); 
?>
                <div class="clear"></div>
            </fieldset>
<?php
        }      

        /**
         * [get_icon description] Add selected card icons to payment method label, defaults to Visa/MC/Amex/Discover
         * @return [type] [description]
         */
        public function get_icon() {
            return WC_Sagepay_Common_Functions::get_icon( $this->cardtypes, $this->sagelink, $this->sagelogo, $this->id );
        }


    } // End class
