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
require_once 'Customweb/SagePay/Util.php';

abstract class Customweb_SagePay_Authorization_AbstractRedirectionAdapter extends Customweb_SagePay_Authorization_AbstractAdapter
{
	const REGISTRATION_FILE_PATH = "vspserver-register.vsp";
	
	protected function getRegistrationUrl() {
		return $this->getBaseUrl() . self::REGISTRATION_FILE_PATH;
	}
	
	protected function validateNotification($responseParameters, $transaction) {
	
		if (strtoupper($responseParameters['VPSSignature']) == $this->calculateSignature($responseParameters, $transaction)) {
			throw new Exception(Customweb_I18n_Translation::__("The calcuatlated and the sent signature do not match."));
		}
	
		// TODO: Are other tests required?
	
	}
	
	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext){
		$fields = array();
		if ($this->getConfiguration()->isGiftAidEnabled()) {
			$fields[] = $this->getGiftAidElement();
		}
	
		return $fields;
	}
	
	protected function getPaymentPageUrl(Customweb_SagePay_Authorization_Transaction $transaction, $parameters) {
		$url = $this->getRegistrationUrl();
		$failedUrl = Customweb_Util_Url::appendParameters(
			$transaction->getTransactionContext()->getFailedUrl(),
			$transaction->getTransactionContext()->getCustomParameters()
		);
		
		$response = Customweb_SagePay_Util::sendRequest($url, $parameters);
		
		
	
		if (!isset($response['Status'])) {
			$transaction->setAuthorizationFailed("The response of the server is invalid. It contains no parameter 'Status'.");
			return $failedUrl;
		}
	
		switch(strtoupper($response['Status'])) {
			case 'MALFORMED':
				$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__(
					"The transaction registration could not be completed, due to a malformed request. Details: !details",
					array('!details' => $response['StatusDetail'])
				));
				return $failedUrl;
	
			case 'INVALID':
				$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__(
					"The transaction registration could not be completed, due to a missing parameter or an invalid parameter. Details: !details",
					array('!details' => $response['StatusDetail'])
				));
				return $failedUrl;
					
			case 'ERROR':
				$transaction->setAuthorizationFailed(Customweb_I18n_Translation::__(
					"The transaction registration could not be completed. Details: !details",
					array('!details' => $response['StatusDetail'])
				));
				return $failedUrl;
		}
	
		$transaction->setPaymentId($response['VPSTxId']);
		$transaction->setSecurityKey($response['SecurityKey']);
	
		if (!isset($response['NextURL'])) {
			$transaction->setAuthorizationFailed("The server does not return a 'NextURL' parameter.");
			return $failedUrl;
		}
	
		return $response['NextURL'];
	}
	
	protected function calculateSignature($responseParameters, Customweb_SagePay_Authorization_Transaction $transaction) {
		$parametersToConcat = array(
			'VPSTxId',
			'VendorTxCode',
			'Status',
			'TxAuthNo',
			'VendorName',
			'AVSCV2',
			'SecurityKey',
			'AddressResult',
			'PostCodeResult',
			'CV2Result',
			'GiftAid',
			'3DSecureStatus',
			'CAVV',
			'AddressStatus',
			'PayerStatus',
			'CardType',
			'Last4Digits',
			'DeclineCode',
			'ExpiryDate',
			'FraudResponse',
			'BankAuthCode'
		);
	
		$responseParameters['SecurityKey'] = $transaction->getSecurityKey();
	
		$message = '';
		foreach ($parametersToConcat as $key => $value) {
			if (isset($responseParameters[$key]) && !empty($responseParameters[$key])) {
				$message .= $value;
			}
		}
	
		return strtoupper(md5($message));
	}
}