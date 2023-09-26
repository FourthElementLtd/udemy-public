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

require_once 'Customweb/Payment/Authorization/ITransactionContext.php';

require_once 'Customweb/SagePay/Authorization/AbstractParameterBuilder.php';
require_once 'Customweb/Util/Url.php';
require_once 'Customweb/Payment/Util.php';
require_once 'Customweb/Util/String.php';
require_once 'Customweb/SagePay/Util.php';

class Customweb_SagePay_Authorization_Server_PayPalCallbackParameterBuilder extends Customweb_SagePay_Authorization_AbstractParameterBuilder
{
	
	public function __construct(Customweb_SagePay_Authorization_Transaction $transaction, Customweb_SagePay_Configuration $configuration, Customweb_DependencyInjection_IContainer $container){
		parent::__construct($transaction, $configuration, $container, array());
	}
	
	/**
	 * 
	 * @param boolean $accept True, if the payment should be accepted.
	 */
	public function buildParameters($accept) {
		
		$paymentMethod = Customweb_SagePay_Method_Factory::getMethod($this->getTransaction()->getPaymentMethod(), $this->container);
		$parameters = $this->getProtocolVersionParameters();
		
		$authorizationParameters = $this->getTransaction()->getAuthorizationParameters();
		
		$parameters['TxType'] = 'COMPLETE';
		$parameters['VPSTxId'] = $authorizationParameters['VPSTxId'];
		$parameters['Amount'] = Customweb_SagePay_Util::formatAmount(
			$this->getOrderContext()->getOrderAmountInDecimals(),
			$this->getOrderContext()->getCurrencyCode()
		);
		if ($accept) {
			$parameters['Accept'] = 'YES';
		}
		else {
			$parameters['Accept'] = 'NO';
		}

		return $parameters;
	}


}