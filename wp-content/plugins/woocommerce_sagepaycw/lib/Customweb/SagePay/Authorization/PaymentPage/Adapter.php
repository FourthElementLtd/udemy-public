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

require_once 'Customweb/Payment/Authorization/PaymentPage/IAdapter.php';
require_once 'Customweb/Http/Request.php';

require_once 'Customweb/SagePay/Configuration.php';
require_once 'Customweb/SagePay/Authorization/Transaction.php';
require_once 'Customweb/SagePay/Authorization/AbstractRedirectionAdapter.php';
require_once 'Customweb/SagePay/Authorization/PaymentPage/ParameterBuilder.php';
require_once 'Customweb/SagePay/Util.php';

/**
 * @Bean
 */
class Customweb_SagePay_Authorization_PaymentPage_Adapter extends Customweb_SagePay_Authorization_AbstractRedirectionAdapter
implements Customweb_Payment_Authorization_PaymentPage_IAdapter {
	
	public function getAdapterPriority() {
		return 0;
	}
	
	public function getAuthorizationMethodName() {
		return self::AUTHORIZATION_METHOD_NAME;
	}
	
	public function createTransaction(Customweb_Payment_Authorization_PaymentPage_ITransactionContext $transactionContext, $failedTransaction){
		$this->checkRecurring($transactionContext);
		$transaction = new Customweb_SagePay_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}

	public function getRedirectionUrl(Customweb_Payment_Authorization_ITransaction $transaction, array $formData){
		$builder = new Customweb_SagePay_Authorization_PaymentPage_ParameterBuilder($transaction, $this->getConfiguration(), $this->container, $formData);
		$parameters = $builder->buildParameters();
		
		return $this->getPaymentPageUrl($transaction, $parameters);
	}
	
	public function isHeaderRedirectionSupported(Customweb_Payment_Authorization_ITransaction $transaction, array $formData) {
		return true;
	}

	public function getParameters(Customweb_Payment_Authorization_ITransaction $transaction, array $formData) {
		return array();
	}

	public function getFormActionUrl(Customweb_Payment_Authorization_ITransaction $transaction, array $formData) {
		return $this->getRedirectionUrl($transaction, $formData);
	}

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters) {
		
		/* @var $transaction Customweb_SagePay_Authorization_Transaction */
		
		// In some cases the notification is executed more than once.
		if ($transaction->isAuthorizationFailed() || $transaction->isAuthorized()) {
			return $this->finalizeAuthorizationRequest($transaction);
		}
		$transaction->setAuthorizationParameters($parameters);
		try {
			$this->validateNotification($parameters, $transaction);				
		}
		catch(Exception $e) {
			$transaction->setAuthorizationFailed($e->getMessage());
			return 'redirect:' . $transaction->getFailedUrl();
		}
		
		$this->processResponse($transaction);
		
		return $this->finalizeAuthorizationRequest($transaction);
	}

	public function finalizeAuthorizationRequest(Customweb_SagePay_Authorization_Transaction $transaction) {
		if ($transaction->isAuthorized()) {
			$url = $transaction->getSuccessUrl();
		}
		else {
			$url = $transaction->getFailedUrl();
		}
		if($transaction->isOneStepCapture()){
			$parameters = array();
			$parameters['Status'] = 'OK';
			// TODO: Check the length of the $url (max 255 chars)
			$parameters['RedirectURL'] = $url;
			
			return Customweb_Core_Http_Response::_(Customweb_SagePay_Util::parseToCRLF($parameters));
		}
		
		return Customweb_Core_Http_Response::redirect($url);
	}
}