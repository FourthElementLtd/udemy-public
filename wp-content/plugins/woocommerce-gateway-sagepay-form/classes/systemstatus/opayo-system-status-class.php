<?php
	/**
	 * Admin Notices for SagePay Form
	 */
	class WC_Gateway_Opayo_System_Status_Additions {
		
		public function __construct() {

			/**
			 * Add some notices to WooCommerce System Status
			 */
			add_action( 'woocommerce_system_status_report', array( $this, 'action_woocommerce_system_status_report' ), 10, 0 );

			// Add WooCommerce Tools option to test host IP address
            		add_filter( 'woocommerce_debug_tools', array( $this, 'woocommerce_opayo_direct_check_ip_address' ) );

		}

		/**
		 * [action_woocommerce_system_status_report description]
		 * @return [type] [description]
		 */
		function action_woocommerce_system_status_report() {

			$woocommerce_opayoform_settings 		= get_option( 'woocommerce_sagepayform_settings' );
			$woocommerce_opayodirect_settings 		= get_option( 'woocommerce_sagepaydirect_settings' );
			$woocommerce_opayopi_settings 			= get_option( 'woocommerce_opayopi_settings' );
			// $woocommerce_opayoserver_settings 	= get_option( 'woocommerce_opayoserver_settings' );
			$woocommerce_opayoserver_settings 		= FALSE;
			$woocommerce_reporting_settings 		= get_option( 'woocommerce_opayo_reporting_options' );
			
			$cipher_method = false;

			if( in_array( 'AES-128-CBC', openssl_get_cipher_methods() ) ) {
				$cipher_method = true;
			}

			if( in_array( 'aes-128-cbc', openssl_get_cipher_methods() ) ) {
				$cipher_method = true;
			}

			$debug_data   = array();

			$debug_data['opayo_plugin_version'] = array(
				'name'    => _x( 'Opayo Plugin Version', 'woocommerce-gateway-sagepay-form' ),
				'tip'     => _x( '', 'woocommerce-gateway-sagepay-form' ),
				'note'    => OPAYOPLUGINVERSION ,
				'success' => OPAYOPLUGINVERSION == OPAYOPLUGINVERSION ? 1 : 0,
			);

			$debug_data['opayo_form_enabled'] = array(
				'name'    => _x( 'Opayo Form Enabled?', 'woocommerce-gateway-sagepay-form' ),
				'tip'     => _x( 'Is SagePay Form enabled?', 'woocommerce-gateway-sagepay-form' ),
				'note'    => '',
				'success' => isset( $woocommerce_opayoform_settings['enabled'] ) && $woocommerce_opayoform_settings['enabled'] == 'yes' ? 1 : 0,
			);

			$debug_data['opayo_direct_enabled'] = array(
				'name'    => _x( 'Opayo Direct Enabled?', 'woocommerce-gateway-sagepay-form' ),
				'tip'     => _x( 'Is Opayo Direct enabled?', 'woocommerce-gateway-sagepay-form' ),
				'note'    => '',
				'success' => isset( $woocommerce_opayodirect_settings['enabled'] ) && $woocommerce_opayodirect_settings['enabled'] == 'yes' ? 1 : 0,
			);

			if ( isset( $woocommerce_opayodirect_settings['enabled'] ) && $woocommerce_opayodirect_settings['enabled'] == 'yes' ) {
				$debug_data['opayo_direct_protocol'] = array(
					'name'    => _x( 'Opayo Direct using Protocol 4.00?', 'woocommerce-gateway-sagepay-form' ),
					'tip'     => _x( 'Is Opayo Direct using Protocol 4.00?', 'woocommerce-gateway-sagepay-form' ),
					'note'    => '',
					'success' => $woocommerce_opayodirect_settings['vpsprotocol'] == '4.00' ? 1 : 0,
				);
			}

			$debug_data['opayo_server_enabled'] = array(
				'name'    => _x( 'Opayo Server Enabled?', 'woocommerce-gateway-sagepay-form' ),
				'tip'     => _x( 'Is Opayo Server enabled?', 'woocommerce-gateway-sagepay-form' ),
				'note'    => '',
				'success' => isset( $woocommerce_opayoserver_settings['enabled'] ) && $woocommerce_opayoserver_settings['enabled'] == 'yes' ? 1 : 0,
			);

			$debug_data['opayo_pi_enabled'] = array(
				'name'    => _x( 'Opayo Pi Enabled?', 'woocommerce-gateway-sagepay-form' ),
				'tip'     => _x( 'Is Opayo Pi enabled?', 'woocommerce-gateway-sagepay-form' ),
				'note'    => '',
				'success' => isset( $woocommerce_opayopi_settings['enabled'] ) && $woocommerce_opayopi_settings['enabled'] == 'yes' ? 1 : 0,
			);

			if ( isset( $woocommerce_opayopi_settings['enabled'] ) && $woocommerce_opayopi_settings['enabled'] == 'yes' ) {
				$debug_data['opayo_pi_checkout_form'] = array(
					'name'    => _x( 'Opayo Pi checkout form?', 'woocommerce-gateway-sagepay-form' ),
					'tip'     => _x( 'Which form is Opayo Pi using?', 'woocommerce-gateway-sagepay-form' ),
					'note'    => isset( $woocommerce_opayopi_settings['checkout_form'] ) ? $woocommerce_opayopi_settings['checkout_form'] : 'WooCommerce',
					'success' => 1,
				);

				$debug_data['opayo_pi_server_time'] = array(
					'name'    => _x( 'Current Date and Time on this server', 'woocommerce-gateway-sagepay-form' ),
					'tip'     => _x( '', 'woocommerce-gateway-sagepay-form' ),
					'note'    => $this->get_server_date_time(),
					'success' => 1,
				);
			}

			$debug_data['opayo_simple_enabled'] = array(
				'name'    => _x( 'Simple XML Available?', 'woocommerce-gateway-sagepay-form' ),
				'tip'     => _x( 'Opayo Reporting requires the PHP XML class', 'woocommerce-gateway-sagepay-form' ),
				'note'    => '',
				'success' => function_exists( 'simplexml_load_string' ) ? 1 : 0,
			);

			$debug_data['opayo_reporting_test_enabled'] = array(
				'name'    => _x( 'Opayo Reporting (Test)', 'woocommerce-gateway-sagepay-form' ),
				'tip'     => _x( 'Is Opayo Reporting enabled for test transactions?', 'woocommerce-gateway-sagepay-form' ),
				'note'    => '',
				'success' => 0,
			);

			$debug_data['opayo_reporting_live_enabled'] = array(
				'name'    => _x( 'Opayo Reporting (Live)', 'woocommerce-gateway-sagepay-form' ),
				'tip'     => _x( 'Is Opayo Reporting enabled for live transactions?', 'woocommerce-gateway-sagepay-form' ),
				'note'    => '',
				'success' => 0,
			);

			if( isset( $woocommerce_reporting_settings['test_opayo_reporting_username'] ) && strlen($woocommerce_reporting_settings['test_opayo_reporting_username']) != 0 ) {
				$debug_data['opayo_reporting_test_enabled']['success'] = 1;
			}

			if( isset( $woocommerce_reporting_settings['live_opayo_reporting_username'] ) && strlen($woocommerce_reporting_settings['live_opayo_reporting_username']) != 0 ) {
				$debug_data['opayo_reporting_live_enabled']['success'] = 1;
			}

			$debug_data['opayo_mcrypt'] = array(
				'name'    => _x( 'MCrypt', 'woocommerce-gateway-sagepay-form' ),
				'tip'	  => _x( 'label that indicates whether the MCrypt library is installed, this is a deprecated library.', 'woocommerce-gateway-sagepay-form' ),
				'note'    => function_exists('mcrypt_encrypt') ? __( 'Yes. MCrypt is deprecated after PHP version 7.1.', 'woocommerce-gateway-sagepay-form' ) :  __( 'No', 'woocommerce-gateway-sagepay-form' ),
				'success' => function_exists('mcrypt_encrypt') ? 0 : 1,
			);

			$debug_data['opayo_openssl'] = array(
				'name'    => _x( 'OpenSSL', 'woocommerce-gateway-sagepay-form' ),
				'tip'     => _x( 'label that indicates whether the OpenSSL library is installed', 'woocommerce-gateway-sagepay-form' ),
				'note'    => function_exists('openssl_encrypt') ? __( 'Yes', 'woocommerce-gateway-sagepay-form' ) :  __( 'No', 'woocommerce-gateway-sagepay-form' ),
				'success' => function_exists('openssl_encrypt') ? 1 : 0,
			);

			$debug_data['opayo_openssl_cbc'] = array(
				'name'    => _x( 'OpenSSL Methods', 'woocommerce-gateway-sagepay-form' ),
				'tip'     => _x( 'label that indicates whether the correct OpenSSL encyption method is installed', 'woocommerce-gateway-sagepay-form' ),
				'note'    => $cipher_method ? __( 'Yes', 'woocommerce-gateway-sagepay-form' ) :  __( 'No', 'woocommerce-gateway-sagepay-form' ),
				'success' => $cipher_method ? 1 : 0,
			);

			$debug_data['max_input_vars'] = array(
				'name'    => _x( 'PHP Max_Input_Vars', 'woocommerce-gateway-sagepay-form' ),
				'tip'     => _x( 'The maximum number of variables your server can use for a single function to avoid overloads.', 'woocommerce-gateway-sagepay-form' ),
				'note'    => ini_get('max_input_vars') >= 2000 ? ini_get('max_input_vars') : sprintf( _x( 'Your php_max_inpt_vars value is %s. If you experience any issues during checkout then increase this value to 5000.', 'woocommerce-gateway-sagepay-form' ), ini_get('max_input_vars') ),
				'success' => ini_get('max_input_vars') >= 2000 ? 1 : 0,
			);

			include( SAGEPLUGINPATH . 'assets/templates/systemstatus.php' );

		}

		function get_server_date_time() {

			$time_now 	= (array) new DateTime();
            		$now        	= $time_now['date'];

            		$reponse = sprintf( __( 'Server Date/Time is : %s', 'woocommerce-gateway-sagepay-form' ), wc_clean( $now ) );

			return $reponse;
		}

		/**
		 * [woocommerce_opayo_direct_check_ip_address description]
		 * @param  [type] $tools [description]
		 * @return [type]        [description]
		 */
		function woocommerce_opayo_direct_check_ip_address( $tools ) {

			$settings 		= get_option( 'woocommerce_sagepaydirect_settings' );

			if( isset($settings) && $settings['enabled'] == 'yes' ) {

				$last_check = get_option( 'opayo_direct_valid_ipaddress' );

				if( isset( $last_check ) && $last_check !='' ) {
					$desc =  sprintf( __( 'This check has previously returned <strong>%s</strong> as your server IP Address. Use this IP Address in MySagePay.', 'woocommerce-gateway-sagepay-form' ), $last_check );
				} else {
					$desc =  __( 'This will send a post from your server to a remote server and return the hosting IP address', 'woocommerce-gateway-sagepay-form' );
				}

		        $tools['opayo_direct_check_ip_address'] = array(
		                'name'   => __( 'Get IP Address for MySagePay settings.', 'woocommerce-gateway-sagepay-form' ),
		                'button' => __( 'Get IP Address', 'woocommerce-gateway-sagepay-form' ),
		                'desc'   => $desc,
		                'callback' => array( $this, 'woocommerce_debug_tools_execute_opayo_direct_check_ip_address' ),
		            );

			    }

	        	return $tools;

	        }

	        /**
	         * [woocommerce_debug_tools_execute_opayo_direct_check_ip_address description]
	         * @return [type] [description]
	         */
	        function woocommerce_debug_tools_execute_opayo_direct_check_ip_address() {

			$params = array(
					'method' 	=> 'POST',
					'timeout' 	=> apply_filters( 'woocommerce_opayo_post_timeout', 45 ),
					'httpversion' 	=> '1.1',
					'headers' 	=> array('Content-Type'=> 'application/x-www-form-urlencoded'),
					'body' 		=> NULL,
				);

			$res = wp_remote_post( 'https://icanhazip.com', $params );

			if( is_wp_error( $res ) ) {
				$reponse = __( 'IP Address can not be obtained. Contact Opayo.', 'woocommerce-gateway-sagepay-form' );
			} else {
				update_option( 'opayo_direct_valid_ipaddress', $res['body'] );
				$reponse = sprintf( __( 'Enter this IP Address in to MySagePay : %s', 'woocommerce-gateway-sagepay-form' ), wc_clean( $res['body'] ) );

			}

			return $reponse;
	        }

	} // End class
	
	$WC_Gateway_Opayo_System_Status_Additions = new WC_Gateway_Opayo_System_Status_Additions;