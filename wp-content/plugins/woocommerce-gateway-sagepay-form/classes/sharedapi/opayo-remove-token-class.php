<?php
	/**
	 * Remove expired tokens via Action Scheduler
	 */
	class WC_Gateway_Opayo_Remove_Token {
		
		public function __construct() {

			// Automatic Token Removal Action scheduler
			add_action( 'admin_init' , array( __CLASS__,'opayo_scheduler_remove_token') );
			add_action( 'woocommerce_opayo_scheduler_remove_token', array( __CLASS__, 'action_scheduler_opayo_scheduler_remove_token' ), 10, 2 );

		}

		/**
		 * [opayo_scheduler_remove_token description]
		 * @return NULL
		 */
		public static function opayo_scheduler_remove_token() {

			$remove_token  = WC_Gateway_Opayo_Remove_Token::get_remove_token_action_scheduler();

			// Update the order status if necessary 
			if( $remove_token == 'yes' ) {
				
				$next = WC()->queue()->get_next( 'woocommerce_opayo_scheduler_remove_token' );

				if ( ! $next ) {

					$date 			= new DateTime('now');
					$nowTimestamp 	= $date->getTimestamp();
					$date->modify('first day of next month');

					$firstDayOfNextMonthTimestamp = $date->getTimestamp();

					WC()->queue()->cancel_all( 'woocommerce_opayo_scheduler_remove_token' );
					WC()->queue()->schedule_single( $firstDayOfNextMonthTimestamp, 'woocommerce_opayo_scheduler_remove_token' );
				}
			} else {
				WC()->queue()->cancel_all( 'woocommerce_opayo_scheduler_remove_token' );
			}
			 
		}

		/**
		 * [action_scheduler_opayo_scheduler_remove_token description]
		 * @param  [type] $args  [description]
		 * @param  string $group [description]
		 * @return [type]        [description]
		 */
		public static function action_scheduler_opayo_scheduler_remove_token( $args = NULL, $group = '' ) {
            global $wpdb;

            // Set the expired tokens array
            $expired_tokens = array();

            // Get the Opayo Direct tokens
            $tokens = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokenmeta AS tm
					INNER JOIN wp_woocommerce_payment_tokens AS t
					ON tm.payment_token_id = t.token_id
					WHERE 
					( 
						t.gateway_id = %s
						AND tm.meta_key = 'expiry_year'
						AND tm.meta_value < %s
					)", 'sagepaydirect', '2026'
				), ARRAY_A
			);

			// wp_mail( 'andrew@chromeorange.co.uk', 'action_scheduler_opayo_scheduler_remove_token 01 ' .time(), '<pre>' . print_r( $tokens, TRUE ) . '</pre>' );

			foreach ( $tokens as $token ) {

				$t = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokenmeta WHERE payment_token_id = %s",
						$token['token_id'] 
					), ARRAY_A
				);

				wp_mail( 'andrew@chromeorange.co.uk', 'action_scheduler_opayo_scheduler_remove_token 02 ' .time(), '<pre>' . print_r( $t, TRUE ) . '</pre>' );


			}
            

            // Check for expired tokens

            // Loop through expired tokens

            // Send to shared API class to remove tokens from Opayo

            // Remove token from WooCommerce maybe

            // Log failed removals

		}

		public static function get_remove_token_action_scheduler() {
			// Get settings
        	$settings = get_option( 'woocommerce_sagepaydirect_settings' );
			return isset( $settings['removeTokenActionScheduler'] ) ? $settings['removeTokenActionScheduler'] : 'no';
		}

		public static function get_remove_token_action_scheduler_time() {
			// Get settings
        	$settings = get_option( 'woocommerce_sagepaydirect_settings' );
			return isset( $settings['removeTokenActionSchedulerTime'] ) ? $settings['removeTokenActionSchedulerTime'] : month;
		}

	} // End class

	// Load the class
    $GLOBALS['WC_Gateway_Opayo_Remove_Token'] = new WC_Gateway_Opayo_Remove_Token();