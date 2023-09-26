<?php

    /**
     * WC_Opayo_Common_Order_Notes class.
     *
     * Order Notes Functions Common to all Opayo Gateways.
     */
    class WC_Opayo_Common_Order_Notes {

        /**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {


        }

        public static function add_order_note( $order_id, $message, $note, $payment_method ) {

            // woocommerce order object
            $order    = wc_get_order( $order_id );

            $ordernote = '';

            if( is_array($note) ) {
                
                foreach ( $note as $key => $value ) {
                    $ordernote .= $key . ' : ' . $value . "\r\n";
                }

            } else {
                $ordernote = $note;
            }

            // Combine $message and $ordernote
            $order_note = $message . '<br />' . $ordernote;

            // Add $payment_method to order note
            $order_note = $order_note . '<br />' . sprintf( __( 'Via %s', 'woocommerce-gateway-sagepay-form' ), $payment_method );

            // Add plugin version to order notes
            $order_note = $order_note . '<br />' . sprintf( __( 'Plugin version: %s', 'woocommerce-gateway-sagepay-form' ), OPAYOPLUGINVERSION );

            $order->add_order_note( $order_note );

        }

        public static function payment_complete( $order, $transaction_id = '', $payment_method = 'Opayo' ) {

            if ( ! $order->get_id() ) { // Order must exist.
                return false;
            }

            try {
                do_action( 'woocommerce_pre_payment_complete', $order->get_id() );

                if ( WC()->session ) {
                    WC()->session->set( 'order_awaiting_payment', false );
                }

                if ( $order->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_payment_complete', array( 'on-hold', 'pending', 'failed', 'cancelled' ), $order ) ) ) {
                    if ( ! empty( $transaction_id ) ) {
                        $order->set_transaction_id( $transaction_id );
                    }
                    if ( ! $order->get_date_paid( 'edit' ) ) {
                        $order->set_date_paid( time() );
                    }

                    // Add $payment_method to order note
                    $order_note = sprintf( __( 'Via %s', 'woocommerce-gateway-sagepay-form' ), $payment_method );

                    // Add plugin version to order notes
                    $order_note = $order_note . '<br />' . sprintf( __( 'Plugin version: %s', 'woocommerce-gateway-sagepay-form' ), OPAYOPLUGINVERSION );

                    $order_note = $order_note . '<br />';

                    WC_Opayo_Common_Order_Notes::set_status( 
                        $order,
                        apply_filters( 'woocommerce_payment_complete_order_status', $order->needs_processing() ? 'processing' : 'completed', $order->get_id(), $order ), 
                        $order_note );

                    $order->save();

                    do_action( 'woocommerce_payment_complete', $order->get_id() );
                } else {
                    do_action( 'woocommerce_payment_complete_order_status_' . $order->get_status(), $order->get_id() );
                }
            } catch ( Exception $e ) {
                /**
                 * If there was an error completing the payment, log to a file and add an order note so the admin can take action.
                 */
                $logger = wc_get_logger();
                $logger->error(
                    sprintf(
                        'Error completing payment for order #%d',
                        $order->get_id()
                    ),
                    array(
                        'order' => $order,
                        'error' => $e,
                    )
                );
                WC_Opayo_Common_Order_Notes::add_order_note( $order->get_id(), __( 'Payment complete event failed.', 'woocommerce' ) . ' ' . $e->getMessage() );
                return false;
            }
            return true;

        }

        public static function parent_set_status( $order, $new_status ) {
            $old_status = $order->get_status();
            $new_status = 'wc-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;

            // If setting the status, ensure it's set to a valid status.
            if ( true === $order->object_read ) {
                // Only allow valid new status.
                if ( ! in_array( 'wc-' . $new_status, $order->get_valid_statuses(), true ) && 'trash' !== $new_status ) {
                    $new_status = 'pending';
                }

                // If the old status is set but unknown (e.g. draft) assume its pending for action usage.
                if ( $old_status && ! in_array( 'wc-' . $old_status, $order->get_valid_statuses(), true ) && 'trash' !== $old_status ) {
                    $old_status = 'pending';
                }
            }

            $order->set_prop( 'status', $new_status );

            return array(
                'from' => $old_status,
                'to'   => $new_status,
            );
        }

        public static function set_status( $order, $new_status, $note = '', $manual_update = false ) {
            $result = WC_Opayo_Common_Order_Notes::parent_set_status( $order, $new_status );

            if ( true === $order->object_read && ! empty( $result['from'] ) && $result['from'] !== $result['to'] ) {
                $order->status_transition = array(
                    'from'   => ! empty( $order->status_transition['from'] ) ? $order->status_transition['from'] : $result['from'],
                    'to'     => $result['to'],
                    'note'   => $note,
                    'manual' => (bool) $manual_update,
                );

                if ( $manual_update ) {
                    do_action( 'woocommerce_order_edit_status', $order->get_id(), $result['to'] );
                }

                $order->maybe_set_date_paid();
                $order->maybe_set_date_completed();
            }

            return $result;
        }

        public static function update_status( $order, $new_status, $note = '', $manual = false, $payment_method = 'Opayo' ) {
            if ( ! $order->get_id() ) { // Order must exist.
                return false;
            }

            // Add $payment_method to order note
            $order_note = sprintf( __( 'Via %s', 'woocommerce-gateway-sagepay-form' ), $payment_method );

            // Add plugin version to order notes
            $order_note = $order_note . '<br />' . sprintf( __( 'Plugin version: %s', 'woocommerce-gateway-sagepay-form' ), OPAYOPLUGINVERSION );

            $note = $note . $order_note;

            try {
                $order->set_status( $new_status, $note, $manual );
                $order->save();
            } catch ( Exception $e ) {
                $logger = wc_get_logger();
                $logger->error(
                    sprintf(
                        'Error updating status for order #%d',
                        $order->get_id()
                    ),
                    array(
                        'order' => $order,
                        'error' => $e,
                    )
                );
                // $order->add_order_note( __( 'Update status event failed.', 'woocommerce' ) . ' ' . $e->getMessage() );
                WC_Opayo_Common_Order_Notes::add_order_note( $order->get_id(), __( 'Update status event failed.', 'woocommerce' ) . ' ' . $e->getMessage() );
                return false;
            }
            return true;
        }

        public static function mark_order_as_pre_ordered( $order ) {

            // Add $payment_method to order note
            $order_note = sprintf( __( 'Via %s', 'woocommerce-gateway-sagepay-form' ), $payment_method );

            // Add plugin version to order notes
            $order_note = $order_note . '<br />' . sprintf( __( 'Plugin version: %s', 'woocommerce-gateway-sagepay-form' ), OPAYOPLUGINVERSION );


            WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );

        }

        public static  function status_transition( $order, $payment_method ) {
            $status_transition = $order->status_transition;

            // Reset status transition variable.
            $order->status_transition = false;

            if ( $status_transition ) {
                try {
                    do_action( 'woocommerce_order_status_' . $status_transition['to'], $order->get_id(), $order );

                    if ( ! empty( $status_transition['from'] ) ) {
                        /* translators: 1: old order status 2: new order status */
                        $transition_note = sprintf( __( 'Order status changed from %1$s to %2$s.', 'woocommerce' ), wc_get_order_status_name( $status_transition['from'] ), wc_get_order_status_name( $status_transition['to'] ) );

                        // Add $payment_method to order note
                        $transition_note = $transition_note . '<br />' . sprintf( __( 'Via %s', 'woocommerce-gateway-sagepay-form' ), $payment_method );

                        // Add plugin version to order notes
                        $transition_note = $transition_note . '<br />' . sprintf( __( 'Plugin version: %s', 'woocommerce-gateway-sagepay-form' ), OPAYOPLUGINVERSION );

                        // Note the transition occurred.
                        // $order->add_status_transition_note( $transition_note, $status_transition );
                        WC_Opayo_Common_Order_Notes::add_order_note( $order->get_id(), $transition_note );


                        do_action( 'woocommerce_order_status_' . $status_transition['from'] . '_to_' . $status_transition['to'], $this->get_id(), $this );
                        do_action( 'woocommerce_order_status_changed', $order->get_id(), $status_transition['from'], $status_transition['to'], $this );

                        // Work out if this was for a payment, and trigger a payment_status hook instead.
                        if (
                            in_array( $status_transition['from'], apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( 'pending', 'failed' ), $order ), true )
                            && in_array( $status_transition['to'], wc_get_is_paid_statuses(), true )
                        ) {
                            /**
                             * Fires when the order progresses from a pending payment status to a paid one.
                             *
                             * @since 3.9.0
                             * @param int Order ID
                             * @param WC_Order Order object
                             */
                            do_action( 'woocommerce_order_payment_status_changed', $order->get_id(), $order );
                        }
                    } else {
                        /* translators: %s: new order status */
                        $transition_note = sprintf( __( 'Order status set to %s.', 'woocommerce' ), wc_get_order_status_name( $status_transition['to'] ) );

                        // Add $payment_method to order note
                        $transition_note = $transition_note . '<br />' . sprintf( __( 'Via %s', 'woocommerce-gateway-sagepay-form' ), $payment_method );

                        // Add plugin version to order notes
                        $transition_note = $transition_note . '<br />' . sprintf( __( 'Plugin version: %s', 'woocommerce-gateway-sagepay-form' ), OPAYOPLUGINVERSION );

                        // Note the transition occurred.
                        // $order->add_status_transition_note( $transition_note, $status_transition );
                        WC_Opayo_Common_Order_Notes::add_order_note( $order->get_id(), $transition_note );

                    }
                } catch ( Exception $e ) {
                    $logger = wc_get_logger();
                    $logger->error(
                        sprintf(
                            'Status transition of order #%d errored!',
                            $order->get_id()
                        ),
                        array(
                            'order' => $order,
                            'error' => $e,
                        )
                    );
                    WC_Opayo_Common_Order_Notes::add_order_note( $order->get_id(), __( 'Error during status transition.', 'woocommerce' ) . ' ' . $e->getMessage() );
                }
            }
        }
    }

    $WC_Opayo_Common_Order_Notes = new WC_Opayo_Common_Order_Notes();