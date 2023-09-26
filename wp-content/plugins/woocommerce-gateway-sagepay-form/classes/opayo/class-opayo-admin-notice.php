<?php
	/**
	 * Admin Notices for SagePay Form
	 */
	class WC_SagePay_Opayo_Rebrand_Notice {
		
		public function __construct() {
			
			/**
             * Add admin notice
             */

    		$opayo_rebrand_nag_dismissed = get_option( 'opayo-rebrand-nag-dismissed' );

			if( empty( $opayo_rebrand_nag_dismissed ) || $opayo_rebrand_nag_dismissed != '1' ) {
            	add_action('admin_notices', array($this, 'admin_notice') );
            }
			
		}
	
		/**
		 * Display a notice
		 */
		function admin_notice() {
		
			$notice  = '<h3 class="alignleft" style="line-height:150%; width:100%;">';
			$notice .= sprintf(__('SagePay is rebranding to Opayo!', 'woocommerce-gateway-sagepay-form') );	
			$notice .= '</h3>';
			$notice .= '<p>';
			$notice .= sprintf(__('You can read more about this upcoming change <a href="%s" target="blank">here</a>.', 'woocommerce-gateway-sagepay-form'), 'https://www.sagepay.co.uk/about-us' );	
			$notice .= '</p>';
			$notice .= '<p>';
			$notice .= sprintf(__('Any changes in the plugin will be released as they are required. The plugin name and references to SagePay will be removed in future releases of the plugin', 'woocommerce-gateway-sagepay-form') );	
			$notice .= '</p>';
			

			$output  = '<div class="notice notice-error opayo-rebrand-nag is-dismissible">';
			$output .= $notice;
			$output .= '<br class="clear">';
			$output .= '</div>';

			echo $output;			
		
		}

	} // End class
	
	$WC_SagePay_Opayo_Rebrand_Notice = new WC_SagePay_Opayo_Rebrand_Notice;