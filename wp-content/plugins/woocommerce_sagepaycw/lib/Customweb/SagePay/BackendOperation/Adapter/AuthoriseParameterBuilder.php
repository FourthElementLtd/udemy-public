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

require_once 'Customweb/SagePay/AbstractMaintenanceParameterBuilder.php';
require_once 'Customweb/SagePay/Util.php';
require_once 'Customweb/I18n/Translation.php';
require_once 'Customweb/Util/String.php';


class Customweb_SagePay_BackendOperation_Adapter_AuthoriseParameterBuilder extends Customweb_SagePay_AbstractMaintenanceParameterBuilder
{

	public function buildParameters($amount) {
		$parameters = array_merge(
			$this->getProtocolVersionParameters(),
			$this->getNewVendorTransactionCodeParameter(),
			$this->getVendorParameters(),
			$this->getRelatedVendorTransactionCodeParameter(),
			$this->getRelatedExternalTransactionIdParameter(),
			$this->getRelatedSecurityKeyParameter()
		);
		$parameters['TxType'] = 'AUTHORISE';
		$parameters['Amount'] = Customweb_SagePay_Util::formatAmount($amount, $this->getTransactionContext()->getOrderContext()->getCurrencyCode());
		$parameters['Description'] = Customweb_I18n_Translation::__("Authorize Transaction ID: !transactionId", array('!transactionId' => $this->getTransaction()->getExternalTransactionId()));
		
		if ($this->getTransaction()->isMoto()) {
			// Deactivate CVC check in case of MoTo
			$parameters['ApplyAVSCV2'] = '2';
		}
		
		return $parameters;
	}
	
	protected function getNewVendorTransactionCodeParameter() {
		$number = count($this->getTransaction()->getCaptures());
		$params = $this->getTransaction()->getAuthorizationParameters();
		
		if (!isset($params['VendorTxCode'])) {
			throw new Exception("No 'VendorTxCode' parameter was provided by the authorization request.");
		}
		$id = $params['VendorTxCode'] . '_a_' . ($number + 1);
		return array(
			'VendorTxCode' => Customweb_Util_String::cutStartOff($id, 40),
		);
	}
	
}