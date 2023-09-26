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

class SagePayCw_AmericanExpress extends SagePayCw_PaymentMethod
{
	public $machineName = 'americanexpress';
	public $admin_title = 'American Express';
	public $title = 'American Express';
	
	protected function getMethodSettings(){
		return array(
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
 			'address_check_behavior' => array(
				'title' => __("Address Check Result", 'woocommerce_sagepaycw'),
 				'default' => array(
					0 => 'NOTPROVIDED',
 				),
 				'description' => __("During the checkout the address and post code are checked against the linked data with the credit card. The selected outcomes are threaded as uncertain transactions.", 'woocommerce_sagepaycw'),
 				'cwType' => 'multiselect',
 				'type' => 'multiselect',
 				'options' => array(
					'NOTPROVIDED' => __("No address or no post code was provided", 'woocommerce_sagepaycw'),
 					'NOTCHECKED' => __("The address or post code are not checked", 'woocommerce_sagepaycw'),
 					'NOTMATCHED' => __("The address or post code do not match", 'woocommerce_sagepaycw'),
 				),
 			),
 			'cv2_check_behavior' => array(
				'title' => __("CV2 Check Result", 'woocommerce_sagepaycw'),
 				'default' => array(
					0 => 'NOTMATCHED',
 					1 => 'NOTPROVIDED',
 				),
 				'description' => __("During the checkout the CV2 code is checked. The selected outcomes are treated as uncertain transactions.", 'woocommerce_sagepaycw'),
 				'cwType' => 'multiselect',
 				'type' => 'multiselect',
 				'options' => array(
					'NOTPROVIDED' => __("No CV2 code was provided", 'woocommerce_sagepaycw'),
 					'NOTCHECKED' => __("CV2 code was not checked", 'woocommerce_sagepaycw'),
 					'NOTMATCHED' => __("CV2 not matched", 'woocommerce_sagepaycw'),
 				),
 			),
 			'three_d_secure_behavior' => array(
				'title' => __("3D Secure Check", 'woocommerce_sagepaycw'),
 				'default' => array(
					0 => 'NOTCHECKED',
 					1 => 'authentication_failed',
 				),
 				'description' => __("During the authorization of the payment a 3D secure check may be done. The selected outcomes are treated as uncertain transactions.", 'woocommerce_sagepaycw'),
 				'cwType' => 'multiselect',
 				'type' => 'multiselect',
 				'options' => array(
					'authentication_failed' => __("The 3D secure authentication failed.", 'woocommerce_sagepaycw'),
 					'NOTCHECKED' => __("The 3D secure check was disabled for the transaction.", 'woocommerce_sagepaycw'),
 					'NOTAVAILABLE' => __("The card does not participate in the 3D scheme.", 'woocommerce_sagepaycw'),
 				),
 			),
 			'fraud_behavior' => array(
				'title' => __("Fraud Check Result", 'woocommerce_sagepaycw'),
 				'default' => array(
					0 => 'DENY',
 				),
 				'description' => __("During the authorization of the payment a fraud check may be done by ReD. The selected outcomes are treated as uncertain transactions.", 'woocommerce_sagepaycw'),
 				'cwType' => 'multiselect',
 				'type' => 'multiselect',
 				'options' => array(
					'DENY' => __("ReD recommends to reject the transaction.", 'woocommerce_sagepaycw'),
 					'NOTCHECKED' => __("No fraud check was done.", 'woocommerce_sagepaycw'),
 				),
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
 			'alias_manager' => array(
				'title' => __("Alias Manager", 'woocommerce_sagepaycw'),
 				'default' => 'inactive',
 				'description' => __("The alias manager allows the customer to select from a credit card previously stored. The sensitive data is stored by Opayo.", 'woocommerce_sagepaycw'),
 				'cwType' => 'select',
 				'type' => 'select',
 				'options' => array(
					'active' => __("Active", 'woocommerce_sagepaycw'),
 					'inactive' => __("Inactive", 'woocommerce_sagepaycw'),
 				),
 			),
 		); 
	}
	
	public function __construct() {
		$this->icon = apply_filters(
			'woocommerce_sagepaycw_americanexpress_icon', 
			SagePayCw_Util::getResourcesUrl('icons/americanexpress.png')
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