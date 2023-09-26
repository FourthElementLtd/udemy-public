<?php

    /**
     * WC_Gateway_Opayo_Reporting class.
     */
    class WC_Gateway_Opayo_Reporting_Meta_Boxes extends WC_Gateway_Opayo_Reporting {

    	/**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {

			// Add Opayo Reporting meta box to WooCommerce orders
			add_action( 'add_meta_boxes', array( __CLASS__,'opayo_reporting_add_meta_box' ), 10, 2 );

			// Add Opayo Reporting meta box to WooCommerce Subscriptions Maybe
			if( class_exists( 'WC_Subscriptions' ) ) {
				add_action( 'add_meta_boxes', array( __CLASS__,'opayo_reporting_add_meta_box_subscription' ), 10, 2 );
			}

        } // END __construct

        /**
         * opayo_reporting_add_meta_box
         *
         * Add a meta box to the edit order screen
         * 
         * @param  [type] $post_type [description]
         * @param  [type] $post      [description]
         * @return [type]            [description]
         */
		public static function opayo_reporting_add_meta_box( $post_type, $post ) {

			if( $post_type == 'shop_order' ) {

				$order_id 			= $post->ID;
				$order 				= new WC_Order( $order_id );
				$payment_method 	= $order->get_payment_method();


				if( isset( $payment_method ) && in_array( $order->get_payment_method(), WC_Gateway_Opayo_Reporting::$sagepay_payment_methods_array ) ) {

					// Get the current order Status
					$order_status = $order->get_status();

					// Set an array of statuses that are "completed" - possibly needed for custom order status manager plugins
					$final_order_statuses = apply_filters( 'opayo_reporting_metabox_final_order_status', array( 'completed' ), $order );

					// Get the transaction details from order meta
					$sageresult = get_post_meta( $order_id, '_sageresult', TRUE );

					// Get the '_opayo_reporting_output' from the order meta
					$opayo_reporting_output = get_post_meta( $order_id, '_opayo_reporting_output', TRUE );

					if( !isset( $opayo_reporting_output ) ) {

						// If _opayo_reporting data is not available then get the report from Opayo regardless of order status
						WC_Gateway_Opayo_Reporting_Meta_Boxes::pre_get_opayo_report( $order, $order_id );

					} elseif ( !in_array( $order_status, $final_order_statuses) ) {

						// If order status is not complete then re-fetch the report
						WC_Gateway_Opayo_Reporting_Meta_Boxes::pre_get_opayo_report( $order, $order_id );

					} else {

						// The order is complete and there is a report in the order meta, no action required
						 
					}

					// Add the meta box(es)
					add_meta_box( 'opayo-reporting-details', __('Opayo Reporting', 'woocommerce-gateway-sagepay-form'), array( __CLASS__,'opayo_reporting_details_meta_box' ), 'shop_order', 'side', 'high');

					if( isset( $settings['opayo_reporting_debugging'] ) && $settings['opayo_reporting_debugging'] == 'yes' ) {
						add_meta_box( 'opayo-reporting-details-full-result', __('Opayo Reporting (Full Result)', 'woocommerce-gateway-sagepay-form'), array( __CLASS__,'opayo_reporting_details_full_meta_box' ), 'shop_order', 'advanced', 'low');
					}

				}


			}

		}

		/**
		 * [pre_get_opayo_report description]
		 * @param  [type] $order      [description]
		 * @param  [type] $sageresult [description]
		 * @param  [type] $order_id   [description]
		 * @return [type]             [description]
		 */
		public static function pre_get_opayo_report( $order, $order_id ) {

			// Get the report
			$output = WC_Gateway_Opayo_Reporting::opayo_reporting( $order );

			// Update order with full report
			update_post_meta( $order_id, '_opayo_reporting_output', $output );

			// Update order with Thirdman Action
			WC_Gateway_Opayo_Reporting::update_order_thirdman_action( $order, $output );

		}

        /**
         * opayo_reporting_details_meta_box
         *
         * Add the thirdman result to the meta box.
         * 
         * @param  [type] $post [description]
         * @return [type]       [description]
         */
		public static function opayo_reporting_details_meta_box( $post ) {
			global $woocommerce;

			$settings 	= get_option( 'woocommerce_opayo_reporting_options' );

			$order_id 	= $post->ID;

			$order  	= new WC_Order( $order_id );
			$sageresult = get_post_meta( $order_id, '_sageresult', TRUE );
			$note 		= '';

			$output 	= get_post_meta( $order_id, '_opayo_reporting_output', TRUE );

			$override_fraud_status = WC_Gateway_Opayo_Reporting::get_override_fraud_status( $order );

			// Update the order status if necessary
			if( isset( $settings['opayo_reporting_update_status'] ) && $settings['opayo_reporting_update_status'] == 'yes' && !$override_fraud_status ) {
				WC_Gateway_Opayo_Reporting::update_order_status( $order, $output );
			}

			if( isset( $output['errorcode'] ) && $output['errorcode'] == '0000' ) {
				?>
				<div class="opayo_reporting_group">
				<table class="opayo_reporting_list">
				<?php
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_status( $output );
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_t3mscore( $output );
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_t3maction( $output );
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_t3mid( $output );
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_fraudcode( $output );
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_fraudcodedetail( $output );
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_fraudscreenrecommendation( $output );
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_cv2result( $output );
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_addressresult( $output );
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_postcoderesult( $output );
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_threedresult( $output );
				?>
				</table>
				<div class="clear"></div>
				</div>
				<?php
			} elseif( isset( $output['errorcode'] ) ) { 

				$validate_account = WC_Gateway_Opayo_Reporting::validate_account( $order );
?>
				<div class="opayo_reporting_group opayo_reporting_group_error">
				<table class="opayo_reporting_list opayo_reporting_list_error">
					<tr class="left">
						<td colspan="2"><p><?php echo __('This transaction can not be verified in MySagePay. Login to MySagePay to confirm the transaction has been properly authorised.', 'woocommerce-gateway-sagepay-form'); ?></p></td>
					</tr>

					<tr class="left">
						<th><?php echo __('Error Code :', 'woocommerce-gateway-sagepay-form') . '</th><td>' . $output['errorcode']; ?></td>
					</tr>
					<tr class="left">
						<th><?php echo __('Error Description :', 'woocommerce-gateway-sagepay-form') . '</th><td>' . htmlentities( $output['error'] ); ?></td>
					</tr>
<?php
				if( !$validate_account ) {
?>
					<tr class="left">
						<td colspan="2"><p><?php echo __('Please check your Opayo Reporting Account details, this account can not connect to Opayo Reporting.', 'woocommerce-gateway-sagepay-form'); ?></p></td>
					</tr>
<?php
				}
?>

				</table>
				<div class="clear"></div>
				</div>
<?php 
			}
			
		}

        /**
         * opayo_reporting_details_meta_box
         *
         * Add the thirdman result to the meta box.
         * 
         * @param  [type] $post [description]
         * @return [type]       [description]
         */
		public static function opayo_reporting_details_full_meta_box( $post ) {
			global $woocommerce;

			$order_id = $post->ID;

			$order  	= new WC_Order( $order_id );
			$sageresult = get_post_meta( $order_id, '_sageresult', TRUE );
			$note 		= '';

			$output 	= get_post_meta( $order_id, '_opayo_reporting_output', TRUE );
			$thirdman 	= get_post_meta( $order_id, '_opayo_thirdman_output', TRUE );

			if( is_array( $output ) ) {
				array_walk_recursive($output, function (&$val) {
					$val = htmlentities($val, ENT_QUOTES);
				});
			}

			if( is_array( $thirdman ) ) {
				array_walk_recursive($thirdman, function (&$val) {
					$val = htmlentities($val, ENT_QUOTES);
				});
			}

			?>
			<div class="sagepay_group">
				<ul class="totals">
				<?php
				echo '<pre>' . print_r( $output, TRUE ) . '</pre>';
				echo '<pre>' . print_r( $thirdman, TRUE ) . '</pre>';
				?>
				</ul>
				<div class="clear"></div>
			</div><?php
			
		}

		// Metabox Getters
		/**
		 * [get_metabox_status description]
		 * @param  [type] $output [description]
		 * @return [type]         [description]
		 */
		private static function get_metabox_status( $output ) {
			if( isset( $output['status'] ) ){
				$return = '<tr class="left opayo_reporting_item">
							<th class="opayo_reporting_title">' . __('Status :', 'woocommerce-gateway-sagepay-form') . '</th>
							<td class="opayo_reporting_value">' . $output['status'] . '</td>
						   </tr>';

				return $return;
			}

			return NULL;
		}

		/**
		 * [get_metabox_t3mscore description]
		 * @param  [type] $output [description]
		 * @return [type]         [description]
		 */
		private static function get_metabox_t3mscore( $output ) {
			if( isset( $output['t3mscore'] ) ){

				$opayo_reporting_item_flag = '';
				if( intval( $output['t3mscore'] ) <= 0 ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_success';
				} else {
					$opayo_reporting_item_flag = ' opayo_reporting_item_check';
				}

				$return = '<tr class="left opayo_reporting_item' . $opayo_reporting_item_flag . '">
							<th class="opayo_reporting_title">' . __('3rd Man Score:', 'woocommerce-gateway-sagepay-form') . '</th>
							<td class="opayo_reporting_value">' . $output['t3mscore'] . '</td>
						   </tr>';

				return $return;
			}

			return NULL;
		}

		/**
		 * [get_metabox_t3maction description]
		 * @param  [type] $output [description]
		 * @return [type]         [description]
		 */
		private static function get_metabox_t3maction( $output ) {
			if( isset( $output['t3maction'] ) ){

				$opayo_reporting_item_flag = '';
				if( strtoupper( $output['t3maction'] ) == 'OK' ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_success';
				}

				if( strtoupper( $output['t3maction'] ) == 'HOLD' ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_check';
				}

				if( strtoupper( $output['t3maction'] ) == 'REJECT' ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_fail';
				}

				$return = '<tr class="left opayo_reporting_item' . $opayo_reporting_item_flag . '">
							<th class="opayo_reporting_title">' . __('3rd Man Recommendation:', 'woocommerce-gateway-sagepay-form') . '</th>
							<td class="opayo_reporting_value">' . $output['t3maction'] . '</td>
						   </tr>';

				return $return;
			}

			return NULL;
		}

		/**
		 * [get_metabox_t3mid description]
		 * @param  [type] $output [description]
		 * @return [type]         [description]
		 */
		private static function get_metabox_t3mid( $output ) {
			if( isset( $output['t3mid'] ) ){
				$return = '<tr class="left opayo_reporting_item">
							<th class="opayo_reporting_title">' . __('ThirdMan ID:', 'woocommerce-gateway-sagepay-form') . '</th>
							<td class="opayo_reporting_value">' . $output['t3mid'] . '</td>
						   </tr>';

				return $return;
			}

			return NULL;
		}

		/**
		 * [get_metabox_fraudcode description]
		 * @param  [type] $output [description]
		 * @return [type]         [description]
		 */
		private static function get_metabox_fraudcode( $output ) {
			if( isset( $output['fraudcode'] ) ){
				$return = '<tr class="left opayo_reporting_item">
							<th class="opayo_reporting_title">' . __('Fraud Code:', 'woocommerce-gateway-sagepay-form') . '</th>
							<td class="opayo_reporting_value">' . $output['fraudcode'] . '</td>
						   </tr>';

				return $return;
			}

			return NULL;
		}

		/**
		 * [get_metabox_fraudcodedetail description]
		 * @param  [type] $output [description]
		 * @return [type]         [description]
		 */
		private static function get_metabox_fraudcodedetail( $output ) {
			if( isset( $output['fraudcodedetail'] ) ){
				$return = '<tr class="left opayo_reporting_item">
							<th class="opayo_reporting_title">' . __('Code Detail:', 'woocommerce-gateway-sagepay-form') . '</th>
							<td class="opayo_reporting_value">' . $output['fraudcodedetail'] . '</td>
						   </tr>';

				return $return;
			}

			return NULL;
		}

		/**
		 * [get_metabox_fraudscreenrecommendation description]
		 * @param  [type] $output [description]
		 * @return [type]         [description]
		 */
		private static function get_metabox_fraudscreenrecommendation( $output ) {
			if( isset( $output['fraudscreenrecommendation'] ) ){
				$return = '<tr class="left opayo_reporting_item">
							<th class="opayo_reporting_title">' . __('Fraud Recommendation:', 'woocommerce-gateway-sagepay-form') . '</th>
							<td class="opayo_reporting_value">' . $output['fraudscreenrecommendation'] . '</td>
						   </tr>';

				return $return;
			}

			return NULL;
		}

		/**
		 * [get_metabox_cv2result description]
		 * @param  [type] $output [description]
		 * @return [type]         [description]
		 */
		private static function get_metabox_cv2result( $output ) {
			if( isset( $output['cv2result'] ) ){

				$opayo_reporting_item_flag = '';
				if( in_array( $output['cv2result'], WC_Gateway_Opayo_Reporting::$cvvAddress_success ) ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_success';
				}

				if( in_array( $output['cv2result'], WC_Gateway_Opayo_Reporting::$cvvAddress_check ) ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_check';
				}

				if( in_array( $output['cv2result'], WC_Gateway_Opayo_Reporting::$cvvAddress_fail ) ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_fail';
				}

				$return = '<tr class="left opayo_reporting_item' . $opayo_reporting_item_flag . '">
							<th class="opayo_reporting_title">' . __('CV2 Check:', 'woocommerce-gateway-sagepay-form') . '</th>
							<td class="opayo_reporting_value">' . $output['cv2result'] . '</td>
						   </tr>';

				return $return;
			}

			return NULL;
		}

		/**
		 * [get_metabox_addressresult description]
		 * @param  [type] $output [description]
		 * @return [type]         [description]
		 */
		private static function get_metabox_addressresult( $output ) {
			if( isset( $output['addressresult'] ) ){

				$opayo_reporting_item_flag = '';
				if( in_array( $output['addressresult'], WC_Gateway_Opayo_Reporting::$cvvAddress_success ) ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_success';
				}

				if( in_array( $output['addressresult'], WC_Gateway_Opayo_Reporting::$cvvAddress_check ) ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_check';
				}

				if( in_array( $output['addressresult'], WC_Gateway_Opayo_Reporting::$cvvAddress_fail ) ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_fail';
				}

				$return = '<tr class="left opayo_reporting_item' . $opayo_reporting_item_flag . '">
							<th class="opayo_reporting_title">' . __('Address Check:', 'woocommerce-gateway-sagepay-form') . '</th>
							<td class="opayo_reporting_value">' . $output['addressresult'] . '</td>
						   </tr>';

				return $return;
			}

			return NULL;
		}

		/**
		 * [get_metabox_postcoderesult description]
		 * @param  [type] $output [description]
		 * @return [type]         [description]
		 */
		private static function get_metabox_postcoderesult( $output ) {
			if( isset( $output['postcoderesult'] ) ){

				$opayo_reporting_item_flag = '';
				if( in_array( $output['postcoderesult'], WC_Gateway_Opayo_Reporting::$cvvAddress_success ) ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_success';
				}

				if( in_array( $output['postcoderesult'], WC_Gateway_Opayo_Reporting::$cvvAddress_check ) ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_check';
				}

				if( in_array( $output['postcoderesult'], WC_Gateway_Opayo_Reporting::$cvvAddress_fail ) ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_fail';
				}

				$return = '<tr class="left opayo_reporting_item' . $opayo_reporting_item_flag . '">
							<th class="opayo_reporting_title">' . __('Postcode Check:', 'woocommerce-gateway-sagepay-form') . '</th>
							<td class="opayo_reporting_value">' . $output['postcoderesult'] . '</td>
						   </tr>';

				return $return;
			}

			return NULL;
		}

		/**
		 * [get_metabox_threedresult description]
		 * @param  [type] $output [description]
		 * @return [type]         [description]
		 */
		private static function get_metabox_threedresult( $output ) {
			if( isset( $output['threedresult'] ) ){

				$opayo_reporting_item_flag = '';
				if( in_array( $output['threedresult'], WC_Gateway_Opayo_Reporting::$thressds_success ) ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_success';
				}

				if( in_array( $output['threedresult'], WC_Gateway_Opayo_Reporting::$thressds_check ) ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_check';
				}

				if( in_array( $output['threedresult'], WC_Gateway_Opayo_Reporting::$thressds_fail ) ) {
					$opayo_reporting_item_flag = ' opayo_reporting_item_fail';
				}

				$return = '<tr class="left opayo_reporting_item' . $opayo_reporting_item_flag . '">
							<th class="opayo_reporting_title">' . __('3D Secure:', 'woocommerce-gateway-sagepay-form') . '</th>
							<td class="opayo_reporting_value">' . $output['threedresult'] . '</td>
						   </tr>';

				return $return;
			}
			
			return NULL;
		}

		/**
		 * [get_metabox_expirydate description]
		 * @param  [type] $output [description]
		 * @return [type]         [description]
		 */
		private static function get_metabox_expirydate( $output ) {

			if( isset( $output['expirydate'] ) ) {
				$message = $output['expirydate'];
			} else {
				$message = __('Expiry date not available', 'woocommerce-gateway-sagepay-form');
			}

			return '<tr class="left">
						<th>' .  __('Card Expiry Date :', 'woocommerce-gateway-sagepay-form') . '</th><td>' . $message . '</td>
					</tr>';

		}

		private static function get_metabox_vpstxid( $output ) {

			if( isset( $output['vpstxid'] ) ) {
				$message = $output['vpstxid'];
			} else {
				$message = __('VPSTxId not available', 'woocommerce-gateway-sagepay-form');
			}

			return '<tr class="left">
						<th>' .  __('VPSTxId :', 'woocommerce-gateway-sagepay-form') . '</th><td>' . $message . '</td>
					</tr>';			
		}

        /**
         * [opayo_reporting_add_meta_box_subscription description]
         * @param  [type] $post_type [description]
         * @param  [type] $post      [description]
         * @return [type]            [description]
         */
        public static function opayo_reporting_add_meta_box_subscription( $post_type, $post ) {

        	if( $post_type == 'shop_subscription' ) {

				$subscription_id 	= $post->ID;
				$subscription 		= new WC_Order( $subscription_id );
				$payment_method 	= $subscription->get_payment_method();

				if( isset( $payment_method ) && in_array( $subscription->get_payment_method(), WC_Gateway_Opayo_Reporting::$sagepay_payment_methods_array ) ) {

					WC_Gateway_Opayo_Reporting_Meta_Boxes::pre_get_opayo_report( $subscription, $subscription_id );

					// Add the meta box
					add_meta_box( 'opayo-reporting-details', __('Opayo Reporting', 'woocommerce-gateway-sagepay-form'), array( __CLASS__,'opayo_reporting_subscription_meta_box' ), 'shop_subscription', 'side', 'high');

				}

			}

		}

		/**
		 * [opayo_reporting_subscription_meta_box description]
		 * @param  [type] $post [description]
		 * @return [type]       [description]
		 */
		public static function opayo_reporting_subscription_meta_box( $post ) {

			$subscription_id 	= $post->ID;
			$subscription 		= new WC_Order( $subscription_id );
			$output 			= get_post_meta( $subscription_id, '_opayo_reporting_output', TRUE );

?>
			<div class="opayo_reporting_group">
			<table class="opayo_reporting_list">
<?php

			if( isset( $output['errorcode'] ) && $output['errorcode'] == '0000' ) {

				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_status( $output );
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_expirydate( $output );
				echo WC_Gateway_Opayo_Reporting_Meta_Boxes::get_metabox_vpstxid( $output );

			} elseif( isset( $output['errorcode'] ) ) { 

				$validate_account = WC_Gateway_Opayo_Reporting::validate_account( $subscription );
?>
					<tr class="left">
						<th><?php echo __('Error Code :', 'woocommerce-gateway-sagepay-form') . '</th><td>' . $output['errorcode']; ?></td>
					</tr>
					<tr class="left">
						<th><?php echo __('Error Description :', 'woocommerce-gateway-sagepay-form') . '</th><td>' . htmlentities( $output['error'] ); ?></td>
					</tr>
<?php
				if( !$validate_account ) {
?>
					<tr class="left">
						<td colspan="2"><p><?php echo __('Please check your Opayo Reporting Account details, this account can not connect to Opayo Reporting.', 'woocommerce-gateway-sagepay-form'); ?></p></td>
					</tr>
<?php
				}
 
			}

?>
			</table>
			<div class="clear"></div>
			</div>
<?php

		}

		// SCRIPTS
		
		/**
		 * [opayo_reporting_script description]
		 * @return [type] [description]
		 */
		private static function opayo_reporting_script() {
			wp_enqueue_script(
				'opayo-reporting',
				SAGEPLUGINURL.'assets/js/opayo-reporting.js',
				array( 'jquery' ),
				OPAYOPLUGINVERSION
			);
		}

	} // End class
	
	$WC_Gateway_Opayo_Reporting_Meta_Boxes = new WC_Gateway_Opayo_Reporting_Meta_Boxes;
