<?php
	class Sagepay_Direct_Messages extends WC_Gateway_Sagepay_Direct {

		private $order_id;
		private $result;

		public function __construct( $order_id, $result ) {

			parent::__construct();

			$this->order_id 	= $order_id;
			$this->result 		= $result;
			$this->settings 	= get_option( 'woocommerce_sagepaydirect_settings' );

		}
	
		function refund() {



		}

		function get_meta_item( $meta, $order ) {
			
		}

	} // End class
