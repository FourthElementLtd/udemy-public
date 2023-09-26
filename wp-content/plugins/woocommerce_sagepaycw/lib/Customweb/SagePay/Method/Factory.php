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
require_once 'Customweb/SagePay/Method/PayPalMethod.php';
require_once 'Customweb/SagePay/Method/CreditCardMethod.php';

final class Customweb_SagePay_Method_Factory {
	
	private function __construct() {}
	
	/**
	 * 
	 * @return Customweb_SagePay_Method_DefaultMethod
	 */
	public static function getMethod(Customweb_Payment_Authorization_IPaymentMethod $method, Customweb_DependencyInjection_IContainer $container) {
		$paymentMethodName = $method->getPaymentMethodName();
		$paymentMethodName = str_replace('-', '', $paymentMethodName);
		switch(strtolower($paymentMethodName)) {
			
			case 'paypal':
				return new Customweb_SagePay_Method_PayPalMethod($method, $container);
				
			case 'visa':
			case 'mastercard':
			case 'debitmastercard':
			case 'debitvisa':
			case 'maestro':
			case 'visaelectron':
			case 'americanexpress':
			case 'diners':
			case 'jcb':
			case 'laser':
			case 'creditcard':
				return new Customweb_SagePay_Method_CreditCardMethod($method, $container);
				
			default:
				return new Customweb_SagePay_Method_DefaultMethod($method, $container);
		}
	}
	
}