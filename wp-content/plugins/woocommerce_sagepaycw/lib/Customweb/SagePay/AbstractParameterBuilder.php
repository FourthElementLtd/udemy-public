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

class Customweb_SagePay_AbstractParameterBuilder
{
	/**
	 * @var Customweb_SagePay_Configuration
	 */
	private $configuration = null;
	
	/**
	 * @var Customweb_SagePay_Authorization_Transaction
	 */
	private $transaction = null;
	
	public function __construct(Customweb_SagePay_Authorization_Transaction $transaction, Customweb_SagePay_Configuration $configuration) {
		$this->configuration = $configuration;
		$this->transaction = $transaction;	
	}
	
	/**
	 * @return Customweb_SagePay_Authorization_Transaction
	 */
	public function getTransaction() {
		return $this->transaction;
	}
	
	/**
	 * @return Customweb_SagePay_Configuration
	 */
	public function getConfiguration() {
		return $this->configuration;
	}
	
	/**
	 * @return Customweb_Payment_Authorization_ITransactionContext
	 */
	public function getTransactionContext() {
		return $this->getTransaction()->getTransactionContext();
	}
	
	/**
	 * @return Customweb_Payment_Authorization_IOrderContext
	 */
	public function getOrderContext() {
		return $this->getTransactionContext()->getOrderContext();
	}
	
	
	protected function getProtocolVersionParameters() {
		$version = $this->getConfiguration()->is3dV2() ? '4.00' : '3.00';
		return array(
			'VPSProtocol' => $version,
		);
	}
	
	protected function getVendorParameters() {
		return array(
			'Vendor' => $this->getConfiguration()->getVendorName(),
		);
	}
	
	
	
}