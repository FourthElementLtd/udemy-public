<?php

/**
 * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
*/

require_once dirname(dirname(__FILE__)) . '/classes/SagePayCw/PaymentMethod.php'; 

class SagePayCw_PayPal extends SagePayCw_PaymentMethod
{
	public $machineName = 'paypal';
	public $admin_title = 'PayPal';
	public $title = 'PayPal';
	
	protected function getMethodSettings(){
		return array(
			'seller_protection' => array(
				'title' => __("PayPal Seller Protection", 'woocommerce_sagepaycw'),
 				'default' => 'uncertain',
 				'description' => __("PayPal may provide seller protection. In case PayPal grants no seller protection (address is not confirmed and the payer could not be verified), what should happend with the transaction?", 'woocommerce_sagepaycw'),
 				'cwType' => 'select',
 				'type' => 'select',
 				'options' => array(
					'accept' => __("Accept the transaction", 'woocommerce_sagepaycw'),
 					'uncertain' => __("Mark the transaction as uncertain", 'woocommerce_sagepaycw'),
 					'cancel' => __("Cancel the transaction (the customer is forced to choose another payment method)", 'woocommerce_sagepaycw'),
 				),
 			),
 			'status_authorized' => array(
				'title' => __("Authorized Status", 'woocommerce_sagepaycw'),
 				'default' => 'wc-processing',
 				'description' => __("This status is set, when the payment was successfull and it is authorized.", 'woocommerce_sagepaycw'),
 				'cwType' => 'orderstatusselect',
 				'type' => 'select',
 				'options' => array(
					'use-default' => __("Use WooCommerce rules", 'woocommerce_sagepaycw'),
 				),
 				'is_order_status' => true,
 			),
 			'status_uncertain' => array(
				'title' => __("Uncertain Status", 'woocommerce_sagepaycw'),
 				'default' => 'wc-on-hold',
 				'description' => __("You can specify the order status for new orders that have an uncertain authorisation status.", 'woocommerce_sagepaycw'),
 				'cwType' => 'orderstatusselect',
 				'type' => 'select',
 				'options' => array(
				),
 				'is_order_status' => true,
 			),
 			'status_cancelled' => array(
				'title' => __("Cancelled Status", 'woocommerce_sagepaycw'),
 				'default' => 'wc-cancelled',
 				'description' => __("You can specify the order status when an order is cancelled.", 'woocommerce_sagepaycw'),
 				'cwType' => 'orderstatusselect',
 				'type' => 'select',
 				'options' => array(
					'no_status_change' => __("Don't change order status", 'woocommerce_sagepaycw'),
 				),
 				'is_order_status' => true,
 			),
 			'status_captured' => array(
				'title' => __("Captured Status", 'woocommerce_sagepaycw'),
 				'default' => 'no_status_change',
 				'description' => __("You can specify the order status for orders that are captured either directly after the order or manually in the backend.", 'woocommerce_sagepaycw'),
 				'cwType' => 'orderstatusselect',
 				'type' => 'select',
 				'options' => array(
					'no_status_change' => __("Don't change order status", 'woocommerce_sagepaycw'),
 				),
 				'is_order_status' => true,
 			),
 			'capturing' => array(
				'title' => __("Capturing", 'woocommerce_sagepaycw'),
 				'default' => 'direct',
 				'description' => __("Should the amount be captured automatically after the order (direct) or should the amount only be reserved (deferred)?", 'woocommerce_sagepaycw'),
 				'cwType' => 'select',
 				'type' => 'select',
 				'options' => array(
					'direct' => __("Directly after order", 'woocommerce_sagepaycw'),
 					'deferred' => __("Deferred", 'woocommerce_sagepaycw'),
 				),
 			),
 			'authorizationMethod' => array(
				'title' => __("Authorization Method", 'woocommerce_sagepaycw'),
 				'default' => 'ServerAuthorization',
 				'description' => __("Select the authorization method to use for processing this payment method.", 'woocommerce_sagepaycw'),
 				'cwType' => 'select',
 				'type' => 'select',
 				'options' => array(
					'ServerAuthorization' => __("Server Authorization (Direct)", 'woocommerce_sagepaycw'),
 				),
 			),
 		); 
	}
	
	public function __construct() {
		$this->icon = apply_filters(
			'woocommerce_sagepaycw_paypal_icon', 
			SagePayCw_Util::getResourcesUrl('icons/paypal.png')
		);
		parent::__construct();
	}
	
	public function createMethodFormFields() {
		$formFields = parent::createMethodFormFields();
		
		return array_merge(
			$formFields,
			$this->getMethodSettings()
		);
	}

}