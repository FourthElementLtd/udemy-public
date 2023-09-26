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

require_once 'Customweb/SagePay/AbstractParameterBuilder.php';

class Customweb_SagePay_AbstractMaintenanceParameterBuilder extends Customweb_SagePay_AbstractParameterBuilder
{
	/**
	 * Returns the parameters for a common maintenance request.
	 */
	protected function getCommonMaintenanceParameters() {
		return array_merge(
			$this->getProtocolVersionParameters(),
			$this->getVendorParameters(),
			$this->getVendorTransactionCodeParameter(),
			$this->getExternalTransactionIdParameter(),
			$this->getSecurityKeyParameter(),
			$this->getTransactionAuthenticationNumberParameter()
		);
	}
	
	/**
	 * 'VendorTxCode' parameter
	 *
	 * @throws Exception
	 */
	protected function getVendorTransactionCodeParameter() {
		$params = $this->getTransaction()->getAuthorizationParameters();
	
		if (!isset($params['VendorTxCode'])) {
			throw new Exception("No 'VendorTxCode' parameter was provided by the authorization request.");
		}
		return array(
			'VendorTxCode' => $params['VendorTxCode'],
		);
	}
	
	/**
	 * 'RelatedVendorTxCode' parameter
	 *
	 * @throws Exception
	 */
	protected function getRelatedVendorTransactionCodeParameter() {
		$params = $this->getTransaction()->getAuthorizationParameters();
	
		if (!isset($params['VendorTxCode'])) {
			throw new Exception("No 'VendorTxCode' parameter was provided by the authorization request.");
		}
		return array(
			'RelatedVendorTxCode' => $params['VendorTxCode'],
		);
	}
	
	/**
	 * 'VPSTxId' parameter
	 *
	 * @throws Exception
	 */
	protected function getExternalTransactionIdParameter() {
		$params = $this->getTransaction()->getAuthorizationParameters();
	
		if (!isset($params['VPSTxId'])) {
			throw new Exception("No 'VPSTxId' parameter was provided by the authorization request.");
		}
		return array(
			'VPSTxId' => $params['VPSTxId'],
		);
	}
	
	/**
	 * 'RelatedVPSTxId' parameter
	 *
	 * @throws Exception
	 */
	protected function getRelatedExternalTransactionIdParameter() {
		$params = $this->getTransaction()->getAuthorizationParameters();
	
		if (!isset($params['VPSTxId'])) {
			throw new Exception("No 'VPSTxId' parameter was provided by the authorization request.");
		}
		return array(
			'RelatedVPSTxId' => $params['VPSTxId'],
		);
	}
	
	/**
	 * 'SecurityKey' parameter
	 *
	 * @throws Exception
	 */
	protected function getSecurityKeyParameter() {
		return array(
			'SecurityKey' => $this->getTransaction()->getSecurityKey(),
		);
	}
	
	/**
	 * 'RelatedSecurityKey' parameter
	 *
	 * @throws Exception
	 */
	protected function getRelatedSecurityKeyParameter() {
		return array(
			'RelatedSecurityKey' => $this->getTransaction()->getSecurityKey(),
		);
	}
	
	/**
	 * 'TxAuthNo' parameter
	 *
	 * @throws Exception
	 */
	protected function getTransactionAuthenticationNumberParameter() {
		$params = $this->getTransaction()->getAuthorizationParameters();
	
		if (!isset($params['TxAuthNo'])) {
			throw new Exception("No 'TxAuthNo' parameter was provided by the authorization request.");
		}
		return array(
			'TxAuthNo' => $params['TxAuthNo'],
		);
	}
	/**
	 * 'RelatedTxAuthNo' parameter
	 *
	 * @throws Exception
	 */
	protected function getRelatedTransactionAuthenticationNumberParameter() {
		$params = $this->getTransaction()->getAuthorizationParameters();
	
		if (!isset($params['TxAuthNo'])) {
			throw new Exception("No 'TxAuthNo' parameter was provided by the authorization request.");
		}
		return array(
			'RelatedTxAuthNo' => $params['TxAuthNo'],
		);
	}
	
	
}