<?php
/**
 *  * You are allowed to use this API in your web application.
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
require_once 'Customweb/SagePay/AbstractParameterBuilder.php';
require_once 'Customweb/SagePay/Authorization/Basket.php';

class Customweb_SagePay_Authorization_AbstractParameterBuilder extends Customweb_SagePay_AbstractParameterBuilder {
	protected $container = null;
	private $formData;

	public function __construct(Customweb_SagePay_Authorization_Transaction $transaction, Customweb_SagePay_Configuration $configuration, Customweb_DependencyInjection_IContainer $container, array $formData){
		parent::__construct($transaction, $configuration);
		$this->container = $container;
		$this->formData = $formData;
	}

	protected function getFormData(){
		return $this->formData;
	}

	protected function getCustomerAddressParameters(){
		$context = $this->getOrderContext();
		
		// Add billing parameters
		$parameters = array(
			'BillingSurname' => Customweb_Util_String::substrUtf8($context->getBillingLastName(), 0, 20),
			'BillingFirstnames' => Customweb_Util_String::substrUtf8($context->getBillingFirstName(), 0, 20),
			'BillingAddress1' => Customweb_Util_String::substrUtf8($context->getBillingStreet(), 0, 100),
			'BillingCity' => Customweb_Util_String::substrUtf8($context->getBillingCity(), 0, 40),
			'BillingPostCode' => Customweb_Util_String::substrUtf8($context->getBillingPostCode(), 0, 10),
			'BillingCountry' => $context->getBillingCountryIsoCode() 
		);
		
		$deliverySurname = trim(Customweb_Util_String::substrUtf8($context->getShippingLastName(), 0, 20));
		$deliveryFirstname = trim(Customweb_Util_String::substrUtf8($context->getShippingFirstName(), 0, 20));
		$deliveryAddress1 = trim(Customweb_Util_String::substrUtf8($context->getShippingStreet(), 0, 100));
		$deliveryCity = trim(Customweb_Util_String::substrUtf8($context->getShippingCity(), 0, 40));
		$deliveryPostCode = trim(Customweb_Util_String::substrUtf8($context->getShippingPostCode(), 0, 10));
		$deliveryCountry = trim($context->getShippingCountryIsoCode());
		
		if (empty($deliverySurname)) {
			$deliverySurname = $parameters['BillingSurname'];
		}
		if (empty($deliveryFirstname)) {
			$deliveryFirstname = $parameters['BillingFirstnames'];
		}
		if (empty($deliveryAddress1)) {
			$deliveryAddress1 = $parameters['BillingAddress1'];
		}
		if (empty($deliveryCity)) {
			$deliveryCity = $parameters['BillingCity'];
		}
		if (empty($deliveryPostCode)) {
			$deliveryPostCode = $parameters['BillingPostCode'];
		}
		if (empty($deliveryCountry)) {
			$deliveryCountry = $parameters['BillingCountry'];
		}
		
		$shippingParameters = array(
			'DeliverySurname' => $deliverySurname,
			'DeliveryFirstnames' => $deliveryFirstname,
			'DeliveryCity' => $deliveryCity,
			'DeliveryAddress1' => $deliveryAddress1,
			'DeliveryPostCode' => $deliveryPostCode,
			'DeliveryCountry' => $deliveryCountry 
		);
		
		if (strtoupper($context->getBillingCountryIsoCode()) == 'US') {
			$stateCode = $context->getBillingState();
			
			if (empty($stateCode)) {
				throw new Exception("No state for billing address provided, for US customer this is required.");
			}
			
			if (strlen($stateCode) > 2) {
				$stateCode = substr($stateCode, -2);
			}
			$parameters['BillingState'] = $stateCode;
		}
		
		if (strtoupper($shippingParameters['DeliveryCountry']) == 'US') {
			$stateCode = $context->getShippingState();
			
			if (empty($stateCode)) {
				if (strtoupper($context->getBillingCountryIsoCode()) == 'US') {
					$stateCode = $context->getBillingState();
				}
				if (empty($stateCode)) {
					throw new Exception("No state for shipping address provided, for US customer this is required.");
				}
			}
			
			if (strlen($stateCode) > 2) {
				$stateCode = substr($stateCode, -2);
			}
			$parameters['DeliveryState'] = $stateCode;
		}
		
		$parameters = array_merge($parameters, $shippingParameters);
		
		// In case we are using the test mode, we override the PostCode and BillingAddress to
		// pass the fraud checks.
		if ($this->getConfiguration()->isTestMode()) {
			$parameters['BillingAddress1'] = '88';
			$parameters['BillingPostCode'] = '412';
		}
		
		$customerMail = $context->getCustomerEMailAddress();
		if (!empty($customerMail)) {
			$parameters['CustomerEMail'] = Customweb_Util_String::substrUtf8($customerMail, 0, 255);
		}
		
		$shippingPhoneNumber = $this->cleanPhoneNumber($context->getShippingAddress()->getPhoneNumber());
		if (empty($shippingPhoneNumber)) {
			$shippingPhoneNumber = $this->cleanPhoneNumber($context->getShippingAddress()->getMobilePhoneNumber());
		}
		if (!empty($shippingPhoneNumber)) {
			$parameters['DeliveryPhone'] = $shippingPhoneNumber;
		}
		
		$billingPhoneNumber = $this->cleanPhoneNumber($context->getBillingAddress()->getPhoneNumber());
		if (empty($billingPhoneNumber)) {
			$billingPhoneNumber = $this->cleanPhoneNumber($context->getBillingAddress()->getMobilePhoneNumber());
		}
		if (!empty($billingPhoneNumber)) {
			$parameters['BillingPhone'] = $billingPhoneNumber;
		}
		
		return $parameters;
	}

	protected function getAccountTypeParameters(){
		$parameters = array();
		
		if ($this->getTransaction()->isMoto()) {
			// Use MoTo account
			$parameters['AccountType'] = 'M';
			
			if (!$this->getConfiguration()->isMotoCvcCheckEnabled()) {
				// Deactivate the CV2 checks
				$parameters['ApplyAVSCV2'] = '2';
			}
		}
		
		return $parameters;
	}

	protected function getGiftAidParameters(){
		$parameters = array();
		$formData = $this->getFormData();
		if ($this->getConfiguration()->isGiftAidEnabled() &&
				 ((isset($formData['gift_aid']) && $formData['gift_aid'] == 'active') || $this->getTransaction()->isGiftAidActive())) {
			$parameters['GiftAidPayment'] = '1';
			$this->getTransaction()->setGiftAidActive(true);
		}
		return $parameters;
	}

	protected function getReferrerParameters(){
		$referrer = 'CW-WOO';
		
		return array(
			'ReferrerID' => Customweb_Util_String::substrUtf8($referrer, 0, 40) 
		);
	}

	protected function getReactionUrlParameters(){
		$this->getProcessingTypeParameters();
		if ($this->getTransaction()->isOneStepCapture()) {
			$notificationUrl = $this->container->getBean('Customweb_Payment_Endpoint_IAdapter')->getUrl("process", "direct",
					array(
						'cw_transaction_id' => $this->getTransaction()->getExternalTransactionId() 
					));
		}
		else {
			$notificationUrl = $this->container->getBean('Customweb_Payment_Endpoint_IAdapter')->getUrl("process", "fast",
					array(
						'cw_transaction_id' => $this->getTransaction()->getExternalTransactionId() 
					));
		}
		return array(
			'NotificationURL' => $notificationUrl 
		);
	}

	protected function getCreateTokenParameters(){
		$parameters = array();
		if ($this->getTransactionContext()->getAlias() !== null && $this->getTransactionContext()->getAlias() != 'new') {
			$parameters['Token'] = $this->getTransactionContext()->getAlias()->getToken();
			$parameters['CreateToken'] = '0';
			$parameters['StoreToken'] = 1;
		}
		else if ($this->getTransactionContext()->getAlias() == 'new' || $this->getTransactionContext()->createRecurringAlias()) {
			$parameters['CreateToken'] = '1';
		}
		else {
			$parameters['CreateToken'] = '0';
		}
		
		return $parameters;
	}

	protected function getLanguageParameters(){
		$language = Customweb_SagePay_Util::getCleanLanguageCode($this->getOrderContext()->getLanguage());
		return array(
			'Language' => $language 
		);
	}

	protected function getProcessingTypeParameters(){
		$paymentMethod = Customweb_SagePay_Method_Factory::getMethod($this->getOrderContext()->getPaymentMethod(),
				$this->container);
		
		if ($this->getTransactionContext()->getCapturingMode() == null) {
			$capturingMode = $paymentMethod->getPaymentMethodConfigurationValue('capturing');
		}
		else {
			$capturingMode = $this->getTransactionContext()->getCapturingMode();
		}
		if (strtolower($capturingMode) == 'direct') {
			if ($this->getConfiguration()->isSingleStepDirectCapture()) {
				$mode = 'PAYMENT';
				$this->getTransaction()->setOneStepCapture(true);
			}
			else {
				$mode = 'DEFERRED';
			}
		}
		else {
			if (strtolower($this->getConfiguration()->getDeferredAuthorizationType()) == 'deferred') {
				$mode = 'DEFERRED';
			}
			else {
				$mode = 'AUTHENTICATE';
			}
		}
		return array(
			'TxType' => $mode 
		);
	}

	protected function getTransactionIdParameters(){
		return array(
			'VendorTxCode' => Customweb_Payment_Util::applyOrderSchema($this->getConfiguration()->getTransactionIdSchema(),
					$this->getTransaction()->getExternalTransactionId(), 35)
		);
	}

	protected function getTransactionAmountParameters(){
		return array(
			'Amount' => Customweb_SagePay_Util::formatAmount($this->getOrderContext()->getOrderAmountInDecimals(),
					$this->getOrderContext()->getCurrencyCode()),
			'Currency' => $this->getOrderContext()->getCurrencyCode() 
		);
	}

	protected function getTransactionDescriptionParameters(){
		$description = $this->getConfiguration()->getTransactionDescription($this->getOrderContext()->getLanguage());
		$description = Customweb_Util_String::substrUtf8($description, 0, 40);
		return array(
			'Description' => $description 
		);
	}

	protected function getBasketParamerters(){
		$basket = new Customweb_SagePay_Authorization_Basket($this->getOrderContext());
		$basketMode = strtolower($this->getConfiguration()->getBasketMode());
		$parameters = array();
		if ($basketMode == 'xml' || $basketMode == 'yes') { //yes for backwards compatibility
			$parameters['BasketXML'] = $basket->getXmlRepresentation();
		}
		if ($basketMode == 'basic') {
			$parameters['Basket'] = $basket->getBasicReperesentation();
		}
		return $parameters;
	}

	protected function cleanPhoneNumber($number){
		$number = trim($number, ' -');
		if (!empty($number)) {
			return Customweb_Core_String::_(preg_replace('/([^\(\)\+0-9A-Z\-[:space:]]+)/i', '', $number))->substring(0, 20)->toString();
		}
		else {
			return null;
		}
	}
}