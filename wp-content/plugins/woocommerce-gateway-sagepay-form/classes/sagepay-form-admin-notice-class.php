<?php
	/**
	 * Admin Notices for SagePay Form
	 */
	class WC_Gateway_Sagepay_Form_Update_Notice {
		
		public function __construct() {
			global $current_user;
			$current_user 	= wp_get_current_user();
			$user_id 		= $current_user->ID;
			
            /**
             * Add admin notice if mcrypt_encrypt not found
             * Only shown on SagePay settings page.
             */
            if( isset( $_GET['page'] ) && isset( $_GET['section'] ) ) {
            	if( current_user_can( 'manage_options' ) && 'wc-settings' == $_GET['page'] && 'checkout' == $_GET['tab'] && 'sagepayform' == $_GET['section'] ) {
            		add_action('admin_notices', array($this, 'admin_notice') );
				}
			}

			/**
			 * Add some notices to WooCommerce System Status
			 */
			if ( class_exists( 'WC_REST_System_Status_Controller' ) ) {
				add_action( 'woocommerce_system_status_report', array( $this, 'action_woocommerce_system_status_report' ), 10, 0 ); 
			}

		}
	
		/**
		 * Display a notice
		 */
		function admin_notice() {

			$is_error = FALSE;
			
			if( !function_exists('openssl_encrypt') ) {
				$is_error = TRUE;

				$error .= '<h3 class="alignleft" style="line-height:150%">';
				$error .= sprintf(__('IMPORTANT! SagePay requires that the information sent from your checkout is encrypted. We recommend that you use openssl_encrypt (<a href="%1$s" target="_blank">%1$s</a>) and your hosting does not appear to have support for this. Please contact your host.', 'woocommerce_sagepayform'), 'http://php.net/manual/en/function.openssl-encrypt.php');
				if( function_exists('mcrypt_encrypt') ) {
					$error .= sprintf(__('<br /><br />Your site will allow payments to SagePay, using the mcrypt library, but this is less secure and is no longer being developed, it will be deprectated in PHP 7.1 (%1$s)', 'woocommerce_sagepayform'), 'http://php.net/manual/en/function.openssl-encrypt.php');
				}

				$error .= '</h3>';
			}

			// Check for potential URL length issues
			if ( function_exists( 'ini_get' ) && extension_loaded( 'suhosin' ) ) {
				
				if( ini_get('suhosin.get.max_value_length') < 2000 || ini_get('suhosin.get.max_vars') < 2000 ) {

					$is_error = TRUE;

					$error .= __( '<h3 class="alignleft" style="line-height:150%">Warning</h3>', 'woocommerce_sagepayform' );
					$error .= __( '<p><strong>Your server configuration may need to be adjusted for SagePay Form to work correctly. Please place a test order to make sure your customers will be returned to your site correctly</strong></p>', 'woocommerce_sagepayform' );
					$error .= __( '<p>If you experience an issue after paying - you will probably see a white screen with a notice to check your WooCommerce SagePay Form settings - please ask your host to increase the following values</p>', 'woocommerce_sagepayform' );

					$error .= sprintf(__( '<p>suhosin.get.max_value_length = %s IDEAL VALUE : 2000</br />', 'woocommerce_sagepayform' ), size_format( wc_let_to_num( ini_get('suhosin.get.max_value_length') ) ) );

					$error .= sprintf(__( 'suhosin.get.max_vars = %s IDEAL VALUE : 2000</p>', 'woocommerce_sagepayform' ), size_format( wc_let_to_num( ini_get('suhosin.get.max_vars') ) ) );

					$error .= __( '<p>If you have successfully placed a test order and were returned to your "Thank you for ordering" page then you can ignore this warning</p>', 'woocommerce_sagepayform' );

				}

			}

			if( TRUE === $is_error ) {

				$output .= '<div class="notice notice-error">';
				$output .= $error;
				$output .= '<br class="clear">';
				$output .= '</div>';

				echo $output;

			}			
		
		}

		function action_woocommerce_system_status_report() {

			$system_status    = new WC_REST_System_Status_Controller;
			$environment      = $system_status->get_environment_info();

			$debug_data   = array();

			$debug_data['sage_mcrypt'] = array(
				'name'    => _x( 'MCrypt', 'woocommerce_sagepayform' ),
				'tip'	  => _x( 'label that indicates whether the MCrypt library is installed, this is a deprecated library.', 'woocommerce_sagepayform' ),
				'note'    => function_exists('mcrypt_encrypt') ? __( 'Yes', 'woocommerce_sagepayform' ) :  __( 'No', 'woocommerce_sagepayform' ),
				'success' => function_exists('mcrypt_encrypt') ? 1 : 0,
			);

			$debug_data['sage_openssl'] = array(
				'name'    => _x( 'OpenSSL', 'woocommerce_sagepayform' ),
				'tip'     => _x( 'label that indicates whether the OpenSSL library is installed', 'woocommerce_sagepayform' ),
				'note'    => function_exists('openssl_encrypt') ? __( 'Yes', 'woocommerce_sagepayform' ) :  __( 'No', 'woocommerce_sagepayform' ),
				'success' => function_exists('openssl_encrypt') ? 1 : 0,
			);

			$debug_data['sage_openssl_cbc'] = array(
				'name'    => _x( 'OpenSSL Methods', 'woocommerce_sagepayform' ),
				'tip'     => _x( 'label that indicates whether the correct OpenSSL encyption method is installed', 'woocommerce_sagepayform' ),
				'note'    => in_array( 'AES-128-CBC', openssl_get_cipher_methods() ) ? __( 'Yes', 'woocommerce_sagepayform' ) :  __( 'No', 'woocommerce_sagepayform' ),
				'success' => in_array( 'AES-128-CBC', openssl_get_cipher_methods() ) ? 1 : 0,
			);

			$debug_data['max_input_vars'] = array(
				'name'    => _x( 'PHP Max_Input_Vars', 'woocommerce_sagepayform' ),
				'tip'     => _x( 'The maximum number of variables your server can use for a single function to avoid overloads.', 'woocommerce_sagepayform' ),
				'note'    => $environment['php_max_input_vars'] >= 2000 ? $environment['php_max_input_vars'] : sprintf( _x( 'Your php_max_inpit_vars value is %s. If you experience any issues during checkout then increase this value to 5000.', 'woocommerce_sagepayform' ), $environment['php_max_input_vars'] ),
				'success' => $environment['php_max_input_vars'] >= 2000 ? 1 : 0,
			);

			include( SAGEPLUGINPATH . 'assets/templates/systemstatus.php' );

		}

	} // End class
	
	$WC_Gateway_Sagepay_Form_Update_Notice = new WC_Gateway_Sagepay_Form_Update_Notice;