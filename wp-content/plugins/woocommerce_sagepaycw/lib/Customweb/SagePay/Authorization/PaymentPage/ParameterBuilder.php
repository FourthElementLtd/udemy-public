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

class Customweb_SagePay_Authorization_PaymentPage_ParameterBuilder extends Customweb_SagePay_Authorization_AbstractParameterBuilder
{

	public function buildParameters() {

		$parameters = array_merge(
			$this->getProtocolVersionParameters(),
			$this->getReferrerParameters(),
			$this->getProcessingTypeParameters(),
			$this->getVendorParameters(),
			$this->getTransactionIdParameters(),
			$this->getTransactionAmountParameters(),
			$this->getTransactionDescriptionParameters(),
			$this->getReactionUrlParameters(),
			$this->getCreateTokenParameters(),
			$this->getCustomerAddressParameters(),
			$this->getLanguageParameters(),
			$this->getBasketParamerters(),
			$this->getAccountTypeParameters(),
			$this->getGiftAidParameters()
		);
		return $parameters;
	}
}