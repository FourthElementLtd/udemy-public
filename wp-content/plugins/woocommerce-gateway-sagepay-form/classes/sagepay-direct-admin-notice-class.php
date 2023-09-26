<?php
	/**
	 * Admin Notices for SagePay Form
	 */
	class WC_Gateway_Sagepay_Direct_Update_Notice {
		
		public function __construct() {
			
			/**
             * Add admin notice if SagePay Direct is enabled
             * Warn customers of changes to Credit Card Form
             */
            $sagepaydirect_cctype_nag_dismissed = get_option( 'sagepaydirect-cctype-nag-dismissed' );

			if( empty( $sagepaydirect_cctype_nag_dismissed ) || $sagepaydirect_cctype_nag_dismissed != '1' ) {
            	add_action('admin_notices', array($this, 'admin_notice') );
            }
			
		}
	
		/**
		 * Display a notice
		 */
		function admin_notice() {
		
			$notice  = '<h3 class="alignleft" style="line-height:150%; width:100%;">';
			$notice .= sprintf(__('IMPORTANT! SagePay Direct now uses a "Card Type" drop down on the checkout page. ', 'woocommerce_sagepayform') );	
			$notice .= '</h3>';
			$notice .= '<p>';
			$notice .= sprintf(__('Please make sure that you have selected all of the card types that you accept in the SagePay settings and that you have placed at least one test order to confirm you are happy with the layout of the credit card form on your checkout page. You can use CSS to modify the drop down if required. The <a href="%s" target="_blank">Docs</a> contain more information on this change', 'woocommerce_sagepayform'), 'https://docs.woothemes.com/document/sagepay-form/');	
			$notice .= '</p>';
			$notice .= '<p>';
			$notice .= sprintf(__('If you are not using SagePay Direct you can ignore this notice.', 'woocommerce_sagepayform') );	
			$notice .= '</p>';

			$output  = '<div class="notice notice-error sagepaydirect-cctype-nag is-dismissible">';
			$output .= $notice;
			$output .= '<br class="clear">';
			$output .= '</div>';

			echo $output;			
		
		}

	} // End class
	
	$WC_Gateway_Sagepay_Direct_Update_Notice = new WC_Gateway_Sagepay_Direct_Update_Notice;