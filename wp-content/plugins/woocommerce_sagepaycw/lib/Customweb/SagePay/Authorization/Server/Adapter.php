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
require_once 'Customweb/SagePay/Authorization/AbstractAdapter.php';
require_once 'Customweb/SagePay/Method/Factory.php';
require_once 'Customweb/SagePay/Util.php';
require_once 'Customweb/SagePay/Authorization/Server/ParameterBuilder.php';
require_once 'Customweb/SagePay/Authorization/Server/PayPalCallbackParameterBuilder.php';
require_once 'Customweb/I18n/Translation.php';

/**
 * @Bean
 */
class Customweb_SagePay_Authorization_Server_Adapter extends Customweb_SagePay_Authorization_AbstractAdapter implements 
		Customweb_Payment_Authorization_Server_IAdapter {
	const DIRECT_REGISTRATION_FILE_PATH = 'vspdirect-register.vsp';
	const PAYPAL_COMPLETE_FILE_PATH = 'complete.vsp';

	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function getAdapterPriority(){
		return 200;
	}

	public function createTransaction(Customweb_Payment_Authorization_Server_ITransactionContext $transactionContext, $failedTransaction){
		$this->checkRecurring($transactionContext);
		$transaction = new Customweb_SagePay_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext){
		return Customweb_SagePay_Method_Factory::getMethod($orderContext->getPaymentMethod(), $this->container)->getFormFields(
				$orderContext, $aliasTransaction, $failedTransaction, self::AUTHORIZATION_METHOD_NAME);
	}

	public function isAuthorizationMethodSupported(Customweb_Payment_Authorization_IOrderContext $orderContext){
		return Customweb_SagePay_Method_Factory::getMethod($orderContext->getPaymentMethod(), $this->container)->isAuthorizationMethodSupported(
				self::AUTHORIZATION_METHOD_NAME);
	}

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		try {
			$builder = new Customweb_SagePay_Authorization_Server_ParameterBuilder($transaction, $this->getConfiguration(), $parameters,
					$this->container);
			$requestParameters = $builder->buildParameters();
			$response = Customweb_SagePay_Util::sendRequest($this->getDirectUrl(), $requestParameters);

			if (isset($requestParameters['CardNumber'])) {
				$response['Last4Digits'] = substr($requestParameters['CardNumber'], -4, 4);
			}
			if (isset($requestParameters['CardType'])) {
				$response['CardType'] = $requestParameters['CardType'];
			}
			if (isset($requestParameters['ExpiryDate'])) {
				$response['ExpiryDate'] = $requestParameters['ExpiryDate'];
			}
			if (isset($requestParameters['CardHolder'])) {
				$response['CardHolder'] = $requestParameters['CardHolder'];
			}
			if (isset($requestParameters['VendorTxCode'])) {
				$response['VendorTxCode'] = $requestParameters['VendorTxCode'];
			}
			if (isset($requestParameters['TxType'])) {
				$response['TxType'] = $requestParameters['TxType'];
			}

			$responseToStore = $response;
			unset($responseToStore['PAReq']);
			unset($responseToStore['ACSURL']);
			unset($responseToStore['CReq']);

			if (isset($response['SecurityKey'])) {
				$transaction->setSecurityKey($response['SecurityKey']);
			}
			$transaction->setAuthorizationParameters($responseToStore);

			// Check whether a 3D secure redirection is required or not.
			if ($response['Status'] == '3DAUTH') {
				global $threeDRedirectionParameters;
				if (isset($response['PAReq']) && isset($response['MD'])) {
					$threeDRedirectionParameters['PaReq'] = $response['PAReq'];
					$threeDRedirectionParameters['ACSURL'] = $response['ACSURL'];
					$threeDRedirectionParameters['MD'] = $response['MD'];
					$transaction->set3DTransactionIdentifier($response['MD']);
				}
				else if (isset($response['CReq']) && isset($response['VPSTxId'])) {
					$threeDRedirectionParameters['creq'] = $response['CReq'];
					$threeDRedirectionParameters['ACSURL'] = $response['ACSURL'];
					$threeDRedirectionParameters['threeDSSessionData'] = $response['VPSTxId'];
					$transaction->set3DTransactionIdentifier($response['VPSTxId']);
				}
			}

			// Check if it is a PayPal transaction and we need to redirect the user
			else if ($response['Status'] == 'PPREDIRECT') {
				// Nothing to do
			}

			else {
				$this->processResponse($transaction);
			}
		}
		catch (Exception $e) {
			$transaction->setAuthorizationFailed($e->getMessage());
		}
		return $this->finalizeAuthorizationRequest($transaction);
	}

	public function processPayPalCallback(Customweb_SagePay_Authorization_Transaction $transaction, array $parameters){
		if ($parameters['Status'] == 'PAYPALOK') {

			// Check whether seller protection is eligible or not
			if ($parameters['AddressStatus'] != 'CONFIRMED' || $parameters['PayerStatus'] != 'VERIFIED') {
				$sellerProtectionBehavior = $transaction->getPaymentMethod()->getPaymentMethodConfigurationValue('seller_protection');

				if (strtolower($sellerProtectionBehavior) == 'uncertain') {
					$transaction->setAuthorizationUncertain();
				}
				else if (strtolower($sellerProtectionBehavior) == 'cancel') {
					$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__("The address or the PayPal account could not be verified."));
					return;
				}
			}

			$builder = new Customweb_SagePay_Authorization_Server_PayPalCallbackParameterBuilder($transaction, $this->getConfiguration(),
					$this->container);
			if ($transaction->isAuthorizationFailed()) {
				$callbackParameters = $builder->buildParameters(false);
			}
			else {
				$callbackParameters = $builder->buildParameters(true);
			}
			$response = Customweb_SagePay_Util::sendRequest($this->getPayPalCallbackUrl(), $callbackParameters);
			$this->mergeResponseWithAuthorizationParameters($transaction, $parameters);
			$this->mergeResponseWithAuthorizationParameters($transaction, $response);
			$this->processResponse($transaction);
		}
		elseif ($parameters['Status'] == 'ERROR') {
			$message = Customweb_I18n_Translation::__('Error processing payment');
			if (isset($parameters['StatusDetail'])) {
				$message = $parameters['StatusDetail'];
			}
			$this->mergeResponseWithAuthorizationParameters($transaction, $parameters);
			$transaction->setAuthorizationFailed($message);
		}
	}

	public function mergeResponseWithAuthorizationParameters(Customweb_SagePay_Authorization_Transaction $transaction, array $response){
		// Merge the previous authorization parameters with the current ones
		$previousAuthorizationParams = $transaction->getAuthorizationParameters();
		foreach ($previousAuthorizationParams as $key => $value) {
			if (!isset($response[$key]) || empty($response[$key])) {
				$response[$key] = $value;
			}
		}
		$transaction->setAuthorizationParameters($response);
	}

	public function finalizeAuthorizationRequest(Customweb_SagePay_Authorization_Transaction $transaction){
		global $threeDRedirectionParameters;
		$response = new Customweb_Core_Http_Response();

		if ($transaction->isAuthorizationFailed()) {
			$response->setLocation($transaction->getFailedUrl());
			return $response;
		}

		if ($transaction->isAuthorized()) {
			$response->setLocation($transaction->getSuccessUrl());
			return $response;
		}

		if ($transaction->getPayPalRedirectionUrl() !== NULL) {
			$response->setLocation($transaction->getPayPalRedirectionUrl());
			return $response;
		}

		if ($transaction->is3DTransaction()) {
			$response = new Customweb_Core_Http_Response();


			$dataParameterName = isset($threeDRedirectionParameters['PaReq']) ? 'PaReq' : 'creq';
			$dataParameterValue = $threeDRedirectionParameters[$dataParameterName];

			$idParameterName = isset($threeDRedirectionParameters['MD']) ? 'MD' : 'threeDSSessionData';
			$idParameterValue = $threeDRedirectionParameters[$idParameterName];
			
			$termUrl = '';
			if($dataParameterName == 'PaReq') {
				$url = $this->container->getBean('Customweb_Payment_Endpoint_IAdapter')->getUrl("process", "server",
						array(
							'cw_transaction_id' => $transaction->getExternalTransactionId()
						));
				$termUrl = '<input type="hidden" name="TermUrl" value="' . $url  . '" />';
			}

			//@formatter:off
			$body = '<html><body>' .
				'<form name="threedredirection" action="' . $threeDRedirectionParameters['ACSURL'] . '" method="POST">' .
					'<input type="hidden" name="' . $dataParameterName . '" value="' . $dataParameterValue . '" />' .
					'<input type="hidden" name="'. $idParameterName.'" value="' . $idParameterValue . '" />' .
					$termUrl .
					'<noscript>' .
						'<p>' . Customweb_I18n_Translation::__('Please click button below to authenticate your card.') . '</p>' .
						'<input type="submit" name="complete" value="' . Customweb_I18n_Translation::__('Continue') . '" />' .
					'</noscript>' .
				'</form>' .
				'<script type="text/javascript"> ' . "\n" .
					' document.threedredirection.submit(); ' . "\n" .
				'</script>' .
			'</body></html>';
			//@formatter:on

			$response->setBody($body);
			return $response;
		}

		$notify = $this->container->getBean('Customweb_Payment_Endpoint_IAdapter')->getUrl("process", "server",
				array(
					'cw_transaction_id' => $transaction->getExternalTransactionId()
				));
		$response->setLocation($notify);
		return $response;
	}

	protected function getDirectUrl(){
		return $this->getBaseUrl() . self::DIRECT_REGISTRATION_FILE_PATH;
	}

	protected function getPayPalCallbackUrl(){
		return $this->getBaseUrl() . self::PAYPAL_COMPLETE_FILE_PATH;
	}
}