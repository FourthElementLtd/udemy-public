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

require_once 'Customweb/SagePay/AbstractMaintenanceAdapter.php';
require_once 'Customweb/Payment/BackendOperation/Adapter/Service/ICapture.php';
require_once 'Customweb/Util/Invoice.php';
require_once 'Customweb/SagePay/BackendOperation/Adapter/AuthoriseParameterBuilder.php';
require_once 'Customweb/SagePay/BackendOperation/Adapter/ReleaseParameterBuilder.php';

/**
 * 
 * @author Thomas Hunziker
 * @Bean
 *
 */
class Customweb_SagePay_BackendOperation_Adapter_CaptureAdapter extends Customweb_SagePay_AbstractMaintenanceAdapter implements Customweb_Payment_BackendOperation_Adapter_Service_ICapture {

	const RELEASE_SERVICE_PATH = 'release.vsp';
	const AUTHORISE_SERVICE_PATH = 'authorise.vsp';
	

	public function capture(Customweb_Payment_Authorization_ITransaction $transaction){
		if (!($transaction instanceof Customweb_SagePay_Authorization_Transaction)) {
			throw new Exception("The given transaction is not instanceof Customweb_SagePay_Authorization_Transaction.");
		}
		$items = $transaction->getUncapturedLineItems();
		$this->partialCapture($transaction, $items, true);
	}
	
	public function partialCapture(Customweb_Payment_Authorization_ITransaction $transaction, $items, $close){
		if (!($transaction instanceof Customweb_SagePay_Authorization_Transaction)) {
			throw new Exception("The given transaction is not instanceof Customweb_SagePay_Authorization_Transaction.");
		}
		
		$amount = Customweb_Util_Invoice::getTotalAmountIncludingTax($items);

		// We have to force the closing of the transaction in case we have a deferred transaction and not a authorise transaction.
		if ($transaction->getAuthorizationMode() != Customweb_SagePay_Authorization_Transaction::AUTHORIZATION_MODE_AUTHORISE) {
			$close = true;
		}
		
		// Check the transaction state
		$transaction->partialCaptureByLineItemsDry($items, $close);
		$url = $this->getServiceUrl($transaction);
		$requestParameters = $this->getServiceParameters($transaction, $amount);
		$response = $this->processServiceRequest(
				$url,
				$requestParameters
		);
		
		// Merge in the $response to the authorization parameters
		$params = $transaction->getAuthorizationParameters();
		$allowedParametersToUpdate = array(
			'AVSCV2' => 'AVSCV2',
			'AddressResult' => 'AddressResult',
			'PostCodeResult' => 'PostCodeResult',
			'CV2Result' => 'CV2Result',
		);
		foreach ($response as $key => $value) {
			if (!empty($value) && isset($allowedParametersToUpdate[$key])) {
				$params[$key] = $value;
			}
		}
		
		$captureItem = $transaction->partialCaptureByLineItems($items, $close);
		
		// Add additional parameters to the capture. This is required, because they are used later
		// for refunds.
		if ($transaction->getAuthorizationMode() == Customweb_SagePay_Authorization_Transaction::AUTHORIZATION_MODE_AUTHORISE) {
			$captureItem->setExternalTransactionId($response['VPSTxId']);
			$captureItem->setTransactionAuthenticationNumber($response['TxAuthNo']);
			$captureItem->setSecurityKey($response['SecurityKey']);
			$captureItem->setVendorTransactionCode($requestParameters['VendorTxCode']);
			$captureItem->setCaptureId($requestParameters['VendorTxCode']);
		}
		else {
			$captureItem->setExternalTransactionId($params['VPSTxId']);
			$captureItem->setTransactionAuthenticationNumber($params['TxAuthNo']);
			$captureItem->setSecurityKey($transaction->getSecurityKey());
			$captureItem->setVendorTransactionCode($params['VendorTxCode']);
		}
	}
	
	protected function getServiceUrl($transaction) {
		if ($transaction->getAuthorizationMode() == Customweb_SagePay_Authorization_Transaction::AUTHORIZATION_MODE_AUTHORISE) {
			return $this->getBaseUrl() . self::AUTHORISE_SERVICE_PATH;
		}
		else {
			return $this->getBaseUrl() . self::RELEASE_SERVICE_PATH;
		}
	}
	
	protected function getServiceParameters($transaction, $amount) {
		if ($transaction->getAuthorizationMode() == Customweb_SagePay_Authorization_Transaction::AUTHORIZATION_MODE_AUTHORISE) {
			$builder = new Customweb_SagePay_BackendOperation_Adapter_AuthoriseParameterBuilder($transaction, $this->getConfiguration());
		}
		else {
			$builder = new Customweb_SagePay_BackendOperation_Adapter_ReleaseParameterBuilder($transaction, $this->getConfiguration());
		}
		return $builder->buildParameters($amount);
	}
		
}