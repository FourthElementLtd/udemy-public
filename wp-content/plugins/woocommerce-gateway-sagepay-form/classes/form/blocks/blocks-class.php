<?php
/**
 * Opayo Form payment gateway implementation for Gutenberg Blocks
 */

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

class Wc_OpayoForm_Blocks extends AbstractPaymentMethodType {
    
    private $localized = 0;
	protected $name    = 'sagepayform';

	public function initialize() {
        $this->settings = get_option( 'woocommerce_sagepayform_settings' );
        $this->icon = apply_filters( 'wc_sagepayform_icon', '' );
	}

    // Register this payment method
    public static function register() {
        add_action( 'woocommerce_blocks_payment_method_type_registration', 
                    function ( $registry ) {
                        $registry->register( new static() );
        });
    }

	public function is_active() {
        return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}

	public function get_payment_method_script_handles() {

        $path           = SAGEPLUGINURL . '/classes/form/blocks/js/wc-payment-method-sagepayform.js';
        $handle         = 'wc-payment-method-sagepayform';
        $dependencies   = array( 'wp-hooks' );

        wp_register_script( $handle, $path, $dependencies, OPAYOPLUGINVERSION, TRUE );
       
        if (!$this->localized) {

            $strings = array( 
                    'Pay via Opayo Form'    => __('Pay via Opayo Form', 'woocommerce-gateway-sagepay-form'),
                    'Opayo Form'            => __('Opayo Form', 'woocommerce-gateway-sagepay-form') 
                );

            wp_localize_script('wc-payment-method-sagepayform', 'OpayoFormLocale', $strings);
            $this->localized = 1;

        }

		return array( 'wc-payment-method-sagepayform' );
	}

	public function get_payment_method_data() {

        $args = array(
            'title'           => $this->get_title(),
            'description'     => $this->get_description(),
            'iconsrc'         => $this->get_icons(),
            'supports'        => $this->get_supports(),
            'poweredbywp'     => $this->get_poweredby(),
            'testmode'        => $this->get_testmode(),
            'testcard'        => $this->get_testcard(),
        );

        return $args;

	}

    private function get_title() {
        return isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Pay with Opayo Form', 'woocommerce-gateway-sagepay-form' );
    }

    private function get_description() {

        $description = isset( $this->settings['description'] ) ? $this->settings['description'] : __( 'Pay with Opayo Form', 'woocommerce-gateway-sagepay-form' );

        return $description;
    }

    private function get_icons() {

        $icons_src  = array();

        if ( $this->icon ) {

            $icons_src[ strtolower($this->settings['title']) ] = array(
                'src' => esc_url( $this->icon ),
                'alt' => $this->settings['title']
            );

        } elseif ( ! empty( $this->settings['cardtypes'] ) ) {
            foreach ( $this->settings['cardtypes'] as $card_type ) {

                $icons_src[esc_attr( strtolower( str_replace( ' ','-',$card_type ) ) )] = array(
                    'src' => esc_url( SAGEPLUGINURL . 'images/card-' . strtolower( str_replace(' ','-',$card_type) ) . '.png' ),
                    'alt' => esc_attr( ucwords( $card_type ) )
                );

            }
        }

        return $icons_src;
    }

    private function get_supports() {

        // Supports
        $this->supports = array(
                                'products'
                        );

        $woocommerce_opayo_reporting_options   = get_option( 'woocommerce_opayo_reporting_options' );
        if( isset( $woocommerce_opayo_reporting_options['live_opayo_reporting_username'] ) || isset( $woocommerce_opayo_reporting_options['test_opayo_reporting_username'] ) ){
           $this->supports[] = 'refunds';
        }

    }

    private function get_poweredby() {
        return esc_url( SAGEPLUGINURL . 'assets/images/cards.png' );
    }

    private function get_testmode() {

        $return = NULL;
        if ( $this->settings['status'] == 'testing' ) {
            $return = __( 'TEST MODE ENABLED.', 'woocommerce-gateway-sagepay-form' );
            $return = trim( $return );
        }

        return $return;
    }

    private function get_testcard() {

        $return = NULL;
        if ( $this->settings['status'] == 'testing' ) {
            $return = __( 'In test mode, you can use Visa card number 4111111111111111 with any CVC and a valid expiration date.', 'woocommerce-gateway-sagepay-form' );
            $return = trim( $return );
        }

        return $return;
    }

}