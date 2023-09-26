<?php

    /**
     * WC_Gateway_Opayo_Reporting_Settings class.
     */
    class WC_Gateway_Opayo_Reporting_Settings extends WC_Gateway_Opayo_Reporting {  

    	/**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {

			// Settings on WooCommerce Advanced tab
			add_filter( 'woocommerce_get_sections_advanced', array( __CLASS__, 'opayo_reporting_heading' ) );
			add_filter( 'woocommerce_get_settings_advanced', array( __CLASS__, 'opayo_reporting_settings' ), 10, 2 );

        	add_action( 'woocommerce_settings_save_advanced', array( __CLASS__, 'opayo_reporting_save_options' ) );

        } // END __construct
		
	    /**
         * [opayo_reporting_heading description]
         * @param  [type] $sections [description]
         * @return [type]           [description]
         */
        public static function opayo_reporting_heading( $sections ) {
        	$current_user 		= wp_get_current_user();
	        $allowed_user_role 	= apply_filters( 'opayo_allowed_user_role_opayo_reporting_options', 'administrator', $sections );

			// Only admins can do this!
			if( in_array( $allowed_user_role, $current_user->roles ) ) {        	
        		$sections['woocommerce_opayo_reporting_options'] = __( 'Opayo Reporting Options', 'woocommerce-gateway-sagepay-form' );
        	}

        	return $sections;

        }

        /**
         * [opayo_reporting_settings description]
         * @param  [type] $settings        [description]
         * @param  [type] $current_section [description]
         * @return [type]                  [description]
         */
        public static function opayo_reporting_settings( $settings, $current_section ) {
        	$current_user 		= wp_get_current_user();
	        $allowed_user_role 	= apply_filters( 'opayo_allowed_user_role_opayo_reporting_options', 'administrator', $current_section );

        	if ( 'woocommerce_opayo_reporting_options' === $current_section && in_array( $allowed_user_role, $current_user->roles ) ) {

        		$reporting_settings = get_option( 'woocommerce_opayo_reporting_options' );

        		$settings = 
					array(
						array(
							'title' 		=> __( 'Opayo Reporting Options', 'woocommerce-gateway-sagepay-form' ),
							'type'  		=> 'title',
							'desc'  		=> 'It is recommended that you create a new MySagePay user for this section to avoid password issues.',
							'id'    		=> 'opayo_reporting_options',
						),
						array(
							'title'   		=> __( 'Testing Username', 'woocommerce-gateway-sagepay-form' ),
							'desc'    		=> __( 'Testing MySagePay Username', 'woocommerce-gateway-sagepay-form' ),
							'id'      		=> 'test_opayo_reporting_username',
							'type'    		=> 'text',
							'default' 		=> '',
							'placeholder' 	=> __( 'Test MySagePay Username', 'woocommerce-gateway-sagepay-form' ),
							'value' 		=> isset( $reporting_settings['test_opayo_reporting_username'] ) ? $reporting_settings['test_opayo_reporting_username'] : NULL
						),
						array(
							'title'   		=> __( 'Testing Password', 'woocommerce-gateway-sagepay-form' ),
							'desc'    		=> __( 'Testing MySagePay Password', 'woocommerce-gateway-sagepay-form' ),
							'id'      		=> 'test_opayo_reporting_password',
							'type'    		=> 'password',
							'default' 		=> '',
							'placeholder' 	=> __( 'Test MySagePay Password', 'woocommerce-gateway-sagepay-form' ),
							'value' 		=> isset( $reporting_settings['test_opayo_reporting_password'] ) ? WC_Gateway_Opayo_Reporting::decrypt_value( $reporting_settings['test_opayo_reporting_password'] ) : NULL
						),
						array(
							'title'   		=> __( 'Live Username', 'woocommerce-gateway-sagepay-form' ),
							'desc'    		=> __( 'Live MySagePay Username', 'woocommerce-gateway-sagepay-form' ),
							'id'      		=> 'live_opayo_reporting_username',
							'type'    		=> 'text',
							'default' 		=> '',
							'placeholder' 	=> __( 'Live MySagePay Username', 'woocommerce-gateway-sagepay-form' ),
							'value' 		=> isset( $reporting_settings['live_opayo_reporting_username'] ) ? $reporting_settings['live_opayo_reporting_username'] : NULL
						),
						array(
							'title'   		=> __( 'Live Password', 'woocommerce-gateway-sagepay-form' ),
							'desc'    		=> __( 'Live MySagePay Password', 'woocommerce-gateway-sagepay-form' ),
							'id'      		=> 'live_opayo_reporting_password',
							'type'    		=> 'password',
							'default' 		=> '',
							'placeholder' 	=> __( 'Live MySagePay Password', 'woocommerce-gateway-sagepay-form' ),
							'value' 		=> isset( $reporting_settings['live_opayo_reporting_password'] ) ? WC_Gateway_Opayo_Reporting::decrypt_value( $reporting_settings['live_opayo_reporting_password'] ) : NULL
						),
						array(
							'title'   		=> __( 'Update Order Status', 'woocommerce-gateway-sagepay-form' ),
							'desc'    		=> __( 'Update the order status in WooCommerce if the status changes in MySagePay', 'woocommerce-gateway-sagepay-form' ),
							'id'      		=> 'opayo_reporting_update_status',
							'type'          => 'select',
				    		'options'       => array('yes'=>'Yes','no'=>'No'),
							'default' 		=> 'no',
							'value' 		=> isset( $reporting_settings['opayo_reporting_update_status'] ) ? $reporting_settings['opayo_reporting_update_status'] : 'no'
						),
						array(
							'title'   		=> __( 'Vaild Order Statuses', 'woocommerce-gateway-sagepay-form' ),
							'desc'    		=> __( 'Only update the order status if the order has one of these statuses. Orders that have been cancelled or refunded in MySagePay will be updated regardless of this setting.', 'woocommerce-gateway-sagepay-form' ),
							'id'      		=> 'opayo_reporting_valid_order_statuses',
							'type'          => 'multiselect',
				    		'options'       => wc_get_order_statuses(),
							'value' 		=> isset( $reporting_settings['opayo_reporting_valid_order_statuses'] ) ? $reporting_settings['opayo_reporting_valid_order_statuses'] : NULL,
							'class' 		=> 'wc-enhanced-select'
						),
						array(
							'title'   		=> __( 'Automate Reporting?', 'woocommerce-gateway-sagepay-form' ),
							'desc'    		=> __( 'Use an automated process to get Opayo transaction report', 'woocommerce-gateway-sagepay-form' ),
							'id'      		=> 'opayo_reporting_action_scheduler',
							'type'          => 'select',
				    		'options'       => array('yes'=>'Yes','no'=>'No'),
							'default' 		=> 'no',
							'value' 		=> isset( $reporting_settings['opayo_reporting_action_scheduler'] ) ? $reporting_settings['opayo_reporting_action_scheduler'] : 'no'
						),
						array(
							'title'   		=> __( 'Automate Reporting Schedule', 'woocommerce-gateway-sagepay-form' ),
							'desc'    		=> __( 'If the Automate Reporting option is "YES" how often should the reports run?<br />Time is shown in seconds, default is hourly. Reduce this value for busier sites.', 'woocommerce-gateway-sagepay-form' ),
							'id'      		=> 'opayo_reporting_action_scheduler_time',
							'type'          => 'text',
							'default' 		=> 3600,
							'value' 		=> isset( $reporting_settings['opayo_reporting_action_scheduler_time'] ) ? $reporting_settings['opayo_reporting_action_scheduler_time'] : 3600
						),
						array(
							'title'   		=> __( 'Debugging', 'woocommerce-gateway-sagepay-form' ),
							'desc'    		=> __( 'Show full report from Opayo when editing order', 'woocommerce-gateway-sagepay-form' ),
							'id'      		=> 'opayo_reporting_debugging',
							'type'          => 'select',
				    		'options'       => array('yes'=>'Yes','no'=>'No'),
							'default' 		=> 'no',
							'value' 		=> isset( $reporting_settings['opayo_reporting_debugging'] ) ? $reporting_settings['opayo_reporting_debugging'] : 'no'
						),
						array(
							'type' 			=> 'sectionend',
							'id'   			=> 'opayo_reporting_options',
						),
						array(
							'title' 		=> '',
							'type'  		=> 'title',
							'desc'  		=> 'For more details on setting up the Opayo Reporting options please review the docs <a href="https://docs.woocommerce.com/document/opayo-reporting/" target="_blank">here</a>',
							'id'    		=> 'opayo_reporting_options',
						),
					);
			}

        	return $settings;

        }

        /**
         * [opayo_reporting_save_options description]
         * @return [type] [description]
         */
        public static function opayo_reporting_save_options() {

        	$section 			= isset( $_GET['section'] ) ? wc_clean( $_GET['section'] ) : NULL;
        	$current_user 		= wp_get_current_user();
	        $allowed_user_role 	= apply_filters( 'opayo_allowed_user_role_opayo_reporting_options', 'administrator', $section );

	        // Only admins can do this!
        	if ( $section === 'woocommerce_opayo_reporting_options' && in_array( $allowed_user_role, $current_user->roles ) ) {
        	
        		$test_opayo_reporting_password 	= isset( $_POST['test_opayo_reporting_password'] ) ? wc_clean( $_POST['test_opayo_reporting_password'] ) : NULL;
        		$live_opayo_reporting_password 	= isset( $_POST['live_opayo_reporting_password'] ) ? wc_clean( $_POST['live_opayo_reporting_password'] ) : NULL;

        		$opayo_reporting = array( 
	        			'test_opayo_reporting_username' 		=> isset( $_POST['test_opayo_reporting_username'] ) ? wc_clean( $_POST['test_opayo_reporting_username'] ) : NULL,
		        		'test_opayo_reporting_password' 		=> WC_Gateway_Opayo_Reporting::encrypt_value( $test_opayo_reporting_password ),
		        		'live_opayo_reporting_username' 		=> isset( $_POST['live_opayo_reporting_username'] ) ? wc_clean( $_POST['live_opayo_reporting_username'] ) : NULL,
		        		'live_opayo_reporting_password' 		=> WC_Gateway_Opayo_Reporting::encrypt_value( $live_opayo_reporting_password ),
		        		'opayo_reporting_update_status' 		=> isset( $_POST['opayo_reporting_update_status'] ) ? wc_clean( $_POST['opayo_reporting_update_status'] ) : NULL,
		        		'opayo_reporting_valid_order_statuses' 	=> isset( $_POST['opayo_reporting_valid_order_statuses'] ) ? wc_clean( $_POST['opayo_reporting_valid_order_statuses'] ) : NULL,
		        		'opayo_reporting_action_scheduler'		=> isset( $_POST['opayo_reporting_action_scheduler'] ) ? wc_clean( $_POST['opayo_reporting_action_scheduler'] ) : NULL,
		        		'opayo_reporting_action_scheduler_time'	=> isset( $_POST['opayo_reporting_action_scheduler_time'] ) ? wc_clean( $_POST['opayo_reporting_action_scheduler_time'] ) : NULL,
		        		'opayo_reporting_debugging' 			=> isset( $_POST['opayo_reporting_debugging'] ) ? wc_clean( $_POST['opayo_reporting_debugging'] ) : NULL,

        		);

        		update_option( 'woocommerce_opayo_reporting_options', $opayo_reporting );

        		// Update Action Scheduler, maybe
        		$schedule_time 	= isset( $opayo_reporting['opayo_reporting_action_scheduler_time'] ) ? $opayo_reporting['opayo_reporting_action_scheduler_time'] : 3600;

				// Delete the existing schedule
				WC()->queue()->cancel_all( 'woocommerce_opayo_reporting_get_transaction_report' );

				// Add new schedule if required
				if( isset( $opayo_reporting['opayo_reporting_action_scheduler'] ) && $opayo_reporting['opayo_reporting_action_scheduler'] == 'yes' ) {
					WC()->queue()->schedule_recurring( time()+$schedule_time, $schedule_time, 'woocommerce_opayo_reporting_get_transaction_report' );
				}


        	}

    	    // Remove options set by WooCommerce
    		delete_option( 'test_opayo_reporting_username' );
	        delete_option( 'test_opayo_reporting_password' );
	        delete_option( 'live_opayo_reporting_username' );
	        delete_option( 'live_opayo_reporting_password' );
	        delete_option( 'opayo_reporting_update_status' );
	        delete_option( 'opayo_reporting_valid_order_statuses' );
	        delete_option( 'opayo_reporting_action_scheduler' );
	        delete_option( 'opayo_reporting_action_scheduler_time' );
	        delete_option( 'opayo_reporting_debugging' );

        }

	} // End class
	
	$WC_Gateway_Opayo_Reporting_Settings = new WC_Gateway_Opayo_Reporting_Settings;
