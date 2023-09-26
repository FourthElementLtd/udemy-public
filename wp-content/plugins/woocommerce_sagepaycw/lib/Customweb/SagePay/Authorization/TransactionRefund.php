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

require_once 'Customweb/Payment/Authorization/DefaultTransactionRefund.php';

class Customweb_SagePay_Authorization_TransactionRefund extends Customweb_Payment_Authorization_DefaultTransactionRefund {

	private $responseParameters = array();

	/**
	 * @var Customweb_SagePay_Authorization_TransactionCapture
	 */
	private $capture = null;
	
	public function getCapture(){
		return $this->capture;
	}
	
	public function setCapture(Customweb_SagePay_Authorization_TransactionCapture $capture){
		$this->capture = $capture;
		return $this;
	}
	
	public function setResponseParameters($parameters) {
		$this->responseParameters = $parameters;
		return $this;
	}
	
	public function getResponseParameters() {
		return $this->responseParameters;
	}
	
}