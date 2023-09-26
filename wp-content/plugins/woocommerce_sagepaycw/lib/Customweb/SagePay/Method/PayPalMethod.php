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

require_once 'Customweb/SagePay/Method/DefaultMethod.php';
require_once 'Customweb/Payment/Util.php';

class Customweb_SagePay_Method_PayPalMethod extends Customweb_SagePay_Method_DefaultMethod {

	
	public function getAuthorizationParameters(Customweb_SagePay_Authorization_Transaction $transaction, array $formData, $authorizationMethod, Customweb_DependencyInjection_IContainer $container) {
		$parameters = parent::getAuthorizationParameters($transaction, $formData, $authorizationMethod, $container);
		
		$parameters['VPSProtocol'] = '3.00';


		$url = $container->getBean('Customweb_Payment_Endpoint_IAdapter')->getUrl("process", "server", array('cw_transaction_id' => $transaction->getExternalTransactionId()));
		
		$parameters['PayPalCallbackURL'] = $url;
		try {
			$ip = $container->getBean('Customweb_Core_Http_IRequest')->getRemoteAddress();
			if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
				//Sagepay only accepts IPv4 
				$parameters['ClientIPAddress'] =  $ip;
			}
		}
		catch(Exception $e) {
			// Ignore, we simply not provide any IP address.
		}
		
		return $parameters;
	}

}