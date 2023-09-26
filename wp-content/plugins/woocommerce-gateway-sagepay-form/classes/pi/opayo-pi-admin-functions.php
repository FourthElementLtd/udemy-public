<?php

    /**
     * WC_Gateway_Opayo_Pi_Admin class.
     *
     * void abort release
     */
    class WC_Gateway_Opayo_Pi_Admin {
        

        public function __construct() {
            // Get settings
            $settings = get_option( 'woocommerce_opayopi_settings' );
            if( $settings['enabled'] == 'yes' ) {
                add_action( 'admin_init', array( $this, 'admin_init' ) );
            }
        }

        public function admin_init() {
            // Order Bulk Actions
            add_filter( 'bulk_actions-edit-shop_order', array( $this, 'bulk_edit_opayo_pi_release_payments' ) );
            add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_pi_release' ), 10, 3 );
        }

        /**
         * [bulk_edit_sagepay_pi_release_payments description]
         * @param  [type] $actions [description]
         * @return [type]          [description]
         */
        public function bulk_edit_opayo_pi_release_payments( $actions ) {

            if ( isset( $actions['edit'] ) ) {
                unset( $actions['edit'] );
            }

            $actions['opayo_pi_release'] = __( 'Release Opayo Payments', 'woocommerce-gateway-sagepay-form' );

            return $actions;

        }

        /**
         * [handle_pi_release description]
         * @param  [type] $redirect_to [description]
         * @param  [type] $action      [description]
         * @param  [type] $ids         [description]
         * @return [type]              [description]
         */
        public function handle_pi_release( $redirect_to, $action, $ids ) {

            // Bail out if this is not the opayo_pi_release.
            if ( $action === 'opayo_pi_release' ) {

                include_once( 'opayo-pi-instructions-class.php' );

                $ids = array_map( 'absint', $ids );

                // Sort Order IDs lowest to highest
                sort( $ids );

                foreach ( $ids as $id ) {
                    $order         = wc_get_order( $id );
                    if( $order->get_status() === 'authorised' ) {
                        $instruction   = new WC_Gateway_Opayo_Pi_Instructions( $order, 'release' );
                    }
                }

            }

            return esc_url_raw( $redirect_to );

        }

	} 

    $WC_Gateway_Opayo_Pi_Admin = new WC_Gateway_Opayo_Pi_Admin();
