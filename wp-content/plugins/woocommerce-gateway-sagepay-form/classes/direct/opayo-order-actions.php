<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

    /**
     * Opayo Order Actions Class
     */
    class WC_Opayo_Order_Actions {

        public function __construct() {

            add_filter( 'bulk_actions-edit-shop_order', array( $this, 'bulk_edit_authorised_status' ) );
            add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_update_status_authorised' ), 10, 3 );
            add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_capture_authorised_payment' ), 10, 3 );

        }

        public function bulk_edit_authorised_status( $actions ) {

            if ( isset( $actions['edit'] ) ) {
                unset( $actions['edit'] );
            }

            $actions['opayo_update_status_authorised'] = __( 'Mark Authorised', 'woocommerce-gateway-sagepay-form' );
            $actions['opayo_capture_authorised']       = __( 'Capture Authorised Payment', 'woocommerce-gateway-sagepay-form' );

            return $actions;

        }

        public function handle_update_status_authorised( $redirect_to, $action, $ids ) {

            // Bail out if this is not the handle_update_status_authorised.
            if ( $action === 'opayo_update_status_authorised' && $ids != NULL ) {

                $ids = array_map( 'absint', $ids );

                foreach ( $ids as $id ) {
                    $order   = new WC_Order( $id );
                    $order->update_status( 'authorised' );
                }

            }

        }

        public function handle_capture_authorised_payment( $redirect_to, $action, $ids ) {

            // Bail out if this is not the handle_update_status_authorised.
            if ( $action === 'opayo_capture_authorised' && $ids != NULL ) {

                $ids = array_map( 'absint', $ids );

                foreach ( $ids as $id ) {

                    $order   = new WC_Order( $id );

                    include_once( 'sagepay-direct-release-class.php' );

                    $response = new Sagepay_Direct_Release( $order );

                    return $response->release();

                }

            }

        }

    }