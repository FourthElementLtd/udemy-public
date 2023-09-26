<?php
	/**
	 * Admin Notices for SagePay Form
	 */
	class WC_Gateway_Sagepay_Direct_Update_Notice {
		
		public function __construct() {

			// Get SagePay Direct settings
        	$settings = get_option( 'woocommerce_sagepaydirect_settings' );
			
			/**
             * Add admin notice if SagePay Direct is enabled
             * Warn customers of changes to cookies for 3D Secure
             */

        	if( isset( $settings['enabled'] ) && $settings['enabled'] == "yes" && isset( $settings['threeDSMethod'] ) && $settings['threeDSMethod'] != "1" ) {

        		$sagepaydirect_cookie_nag_dismissed = get_option( 'sagepaydirect-cookie-nag-dismissed' );

				if( empty( $sagepaydirect_cookie_nag_dismissed ) || $sagepaydirect_cookie_nag_dismissed != '1' ) {
	            	add_action('admin_notices', array($this, 'iframe_admin_notice') );
	            }

	        }

			/**
             * Add admin notice if SagePay Direct is enabled
             * Warn admins of 3D Secure 2.0 and Protocol 4.00
             */

        	if( isset( $settings['enabled'] ) && $settings['enabled'] == "yes" && $settings['vpsprotocol'] == '4.00' ) {

        		$sagepaydirect_protocol4_nag_dismissed = get_option( 'sagepaydirect-protocol4-nag-dismissed' );

				if( empty( $sagepaydirect_protocol4_nag_dismissed ) || $sagepaydirect_protocol4_nag_dismissed != '1' ) {
	            	add_action('admin_notices', array($this, 'protocol4_admin_notice') );
	            }

	        } elseif( isset( $settings['enabled'] ) && $settings['enabled'] == "yes" && $settings['vpsprotocol'] != '4.00' ) { 

        		$sagepaydirect_threeds2_nag_dismissed = get_option( 'sagepaydirect-threeds2-nag-dismissed' );

				if( empty( $sagepaydirect_threeds2_nag_dismissed ) || $sagepaydirect_threeds2_nag_dismissed != '1' ) {
	            	add_action('admin_notices', array($this, 'threeds2_admin_notice') );
	            }

	        }

		}
	
		/**
		 * Display a notice
		 */
		function iframe_admin_notice() {
		
			$notice  = '<h3 class="alignleft" style="line-height:150%; width:100%;">';
			$notice .= sprintf(__('IMPORTANT! Due to upcoming cookie changes the 3D Secure method will no longer use iFrames.', 'woocommerce-gateway-sagepay-form') );	
			$notice .= '</h3>';
			$notice .= '<p>';
			$notice .= sprintf(__('During the 3D Secure process third party cookies are used. Browsers like Chrome have introduced changes that will reject these cookies which can cause 3D Secure to fail if the site uses the iFrame method.', 'woocommerce-gateway-sagepay-form') );	
			$notice .= '</p>';
			$notice .= '<p>';
			$notice .= sprintf(__('The <a href="%s" target="_blank">Docs</a> contain more information on this change', 'woocommerce-gateway-sagepay-form'), 'https://docs.woocommerce.com/document/sagepay-form/#section-21');	
			$notice .= '</p>';
			

			$output  = '<div class="notice notice-error sagepaydirect-cookie-nag is-dismissible">';
			$output .= $notice;
			$output .= '<br class="clear">';
			$output .= '</div>';

			echo $output;			
		
		}

		/**
		 * Display a notice
		 */
		function protocol4_admin_notice() {
		
			$notice  = '<h3 class="alignleft" style="line-height:150%; width:100%;">';
			$notice .= sprintf(__('Opayo Protocol 4.00 is now Live.', 'woocommerce-gateway-sagepay-form') );	
			$notice .= '</h3>';
			$notice .= '<p><strong>';
			$notice .= sprintf(__('Protocol 4.00 has been enabled on the Opayo Live servers, this update means that 3D Secure 2.0 is now live. Your site is set to use Protocol 4.00 and 3D Secure 2.0.', 'woocommerce-gateway-sagepay-form') );	
			$notice .= '</p></strong>';
			$notice .= '<p>';
			$notice .= sprintf(__('You should make sure that you have 3D Secure turned on in <a href="%s" target="_blank">MySagePay</a> and confirm that transactions are working.', 'woocommerce-gateway-sagepay-form'), 'https://live.sagepay.com/mysagepay/' );	
			$notice .= '</p>';
			$notice .= '<p>';
			$notice .= sprintf(__('The <a href="%s" target="_blank">Docs</a> contain more information on this update.', 'woocommerce-gateway-sagepay-form'), 'https://docs.woocommerce.com/document/sagepay-form/#section-18');	
			$notice .= '</p>';
			

			$output  = '<div class="notice notice-error sagepaydirect-protocol4-nag is-dismissible">';
			$output .= $notice;
			$output .= '<br class="clear">';
			$output .= '</div>';

			echo $output;			
		
		}

		/**
		 * Display a notice
		 */
		function threeds2_admin_notice() {
		
			$notice  = '<h3 class="alignleft" style="line-height:150%; width:100%;">';
			$notice .= sprintf(__('Opayo Protocol 4.00 is now Live', 'woocommerce-gateway-sagepay-form') );	
			$notice .= '</h3>';
			$notice .= '<p>';
			$notice .= sprintf(__('Protocol 4.00 has been enabled on the Opayo Live servers, this update means that 3D Secure 2.0 is now live. Your site is set to use Protocol 3.00.', 'woocommerce-gateway-sagepay-form') );	
			$notice .= '</p>';
			$notice .= '<p>';
			$notice .= sprintf(__('You should make sure that you have 3D Secure turned on in <a href="%s" target="_blank">MySagePay</a> and confirm that test transactions are working with Protocol 4.00.', 'woocommerce-gateway-sagepay-form'), 'https://live.sagepay.com/mysagepay/' );	
			$notice .= '</p>';
			$notice .= '<p>';
			$notice .= sprintf(__('The <a href="%s" target="_blank">Docs</a> contain more information on this change', 'woocommerce-gateway-sagepay-form'), 'https://docs.woocommerce.com/document/sagepay-form/#section-18');	
			$notice .= '</p>';
			

			$output  = '<div class="notice notice-error sagepaydirect-threeds2-nag is-dismissible">';
			$output .= $notice;
			$output .= '<br class="clear">';
			$output .= '</div>';

			echo $output;			
		
		}

	} // End class
	
	$WC_Gateway_Sagepay_Direct_Update_Notice = new WC_Gateway_Sagepay_Direct_Update_Notice;