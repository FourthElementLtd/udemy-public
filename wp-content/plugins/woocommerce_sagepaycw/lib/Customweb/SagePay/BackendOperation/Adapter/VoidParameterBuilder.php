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


class Customweb_SagePay_BackendOperation_Adapter_VoidParameterBuilder extends Customweb_SagePay_AbstractMaintenanceParameterBuilder
{

	public function buildParameters() {
		$parameters = array_merge(
			$this->getProtocolVersionParameters(),
			$this->getVendorParameters(),
			$this->getVendorTransactionCodeParameter(),
			$this->getSecurityKeyParameter(),
			$this->getExternalTransactionIdParameter(),
			$this->getTransactionAuthenticationNumberParameter()
		);
		$parameters['TxType'] = 'VOID';
		
		return $parameters;
	}
	
}