<?php

    /**
     * WC_Gateway_Opayo_Pi_Instructions class.
     *
     * @extends WC_Gateway_Opayo_Pi
     *
     * void abort release
     */
    class WC_Gateway_Opayo_Pi_Instructions extends WC_Gateway_Opayo_Pi {

        private $order;
        private $instruction;
        

        public function __construct( $order, $instruction ) {

            parent::__construct();

            $this->order        = $order;
            $this->instruction  = $instruction;
            $this->settings     = get_option( 'woocommerce_opayopi_settings' );

            $this->transaction_id   = NULL;

        }
    
        /**
         * [instruction description]
         * @return [type] [description]
         */
        function instruction() {

            $order    = $this->order;
            $order_id = $order->get_id();

            // New API Request for instruction
            $data    = array(
                "instructionType" => $this->instruction,
            );

            if( $this->instruction === 'release' ) {
                $data['amount'] = $order->get_total() * 100;
            }

            $this->transaction_id   = get_post_meta( $order_id, '_transaction_id', TRUE );
            $this->instructions_url = str_replace( '<transactionId>', $this->transaction_id, $this->instructions_url );

            $result = $this->remote_post( $data, $this->instructions_url, NULL, 'Basic' );

            if( isset( $result['description'] ) && isset( $result['code'] ) ) {
                $order->add_order_note( $result['description'] );
            }

            // Release
            if( isset( $result['instructionType'] ) && $result['instructionType'] === 'release' ) {
                $order->update_status( 'processing',  _x( 'Payment released<br />', 'woocommerce-gateway-sagepay-form' ) . $result['date'] . '<br />' );
            }

            // Void
            if( isset( $result['instructionType'] ) && $result['instructionType'] === 'void' ) {
                $order->update_status( 'pending',  _x( 'Payment voided<br />', 'woocommerce-gateway-sagepay-form' ) . $result['date'] . '<br />' );
            }

            // Abort
            if( isset( $result['instructionType'] ) && $result['instructionType'] === 'abort' ) {
                $order->update_status( 'pending',  _x( 'Payment aborted<br />', 'woocommerce-gateway-sagepay-form' ) . $result['date'] . '<br />' );
            }

            // Do nothing if nothing else is returned. Order status remains as it was.

        }

        /**
         * [array_flatten description]
         * @param  [type] $array [description]
         * @return [type]        [description]
         */
        function array_flatten( $array ) { 

            if ( !is_array($array) ) { 
                return FALSE; 
            }

            $result = array(); 

            foreach ( $array as $key => $value ) {

                if ( is_array($value) ) { 
                    $result = array_merge( $result, $this->array_flatten($value) ); 
                } else { 
                    $result[$key] = $value; 
                }

            }

            return $result; 

        }

	} // END CLASS
