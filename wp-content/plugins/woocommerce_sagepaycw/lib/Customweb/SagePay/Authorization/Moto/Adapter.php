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

require_once 'Customweb/SagePay/Authorization/AbstractAdapter.php';
require_once 'Customweb/SagePay/Authorization/Iframe/Adapter.php';
require_once 'Customweb/SagePay/Authorization/PaymentPage/Adapter.php';
require_once 'Customweb/SagePay/Authorization/Server/Adapter.php';
require_once 'Customweb/Payment/Authorization/Moto/IAdapter.php';


/**
 * @Bean
 */
class Customweb_SagePay_Authorization_Moto_Adapter extends Customweb_SagePay_Authorization_AbstractAdapter implements Customweb_Payment_Authorization_Moto_IAdapter{
	
	public function getAuthorizationMethodName() {
		return self::AUTHORIZATION_METHOD_NAME;
	}
	
	public function getAdapterPriority() {
		return 1000;
	}
	
	public function createTransaction(Customweb_Payment_Authorization_Moto_ITransactionContext $transactionContext, $failedTransaction){
		$this->checkRecurring($transactionContext);
		$transaction = new Customweb_SagePay_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}
	
	
	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext) {
		$adapter = $this->getAdapterInstanceByPaymentMethod($orderContext->getPaymentMethod());
		
		if ($adapter instanceof Customweb_SagePay_Authorization_Server_Adapter) {
			return Customweb_SagePay_Method_Factory::getMethod($orderContext->getPaymentMethod(), $this->container)->getFormFields($orderContext, $aliasTransaction, $failedTransaction, self::AUTHORIZATION_METHOD_NAME);
		}
		else {
			return $this->getAdapterInstanceByPaymentMethod($orderContext->getPaymentMethod())->getVisibleFormFields($orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext);
		}		
	}
	
	public function getFormActionUrl(Customweb_Payment_Authorization_ITransaction $transaction) {
		return $this->container->getBean('Customweb_Payment_Endpoint_IAdapter')->getUrl("process", "moto", array('cw_transaction_id' => $transaction->getExternalTransactionId()));
	}
	
	public function getParameters(Customweb_Payment_Authorization_ITransaction $transaction) {
		return array();
	}

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters) {
		$adapter = $this->getAdapterInstanceByPaymentMethod($transaction->getTransactionContext()->getOrderContext()->getPaymentMethod());
		
		if ($adapter instanceof Customweb_SagePay_Authorization_Server_Adapter) {
			$rs = $adapter->processAuthorization($transaction, $parameters);
		}
		else {
			if ($transaction->isRedirected()) {
				$rs = $adapter->processAuthorization($transaction, $parameters);
			}
			else {
				try {
					$transaction->setRedirected();
					$rs = Customweb_Core_Http_Response::redirect($adapter->getRedirectionUrl($transaction, array()));
				} catch(Exception $e) {
					$transaction->setAuthorizationFailed($e->getMessage());
				}
			}
		}
		
		if ($transaction->isAuthorized() || $transaction->isAuthorizationFailed()) {
			if ($transaction->isAuthorized()) {
				$url = Customweb_Util_Url::appendParameters(
						$transaction->getTransactionContext()->getBackendSuccessUrl(),
						$transaction->getTransactionContext()->getCustomParameters()
				);
			}
			else {
				$url = Customweb_Util_Url::appendParameters(
					$transaction->getTransactionContext()->getBackendFailedUrl(),
					$transaction->getTransactionContext()->getCustomParameters()
				);
			}
			
			if($transaction->isOneStepCapture()){
				$parameters = array();
				$parameters['Status'] = 'OK';
				$parameters['RedirectURL'] = $url;
				return Customweb_Core_Http_Response::_(Customweb_SagePay_Util::parseToCRLF($parameters));
			}

			return Customweb_Core_Http_Response::redirect($url);
			
		}
		else {
			return $rs;
		}
	}
	
	protected function getAdapterInstanceByPaymentMethod(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod) {
		$configuredAuthorizationMethod = $paymentMethod->getPaymentMethodConfigurationValue('authorizationMethod');
		switch (strtolower($configuredAuthorizationMethod)) {
			case strtolower(Customweb_SagePay_Authorization_Server_Adapter::AUTHORIZATION_METHOD_NAME):
				return new Customweb_SagePay_Authorization_Server_Adapter($this->getConfiguration()->getConfigurationAdapter(), $this->container);
			
			case strtolower(Customweb_SagePay_Authorization_Iframe_Adapter::AUTHORIZATION_METHOD_NAME):
			case strtolower(Customweb_SagePay_Authorization_PaymentPage_Adapter::AUTHORIZATION_METHOD_NAME):
				return new Customweb_SagePay_Authorization_PaymentPage_Adapter($this->getConfiguration()->getConfigurationAdapter(), $this->container);
			default:
				throw new Exception(Customweb_I18n_Translation::__("Could not find an adapter for the authoriztion method !methodName.", array('!methodName' => $configuredAuthorizationMethod)));
		}
	}
	
}