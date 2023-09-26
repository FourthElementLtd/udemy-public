<?php

    /**
     * WC_Sagepay_Activation_Class class.
     *
     * Things that need to happen on activation.
     */
    class WC_Sagepay_Activation_Class {

        /**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {


        }

        public static function activate() {

            // Get settings
            $settings = get_option( 'woocommerce_sagepaydirect_settings' );

            // Is Direct Active?
            if( isset( $settings['enabled'] ) && $settings['enabled'] == "yes" && isset( $settings['status'] ) && $settings['status'] == 'live' ) {
                
                // Reset Protocol to 3.00.
                $woocommerce_sagepaydirect_version = get_option( 'woocommerce_sagepaydirect_version' );

                if ( !$woocommerce_sagepaydirect_version || version_compare( $woocommerce_sagepaydirect_version, '4.7.0', '>=' ) ) {
                    $settings['vpsprotocol'] = '3.00';
                    update_option( 'woocommerce_sagepaydirect_settings', $settings );
                }

            }

            update_option( 'woocommerce_sagepaydirect_version', OPAYOPLUGINVERSION );
                        
        }

        public static function deactivate() {
            // empty
        }

    }