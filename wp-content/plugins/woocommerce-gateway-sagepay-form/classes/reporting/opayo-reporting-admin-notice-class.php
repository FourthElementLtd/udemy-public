<?php
	/**
	 * Admin Notices for Opayo Reporting
	 */
	class WC_SagePay_Opayo_Reporting_Setup_Notice {
		
		public function __construct() {

			global $current_user;

			$current_user 	= wp_get_current_user();
			$user_id 		= $current_user->ID;

			if( current_user_can( 'manage_woocommerce' ) ) {

				$show_notice 	= get_user_meta( $user_id, 'setup-opayo-reporting-notice-dismissed', true );

				if( empty( $show_notice ) || $show_notice != '1' ) {

					$settings = get_option( 'woocommerce_opayo_reporting_options' );

					if( ( isset( $settings['test_opayo_reporting_username'] ) && strlen($settings['test_opayo_reporting_username']) != 0 ) || ( isset( $settings['live_opayo_reporting_username'] ) && strlen($settings['live_opayo_reporting_username']) != 0 ) ) {
						// No need to show the admin notice
					} else {
            			add_action('admin_notices', array($this, 'admin_notice') );

						// Dismiss the notice
						add_action( 'wp_ajax_dismiss_setup_opayo_reporting_notice', array( $this, 'dismiss_setup_opayo_reporting_notice' ) );

						// Enqueue the jQuery to dismiss the notice
						add_action( 'admin_enqueue_scripts', array( $this, 'setup_opayo_reporting_notice_enqueue_admin_script' ) );

					}

            	}
            }
			
		}
	
		/**
		 * Display a notice
		 */
		function admin_notice() {

			$reporting_heading 	 = __('Setup Opayo Reporting For Improved Security And Transaction Reporting', 'woocommerce-gateway-sagepay-form' );
			
			$reporting_body 	 = __('<p id="fa-link">Link your site to Opayo Reporting and get access to transaction confirmations, fraud scores and additional transaction checks.</p>', 'woocommerce-gateway-sagepay-form' );
			
			$reporting_body 	.= sprintf( __('<p id="fa-cogs">Go to the <a href="%s">Opayo Reporting setup page</a>.</p>', 'woocommerce-gateway-sagepay-form' ), admin_url( 'admin.php?page=wc-settings&tab=advanced&section=woocommerce_opayo_reporting_options', 'https' ) );
			
			$reporting_body 	.= sprintf( __('<p id="fa-guide">More information and a setup guide is available in the <a href="%s" target="_blank">Opayo Reporting Documentation</a>.</p>', 'woocommerce-gateway-sagepay-form' ), 'https://docs.woocommerce.com/document/opayo-reporting/' );
			
			// setup-opayo-reporting-notice
			echo '<div id="opayo_reporting_setup" class="notice notice-success setup-opayo-reporting-notice is-dismissible"><h2>' . $reporting_heading . '</h2>' . $reporting_body . '</div>';		
		
		}

		/**
		 * [dismiss_setup_opayo_reporting_notice description]
		 * @return [type] [description]
		 */
		function dismiss_setup_opayo_reporting_notice() {
			global $current_user;

			$current_user 	= wp_get_current_user();
			$user_id 		= $current_user->ID;

			update_user_meta( $user_id, 'setup-opayo-reporting-notice-dismissed', 1 );
	    }

	    /**
	     * [pdf_invoices_missing_logo_enqueue_admin_script description]
	     * @param  [type] $hook [description]
	     * @return [type]       [description]
	     */
	    function setup_opayo_reporting_notice_enqueue_admin_script( $hook ) {
		    wp_enqueue_script( 'setup_opayo_reporting_notice_enqueue_admin_script', SAGEPLUGINURL . '/assets/js/opayo-reporting-dismiss.js', array('jquery' ), OPAYOPLUGINVERSION, true );
		}
	} // End class
	
	$WC_SagePay_Opayo_Reporting_Setup_Notice = new WC_SagePay_Opayo_Reporting_Setup_Notice;