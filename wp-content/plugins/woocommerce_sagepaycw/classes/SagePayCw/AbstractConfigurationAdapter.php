<?php
/**
 * * You are allowed to use this API in your web application.
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

require_once 'Customweb/Core/Stream/Input/File.php';
require_once 'Customweb/Payment/IConfigurationAdapter.php';


/**
 *
 */
abstract class SagePayCw_AbstractConfigurationAdapter implements Customweb_Payment_IConfigurationAdapter
{
	
	protected $settingsMap=array(
		'vendor' => array(
			'id' => 'sagepay-vendor-setting',
 			'machineName' => 'vendor',
 			'type' => 'textfield',
 			'label' => 'Opayo Vender Name',
 			'description' => 'Used to authenticate your site. This should contain the Opayo Vendor Name supplied by Opayo when your account was created.',
 			'defaultValue' => '',
 			'allowedFileExtensions' => array(
			),
 		),
 		'operation_mode' => array(
			'id' => 'sagepay-operation-mode-setting',
 			'machineName' => 'operation_mode',
 			'type' => 'select',
 			'label' => 'Operation Mode',
 			'description' => 'You can switch between the different environments, by selecting the corresponding operation mode.',
 			'defaultValue' => 'test',
 			'allowedFileExtensions' => array(
			),
 		),
 		'deferred_authorization_type' => array(
			'id' => 'sagepay-deferred-authorization-type-setting',
 			'machineName' => 'deferred_authorization_type',
 			'type' => 'select',
 			'label' => 'Deferred Authorization Type',
 			'description' => 'Opayo supports two types of deferred authorization. The deferred authorization allows only one capture per transaction, but it guarantees the payment, because a reservation is added on the customer\'s card. In case of authenticate you can do multiple captures per transaction, but there is no reservation of the amount on the card.',
 			'defaultValue' => 'deferred',
 			'allowedFileExtensions' => array(
			),
 		),
 		'direct_capture_type' => array(
			'id' => 'sagepay-direct-capture-type-setting',
 			'machineName' => 'direct_capture_type',
 			'type' => 'select',
 			'label' => 'Direct Capture Type',
 			'description' => 'Here you can select how the direct capture process is done. Either we first authorize the transaction and capture it automatically later (Two Step). Or it is done immediately within the authorization (During authorization). During the authorization means we use the transaction Type \'PAYMENT\', we also process the feedback from Opayo immediately. This can lead to issues, if your shop takes a long time to process an order. (e.g. send confirmation mail, update stock, etc.) Two Step uses the Transaction Type \'DEFERRED\'.',
 			'defaultValue' => 'two_step',
 			'allowedFileExtensions' => array(
			),
 		),
 		'description' => array(
			'id' => 'sagepay-description-setting',
 			'machineName' => 'description',
 			'type' => 'multilangfield',
 			'label' => 'Description of the order',
 			'description' => 'The description of goods purchased is displayed on the Opayo Server payment page as the customer enters their card details.',
 			'defaultValue' => 'Your order description',
 			'allowedFileExtensions' => array(
			),
 		),
 		'transaction_id_schema' => array(
			'id' => 'sagepay-transaction-id-schema',
 			'machineName' => 'transaction_id_schema',
 			'type' => 'textfield',
 			'label' => 'Transaction ID Prefix',
 			'description' => 'Here you can insert a transaction prefix. The prefix allows you to change the transaction number that is transmitted to Opayo. The prefix must contain the tag {id}. It will then be replaced by the order number (e.g. name_{id}).',
 			'defaultValue' => 'order_{id}',
 			'allowedFileExtensions' => array(
			),
 		),
 		'send_basket' => array(
			'id' => 'sagepay-send-basket-setting',
 			'machineName' => 'send_basket',
 			'type' => 'select',
 			'label' => 'Basket',
 			'description' => 'During the checkout the basket can be sent to Opayo. It can be sent as XML, Basic.',
 			'defaultValue' => 'none',
 			'allowedFileExtensions' => array(
			),
 		),
 		'gift_aid' => array(
			'id' => 'sagepay-gift-aid-setting',
 			'machineName' => 'gift_aid',
 			'type' => 'select',
 			'label' => 'Gift Aid',
 			'description' => 'By enabling the gife aid option the customer can ticke a box during the checkout process to indicate she or he wish to donate the tax.This option requires that the your Opayo account has enabled the gift aid option.',
 			'defaultValue' => 'disabled',
 			'allowedFileExtensions' => array(
			),
 		),
 		'T3M' => array(
			'id' => 'sagepay-t3m-setting',
 			'machineName' => 'T3M',
 			'type' => 'select',
 			'label' => 'The 3rd Man',
 			'description' => 'Should results from The 3rd Man fraud screening be polled and saved on the transaction?',
 			'defaultValue' => 'off',
 			'allowedFileExtensions' => array(
			),
 		),
 		'username' => array(
			'id' => 'sagepay-user-name-setting',
 			'machineName' => 'username',
 			'type' => 'textfield',
 			'label' => 'Username',
 			'description' => 'The username used for administrative requests.',
 			'defaultValue' => '',
 			'allowedFileExtensions' => array(
			),
 		),
 		'password' => array(
			'id' => 'sagepay-password-setting',
 			'machineName' => 'password',
 			'type' => 'password',
 			'label' => 'Password',
 			'description' => 'The password used for administrative requests.',
 			'defaultValue' => '',
 			'allowedFileExtensions' => array(
			),
 		),
 		'threed_version' => array(
			'id' => 'sagepay-3d-2-setting',
 			'machineName' => 'threed_version',
 			'type' => 'select',
 			'label' => '3D Version',
 			'description' => 'Should we request 3D v1 or 3D v2? Please contact Opayo support directly for information on which to configure.',
 			'defaultValue' => 'v1',
 			'allowedFileExtensions' => array(
			),
 		),
 		'review_input_form' => array(
			'id' => 'woocommerce-input-form-in-review-pane-setting',
 			'machineName' => 'review_input_form',
 			'type' => 'select',
 			'label' => 'Review Input Form',
 			'description' => 'Should the input form for credit card data rendered in the review pane? To work the user must have JavaScript activated. In case the browser does not support JavaScript a fallback is provided. This feature is not supported by all payment methods.',
 			'defaultValue' => 'active',
 			'allowedFileExtensions' => array(
			),
 		),
 		'order_identifier' => array(
			'id' => 'woocommerce-order-number-setting',
 			'machineName' => 'order_identifier',
 			'type' => 'select',
 			'label' => 'Order Identifier',
 			'description' => 'Set which identifier should be sent to the payment service provider. If a plugin modifies the order number and can not guarantee it\'s uniqueness, select Post Id.',
 			'defaultValue' => 'ordernumber',
 			'allowedFileExtensions' => array(
			),
 		),
 		'log_level' => array(
			'id' => '',
 			'machineName' => 'log_level',
 			'type' => 'select',
 			'label' => 'Log Level',
 			'description' => 'Messages of this or a higher level will be logged.',
 			'defaultValue' => 'error',
 			'allowedFileExtensions' => array(
			),
 		),
 	);

	
	/**
	 * (non-PHPdoc)
	 * @see Customweb_Payment_IConfigurationAdapter::getConfigurationValue()
	 */
	public function getConfigurationValue($key, $languageCode = null) {
	    if (!isset($this->settingsMap[$key])) {
	        return null;
	    }
		$setting = $this->settingsMap[$key];
		$value =  get_option('woocommerce_sagepaycw_' . $key, $setting['defaultValue']);
		
		if($setting['type'] == 'file') {
			if(isset($value['path']) && file_exists($value['path'])) {
				return new Customweb_Core_Stream_Input_File($value['path']);
			}
			else {
				$resolver = SagePayCw_Util::getAssetResolver();
				return $resolver->resolveAssetStream($setting['defaultValue']);
			}
		}
		else if($setting['type'] == 'multiselect') {
			if(empty($value)){
				return array();
			}
		}
		return $value;
	}
		
	public function existsConfiguration($key, $languageCode = null) {
	    if (!isset($this->settingsMap[$key])) {
	        return false;
	    }
		if ($languageCode !== null) {
			$languageCode = (string)$languageCode;
		}
		$value = get_option('woocommerce_sagepaycw_' . $key, null);
		if ($value === null) {
			return false;
		}
		else {
			return true;
		}
	}
	
	
}