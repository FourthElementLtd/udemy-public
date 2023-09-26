<?php

    /**
     * WC_Opayo_Die class.
     *
     */
    class WC_Opayo_Die {

        public function __construct() {

            add_filter( 'wp_die_handler', array( $this, 'opayo_die_handler' ) );

        }

        function opayo_die_handler( $die_handler ) {
            return 'opayo_die_output';
        }

    }