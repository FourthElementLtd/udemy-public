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
require_once 'Customweb/SagePay/AbstractAdapter.php';
require_once 'Customweb/SagePay/Method/Factory.php';

abstract class Customweb_SagePay_Authorization_AbstractAdapter extends Customweb_SagePay_AbstractAdapter {

	public function preValidate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		$paymentMethod = Customweb_SagePay_Method_Factory::getMethod($orderContext->getPaymentMethod(), $this->container);
		$paymentMethod->preValidate($orderContext, $paymentContext);
	}

	public function validate(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext, array $formData){}

	public function isDeferredCapturingSupported(Customweb_Payment_Authorization_IOrderContext $orderContext, Customweb_Payment_Authorization_IPaymentCustomerContext $paymentContext){
		return $orderContext->getPaymentMethod()->existsPaymentMethodConfigurationValue('capturing');
	}

	public function isAuthorizationMethodSupported(Customweb_Payment_Authorization_IOrderContext $orderContext){
		$paymentMethod = Customweb_SagePay_Method_Factory::getMethod($orderContext->getPaymentMethod(), $this->container);
		return $paymentMethod->isAuthorizationMethodSupported($this->getAuthorizationMethodName());
	}

	protected function checkRecurring(Customweb_Payment_Authorization_Server_ITransactionContext $transactionContext){
		if ($transactionContext->createRecurringAlias()) {
			if (!Customweb_SagePay_Method_Factory::getMethod($transactionContext->getOrderContext()->getPaymentMethod(),
					$this->container)->isRecurringPaymentSupported()) {
				throw new Exception(
						Customweb_I18n_Translation::__("The payment method !paymentMethod does not support recurring payment.",
								array(
									'!paymentMethod' => $transactionContext->getOrderContext()->getPaymentMethod()->getPaymentMethodName() 
								)));
			}
		}
	}

	/**
	 * This method processes the given response.
	 *
	 *
	 * @param Customweb_SagePay_Authorization_Transaction $transaction
	 * @param array $parameters
	 */
	public function processResponse(Customweb_SagePay_Authorization_Transaction $transaction){
		$parameters = $transaction->getAuthorizationParameters();
		
		$status = strtoupper($parameters['Status']);
		
		if ($status == 'OK' || $status == 'AUTHENTICATED' || $status == 'REGISTERED' || $status == 'PAYPALOK ') {
			if (isset($parameters['SecurityKey'])) {
				$transaction->setSecurityKey($parameters['SecurityKey']);
			}
			$transaction->setPaymentId($parameters['VPSTxId']);
			$transaction->authorize();
			
			$paymentMethod = Customweb_SagePay_Method_Factory::getMethod(
					$transaction->getTransactionContext()->getOrderContext()->getPaymentMethod(), $this->container);
			
			if (strtoupper($parameters['TxType']) == 'PAYMENT' && $transaction->isOneStepCapture()) {
				$captureItem = $transaction->capture();
				$captureItem->setExternalTransactionId($parameters['VPSTxId']);
				if (isset($parameters['TxAuthNo'])) {
					$captureItem->setTransactionAuthenticationNumber($parameters['TxAuthNo']);
				}
				if (isset($parameters['SecurityKey'])) {
					$captureItem->setSecurityKey($parameters['SecurityKey']);
				}
				else {
					$captureItem->setSecurityKey($transaction->getSecurityKey());
				}
				$captureItem->setVendorTransactionCode($parameters['VendorTxCode']);
			}
			else {
				if ($transaction->getTransactionContext()->getCapturingMode() == null) {
					$capturingMode = $paymentMethod->getPaymentMethodConfigurationValue('capturing');
				}
				else {
					$capturingMode = $this->getTransactionContext()->getCapturingMode();
				}
				if (strtolower($capturingMode) == 'direct') {
					
					/* @var Customweb_SagePay_BackendOperation_Adapter_CaptureAdapter */
					$captureAdapter = $this->container->getBean('Customweb_SagePay_BackendOperation_Adapter_CaptureAdapter');
					try {
						$captureAdapter->capture($transaction);
					}
					catch (Exception $e) {
						Customweb_Core_Logger_Factory::getLogger(get_class($this))->logException($e);
						$transaction->addHistoryItem(
								new Customweb_Payment_Authorization_DefaultTransactionHistoryItem(
										Customweb_I18n_Translation::__("Error capturing the transaction."),
										Customweb_Payment_Authorization_ITransactionHistoryItem::ACTION_CAPTURING));
					}
				}
			}
			
			if(!isset($parameters['CardType']) || $parameters['CardType'] != 'PAYPAL'){
				// Check Address and Post Code
				if ($transaction->getPaymentMethod()->existsPaymentMethodConfigurationValue("address_check_behavior") &&
						 $transaction->getAuthorizationMethod() != Customweb_Payment_Authorization_Recurring_IAdapter::AUTHORIZATION_METHOD_NAME &&
						 !$transaction->isMoto()) {
					$uncertainAddressStates = $transaction->getPaymentMethod()->getPaymentMethodConfigurationValue("address_check_behavior");
					if (isset($parameters['AddressResult'])) {
						if (is_array($uncertainAddressStates) && in_array(strtoupper($parameters['AddressResult']), $uncertainAddressStates)) {
							$transaction->setAuthorizationUncertain();
						}
					}
					if (isset($parameters['PostCodeResult'])) {
						if (is_array($uncertainAddressStates) && in_array(strtoupper($parameters['PostCodeResult']), $uncertainAddressStates)) {
							$transaction->setAuthorizationUncertain();
						}
					}
				}
				
				// Check CV2
				if (isset($parameters['CV2Result']) && $transaction->getPaymentMethod()->existsPaymentMethodConfigurationValue("cv2_check_behavior") &&
						 $transaction->getAuthorizationMethod() != Customweb_Payment_Authorization_Recurring_IAdapter::AUTHORIZATION_METHOD_NAME &&
						 !$transaction->isMoto()) {
					$uncertainCV2States = $transaction->getPaymentMethod()->getPaymentMethodConfigurationValue("cv2_check_behavior");
					if (is_array($uncertainCV2States) && in_array(strtoupper($parameters['CV2Result']), $uncertainCV2States)) {
						$transaction->setAuthorizationUncertain();
					}
				}
				
				// 3D secure check (Not applied on MoTo transactions)
				if (isset($parameters['3DSecureStatus']) && !$transaction->isMoto() &&
						 $transaction->getAuthorizationMethod() != Customweb_Payment_Authorization_Recurring_IAdapter::AUTHORIZATION_METHOD_NAME &&
						 $transaction->getPaymentMethod()->existsPaymentMethodConfigurationValue("three_d_secure_behavior")) {
					$uncertainCV2States = $transaction->getPaymentMethod()->getPaymentMethodConfigurationValue("three_d_secure_behavior");
					$result = strtoupper($parameters['3DSecureStatus']);
					if ($result == 'NOTAUTHED' || $result == 'INCOMPLETE' || $result == 'ERROR' || $result == 'ATTEMPT ONLY') {
						$result = 'authentication_failed';
					}
					
					if (in_array($result, $uncertainCV2States)) {
						$transaction->setAuthorizationUncertain();
					}
					
					if (strtoupper($parameters['3DSecureStatus']) == 'OK' || strtoupper($parameters['3DSecureStatus']) == 'NOTAVAILABLE') {
						$transaction->setState3DSecure(Customweb_SagePay_Authorization_Transaction::STATE_3D_SECURE_SUCCESS);
					}
					else {
						$transaction->setState3DSecure(Customweb_SagePay_Authorization_Transaction::STATE_3D_SECURE_FAILED);
					}
				}
			}
			
			if($parameters['CardType'] == 'PAYPAL'){
				if (($parameters['AddressStatus'] != 'CONFIRMED' || $parameters['PayerStatus'] != 'VERIFIED') && $paymentMethod->existsPaymentMethodConfigurationValue('seller_protection')) {
					$sellerProtectionBehavior = $paymentMethod->getPaymentMethodConfigurationValue('seller_protection');
					if (strtolower($sellerProtectionBehavior) == 'uncertain') {
						$transaction->setAuthorizationUncertain();
					}
				}
			}
			
			// If ReD does deny the transaction, we always mark it as uncertain
			if (isset($parameters['FraudResponse']) && $transaction->getPaymentMethod()->existsPaymentMethodConfigurationValue("fraud_behavior")) {
				$uncertainFraudState = $transaction->getPaymentMethod()->getPaymentMethodConfigurationValue("fraud_behavior");
				if (is_array($uncertainFraudState) && in_array(strtoupper($parameters['FraudResponse']), $uncertainFraudState)) {
					$transaction->setAuthorizationUncertain();
				}
			}
			
			// When the 3rd man is active, we schedule the transaction to updated.
			if ($this->getConfiguration()->isThirdManEnabled() && $transaction->isAuthorized()) {
				$transaction->setUpdateExecutionDate(Customweb_Core_DateTime::_()->addMinutes(15));
			}
			
			if (isset($parameters['Token'])) {
				$transaction->setAliasForDisplay($transaction->extractDisplayNameForToken());
			}
		}
		else {
			$transaction->setAuthorizationFailed($parameters['StatusDetail']);
		}
		$transaction->setClearStorage(true);
		$transaction->setUpdateExecutionDate(Customweb_Core_DateTime::_()->addMinutes(15));
	}

	protected function getGiftAidElement(){
		$control = new Customweb_Form_Control_SingleCheckbox("gift_aid", "active",
				Customweb_I18n_Translation::__("Yes, I want to donate the tax to a charity."));
		$element = new Customweb_Form_Element(Customweb_I18n_Translation::__("Tax Donation"), $control);
		return $element;
	}
}