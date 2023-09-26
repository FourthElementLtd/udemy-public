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
require_once 'Customweb/Payment/BackendOperation/Adapter/Service/ICancel.php';
require_once 'Customweb/Util/Invoice.php';
require_once 'Customweb/SagePay/BackendOperation/Adapter/AbortParameterBuilder.php';
require_once 'Customweb/SagePay/BackendOperation/Adapter/CancelParameterBuilder.php';

/**
 * 
 * @author Thomas Hunziker
 * @Bean
 *
 */
class Customweb_SagePay_BackendOperation_Adapter_CancellationAdapter extends Customweb_SagePay_AbstractMaintenanceAdapter implements Customweb_Payment_BackendOperation_Adapter_Service_ICancel {
	
	const ABORD_SERVICE_PATH = 'abort.vsp';
	const CANCEL_SERVICE_PATH = 'cancel.vsp';
	const VOID_SERVICE_PATH = 'void.vsp';
	
	

	public function cancel(Customweb_Payment_Authorization_ITransaction $transaction) {
		if (!($transaction instanceof Customweb_SagePay_Authorization_Transaction)) {
			throw new Exception("The given transaction is not instanceof Customweb_SagePay_Authorization_Transaction.");
		}
		$transaction->cancelDry();
		
		$this->processServiceRequest(
			$this->getServiceUrl($transaction),
			$this->getServiceParameters($transaction)
		);
		
		$transaction->cancel();
	}
	
	protected function getServiceUrl(Customweb_SagePay_Authorization_Transaction $transaction) {
		if ($transaction->getAuthorizationMode() == Customweb_SagePay_Authorization_Transaction::AUTHORIZATION_MODE_AUTHORISE) {
			return $this->getBaseUrl() . self::CANCEL_SERVICE_PATH;
		}
		else {
			return $this->getBaseUrl() . self::ABORD_SERVICE_PATH;
		}
	}
	
	protected function getServiceParameters(Customweb_SagePay_Authorization_Transaction $transaction) {
		if ($transaction->getAuthorizationMode() == Customweb_SagePay_Authorization_Transaction::AUTHORIZATION_MODE_AUTHORISE) {
			$builder = new Customweb_SagePay_BackendOperation_Adapter_CancelParameterBuilder($transaction, $this->getConfiguration());
		}
		else {
			$builder = new Customweb_SagePay_BackendOperation_Adapter_AbortParameterBuilder($transaction, $this->getConfiguration());
		}
		return $builder->buildParameters();
	}
		
			
}