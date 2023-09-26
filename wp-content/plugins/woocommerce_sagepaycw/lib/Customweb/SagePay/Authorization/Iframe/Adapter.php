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

require_once 'Customweb/Payment/Authorization/Server/IAdapter.php';

require_once 'Customweb/SagePay/Configuration.php';
require_once 'Customweb/SagePay/Authorization/Transaction.php';
require_once 'Customweb/SagePay/Authorization/AbstractRedirectionAdapter.php';

require_once 'Customweb/SagePay/Authorization/Iframe/ParameterBuilder.php';
require_once 'Customweb/SagePay/Util.php';
require_once 'Customweb/Payment/Authorization/Iframe/IAdapter.php';


/**
 * @Bean
 */
class Customweb_SagePay_Authorization_Iframe_Adapter extends Customweb_SagePay_Authorization_AbstractRedirectionAdapter
implements Customweb_Payment_Authorization_Iframe_IAdapter {
	
	public function getAdapterPriority() {
		return 100;
	}
	
	public function getAuthorizationMethodName() {
		return self::AUTHORIZATION_METHOD_NAME;
	}
	
	public function createTransaction(Customweb_Payment_Authorization_Iframe_ITransactionContext $transactionContext, $failedTransaction){
		$this->checkRecurring($transactionContext);
		$transaction = new Customweb_SagePay_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}
	
	public function getIframeUrl(Customweb_Payment_Authorization_ITransaction $transaction, array $formData) {
		$builder = new Customweb_SagePay_Authorization_Iframe_ParameterBuilder($transaction, $this->getConfiguration(), $this->container, $formData);
		$parameters = $builder->buildParameters();
		$url = $this->getPaymentPageUrl($transaction, $parameters);
		
		
		if($transaction->isAuthorizationFailed()){
			return Customweb_Util_Url::appendParameters(
				$transaction->getTransactionContext()->getIframeBreakOutUrl(),
				$transaction->getTransactionContext()->getCustomParameters()
			);
		}
		
		return $url;
	}
	
	
	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
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
	
	public function finalizeAuthorizationRequest(Customweb_Payment_Authorization_ITransaction $transaction){
	
			
		$url = Customweb_Util_Url::appendParameters(
			$transaction->getTransactionContext()->getIframeBreakOutUrl(),
			$transaction->getTransactionContext()->getCustomParameters()
		);
		if($transaction ->isOneStepCapture()){
			$response = new Customweb_Core_Http_Response();
			
			$parameters = array();
			$parameters['Status'] = 'OK';

			$parameters['RedirectURL'] = $url;
			
			$response->setBody(Customweb_SagePay_Util::parseToCRLF($parameters));
			return $response;
			
			
		}
		return Customweb_Core_Http_Response::redirect($url);
	}
	
	public function getIframeHeight(Customweb_Payment_Authorization_ITransaction $transaction, array $formData) {
		$paymentMethod = Customweb_SagePay_Method_Factory::getMethod($transaction->getPaymentMethod(), $this->container);
		return $paymentMethod->getIframeHeight();
	}

}