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

require_once 'Customweb/Payment/Authorization/Recurring/IAdapter.php';
require_once 'Customweb/SagePay/Method/Factory.php';
require_once 'Customweb/SagePay/Authorization/AbstractAdapter.php';
require_once 'Customweb/SagePay/Authorization/Recurring/ParameterBuilder.php';


/**
 * @Bean
 */
class Customweb_SagePay_Authorization_Recurring_Adapter extends Customweb_SagePay_Authorization_AbstractAdapter implements Customweb_Payment_Authorization_Recurring_IAdapter
{
	const RECURRING_REGISTRATION_FILE_PATH = 'vspdirect-register.vsp';

	public function getAdapterPriority() {
		return 1000;
	}

	public function getAuthorizationMethodName() {
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function isPaymentMethodSupportingRecurring(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod) {
		$wrappedPaymentMethod = Customweb_SagePay_Method_Factory::getMethod($paymentMethod, $this->container);
		return $wrappedPaymentMethod->isRecurringPaymentSupported();
	}

	public function createTransaction(Customweb_Payment_Authorization_Recurring_ITransactionContext $transactionContext) {
		$transaction = new Customweb_SagePay_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}

	public function process(Customweb_Payment_Authorization_ITransaction $transaction) {
		try {
			$parameterBuilder = new Customweb_SagePay_Authorization_Recurring_ParameterBuilder($transaction, $this->getConfiguration(), $this->container, array());
			$parameters = $parameterBuilder->buildParameters();
			$response = Customweb_SagePay_Util::sendRequest($this->getRecurringUrl(), $parameters);

			// Store the relevant request parameters
			if (isset($parameters['CardType'])) {
				$response['CardType'] = $parameters['CardType'];
			}
			if (isset($parameters['ExpiryDate'])) {
				$response['ExpiryDate'] = $parameters['ExpiryDate'];
			}
			if (isset($parameters['CardHolder'])) {
				$response['CardHolder'] = $parameters['CardHolder'];
			}
			if (isset($parameters['VendorTxCode'])) {
				$response['VendorTxCode'] = $parameters['VendorTxCode'];
			}
			if (isset($parameters['TxType'])) {
				$response['TxType'] = $parameters['TxType'];
			}

			$transaction->setAuthorizationParameters($response);
			$this->processResponse($transaction);

			if ($transaction->isAuthorizationFailed()) {
				$errorMessages = $transaction->getErrorMessages();
				throw new Customweb_Payment_Exception_RecurringPaymentErrorException((string)current($errorMessages));
			}
		} catch(Exception $e) {
			throw new Customweb_Payment_Exception_RecurringPaymentErrorException($e);
		}
	}

	protected function getRecurringUrl() {
		return $this->getBaseUrl() . self::RECURRING_REGISTRATION_FILE_PATH;
	}

}